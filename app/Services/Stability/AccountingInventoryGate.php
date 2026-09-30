<?php

namespace App\Services\Stability;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Purchase;
use App\Services\AccountingService;
use App\Services\FinancialReportingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AccountingInventoryGate
{
    /**
     * Run the accounting and inventory release gate.
     *
     * @return array{
     *     passed: bool,
     *     negative_stock_count: int,
     *     stock_mismatch_count: int,
     *     overpaid_purchases_count: int,
     *     journal_integrity_passed: bool,
     *     accounting_critical_failures: int,
     *     accounting_high_failures: int,
     *     failures: array<string>
     * }
     */
    public function checkGate(): array
    {
        $failures = [];

        // 1. Check Negative Stock in product_warehouse
        $negativeStockCount = (int) DB::table('product_warehouse')
            ->where('qty', '<', 0)
            ->count();

        if ($negativeStockCount > 0) {
            $failures[] = "Found {$negativeStockCount} product_warehouse records with negative stock.";
        }

        // 2. Check Product vs Warehouse aggregate stock mismatches
        $stockMismatchCount = 0;
        $products = DB::table('products')
            ->where('is_active', true)
            ->whereIn('type', ['standard', 'combo'])
            ->select('id', 'name', 'qty')
            ->get();

        foreach ($products as $p) {
            $warehouseSum = (float) DB::table('product_warehouse')
                ->where('product_id', $p->id)
                ->sum('qty');

            if (abs((float) $p->qty - $warehouseSum) > 0.001) {
                // If product is non-variant standard and warehouse sum differs
                $isVariant = DB::table('products')->where('id', $p->id)->value('is_variant');
                if (!$isVariant) {
                    $stockMismatchCount++;
                }
            }
        }

        if ($stockMismatchCount > 0) {
            $failures[] = "Found {$stockMismatchCount} products with warehouse aggregate stock mismatches.";
        }

        // 3. Check Overpaid Purchases
        $overpaidPurchasesCount = (int) DB::table('purchases')
            ->whereNull('deleted_at')
            ->whereRaw('paid_amount > (grand_total + 0.01)')
            ->count();

        if ($overpaidPurchasesCount > 0) {
            $failures[] = "Found {$overpaidPurchasesCount} purchases with overpaid amounts exceeding grand total.";
        }

        // 4. Check Journal Line Integrity (Debits == Credits per Entry)
        $unbalancedCount = JournalEntry::with('lines')->get()->filter(function ($entry) {
            $debits = (float) $entry->lines->sum('debit');
            $credits = (float) $entry->lines->sum('credit');
            return abs(round($debits, 2) - round($credits, 2)) > 0.01;
        })->count();

        $journalIntegrityPassed = ($unbalancedCount === 0);
        if (!$journalIntegrityPassed) {
            $failures[] = "Found {$unbalancedCount} unbalanced journal entries.";
        }

        // 5. Run accounting:audit if command exists
        $accountingCritical = 0;
        $accountingHigh = 0;
        try {
            if (array_key_exists('accounting:audit', Artisan::all())) {
                $exitCode = Artisan::call('accounting:audit');
                if ($exitCode !== 0) {
                    $accountingCritical++;
                    $failures[] = "Artisan command 'accounting:audit' returned non-zero exit code: {$exitCode}";
                }
            }
        } catch (\Throwable $e) {
            $accountingHigh++;
            $failures[] = "Accounting audit check failed: " . $e->getMessage();
        }
        // 6. Application Referential Integrity Audit
        $referentialIntegrityFailures = 0;
        $referentialDetails = [];

        $refChecks = [
            ['table' => 'products', 'column' => 'unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => false, 'allow_zero_for_types' => ['combo', 'service', 'digital']],
            ['table' => 'products', 'column' => 'sale_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'products', 'column' => 'purchase_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_sales', 'column' => 'sale_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_returns', 'column' => 'sale_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_purchases', 'column' => 'purchase_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'purchase_product_return', 'column' => 'purchase_unit_id', 'target_table' => 'units', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_sales', 'column' => 'product_id', 'target_table' => 'products', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'product_purchases', 'column' => 'product_id', 'target_table' => 'products', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'product_returns', 'column' => 'product_id', 'target_table' => 'products', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'purchase_product_return', 'column' => 'product_id', 'target_table' => 'products', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'sales', 'column' => 'warehouse_id', 'target_table' => 'warehouses', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'purchases', 'column' => 'warehouse_id', 'target_table' => 'warehouses', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'returns', 'column' => 'warehouse_id', 'target_table' => 'warehouses', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'return_purchases', 'column' => 'warehouse_id', 'target_table' => 'warehouses', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'product_warehouse', 'column' => 'product_id', 'target_table' => 'products', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'product_warehouse', 'column' => 'warehouse_id', 'target_table' => 'warehouses', 'target_col' => 'id', 'allow_null' => false],
            ['table' => 'sales', 'column' => 'currency_id', 'target_table' => 'currencies', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'purchases', 'column' => 'currency_id', 'target_table' => 'currencies', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'payments', 'column' => 'account_id', 'target_table' => 'accounts', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_sales', 'column' => 'variant_id', 'target_table' => 'variants', 'target_col' => 'id', 'allow_null' => true],
            ['table' => 'product_sales', 'column' => 'product_batch_id', 'target_table' => 'product_batches', 'target_col' => 'id', 'allow_null' => true],
        ];

        foreach ($refChecks as $check) {
            $table = $check['table'];
            $col = $check['column'];
            $target = $check['target_table'];
            $targetCol = $check['target_col'];
            $allowNull = $check['allow_null'];

            if (!DB::getSchemaBuilder()->hasTable($table) || !DB::getSchemaBuilder()->hasTable($target)) {
                continue;
            }

            $query = DB::table($table)->whereNotNull($col);
            if ($allowNull) {
                $query->where($col, '!=', 0);
            }
            if (isset($check['allow_zero_for_types'])) {
                $query->where(function ($typed) use ($col, $check) {
                    $typed->where($col, '!=', 0)
                        ->orWhereNotIn('type', $check['allow_zero_for_types']);
                });
            }

            $orphans = $query->whereNotIn($col, function ($sub) use ($target, $targetCol) {
                $sub->select($targetCol)->from($target);
            })->pluck($col)->toArray();

            if (!empty($orphans)) {
                $distinctCount = count(array_unique($orphans));
                $sentinelCount = count(array_filter($orphans, fn($v) => $v == 0));
                $nonZeroCount = count($orphans) - $sentinelCount;
                $referentialIntegrityFailures += count($orphans);
                $msg = "Referential violation in {$table}.{$col} -> {$target}.{$targetCol}: " . count($orphans) . " orphan rows (non-zero: {$nonZeroCount}, sentinel 0: {$sentinelCount}).";
                $referentialDetails[] = $msg;
                $failures[] = $msg;
            }
        }

        return [
            'passed' => count($failures) === 0,
            'negative_stock_count' => $negativeStockCount,
            'stock_mismatch_count' => $stockMismatchCount,
            'overpaid_purchases_count' => $overpaidPurchasesCount,
            'journal_integrity_passed' => $journalIntegrityPassed,
            'accounting_critical_failures' => $accountingCritical,
            'accounting_high_failures' => $accountingHigh,
            'referential_integrity_failures' => $referentialIntegrityFailures,
            'referential_details' => $referentialDetails,
            'failures' => $failures,
        ];
    }
}
