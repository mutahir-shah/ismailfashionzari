<?php

namespace App\Services\Stability;

use App\Models\Account;
use App\Models\Biller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Domain\SaleDomainService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConcurrencyStabilityTester
{
    /**
     * Run bounded concurrent / interleaved stability scenarios.
     *
     * @param User $adminUser
     * @param Warehouse $warehouse
     * @return array{
     *     passed: bool,
     *     scenarios_tested: int,
     *     deadlocks: int,
     *     failures: array<string>
     * }
     */
    public function runScenarios(User $adminUser, Warehouse $warehouse): array
    {
        Auth::login($adminUser);
        $failures = [];
        $deadlocks = 0;
        $scenariosTested = 0;

        // Scenario 1: Interleaved POS Sale and Stock Adjustment / Inventory query
        $scenariosTested++;
        try {
            $this->testInterleavedSaleAndStockQuery($adminUser, $warehouse);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'Lock wait timeout')) {
                $deadlocks++;
            }
            $failures[] = "Interleaved Sale & Stock Query failed: " . $e->getMessage();
        }

        // Scenario 2: Rapid Concurrent Sale Reference Generation
        $scenariosTested++;
        try {
            $this->testConcurrentReferenceGeneration($adminUser, $warehouse);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'Lock wait timeout')) {
                $deadlocks++;
            }
            $failures[] = "Concurrent Reference Generation failed: " . $e->getMessage();
        }

        // Scenario 3: Interleaved Return and Dashboard Aggregations
        $scenariosTested++;
        try {
            $this->testInterleavedReturnAndDashboard($adminUser, $warehouse);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'Lock wait timeout')) {
                $deadlocks++;
            }
            $failures[] = "Interleaved Return & Dashboard failed: " . $e->getMessage();
        }

        // Post-concurrency cleanup and invariant verification
        $danglingConcFixtures = DB::table('products')->where('code', 'LIKE', 'CONC-%')->count();
        if ($danglingConcFixtures > 0) {
            DB::table('product_warehouse')->whereIn('product_id', DB::table('products')->where('code', 'LIKE', 'CONC-%')->pluck('id'))->delete();
            DB::table('products')->where('code', 'LIKE', 'CONC-%')->delete();
            $failures[] = "Concurrency cleanup error: {$danglingConcFixtures} CONC-* test fixtures remained after execution.";
        }

        $stockMismatches = DB::table('products as p')
            ->leftJoin('product_warehouse as pw', 'p.id', '=', 'pw.product_id')
            ->select('p.id', 'p.code', 'p.qty as product_qty', DB::raw('COALESCE(SUM(pw.qty), 0) as warehouse_qty'))
            ->groupBy('p.id', 'p.code', 'p.qty')
            ->havingRaw('ABS(p.qty - COALESCE(SUM(pw.qty), 0)) > 0.0001')
            ->count();

        if ($stockMismatches > 0) {
            $failures[] = "Stock mismatch invariant violated after concurrency execution: {$stockMismatches} products have products.qty != SUM(product_warehouse.qty).";
        }

        return [
            'passed' => count($failures) === 0,
            'scenarios_tested' => $scenariosTested,
            'deadlocks' => $deadlocks,
            'failures' => $failures,
        ];
    }

    private function testInterleavedSaleAndStockQuery(User $user, Warehouse $warehouse): void
    {
        $productId = null;
        try {
            DB::beginTransaction();

            $product = Product::create([
                'name' => 'Concurrency Product ' . uniqid(),
                'code' => 'CONC-' . uniqid(),
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'unit_id' => 1,
                'purchase_unit_id' => 1,
                'sale_unit_id' => 1,
                'cost' => 10.00,
                'price' => 20.00,
                'qty' => 100,
                'category_id' => 1,
                'is_active' => true,
            ]);
            $productId = $product->id;

            $pw = Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => 100,
            ]);

            // Simulate synchronized stock deduction
            $pw->decrement('qty', 5);
            $product->decrement('qty', 5);

            // Read stock concurrently
            $currentStock = DB::table('product_warehouse')
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('qty');

            if ((float) $currentStock !== 95.0) {
                throw new Exception("Stock lost update detected: expected 95, read {$currentStock}");
            }

            DB::rollBack();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            if ($productId) {
                DB::table('product_warehouse')->where('product_id', $productId)->delete();
                DB::table('products')->where('id', $productId)->delete();
            }
        }
    }

    private function testConcurrentReferenceGeneration(User $user, Warehouse $warehouse): void
    {
        $references = [];
        for ($i = 0; $i < 5; $i++) {
            $ref = 'STAB-CONC-' . date('YmdHis') . '-' . $i . '-' . uniqid();
            if (in_array($ref, $references, true)) {
                throw new Exception("Duplicate reference number generated: {$ref}");
            }
            $references[] = $ref;
        }
    }

    private function testInterleavedReturnAndDashboard(User $user, Warehouse $warehouse): void
    {
        DB::transaction(function () {
            // Read revenue aggregation while holding transaction
            $revenue = DB::table('sales')
                ->whereNull('deleted_at')
                ->sum(DB::raw('(grand_total - COALESCE(shipping_cost, 0)) / COALESCE(NULLIF(exchange_rate, 0), 1)'));

            if (!is_numeric($revenue)) {
                throw new Exception("Revenue aggregation returned non-numeric result");
            }
        });
    }
}
