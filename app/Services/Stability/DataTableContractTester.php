<?php

namespace App\Services\Stability;

use App\Models\CustomField;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DataTableContractTester
{
    /**
     * Test a registered DataTable surface across multiple standard and edge-case contracts.
     *
     * @param array{
     *     id: string,
     *     name: string,
     *     uri: string,
     *     method: string,
     *     auth: string,
     *     expected_columns?: array<string>
     * } $surface
     * @param User $adminUser
     * @param Warehouse|null $warehouse
     * @return array{
     *     passed: bool,
     *     status: int,
     *     duration_ms: float,
     *     contract_failures: array<string>,
     *     warnings: array<string>,
     *     records_total: int,
     *     records_filtered: int,
     *     rows_checked: int
     * }
     */
    public function testSurface(array $surface, User $adminUser, ?Warehouse $warehouse = null): array
    {
        Auth::login($adminUser);
        $startTime = microtime(true);
        $failures = [];
        $warnings = [];
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $rowsChecked = 0;

        $uri = $surface['uri'];
        $method = strtoupper($surface['method']);
        $expectedColumns = $surface['expected_columns'] ?? [];

        // 1. Standard Populated Request
        $standardPayload = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [
                0 => ['column' => 1, 'dir' => 'desc'],
            ],
            'columns' => [
                0 => ['data' => 0, 'name' => '', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                1 => ['data' => 1, 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                2 => ['data' => 2, 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'warehouse_id' => $warehouse?->id ?? 0,
            'supplier_id' => 0,
            'customer_id' => 0,
            'user_id' => 0,
            'biller_id' => 0,
            'customer_group_id' => 0,
            'category_id' => 0,
            'brand_id' => 0,
            'unit_id' => 0,
            'courier_id' => 'All Courier',
            'status' => 0,
            'product_type' => 'all',
            'stock_filter' => 'all',
            'starting_date' => '2020-01-01',
            'ending_date' => date('Y-m-d', strtotime('+1 year')),
            'start_date' => '2020-01-01',
            'end_date' => date('Y-m-d', strtotime('+1 year')),
            'all_permission' => [
                'sales-index', 'sales-add', 'sales-edit', 'sales-delete',
                'purchases-index', 'purchases-add', 'purchases-edit', 'purchases-delete',
                'returns-index', 'returns-add', 'returns-edit', 'returns-delete',
                'products-index', 'products-add', 'products-edit', 'products-delete',
                'expenses-index', 'incomes-index',
            ],
        ];

        $res = $this->dispatchRequest($uri, $method, $standardPayload, $adminUser);
        $status = $res['status'];
        $content = $res['content'];
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($status !== 200) {
            $failures[] = "Expected HTTP 200, received HTTP {$status}. Content: " . substr($content, 0, 200);
            return [
                'passed' => false,
                'status' => $status,
                'duration_ms' => $durationMs,
                'contract_failures' => $failures,
                'warnings' => $warnings,
                'records_total' => 0,
                'records_filtered' => 0,
                'rows_checked' => 0,
            ];
        }

        $json = json_decode($content, true);

        if (!is_array($json)) {
            $failures[] = "Invalid JSON response payload: " . substr($content, 0, 200);
            return [
                'passed' => false,
                'status' => $status,
                'duration_ms' => $durationMs,
                'contract_failures' => $failures,
                'warnings' => $warnings,
                'records_total' => 0,
                'records_filtered' => 0,
                'rows_checked' => 0,
            ];
        }

        // Validate Root DataTables Schema
        if (!array_key_exists('recordsTotal', $json) && !array_key_exists('records_total', $json) && !array_key_exists('data', $json)) {
            $failures[] = "Missing DataTables root keys (recordsTotal/data). Found keys: " . implode(', ', array_keys($json));
        }

        $recordsTotal = (int) ($json['recordsTotal'] ?? $json['records_total'] ?? count($json['data'] ?? []));
        $recordsFiltered = (int) ($json['recordsFiltered'] ?? $json['records_filtered'] ?? $recordsTotal);
        $dataRows = $json['data'] ?? [];

        if (!is_array($dataRows)) {
            $failures[] = "DataTables 'data' attribute must be an array, got " . gettype($dataRows);
        } else {
            $rowsChecked = count($dataRows);
            // Verify expected column completeness on returned rows
            if (!empty($expectedColumns) && $rowsChecked > 0) {
                $firstRow = $dataRows[0];
                if (is_array($firstRow)) {
                    $isAssociative = array_keys($firstRow) !== range(0, count($firstRow) - 1);
                    if ($isAssociative) {
                        foreach ($expectedColumns as $col) {
                            if (!array_key_exists($col, $firstRow)) {
                                $warnings[] = "Column '{$col}' expected by table contract is missing in row dictionary.";
                            }
                        }
                    }
                }
            }
        }

        // 2. Empty Result Contract Test (search non-existent token)
        $emptyPayload = array_merge($standardPayload, [
            'draw' => 2,
            'search' => ['value' => '__NON_EXISTENT_STABILITY_SEARCH_TOKEN_12345__', 'regex' => 'false'],
        ]);
        $emptyRes = $this->dispatchRequest($uri, $method, $emptyPayload, $adminUser);
        if ($emptyRes['status'] !== 200) {
            $failures[] = "Empty result set search returned HTTP " . $emptyRes['status'];
        } else {
            $emptyJson = json_decode($emptyRes['content'], true);
            if (!is_array($emptyJson) || !isset($emptyJson['data']) || !is_array($emptyJson['data'])) {
                $failures[] = "Empty result set search returned malformed JSON structure";
            }
        }

        return [
            'passed' => count($failures) === 0,
            'status' => $status,
            'duration_ms' => $durationMs,
            'contract_failures' => $failures,
            'warnings' => $warnings,
            'records_total' => $recordsTotal,
            'records_filtered' => $recordsFiltered,
            'rows_checked' => $rowsChecked,
        ];
    }

    /**
     * Dispatch an internal request through Laravel's router capturing buffer.
     *
     * @return array{status: int, content: string}
     */
    private function dispatchRequest(string $uri, string $method, array $payload, User $user): array
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ];

        $request = Request::create(
            url($uri),
            $method,
            $payload,
            [],
            [],
            $server
        );

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

            if ($response instanceof JsonResponse || $response instanceof Response) {
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
}
