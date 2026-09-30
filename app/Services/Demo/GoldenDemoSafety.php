<?php

namespace App\Services\Demo;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transfer;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\SaleExchange;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoldenDemoSafety
{
    public static function assertExactDemoDatabase(): void
    {
        $current = DB::connection()->getDatabaseName();
        $allowed = env('DEMO_ALLOWED_DATABASE');
        if (!$allowed || strtolower($current) !== strtolower($allowed)) {
            throw new RuntimeException("SAFETY VIOLATION: Phase execution database '{$current}' is not the explicitly allowed demo database.");
        }
    }
    /**
     * Enforce strict safety guards before executing demo builder commands.
     *
     * @param bool $force
     * @return void
     * @throws RuntimeException
     */
    public static function assertSafeToBuild(bool $force = false): void
    {
        // Guard 1: APP_ENV must not be production
        if (App::environment('production')) {
            throw new RuntimeException("SAFETY VIOLATION: Golden Demo Builder CANNOT be run in 'production' environment!");
        }

        // Guard 2: ALLOW_DEMO_BUILD must be explicitly set to true
        $allowDemoBuild = env('ALLOW_DEMO_BUILD', false);
        if ($allowDemoBuild !== true && $allowDemoBuild !== 'true' && $allowDemoBuild !== '1') {
            throw new RuntimeException("SAFETY VIOLATION: Set ALLOW_DEMO_BUILD=true in .env to enable the Golden Demo Builder.");
        }

        // Guard 3: Exact Database Name Match
        $currentDb = DB::connection()->getDatabaseName();
        $allowedDb = env('DEMO_ALLOWED_DATABASE', 'salepro');
        if (strtolower($currentDb) !== strtolower($allowedDb)) {
            throw new RuntimeException(sprintf(
                "SAFETY VIOLATION: Current database '%s' does not match DEMO_ALLOWED_DATABASE '%s'!",
                $currentDb,
                $allowedDb
            ));
        }

        // Guard 4: Require --force flag or non-interactive confirmation
        if (!$force && App::runningInConsole() && !app()->runningUnitTests()) {
            // Note: Command handler handles interactive prompt or requires --force
        }

        // Guard 5: Check for existing Golden Demo transaction data
        if (self::hasExistingDemoTransactions()) {
            throw new RuntimeException(
                "SAFETY VIOLATION: Existing Golden Demo transaction data detected. " .
                "In-place rebuilding is disabled; create a fresh dedicated demo database instead."
            );
        }
    }

    /**
     * Clean existing Golden Demo transaction data safely.
     */
    public static function cleanExistingDemoTransactions(): void
    {
        $demoPurchaseIds = DB::table('purchases')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoSaleIds = DB::table('sales')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoTransferIds = DB::table('transfers')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoAdjustmentIds = DB::table('adjustments')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoReturnIds = DB::table('returns')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoPurchaseReturnIds = DB::table('return_purchases')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        $demoExchangeIds = DB::table('sale_exchanges')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();

        $demoPaymentIds = DB::table('payments')
            ->where(function ($query) use ($demoPurchaseIds, $demoSaleIds, $demoReturnIds, $demoPurchaseReturnIds) {
                $query->where('payment_reference', 'LIKE', 'DEMO-%');
                if ($demoPurchaseIds) {
                    $query->orWhereIn('purchase_id', $demoPurchaseIds);
                }
                if ($demoSaleIds) {
                    $query->orWhereIn('sale_id', $demoSaleIds);
                }
                if ($demoReturnIds) {
                    $query->orWhereIn('return_id', $demoReturnIds);
                }
                if ($demoPurchaseReturnIds) {
                    $query->orWhereIn('purchase_return_id', $demoPurchaseReturnIds);
                }
            })
            ->pluck('id')
            ->toArray();

        self::deleteSourceOwnedJournals([
            \App\Models\Purchase::class => $demoPurchaseIds,
            \App\Models\Sale::class => $demoSaleIds,
            \App\Models\Payment::class => $demoPaymentIds,
            \App\Models\Transfer::class => $demoTransferIds,
            \App\Models\Adjustment::class => $demoAdjustmentIds,
            \App\Models\Returns::class => $demoReturnIds,
            \App\Models\ReturnPurchase::class => $demoPurchaseReturnIds,
            \App\Models\SaleExchange::class => $demoExchangeIds,
        ]);

        if (count($demoPurchaseIds)) {
            DB::table('product_purchases')->whereIn('purchase_id', $demoPurchaseIds)->delete();
            DB::table('payments')->whereIn('purchase_id', $demoPurchaseIds)->delete();
            DB::table('purchases')->whereIn('id', $demoPurchaseIds)->delete();
        }

        // Clean DEMO transfers
        if (count($demoTransferIds)) {
            DB::table('product_transfer')->whereIn('transfer_id', $demoTransferIds)->delete();
            DB::table('transfers')->whereIn('id', $demoTransferIds)->delete();
        }

        // Clean DEMO adjustments
        if (count($demoAdjustmentIds)) {
            DB::table('product_adjustments')->whereIn('adjustment_id', $demoAdjustmentIds)->delete();
            DB::table('adjustments')->whereIn('id', $demoAdjustmentIds)->delete();
        }


        // Clean DEMO sales & payments
        if (count($demoSaleIds)) {
            $productSaleIds = DB::table('product_sales')->whereIn('sale_id', $demoSaleIds)->pluck('id')->toArray();
            if ($productSaleIds && \Illuminate\Support\Facades\Schema::hasTable('product_sale_modifiers')) {
                DB::table('product_sale_modifiers')->whereIn('product_sale_id', $productSaleIds)->delete();
            }
            DB::table('product_sales')->whereIn('sale_id', $demoSaleIds)->delete();
            DB::table('payments')->whereIn('sale_id', $demoSaleIds)->delete();
            DB::table('sales')->whereIn('id', $demoSaleIds)->delete();
        }

        DB::table('payments')->whereIn('id', $demoPaymentIds)->delete();




        // Clean DEMO sale returns
        if (count($demoReturnIds)) {
            DB::table('product_returns')->whereIn('return_id', $demoReturnIds)->delete();
            DB::table('payments')->whereIn('return_id', $demoReturnIds)->delete();
            DB::table('returns')->whereIn('id', $demoReturnIds)->delete();
        }

        // Clean DEMO sale exchanges
        if (count($demoExchangeIds)) {
            DB::table('product_exchanges')->whereIn('exchange_id', $demoExchangeIds)->delete();
            DB::table('sale_exchanges')->whereIn('id', $demoExchangeIds)->delete();
        }

        if (count($demoPurchaseReturnIds)) {
            DB::table('purchase_product_return')->whereIn('return_id', $demoPurchaseReturnIds)->delete();
            DB::table('payments')->whereIn('purchase_return_id', $demoPurchaseReturnIds)->delete();
            DB::table('return_purchases')->whereIn('id', $demoPurchaseReturnIds)->delete();
        }


        // Clean DEMO journals

        $demoJournalIds = DB::table('journal_entries')->where('reference_no', 'LIKE', 'DEMO-%')->pluck('id')->toArray();
        if (count($demoJournalIds)) {
            DB::table('journal_lines')->whereIn('journal_entry_id', $demoJournalIds)->delete();
            DB::table('journal_entries')->whereIn('id', $demoJournalIds)->delete();
        }
    }

    private static function deleteSourceOwnedJournals(array $sources): void
    {
        $journalIds = DB::table('journal_entries')
            ->where(function ($query) use ($sources) {
                foreach ($sources as $sourceType => $sourceIds) {
                    if (!$sourceIds) {
                        continue;
                    }

                    $query->orWhere(function ($sourceQuery) use ($sourceType, $sourceIds) {
                        $sourceQuery->whereIn('source_type', [
                            $sourceType,
                            class_basename($sourceType),
                            '\\'.ltrim($sourceType, '\\'),
                        ])->whereIn('source_id', $sourceIds);
                    });
                }
            })
            ->pluck('id')
            ->toArray();

        if ($journalIds) {
            DB::table('journal_lines')->whereIn('journal_entry_id', $journalIds)->delete();
            DB::table('journal_entries')->whereIn('id', $journalIds)->delete();
        }
    }



    /**
     * Check whether existing Golden Demo transaction records exist.
     *
     * @return bool
     */
    public static function hasExistingDemoTransactions(): bool
    {
        $hasDemoPurchases = \Illuminate\Support\Facades\Schema::hasTable('purchases') && DB::table('purchases')->where('reference_no', 'LIKE', 'DEMO-%')->exists();
        $hasDemoSales = \Illuminate\Support\Facades\Schema::hasTable('sales') && DB::table('sales')->where('reference_no', 'LIKE', 'DEMO-%')->exists();
        $hasDemoTransfers = \Illuminate\Support\Facades\Schema::hasTable('transfers') && DB::table('transfers')->where('reference_no', 'LIKE', 'DEMO-%')->exists();
        $hasDemoReturns = \Illuminate\Support\Facades\Schema::hasTable('returns') && DB::table('returns')->where('reference_no', 'LIKE', 'DEMO-%')->exists();
        $hasDemoPurchaseReturns = \Illuminate\Support\Facades\Schema::hasTable('return_purchases') && DB::table('return_purchases')->where('reference_no', 'LIKE', 'DEMO-%')->exists();
        $hasDemoExchanges = \Illuminate\Support\Facades\Schema::hasTable('sale_exchanges') && DB::table('sale_exchanges')->where('reference_no', 'LIKE', 'DEMO-%')->exists();

        return $hasDemoPurchases || $hasDemoSales || $hasDemoTransfers || $hasDemoReturns || $hasDemoPurchaseReturns || $hasDemoExchanges;
    }


}
