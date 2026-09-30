<?php

namespace App\Services;

use App\Models\AccountingLiveRemediationApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AccountingLiveRemediationApprovalService
{
    public const CONFIRMATION = 'I_APPROVE_SINGLE_USE_LIVE_CLIENT_REMEDIATION';

    public function __construct(private ClientPreservingAccountingRemediationService $remediation)
    {
    }

    public function validate(array $options, bool $lock = false): array
    {
        $approval = $this->approval((string) $options['approval'], $lock);
        $this->assertApprovalState($approval);
        $this->assertOptionMatches($approval, $options);
        $this->assertEnvironment($approval, $options);

        $fingerprint = $this->remediation->fingerprint();
        if (!hash_equals($approval->database_fingerprint, $fingerprint['hash'])) {
            throw new RuntimeException('Current database fingerprint differs from the approved fingerprint.');
        }

        $this->assertExpectedPayload($approval->expected_key_row_counts ?: [], $fingerprint['payload'], 'row count / key fingerprint');
        $this->assertExpectedPayload($approval->expected_financial_balances ?: [], $fingerprint['payload'], 'financial baseline');

        $plan = $this->remediation->dryRun((string) $approval->target_database_name);
        if (!hash_equals($approval->remediation_plan_hash, (string) $plan['plan_hash'])) {
            throw new RuntimeException('Current remediation dry-run plan hash differs from approved plan hash.');
        }
        if (!empty($plan['blocking_conditions'])) {
            throw new RuntimeException('Current remediation dry-run has blocking conditions: ' . implode('; ', $plan['blocking_conditions']));
        }

        return compact('approval', 'fingerprint', 'plan');
    }

    public function consumeAndExecute(array $options, ?int $operatorId = null): array
    {
        return DB::transaction(function () use ($options, $operatorId) {
            $validated = $this->validate($options, true);
            /** @var AccountingLiveRemediationApproval $approval */
            $approval = $validated['approval'];

            try {
                $result = $this->remediation->execute(
                    (string) $approval->target_database_name,
                    (string) $approval->database_fingerprint,
                    $operatorId
                );

                $manifestId = DB::table('accounting_client_remediation_manifests')
                    ->where('database_name', $approval->target_database_name)
                    ->latest('id')
                    ->value('id');

                $approval->forceFill([
                    'status' => 'consumed',
                    'consumed_at' => now(),
                    'remediation_manifest_id' => $manifestId,
                    'failure_reason' => null,
                ])->save();

                return ['approval' => $approval->fresh(), 'result' => $result];
            } catch (Throwable $e) {
                $approval->forceFill([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ])->save();

                throw $e;
            }
        }, 1);
    }

    private function approval(string $uuid, bool $lock): AccountingLiveRemediationApproval
    {
        if ($uuid === '') {
            throw new RuntimeException('--approval is required.');
        }
        if (!Schema::hasTable('accounting_live_remediation_approvals')) {
            throw new RuntimeException('Live remediation approval table is missing.');
        }

        $query = AccountingLiveRemediationApproval::where('uuid', $uuid);
        if ($lock) {
            $query->lockForUpdate();
        }

        $approval = $query->first();
        if (!$approval) {
            throw new RuntimeException('Approval does not exist.');
        }

        return $approval;
    }

    private function assertApprovalState(AccountingLiveRemediationApproval $approval): void
    {
        if ($approval->status === 'revoked') {
            throw new RuntimeException('Approval was revoked: ' . ($approval->revocation_reason ?: 'no reason recorded'));
        }
        if ($approval->status !== 'approved') {
            throw new RuntimeException('Approval is not approved.');
        }
        if ($approval->expires_at && $approval->expires_at->isPast()) {
            throw new RuntimeException('Approval expired.');
        }
        if ($approval->consumed_at) {
            throw new RuntimeException('Approval was already consumed.');
        }
        if (!$approval->backup_restore_test_confirmed) {
            throw new RuntimeException('Backup restore-test proof is absent.');
        }
        if (!$approval->approved_at) {
            throw new RuntimeException('Approval timestamp is absent.');
        }
    }

    private function assertOptionMatches(AccountingLiveRemediationApproval $approval, array $options): void
    {
        $pairs = [
            'database' => 'target_database_name',
            'expected-fingerprint' => 'database_fingerprint',
            'expected-plan-hash' => 'remediation_plan_hash',
            'expected-dump-sha256' => 'dump_sha256',
            'expected-commit' => 'expected_application_commit',
        ];

        foreach ($pairs as $option => $column) {
            if ((string) ($options[$option] ?? '') === '') {
                throw new RuntimeException("--{$option} is required.");
            }
            if (!hash_equals((string) $approval->{$column}, (string) $options[$option])) {
                throw new RuntimeException("--{$option} does not match the approval.");
            }
        }

        if ((string) ($options['confirmation'] ?? '') !== self::CONFIRMATION) {
            throw new RuntimeException('Execution confirmation token mismatch.');
        }
    }

    private function assertEnvironment(AccountingLiveRemediationApproval $approval, array $options): void
    {
        $database = (string) $options['database'];
        if ($database !== $approval->target_database_name) {
            throw new RuntimeException('Database name differs from approval target.');
        }

        if ($database !== (string) config('database.connections.mysql.database')) {
            throw new RuntimeException('Current database connection is not the explicitly approved target.');
        }

        if ($database === 'accounting_latest_remediation_test') {
            throw new RuntimeException('Live command refuses the disposable remediation clone.');
        }

        if ($approval->maintenance_mode_required && !app()->isDownForMaintenance()) {
            throw new RuntimeException('Maintenance mode is disabled.');
        }

        $commit = $this->currentCommit();
        if ($commit !== '' && !hash_equals((string) $approval->expected_application_commit, $commit)) {
            throw new RuntimeException('Deployed commit differs from approval.');
        }

        if ($this->hasPendingMigrations()) {
            throw new RuntimeException('Unapproved migrations are pending.');
        }
    }

    private function assertExpectedPayload(array $expected, array $actual, string $label): void
    {
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                throw new RuntimeException("Approved {$label} key is unknown: {$key}.");
            }
            if ((string) $actual[$key] !== (string) $value) {
                throw new RuntimeException("Approved {$label} changed for {$key}: expected {$value}, found {$actual[$key]}.");
            }
        }
    }

    private function currentCommit(): string
    {
        try {
            $process = new Process(['git', 'rev-parse', 'HEAD'], base_path());
            $process->run();
            return $process->isSuccessful() ? trim($process->getOutput()) : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function hasPendingMigrations(): bool
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            return count($migrator->getPendingMigrations($files, $ran)) > 0;
        } catch (Throwable) {
            return true;
        }
    }
}
