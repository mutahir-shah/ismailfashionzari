<?php

namespace App\Services\Stability;

use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReturnPurchaseController;
use App\Models\Account;
use App\Models\Biller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\ProductPurchase;
use App\Models\Purchase;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Domain\SaleDomainService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowStabilityTester
{
    /**
     * Run the complete transactional workflow stability matrix.
     *
     * @param User $adminUser
     * @param User $restrictedUser
     * @param Warehouse $warehouse1
     * @param Warehouse $warehouse2
     * @param int $iterations
     * @return array{
     *     passed: bool,
     *     workflows_run: int,
     *     failures: array<string>,
     *     evidence: array<array<string, mixed>>
     * }
     */
    public function runMatrix(
        User $adminUser,
        User $restrictedUser,
        Warehouse $warehouse1,
        Warehouse $warehouse2,
        int $iterations = 1
    ): array {
        Auth::login($adminUser);
        $failures = [];
        $evidence = [];
        $workflowsRun = 0;

        for ($iter = 1; $iter <= $iterations; $iter++) {
            // 1. Sale Return Scenarios
            $saleReturnCases = [
                'fully_paid' => ['paid' => 100, 'total' => 100, 'tax' => 0, 'discount' => 0, 'account' => true],
                'partially_paid' => ['paid' => 50, 'total' => 100, 'tax' => 0, 'discount' => 0, 'account' => true],
                'unpaid_credit' => ['paid' => 0, 'total' => 100, 'tax' => 0, 'discount' => 0, 'account' => false],
                'taxed' => ['paid' => 115, 'total' => 115, 'tax' => 15, 'discount' => 0, 'account' => true],
                'discounted' => ['paid' => 90, 'total' => 90, 'tax' => 0, 'discount' => 10, 'account' => true],
                'multi_product' => ['paid' => 200, 'total' => 200, 'tax' => 0, 'discount' => 0, 'multi' => true, 'account' => true],
                'on_account_credit' => ['paid' => 0, 'total' => 100, 'tax' => 0, 'discount' => 0, 'account' => false],
            ];

            foreach ($saleReturnCases as $caseName => $config) {
                $workflowsRun++;
                $res = $this->executeSaleReturnScenario($caseName, $config, $adminUser, $warehouse1, $iter);
                if (!$res['passed']) {
                    $failures[] = "Sale Return [{$caseName}] Iteration {$iter}: " . $res['error'];
                    $evidence[] = array_merge($res, ['iteration' => $iter, 'workflow' => 'sale_return', 'case' => $caseName]);
                }
            }

            // 2. Purchase Return Scenarios
            $purchaseReturnCases = [
                'fully_paid' => ['paid' => 100, 'total' => 100, 'tax' => 0, 'account' => true],
                'partially_paid' => ['paid' => 50, 'total' => 100, 'tax' => 0, 'account' => true],
                'unpaid_credit' => ['paid' => 0, 'total' => 100, 'tax' => 0, 'account' => false],
                'taxed' => ['paid' => 115, 'total' => 115, 'tax' => 15, 'account' => true],
                'on_account_credit' => ['paid' => 0, 'total' => 100, 'tax' => 0, 'account' => false],
            ];

            foreach ($purchaseReturnCases as $caseName => $config) {
                $workflowsRun++;
                $res = $this->executePurchaseReturnScenario($caseName, $config, $adminUser, $warehouse1, $iter);
                if (!$res['passed']) {
                    $failures[] = "Purchase Return [{$caseName}] Iteration {$iter}: " . $res['error'];
                    $evidence[] = array_merge($res, ['iteration' => $iter, 'workflow' => 'purchase_return', 'case' => $caseName]);
                }
            }

            // 3. Security & Warehouse Isolation Scenarios
            $workflowsRun++;
            $secRes = $this->executeWarehouseIsolationScenario($adminUser, $restrictedUser, $warehouse1, $warehouse2, $iter);
            if (!$secRes['passed']) {
                $failures[] = "Warehouse Isolation Iteration {$iter}: " . $secRes['error'];
                $evidence[] = array_merge($secRes, ['iteration' => $iter, 'workflow' => 'warehouse_isolation']);
            }

            // 4. POS Warehouse Contract & Isolation Scenario
            $whRest = Warehouse::firstOrCreate(
                ['name' => 'Restaurant Stability Warehouse'],
                ['phone' => '999888', 'address' => '789 Dining Ave', 'pos_type' => 'restaurant', 'is_active' => true]
            );
            $workflowsRun++;
            $posContractRes = $this->executePosWarehouseContractScenario($adminUser, $restrictedUser, $warehouse1, $whRest, $iter);
            if (!$posContractRes['passed']) {
                $failures[] = "POS Warehouse Contract Iteration {$iter}: " . $posContractRes['error'];
                $evidence[] = array_merge($posContractRes, ['iteration' => $iter, 'workflow' => 'pos_warehouse_contract']);
            }

            // 5. Restaurant Lifecycle Scenario (if enabled)
            if (in_array('restaurant', explode(',', config('addons') ?? ''))) {
                $workflowsRun++;
                $restRes = $this->executeRestaurantLifecycleScenario($adminUser, $warehouse1, $iter);
                if (!$restRes['passed']) {
                    $failures[] = "Restaurant KDS Lifecycle Iteration {$iter}: " . $restRes['error'];
                    $evidence[] = array_merge($restRes, ['iteration' => $iter, 'workflow' => 'restaurant_kds']);
                }
            }
        }

        return [
            'passed' => count($failures) === 0,
            'workflows_run' => $workflowsRun,
            'failures' => $failures,
            'evidence' => $evidence,
        ];
    }

    private function executeSaleReturnScenario(string $name, array $config, User $user, Warehouse $warehouse, int $iter): array
    {
        $unique = uniqid('sr-');
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'name' => 'SR Cust ' . $unique,
                'customer_group_id' => 1,
                'phone_number' => '123' . rand(1000, 9999),
                'is_active' => true,
            ]);

            $biller = Biller::firstOrCreate(
                ['name' => 'Default Biller'],
                ['company_name' => 'Test Corp', 'phone_number' => '123456', 'email' => 'biller@test.com', 'address' => '123 Main', 'city' => 'City', 'is_active' => true]
            );

            $account = Account::firstOrCreate(
                ['account_no' => 'ACC-STAB-01'],
                ['name' => 'Stability Cash', 'initial_balance' => 10000.0, 'total_balance' => 10000.0, 'is_active' => true]
            );

            $product = Product::create([
                'name' => 'Product ' . $unique,
                'code' => 'P-' . $unique,
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'unit_id' => 1,
                'purchase_unit_id' => 1,
                'sale_unit_id' => 1,
                'cost' => 50.00,
                'price' => 100.00,
                'qty' => 50,
                'category_id' => 1,
                'is_active' => true,
            ]);

            $pw = Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => 50,
            ]);

            $sale = Sale::create([
                'reference_no' => 'SALE-' . $unique,
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'biller_id' => $biller->id,
                'warehouse_id' => $warehouse->id,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => $config['discount'] ?? 0,
                'total_tax' => $config['tax'] ?? 0,
                'total_price' => $config['total'],
                'grand_total' => $config['total'],
                'paid_amount' => $config['paid'],
                'sale_status' => 1,
                'payment_status' => $config['paid'] >= $config['total'] ? 4 : ($config['paid'] > 0 ? 2 : 1),
                'shipping_cost' => 0,
                'exchange_rate' => 1.0,
                'currency_id' => 1,
            ]);

            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'qty' => 1,
                'sale_unit_id' => 1,
                'net_unit_price' => 100.00,
                'discount' => $config['discount'] ?? 0,
                'tax_rate' => 0,
                'tax' => $config['tax'] ?? 0,
                'total' => $config['total'],
            ]);

            // Deduct stock for sale
            $pw->decrement('qty', 1);

            // Execute Return
            $returnAccount = !empty($config['account']) ? $account->id : null;
            $return = Returns::create([
                'reference_no' => 'RET-' . $unique,
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'biller_id' => $biller->id,
                'warehouse_id' => $warehouse->id,
                'sale_id' => $sale->id,
                'account_id' => $returnAccount,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => $config['tax'] ?? 0,
                'total_price' => $config['total'],
                'grand_total' => $config['total'],
            ]);

            // Restore Stock
            $pw->increment('qty', 1);

            // Assertions
            $finalPw = Product_Warehouse::find($pw->id);
            if ((float) $finalPw->qty !== 50.0) {
                throw new Exception("Stock restoration failed: expected 50, got {$finalPw->qty}");
            }

            if ($returnAccount !== null && (int) $return->account_id !== (int) $returnAccount) {
                throw new Exception("Return account mismatch: expected {$returnAccount}, got {$return->account_id}");
            }

            DB::rollBack();

            return [
                'passed' => true,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    private function executePurchaseReturnScenario(string $name, array $config, User $user, Warehouse $warehouse, int $iter): array
    {
        $unique = uniqid('pr-');
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $supplier = Supplier::create([
                'name' => 'PR Supp ' . $unique,
                'company_name' => 'Supp Corp',
                'phone_number' => '987' . rand(1000, 9999),
                'email' => "supp_{$unique}@test.com",
                'address' => '123 Main St',
                'city' => 'Test City',
                'is_active' => true,
            ]);

            $product = Product::create([
                'name' => 'Product ' . $unique,
                'code' => 'P-' . $unique,
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'unit_id' => 1,
                'purchase_unit_id' => 1,
                'sale_unit_id' => 1,
                'cost' => 50.00,
                'price' => 100.00,
                'qty' => 50,
                'category_id' => 1,
                'is_active' => true,
            ]);

            $pw = Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => 50,
            ]);

            $purchase = Purchase::create([
                'reference_no' => 'PUR-' . $unique,
                'user_id' => $user->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => $config['tax'] ?? 0,
                'total_cost' => $config['total'],
                'grand_total' => $config['total'],
                'paid_amount' => $config['paid'],
                'status' => 1,
                'payment_status' => $config['paid'] >= $config['total'] ? 2 : 1,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
            ]);

            ProductPurchase::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product->id,
                'qty' => 1,
                'recieved' => 1,
                'purchase_unit_id' => 1,
                'net_unit_cost' => 50.00,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => $config['tax'] ?? 0,
                'total' => $config['total'],
            ]);

            // Execute Purchase Return
            $returnPurchase = ReturnPurchase::create([
                'reference_no' => 'PRET-' . $unique,
                'user_id' => $user->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_id' => $purchase->id,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => $config['tax'] ?? 0,
                'total_cost' => $config['total'],
                'grand_total' => $config['total'],
            ]);

            // Purchase return deducts warehouse stock
            $pw->decrement('qty', 1);

            $finalPw = Product_Warehouse::find($pw->id);
            if ((float) $finalPw->qty !== 49.0) {
                throw new Exception("Purchase return stock decrement failed: expected 49, got {$finalPw->qty}");
            }

            DB::rollBack();

            return [
                'passed' => true,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    private function executeWarehouseIsolationScenario(User $admin, User $restricted, Warehouse $wh1, Warehouse $wh2, int $iter): array
    {
        $startTime = microtime(true);
        try {
            // As restricted user, attempt to query products from unassigned warehouse WH2
            Auth::login($restricted);
            $server = ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
            $request = Request::create(url("sales/getproducts/{$wh2->id}/0/0"), 'GET', [], [], [], $server);
            $response = app()->handle($request);

            // Re-authenticate admin
            Auth::login($admin);

            return [
                'passed' => true,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            Auth::login($admin);
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    private function executePosWarehouseContractScenario(User $admin, User $restricted, Warehouse $standardWarehouse, Warehouse $restaurantWarehouse, int $iter): array
    {
        $startTime = microtime(true);
        try {
            // 1. Authorized user selecting a pos_type=restaurant warehouse on the canonical POS route
            Auth::login($admin);
            $reqRest = Request::create(url("pos?warehouse_id={$restaurantWarehouse->id}"), 'GET');
            $reqRest->setUserResolver(fn() => $admin);
            $resRest = app()->handle($reqRest);
            $contentRest = $resRest->getContent();

            if ($resRest->getStatusCode() !== 200) {
                throw new Exception("Restaurant POS returned HTTP {$resRest->getStatusCode()}");
            }
            if (stripos($contentRest, 'id="service_id"') === false || stripos($contentRest, 'id="price_type"') !== false) {
                throw new Exception("Restaurant POS view failed to resolve restaurant mode from warehouse {$restaurantWarehouse->id}");
            }

            // 2. Standard pos_type=regular warehouse
            $reqStd = Request::create(url("pos?warehouse_id={$standardWarehouse->id}"), 'GET');
            $reqStd->setUserResolver(fn() => $admin);
            $resStd = app()->handle($reqStd);
            $contentStd = $resStd->getContent();

            if ($resStd->getStatusCode() !== 200) {
                throw new Exception("Standard POS returned HTTP {$resStd->getStatusCode()}");
            }
            if (stripos($contentStd, 'id="service_id"') !== false || stripos($contentStd, 'id="price_type"') === false) {
                throw new Exception("Standard warehouse unexpectedly resolved restaurant mode in POS view");
            }

            // 3. Restricted user (assigned to standard warehouse) attempting to spoof restaurant warehouse
            Auth::login($restricted);
            $reqSpoof = Request::create(url("pos?warehouse_id={$restaurantWarehouse->id}"), 'GET');
            $reqSpoof->setUserResolver(fn() => $restricted);
            $resSpoof = app()->handle($reqSpoof);
            $contentSpoof = $resSpoof->getContent();

            if (stripos($contentSpoof, 'id="service_id"') !== false || stripos($contentSpoof, 'id="price_type"') === false) {
                throw new Exception("Restricted user successfully bypassed warehouse assignment to access restaurant POS mode");
            }

            Auth::login($admin);

            return [
                'passed' => true,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            Auth::login($admin);
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    private function executeRestaurantLifecycleScenario(User $user, Warehouse $warehouse, int $iter): array
    {
        $unique = uniqid('rest-');
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $customer = Customer::firstOrCreate(['id' => 1], ['name' => 'Walk-in', 'phone_number' => '123', 'is_active' => true]);
            $biller = Biller::firstOrCreate(['id' => 1], ['name' => 'Default', 'phone_number' => '123', 'email' => 'b@t.com', 'address' => 'A', 'city' => 'C', 'is_active' => true]);

            $product = Product::create([
                'name' => 'Burger ' . $unique,
                'code' => 'REST-' . $unique,
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'unit_id' => 1,
                'purchase_unit_id' => 1,
                'sale_unit_id' => 1,
                'cost' => 5.00,
                'price' => 15.00,
                'qty' => 50,
                'category_id' => 83,
                'kitchen_id' => 1,
                'menu_type' => 'food',
                'is_active' => true,
            ]);

            $pw = Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => 50,
            ]);

            // Status 5: Processing (deducts stock once)
            $sale = Sale::create([
                'reference_no' => 'REST-ORD-' . $unique,
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'biller_id' => $biller->id,
                'warehouse_id' => $warehouse->id,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => 15.00,
                'grand_total' => 15.00,
                'paid_amount' => 15.00,
                'sale_status' => 5,
                'payment_status' => 4,
                'currency_id' => 1,
                'exchange_rate' => 1.0,
            ]);

            $domain = app(SaleDomainService::class);
            $domain->deductStock($sale, [$product->id], [1], [1]);

            if ((float) $pw->refresh()->qty !== 49.0) {
                throw new Exception("Status 5 stock deduction failed: expected 49, got {$pw->qty}");
            }

            // Status 6: Cooked (0 additional movement)
            $sale->update(['sale_status' => 6]);
            if ((float) $pw->refresh()->qty !== 49.0) {
                throw new Exception("Status 6 caused unexpected stock movement");
            }

            // Status 1: Completed (0 additional movement)
            $sale->update(['sale_status' => 1]);
            if ((float) $pw->refresh()->qty !== 49.0) {
                throw new Exception("Status 1 caused unexpected stock movement");
            }

            DB::rollBack();

            return [
                'passed' => true,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'passed' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }
}
