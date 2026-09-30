<?php

namespace App\Console\Commands;

use App\Services\ClientPreservingAccountingRemediationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClientPreservingAccountingRemediationCommand extends Command
{
    protected $signature = 'accounting:remediate-client-preserving-data
        {--database= : Disposable remediation clone database}
        {--expected-fingerprint= : Deterministic pre-remediation fingerprint hash}
        {--confirmation= : Explicit execution confirmation token}
        {--execute : Apply the remediation; omitted means dry-run}
        {--failure-stage= : Test-only controlled failure stage}';

    protected $description = 'Guarded data-preserving client accounting remediation for the Phase 3H.2B clone rehearsal.';

    private const CONFIRMATION = 'I_UNDERSTAND_THIS_IS_THE_DISPOSABLE_REMEDIATION_CLONE';

    public function handle(ClientPreservingAccountingRemediationService $service): int
    {
        $database = (string) $this->option('database');
        if ($database === '') {
            $this->error('--database is required.');
            return 1;
        }

        $originalDatabase = (string) config('database.connections.mysql.database');
        if (in_array($database, ['accounting_latest', $originalDatabase], true)) {
            $this->error('Refusing to run against forensic/live database: ' . $database);
            return 1;
        }
        if ($database !== 'accounting_latest_remediation_test') {
            $this->error('Unexpected database. Expected accounting_latest_remediation_test.');
            return 1;
        }

        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $execute = (bool) $this->option('execute');
        $fingerprint = $service->fingerprint();
        $this->line('Database: ' . $database);
        $this->line('Current fingerprint: ' . $fingerprint['hash']);

        try {
            if (!$execute) {
                $plan = $service->dryRun($database, 1);
                $this->line('Mode: DRY-RUN');
                $this->line('Plan hash: ' . $plan['plan_hash']);
                $this->line('Blocking conditions: ' . count($plan['blocking_conditions']));
                if (!empty($plan['already_remediated'])) {
                    $this->line('Already remediated: yes');
                    $this->line('Post-remediation: ' . json_encode($plan['post_remediation'] ?? [], JSON_UNESCAPED_SLASHES));
                } else {
                    $this->line('Initial Stock sources: ' . $plan['actions']['initial_stock']['purchase_count']);
                    $this->line('Initial Stock total: ' . $plan['actions']['initial_stock']['total_value']);
                }
                $this->line('Dry-run manifest persisted as non-executed evidence when manifest table is available.');
                return $plan['blocking_conditions'] ? 1 : 0;
            }

            if ((string) $this->option('confirmation') !== self::CONFIRMATION) {
                $this->error('Execution confirmation token mismatch.');
                return 1;
            }
            $expected = (string) $this->option('expected-fingerprint');
            if ($expected === '') {
                $this->error('--expected-fingerprint is required for execution.');
                return 1;
            }

            $result = $service->execute($database, $expected, 1, $this->option('failure-stage') ?: null);
            $this->line(!empty($result['already_remediated']) ? 'Mode: ALREADY-REMEDIATED NO-OP' : 'Mode: EXECUTED');
            $this->line('Plan hash: ' . $result['plan_hash']);
            $this->line('Post-remediation: ' . json_encode($result['post_remediation'] ?? [], JSON_UNESCAPED_SLASHES));
            return 0;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
