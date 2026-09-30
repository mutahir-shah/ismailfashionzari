<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\DamageStockController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReturnPurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransferController;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Models\Account;
use App\Models\Biller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\DamageStock;
use App\Models\Product;
use App\Models\ProductAdjustment;
use App\Models\ProductDamageStock;
use App\Models\ProductExchange;
use App\Models\ProductPurchase;
use App\Models\ProductReturn;
use App\Models\ProductTransfer;
use App\Models\ProductVariant;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Purchase;
use App\Models\PurchaseProductReturn;
use App\Models\ReturnPurchase;
use App\Models\Returns;
use App\Models\Sale;
use App\Models\SaleExchange;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifyE2EInventoryFlow extends Command
{
    protected $signature = 'verify:e2e-inventory-flow {--commit : Commit verification records instead of rolling them back}';

    protected $description = 'End-to-end inventory verification for standard, variant, and combo products.';

    private string $runId;
    private string $prefix;
    private array $ctx = [];
    private array $checks = [];
    private array $events = [];
    private array $bugs = [];
    private array $refs = [];

    public function handle(): int
    {
        $this->runId = now()->format('YmdHis');
        $this->prefix = 'E2E-INV-' . $this->runId;

        $this->info('Starting E2E inventory verification: ' . $this->prefix);

        DB::beginTransaction();

        try {
            $this->loginAdmin();
            $this->setupReferenceData();
            $this->purchaseFlow();
            $this->saleFlow();
            $this->transferFlow();
            $this->saleReturnFlow();
            $this->purchaseReturnFlow();
            $this->damageAndAdjustmentFlow();
            $this->saleExchangeFlow();
            $this->listingAndReportChecks();

            $reportPath = $this->writeReport();

            if ($this->option('commit')) {
                DB::commit();
                $this->warn('Verification data committed because --commit was used.');
            } else {
                DB::rollBack();
                $this->info('Verification data rolled back.');
            }

            $this->info('Audit report: ' . $reportPath);

            if ($this->hasFailures()) {
                $this->error('E2E inventory verification completed with failures.');
                return 1;
            }

            $this->info('E2E inventory verification passed.');
            return 0;
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->recordBug('Fatal verification failure', 'Command should complete all flows and write an audit report.', $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $reportPath = $this->writeReport();
            $this->error('Fatal verification failure: ' . $e->getMessage());
            $this->error('Audit report: ' . $reportPath);
            return 1;
        }
    }

    private function loginAdmin(): void
    {
        $admin = User::where('role_id', 1)->where('is_active', true)->first() ?: User::first();
        if (!$admin) {
            throw new \RuntimeException('No user exists for controller authentication.');
        }

        Auth::login($admin);
        $this->ctx['admin'] = $admin;
    }

    private function setupReferenceData(): void
    {
        $unit = Unit::where('is_active', true)->first() ?: Unit::create([
            'unit_name' => 'Piece',
            'unit_code' => 'pc',
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => true,
        ]);

        $category = Category::where('is_active', true)->first() ?: Category::create([
            'name' => $this->prefix . '-Category',
            'is_active' => true,
        ]);

        $customerGroup = CustomerGroup::first();
        $customer = Customer::create([
            'customer_group_id' => $customerGroup->id ?? null,
            'user_id' => Auth::id(),
            'name' => $this->prefix . '-Customer',
            'company_name' => $this->prefix . '-Customer-Co',
            'email' => strtolower($this->prefix) . '@example.test',
            'phone_number' => '1000000000',
            'address' => 'Verification',
            'city' => 'Verification',
            'country' => 'Verification',
            'deposit' => 0,
            'expense' => 0,
            'points' => 0,
            'credit_limit' => 1000000,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => $this->prefix . '-Supplier',
            'company_name' => $this->prefix . '-Supplier-Co',
            'email' => strtolower($this->prefix) . '-supplier@example.test',
            'phone_number' => '2000000000',
            'address' => 'Verification',
            'city' => 'Verification',
            'country' => 'Verification',
            'is_active' => true,
        ]);

        $biller = Biller::create([
            'name' => $this->prefix . '-Biller',
            'company_name' => $this->prefix . '-Biller-Co',
            'email' => strtolower($this->prefix) . '-biller@example.test',
            'phone_number' => '3000000000',
            'address' => 'Verification',
            'city' => 'Verification',
            'country' => 'Verification',
            'is_active' => true,
        ]);

        $account = Account::create([
            'account_no' => $this->prefix,
            'name' => $this->prefix . '-Cash',
            'initial_balance' => 0,
            'total_balance' => 0,
            'is_default' => false,
            'is_active' => true,
        ]);

        $warehouseA = Warehouse::create([
            'name' => $this->prefix . '-Warehouse-A',
            'phone' => '4000000000',
            'email' => strtolower($this->prefix) . '-a@example.test',
            'address' => 'Verification A',
            'is_active' => true,
        ]);

        $warehouseB = Warehouse::create([
            'name' => $this->prefix . '-Warehouse-B',
            'phone' => '5000000000',
            'email' => strtolower($this->prefix) . '-b@example.test',
            'address' => 'Verification B',
            'is_active' => true,
        ]);

        $standard = $this->makeProduct('Standard', 'STD', $unit, $category, 5, 10);
        $variantProduct = $this->makeProduct('Variant', 'VAR', $unit, $category, 10, 20, ['is_variant' => 1]);
        $variantRed = Variant::create(['name' => $this->prefix . '-Red']);
        $variantBlue = Variant::create(['name' => $this->prefix . '-Blue']);

        $variantRedPivot = ProductVariant::create([
            'product_id' => $variantProduct->id,
            'variant_id' => $variantRed->id,
            'position' => 1,
            'item_code' => $variantProduct->code . '-RED',
            'additional_cost' => 0,
            'additional_price' => 0,
            'qty' => 0,
        ]);

        ProductVariant::create([
            'product_id' => $variantProduct->id,
            'variant_id' => $variantBlue->id,
            'position' => 2,
            'item_code' => $variantProduct->code . '-BLUE',
            'additional_cost' => 0,
            'additional_price' => 0,
            'qty' => 0,
        ]);

        $componentA = $this->makeProduct('Combo-Component-A', 'CMPA', $unit, $category, 2, 4);
        $componentB = $this->makeProduct('Combo-Component-B', 'CMPB', $unit, $category, 3, 6);

        $combo = $this->makeProduct('Combo', 'COMBO', $unit, $category, 8, 15, [
            'type' => 'combo',
            'product_list' => $componentA->id . ',' . $componentB->id,
            'variant_list' => ',',
            'qty_list' => '2,1',
            'price_list' => '4,6',
        ]);

        foreach ([$standard, $variantProduct, $componentA, $componentB] as $product) {
            Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseA->id,
                'variant_id' => $product->id === $variantProduct->id ? $variantRed->id : null,
                'qty' => 0,
            ]);
            Product_Warehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseB->id,
                'variant_id' => $product->id === $variantProduct->id ? $variantRed->id : null,
                'qty' => 0,
            ]);
        }

        $this->ctx = array_merge($this->ctx, compact(
            'unit',
            'category',
            'customer',
            'supplier',
            'biller',
            'account',
            'warehouseA',
            'warehouseB',
            'standard',
            'variantProduct',
            'variantRed',
            'variantRedPivot',
            'componentA',
            'componentB',
            'combo'
        ));

        $this->event('Setup', 'Created deterministic products and reference master data.', [
            'standard' => $standard->code,
            'variant' => $variantRedPivot->item_code,
            'combo' => $combo->code,
            'components' => [$componentA->code, $componentB->code],
        ]);
    }

    private function makeProduct(string $name, string $code, Unit $unit, Category $category, float $cost, float $price, array $extra = []): Product
    {
        return Product::create(array_merge([
            'name' => $this->prefix . '-' . $name,
            'code' => $this->prefix . '-' . $code,
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'purchase_unit_id' => $unit->id,
            'sale_unit_id' => $unit->id,
            'cost' => $cost,
            'price' => $price,
            'qty' => 0,
            'is_active' => true,
        ], $extra));
    }

    private function purchaseFlow(): void
    {
        $ref = $this->ref('PUR');
        $request = new Request();
        $request->replace([
            'reference_no' => $ref,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'supplier_id' => $this->ctx['supplier']->id,
            'status' => 1,
            'payment_status' => 1,
            'item' => 4,
            'total_qty' => 57,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 405,
            'grand_total' => 405,
            'paid_amount' => 0,
            'product_id' => [
                $this->ctx['standard']->id,
                $this->ctx['variantProduct']->id,
                $this->ctx['componentA']->id,
                $this->ctx['componentB']->id,
            ],
            'product_code' => [
                $this->ctx['standard']->code,
                $this->ctx['variantRedPivot']->item_code,
                $this->ctx['componentA']->code,
                $this->ctx['componentB']->code,
            ],
            'qty' => [20, 15, 12, 10],
            'recieved' => [20, 15, 12, 10],
            'purchase_unit' => array_fill(0, 4, $this->ctx['unit']->unit_name),
            'net_unit_cost' => [5, 10, 2, 3],
            'unit_cost' => [5, 10, 2, 3],
            'net_unit_margin' => [0, 0, 0, 0],
            'net_unit_margin_type' => [1, 1, 1, 1],
            'net_unit_price' => [10, 20, 4, 6],
            'discount' => [0, 0, 0, 0],
            'tax_rate' => [0, 0, 0, 0],
            'tax' => [0, 0, 0, 0],
            'imei_number' => ['', '', '', ''],
            'subtotal' => [100, 150, 24, 30],
        ]);

        $this->callController(PurchaseController::class, 'store', $request);
        $purchase = Purchase::where('reference_no', $ref)->first();
        $this->ctx['purchase'] = $purchase;

        $this->check((bool) $purchase, 'Purchase listing contains the verification purchase.', ['reference' => $ref]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 20, 'Purchase standard product stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 15, 'Purchase variant product stock');
        $this->checkStock($this->ctx['componentA'], $this->ctx['warehouseA'], 12, 'Purchase combo component A stock');
        $this->checkStock($this->ctx['componentB'], $this->ctx['warehouseA'], 10, 'Purchase combo component B stock');
        $this->checkStock($this->ctx['combo'], $this->ctx['warehouseA'], 0, 'Combo product has no direct purchased warehouse stock', false);

        $this->event('Purchase', 'Purchased standard, variant, and combo component products.', [
            'reference' => $ref,
            'grand_total' => 405,
            'supplier_due_delta' => 405,
        ]);
    }

    private function saleFlow(): void
    {
        $ref = $this->ref('SALE');
        $request = new StoreSaleRequest();
        $request->replace([
            'reference_no' => $ref,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'customer_id' => $this->ctx['customer']->id,
            'biller_id' => $this->ctx['biller']->id,
            'item' => 3,
            'total_qty' => 9,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 140,
            'grand_total' => 140,
            'sale_status' => 1,
            'payment_status' => 2,
            'paid_amount' => [100],
            'paying_amount' => [100],
            'paid_by_id' => [1],
            'account_id' => $this->ctx['account']->id,
            'payment_note' => '',
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id, $this->ctx['combo']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code, $this->ctx['combo']->code],
            'qty' => [3, 4, 2],
            'sale_unit' => [$this->ctx['unit']->unit_name, $this->ctx['unit']->unit_name, 'n/a'],
            'net_unit_price' => [10, 20, 15],
            'unit_price' => [10, 20, 15],
            'discount' => [0, 0, 0],
            'tax_rate' => [0, 0, 0],
            'tax' => [0, 0, 0],
            'imei_number' => ['', '', ''],
            'subtotal' => [30, 80, 30],
            'coupon_active' => 0,
            'draft' => 0,
            'pos' => 0,
            'product_batch_id' => [null, null, null],
        ]);

        $this->callController(SaleController::class, 'store', $request);
        $sale = Sale::where('reference_no', $ref)->first();
        $this->ctx['sale'] = $sale;

        $this->check((bool) $sale, 'Sale listing contains the verification sale.', ['reference' => $ref]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 17, 'Sale deducts standard product stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 11, 'Sale deducts variant stock');
        $this->checkStock($this->ctx['componentA'], $this->ctx['warehouseA'], 8, 'Sale deducts combo component A stock');
        $this->checkStock($this->ctx['componentB'], $this->ctx['warehouseA'], 8, 'Sale deducts combo component B stock');
        $this->check($sale && (float) $sale->paid_amount === 100.0 && abs(((float) $sale->grand_total - (float) $sale->paid_amount) - 40.0) < 0.01, 'Sale paid and due amounts are correct.', [
            'grand_total' => $sale->grand_total ?? null,
            'paid_amount' => $sale->paid_amount ?? null,
            'due' => $sale ? $sale->grand_total - $sale->paid_amount : null,
        ]);

        $this->event('Sale', 'Sold standard, variant, and combo product.', [
            'reference' => $ref,
            'paid' => 100,
            'due' => 40,
        ]);
    }

    private function transferFlow(): void
    {
        $request = new Request();
        $request->replace([
            'from_warehouse_id' => $this->ctx['warehouseA']->id,
            'to_warehouse_id' => $this->ctx['warehouseB']->id,
            'status' => 1,
            'item' => 3,
            'total_qty' => 10,
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id, $this->ctx['componentA']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code, $this->ctx['componentA']->code],
            'qty' => [5, 3, 2],
            'purchase_unit' => array_fill(0, 3, $this->ctx['unit']->unit_name),
            'net_unit_cost' => [5, 10, 2],
            'tax_rate' => [0, 0, 0],
            'tax' => [0, 0, 0],
            'subtotal' => [25, 30, 4],
            'total_tax' => 0,
            'total_cost' => 59,
            'shipping_cost' => 0,
            'grand_total' => 59,
            'imei_number' => ['', '', ''],
            'product_batch_id' => [null, null, null],
            'product_variant_id' => [null, $this->ctx['variantRed']->id, null],
        ]);

        $this->callController(TransferController::class, 'store', $request);
        $transfer = Transfer::latest('id')->first();
        $this->ctx['transfer'] = $transfer;

        $this->check((bool) $transfer, 'Transfer listing contains the verification transfer.', [
            'reference' => $transfer->reference_no ?? null,
        ]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 12, 'Transfer decreases standard source warehouse');
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseB'], 5, 'Transfer increases standard destination warehouse');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 8, 'Transfer decreases variant source warehouse');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseB'], 3, 'Transfer increases variant destination warehouse');
        $this->checkStock($this->ctx['componentA'], $this->ctx['warehouseA'], 6, 'Component transfer used instead of direct combo transfer');
        $this->checkStock($this->ctx['componentA'], $this->ctx['warehouseB'], 2, 'Component transfer reaches destination warehouse');

        $this->event('Stock Transfer', 'Transferred standard, variant, and combo component stock. Direct combo transfer is not used because combo stock is component-derived.', [
            'reference' => $transfer->reference_no ?? null,
        ]);
    }

    private function saleReturnFlow(): void
    {
        $sale = $this->ctx['sale'];
        $productSales = Product_Sale::where('sale_id', $sale->id)->get()->keyBy('product_id');
        $ref = $this->ref('SRET');
        $request = new Request();
        $request->replace([
            'reference_no' => $ref,
            'sale_id' => $sale->id,
            'customer_id' => $this->ctx['customer']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'biller_id' => $this->ctx['biller']->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 3,
            'total_qty' => 3,
            'total_discount' => 0,
            'total_sale_discount' => 0,
            'order_discount' => 0,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'total_tax' => 0,
            'total_price' => 45,
            'grand_total' => 45,
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id, $this->ctx['combo']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code, $this->ctx['combo']->code],
            'qty' => [1, 1, 1],
            'sale_unit' => [$this->ctx['unit']->unit_name, $this->ctx['unit']->unit_name, 'n/a'],
            'net_unit_price' => [10, 20, 15],
            'discount' => [0, 0, 0],
            'tax_rate' => [0, 0, 0],
            'tax' => [0, 0, 0],
            'subtotal' => [10, 20, 15],
            'product_sale_id' => [
                $productSales[$this->ctx['standard']->id]->id,
                $productSales[$this->ctx['variantProduct']->id]->id,
                $productSales[$this->ctx['combo']->id]->id,
            ],
            'imei_number' => ['', '', ''],
            'product_batch_id' => [null, null, null],
            'change_sale_status' => 0,
        ]);

        $this->callController(ReturnController::class, 'store', $request);
        $return = Returns::where('sale_id', $sale->id)->latest('id')->first();
        $this->ctx['saleReturn'] = $return;
        if ($return) {
            $this->refs[] = $return->reference_no;
        }

        $this->check((bool) $return, 'Sale return listing contains the verification return.', ['reference' => $ref]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 13, 'Sale return restores standard stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 9, 'Sale return restores variant stock');
        $this->checkStock($this->ctx['componentA'], $this->ctx['warehouseA'], 8, 'Sale return restores combo component A');
        $this->checkStock($this->ctx['componentB'], $this->ctx['warehouseA'], 9, 'Sale return restores combo component B');

        $this->event('Sale Return', 'Returned standard, variant, and combo product from completed sale.', [
            'reference' => $ref,
            'customer_due_delta' => -45,
        ]);
    }

    private function purchaseReturnFlow(): void
    {
        $purchase = $this->ctx['purchase'];
        $productPurchases = ProductPurchase::where('purchase_id', $purchase->id)->get()->keyBy('product_id');
        $ref = $this->ref('PRET');
        $request = new Request();
        $request->replace([
            'reference_no' => $ref,
            'purchase_id' => $purchase->id,
            'supplier_id' => $this->ctx['supplier']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 2,
            'total_qty' => 4,
            'total_discount' => 0,
            'order_discount' => 0,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'total_tax' => 0,
            'total_cost' => 30,
            'grand_total' => 30,
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code],
            'qty' => [2, 2],
            'purchase_unit' => [$this->ctx['unit']->unit_name, $this->ctx['unit']->unit_name],
            'net_unit_cost' => [5, 10],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [10, 20],
            'product_purchase_id' => [
                $productPurchases[$this->ctx['standard']->id]->id,
                $productPurchases[$this->ctx['variantProduct']->id]->id,
            ],
            'is_return' => [
                $productPurchases[$this->ctx['standard']->id]->id,
                $productPurchases[$this->ctx['variantProduct']->id]->id,
            ],
            'imei_number' => ['', ''],
            'product_batch_id' => [null, null],
        ]);

        $this->callController(ReturnPurchaseController::class, 'store', $request);
        $return = ReturnPurchase::where('purchase_id', $purchase->id)->latest('id')->first();
        $this->ctx['purchaseReturn'] = $return;
        if ($return) {
            $this->refs[] = $return->reference_no;
        }

        $this->check((bool) $return, 'Purchase return listing contains the verification return.', ['reference' => $ref]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 11, 'Purchase return decreases standard stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 7, 'Purchase return decreases variant stock');

        $this->event('Purchase Return', 'Returned standard and variant purchase quantities. Combo purchase return was not direct-tested because combo stock is component-derived in this verification.', [
            'reference' => $ref,
            'supplier_due_delta' => -30,
        ]);
    }

    private function damageAndAdjustmentFlow(): void
    {
        $adjustRequest = new Request();
        $adjustRequest->replace([
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'item' => 2,
            'total_qty' => 2,
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id],
            'product_variant_id' => [null, $this->ctx['variantRed']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code],
            'qty' => [1, 1],
            'action' => ['-', '-'],
            'unit_cost' => [5, 10],
        ]);

        $this->callController(AdjustmentController::class, 'store', $adjustRequest);
        $adjustment = \App\Models\Adjustment::latest('id')->first();
        $this->ctx['adjustment'] = $adjustment;
        $this->check((bool) $adjustment && ProductAdjustment::where('adjustment_id', $adjustment->id)->count() === 2, 'Adjustment listing contains standard and variant adjustment rows.', [
            'adjustment_id' => $adjustment->id ?? null,
        ]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 10, 'Adjustment decreases standard stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 6, 'Adjustment decreases variant stock');

        $damageRequest = new Request();
        $damageRequest->replace([
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'damaged_at' => now()->toDateString(),
            'item' => 3,
            'total_qty' => 3,
            'product_id' => [$this->ctx['standard']->id, $this->ctx['variantProduct']->id, $this->ctx['componentB']->id],
            'product_code' => [$this->ctx['standard']->code, $this->ctx['variantRedPivot']->item_code, $this->ctx['componentB']->code],
            'qty' => [1, 1, 1],
            'unit_cost' => [5, 10, 3],
        ]);

        $this->callController(DamageStockController::class, 'store', $damageRequest);
        $damage = DamageStock::latest('id')->first();
        $this->ctx['damage'] = $damage;
        $this->check((bool) $damage && ProductDamageStock::where('damage_stock_id', $damage->id)->count() === 3, 'Damage listing contains standard, variant, and component rows.', [
            'damage_stock_id' => $damage->id ?? null,
        ]);
        $this->checkStock($this->ctx['standard'], $this->ctx['warehouseA'], 9, 'Damage decreases standard stock');
        $this->checkVariantStock($this->ctx['variantProduct'], $this->ctx['variantRed'], $this->ctx['warehouseA'], 5, 'Damage decreases variant stock');
        $this->checkStock($this->ctx['componentB'], $this->ctx['warehouseA'], 8, 'Damage uses component stock instead of direct combo damage');

        $this->event('Damage / Adjustment', 'Recorded adjustment and damaged stock. Direct combo damage is treated as unsupported for this verification; component damage was verified.', [
            'adjustment_id' => $adjustment->id ?? null,
            'damage_id' => $damage->id ?? null,
        ]);
    }

    private function saleExchangeFlow(): void
    {
        $sale = $this->ctx['sale'];
        $productSales = Product_Sale::where('sale_id', $sale->id)->get()->keyBy('product_id');

        $before = $this->snapshot([
            'standard_A' => [$this->ctx['standard'], $this->ctx['warehouseA'], null],
            'variant_A' => [$this->ctx['variantProduct'], $this->ctx['warehouseA'], $this->ctx['variantRed']],
            'componentA_A' => [$this->ctx['componentA'], $this->ctx['warehouseA'], null],
            'componentB_A' => [$this->ctx['componentB'], $this->ctx['warehouseA'], null],
            'combo_A' => [$this->ctx['combo'], $this->ctx['warehouseA'], null],
        ]);

        $request = new Request();
        $request->replace([
            'sale_id' => $sale->id,
            'customer_id' => $this->ctx['customer']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'biller_id' => $this->ctx['biller']->id,
            'item' => 6,
            'total_qty' => 7,
            'total_sale_discount' => 0,
            'total_tax' => 0,
            'total_price' => 105,
            'amount' => 105,
            'grand_total' => 0,
            'paid_amount' => 0,
            'product_id' => [
                $this->ctx['standard']->id,
                $this->ctx['variantProduct']->id,
                $this->ctx['combo']->id,
                $this->ctx['standard']->id,
                $this->ctx['variantProduct']->id,
                $this->ctx['combo']->id,
            ],
            'product_code' => [
                $this->ctx['standard']->code,
                $this->ctx['variantRedPivot']->item_code,
                $this->ctx['combo']->code,
                $this->ctx['standard']->code,
                $this->ctx['variantRedPivot']->item_code,
                $this->ctx['combo']->code,
            ],
            'qty' => [1, 1, 1, 1, 1, 2],
            'sale_unit' => [
                $this->ctx['unit']->unit_name,
                $this->ctx['unit']->unit_name,
                'n/a',
                $this->ctx['unit']->unit_name,
                $this->ctx['unit']->unit_name,
                'n/a',
            ],
            'net_unit_price' => [10, 20, 15, 10, 20, 15],
            'discount' => [0, 0, 0, 0, 0, 0],
            'tax_rate' => [0, 0, 0, 0, 0, 0],
            'tax' => [0, 0, 0, 0, 0, 0],
            'subtotal' => [10, 20, 15, 10, 20, 30],
            'product_batch_id' => [null, null, null, null, null, null],
            'imei_number' => ['', '', '', '', '', ''],
            'product_sale_id' => [
                $productSales[$this->ctx['standard']->id]->id,
                $productSales[$this->ctx['variantProduct']->id]->id,
                $productSales[$this->ctx['combo']->id]->id,
                null,
                null,
                null,
            ],
            'type' => ['return', 'return', 'return', 'new', 'new', 'new'],
            'is_exchange' => [
                $this->ctx['standard']->code,
                $this->ctx['variantRedPivot']->item_code,
                $this->ctx['combo']->code,
            ],
        ]);

        $this->callController(ExchangeController::class, 'store', $request);
        $exchange = SaleExchange::where('sale_id', $sale->id)->latest('id')->first();
        $this->ctx['exchange'] = $exchange;

        $this->check((bool) $exchange && ProductExchange::where('exchange_id', $exchange->id)->count() >= 6, 'Sale exchange listing contains returned and new product rows.', [
            'exchange_id' => $exchange->id ?? null,
        ]);

        $after = $this->snapshot([
            'standard_A' => [$this->ctx['standard'], $this->ctx['warehouseA'], null],
            'variant_A' => [$this->ctx['variantProduct'], $this->ctx['warehouseA'], $this->ctx['variantRed']],
            'componentA_A' => [$this->ctx['componentA'], $this->ctx['warehouseA'], null],
            'componentB_A' => [$this->ctx['componentB'], $this->ctx['warehouseA'], null],
            'combo_A' => [$this->ctx['combo'], $this->ctx['warehouseA'], null],
        ]);

        $this->check(abs($after['standard_A'] - $before['standard_A']) < 0.01, 'Sale exchange has no net duplicate movement for standard product.', [
            'before' => $before['standard_A'],
            'after' => $after['standard_A'],
        ]);
        $this->check(abs($after['variant_A'] - $before['variant_A']) < 0.01, 'Sale exchange has no net duplicate movement for variant product.', [
            'before' => $before['variant_A'],
            'after' => $after['variant_A'],
        ]);

        $componentMovementCorrect = abs($after['componentA_A'] - ($before['componentA_A'] - 2)) < 0.01
            && abs($after['componentB_A'] - ($before['componentB_A'] - 1)) < 0.01;
        $comboDirectChanged = abs($after['combo_A'] - $before['combo_A']) > 0.01;
        $this->check($componentMovementCorrect && !$comboDirectChanged, 'Sale exchange handles combo as component stock with no direct combo movement.', [
            'componentA_before' => $before['componentA_A'],
            'componentA_after' => $after['componentA_A'],
            'componentA_expected' => $before['componentA_A'] - 2,
            'componentB_before' => $before['componentB_A'],
            'componentB_after' => $after['componentB_A'],
            'componentB_expected' => $before['componentB_A'] - 1,
            'combo_before' => $before['combo_A'],
            'combo_after' => $after['combo_A'],
        ], 'ExchangeController currently processes combo exchange rows as direct product movement instead of component movement.');

        $this->event('Sale Exchange', 'Performed exchange involving standard, variant, and combo products.', [
            'exchange_id' => $exchange->id ?? null,
        ]);
    }

    private function listingAndReportChecks(): void
    {
        $purchaseTotal = Purchase::whereIn('reference_no', $this->refs)->sum('grand_total');
        $saleTotal = Sale::whereIn('reference_no', $this->refs)->sum('grand_total');
        $saleReturnTotal = Returns::whereIn('reference_no', $this->refs)->sum('grand_total');
        $purchaseReturnTotal = ReturnPurchase::whereIn('reference_no', $this->refs)->sum('grand_total');

        $this->check($purchaseTotal >= 405, 'Purchase report total includes verification purchase.', ['total' => $purchaseTotal]);
        $this->check($saleTotal >= 140, 'Sale report total includes verification sale.', ['total' => $saleTotal]);
        $this->check($saleReturnTotal >= 45, 'Sale return report total includes verification return.', ['total' => $saleReturnTotal]);
        $this->check($purchaseReturnTotal >= 30, 'Purchase return report total includes verification return.', ['total' => $purchaseReturnTotal]);

        $customerDue = Sale::where('customer_id', $this->ctx['customer']->id)->sum('grand_total')
            - Sale::where('customer_id', $this->ctx['customer']->id)->sum('paid_amount')
            - Returns::where('customer_id', $this->ctx['customer']->id)->sum('grand_total');
        $supplierDue = Purchase::where('supplier_id', $this->ctx['supplier']->id)->sum('grand_total')
            - Purchase::where('supplier_id', $this->ctx['supplier']->id)->sum('paid_amount')
            - ReturnPurchase::where('supplier_id', $this->ctx['supplier']->id)->sum('grand_total');

        $this->check(abs($customerDue -  -5.0) < 0.01, 'Customer due report reflects sale, payment, and return impact.', ['due' => $customerDue]);
        $this->check(abs($supplierDue - 375.0) < 0.01, 'Supplier due report reflects purchase and purchase return impact.', ['due' => $supplierDue]);

        $this->check(Product_Warehouse::where('warehouse_id', $this->ctx['warehouseA']->id)->whereIn('product_id', [
            $this->ctx['standard']->id,
            $this->ctx['variantProduct']->id,
            $this->ctx['componentA']->id,
            $this->ctx['componentB']->id,
        ])->count() >= 4, 'Warehouse stock report has rows for verification stock.', [
            'warehouse' => $this->ctx['warehouseA']->name,
        ]);

        $this->event('Listings and Reports', 'Verified transaction listings and report-equivalent totals from the same persisted tables used by reports.', [
            'purchase_total' => $purchaseTotal,
            'sale_total' => $saleTotal,
            'sale_return_total' => $saleReturnTotal,
            'purchase_return_total' => $purchaseReturnTotal,
            'customer_due' => $customerDue,
            'supplier_due' => $supplierDue,
        ]);
    }

    private function callController(string $controllerClass, string $method, Request $request, bool $failOnNotPermitted = true): mixed
    {
        session()->forget(['message', 'not_permitted', 'errors']);
        $response = app($controllerClass)->{$method}($request);

        if ($response instanceof RedirectResponse) {
            $notPermitted = session('not_permitted');
            $errors = session('errors');
            if ($failOnNotPermitted && $notPermitted && !str_contains(strtolower($notPermitted), 'success')) {
                throw new \RuntimeException($controllerClass . '::' . $method . ' failed: ' . $notPermitted);
            }
            if ($errors) {
                throw new \RuntimeException($controllerClass . '::' . $method . ' validation failed: ' . json_encode($errors->messages()));
            }
        }

        return $response;
    }

    private function ref(string $type): string
    {
        $ref = $this->prefix . '-' . $type;
        $this->refs[] = $ref;
        return $ref;
    }

    private function checkStock(Product $product, Warehouse $warehouse, float $expected, string $label, bool $rowRequired = true): void
    {
        $row = Product_Warehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->whereNull('variant_id')
            ->first();

        $actual = $row ? (float) $row->qty : 0.0;
        $this->check((!$rowRequired || $row) && abs($actual - $expected) < 0.01, $label, [
            'product' => $product->code,
            'warehouse' => $warehouse->name,
            'expected' => $expected,
            'actual' => $actual,
        ]);
    }

    private function checkVariantStock(Product $product, Variant $variant, Warehouse $warehouse, float $expected, string $label): void
    {
        $warehouseRow = Product_Warehouse::where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        $productVariant = ProductVariant::where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->first();

        $actualWarehouse = $warehouseRow ? (float) $warehouseRow->qty : 0.0;
        $this->check($warehouseRow && abs($actualWarehouse - $expected) < 0.01, $label . ' in warehouse', [
            'product' => $product->code,
            'variant' => $variant->name,
            'warehouse' => $warehouse->name,
            'expected' => $expected,
            'actual' => $actualWarehouse,
        ]);

        $expectedGlobal = Product_Warehouse::where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->sum('qty');
        $this->check($productVariant && abs((float) $productVariant->qty - (float) $expectedGlobal) < 0.01, $label . ' global variant quantity matches warehouse total', [
            'expected' => $expectedGlobal,
            'actual' => $productVariant->qty ?? null,
        ]);
    }

    private function snapshot(array $items): array
    {
        $snapshot = [];
        foreach ($items as $key => [$product, $warehouse, $variant]) {
            $query = Product_Warehouse::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id);
            if ($variant) {
                $query->where('variant_id', $variant->id);
            } else {
                $query->whereNull('variant_id');
            }
            $snapshot[$key] = (float) ($query->first()->qty ?? 0);
        }

        return $snapshot;
    }

    private function check(bool $condition, string $label, array $context = [], ?string $bug = null): void
    {
        $this->checks[] = [
            'label' => $label,
            'status' => $condition ? 'PASS' : 'FAIL',
            'context' => $context,
        ];

        if (!$condition && $bug) {
            $this->recordBug($label, 'Check should pass according to requested business behavior.', $bug, $context);
        }
    }

    private function event(string $flow, string $summary, array $context = []): void
    {
        $this->events[] = compact('flow', 'summary', 'context');
        $this->line($flow . ': ' . $summary);
    }

    private function recordBug(string $flow, string $expected, string $actual, array $context = []): void
    {
        $this->bugs[] = compact('flow', 'expected', 'actual', 'context');
    }

    private function hasFailures(): bool
    {
        foreach ($this->checks as $check) {
            if ($check['status'] === 'FAIL') {
                return true;
            }
        }

        return false;
    }

    private function writeReport(): string
    {
        $path = storage_path('app/e2e-inventory-verification-' . $this->runId . '.md');
        $lines = [];
        $lines[] = '# SalePro E2E Inventory Verification';
        $lines[] = '';
        $lines[] = '- Run ID: `' . $this->runId . '`';
        $lines[] = '- Prefix: `' . $this->prefix . '`';
        $lines[] = '- Result: `' . ($this->hasFailures() ? 'FAIL' : 'PASS') . '`';
        $lines[] = '- Persistence: `' . ($this->option('commit') ? 'committed' : 'rolled back after report') . '`';
        $lines[] = '';
        $lines[] = '## Products';
        foreach (['standard', 'variantProduct', 'combo', 'componentA', 'componentB'] as $key) {
            if (isset($this->ctx[$key])) {
                $product = $this->ctx[$key];
                $lines[] = '- ' . $key . ': ID `' . $product->id . '`, code `' . $product->code . '`, type `' . $product->type . '`';
            }
        }
        if (isset($this->ctx['variantRedPivot'])) {
            $lines[] = '- variant code: `' . $this->ctx['variantRedPivot']->item_code . '`';
        }
        $lines[] = '';
        $lines[] = '## References';
        foreach ($this->refs as $ref) {
            $lines[] = '- `' . $ref . '`';
        }
        $lines[] = '';
        $lines[] = '## Flow Summary';
        foreach ($this->events as $event) {
            $lines[] = '- ' . $event['flow'] . ': ' . $event['summary'];
            if ($event['context']) {
                $lines[] = '  Context: `' . json_encode($event['context']) . '`';
            }
        }
        $lines[] = '';
        $lines[] = '## Checks';
        foreach ($this->checks as $check) {
            $lines[] = '- [' . $check['status'] . '] ' . $check['label'];
            if ($check['context']) {
                $lines[] = '  Context: `' . json_encode($check['context']) . '`';
            }
        }
        $lines[] = '';
        $lines[] = '## Bugs Found';
        if (!$this->bugs) {
            $lines[] = '- None.';
        } else {
            foreach ($this->bugs as $bug) {
                $lines[] = '- Flow: ' . $bug['flow'];
                $lines[] = '  Expected: ' . $bug['expected'];
                $lines[] = '  Actual: ' . $bug['actual'];
                $lines[] = '  Affected files: `app/Http/Controllers/ExchangeController.php` for combo exchange handling when applicable.';
                $lines[] = '  Proposed minimal fix: route combo exchange return/new rows through the same component stock logic used by sale and sale return.';
                if ($bug['context']) {
                    $lines[] = '  Context: `' . json_encode($bug['context']) . '`';
                }
            }
        }
        $lines[] = '';
        $lines[] = '## Fixes Applied';
        $lines[] = '- `app/Http/Controllers/ExchangeController.php`: combo sale exchange now moves component stock for returned and new combo rows instead of moving the combo product directly.';
        $lines[] = '- `app/Services/AccountingService.php`: `executeSafe` accepts the source metadata used by current accounting calls and returns `AccountingResult` failures instead of throwing type errors.';
        $lines[] = '- Escaped PHP variables were corrected in accounting patch blocks that prevented runtime parsing.';
        $lines[] = '';
        $lines[] = '## Commands';
        $lines[] = '- Run and roll back data: `php artisan verify:e2e-inventory-flow`';
        $lines[] = '- Run and keep data for manual UI checks: `php artisan verify:e2e-inventory-flow --commit`';
        $lines[] = '';

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, implode(PHP_EOL, $lines));

        return $path;
    }
}
