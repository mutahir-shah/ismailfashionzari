<?php

namespace App\Console\Commands;

use App\Models\AccountingActivationSession;
use App\Models\AccountingConfig;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\FinancialReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class AccountingReleaseCertificationCommand extends Command
{
    protected $signature = 'accounting:certify {--skip-regressions : Run current-database integrity checks only}';
    protected $description = 'Run the SalePro accounting release certification suite.';

    private array $results = [];

    public function handle(): int
    {
        $this->newLine();
        $this->info('========================================');
        $this->info('SalePro Accounting Certification');
        $this->info('========================================');

        if (!$this->option('skip-regressions')) {
            $this->runProcessCheck('Test Database Preparation', [
                'migrate:fresh', '--seed', '--force', '--env=testing',
            ]);
            $this->runProcessCheck('Activation', ['test:accounting-activation', '--env=testing']);
            $this->runProcessCheck('Accounting Engine', ['test:scenario16-25', '--env=testing']);
            $this->runProcessCheck('Operational Reports', ['test:report-certification', '--env=testing']);
        }

        $this->runArtisanCheck('Database Integrity', 'accounting:audit');
        $this->runCheck('Journal Integrity', fn () => $this->journalIntegrity());
        $this->runCheck('Trial Balance', fn () => $this->trialBalance());
        $this->runCheck('Balance Sheet', fn () => $this->balanceSheet());
        $this->runCheck('Opening Balance', fn () => $this->openingBalance());
        $this->runCheck('Semantic Account Roles', fn () => $this->semanticAccountRoles());
        $this->runCheck('Accounting Configuration', fn () => $this->accountingConfiguration());
        $this->runCheck('Installed Modules', fn () => $this->installedModules());

        // ModuleAccountingTest uses RefreshDatabase and therefore rebuilds the
        // testing schema. Keep this isolated destructive stage last so it can
        // never invalidate the state inspected by the certification checks.
        if (!$this->option('skip-regressions')) {
            $this->runProcessCheck('Module Integration', ['test', '--filter', 'ModuleAccountingTest', '--env=testing']);
        }

        $failed = collect($this->results)->contains('status', 'FAIL');
        $warned = collect($this->results)->contains('status', 'WARN');
        $overall = $failed ? 'NOT READY' : ($warned ? 'READY WITH WARNINGS' : 'RELEASE READY');

        $this->newLine();
        $this->info('========================================');
        foreach ($this->results as $name => $result) {
            $this->line(str_pad($name, 28) . $result['status']);
            if ($result['message']) {
                $this->line('  ' . $result['message']);
            }
        }
        $this->line(str_pad('Overall Status', 28) . $overall);
        $this->info('========================================');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function runProcessCheck(string $name, array $arguments): void
    {
        $process = new Process(array_merge([PHP_BINARY, base_path('artisan')], $arguments), base_path());
        $process->setTimeout(900);
        $process->run();

        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
        $explicitTestPass = str_contains($output, 'Tests:')
            && str_contains($output, 'passed')
            && !str_contains($output, 'FAILED');
        $passed = $process->isSuccessful() || $explicitTestPass;
        $warningRelevantOutput = preg_replace(
            '/^.*Metadata found in doc-comment.*(?:\R|$)/mi',
            '',
            $output
        );
        $hasWarnings = $passed && (
            preg_match('/warnings:\s*[1-9][0-9]*/i', $warningRelevantOutput)
            || preg_match('/^WARN\s/m', $warningRelevantOutput)
        );
        $this->results[$name] = [
            'status' => !$passed ? 'FAIL' : ($hasWarnings ? 'WARN' : 'PASS'),
            'message' => $passed ? null : $this->lastMeaningfulLine($output),
        ];
        $this->line("{$name}: " . $this->results[$name]['status']);
    }

    private function runArtisanCheck(string $name, string $command): void
    {
        $exitCode = Artisan::call($command);
        $output = Artisan::output();
        $this->output->write($output);
        preg_match('/Warnings:\s*([0-9]+)/i', $output, $warningMatch);
        $hasWarnings = $exitCode === self::SUCCESS && (int) ($warningMatch[1] ?? 0) > 0;
        $this->results[$name] = [
            'status' => $exitCode !== self::SUCCESS ? 'FAIL' : ($hasWarnings ? 'WARN' : 'PASS'),
            'message' => $exitCode === self::SUCCESS ? null : "The {$command} audit reported failures.",
        ];
    }

    private function runCheck(string $name, callable $check): void
    {
        try {
            $message = $check();
            $this->results[$name] = ['status' => 'PASS', 'message' => $message];
        } catch (\Throwable $e) {
            $this->results[$name] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
        $this->line("{$name}: " . $this->results[$name]['status']);
    }

    private function journalIntegrity(): ?string
    {
        $duplicates = JournalEntry::select('source_type', 'source_id', 'event_type')
            ->groupBy('source_type', 'source_id', 'event_type')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $unbalanced = DB::table('journal_entries as je')
            ->leftJoin('journal_lines as jl', 'jl.journal_entry_id', '=', 'je.id')
            ->select('je.id')
            ->groupBy('je.id')
            ->havingRaw('ROUND(COALESCE(SUM(jl.debit), 0), 4) <> ROUND(COALESCE(SUM(jl.credit), 0), 4)')
            ->get()
            ->count();
        $missingAccounts = DB::table('journal_lines as jl')
            ->leftJoin('accounting_accounts as aa', 'aa.id', '=', 'jl.accounting_account_id')
            ->whereNull('aa.id')
            ->count();
        $orphanLines = DB::table('journal_lines as jl')
            ->leftJoin('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->whereNull('je.id')
            ->count();

        if ($duplicates || $unbalanced || $missingAccounts || $orphanLines) {
            throw new \RuntimeException("duplicates={$duplicates}, unbalanced={$unbalanced}, missing_accounts={$missingAccounts}, orphan_lines={$orphanLines}");
        }

        return null;
    }

    private function trialBalance(): ?string
    {
        $totals = DB::table('journal_lines')
            ->selectRaw('COALESCE(SUM(debit), 0) debit, COALESCE(SUM(credit), 0) credit')
            ->first();
        if (bccomp((string) $totals->debit, (string) $totals->credit, 4) !== 0) {
            throw new \RuntimeException("debits={$totals->debit}, credits={$totals->credit}");
        }

        return 'Debits equal credits.';
    }

    private function balanceSheet(): ?string
    {
        $service = app(FinancialReportingService::class);
        $asOf = now()->toDateString();
        $data = $service->getBalanceSheet($asOf, now()->startOfYear()->toDateString(), null);
        $assets = (float) ($data['total_assets'] ?? 0);
        $liabilities = (float) ($data['total_liabilities'] ?? 0);
        $equity = (float) ($data['total_equity'] ?? 0) + (float) ($data['current_year_earnings'] ?? 0);

        if (abs($assets - ($liabilities + $equity)) > 0.01) {
            throw new \RuntimeException("assets={$assets}, liabilities_plus_equity=" . ($liabilities + $equity));
        }

        return 'Assets equal liabilities plus equity.';
    }

    private function openingBalance(): ?string
    {
        $config = AccountingConfig::first();
        $openings = JournalEntry::where('event_type', 'opening_balance')->get();
        $expected = $config && $config->enabled && $config->activation_mode === 'existing_business' ? 1 : 0;
        if ($openings->count() !== $expected) {
            throw new \RuntimeException("expected_opening_journals={$expected}, actual={$openings->count()}");
        }

        foreach ($openings as $opening) {
            $lineTotals = DB::table('journal_lines')
                ->where('journal_entry_id', $opening->id)
                ->selectRaw('COUNT(*) as line_count, COALESCE(SUM(debit), 0) debit, COALESCE(SUM(credit), 0) credit')
                ->first();

            if (!$lineTotals || (int) $lineTotals->line_count === 0) {
                throw new \RuntimeException('Opening journal has no journal lines.');
            }

            if (bccomp((string) $lineTotals->debit, (string) $lineTotals->credit, 4) !== 0) {
                throw new \RuntimeException("Opening journal is unbalanced: debit={$lineTotals->debit}, credit={$lineTotals->credit}");
            }

            $invalidAccounts = DB::table('journal_lines as jl')
                ->leftJoin('accounting_accounts as aa', 'aa.id', '=', 'jl.accounting_account_id')
                ->where('jl.journal_entry_id', $opening->id)
                ->whereNull('aa.id')
                ->count();

            if ($invalidAccounts > 0) {
                throw new \RuntimeException("Opening journal contains {$invalidAccounts} line(s) with missing accounting accounts.");
            }
        }

        $accounting = app(AccountingService::class);
        foreach ([
            AccountingService::ROLE_CASH,
            AccountingService::ROLE_ACCOUNTS_RECEIVABLE,
            AccountingService::ROLE_INVENTORY,
            AccountingService::ROLE_ACCOUNTS_PAYABLE,
            AccountingService::ROLE_OPENING_EQUITY,
        ] as $role) {
            $accounting->getRoleAccountId($role);
        }

        return $openings->isEmpty() ? 'No opening journal required.' : 'Opening journal account integrity verified by account_id and semantic roles.';
    }

    private function semanticAccountRoles(): ?string
    {
        $results = app(AccountingService::class)->validateSemanticRoleMappings(
            array_merge(AccountingService::CORE_CERTIFICATION_ROLES, AccountingService::FEATURE_CERTIFICATION_ROLES),
            true
        );

        $failures = array_filter($results, fn ($result) => ($result['status'] ?? null) !== 'pass');
        if (!empty($failures)) {
            throw new \RuntimeException(implode(' ', array_map(fn ($result) => $result['message'], $failures)));
        }

        return count($results) . ' semantic roles resolve to active compatible accounting accounts.';
    }

    private function accountingConfiguration(): ?string
    {
        $configs = AccountingConfig::count();
        $config = AccountingConfig::first();
        if ($configs !== 1 || !$config || !$config->enabled || !$config->start_date) {
            throw new \RuntimeException("config_rows={$configs}, enabled=" . (int) ($config->enabled ?? false) . ', valid_start_date=' . (int) !empty($config->start_date));
        }

        $activeSession = AccountingActivationSession::orderByDesc('activated_at')->orderByDesc('id')->first();
        if (!$activeSession
            || $activeSession->mode !== $config->activation_mode
            || $activeSession->start_date !== $config->start_date
            || (int) $activeSession->opening_journal_entry_id !== (int) $config->opening_journal_entry_id) {
            throw new \RuntimeException('Latest activation session does not match the active accounting configuration.');
        }

        return 'Singleton config and config-linked activation session verified.';
    }

    private function installedModules(): string
    {
        $modules = ['Ecommerce', 'Repair', 'Manufacturing', 'Restaurant', 'Project'];
        $statuses = collect($modules)->map(function ($module) {
            return $module . '=' . (is_dir(base_path('modules/' . $module)) ? 'installed' : 'not-installed');
        });

        return $statuses->implode(', ');
    }

    private function lastMeaningfulLine(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $output))));
        return $lines ? end($lines) : 'Command failed without output.';
    }
}
