<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ClientPreservingAccountingRemediationService
{
    public const BASELINE_DUMP_SHA256 = '36B1B3C816FEC9C2514835AB2680BC1C06E3032DD2327BE647E69AACA1D1D328';
    public const REMEDIATION_KEY = 'phase3h2b-client-data-preserving-reconstruction';
    public const PAYMENT_EXCLUDED_STATUS = 'excluded';

    public const EXPECTED = [
        'sales' => '8',
        'active_sales' => '4',
        'purchases' => '214',
        'active_initial_stock_artifacts' => '213',
        'payments' => '217',
        'journals' => '41',
        'journal_lines' => '84',
        'products' => '215',
        'product_warehouse_rows' => '214',
        'stored_stock_quantity' => '3981',
        'journal_debits' => '103670.0000',
        'journal_credits' => '103670.0000',
        'legacy_account_total_balance' => '101291.0000',
        'cash_bank_ledger' => '101000.0000',
        'ar_ledger' => '12.0000',
        'inventory_ledger' => '190.0000',
        'opening_balance_equity_credit' => '101198.0000',
        'sales_revenue_credit' => '160.5000',
    ];

    public function __construct(
        private AccountingService $accounting,
        private PeriodicInventoryCloseService $inventoryClose
    ) {}

    public function fingerprint(): array
    {
        $payload = [
            'sales' => (string) DB::table('sales')->count(),
            'active_sales' => (string) DB::table('sales')->whereNull('deleted_at')->count(),
            'purchases' => (string) DB::table('purchases')->count(),
            'active_initial_stock_artifacts' => (string) DB::table('purchases')
                ->whereNull('deleted_at')->whereNull('supplier_id')->count(),
            'payments' => (string) DB::table('payments')->count(),
            'journals' => (string) DB::table('journal_entries')->count(),
            'journal_lines' => (string) DB::table('journal_lines')->count(),
            'products' => (string) DB::table('products')->count(),
            'product_warehouse_rows' => (string) DB::table('product_warehouse')->count(),
            'stored_stock_quantity' => $this->num(DB::table('product_warehouse')->sum('qty'), 0),
            'journal_debits' => $this->num(DB::table('journal_lines')->sum('debit')),
            'journal_credits' => $this->num(DB::table('journal_lines')->sum('credit')),
            'legacy_account_total_balance' => $this->num(DB::table('accounts')->sum('total_balance')),
            'cash_bank_ledger' => $this->accountBalance('1000'),
            'ar_ledger' => $this->accountBalance('1100'),
            'inventory_ledger' => $this->accountBalance('1200'),
            'opening_balance_equity_credit' => $this->creditBalance('3900'),
            'sales_revenue_credit' => $this->creditBalance('4100'),
            'opening_journal_reference' => (string) DB::table('journal_entries')->where('id', 1)->value('reference_no'),
            'payment_56_reference' => (string) DB::table('payments')->where('id', 56)->value('payment_reference'),
            'product_12_qty' => $this->num(DB::table('products')->where('id', 12)->value('qty'), 0),
            'product_12_warehouse_qty' => $this->num(DB::table('product_warehouse')->where('product_id', 12)->where('warehouse_id', 1)->value('qty'), 0),
        ];

        return [
            'hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES)),
            'payload' => $payload,
        ];
    }

    public function buildPlan(): array
    {
        $this->assertRequiredSchema();
        $fingerprint = $this->fingerprint();
        $baselineMismatches = $this->baselineMismatches($fingerprint['payload']);

        $initialStock = DB::table('purchases as p')
            ->join('product_purchases as pp', 'pp.purchase_id', '=', 'p.id')
            ->leftJoin('payments as pay', 'pay.purchase_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->whereNull('p.supplier_id')
            ->selectRaw('p.id, p.reference_no, p.created_at, p.purchase_type, p.accounting_status, p.grand_total, p.paid_amount, COUNT(pp.id) line_count, SUM(pp.qty) qty, SUM(pp.total) value, MAX(pay.id) payment_id, MAX(pay.accounting_status) payment_status')
            ->groupBy('p.id', 'p.reference_no', 'p.created_at', 'p.purchase_type', 'p.accounting_status', 'p.grand_total', 'p.paid_amount')
            ->orderBy('p.id')
            ->get();

        $initialStockErrors = [];
        if ($initialStock->count() !== 213) {
            $initialStockErrors[] = 'Expected 213 active Initial Stock artifacts, found ' . $initialStock->count();
        }
        $initialStockValue = round((float) $initialStock->sum('value'), 2);
        if (abs($initialStockValue - 4928.35) > 0.0001) {
            $initialStockErrors[] = 'Expected Initial Stock value 4928.35, found ' . number_format($initialStockValue, 2, '.', '');
        }
        foreach ($initialStock as $row) {
            if ((int) $row->line_count !== 1) {
                $initialStockErrors[] = "Purchase {$row->id} does not have exactly one product_purchases line.";
            }
            if (round((float) $row->grand_total, 2) !== round((float) $row->paid_amount, 2)) {
                $initialStockErrors[] = "Purchase {$row->id} grand_total and paid_amount differ.";
            }
        }

        $payment56 = DB::table('payments')->where('id', 56)->first();
        $sale5 = DB::table('sales')->where('id', 5)->first();
        $product12 = DB::table('products')->where('id', 12)->first();
        $warehouse12 = DB::table('product_warehouse')->where('product_id', 12)->where('warehouse_id', 1)->first();
        $legacyAccounts = DB::table('accounts')->orderBy('id')->get(['id', 'name', 'initial_balance', 'total_balance', 'is_active', 'is_default']);

        $plan = [
            'remediation_key' => self::REMEDIATION_KEY,
            'baseline_dump_sha256' => self::BASELINE_DUMP_SHA256,
            'fingerprint' => $fingerprint,
            'baseline_mismatches' => $baselineMismatches,
            'blocking_conditions' => array_values(array_filter([
                ...$baselineMismatches,
                ...$initialStockErrors,
                !$payment56 ? 'Payment 56 is missing.' : null,
                $payment56 && round((float) $payment56->amount, 2) !== 8.00 ? 'Payment 56 amount changed.' : null,
                !$sale5 || !$sale5->deleted_at ? 'Sale 5 is missing or not deleted.' : null,
                !$product12 || (float) $product12->qty !== 7.0 ? 'Product 12 quantity is not the expected pre-remediation 7.' : null,
                !$warehouse12 || (float) $warehouse12->qty !== 7.0 ? 'Product 12 warehouse 1 quantity is not the expected pre-remediation 7.' : null,
            ])),
            'actions' => [
                'opening_reversal' => [
                    'idempotency_key' => 'phase3h2b:reverse-opening:journal-entry:1',
                    'source_journal_entry_id' => 1,
                    'posting_date' => '2026-07-25',
                    'debit' => ['3900 Opening Balance Equity' => '101198.00'],
                    'credit' => [
                        '1000 Cash & Bank' => '101000.00',
                        '1100 Accounts Receivable' => '8.00',
                        '1200 Inventory' => '190.00',
                    ],
                ],
                'initial_stock' => [
                    'idempotency_key_pattern' => 'phase3h2b:initial-stock:purchase:{purchase_id}',
                    'purchase_count' => $initialStock->count(),
                    'total_value' => number_format($initialStockValue, 2, '.', ''),
                    'purchase_ids' => $initialStock->pluck('id')->all(),
                ],
                'synthetic_payments' => [
                    'status' => self::PAYMENT_EXCLUDED_STATUS,
                    'payment_ids' => $initialStock->pluck('payment_id')->filter()->values()->all(),
                ],
                'repair_payment_56' => [
                    'idempotency_key' => 'phase3h2b:reverse-repair-payment:56',
                    'payment_reference' => $payment56?->payment_reference,
                    'amount' => $payment56 ? number_format((float) $payment56->amount, 2, '.', '') : null,
                    'posting_date' => $sale5?->deleted_at ? substr((string) $sale5->deleted_at, 0, 10) : '2026-07-26',
                ],
                'tecno_stock' => [
                    'idempotency_key' => 'phase3h2b:restore-stock:product:12:warehouse:1',
                    'product_id' => 12,
                    'warehouse_id' => 1,
                    'before_qty' => '7',
                    'after_qty' => '8',
                    'unit_cost' => '6.60',
                ],
                'repair_statuses' => [
                    ['sale_id' => 1, 'from' => 'posted', 'to' => 'reversed'],
                    ['sale_id' => 5, 'from' => 'posted', 'to' => 'reversed'],
                    ['sale_id' => 7, 'from' => 'posted', 'to' => 'reversed'],
                ],
                'legacy_accounts' => $legacyAccounts->map(fn ($account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'initial_balance_before' => $account->initial_balance,
                    'total_balance_before' => $account->total_balance,
                    'initial_balance_after' => 0,
                    'total_balance_after' => 0,
                ])->values()->all(),
                'payment_account_mappings' => [
                    'account_1' => 'preserve existing mapping to 10A1',
                    'account_8' => 'map to Cash & Bank 1000 without opening journal',
                    'account_10' => 'deactivate as operational payment source if unused',
                ],
                'periodic_close' => [
                    'period_start' => '2026-07-25',
                    'period_end' => '2026-07-28',
                    'expected_book_inventory' => '4928.35',
                    'expected_operational_inventory' => '4830.35',
                    'expected_cogs' => '98.00',
                ],
            ],
            'expected_financials' => $this->expectedFinancials(),
        ];

        $plan['plan_hash'] = hash('sha256', json_encode($plan['actions'], JSON_UNESCAPED_SLASHES));

        return $plan;
    }

    public function dryRun(string $databaseName, ?int $operatorId = null): array
    {
        if ($this->alreadyRemediated()) {
            $current = $this->fingerprint();
            $plan = [
                'remediation_key' => self::REMEDIATION_KEY,
                'already_remediated' => true,
                'fingerprint' => $current,
                'blocking_conditions' => [],
                'plan_hash' => hash('sha256', self::REMEDIATION_KEY . ':dry-run-already-remediated'),
                'post_remediation' => $this->postRemediationSummary(),
            ];
            $this->persistManifest('dry_run_already_remediated', $databaseName, $plan, $current['hash'], $current['hash'], $operatorId);
            return $plan;
        }

        $plan = $this->buildPlan();
        $this->persistManifest('dry_run', $databaseName, $plan, $plan['fingerprint']['hash'], null, $operatorId);

        return $plan;
    }

    public function execute(string $databaseName, string $expectedFingerprint, ?int $operatorId = null, ?string $failureStage = null): array
    {
        if ($this->alreadyRemediated()) {
            $current = $this->fingerprint();
            $approvedExecutionExists = Schema::hasTable('accounting_client_remediation_manifests')
                && DB::table('accounting_client_remediation_manifests')
                    ->where('remediation_key', self::REMEDIATION_KEY)
                    ->where('status', 'executed')
                    ->where('fingerprint_before', $expectedFingerprint)
                    ->exists();
            if (!$approvedExecutionExists && $current['hash'] !== $expectedFingerprint) {
                throw new RuntimeException('Already-remediated database does not have an approved execution manifest for the supplied fingerprint.');
            }

            $this->synchronizeActivationConfiguration();

            $result = [
                'remediation_key' => self::REMEDIATION_KEY,
                'already_remediated' => true,
                'fingerprint' => $current,
                'plan_hash' => hash('sha256', self::REMEDIATION_KEY . ':already-remediated'),
                'post_remediation' => $this->postRemediationSummary(),
            ];
            $this->persistManifest('already_remediated', $databaseName, $result, $current['hash'], $current['hash'], $operatorId);
            return $result;
        }

        $plan = $this->buildPlan();
        if ($plan['fingerprint']['hash'] !== $expectedFingerprint) {
            throw new RuntimeException('Fingerprint mismatch. Current: ' . $plan['fingerprint']['hash']);
        }
        if ($plan['blocking_conditions']) {
            throw new RuntimeException('Blocking conditions: ' . implode('; ', $plan['blocking_conditions']));
        }

        try {
            return DB::transaction(function () use ($databaseName, $operatorId, $failureStage, $plan) {
                $this->reverseOpening();
                $this->synchronizeActivationConfiguration();
                $this->failIfRequested($failureStage, 'after_opening_reversal');

                $this->reconstructInitialStock();
                $this->failIfRequested($failureStage, 'after_initial_stock');

                $this->reverseRepairPayment56();
                $this->failIfRequested($failureStage, 'after_repair_payment');

                $this->restoreTecnoStock();
                $this->correctRepairStatuses();
                $this->isolateLegacyAccountsAndMappings();
                $this->failIfRequested($failureStage, 'after_operational_corrections');

                $close = $this->postPeriodicClose();
                $this->failIfRequested($failureStage, 'after_periodic_close');

                $after = $this->postRemediationSummary();
                $plan['post_remediation'] = $after;
                $plan['periodic_close_id'] = $close?->id;

                $this->persistManifest('executed', $databaseName, $plan, $plan['fingerprint']['hash'], $this->postFingerprintHash(), $operatorId);

                return $plan;
            });
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private function reverseOpening(): void
    {
        if (JournalEntry::where('source_type', 'activation')->where('source_id', 1)->where('event_type', 'opening_balance_reversed_phase3h2b')->exists()) {
            return;
        }

        $original = JournalEntry::with('lines')->findOrFail(1);
        if ($original->reference_no !== 'JE-OPENING-20260725091841') {
            throw new RuntimeException('Opening journal reference changed.');
        }

        $builder = JournalBuilder::create()
            ->setSource('activation', 1)
            ->setSourceSubtype('remediation')
            ->setEventType('opening_balance_reversed_phase3h2b')
            ->setReference('JE-OPENING-20260725091841-PHASE3H2B-REV')
            ->setDate('2026-07-25')
            ->setNote('Phase 3H.2B reversal of unwanted activation opening balances');

        foreach ($original->lines as $line) {
            if ((float) $line->debit > 0) {
                $builder->addCredit($line->accounting_account_id, $line->debit, 'Phase 3H.2B reversal: ' . $line->description);
            }
            if ((float) $line->credit > 0) {
                $builder->addDebit($line->accounting_account_id, $line->credit, 'Phase 3H.2B reversal: ' . $line->description);
            }
        }

        $reversal = $builder->save();
        $reversal->related_journal_entry_id = $original->id;
        $reversal->saveQuietly();

        $this->synchronizeActivationConfiguration();
    }

    private function synchronizeActivationConfiguration(): void
    {
        DB::table('accounting_configs')
            ->where('id', 1)
            ->where('enabled', true)
            ->where('activation_mode', 'existing_business')
            ->update([
                'opening_journal_entry_id' => 1,
                'updated_at' => now(),
            ]);
    }

    private function reconstructInitialStock(): void
    {
        Purchase::query()
            ->whereNull('deleted_at')
            ->whereNull('supplier_id')
            ->orderBy('id')
            ->chunkById(50, function ($purchases) {
                foreach ($purchases as $purchase) {
                    $lineCount = DB::table('product_purchases')->where('purchase_id', $purchase->id)->count();
                    if ($lineCount !== 1) {
                        throw new RuntimeException("Initial Stock purchase {$purchase->id} line count changed.");
                    }
                    $purchase->purchase_type = 'initial_stock';
                    $purchase->saveQuietly();

                    $result = $this->accounting->recordInitialStock($purchase->fresh());
                    if (!$result->isSuccess()) {
                        throw new RuntimeException($result->getMessage() ?? "Initial Stock posting failed for purchase {$purchase->id}");
                    }
                    $purchase->fresh()->forceFill(['accounting_status' => 'posted'])->saveQuietly();

                    Payment::where('purchase_id', $purchase->id)->update([
                        'accounting_status' => self::PAYMENT_EXCLUDED_STATUS,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function reverseRepairPayment56(): void
    {
        $payment = Payment::findOrFail(56);
        if ($payment->accounting_status === 'reversed') {
            return;
        }

        $original = JournalEntry::with('lines')
            ->where('source_type', Payment::class)
            ->where('source_id', 56)
            ->where('event_type', 'repair_payment_received')
            ->firstOrFail();

        if (JournalEntry::where('related_journal_entry_id', $original->id)->exists()) {
            $payment->forceFill(['accounting_status' => 'reversed'])->saveQuietly();
            return;
        }

        $saleDeletedAt = Sale::where('id', 5)->value('deleted_at');
        $postingDate = $saleDeletedAt ? substr((string) $saleDeletedAt, 0, 10) : '2026-07-26';
        $builder = JournalBuilder::create()
            ->setSource(Payment::class, 56)
            ->setSourceSubtype('customer_payment')
            ->setEventType('repair_payment_received_deleted')
            ->setReference('rep-20260726-070641-56-REPAIR_PAYMENT_RECEIVED-PHASE3H2B-REV')
            ->setDate($postingDate)
            ->setNote('Phase 3H.2B reversal of deleted Repair payment 56');

        foreach ($original->lines as $line) {
            if ((float) $line->debit > 0) {
                $builder->addCredit($line->accounting_account_id, $line->debit, 'Phase 3H.2B reversal: ' . $line->description);
            }
            if ((float) $line->credit > 0) {
                $builder->addDebit($line->accounting_account_id, $line->credit, 'Phase 3H.2B reversal: ' . $line->description);
            }
        }

        $reversal = $builder->save();
        $reversal->related_journal_entry_id = $original->id;
        $reversal->saveQuietly();

        $payment->forceFill(['accounting_status' => 'reversed'])->saveQuietly();
    }

    private function restoreTecnoStock(): void
    {
        $product = Product::findOrFail(12);
        $warehouse = Product_Warehouse::where('product_id', 12)->where('warehouse_id', 1)->firstOrFail();

        if ((float) $product->qty === 8.0 && (float) $warehouse->qty === 8.0) {
            return;
        }
        if ((float) $product->qty !== 7.0 || (float) $warehouse->qty !== 7.0) {
            throw new RuntimeException('TECNO stock correction precondition failed.');
        }

        $product->qty = 8;
        $product->save();
        $warehouse->qty = 8;
        $warehouse->save();
    }

    private function correctRepairStatuses(): void
    {
        foreach ([1, 5, 7] as $saleId) {
            $sale = Sale::withTrashed()->whereKey($saleId)->whereNotNull('deleted_at')->firstOrFail();
            if ($saleId === 5 && Payment::whereKey(56)->value('accounting_status') !== 'reversed') {
                throw new RuntimeException('Sale 5 status cannot be corrected before Payment 56 reversal.');
            }
            $sale->forceFill(['accounting_status' => 'reversed'])->saveQuietly();
        }
    }

    private function isolateLegacyAccountsAndMappings(): void
    {
        Account::query()->update([
            'initial_balance' => 0,
            'total_balance' => 0,
            'updated_at' => now(),
        ]);

        $cashAccountId = $this->accounting->getRoleAccountId(AccountingService::ROLE_CASH);
        AccountMapping::updateOrCreate(
            ['mapped_type' => Account::class, 'mapped_id' => 8],
            ['accounting_account_id' => $cashAccountId]
        );

        $account10Used = Payment::where('account_id', 10)->exists()
            || DB::table('users')->where('account_id', 10)->exists()
            || DB::table('cash_registers')->where('cash_in_hand', '>', 0)->where('id', 10)->exists();
        if ($account10Used) {
            throw new RuntimeException('Account 10 is referenced by current activity; false Cash mapping refused.');
        }
        Account::whereKey(10)->update(['is_active' => false, 'updated_at' => now()]);
    }

    private function postPeriodicClose()
    {
        if (!Schema::hasTable('periodic_inventory_closes')) {
            throw new RuntimeException('periodic_inventory_closes table is missing.');
        }

        $preview = $this->inventoryClose->preview('2026-07-25', '2026-07-28');
        if (round((float) $preview['book_inventory'], 2) !== 4928.35) {
            throw new RuntimeException('Unexpected book inventory before close: ' . $preview['book_inventory']);
        }
        if (round((float) $preview['operational_inventory'], 2) !== 4830.35) {
            throw new RuntimeException('Unexpected operational inventory before close: ' . $preview['operational_inventory']);
        }
        if (round((float) $preview['adjustment'], 2) !== 98.00) {
            throw new RuntimeException('Unexpected inventory close adjustment: ' . $preview['adjustment']);
        }

        return $this->inventoryClose->post('2026-07-25', '2026-07-28', 1);
    }

    public function postRemediationSummary(): array
    {
        $balances = DB::table('accounting_accounts as aa')
            ->leftJoin('journal_lines as jl', 'jl.accounting_account_id', '=', 'aa.id')
            ->groupBy('aa.id', 'aa.code', 'aa.name', 'aa.account_type', 'aa.is_cash_account')
            ->selectRaw('aa.code, aa.name, aa.account_type, aa.is_cash_account, COALESCE(SUM(jl.debit - jl.credit), 0) balance')
            ->get();

        $cash = (float) $balances->where('is_cash_account', 1)->sum('balance');
        $ar = (float) $balances->firstWhere('code', '1100')->balance;
        $inventory = (float) $balances->firstWhere('code', '1200')->balance;
        $obe = -(float) $balances->firstWhere('code', '3900')->balance;
        $revenue = -(float) $balances->firstWhere('code', '4100')->balance;
        $cogsRow = $balances->firstWhere('code', '5000');
        $cogs = $cogsRow ? (float) $cogsRow->balance : 0.0;

        return [
            'sales' => DB::table('sales')->count(),
            'active_sales' => DB::table('sales')->whereNull('deleted_at')->count(),
            'active_sales_total' => $this->num(DB::table('sales')->whereNull('deleted_at')->sum('grand_total'), 2),
            'active_ar' => $this->num($ar, 2),
            'products' => DB::table('products')->count(),
            'active_initial_stock_artifacts' => DB::table('purchases')->whereNull('deleted_at')->where('purchase_type', 'initial_stock')->count(),
            'initial_stock_entered_quantity' => $this->num(DB::table('product_purchases as pp')->join('purchases as p', 'p.id', '=', 'pp.purchase_id')->whereNull('p.deleted_at')->sum('pp.qty'), 0),
            'active_repair_jobs' => DB::table('service_jobs')->count(),
            'cash' => $this->num($cash, 2),
            'inventory' => $this->num($inventory, 2),
            'opening_balance_equity' => $this->num($obe, 2),
            'sales_revenue' => $this->num($revenue, 2),
            'cogs' => $this->num($cogs, 2),
            'trial_balance' => $this->trialBalance(),
            'legacy_account_total_balance' => $this->num(DB::table('accounts')->sum('total_balance'), 2),
            'payment_56_status' => DB::table('payments')->where('id', 56)->value('accounting_status'),
            'product_12_qty' => $this->num(DB::table('products')->where('id', 12)->value('qty'), 0),
            'product_12_warehouse_qty' => $this->num(DB::table('product_warehouse')->where('product_id', 12)->where('warehouse_id', 1)->value('qty'), 0),
            'periodic_close_count' => Schema::hasTable('periodic_inventory_closes') ? DB::table('periodic_inventory_closes')->whereIn('status', ['posted', 'zero'])->count() : 0,
        ];
    }

    private function alreadyRemediated(): bool
    {
        return JournalEntry::where('event_type', 'opening_balance_reversed_phase3h2b')->exists()
            && DB::table('purchases')->whereNull('deleted_at')->where('purchase_type', 'initial_stock')->count() === 213
            && Payment::whereKey(56)->where('accounting_status', 'reversed')->exists()
            && (float) Product::whereKey(12)->value('qty') === 8.0
            && (float) Product_Warehouse::where('product_id', 12)->where('warehouse_id', 1)->value('qty') === 8.0
            && (!Schema::hasTable('periodic_inventory_closes') || DB::table('periodic_inventory_closes')->whereIn('status', ['posted', 'zero'])->exists());
    }

    private function persistManifest(string $status, string $databaseName, array $manifest, string $before, ?string $after, ?int $operatorId): void
    {
        if (!Schema::hasTable('accounting_client_remediation_manifests')) {
            return;
        }

        DB::table('accounting_client_remediation_manifests')->insert([
            'run_uuid' => (string) Str::uuid(),
            'remediation_key' => self::REMEDIATION_KEY,
            'status' => $status,
            'database_name' => $databaseName,
            'baseline_dump_sha256' => self::BASELINE_DUMP_SHA256,
            'fingerprint_before' => $before,
            'fingerprint_after' => $after,
            'plan_hash' => $manifest['plan_hash'] ?? hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES)),
            'operator_id' => $operatorId,
            'executed_at' => $status === 'executed' ? now() : null,
            'manifest_json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertRequiredSchema(): void
    {
        foreach (['journal_entries', 'journal_lines', 'purchases', 'product_purchases', 'payments', 'products', 'product_warehouse', 'accounts', 'account_mappings'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Required table {$table} is missing.");
            }
        }
    }

    private function baselineMismatches(array $payload): array
    {
        $mismatches = [];
        foreach (self::EXPECTED as $key => $expected) {
            if (($payload[$key] ?? null) !== $expected) {
                $mismatches[] = "Baseline {$key} expected {$expected}, found " . ($payload[$key] ?? 'NULL');
            }
        }
        return $mismatches;
    }

    private function accountBalance(string $code): string
    {
        return $this->num(DB::table('journal_lines as jl')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'jl.accounting_account_id')
            ->where('aa.code', $code)
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) balance')
            ->value('balance'));
    }

    private function creditBalance(string $code): string
    {
        return $this->num(DB::table('journal_lines as jl')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'jl.accounting_account_id')
            ->where('aa.code', $code)
            ->selectRaw('COALESCE(SUM(jl.credit - jl.debit), 0) balance')
            ->value('balance'));
    }

    private function trialBalance(): array
    {
        $debits = (float) DB::table('journal_lines')->sum('debit');
        $credits = (float) DB::table('journal_lines')->sum('credit');
        return [
            'debits' => $this->num($debits, 2),
            'credits' => $this->num($credits, 2),
            'difference' => $this->num($debits - $credits, 2),
        ];
    }

    private function expectedFinancials(): array
    {
        return [
            'trial_balance' => [
                'cash' => '148.50',
                'accounts_receivable' => '12.00',
                'inventory' => '4830.35',
                'cogs' => '98.00',
                'opening_balance_equity' => '4928.35',
                'sales_revenue' => '160.50',
                'debits' => '5088.85',
                'credits' => '5088.85',
                'difference' => '0.00',
            ],
            'profit_and_loss' => [
                'net_revenue' => '160.50',
                'cogs' => '98.00',
                'gross_profit' => '62.50',
                'operating_expenses' => '0.00',
                'net_profit' => '62.50',
            ],
            'balance_sheet' => [
                'cash' => '148.50',
                'accounts_receivable' => '12.00',
                'inventory' => '4830.35',
                'assets' => '4990.85',
                'liabilities' => '0.00',
                'opening_equity' => '4928.35',
                'current_earnings' => '62.50',
                'liabilities_plus_equity' => '4990.85',
                'variance' => '0.00',
            ],
        ];
    }

    private function postFingerprintHash(): string
    {
        return hash('sha256', json_encode($this->postRemediationSummary(), JSON_UNESCAPED_SLASHES));
    }

    private function failIfRequested(?string $failureStage, string $stage): void
    {
        if ($failureStage === $stage) {
            throw new RuntimeException("Controlled failure requested at {$stage}");
        }
    }

    private function num($value, int $decimals = 4): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }
}
