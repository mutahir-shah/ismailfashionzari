<?php

namespace App\Services\Demo;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\JournalSourceIntegrityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GoldenDemoPhase17Service
{
    private const REQUIRED_PHASES = [
        'Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6',
        'Phase 7', 'Phase 8', 'Phase 9', 'Phase 10', 'Phase 11', 'Phase 12',
        'Phase 13', 'Phase 14', 'Phase 15', 'Phase 16',
    ];

    private const REQUIRED_MANIFEST_KEYS = [
        'version', 'created_at', 'phases_completed', 'baseline', 'masters_created',
        'cash_register', 'purchases_created', 'payments_created', 'transfers_created',
        'adjustments_created', 'sales_created', 'payment_mutations', 'returns_created',
        'exchanges_created', 'purchase_mutations', 'purchase_returns_created',
        'restaurant_sales_created', 'phase13_validation', 'phase13_checkpoint',
        'phase14', 'phase14_validation', 'phase14_checkpoint', 'phase15',
        'phase15_validation', 'phase15_checkpoint', 'phase16', 'phase16_validation',
        'database_identifier', 'environment', 'build_identifier', 'manifest_schema_version',
        'source_ledgers', 'accounting_expectation_set', 'phase_evidence_snapshots',
        'phase17_expected_state', 'catalog_baseline', 'catalog_pre_phase1_validation',
    ];

    public function __construct(
        private GoldenDemoValidator $validator,
        private GoldenDemoPhase16Service $phase16,
        private JournalSourceIntegrityService $sourceIntegrity,
        private GoldenDemoManifestService $manifestService,
        private GoldenDemoCatalogService $catalogService,
    ) {}

    public function certify(): array
    {
        GoldenDemoSafety::assertExactDemoDatabase();

        $manifest = $this->manifestService->read();
        $this->manifestService->assertOwnedByActiveDatabase($manifest);
        $database = DB::connection()->getDatabaseName();
        $catalog = isset($manifest['catalog_baseline'])
            ? $this->catalogService->validateCompleteness($manifest['catalog_baseline'])
            : ['passed' => false, 'failures' => ['Immutable master catalog baseline is absent.']];
        $phase16 = isset($manifest['phase15_checkpoint'], $manifest['phase16'])
            ? $this->phase16->validate($manifest['phase15_checkpoint'], $manifest['phase16'])
            : ['passed' => false, 'ready_for_final_certification_phase' => 'NO'];

        $manifestAudit = $this->auditManifest($manifest, $database);
        $referenceAudit = $this->auditManifestReferences($manifest);
        $relationships = $this->auditRelationships();
        $duplicates = $this->auditDuplicates();
        $accounting = $this->auditAccounting();
        $baseline = $this->validator->recordBaseline();
        $expectedLedgers = $this->auditExpectedLedgers($manifest, $baseline, $accounting);

        $blockers = array_values(array_merge(
            ($phase16['ready_for_final_certification_phase'] ?? 'NO') === 'YES'
                ? [] : ['Phase-16 prerequisite is not READY FOR FINAL CERTIFICATION PHASE: YES.'],
            $catalog['failures'] ?? [],
            $manifestAudit['failures'],
            $referenceAudit['failures'],
            $relationships['failures'],
            $duplicates['failures'],
            $accounting['failures'],
            $expectedLedgers['failures'],
        ));

        // Final stock reconstruction requires an immutable pre-transaction tuple map.
        // Never substitute operational stock or a later checkpoint for this evidence.
        $stockMeasurementAvailable = isset($manifest['baseline']['stock']['tuple_map'])
            || isset($manifest['baseline']['stock_tuple_map']);
        if (!$stockMeasurementAvailable) {
            $blockers[] = 'Independent stock reconstruction unavailable: immutable Phase-1 stock tuple map is absent.';
        }

        $expectedSourceSetAvailable = $manifestAudit['scenario_sections_complete'];
        if (!$expectedSourceSetAvailable) {
            $blockers[] = 'Expected accounting-source set unavailable: one or more certified scenario sections are absent.';
        }

        $independentLedgerComplete = ($manifest['phase17_expected_state']['independent_reconstruction_completed'] ?? false) === true;
        if (!$independentLedgerComplete) {
            $blockers[] = 'Independent Phase-17 stock, A/R, A/P, payment-account, and report ledgers are not complete.';
        }

        $blockers = array_values(array_unique($blockers));
        $passed = $blockers === []
            && ($phase16['passed'] ?? false)
            && ($catalog['passed'] ?? false)
            && $stockMeasurementAvailable
            && $expectedSourceSetAvailable
            && $independentLedgerComplete
            && $expectedLedgers['passed'];

        return [
            'passed' => $passed,
            'golden_demo_certified' => $passed ? 'YES' : 'NO',
            'database' => $database,
            'phpunit_database_required' => 'salepro_testing',
            'phase16_prerequisite' => $phase16['ready_for_final_certification_phase'] ?? 'NO',
            'catalog' => $catalog,
            'manifest' => $manifestAudit,
            'manifest_references' => $referenceAudit,
            'scenario_inventory' => $this->scenarioInventory(),
            'stock' => [
                'independent_measurement_available' => $stockMeasurementAvailable,
                'independent_ledger_complete' => $independentLedgerComplete,
                'actual_product_qty' => (float) $baseline['stock']['total_product_qty'],
                'actual_warehouse_qty' => (float) $baseline['stock']['total_warehouse_qty'],
                'actual_tuple_count' => (int) $baseline['stock']['tuple_count'],
                'expected_product_qty' => $expectedLedgers['stock']['expected_product_qty'] ?? null,
                'expected_warehouse_qty' => $expectedLedgers['stock']['expected_warehouse_qty'] ?? null,
                'product_variance' => $expectedLedgers['stock']['product_variance'] ?? null,
                'warehouse_variance' => $expectedLedgers['stock']['warehouse_variance'] ?? null,
                'unexplained_tuple_variance' => $stockMeasurementAvailable
                    ? ($expectedLedgers['stock']['tuple_variance'] ?? [])
                    : 'MEASUREMENT UNAVAILABLE',
            ],
            'financial' => [
                'customer_ar_operational' => (float) $baseline['financial']['customer_operational_dues'],
                'supplier_ap_operational' => (float) $baseline['financial']['supplier_operational_dues'],
                'gl_ar' => (float) $baseline['accounting']['gl_ar_balance'],
                'gl_ap' => (float) $baseline['accounting']['gl_ap_balance'],
                'payment_accounts' => $baseline['financial']['payment_account_balances'],
                'expected_customer_ar' => $expectedLedgers['financial']['expected_customer_ar'] ?? null,
                'customer_ar_variance' => $expectedLedgers['financial']['customer_ar_variance'] ?? null,
                'expected_supplier_ap' => $expectedLedgers['financial']['expected_supplier_ap'] ?? null,
                'supplier_ap_variance' => $expectedLedgers['financial']['supplier_ap_variance'] ?? null,
                'payment_account_reconciliation' => $expectedLedgers['financial']['payment_accounts'] ?? [],
            ],
            'register' => $phase16['register'] ?? [],
            'accounting' => array_merge($accounting, $expectedLedgers['accounting']),
            'relationships' => $relationships,
            'duplicates' => $duplicates,
            'blockers' => $blockers,
            'final_verdict' => 'GOLDEN DEMO CERTIFIED: '.($passed ? 'YES' : 'NO'),
        ];
    }

    public function auditManifest(array $manifest, string $database): array
    {
        $failures = [];
        $phases = array_values($manifest['phases_completed'] ?? []);
        $duplicates = array_keys(array_filter(array_count_values($phases), fn (int $count) => $count > 1));
        $missingPhases = array_values(array_diff(self::REQUIRED_PHASES, $phases));
        $extraPhases = array_values(array_diff($phases, self::REQUIRED_PHASES));
        $missingKeys = array_values(array_filter(
            self::REQUIRED_MANIFEST_KEYS,
            fn (string $key) => !array_key_exists($key, $manifest)
        ));

        if ($duplicates) $failures[] = 'Duplicate phase entries: '.implode(', ', $duplicates).'.';
        if ($missingPhases) $failures[] = 'Missing certified phases: '.implode(', ', $missingPhases).'.';
        if ($extraPhases) $failures[] = 'Unexpected phase entries: '.implode(', ', $extraPhases).'.';
        if ($missingKeys) $failures[] = 'Missing manifest sections: '.implode(', ', $missingKeys).'.';

        $identifier = $manifest['database_identifier'] ?? $manifest['database'] ?? null;
        if (!$identifier) {
            $failures[] = 'Manifest database identifier is absent.';
        } elseif (strcasecmp((string) $identifier, $database) !== 0) {
            $failures[] = "Manifest database identifier '{$identifier}' does not match '{$database}'.";
        }

        foreach (['phase14_checkpoint', 'phase15_checkpoint'] as $checkpointKey) {
            if (!isset($manifest[$checkpointKey])) continue;
            $hashKey = $checkpointKey.'_hash';
            $actualHash = hash('sha256', json_encode($manifest[$checkpointKey], JSON_UNESCAPED_SLASHES));
            if (!isset($manifest[$hashKey]) || !hash_equals((string) $manifest[$hashKey], $actualHash)) {
                $failures[] = "Immutable {$checkpointKey} hash mismatch.";
            }
        }

        $scenarioKeys = [
            'purchases_created', 'transfers_created', 'adjustments_created', 'sales_created',
            'payment_mutations', 'returns_created', 'exchanges_created', 'purchase_mutations',
            'purchase_returns_created', 'restaurant_sales_created', 'phase14', 'phase15', 'phase16',
        ];
        $scenarioSectionsComplete = collect($scenarioKeys)->every(
            fn (string $key) => array_key_exists($key, $manifest)
        );

        return [
            'passed' => $failures === [],
            'database_identifier' => $identifier,
            'phases' => $phases,
            'missing_phases' => $missingPhases,
            'duplicate_phases' => $duplicates,
            'missing_sections' => $missingKeys,
            'scenario_sections_complete' => $scenarioSectionsComplete,
            'failures' => $failures,
        ];
    }

    private function auditManifestReferences(array $manifest): array
    {
        $failures = [];
        $checks = [
            ['masters_created.warehouses', 'warehouses', 'name'],
            ['masters_created.customers', 'customers', 'name'],
            ['masters_created.suppliers', 'suppliers', 'name'],
            ['masters_created.floors', 'floors', 'name'],
            ['masters_created.tables', 'tables', 'name'],
            ['masters_created.modifier_groups', 'modifier_groups', 'name'],
            ['masters_created.modifiers', 'modifiers', 'name'],
            ['purchases_created', 'purchases', 'reference_no'],
            ['transfers_created', 'transfers', 'reference_no'],
            ['sales_created', 'sales', 'reference_no'],
            ['returns_created', 'returns', 'reference_no'],
            ['exchanges_created', 'sale_exchanges', 'reference_no'],
            ['purchase_returns_created', 'return_purchases', 'reference_no'],
            ['restaurant_sales_created', 'sales', 'reference_no'],
        ];

        $checked = 0;
        foreach ($checks as [$path, $table, $labelColumn]) {
            if (!Schema::hasTable($table)) continue;
            $rows = data_get($manifest, $path, []);
            if (!is_array($rows)) continue;
            foreach ($rows as $row) {
                if (!is_array($row) || !isset($row['id'])) continue;
                $checked++;
                $actual = DB::table($table)->where('id', $row['id'])->first();
                $expectedLabel = $row[$labelColumn] ?? null;
                if (!$actual) {
                    $failures[] = "{$path} id {$row['id']} is missing from {$table}.";
                } elseif ($expectedLabel !== null && (string) ($actual->{$labelColumn} ?? '') !== (string) $expectedLabel) {
                    $failures[] = "{$path} id {$row['id']} points to the wrong {$table} record.";
                }
            }
        }

        return ['passed' => $failures === [], 'references_checked' => $checked, 'failures' => $failures];
    }

    private function auditRelationships(): array
    {
        $failures = [];
        $checks = [
            ['product_warehouse', 'product_id', 'products'],
            ['product_warehouse', 'warehouse_id', 'warehouses'],
            ['product_purchases', 'purchase_id', 'purchases'],
            ['product_purchases', 'product_id', 'products'],
            ['product_sales', 'sale_id', 'sales'],
            ['product_sales', 'product_id', 'products'],
            ['product_transfer', 'transfer_id', 'transfers'],
            ['product_transfer', 'product_id', 'products'],
            ['product_adjustments', 'adjustment_id', 'adjustments'],
            ['product_adjustments', 'product_id', 'products'],
            ['product_returns', 'return_id', 'returns'],
            ['purchase_product_return', 'return_id', 'return_purchases'],
            ['journal_lines', 'journal_entry_id', 'journal_entries'],
            ['journal_lines', 'accounting_account_id', 'accounting_accounts'],
        ];
        $checked = 0;
        foreach ($checks as [$child, $foreignKey, $parent]) {
            if (!Schema::hasTable($child) || !Schema::hasTable($parent) || !Schema::hasColumn($child, $foreignKey)) continue;
            $checked++;
            $orphans = DB::table("{$child} as c")
                ->leftJoin("{$parent} as p", "p.id", '=', "c.{$foreignKey}")
                ->whereNotNull("c.{$foreignKey}")->whereNull('p.id')->count();
            if ($orphans) $failures[] = "{$child}.{$foreignKey} has {$orphans} orphan row(s).";
        }
        return ['passed' => $failures === [], 'relationships_checked' => $checked, 'failures' => $failures];
    }

    private function auditDuplicates(): array
    {
        $failures = [];
        $tables = ['purchases', 'transfers', 'adjustments', 'sales', 'returns', 'sale_exchanges', 'return_purchases', 'productions', 'service_jobs'];
        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'reference_no')) continue;
            $duplicates = DB::table($table)->select('reference_no', DB::raw('COUNT(*) as aggregate'))
                ->where('reference_no', 'like', 'DEMO-%')->groupBy('reference_no')->havingRaw('COUNT(*) > 1')->get();
            foreach ($duplicates as $duplicate) {
                $failures[] = "{$table}.{$duplicate->reference_no} occurs {$duplicate->aggregate} times.";
            }
        }
        $journalDuplicates = JournalEntry::query()
            ->select('source_type', 'source_id', 'event_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('source_type', 'source_id', 'event_type')->havingRaw('COUNT(*) > 1')->get();
        foreach ($journalDuplicates as $duplicate) {
            $failures[] = "Duplicate journal economic key {$duplicate->source_type}:{$duplicate->source_id}:{$duplicate->event_type}.";
        }
        return ['passed' => $failures === [], 'failures' => $failures];
    }

    private function auditAccounting(): array
    {
        $failures = [];
        $journals = JournalEntry::with('lines')->get();
        $unbalanced = 0;
        $sourceFailures = 0;
        $brokenReversals = 0;
        foreach ($journals as $journal) {
            $debit = (float) $journal->lines->sum('debit');
            $credit = (float) $journal->lines->sum('credit');
            if (abs($debit - $credit) >= .01) $unbalanced++;
            if ($this->sourceIntegrity->isFailure($this->sourceIntegrity->classify($journal))) $sourceFailures++;
            if ($journal->related_journal_entry_id && !JournalEntry::whereKey($journal->related_journal_entry_id)->exists()) {
                $brokenReversals++;
            }
        }
        if ($unbalanced) $failures[] = "{$unbalanced} unbalanced journal(s).";
        if ($sourceFailures) $failures[] = "{$sourceFailures} journal source-integrity failure(s).";
        if ($brokenReversals) $failures[] = "{$brokenReversals} broken reversal link(s).";

        return [
            'passed' => $failures === [],
            'journal_count' => $journals->count(),
            'journal_lines' => JournalLine::count(),
            'total_debit' => (float) JournalLine::sum('debit'),
            'total_credit' => (float) JournalLine::sum('credit'),
            'unbalanced' => $unbalanced,
            'source_integrity_failures' => $sourceFailures,
            'broken_reversal_links' => $brokenReversals,
            'failures' => $failures,
        ];
    }

    private function auditExpectedLedgers(array $manifest, array $actual, array $accounting): array
    {
        $failures = [];
        $expectedState = $manifest['phase17_expected_state'] ?? [];
        foreach (['stock_tuple_map', 'customer_ar', 'supplier_ap', 'payment_accounts', 'accounting_sources'] as $key) {
            if (!array_key_exists($key, $expectedState)) {
                $failures[] = "Independent expected ledger [{$key}] is absent.";
            }
        }

        $expectedTuple = $expectedState['stock_tuple_map'] ?? [];
        $actualTuple = DB::table('product_warehouse')->orderBy('product_id')->orderBy('variant_id')->orderBy('warehouse_id')
            ->get()->mapWithKeys(fn ($row) => [
                $row->product_id.':'.($row->variant_id ?: 0).':'.$row->warehouse_id => (float) $row->qty,
            ])->all();
        $tupleVariance = [];
        foreach (array_unique(array_merge(array_keys($expectedTuple), array_keys($actualTuple))) as $tuple) {
            $variance = round((float) ($actualTuple[$tuple] ?? 0) - (float) ($expectedTuple[$tuple] ?? 0), 6);
            if (abs($variance) >= .0001) $tupleVariance[$tuple] = $variance;
        }
        $expectedProduct = isset($expectedState['stock_product_qty']) ? (float) $expectedState['stock_product_qty'] : null;
        $expectedWarehouse = isset($expectedState['stock_warehouse_qty']) ? (float) $expectedState['stock_warehouse_qty'] : null;
        $productVariance = $expectedProduct === null ? null : (float) $actual['stock']['total_product_qty'] - $expectedProduct;
        $warehouseVariance = $expectedWarehouse === null ? null : (float) $actual['stock']['total_warehouse_qty'] - $expectedWarehouse;
        if ($tupleVariance) $failures[] = count($tupleVariance).' stock tuple variance(s) remain.';
        if ($productVariance === null || abs($productVariance) >= .0001) $failures[] = 'Expected vs actual Product quantity does not reconcile.';
        if ($warehouseVariance === null || abs($warehouseVariance) >= .0001) $failures[] = 'Expected vs actual warehouse quantity does not reconcile.';

        $expectedAr = isset($expectedState['customer_ar']) ? (float) $expectedState['customer_ar'] : null;
        $expectedAp = isset($expectedState['supplier_ap']) ? (float) $expectedState['supplier_ap'] : null;
        $arVariance = $expectedAr === null ? null : (float) $actual['financial']['customer_operational_dues'] - $expectedAr;
        $apVariance = $expectedAp === null ? null : (float) $actual['financial']['supplier_operational_dues'] - $expectedAp;
        if ($arVariance === null || abs($arVariance) >= .0001) $failures[] = 'Independent customer A/R does not reconcile to operational A/R.';
        if ($apVariance === null || abs($apVariance) >= .0001) $failures[] = 'Independent supplier A/P does not reconcile to operational A/P.';

        $paymentReconciliation = [];
        $operationalPaymentBalances = $this->actualPaymentAccountBalances();
        foreach (($expectedState['payment_accounts'] ?? []) as $id => $expected) {
            $account = DB::table('accounts')->where('id', $id)->first();
            $actualBalance = array_key_exists((int) $id, $operationalPaymentBalances)
                ? (float) $operationalPaymentBalances[(int) $id]
                : null;
            $variance = $actualBalance === null ? null : round($actualBalance - (float) $expected, 6);
            $paymentReconciliation[] = [
                'id' => (int) $id,
                'name' => $account->name ?? null,
                'account_no' => $account->account_no ?? null,
                'expected' => (float) $expected,
                'actual' => $actualBalance,
                'legacy_stored_balance' => $account ? (float) $account->total_balance : null,
                'variance' => $variance,
            ];
            if ($variance === null || abs($variance) >= .0001) {
                $failures[] = "Payment account {$id} does not reconcile.";
            }
        }

        $expectedKeys = collect($expectedState['accounting_sources'] ?? [])->pluck('economic_key')->sort()->values()->all();
        $actualSources = DB::table('journal_entries')->orderBy('id')->get()->map(fn ($journal) => [
            'economic_key' => $journal->source_type.':'.$journal->source_id.':'.$journal->event_type,
            'source_reference' => $journal->reference_no,
        ]);
        $actualKeys = $actualSources->pluck('economic_key')->sort()->values()->all();
        $missing = array_values(array_diff($expectedKeys, $actualKeys));
        $unexpected = array_values(array_diff($actualKeys, $expectedKeys));
        if ($missing) $failures[] = count($missing).' expected accounting posting(s) are missing.';
        if ($unexpected) $failures[] = count($unexpected).' unexpected accounting posting(s) exist.';
        if (count($expectedKeys) !== count(array_unique($expectedKeys))) {
            $failures[] = 'Expected accounting source set contains duplicate economic keys.';
        }

        return [
            'passed' => $failures === [],
            'stock' => [
                'expected_product_qty' => $expectedProduct,
                'expected_warehouse_qty' => $expectedWarehouse,
                'product_variance' => $productVariance,
                'warehouse_variance' => $warehouseVariance,
                'tuple_variance' => $tupleVariance,
            ],
            'financial' => [
                'expected_customer_ar' => $expectedAr,
                'customer_ar_variance' => $arVariance,
                'expected_supplier_ap' => $expectedAp,
                'supplier_ap_variance' => $apVariance,
                'payment_accounts' => $paymentReconciliation,
            ],
            'accounting' => [
                'expected_journal_count' => count($expectedKeys),
                'actual_journal_count' => $accounting['journal_count'],
                'missing' => $missing,
                'unexpected' => $unexpected,
            ],
            'failures' => $failures,
        ];
    }

    private function actualPaymentAccountBalances(): array
    {
        $balances = DB::table('accounts')->orderBy('id')->pluck('initial_balance', 'id')
            ->map(fn ($amount) => (float) $amount)->all();
        foreach (DB::table('payments')->orderBy('id')->get() as $payment) {
            if (!$payment->account_id) continue;
            $rate = (float) $payment->exchange_rate ?: 1;
            $amount = (float) $payment->amount / $rate;
            if ($payment->sale_id && !$payment->return_id) $sign = 1;
            elseif ($payment->sale_id && $payment->return_id) $sign = -1;
            elseif ($payment->purchase_return_id) $sign = 1;
            elseif ($payment->purchase_id) $sign = -1;
            else continue;
            $balances[(int) $payment->account_id] = round((float) ($balances[(int) $payment->account_id] ?? 0) + $sign * $amount, 6);
        }
        if (Schema::hasTable('expenses')) {
            foreach (DB::table('expenses')->whereNotNull('account_id')->whereNotNull('cash_register_id')->get() as $expense) {
                $balances[(int) $expense->account_id] = round((float) ($balances[(int) $expense->account_id] ?? 0) - (float) $expense->amount, 6);
            }
        }
        return $balances;
    }

    private function scenarioInventory(): array
    {
        $count = fn (string $table, string $pattern) => Schema::hasTable($table)
            ? DB::table($table)->where('reference_no', 'like', $pattern)->count() : 0;
        return [
            'purchases' => $count('purchases', 'DEMO-PUR-%'),
            'transfers' => $count('transfers', 'DEMO-TRF-%'),
            'adjustments' => $count('adjustments', 'DEMO-ADJ-%'),
            'sales' => $count('sales', 'DEMO-SALE-%'),
            'sale_returns' => $count('returns', 'DEMO-RET-%'),
            'exchanges' => $count('sale_exchanges', 'DEMO-EXCH-%'),
            'purchase_returns' => $count('return_purchases', 'DEMO-PRET-%'),
            'restaurant_sales' => $count('sales', 'DEMO-REST-%'),
            'productions' => $count('productions', 'DEMO-PROD-%'),
            'repairs' => $count('service_jobs', 'DEMO-REP-%'),
        ];
    }
}
