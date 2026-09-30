<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Purchase;
use App\Models\ProductPurchase;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\ReturnPurchase;
use App\Models\PurchaseProductReturn;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use App\Models\Transfer;
use App\Models\ProductTransfer;
use App\Models\Warehouse;

class RunScenario15 extends Command
{
    protected $signature = 'test:scenario-15';
    protected $description = 'Scenario 15: Advanced Inventory Integrity Regression Suite';

    protected $preOrphans = [];

    // Test Entities
    private $testWarehouseA;
    private $testWarehouseB;
    private $testCustomer;
    private $testSupplier;
    private $testBiller;
    private $testAccount;
    private $testUnit;
    
    // Scenario 15 Specific Products
    private $variantProduct;
    private $variantA;
    private $variantB;
    
    private $comboProduct;
    private $baseBurger;
    private $baseFries;
    private $baseDrink;
    
    private $simpleProduct;

    public function handle()
    {
        $this->info("Starting Scenario 15: Advanced Inventory Integrity Regression Suite");

        $admin = \App\Models\User::where('role_id', 1)->where('is_active', true)->first();
        if ($admin) {
            \Illuminate\Support\Facades\Auth::login($admin);
        }

        $this->setupTestData();

        $this->captureOrphans('pre');

        try {
            $this->phaseA();
            $this->phaseB();
            $this->phaseC();
            $this->phaseD();
            $this->phaseE();

            $this->captureOrphans('post');
            $this->verifyNoNewOrphans();

            $this->info("✅ SCENARIO 15 PASSED");
        } catch (\Exception $e) {
            $this->error("❌ SCENARIO 15 FAILED");
            $this->error($e->getMessage());
            $this->error($e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }

    private function setupTestData()
    {
        $this->testCustomer = \App\Models\Customer::first();
        $this->testSupplier = \App\Models\Supplier::first();
        $this->testWarehouseA = \App\Models\Warehouse::first();
        if (\App\Models\Warehouse::count() > 1) {
            $this->testWarehouseB = \App\Models\Warehouse::where('id', '!=', $this->testWarehouseA->id)->first();
        } else {
            $this->testWarehouseB = \App\Models\Warehouse::create([
                'name' => 'S15_WarehouseB',
                'phone' => '123456789',
                'email' => 'whb@example.com',
                'address' => 'Test',
                'is_active' => 1
            ]);
        }
        $this->testBiller = \App\Models\Biller::first();
        $this->testAccount = \App\Models\Account::first();
        $this->testUnit = \App\Models\Unit::first();
        $testCategory = \App\Models\Category::first();
        $catId = $testCategory ? $testCategory->id : 1;

        // 1. Variant Product
        $this->variantProduct = Product::create([
            'name' => 'S15_Variant_Shirt',
            'code' => 'S15-VAR-' . time(),
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $catId,
            'unit_id' => $this->testUnit->id,
            'purchase_unit_id' => $this->testUnit->id,
            'sale_unit_id' => $this->testUnit->id,
            'cost' => 10,
            'price' => 20,
            'qty' => 0,
            'is_variant' => 1,
            'is_active' => 1
        ]);

        $this->variantA = Variant::create(['name' => 'S15_Red']);
        $this->variantB = Variant::create(['name' => 'S15_Blue']);

        ProductVariant::create([
            'product_id' => $this->variantProduct->id,
            'variant_id' => $this->variantA->id,
            'position' => 1,
            'item_code' => $this->variantProduct->code . '-A',
            'additional_cost' => 0,
            'additional_price' => 0,
            'qty' => 0
        ]);

        ProductVariant::create([
            'product_id' => $this->variantProduct->id,
            'variant_id' => $this->variantB->id,
            'position' => 2,
            'item_code' => $this->variantProduct->code . '-B',
            'additional_cost' => 0,
            'additional_price' => 0,
            'qty' => 0
        ]);

        // 2. Combo Base Items
        $this->baseBurger = Product::create(['name'=>'S15_Burger','code'=>'S15-B-'.time(),'type'=>'standard','barcode_symbology'=>'C128','category_id'=>$catId,'unit_id'=>$this->testUnit->id,'purchase_unit_id'=>$this->testUnit->id,'sale_unit_id'=>$this->testUnit->id,'cost'=>5,'price'=>10,'qty'=>0,'is_active'=>1]);
        $this->baseFries = Product::create(['name'=>'S15_Fries','code'=>'S15-F-'.time(),'type'=>'standard','barcode_symbology'=>'C128','category_id'=>$catId,'unit_id'=>$this->testUnit->id,'purchase_unit_id'=>$this->testUnit->id,'sale_unit_id'=>$this->testUnit->id,'cost'=>2,'price'=>4,'qty'=>0,'is_active'=>1]);
        $this->baseDrink = Product::create(['name'=>'S15_Drink','code'=>'S15-D-'.time(),'type'=>'standard','barcode_symbology'=>'C128','category_id'=>$catId,'unit_id'=>$this->testUnit->id,'purchase_unit_id'=>$this->testUnit->id,'sale_unit_id'=>$this->testUnit->id,'cost'=>1,'price'=>2,'qty'=>0,'is_active'=>1]);

        // 3. Combo Product
        $this->comboProduct = Product::create([
            'name' => 'S15_Combo_Meal',
            'code' => 'S15-COMBO-' . time(),
            'type' => 'combo',
            'barcode_symbology' => 'C128',
            'category_id' => $catId,
            'unit_id' => $this->testUnit->id,
            'purchase_unit_id' => $this->testUnit->id,
            'sale_unit_id' => $this->testUnit->id,
            'cost' => 8,
            'price' => 15,
            'qty' => 0,
            'is_active' => 1,
            'product_list' => $this->baseBurger->id . ',' . $this->baseFries->id . ',' . $this->baseDrink->id,
            'variant_list' => ',,',
            'qty_list' => '1,1,1',
            'price_list' => '10,4,2'
        ]);

        // 4. Simple Product for generic adjustments / transfers
        $this->simpleProduct = Product::create(['name'=>'S15_Simple','code'=>'S15-SIMP-'.time(),'type'=>'standard','barcode_symbology'=>'C128','category_id'=>$catId,'unit_id'=>$this->testUnit->id,'purchase_unit_id'=>$this->testUnit->id,'sale_unit_id'=>$this->testUnit->id,'cost'=>5,'price'=>10,'qty'=>0,'is_active'=>1]);
    }

    private function captureOrphans($stage)
    {
        $counts = [
            'product_sales' => DB::table('product_sales')->whereNotIn('sale_id', function($q) { $q->select('id')->from('sales'); })->count(),
            'product_purchases' => DB::table('product_purchases')->whereNotIn('purchase_id', function($q) { $q->select('id')->from('purchases'); })->count(),
            'product_returns' => DB::table('product_returns')->whereNotIn('return_id', function($q) { $q->select('id')->from('returns'); })->count(),
            'purchase_product_return' => DB::table('purchase_product_return')->whereNotIn('return_id', function($q) { $q->select('id')->from('return_purchases'); })->count(),
            'product_transfer' => DB::table('product_transfer')->whereNotIn('transfer_id', function($q) { $q->select('id')->from('transfers'); })->count(),
            'product_adjustments' => DB::table('product_adjustments')->whereNotIn('adjustment_id', function($q) { $q->select('id')->from('adjustments'); })->count(),
            'payments' => DB::table('payments')->whereNotNull('sale_id')->whereNotIn('sale_id', function($q) { $q->select('id')->from('sales'); })->count()
        ];

        if ($stage === 'pre') {
            $this->preOrphans = $counts;
        } else {
            foreach ($counts as $table => $count) {
                $pre = $this->preOrphans[$table];
                if ($count > $pre) {
                    throw new \Exception("Orphan Leak Detected in {$table}: Pre={$pre}, Post={$count}");
                }
            }
        }
    }

    private function phaseA()
    {
        $this->info("--- Phase A: Variant Product Integrity ---");
        DB::beginTransaction();
        try {
            // A1. Variant Purchase
            $this->info("  A1. Variant Purchase");
            $request = new \Illuminate\Http\Request();
            $request->replace([
                'warehouse_id' => $this->testWarehouseA->id,
                'supplier_id' => $this->testSupplier->id,
                'status' => 1, // Received
                'payment_status' => 1, // Pending
                'item' => 1,
                'total_qty' => 10,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_cost' => 100,
                'grand_total' => 100,
                'paid_amount' => 0,
                'product_id' => [$this->variantProduct->id],
                'product_code' => [$this->variantProduct->code . '-A'],
                'qty' => [10],
                'recieved' => [10],
                'purchase_unit' => [$this->testUnit->unit_name],
                'net_unit_cost' => [10],
                'unit_cost' => [10],
                'net_unit_margin' => [10],
                'net_unit_margin_type' => [1],
                'net_unit_price' => [20],
                'discount' => [0],
                'tax_rate' => [0],
                'tax' => [0],
                'imei_number' => [''],
                'subtotal' => [100],
            ]);
            
            $purchaseController = app(\App\Http\Controllers\PurchaseController::class);
            $response = $purchaseController->store($request);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('message') && !str_contains(strtolower(session('message')), 'successfully')) {
                throw new \Exception("Controller redirect message: " . session('message'));
            }
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("Controller redirect not_permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("Controller validation errors: " . json_encode(session('errors')->messages()));
            }
            
            $variantAStock = ProductVariant::where('product_id', $this->variantProduct->id)->where('variant_id', $this->variantA->id)->first()->qty;
            if ($variantAStock != 10) throw new \Exception("Variant A Stock expected 10, got $variantAStock");
            
            $globalStock = Product::find($this->variantProduct->id)->qty;
            if ($globalStock != 10) throw new \Exception("Global Variant Stock expected 10, got $globalStock");

            $warehouseStock = Product_Warehouse::where('product_id', $this->variantProduct->id)->where('variant_id', $this->variantA->id)->first()->qty;
            if ($warehouseStock != 10) throw new \Exception("Warehouse Variant Stock expected 10, got $warehouseStock");

            // A3. Variant Sale
            $this->info("  A3. Variant Sale");
            $saleRequest = new \App\Http\Requests\Sale\StoreSaleRequest();
            $saleRequest->replace([
                'warehouse_id' => $this->testWarehouseA->id,
                'customer_id' => $this->testCustomer->id,
                'biller_id' => $this->testBiller->id,
                'item' => 1,
                'total_qty' => 2,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => 40,
                'grand_total' => 40,
                'sale_status' => 1, // Completed
                'payment_status' => 4, // Paid
                'paid_amount' => [40],
                'paying_amount' => [40],
                'paid_by_id' => [1], // Cash
                'payment_note' => '',
                'product_id' => [$this->variantProduct->id],
                'product_code' => [$this->variantProduct->code . '-A'],
                'qty' => [2],
                'sale_unit' => [$this->testUnit->unit_name],
                'net_unit_price' => [20],
                'unit_price' => [20],
                'discount' => [0],
                'tax_rate' => [0],
                'tax' => [0],
                'imei_number' => [''],
                'subtotal' => [40],
                'coupon_active' => 0,
                'draft' => 0,
                'pos' => 0,
                'product_batch_id' => [null],
            ]);
            
            $saleController = app(\App\Http\Controllers\SaleController::class);
            $response = $saleController->store($saleRequest);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("SaleController redirect not_permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("SaleController validation errors: " . json_encode(session('errors')->messages()));
            }
            
            $variantAStock = ProductVariant::where('product_id', $this->variantProduct->id)->where('variant_id', $this->variantA->id)->first()->qty;
            if ($variantAStock != 8) throw new \Exception("Variant A Stock expected 8, got $variantAStock");

            DB::rollBack();
            $this->info("Phase A complete and rolled back.");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function phaseB()
    {
        $this->info("--- Phase B: Combo Product Integrity ---");
        DB::beginTransaction();
        try {
            // First inject some base stock to avoid negative stock errors
            Product::whereIn('id', [$this->baseBurger->id, $this->baseFries->id, $this->baseDrink->id])->update(['qty' => 50]);
            foreach ([$this->baseBurger->id, $this->baseFries->id, $this->baseDrink->id] as $pid) {
                Product_Warehouse::create(['product_id' => $pid, 'warehouse_id' => $this->testWarehouseA->id, 'qty' => 50]);
            }

            // B2. Combo Sale
            $this->info("  B2. Combo Sale");
            $saleRequest = new \App\Http\Requests\Sale\StoreSaleRequest();
            $saleRequest->replace([
                'warehouse_id' => $this->testWarehouseA->id,
                'customer_id' => $this->testCustomer->id,
                'biller_id' => $this->testBiller->id,
                'item' => 1,
                'total_qty' => 2,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => 30,
                'grand_total' => 30,
                'sale_status' => 1,
                'payment_status' => 4,
                'paid_amount' => [30],
                'paying_amount' => [30],
                'paid_by_id' => [1], // Cash
                'payment_note' => '',
                'product_id' => [$this->comboProduct->id],
                'product_code' => [$this->comboProduct->code],
                'qty' => [2],
                'sale_unit' => [$this->testUnit->unit_name],
                'net_unit_price' => [15],
                'unit_price' => [15],
                'discount' => [0],
                'tax_rate' => [0],
                'tax' => [0],
                'imei_number' => [''],
                'subtotal' => [30],
                'coupon_active' => 0,
                'draft' => 0,
                'pos' => 0,
                'product_batch_id' => [null],
            ]);
            
            $saleController = app(\App\Http\Controllers\SaleController::class);
            $response = $saleController->store($saleRequest);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("SaleController redirect not_permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("SaleController validation errors: " . json_encode(session('errors')->messages()));
            }

            // Verify
            $burgerStock = Product::find($this->baseBurger->id)->qty;
            if ($burgerStock != 48) throw new \Exception("Combo Sale failed to deduct Burger. Expected 48, got $burgerStock");
            $friesStock = Product_Warehouse::where('product_id', $this->baseFries->id)->where('warehouse_id', $this->testWarehouseA->id)->first()->qty;
            if ($friesStock != 48) throw new \Exception("Combo Sale failed to deduct Fries in warehouse. Expected 48, got $friesStock");

            // B3. Combo Sale Return
            $this->info("  B3. Combo Sale Return");
            $sale = Sale::orderBy('id', 'desc')->first();
            $productSale = DB::table('product_sales')->where('sale_id', $sale->id)->first();
            
            $returnRequest = new \Illuminate\Http\Request();
            $returnRequest->replace([
                'sale_id' => $sale->id,
                'customer_id' => $this->testCustomer->id,
                'warehouse_id' => $this->testWarehouseA->id,
                'biller_id' => $this->testBiller->id,
                'account_id' => $this->testAccount->id,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_sale_discount' => 0,
                'order_discount' => 0,
                'order_tax_rate' => 0,
                'total_tax' => 0,
                'total_price' => 15,
                'grand_total' => 15,
                'product_id' => [$this->comboProduct->id],
                'product_code' => [$this->comboProduct->code],
                'qty' => [1],
                'sale_unit' => ['n/a'],
                'net_unit_price' => [15],
                'discount' => [0],
                'tax_rate' => [0],
                'tax' => [0],
                'subtotal' => [15],
                'product_sale_id' => [$productSale->id],
                'imei_number' => [''],
                'product_batch_id' => [null],
                'change_sale_status' => 0
            ]);
            $returnController = app(\App\Http\Controllers\ReturnController::class);
            $response = $returnController->store($returnRequest);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("ReturnController redirect not_permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("ReturnController validation errors: " . json_encode(session('errors')->messages()));
            }

            $burgerStock = Product::find($this->baseBurger->id)->qty;
            if ($burgerStock != 49) throw new \Exception("Combo Return failed to restore Burger. Expected 49, got $burgerStock");

            // B4. Combo Edit
            // We'll skip edit if the controller's update is complex. Actually, the user asked for Edit.
            $this->info("  B4. Combo Edit");
            $editRequest = $saleRequest->all();
            $editRequest['total_qty'] = 3;
            $editRequest['qty'] = [3];
            $editRequest['paid_amount'] = 30; // Float, not array
            $saleRequest->replace($editRequest);
            
            $response = $saleController->update($saleRequest, $sale->id);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("SaleController update redirect not_permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("SaleController update validation errors: " . json_encode(session('errors')->messages()));
            }

            // Stock was 48, return +1 = 49. Edit sale from 2 to 3 means we deduct 1 more. Expected: 48.
            $burgerStock = Product::find($this->baseBurger->id)->qty;
            if ($burgerStock != 48) throw new \Exception("Combo Edit failed. Expected 48, got $burgerStock");

            // B5. Combo Delete
            $this->info("  B5. Combo Delete");
            $saleController->destroy($sale->id);
            $burgerStock = Product::find($this->baseBurger->id)->qty;
            // Delete sale removes the -3 deduction, but the return (+1) is still there? 
            // The return is linked to the sale. If sale is deleted, does it delete returns?
            // Actually, we'll just check if stock changes.
            if ($burgerStock != 51) throw new \Exception("Combo Delete failed. Expected 51, got $burgerStock");

            DB::rollBack();
            $this->info("Phase B complete and rolled back.");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function phaseC()
    {
        $this->info("--- Phase C: Stock Adjustment Integrity ---");
        DB::beginTransaction();
        try {
            // C1. Create Adjustment
            $this->info("  C1. Create Adjustment");
            // Setup simple product stock
            Product::find($this->simpleProduct->id)->update(['qty' => 50]);
            Product_Warehouse::create(['product_id' => $this->simpleProduct->id, 'warehouse_id' => $this->testWarehouseA->id, 'qty' => 50]);

            $adjRequest = new \Illuminate\Http\Request();
            $adjRequest->replace([
                'warehouse_id' => $this->testWarehouseA->id,
                'item' => 1,
                'total_qty' => 10,
                'product_id' => [$this->simpleProduct->id],
                'product_variant_id' => [null],
                'product_code' => [$this->simpleProduct->code],
                'qty' => [10],
                'action' => ['-'], // Subtract 10
                'unit_cost' => [5],
            ]);
            $adjController = app(\App\Http\Controllers\AdjustmentController::class);
            $response = $adjController->store($adjRequest);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("Adj create not permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("Adj create validation errors: " . json_encode(session('errors')->messages()));
            }

            $stock = Product::find($this->simpleProduct->id)->qty;
            if ($stock != 40) throw new \Exception("Adj create failed. Expected 40, got $stock");

            // C2. Edit Adjustment
            $this->info("  C2. Edit Adjustment");
            $adj = Adjustment::orderBy('id', 'desc')->first();
            $adjRequest->replace(array_merge($adjRequest->all(), ['qty' => [5], 'total_qty' => 5]));
            $response = $adjController->update($adjRequest, $adj->id);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("Adj update not permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("Adj update validation errors: " . json_encode(session('errors')->messages()));
            }

            $stock = Product::find($this->simpleProduct->id)->qty;
            if ($stock != 45) throw new \Exception("Adj edit failed. Expected 45, got $stock");

            // C3. Delete Adjustment
            $this->info("  C3. Delete Adjustment");
            $adjController->destroy($adj->id);

            $stock = Product::find($this->simpleProduct->id)->qty;
            if ($stock != 50) throw new \Exception("Adj delete failed. Expected 50, got $stock");

            DB::rollBack();
            $this->info("Phase C complete and rolled back.");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function phaseD()
    {
        $this->info("--- Phase D: Stock Transfer Integrity ---");
        DB::beginTransaction();
        try {
            $this->info("  D1. Create Transfer");
            // Set initial stock in Warehouse A to 100, Warehouse B to 0
            Product::find($this->simpleProduct->id)->update(['qty' => 100]);
            Product_Warehouse::where('product_id', $this->simpleProduct->id)->delete();
            Product_Warehouse::create(['product_id' => $this->simpleProduct->id, 'warehouse_id' => $this->testWarehouseA->id, 'qty' => 100]);
            Product_Warehouse::create(['product_id' => $this->simpleProduct->id, 'warehouse_id' => $this->testWarehouseB->id, 'qty' => 0]);

            $transferRequest = new \Illuminate\Http\Request();
            $transferRequest->replace([
                'from_warehouse_id' => $this->testWarehouseA->id,
                'to_warehouse_id' => $this->testWarehouseB->id,
                'status' => 1, // Completed
                'item' => 1,
                'total_qty' => 20,
                'product_id' => [$this->simpleProduct->id],
                'product_code' => [$this->simpleProduct->code],
                'qty' => [20],
                'purchase_unit' => ['Piece'],
                'net_unit_cost' => [10],
                'tax_rate' => [0],
                'tax' => [0],
                'subtotal' => [200],
                'total' => [200],
                'total_tax' => 0,
                'total_cost' => 200,
                'shipping_cost' => 0,
                'grand_total' => 200,
                'imei_number' => [null],
                'product_batch_id' => [null],
                'product_variant_id' => [null]
            ]);

            $transferController = app(\App\Http\Controllers\TransferController::class);
            $response = $transferController->store($transferRequest);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("Transfer create not permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("Transfer create validation errors: " . json_encode(session('errors')->messages()));
            }

            $stockA = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseA->id)->first()->qty;
            $stockB = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseB->id)->first()->qty;
            if ($stockA != 80) {
                $msg = session('message') ?? 'no message';
                $pt = \App\Models\ProductTransfer::where('product_id', $this->simpleProduct->id)->first();
                $ptId = $pt ? $pt->id : 'NOT FOUND';
                $allA = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseA->id)->get()->pluck('qty')->toArray();
                throw new \Exception("Transfer create failed. Expected 80 in A, got $stockA. Msg: $msg. All A qtys: " . json_encode($allA));
            }
            if ($stockB != 20) throw new \Exception("Transfer create failed. Expected 20 in B, got $stockB");

            $this->info("  D2. Edit Transfer");
            $transfer = Transfer::orderBy('id', 'desc')->first();
            $transferRequest->replace(array_merge($transferRequest->all(), ['qty' => [30], 'total_qty' => 30]));
            $response = $transferController->update($transferRequest, $transfer->id);

            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) {
                throw new \Exception("Transfer edit not permitted: " . session('not_permitted'));
            }
            if (session()->has('errors')) {
                throw new \Exception("Transfer edit validation errors: " . json_encode(session('errors')->messages()));
            }

            $stockA = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseA->id)->first()->qty;
            $stockB = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseB->id)->first()->qty;
            
            // Expected: 100 - 30 = 70 in A, 30 in B
            if ($stockA != 70) {
                $msg = session('message') ?? 'no message';
                throw new \Exception("Transfer edit failed. Expected 70 in A, got $stockA. Msg: $msg");
            }
            if ($stockB != 30) throw new \Exception("Transfer edit failed. Expected 30 in B, got $stockB");

            $this->info("  D3. Delete Transfer");
            $transferController->destroy($transfer->id);
            $stockA = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseA->id)->first()->qty;
            $stockB = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseB->id)->first()->qty;
            
            if ($stockA != 100) throw new \Exception("Transfer delete failed. Expected 100 in A, got $stockA");
            if ($stockB != 0) throw new \Exception("Transfer delete failed. Expected 0 in B, got $stockB");

            DB::rollBack();
            $this->info("Phase D complete and rolled back.");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function phaseE()
    {
        $this->info("--- Phase E: Mixed Inventory Stress Test ---");
        DB::beginTransaction();
        try {
            // Clear existing Product_Warehouse for simpleProduct to avoid pollution
            Product_Warehouse::where('product_id', $this->simpleProduct->id)->delete();
            Product::find($this->simpleProduct->id)->update(['qty' => 0]);
            Product_Warehouse::create(['product_id' => $this->simpleProduct->id, 'warehouse_id' => $this->testWarehouseA->id, 'qty' => 0]);
            Product_Warehouse::create(['product_id' => $this->simpleProduct->id, 'warehouse_id' => $this->testWarehouseB->id, 'qty' => 0]);

            $this->info("  E1. Mixed Purchase -> Transfer -> Adjust -> Sale");
            
            // Purchase 100 in A
            $purchaseReq = new \Illuminate\Http\Request();
            $purchaseReq->replace([
                'warehouse_id' => $this->testWarehouseA->id,
                'supplier_id' => $this->testSupplier->id,
                'status' => 1, // Received
                'item' => 1,
                'total_qty' => 100,
                'product_id' => [$this->simpleProduct->id],
                'product_code' => [$this->simpleProduct->code],
                'qty' => [100],
                'recieved' => [100],
                'purchase_unit' => ['Piece'],
                'net_unit_cost' => [10],
                'unit_cost' => [10],
                'net_unit_price' => [15],
                'net_unit_margin' => [10],
                'net_unit_margin_type' => [1],
                'tax_rate' => [0],
                'tax' => [0],
                'discount' => [0],
                'subtotal' => [1000],
                'total' => [1000],
                'total_tax' => 0,
                'total_discount' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'total_cost' => 1000,
                'grand_total' => 1000,
                'paid_amount' => 1000,
                'payment_status' => 2, // Paid
                'imei_number' => [null],
            ]);
            $response = app(\App\Http\Controllers\PurchaseController::class)->store($purchaseReq);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) throw new \Exception("Purchase E1 failed: " . session('not_permitted'));

            // Transfer 40 from A to B
            $transferReq = new \Illuminate\Http\Request();
            $transferReq->replace([
                'from_warehouse_id' => $this->testWarehouseA->id,
                'to_warehouse_id' => $this->testWarehouseB->id,
                'status' => 1, // Completed
                'item' => 1,
                'total_qty' => 40,
                'product_id' => [$this->simpleProduct->id],
                'product_code' => [$this->simpleProduct->code],
                'qty' => [40],
                'purchase_unit' => ['Piece'],
                'net_unit_cost' => [10],
                'tax_rate' => [0],
                'tax' => [0],
                'subtotal' => [400],
                'total' => [400],
                'total_tax' => 0,
                'total_cost' => 400,
                'shipping_cost' => 0,
                'grand_total' => 400,
                'imei_number' => [null],
                'product_batch_id' => [null],
                'product_variant_id' => [null]
            ]);
            $response = app(\App\Http\Controllers\TransferController::class)->store($transferReq);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) throw new \Exception("Transfer E1 failed: " . session('not_permitted'));

            // Adjust: Add 10 to B
            $adjustReq = new \Illuminate\Http\Request();
            $adjustReq->replace([
                'warehouse_id' => $this->testWarehouseB->id,
                'item' => 1,
                'total_qty' => 10,
                'product_id' => [$this->simpleProduct->id],
                'qty' => [10],
                'action' => ['+'],
                'product_code' => [$this->simpleProduct->code],
            ]);
            $response = app(\App\Http\Controllers\AdjustmentController::class)->store($adjustReq);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) throw new \Exception("Adjust E1 failed: " . session('not_permitted'));

            // Sale: Sell 20 from A
            $saleReq = new \App\Http\Requests\Sale\StoreSaleRequest();
            $saleReq->replace([
                'customer_id' => $this->testCustomer->id,
                'warehouse_id' => $this->testWarehouseA->id,
                'biller_id' => $this->testBiller->id,
                'item' => 1,
                'total_qty' => 20,
                'product_id' => [$this->simpleProduct->id],
                'product_code' => [$this->simpleProduct->code],
                'qty' => [20],
                'sale_unit' => ['Piece'],
                'net_unit_price' => [15],
                'discount' => [0],
                'tax_rate' => [0],
                'tax' => [0],
                'subtotal' => [300],
                'total' => [300],
                'total_tax' => 0,
                'total_discount' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'shipping_cost' => 0,
                'total_price' => 300,
                'grand_total' => 300,
                'sale_status' => 1, // Completed
                'payment_status' => 4, // Paid
                'paid_amount' => [300.00],
                'paying_amount' => [300.00],
                'paid_by_id' => [1],
                'payment_note' => '',
                'coupon_active' => 0,
                'imei_number' => [null],
                'product_batch_id' => [null],
            ]);
            $response = app(\App\Http\Controllers\SaleController::class)->store($saleReq);
            if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('not_permitted') && !str_contains(strtolower(session('not_permitted')), 'successfully')) throw new \Exception("Sale E1 failed: " . session('not_permitted'));

            $stockA = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseA->id)->first()->qty;
            $stockB = Product_Warehouse::where('product_id', $this->simpleProduct->id)->where('warehouse_id', $this->testWarehouseB->id)->first()->qty;

            // Math: A = 100 - 40 - 20 = 40. B = 0 + 40 + 10 = 50.
            if ($stockA != 40) throw new \Exception("Phase E failed. Expected 40 in A, got $stockA");
            if ($stockB != 50) throw new \Exception("Phase E failed. Expected 50 in B, got $stockB");

            $this->info("  Phase E Mixed Math Validated.");
            DB::rollBack();
            $this->info("Phase E complete and rolled back.");
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function verifyNoNewOrphans()
    {
        $this->info("Orphan scan successful. No new orphans created.");
    }
}
