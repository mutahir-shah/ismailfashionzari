<?php

namespace App\Services;

use App\Models\AccountingSyncQueue;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class AccountingHealthService
{
    private const GROUPS = [
        'financial_records' => [
            'title_key' => 'accounting_health_group_financial_records',
            'description_key' => 'accounting_health_group_financial_records_description',
            'details_id' => 'financial-records-details',
            'checks' => [
                'journal_integrity',
                'trial_balance',
                'financial_statements',
                'accounts_receivable',
                'accounts_payable',
                'cash_flow_consistency',
                'inventory_close',
                'orphan_journals',
            ],
        ],
        'transaction_processing' => [
            'title_key' => 'accounting_health_group_transaction_processing',
            'description_key' => 'accounting_health_group_transaction_processing_description',
            'details_id' => 'transaction-processing-details',
            'checks' => [
                'failed_jobs',
                'queue_processing',
            ],
        ],
        'accounting_setup' => [
            'title_key' => 'accounting_health_group_accounting_setup',
            'description_key' => 'accounting_health_group_accounting_setup_description',
            'details_id' => 'accounting-setup-details',
            'checks' => [
                'semantic_mappings',
                'payment_accounts',
            ],
        ],
    ];

    public function build(?array $certification = null): array
    {
        $queueStats = $this->queueStats();
        $hasCertification = is_array($certification);

        $checks = [
            'journal_integrity' => $this->journalIntegrityCheck(),
            'trial_balance' => $this->trialBalanceCheck(),
            'financial_statements' => $this->notRunCheck(
                'accounting_health_check_financial_statements',
                'accounting_health_check_financial_statements_description',
                'accounting_health_check_financial_statements_not_checked'
            ),
            'semantic_mappings' => $this->semanticMappingCheck(),
            'failed_jobs' => $this->check(
                'accounting_health_check_failed_jobs',
                'accounting_health_check_failed_jobs_description',
                $queueStats['failed'] > 0 ? 'fail' : 'pass',
                $queueStats['failed'] > 0
                    ? 'accounting_health_check_failed_jobs_problem'
                    : 'accounting_health_check_failed_jobs_ok',
                ['count' => $queueStats['failed']],
                'accounting_health_action_view_details',
                '#technical-details',
                'accounting_health_technical_label_failed_jobs',
                $queueStats['failed']
            ),
            'queue_processing' => $this->check(
                'accounting_health_check_queue_processing',
                'accounting_health_check_queue_processing_description',
                $queueStats['pending'] > 0 ? 'warn' : 'pass',
                $queueStats['pending'] > 0
                    ? 'accounting_health_check_queue_processing_waiting'
                    : 'accounting_health_check_queue_processing_ok',
                ['count' => $queueStats['pending']],
                'accounting_health_action_view_details',
                '#technical-details',
                'accounting_health_technical_label_queue_processing',
                $queueStats['pending']
            ),
            'accounts_receivable' => $this->notRunCheck(
                'accounting_health_check_customer_balances',
                'accounting_health_check_customer_balances_description',
                'accounting_health_check_customer_balances_not_checked'
            ),
            'accounts_payable' => $this->notRunCheck(
                'accounting_health_check_supplier_balances',
                'accounting_health_check_supplier_balances_description',
                'accounting_health_check_supplier_balances_not_checked'
            ),
            'cash_flow_consistency' => $this->notRunCheck(
                'accounting_health_check_cash_flow_consistency',
                'accounting_health_check_cash_flow_consistency_description',
                'accounting_health_check_cash_flow_consistency_not_checked'
            ),
            'inventory_close' => $this->inventoryCloseCheck(),
            'payment_accounts' => $this->paymentAccountCheck(),
            'orphan_journals' => $this->orphanJournalCheck(),
        ];

        if ($hasCertification) {
            $checks = $this->applyCertificationResult($checks, $certification);
        }

        $checks = $this->decorateChecks($checks, $hasCertification);
        $groups = $this->groupsFromChecks($checks, $hasCertification);
        $issues = $this->issuesFromChecks($checks, $certification);
        $overall = $this->overallStatus($checks, $issues, $certification);

        return [
            'overall' => $overall,
            'groups' => $groups,
            'checks' => $checks,
            'issues' => $issues,
            'recent_activity' => $this->recentActivity($certification),
            'queue_stats' => $queueStats,
            'certification' => $certification,
            'generated_at' => now(),
        ];
    }

    public function runCertification(): array
    {
        $buffer = new BufferedOutput();
        $exitCode = Artisan::call('accounting:certify', [
            '--skip-regressions' => true,
        ], $buffer);

        $output = $buffer->fetch();
        if (trim($output) === '') {
            $output = Artisan::output();
        }
        $output = $this->sanitizeCertificationOutput($output);

        return [
            'exit_code' => $exitCode,
            'status' => $this->certificationStatus($exitCode, $output),
            'checked_at' => now()->toDateTimeString(),
            'output' => $output,
        ];
    }

    private function sanitizeCertificationOutput(string $output): string
    {
        $output = preg_replace('/\x1B(?:[@-_][0-?]*[ -\/]*[@-~]|\][^\x07]*(?:\x07|\x1B\\\\))/', '', $output) ?? $output;
        $output = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $output) ?? $output;

        return trim(str_replace(["\r\n", "\r"], "\n", $output));
    }

    private function queueStats(): array
    {
        return [
            'total' => AccountingSyncQueue::count(),
            'failed' => AccountingSyncQueue::where('status', 'failed')->count(),
            'pending' => AccountingSyncQueue::where('status', 'pending')->count(),
            'posted' => AccountingSyncQueue::where('status', 'posted')->count(),
        ];
    }

    private function journalIntegrityCheck(): array
    {
        $unbalanced = DB::table('journal_entries as je')
            ->leftJoin('journal_lines as jl', 'jl.journal_entry_id', '=', 'je.id')
            ->select('je.id')
            ->groupBy('je.id')
            ->havingRaw('ROUND(COALESCE(SUM(jl.debit), 0), 4) <> ROUND(COALESCE(SUM(jl.credit), 0), 4)')
            ->get()
            ->count();

        $orphanLines = JournalLine::whereDoesntHave('journalEntry')->count();
        $missingAccounts = DB::table('journal_lines as jl')
            ->leftJoin('accounting_accounts as aa', 'aa.id', '=', 'jl.accounting_account_id')
            ->whereNull('aa.id')
            ->count();

        $problemCount = $unbalanced + $orphanLines + $missingAccounts;

        return $this->check(
            'accounting_health_check_journal_integrity',
            'accounting_health_check_journal_integrity_description',
            $problemCount > 0 ? 'fail' : 'pass',
            $problemCount > 0
                ? 'accounting_health_check_journal_integrity_problem'
                : 'accounting_health_check_journal_integrity_ok',
            ['count' => $problemCount],
            'accounting_health_action_view_details',
            '#technical-details',
            'accounting_health_technical_label_journal_integrity',
            $problemCount
        );
    }

    private function trialBalanceCheck(): array
    {
        $totals = DB::table('journal_lines')
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $difference = round((float) ($totals->debit ?? 0) - (float) ($totals->credit ?? 0), 4);

        return $this->check(
            'accounting_health_check_trial_balance',
            'accounting_health_check_trial_balance_description',
            abs($difference) > 0.0001 ? 'fail' : 'pass',
            abs($difference) > 0.0001
                ? 'accounting_health_check_trial_balance_problem'
                : 'accounting_health_check_trial_balance_ok',
            ['difference' => number_format($difference, 2)],
            'accounting_health_action_view_financial_details',
            route('accounting.trialBalance'),
            'accounting_health_technical_label_trial_balance',
            abs($difference)
        );
    }

    private function inventoryCloseCheck(): array
    {
        try {
            $preview = app(PeriodicInventoryCloseService::class)->preview(now()->startOfMonth()->toDateString(), now()->toDateString());
            $duplicates = DB::table('periodic_inventory_closes')->whereIn('status', ['posted', 'zero'])
                ->groupBy('period_start', 'period_end', 'scope')->havingRaw('COUNT(*) > 1')->count();
            $invalidJournals = DB::table('periodic_inventory_closes as pic')
                ->leftJoin('journal_entries as je', 'je.id', '=', 'pic.journal_entry_id')
                ->where('pic.status', 'posted')->whereNull('je.id')->count();
            $problemCount = $preview['blocking_errors']->count() + $duplicates + $invalidJournals;
            $needsClose = !$preview['active_close'] && ($preview['book_inventory'] != 0.0 || $preview['operational_inventory'] != 0.0);
            $status = $problemCount > 0 ? 'fail' : ($needsClose ? 'warn' : 'pass');
            return $this->check(
                'accounting_health_check_inventory_close',
                'accounting_health_check_inventory_close_description',
                $status,
                $problemCount > 0 ? 'accounting_health_check_inventory_close_problem' : ($needsClose ? 'accounting_health_check_inventory_close_needed' : 'accounting_health_check_inventory_close_ok'),
                ['count' => $problemCount],
                'accounting_health_action_inventory_close',
                route('accounting.inventory-close.index'),
                'accounting_health_technical_label_inventory_close',
                $problemCount
            );
        } catch (Throwable $e) {
            return $this->check('accounting_health_check_inventory_close', 'accounting_health_check_inventory_close_description',
                'fail', 'accounting_health_check_inventory_close_problem', ['count' => 1],
                'accounting_health_action_inventory_close', route('accounting.inventory-close.index'),
                'accounting_health_technical_label_inventory_close', 1);
        }
    }

    private function semanticMappingCheck(): array
    {
        $roles = array_merge(AccountingService::CORE_CERTIFICATION_ROLES, AccountingService::FEATURE_CERTIFICATION_ROLES);
        $results = app(AccountingService::class)->validateSemanticRoleMappings($roles, false);
        $failures = array_filter($results, fn ($result) => ($result['status'] ?? null) !== 'pass');

        return $this->check(
            'accounting_health_check_semantic_mappings',
            'accounting_health_check_semantic_mappings_description',
            $failures ? 'fail' : 'pass',
            $failures
                ? 'accounting_health_check_semantic_mappings_problem'
                : 'accounting_health_check_semantic_mappings_ok',
            ['count' => count($failures)],
            'accounting_health_action_configure_accounting',
            route('accounting.semantic-mappings.index'),
            'accounting_health_technical_label_semantic_mappings',
            count($failures)
        );
    }

    private function paymentAccountCheck(): array
    {
        if (app(AccountingModeService::class)->isLegacy()) {
            return $this->check('accounting_health_check_payment_accounts', 'accounting_health_check_payment_accounts_description',
                'pass', 'accounting_health_check_payment_accounts_legacy', [],
                'accounting_health_action_payment_accounts', route('accounts.index'),
                'accounting_health_technical_label_payment_accounts', 0);
        }

        $service = app(PaymentAccountService::class);
        $repair = app(PaymentAccountMappingRepairService::class)->inspect();
        $accounts = \App\Models\Account::where('is_active', true)->get();
        $invalidIds = $accounts->reject(fn ($account) => $service->isValid($account))->pluck('id');
        $invalidDefaults = DB::table('users')->whereNotNull('account_id')->whereIn('account_id', $invalidIds)->count();
        $problemCount = $invalidIds->count() + $invalidDefaults;

        return $this->check('accounting_health_check_payment_accounts', 'accounting_health_check_payment_accounts_description',
            $problemCount ? 'fail' : 'pass',
            $problemCount ? 'accounting_health_check_payment_accounts_problem' : 'accounting_health_check_payment_accounts_ok',
            ['count' => $problemCount],
            $repair['repairable_count'] > 0 ? 'accounting_health_action_repair_payment_mappings' : 'accounting_health_action_payment_accounts',
            $repair['repairable_count'] > 0 ? '#payment-account-repair-review' : route('accounts.index'),
            'accounting_health_technical_label_payment_accounts', $problemCount);
    }

    private function orphanJournalCheck(): array
    {
        $orphanCount = 0;
        $historicalCount = 0;
        $integrity = app(JournalSourceIntegrityService::class);

        JournalEntry::query()
            ->whereNotNull('source_type')
            ->select(['id', 'source_type', 'source_id', 'related_journal_entry_id'])
            ->chunkById(200, function ($journals) use (&$orphanCount, &$historicalCount, $integrity) {
                foreach ($journals as $journal) {
                    $classification = $integrity->classify($journal);
                    if ($integrity->isFailure($classification)) {
                        $orphanCount++;
                    } elseif ($classification['status'] === JournalSourceIntegrityService::HISTORICAL_ABSENT_REVERSED) {
                        $historicalCount++;
                    }
                }
            });

        $status = $orphanCount > 0 ? 'fail' : ($historicalCount > 0 ? 'warn' : 'pass');

        return $this->check(
            'accounting_health_check_orphan_journals',
            'accounting_health_check_orphan_journals_description',
            $status,
            $orphanCount > 0
                ? 'accounting_health_check_orphan_journals_problem'
                : 'accounting_health_check_orphan_journals_ok',
            ['count' => $orphanCount, 'historical_count' => $historicalCount],
            'accounting_health_action_view_details',
            '#technical-details',
            'accounting_health_technical_label_orphan_journals',
            $orphanCount
        );
    }

    private function notRunCheck(string $labelKey, string $descriptionKey, string $detailKey): array
    {
        return $this->check($labelKey, $descriptionKey, 'not_run', $detailKey);
    }

    private function check(
        string $labelKey,
        string $descriptionKey,
        string $status,
        string $detailKey,
        array $detailParams = [],
        ?string $actionLabelKey = null,
        ?string $actionUrl = null,
        ?string $technicalLabelKey = null,
        float|int|null $count = null
    ): array {
        return [
            'label_key' => $labelKey,
            'description_key' => $descriptionKey,
            'status' => $status,
            'detail_key' => $detailKey,
            'detail_params' => $detailParams,
            'action_label_key' => $actionLabelKey,
            'action_url' => $actionUrl,
            'technical_label_key' => $technicalLabelKey,
            'count' => $count,
        ];
    }

    private function decorateChecks(array $checks, bool $hasCertification): array
    {
        foreach ($checks as &$check) {
            $displayStatus = $this->displayStatus($check['status'], $hasCertification);
            $check['display_status'] = $displayStatus;
            $check['display_status_key'] = $this->statusTranslationKey($displayStatus);
            $check['tone'] = $this->toneForDisplayStatus($displayStatus);
        }

        return $checks;
    }

    private function applyCertificationResult(array $checks, array $certification): array
    {
        $output = (string) ($certification['output'] ?? '');

        $this->applySummaryStatus($checks['journal_integrity'], $output, 'Journal Integrity', [
            'PASS' => 'accounting_health_certification_journal_integrity_pass',
            'FAIL' => 'accounting_health_certification_journal_integrity_fail',
            'WARN' => 'accounting_health_certification_journal_integrity_warn',
        ]);

        $this->applySummaryStatus($checks['trial_balance'], $output, 'Trial Balance', [
            'PASS' => 'accounting_health_certification_trial_balance_pass',
            'FAIL' => 'accounting_health_certification_trial_balance_fail',
            'WARN' => 'accounting_health_certification_trial_balance_warn',
        ]);

        $this->applySummaryStatus($checks['semantic_mappings'], $output, 'Semantic Account Roles', [
            'PASS' => 'accounting_health_certification_semantic_mappings_pass',
            'FAIL' => 'accounting_health_certification_semantic_mappings_fail',
            'WARN' => 'accounting_health_certification_semantic_mappings_warn',
        ]);

        $checks['financial_statements'] = $this->financialStatementCertificationCheck($checks['financial_statements'], $output);
        $checks['accounts_receivable'] = $this->auditLayerCheck(
            $checks['accounts_receivable'],
            $output,
            'Accounts Receivable',
            'accounting_health_certification_accounts_receivable_pass',
            'accounting_health_certification_accounts_receivable_fail'
        );
        $checks['accounts_payable'] = $this->auditLayerCheck(
            $checks['accounts_payable'],
            $output,
            'Accounts Payable',
            'accounting_health_certification_accounts_payable_pass',
            'accounting_health_certification_accounts_payable_fail'
        );
        $checks['cash_flow_consistency'] = $this->auditLayerCheck(
            $checks['cash_flow_consistency'],
            $output,
            'Cash Flow Reconciliation',
            'accounting_health_certification_cash_flow_pass',
            'accounting_health_certification_cash_flow_fail'
        );
        $checks['orphan_journals'] = $this->auditLayerCheck(
            $checks['orphan_journals'],
            $output,
            'Orphan Journals',
            'accounting_health_certification_orphan_journals_pass',
            'accounting_health_certification_orphan_journals_fail'
        );

        return $checks;
    }

    private function applySummaryStatus(array &$check, string $output, string $summaryLabel, array $detailKeys): void
    {
        $status = $this->summaryStatus($output, $summaryLabel);

        if (!$status) {
            return;
        }

        $check['status'] = strtolower($status) === 'pass'
            ? 'pass'
            : (strtolower($status) === 'warn' ? 'warn' : 'fail');
        $check['detail_key'] = $detailKeys[$status] ?? $check['detail_key'];
        $check['detail_params'] = [];
    }

    private function financialStatementCertificationCheck(array $check, string $output): array
    {
        $summaryLabels = ['Balance Sheet', 'Opening Balance'];
        $statuses = array_filter(array_map(fn ($label) => $this->summaryStatus($output, $label), $summaryLabels));

        if (str_contains($output, '[FAIL CRITICAL] Trial Balance totals do not match Financial Statements')
            || str_contains($output, 'Cash Flow Reconciliation Failed')
            || in_array('FAIL', $statuses, true)) {
            $check['status'] = 'fail';
            $check['detail_key'] = 'accounting_health_certification_financial_statements_fail';
            $check['detail_params'] = [];
            return $check;
        }

        if (str_contains($output, '[PASS] Trial Balance Cross-Verification') || in_array('PASS', $statuses, true)) {
            $check['status'] = 'pass';
            $check['detail_key'] = 'accounting_health_certification_financial_statements_pass';
            $check['detail_params'] = [];
        }

        return $check;
    }

    private function auditLayerCheck(array $check, string $output, string $label, string $passDetailKey, string $failDetailKey): array
    {
        if (str_contains($output, "[FAIL] {$label}")
            || str_contains($output, "[FAIL CRITICAL] {$label}")
            || str_contains($output, "{$label} Failed")) {
            $check['status'] = 'fail';
            $check['detail_key'] = $failDetailKey;
            $check['detail_params'] = [];
            return $check;
        }

        if (str_contains($output, "[PASS] {$label}")) {
            $check['status'] = 'pass';
            $check['detail_key'] = $passDetailKey;
            $check['detail_params'] = [];
        }

        return $check;
    }

    private function summaryStatus(string $output, string $label): ?string
    {
        $pattern = '/^' . preg_quote($label, '/') . '\s+(PASS|WARN|FAIL)\s*$/m';

        if (preg_match($pattern, $output, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function certificationStatus(int $exitCode, string $output): string
    {
        if ($exitCode !== 0) {
            return 'fail';
        }

        return str_contains($output, 'READY WITH WARNINGS') ? 'warn' : 'pass';
    }

    private function groupsFromChecks(array $checks, bool $hasCertification): array
    {
        $groups = [];

        foreach (self::GROUPS as $key => $definition) {
            $groupChecks = array_intersect_key($checks, array_flip($definition['checks']));
            $rawStatus = $this->aggregateRawStatus($groupChecks, $hasCertification);
            $displayStatus = $this->displayStatus($rawStatus, $hasCertification);

            $groups[$key] = [
                'key' => $key,
                'title_key' => $definition['title_key'],
                'description_key' => $definition['description_key'],
                'details_id' => $definition['details_id'],
                'checks' => $groupChecks,
                'status' => $rawStatus,
                'display_status' => $displayStatus,
                'display_status_key' => $this->statusTranslationKey($displayStatus),
                'tone' => $this->toneForDisplayStatus($displayStatus),
                'summary_key' => $this->groupSummaryKey($key, $displayStatus),
                'action_label_key' => $this->groupActionLabelKey($key, $displayStatus),
                'action_url' => $this->groupActionUrl($key, $displayStatus),
            ];
        }

        return $groups;
    }

    private function aggregateRawStatus(array $checks, bool $hasCertification): string
    {
        if (!$hasCertification && collect($checks)->contains(fn ($check) => $check['status'] === 'not_run')) {
            $hasLiveConcern = collect($checks)->contains(fn ($check) => in_array($check['status'], ['fail', 'warn'], true));

            return $hasLiveConcern ? 'warn' : 'not_run';
        }

        if (collect($checks)->contains(fn ($check) => $check['status'] === 'fail')) {
            return 'fail';
        }

        if (collect($checks)->contains(fn ($check) => $check['status'] === 'warn')) {
            return 'warn';
        }

        if (collect($checks)->contains(fn ($check) => $check['status'] === 'not_run')) {
            return 'not_run';
        }

        return 'pass';
    }

    private function overallStatus(array $checks, array $issues, ?array $certification): array
    {
        if (!$certification) {
            return [
                'level' => 'neutral',
                'display_status' => 'not_checked',
                'label_key' => 'accounting_health_status_not_checked',
                'message_key' => 'accounting_health_overall_not_checked_message',
                'issues_count' => count($issues),
            ];
        }

        $hasFail = collect($checks)->contains(fn ($check) => $check['status'] === 'fail')
            || (($certification['status'] ?? null) === 'fail');
        $hasWarn = collect($checks)->contains(fn ($check) => $check['status'] === 'warn')
            || (($certification['status'] ?? null) === 'warn');

        if ($hasFail) {
            return [
                'level' => 'red',
                'display_status' => 'action_required',
                'label_key' => 'accounting_health_status_action_required',
                'message_key' => 'accounting_health_overall_action_required_message',
                'issues_count' => count($issues),
            ];
        }

        if ($hasWarn) {
            return [
                'level' => 'yellow',
                'display_status' => 'needs_review',
                'label_key' => 'accounting_health_status_needs_review',
                'message_key' => 'accounting_health_overall_needs_review_message',
                'issues_count' => count($issues),
            ];
        }

        return [
            'level' => 'green',
            'display_status' => 'healthy',
            'label_key' => 'accounting_health_status_healthy',
            'message_key' => 'accounting_health_overall_healthy_message',
            'issues_count' => count($issues),
        ];
    }

    private function issuesFromChecks(array $checks, ?array $certification): array
    {
        $issues = [];
        $hasCertification = is_array($certification);

        foreach ($checks as $key => $check) {
            if (!in_array($check['status'], ['fail', 'warn'], true)) {
                continue;
            }

            $issues[$key] = $this->ownerFriendlyIssue($key, $check, $hasCertification);
        }

        if (($certification['status'] ?? null) === 'fail' && empty($issues)) {
            $issues['certification'] = [
                'key' => 'certification',
                'severity' => 'fail',
                'tone' => 'fail',
                'title_key' => 'accounting_health_issue_certification_failed_title',
                'explanation_key' => 'accounting_health_issue_certification_failed_explanation',
                'explanation_params' => [],
                'action_label_key' => 'accounting_health_action_view_details',
                'action_url' => '#technical-details',
                'technical_label_key' => 'accounting_health_technical_label_certification',
            ];
        }

        return array_values($issues);
    }

    private function ownerFriendlyIssue(string $key, array $check, bool $hasCertification): array
    {
        $severity = $hasCertification && $check['status'] === 'fail' ? 'fail' : 'warn';

        $issue = [
            'key' => $key,
            'severity' => $severity,
            'tone' => $this->toneForDisplayStatus($this->displayStatus($check['status'], $hasCertification)),
            'title_key' => 'accounting_health_issue_generic_title',
            'explanation_key' => 'accounting_health_issue_generic_explanation',
            'explanation_params' => [],
            'action_label_key' => $check['action_label_key'] ?: 'accounting_health_action_review_issue',
            'action_url' => $check['action_url'],
            'technical_label_key' => $check['technical_label_key'],
        ];

        if ($key === 'semantic_mappings') {
            return array_merge($issue, [
                'title_key' => 'accounting_health_issue_setup_incomplete_title',
                'explanation_key' => 'accounting_health_issue_setup_incomplete_explanation',
                'explanation_params' => ['count' => $check['count'] ?? 0],
                'action_label_key' => 'accounting_health_action_configure_accounting',
            ]);
        }

        if ($key === 'orphan_journals') {
            return array_merge($issue, [
                'title_key' => ($check['count'] ?? 0) === 1
                    ? 'accounting_health_issue_orphan_journal_one_title'
                    : 'accounting_health_issue_orphan_journal_many_title',
                'explanation_key' => 'accounting_health_issue_orphan_journal_explanation',
                'explanation_params' => ['count' => $check['count'] ?? 0],
                'action_label_key' => 'accounting_health_action_view_details',
            ]);
        }

        if ($key === 'failed_jobs') {
            return array_merge($issue, [
                'title_key' => ($check['count'] ?? 0) === 1
                    ? 'accounting_health_issue_failed_job_one_title'
                    : 'accounting_health_issue_failed_job_many_title',
                'explanation_key' => 'accounting_health_issue_failed_job_explanation',
                'explanation_params' => ['count' => $check['count'] ?? 0],
                'action_label_key' => 'accounting_health_action_view_details',
            ]);
        }

        if ($key === 'queue_processing') {
            return array_merge($issue, [
                'title_key' => 'accounting_health_issue_queue_waiting_title',
                'explanation_key' => 'accounting_health_issue_queue_waiting_explanation',
                'explanation_params' => ['count' => $check['count'] ?? 0],
                'action_label_key' => 'accounting_health_action_view_details',
            ]);
        }

        if ($key === 'trial_balance') {
            return array_merge($issue, [
                'title_key' => 'accounting_health_issue_trial_balance_title',
                'explanation_key' => 'accounting_health_issue_trial_balance_explanation',
                'explanation_params' => $check['detail_params'] ?? [],
                'action_label_key' => 'accounting_health_action_view_financial_details',
            ]);
        }

        if (in_array($key, ['financial_statements', 'accounts_receivable', 'accounts_payable', 'cash_flow_consistency'], true)) {
            return array_merge($issue, [
                'title_key' => $check['label_key'],
                'explanation_key' => $check['detail_key'],
                'explanation_params' => $check['detail_params'] ?? [],
                'action_label_key' => 'accounting_health_action_view_financial_details',
                'action_url' => '#financial-records-details',
            ]);
        }

        return $issue;
    }

    private function recentActivity(?array $certification): array
    {
        $activity = [];

        if ($certification) {
            $activity[] = [
                'label_key' => 'accounting_health_activity_health_check_completed',
                'detail_key' => match ($certification['status'] ?? null) {
                    'fail' => 'accounting_health_activity_health_check_failed_detail',
                    'warn' => 'accounting_health_activity_health_check_warning_detail',
                    default => 'accounting_health_activity_health_check_success_detail',
                },
                'detail_params' => ['code' => $certification['exit_code']],
                'time' => $certification['checked_at'],
            ];
        }

        AccountingSyncQueue::query()
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->each(function (AccountingSyncQueue $queue) use (&$activity) {
                $activity[] = [
                    'label_key' => 'accounting_health_activity_transaction_job',
                    'label_params' => ['source' => class_basename($queue->source_type)],
                    'detail_key' => match ($queue->status) {
                        'posted' => 'accounting_health_activity_job_posted',
                        'failed' => 'accounting_health_activity_job_failed',
                        'reversed' => 'accounting_health_activity_job_reversed',
                        default => 'accounting_health_activity_job_pending',
                    },
                    'detail_params' => [],
                    'time' => optional($queue->updated_at)->toDateTimeString(),
                ];
            });

        JournalEntry::query()
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function (JournalEntry $entry) use (&$activity) {
                $activity[] = [
                    'label_key' => $this->eventLabelKey((string) $entry->event_type),
                    'detail_key' => 'accounting_health_activity_reference_detail',
                    'detail_params' => [
                        'reference' => $entry->reference_no ?: '#' . $entry->id,
                    ],
                    'time' => optional($entry->created_at)->toDateTimeString(),
                ];
            });

        return array_slice($activity, 0, 6);
    }

    private function eventLabelKey(string $eventType): string
    {
        $eventType = strtolower($eventType);

        if (str_contains($eventType, 'money_transfer')) {
            return 'accounting_health_activity_money_transfer_recorded';
        }

        if (str_contains($eventType, 'opening')) {
            return 'accounting_health_activity_opening_balance_created';
        }

        if (str_contains($eventType, 'sale_return')) {
            return 'accounting_health_activity_sale_return_recorded';
        }

        if (str_contains($eventType, 'purchase_return')) {
            return 'accounting_health_activity_purchase_return_recorded';
        }

        if (str_contains($eventType, 'sale')) {
            return 'accounting_health_activity_sale_recorded';
        }

        if (str_contains($eventType, 'purchase')) {
            return 'accounting_health_activity_purchase_recorded';
        }

        if (str_contains($eventType, 'payment')) {
            return 'accounting_health_activity_payment_recorded';
        }

        if (str_contains($eventType, 'expense')) {
            return 'accounting_health_activity_expense_recorded';
        }

        if (str_contains($eventType, 'income')) {
            return 'accounting_health_activity_income_recorded';
        }

        return 'accounting_health_activity_entry_recorded';
    }

    private function displayStatus(string $status, bool $hasCertification): string
    {
        if ($status === 'pass') {
            return 'healthy';
        }

        if ($status === 'not_run') {
            return 'not_checked';
        }

        if ($status === 'fail') {
            return $hasCertification ? 'action_required' : 'needs_review';
        }

        return 'needs_review';
    }

    private function statusTranslationKey(string $displayStatus): string
    {
        return match ($displayStatus) {
            'healthy' => 'accounting_health_status_healthy',
            'action_required' => 'accounting_health_status_action_required',
            'not_checked' => 'accounting_health_status_not_checked',
            default => 'accounting_health_status_needs_review',
        };
    }

    private function toneForDisplayStatus(string $displayStatus): string
    {
        return match ($displayStatus) {
            'healthy' => 'pass',
            'action_required' => 'fail',
            'not_checked' => 'not_run',
            default => 'warn',
        };
    }

    private function groupSummaryKey(string $group, string $displayStatus): string
    {
        return "accounting_health_group_{$group}_{$displayStatus}";
    }

    private function groupActionLabelKey(string $group, string $displayStatus): ?string
    {
        if ($displayStatus === 'healthy') {
            return null;
        }

        return match ($group) {
            'accounting_setup' => 'accounting_health_action_configure_accounting',
            'financial_records' => 'accounting_health_action_view_financial_details',
            default => 'accounting_health_action_view_details',
        };
    }

    private function groupActionUrl(string $group, string $displayStatus): ?string
    {
        if ($displayStatus === 'healthy') {
            return null;
        }

        return match ($group) {
            'accounting_setup' => route('accounting.semantic-mappings.index'),
            default => '#' . (self::GROUPS[$group]['details_id'] ?? 'technical-details'),
        };
    }
}
