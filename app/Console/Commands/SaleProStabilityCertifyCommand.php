<?php

namespace App\Console\Commands;

use App\Models\Biller;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Stability\AccountingInventoryGate;
use App\Services\Stability\ConcurrencyStabilityTester;
use App\Services\Stability\DataTableContractTester;
use App\Services\Stability\LogCertificationScanner;
use App\Services\Stability\StabilitySurfaceRegistry;
use App\Services\Stability\SurfaceDiscoveryAudit;
use App\Services\Stability\WorkflowStabilityTester;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SaleProStabilityCertifyCommand extends Command
{
    protected $signature = 'salepro:stability-certify
                            {--repeat=10 : Number of iterations for stability-critical surfaces}
                            {--skip-slow : Skip heavy accounting and concurrency re-runs}
                            {--with-modules : Include optional module surfaces}
                            {--with-browser : Attempt real browser certification}
                            {--report=storage/app/stability-report.json : Path to export JSON report}';

    protected $description = 'Execute the automated Production Stability Certification release gate.';

    private int $httpRequests = 0;
    private int $repeatIterations = 0;
    private int $workflowTransactions = 0;
    private int $http500Count = 0;
    private int $serverSnappedCount = 0;
    private int $invalidJsonCount = 0;
    private int $datatableFailures = 0;
    private int $ajaxFailures = 0;
    private int $deadlocks = 0;
    private int $partialCommits = 0;
    private int $warehouseIsolationFailures = 0;

    private array $durations = [];
    private array $requestLogs = [];
    private array $attemptedSurfaces = [];
    private array $passedSurfaces = [];
    private array $skippedSurfaces = [];
    private array $failedSurfaces = [];
    private array $failureEvidence = [];

    public function handle(
        SurfaceDiscoveryAudit $discoveryAudit,
        DataTableContractTester $dtTester,
        ConcurrencyStabilityTester $concurrencyTester,
        WorkflowStabilityTester $workflowTester,
        LogCertificationScanner $logScanner,
        AccountingInventoryGate $accountingGate
    ): int {
        $this->info("==================================================");
        $this->info("  SALEPRO PRODUCTION STABILITY CERTIFICATION");
        $this->info("==================================================\n");

        $repeat = max(1, (int) $this->option('repeat'));
        $skipSlow = (bool) $this->option('skip-slow');
        $withModules = (bool) $this->option('with-modules');
        $withBrowser = (bool) $this->option('with-browser');
        $reportPath = $this->option('report');

        // 1. Safety Guard: Check Environment
        $env = app()->environment();
        $dbName = DB::connection()->getDatabaseName();
        $this->line("Environment: <comment>{$env}</comment> | Database: <comment>{$dbName}</comment>");

        if ($env === 'production') {
            $this->error("Safety violation: Destructive certification cannot run in production environment.");
            return 1;
        }

        // 2. Snapshot log offset
        $logScanner->snapshot();

        // 3. Load Fixtures (Admin User, Restricted User, Warehouses)
        $fixtures = $this->ensureFixtures();
        $adminUser = $fixtures['admin'];
        $restrictedUser = $fixtures['restricted'];
        $warehouse1 = $fixtures['wh1'];
        $warehouse2 = $fixtures['wh2'];

        // 4. Phase 4: Discovery & Coverage Audit
        $this->line("Auditing surface coverage and discovering routes...");
        $surfaces = StabilitySurfaceRegistry::getSurfaces();
        $auditResult = $discoveryAudit->audit($surfaces);

        if (!$auditResult['passed']) {
            $this->error("FAIL: Discovered unregistered server-side DataTables!");
            foreach ($auditResult['unregistered_surfaces'] as $unregistered) {
                $this->line(" - Unregistered: <error>{$unregistered}</error>");
            }
        }

        // 5. Execute Registered Surfaces (Pages, DataTables, AJAX)
        $this->line("Executing registered surfaces (repeat = {$repeat})...");

        foreach ($surfaces as $surfaceId => $surface) {
            // Check module prerequisite
            if (!empty($surface['module']) && !$withModules) {
                $this->skippedSurfaces[] = $surfaceId;
                continue;
            }

            $isRepeatCritical = !empty($surface['repeat_critical']);
            $surfaceIterations = $isRepeatCritical ? $repeat : 1;
            $this->line("  - Testing: <comment>{$surface['name']}</comment> ({$surface['uri']})");

            for ($iter = 1; $iter <= $surfaceIterations; $iter++) {
                $this->repeatIterations++;
                $type = $surface['type'];

                $requestLogEntry = [
                    'surface_id' => $surfaceId,
                    'name' => $surface['name'],
                    'uri' => $surface['uri'],
                    'type' => $type,
                    'iteration' => $iter,
                    'duration_ms' => 0.0,
                    'classification' => 'NORMAL',
                ];

                if ($type === StabilitySurfaceRegistry::TYPE_DATATABLE) {
                    $this->httpRequests++;
                    $dtRes = $dtTester->testSurface($surface, $adminUser, $warehouse1);
                    $durationMs = $dtRes['duration_ms'];
                    $this->durations[] = $durationMs;
                    $requestLogEntry['duration_ms'] = $durationMs;
                    $requestLogEntry['classification'] = $durationMs >= 5000 ? 'VERY SLOW' : ($durationMs >= 2000 ? 'SLOW' : 'NORMAL');
                    $this->requestLogs[] = $requestLogEntry;

                    if ($dtRes['status'] >= 500) {
                        $this->http500Count++;
                    }

                    if (!$dtRes['passed']) {
                        $this->datatableFailures++;
                        $this->failedSurfaces[$surfaceId] = $surface['name'];
                        $this->failureEvidence[] = [
                            'surface' => $surfaceId,
                            'uri' => $surface['uri'],
                            'iteration' => $iter,
                            'status' => $dtRes['status'],
                            'failures' => $dtRes['contract_failures'],
                            'duration_ms' => $dtRes['duration_ms'],
                        ];
                    }
                } elseif ($type === StabilitySurfaceRegistry::TYPE_READ_PAGE || $type === StabilitySurfaceRegistry::TYPE_AJAX_READ) {
                    $this->httpRequests++;
                    $startTime = microtime(true);
                    $userToDispatch = ($surfaceId === 'restaurant.page.pos') ? ($fixtures['restaurantStaff'] ?? $adminUser) : $adminUser;
                    $resp = $this->dispatchSimpleRequest($surface['uri'], $surface['method'], $userToDispatch, $type === StabilitySurfaceRegistry::TYPE_AJAX_READ);
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $this->durations[] = $durationMs;
                    $requestLogEntry['duration_ms'] = $durationMs;
                    $requestLogEntry['classification'] = $durationMs >= 5000 ? 'VERY SLOW' : ($durationMs >= 2000 ? 'SLOW' : 'NORMAL');
                    $this->requestLogs[] = $requestLogEntry;

                    $status = $resp['status'];
                    $content = $resp['content'];

                    if ($status >= 500) {
                        $this->http500Count++;
                        $this->failedSurfaces[$surfaceId] = $surface['name'];
                    }

                    if (stripos($content, 'Server snapped') !== false) {
                        $this->serverSnappedCount++;
                        $this->failedSurfaces[$surfaceId] = $surface['name'];
                    }

                    if ($type === StabilitySurfaceRegistry::TYPE_AJAX_READ) {
                        $json = json_decode($content, true);
                        if (!is_array($json)) {
                            $this->invalidJsonCount++;
                            $this->ajaxFailures++;
                            $this->failedSurfaces[$surfaceId] = $surface['name'];
                        }
                    }

                    $expectedStatus = $surface['expected_status'] ?? 200;
                    $statusPassed = is_array($expectedStatus) ? in_array($status, $expectedStatus) : ($status === $expectedStatus);
                    if (!$statusPassed) {
                        $this->failedSurfaces[$surfaceId] = $surface['name'];
                        $this->failureEvidence[] = [
                            'surface' => $surfaceId,
                            'uri' => $surface['uri'],
                            'iteration' => $iter,
                            'status' => $status,
                            'expected_status' => $expectedStatus,
                            'duration_ms' => $durationMs,
                        ];
                    }
                }
            }

            $this->attemptedSurfaces[] = $surfaceId;
            if (!isset($this->failedSurfaces[$surfaceId])) {
                $this->passedSurfaces[] = $surfaceId;
            }
        }

        // 6. Execute Transactional Workflow Scenarios
        $this->line("Executing transactional workflow scenarios (Sale Returns, Purchase Returns, POS)...");
        $workflowResult = $workflowTester->runMatrix($adminUser, $restrictedUser, $warehouse1, $warehouse2, $skipSlow ? 1 : $repeat);
        $this->workflowTransactions += $workflowResult['workflows_run'];

        if (!$workflowResult['passed']) {
            foreach ($workflowResult['failures'] as $wfFail) {
                $this->failureEvidence[] = ['workflow_failure' => $wfFail];
            }
        }

        // 7. Execute Concurrency Stability Scenarios
        if (!$skipSlow) {
            $this->line("Executing concurrency and race-condition checks...");
            $concResult = $concurrencyTester->runScenarios($adminUser, $warehouse1);
            $this->deadlocks += $concResult['deadlocks'];
            if (!$concResult['passed']) {
                foreach ($concResult['failures'] as $cFail) {
                    $this->failureEvidence[] = ['concurrency_failure' => $cFail];
                }
            }
        }

        // 8. Log Offset Scan
        $logResult = $logScanner->scan();
        $unexpectedLogErrors = $logResult['error_count'];

        // 9. Accounting & Inventory Gate (Runs after workflow cleanup)
        $this->line("Evaluating Accounting & Inventory gate...");
        $gateResult = $accountingGate->checkGate();

        // 10. Performance / Timing Calculations & Top 10 Slowest
        sort($this->durations);
        $countDurations = count($this->durations);
        $p95Index = $countDurations > 0 ? (int) floor(0.95 * ($countDurations - 1)) : 0;
        $minDuration = $countDurations > 0 ? $this->durations[0] : 0.0;
        $maxDuration = $countDurations > 0 ? $this->durations[$countDurations - 1] : 0.0;
        $p95Duration = $countDurations > 0 ? $this->durations[$p95Index] : 0.0;

        usort($this->requestLogs, fn($a, $b) => $b['duration_ms'] <=> $a['duration_ms']);
        $top10Slowest = array_slice($this->requestLogs, 0, 10);
        $slowCount = count(array_filter($this->durations, fn($d) => $d >= 2000 && $d < 5000));
        $verySlowCount = count(array_filter($this->durations, fn($d) => $d >= 5000));

        // 11. Final Certification Evaluation
        $isCertified = (
            $auditResult['passed'] &&
            $this->http500Count === 0 &&
            $this->serverSnappedCount === 0 &&
            $this->invalidJsonCount === 0 &&
            $this->datatableFailures === 0 &&
            $this->ajaxFailures === 0 &&
            $this->deadlocks === 0 &&
            $this->partialCommits === 0 &&
            $this->warehouseIsolationFailures === 0 &&
            $unexpectedLogErrors === 0 &&
            $gateResult['passed'] &&
            count($this->failedSurfaces) === 0 &&
            $workflowResult['passed']
        );

        // 12. Format and Display Report
        $this->newLine();
        $this->line("SALEPRO PRODUCTION STABILITY CERTIFICATION\n");

        $this->line("Coverage");
        $this->line("---------------------------------");
        $this->line(sprintf("Discovered critical surfaces:    %5d", $auditResult['discovered_count']));
        $this->line(sprintf("Registered surfaces:             %5d", count($surfaces)));
        $this->line(sprintf("Attempted surfaces:              %5d", count($this->attemptedSurfaces)));
        $this->line(sprintf("Passed surfaces:                 %5d", count($this->passedSurfaces)));
        $this->line(sprintf("Failed surfaces:                 %5d", count($this->failedSurfaces)));
        $this->line(sprintf("Unregistered surfaces:            %5d", $auditResult['unregistered_count']));
        $this->line(sprintf("Required surfaces skipped:        %5d", count($this->skippedSurfaces)));

        $this->newLine();
        $this->line("Execution");
        $this->line("---------------------------------");
        $this->line(sprintf("HTTP requests:                 %7s", number_format($this->httpRequests)));
        $this->line(sprintf("Repeat iterations:             %7s", number_format($this->repeatIterations)));
        $this->line(sprintf("Workflow transactions:         %7s", number_format($this->workflowTransactions)));
        $this->line(sprintf("Latency (min / p95 / max ms):    %.1f / %.1f / %.1f", $minDuration, $p95Duration, $maxDuration));
        $this->line(sprintf("Slow requests (>2s / >5s):       %5d / %5d", $slowCount, $verySlowCount));

        $this->newLine();
        $this->line("Failures");
        $this->line("---------------------------------");
        $this->line(sprintf("HTTP 500/502/503/504:             %5d", $this->http500Count));
        $this->line(sprintf("Server Snapped responses:         %5d", $this->serverSnappedCount));
        $this->line(sprintf("Invalid JSON responses:           %5d", $this->invalidJsonCount));
        $this->line(sprintf("DataTables contract failures:     %5d", $this->datatableFailures));
        $this->line(sprintf("Unexpected AJAX failures:         %5d", $this->ajaxFailures));
        $this->line(sprintf("Unexpected Laravel exceptions:    %5d", $unexpectedLogErrors));
        $this->line(sprintf("Deadlocks/lock timeouts:           %5d", $this->deadlocks));
        $this->line(sprintf("Partial commits:                   %5d", $this->partialCommits));
        $this->line(sprintf("Warehouse isolation failures:     %5d", $this->warehouseIsolationFailures));

        $this->newLine();
        $this->line("Data Integrity");
        $this->line("---------------------------------");
        $this->line(sprintf("Negative warehouse stock:         %5d", $gateResult['negative_stock_count']));
        $this->line(sprintf("Product/warehouse mismatches:      %5d", $gateResult['stock_mismatch_count']));
        $this->line(sprintf("Overpaid purchases:                %5d", $gateResult['overpaid_purchases_count']));
        $this->line(sprintf("Referential integrity failures:    %5d", $gateResult['referential_integrity_failures'] ?? 0));
        $this->line(sprintf("Accounting Critical failures:      %5d", $gateResult['accounting_critical_failures']));
        $this->line(sprintf("Accounting High failures:          %5d", $gateResult['accounting_high_failures']));

        $this->newLine();
        $this->line("Latency Top 10 Slowest Requests");
        $this->line("---------------------------------");
        foreach ($top10Slowest as $idx => $slowReq) {
            $flagTag = $slowReq['classification'] === 'VERY SLOW' ? '<error>[VERY SLOW]</error>' : ($slowReq['classification'] === 'SLOW' ? '<comment>[SLOW]</comment>' : '[NORMAL]');
            $this->line(sprintf(" %2d. %-32s (%-30s) : %7.1f ms %s", $idx + 1, substr($slowReq['surface_id'], 0, 32), substr($slowReq['uri'], 0, 30), $slowReq['duration_ms'], $flagTag));
        }

        $this->newLine();
        $this->line("Browser");
        $this->line("---------------------------------");
        $this->line("Real browser runner:          NOT INSTALLED");
        $this->line("Real-browser certification:       NOT RUN");

        $this->newLine();
        $this->line("FINAL STATUS:");
        if ($isCertified) {
            $this->info("SERVER-SIDE STABILITY CERTIFIED");
            $this->comment("FULL RELEASE CERTIFICATION PENDING REAL-BROWSER/HUMAN QA");
        } else {
            $this->error("NOT CERTIFIED — FAILURES DETECTED");
        }

        // 13. Export JSON report
        if ($reportPath) {
            $reportData = [
                'timestamp' => date('c'),
                'is_certified' => $isCertified,
                'final_status' => $isCertified ? 'SERVER-SIDE STABILITY CERTIFIED' : 'NOT CERTIFIED',
                'browser_status' => 'REAL-BROWSER CERTIFICATION NOT RUN',
                'coverage' => [
                    'discovered' => $auditResult['discovered_count'],
                    'registered' => count($surfaces),
                    'attempted' => count($this->attemptedSurfaces),
                    'passed' => count($this->passedSurfaces),
                    'failed' => count($this->failedSurfaces),
                    'skipped' => count($this->skippedSurfaces),
                    'unregistered' => $auditResult['unregistered_count'],
                ],
                'execution' => [
                    'http_requests' => $this->httpRequests,
                    'repeat_iterations' => $this->repeatIterations,
                    'workflow_transactions' => $this->workflowTransactions,
                    'latency_ms' => [
                        'min' => $minDuration,
                        'p95' => $p95Duration,
                        'max' => $maxDuration,
                        'slow_count_gt_2s' => $slowCount,
                        'very_slow_count_gt_5s' => $verySlowCount,
                        'top_10_slowest' => $top10Slowest,
                    ],
                ],
                'failures' => [
                    'http_500' => $this->http500Count,
                    'server_snapped' => $this->serverSnappedCount,
                    'invalid_json' => $this->invalidJsonCount,
                    'datatable_failures' => $this->datatableFailures,
                    'ajax_failures' => $this->ajaxFailures,
                    'log_exceptions' => $unexpectedLogErrors,
                    'deadlocks' => $this->deadlocks,
                    'failed_surfaces' => $this->failedSurfaces,
                    'evidence' => $this->failureEvidence,
                ],
                'data_integrity' => $gateResult,
            ];

            $fullReportPath = base_path($reportPath);
            File::ensureDirectoryExists(dirname($fullReportPath));
            File::put($fullReportPath, json_encode($reportData, JSON_PRETTY_PRINT));
            $this->line("\nDetailed JSON evidence written to: <info>{$reportPath}</info>");
        }

        return $isCertified ? 0 : 1;
    }

    private function dispatchSimpleRequest(string $uri, string $method, User $user, bool $ajax = false): array
    {
        Auth::login($user);
        $server = [];
        if ($ajax) {
            $server['HTTP_ACCEPT'] = 'application/json';
            $server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }

        $request = Request::create(url($uri), strtoupper($method), [], [], [], $server);
        $request->setUserResolver(fn() => $user);
        Auth::setUser($user);
        if (app()->bound('session.store')) {
            $session = app('session.store');
            if (!$session->isStarted()) {
                $session->start();
            }
            $session->put('login_web_' . sha1(User::class), $user->id);
            $token = $session->token();
            $request->setLaravelSession($session);
            $request->headers->set('X-CSRF-TOKEN', $token);
            $request->headers->set('X-XSRF-TOKEN', $token);
            $request->request->set('_token', $token);
        }

        ob_start();
        try {
            $response = app()->handle($request);
            $echoed = ob_get_clean();

            $status = 200;
            $content = '';

            if (method_exists($response, 'getStatusCode')) {
                $status = $response->getStatusCode();
                $content = $response->getContent();
            } else {
                $content = is_string($response) ? $response : $echoed;
            }

            if (empty($content) && !empty($echoed)) {
                $content = $echoed;
            }

            return ['status' => $status, 'content' => $content];
        } catch (\Throwable $e) {
            ob_end_clean();
            return ['status' => 500, 'content' => $e->getMessage()];
        }
    }

    private function ensureFixtures(): array
    {
        DB::table('roles')->updateOrInsert(
            ['id' => 4],
            ['name' => 'staff', 'guard_name' => 'web', 'is_active' => true]
        );
        DB::table('roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Admin', 'guard_name' => 'web', 'is_active' => true]
        );
        $staffRole = \Spatie\Permission\Models\Role::findOrFail(4);
        $adminRole = \Spatie\Permission\Models\Role::findOrFail(1);

        $staffPermissions = \Spatie\Permission\Models\Permission::whereIn('name', [
            'sales-index', 'sales-add', 'sales-edit', 'sales-delete', 'pos-sale', 'restaurant-pos'
        ])->get();
        if ($staffPermissions->isNotEmpty()) {
            $staffRole->syncPermissions($staffPermissions);
        }

        $wh1 = Warehouse::firstOrCreate(
            ['id' => 1],
            ['name' => 'Main Warehouse', 'phone' => '123456', 'address' => '123 Main St', 'is_active' => true]
        );

        $wh2 = Warehouse::firstOrCreate(
            ['id' => 2],
            ['name' => 'Secondary Warehouse', 'phone' => '654321', 'address' => '456 Side St', 'is_active' => true]
        );

        $whRest = Warehouse::firstOrCreate(
            ['name' => 'Restaurant Stability Warehouse'],
            ['phone' => '999888', 'address' => '789 Dining Ave', 'pos_type' => 'restaurant', 'is_active' => true]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            ['name' => 'Admin Stability', 'password' => bcrypt('12345678'), 'phone' => '12345678', 'role_id' => 1, 'is_active' => true, 'is_deleted' => false]
        );
        $admin->role_id = 1;
        $admin->save();

        $restricted = User::firstOrCreate(
            ['email' => 'restricted@test.com'],
            ['name' => 'Restricted Stability', 'password' => bcrypt('12345678'), 'phone' => '87654321', 'role_id' => 4, 'warehouse_id' => $wh1->id, 'is_active' => true, 'is_deleted' => false]
        );
        $restricted->role_id = 4;
        $restricted->warehouse_id = $wh1->id;
        $restricted->save();

        $restaurantStaff = User::firstOrCreate(
            ['email' => 'restaurant_staff@test.com'],
            ['name' => 'Restaurant Staff', 'password' => bcrypt('12345678'), 'phone' => '99988877', 'role_id' => 4, 'warehouse_id' => $whRest->id, 'is_active' => true, 'is_deleted' => false]
        );
        $restaurantStaff->role_id = 4;
        $restaurantStaff->warehouse_id = $whRest->id;
        $restaurantStaff->save();

        return [
            'admin' => $admin,
            'restricted' => $restricted,
            'restaurantStaff' => $restaurantStaff,
            'wh1' => $wh1,
            'wh2' => $wh2,
            'whRest' => $whRest,
        ];
    }
}
