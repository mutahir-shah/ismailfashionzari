<?php

namespace App\Console\Commands;

use App\Services\AccountingLiveRemediationApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ClientPreservingAccountingLiveRemediationCommand extends Command
{
    protected $signature = 'accounting:remediate-client-preserving-live
        {--database= : Explicit approved live database}
        {--approval= : Single-use approval UUID}
        {--expected-fingerprint= : Approved database fingerprint}
        {--expected-plan-hash= : Approved remediation plan hash}
        {--expected-dump-sha256= : Approved final dump SHA-256}
        {--expected-commit= : Approved application commit}
        {--confirmation= : Explicit live execution confirmation token}
        {--dry-run : Validate only; do not execute or consume approval}';

    protected $description = 'Single-use guarded live client accounting remediation command.';

    public function handle(AccountingLiveRemediationApprovalService $approvals): int
    {
        $database = (string) $this->option('database');
        if ($database === '') {
            $this->error('--database is required.');
            return 1;
        }

        if ($database === 'accounting_latest_remediation_test') {
            $this->error('The live command refuses the disposable remediation clone.');
            return 1;
        }

        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $options = [
            'database' => $database,
            'approval' => (string) $this->option('approval'),
            'expected-fingerprint' => (string) $this->option('expected-fingerprint'),
            'expected-plan-hash' => (string) $this->option('expected-plan-hash'),
            'expected-dump-sha256' => (string) $this->option('expected-dump-sha256'),
            'expected-commit' => (string) $this->option('expected-commit'),
            'confirmation' => (string) $this->option('confirmation'),
        ];

        try {
            if ((bool) $this->option('dry-run')) {
                $validated = $approvals->validate($options);
                $this->info('Live remediation approval dry-run passed.');
                $this->line('Database: ' . $database);
                $this->line('Approval: ' . $validated['approval']->uuid);
                $this->line('Fingerprint: ' . $validated['fingerprint']['hash']);
                $this->line('Plan hash: ' . $validated['plan']['plan_hash']);
                $this->line('No writes executed and approval not consumed.');
                return 0;
            }

            $result = $approvals->consumeAndExecute($options, auth()->id());
            $this->info('Live remediation executed and approval consumed.');
            $this->line('Approval: ' . $result['approval']->uuid);
            $this->line('Manifest ID: ' . ($result['approval']->remediation_manifest_id ?: 'not recorded'));
            return 0;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        } catch (Throwable $e) {
            $this->error('Live remediation failed safely: ' . $e->getMessage());
            return 1;
        }
    }
}
