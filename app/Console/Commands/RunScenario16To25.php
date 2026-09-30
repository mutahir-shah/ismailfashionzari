<?php

namespace App\Console\Commands;

use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReturnPurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\DamageStockController;
use App\Http\Controllers\ExchangeController;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Models\Account;
use App\Models\AccountingAccount;
use App\Models\Biller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Adjustment;
use App\Models\DamageStock;
use App\Models\Product;
use App\Models\ProductExchange;
use App\Models\ProductAdjustment;
use App\Models\ProductBatch;
use App\Models\ProductDamageStock;
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

class RunScenario16To25 extends Command
{
    protected $signature = 'test:scenario16-25 {--commit : Commit scenario data instead of rolling back}';

    protected $description = 'Scenario 16-25 regression suite: edits, deletes, due payments, warehouse isolation, batch/IMEI, and accounting/report reconciliation.';

    private string $prefix;
    private array $ctx = [];
    private array $refs = [];

    public function handle(): int
    {
        $this->prefix = 'S16-25-' . now()->format('YmdHis');
        $this->info('Starting Scenario 16-25 Regression Suite: ' . $this->prefix);

        DB::beginTransaction();

        try {
            $this->login();
            $this->setup();
            $this->scenario16SaleEdit();
            $this->scenario17TransferEditDelete();
            $this->scenario18PurchaseDelete();
            $this->scenario19SaleDuePayments();
            $this->scenario20PurchaseDuePayments();
            $this->scenario21WarehouseIsolation();
            $this->scenario22BatchFlow();
            $this->scenario23ImeiFlow();
            $this->scenario24AccountingReconciliation();
            $this->scenario26DeleteVoidAccountingAndStock();
            $this->scenario27PaymentRefundAccounting();
            $this->scenario28SaleExchangeAccountingAndStock();
            $this->scenario25ReportReconciliation();

            if ($this->option('commit')) {
                DB::commit();
                $this->warn('Scenario data committed because --commit was used.');
            } else {
                DB::rollBack();
                $this->info('Scenario data rolled back.');
            }

            $this->info('Scenario 16-25 PASSED.');
            return 0;
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('Scenario 16-25 FAILED: ' . $e->getMessage());
            $this->error($e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }

    private function login(): void
    {
        $admin = User::where('role_id', 1)->where('is_active', true)->first() ?: User::first();
        if (!$admin) {
            throw new \RuntimeException('No user available for scenario authentication.');
        }
        Auth::login($admin);
    }

    private function setup(): void
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
            'address' => 'Regression',
            'city' => 'Regression',
            'country' => 'Regression',
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
            'address' => 'Regression',
            'city' => 'Regression',
            'country' => 'Regression',
            'is_active' => true,
        ]);

        $biller = Biller::create([
            'name' => $this->prefix . '-Biller',
            'company_name' => $this->prefix . '-Biller-Co',
            'email' => strtolower($this->prefix) . '-biller@example.test',
            'phone_number' => '3000000000',
            'address' => 'Regression',
            'city' => 'Regression',
            'country' => 'Regression',
            'is_active' => true,
        ]);

        $account = Account::create([
            'account_no' => $this->prefix,
            'name' => $this->prefix . '-Cash',
            'initial_balance' => 10000,
            'total_balance' => 10000,
            'is_default' => false,
            'is_active' => true,
        ]);

        $warehouseA = Warehouse::create([
            'name' => $this->prefix . '-Warehouse-A',
            'phone' => '4000000000',
            'email' => strtolower($this->prefix) . '-a@example.test',
            'address' => 'Regression A',
            'is_active' => true,
        ]);

        $warehouseB = Warehouse::create([
            'name' => $this->prefix . '-Warehouse-B',
            'phone' => '5000000000',
            'email' => strtolower($this->prefix) . '-b@example.test',
            'address' => 'Regression B',
            'is_active' => true,
        ]);

        $standard = $this->makeProduct('STD', $unit, $category, 5, 10);
        $standardTwo = $this->makeProduct('STD2', $unit, $category, 4, 8);
        $comboChild = $this->makeProduct('COMBO-CHILD', $unit, $category, 3, 6);
        $batch = $this->makeProduct('BATCH', $unit, $category, 6, 12, ['is_batch' => 1]);
        $imei = $this->makeProduct('IMEI', $unit, $category, 20, 30, ['is_imei' => 1]);
        $variantProduct = $this->makeProduct('VAR', $unit, $category, 7, 14, ['is_variant' => 1]);
        $combo = $this->makeProduct('COMBO', $unit, $category, 6, 18, [
            'type' => 'combo',
            'product_list' => (string) $comboChild->id,
            'qty_list' => '2',
            'price_list' => '6',
            'variant_list' => '',
        ]);
        $variant = Variant::create(['name' => $this->prefix . '-Variant-A']);
        $productVariant = ProductVariant::create([
            'product_id' => $variantProduct->id,
            'variant_id' => $variant->id,
            'position' => 1,
            'item_code' => $variantProduct->code . '-A',
            'additional_cost' => 0,
            'additional_price' => 0,
            'qty' => 0,
        ]);

        foreach ([$standard, $standardTwo, $comboChild, $batch, $imei] as $product) {
            Product_Warehouse::create(['product_id' => $product->id, 'warehouse_id' => $warehouseA->id, 'qty' => 0]);
            Product_Warehouse::create(['product_id' => $product->id, 'warehouse_id' => $warehouseB->id, 'qty' => 0]);
        }
        Product_Warehouse::create(['product_id' => $variantProduct->id, 'variant_id' => $variant->id, 'warehouse_id' => $warehouseA->id, 'qty' => 0]);
        Product_Warehouse::create(['product_id' => $variantProduct->id, 'variant_id' => $variant->id, 'warehouse_id' => $warehouseB->id, 'qty' => 0]);

        $this->ctx = compact(
            'unit',
            'category',
            'customer',
            'supplier',
            'biller',
            'account',
            'warehouseA',
            'warehouseB',
            'standard',
            'standardTwo',
            'comboChild',
            'combo',
            'batch',
            'imei',
            'variantProduct',
            'variant',
            'productVariant'
        );

        $this->seedStock($standard, $warehouseA, 100);
        $this->seedStock($standardTwo, $warehouseA, 100);
        $this->seedStock($comboChild, $warehouseA, 100);
        $this->seedStock($variantProduct, $warehouseA, 60, $variant);
        $this->seedStock($batch, $warehouseA, 0);
        $this->seedStock($imei, $warehouseA, 0);
        $this->ensureAccountingAccounts();
    }

    private function makeProduct(string $code, Unit $unit, Category $category, float $cost, float $price, array $extra = []): Product
    {
        return Product::create(array_merge([
            'name' => $this->prefix . '-' . $code,
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

    private function seedStock(Product $product, Warehouse $warehouse, float $qty, ?Variant $variant = null): void
    {
        $product->qty += $qty;
        $product->save();

        $query = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id);
        $variant ? $query->where('variant_id', $variant->id) : $query->whereNull('variant_id');
        $row = $query->firstOrFail();
        $row->qty += $qty;
        $row->save();

        if ($variant) {
            ProductVariant::where('product_id', $product->id)->where('variant_id', $variant->id)->increment('qty', $qty);
        }
    }

    private function scenario16SaleEdit(): void
    {
        $this->info('Scenario 16: sale edit stock recalculation');
        $sale = $this->createSale($this->ctx['standard'], 5, 50, 50, 'S16-SALE');
        $this->assertEquals(95, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S16 sale create stock');

        $request = $this->saleRequest($this->ctx['standard'], 8, 80, 80, $sale->reference_no);
        $request->merge(['paid_amount' => 80]);
        $this->callController(SaleController::class, 'update', $request, [$sale->id]);
        $this->assertEquals(92, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S16 sale edit up stock');

        $request = $this->saleRequest($this->ctx['standard'], 3, 30, 30, $sale->reference_no);
        $request->merge(['paid_amount' => 30]);
        $this->callController(SaleController::class, 'update', $request, [$sale->id]);
        $this->assertEquals(97, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S16 sale edit down stock');
    }

    private function scenario17TransferEditDelete(): void
    {
        $this->info('Scenario 17: transfer edit and delete isolation');
        $request = $this->transferRequest($this->ctx['standard'], 10);
        $this->callController(TransferController::class, 'store', $request);
        $transfer = Transfer::latest('id')->first();
        $this->assertEquals(87, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S17 transfer create source');
        $this->assertEquals(10, $this->stock($this->ctx['standard'], $this->ctx['warehouseB']), 'S17 transfer create destination');

        $request = $this->transferRequest($this->ctx['standard'], 15);
        $this->callController(TransferController::class, 'update', $request, [$transfer->id]);
        $this->assertEquals(82, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S17 transfer edit source');
        $this->assertEquals(15, $this->stock($this->ctx['standard'], $this->ctx['warehouseB']), 'S17 transfer edit destination');

        $this->callController(TransferController::class, 'destroy', null, [$transfer->id]);
        $this->assertEquals(97, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S17 transfer delete source');
        $this->assertEquals(0, $this->stock($this->ctx['standard'], $this->ctx['warehouseB']), 'S17 transfer delete destination');
        $this->assertFalse(ProductTransfer::where('transfer_id', $transfer->id)->exists(), 'S17 no orphan transfer rows');
    }

    private function scenario18PurchaseDelete(): void
    {
        $this->info('Scenario 18: purchase delete reverses stock and children');
        $purchase = $this->createPurchase($this->ctx['standard'], 10, 50, 'S18-PUR');
        $this->assertEquals(107, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S18 purchase stock');
        $this->callController(PurchaseController::class, 'destroy', null, [$purchase->id]);
        $this->assertEquals(97, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S18 purchase delete stock');
        $this->assertFalse(ProductPurchase::where('purchase_id', $purchase->id)->exists(), 'S18 no orphan purchase rows');
        $this->assertFalse(Payment::where('purchase_id', $purchase->id)->exists(), 'S18 no orphan purchase payments');
    }

    private function scenario19SaleDuePayments(): void
    {
        $this->info('Scenario 19: sale due payment add/update/delete');
        $sale = $this->createSale($this->ctx['standard'], 4, 40, 0, 'S19-DUE-SALE');
        $this->assertEquals(40, $sale->grand_total - $sale->paid_amount, 'S19 initial due');

        $request = new Request([
            'sale_id' => $sale->id,
            'amount' => 15,
            'paying_amount' => 15,
            'paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'payment_note' => 'S19 payment',
            'payment_receiver' => 'Scenario',
        ]);
        $this->callController(SaleController::class, 'addPayment', $request);
        $sale->refresh();
        $payment = Payment::where('sale_id', $sale->id)->latest('id')->first();
        $this->assertEquals(25, $sale->grand_total - $sale->paid_amount, 'S19 due after payment');

        $delete = new Request(['id' => $payment->id]);
        $this->callController(SaleController::class, 'deletePayment', $delete);
        $sale->refresh();
        $this->assertEquals(40, $sale->grand_total - $sale->paid_amount, 'S19 due after payment delete');
    }

    private function scenario20PurchaseDuePayments(): void
    {
        $this->info('Scenario 20: purchase due payment add/update/delete');
        $purchase = $this->createPurchase($this->ctx['standard'], 4, 20, 'S20-DUE-PUR', 0);
        $this->assertEquals(20, $purchase->grand_total - $purchase->paid_amount, 'S20 initial due');

        $request = new Request([
            'purchase_id' => $purchase->id,
            'amount' => 12,
            'paying_amount' => 12,
            'paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'payment_note' => 'S20 payment',
            'cheque_no' => '',
        ]);
        $this->callController(PurchaseController::class, 'addPayment', $request);
        $purchase->refresh();
        $payment = Payment::where('purchase_id', $purchase->id)->latest('id')->first();
        $this->assertEquals(8, $purchase->grand_total - $purchase->paid_amount, 'S20 due after payment');

        $this->callController(PurchaseController::class, 'deletePayment', new Request(['id' => $payment->id]));
        $purchase->refresh();
        $this->assertEquals(20, $purchase->grand_total - $purchase->paid_amount, 'S20 due after payment delete');
    }

    private function scenario21WarehouseIsolation(): void
    {
        $this->info('Scenario 21: warehouse isolation for standard and variant stock');
        $beforeA = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $beforeB = $this->stock($this->ctx['standard'], $this->ctx['warehouseB']);
        $this->createSale($this->ctx['standard'], 2, 20, 20, 'S21-WHA', $this->ctx['warehouseA']);
        $this->assertEquals($beforeA - 2, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S21 A stock decreases');
        $this->assertEquals($beforeB, $this->stock($this->ctx['standard'], $this->ctx['warehouseB']), 'S21 B stock unchanged');

        $variantBeforeA = $this->stock($this->ctx['variantProduct'], $this->ctx['warehouseA'], $this->ctx['variant']);
        $variantBeforeB = $this->stock($this->ctx['variantProduct'], $this->ctx['warehouseB'], $this->ctx['variant']);
        $this->createSale($this->ctx['variantProduct'], 2, 28, 28, 'S21-VARA', $this->ctx['warehouseA'], $this->ctx['productVariant']->item_code);
        $this->assertEquals($variantBeforeA - 2, $this->stock($this->ctx['variantProduct'], $this->ctx['warehouseA'], $this->ctx['variant']), 'S21 variant A decreases');
        $this->assertEquals($variantBeforeB, $this->stock($this->ctx['variantProduct'], $this->ctx['warehouseB'], $this->ctx['variant']), 'S21 variant B unchanged');
    }

    private function scenario22BatchFlow(): void
    {
        $this->info('Scenario 22: batch purchase/sale/return reconciliation');
        $batchNo = $this->prefix . '-BATCH-001';
        $purchase = $this->createPurchase($this->ctx['batch'], 10, 60, 'S22-BATCH-PUR', 0, [
            'batch_no' => [$batchNo],
            'expired_date' => [now()->addYear()->toDateString()],
        ]);
        $batch = ProductBatch::where('product_id', $this->ctx['batch']->id)->where('batch_no', $batchNo)->firstOrFail();
        $this->assertEquals(10, (float) $batch->qty, 'S22 batch purchase qty');

        $sale = $this->createSale($this->ctx['batch'], 3, 36, 36, 'S22-BATCH-SALE', $this->ctx['warehouseA'], $this->ctx['batch']->code, $batch->id);
        $batch->refresh();
        $this->assertEquals(7, (float) $batch->qty, 'S22 batch sale qty');
        $this->assertTrue(Product_Sale::where('sale_id', $sale->id)->where('product_batch_id', $batch->id)->exists(), 'S22 sale row keeps batch id');
    }

    private function scenario23ImeiFlow(): void
    {
        $this->info('Scenario 23: IMEI purchase/sale isolation');
        $imeis = $this->prefix . '-IMEI-1,' . $this->prefix . '-IMEI-2';
        $this->createPurchase($this->ctx['imei'], 2, 40, 'S23-IMEI-PUR', 0, ['imei_number' => [$imeis]]);
        $warehouse = Product_Warehouse::where('product_id', $this->ctx['imei']->id)->where('warehouse_id', $this->ctx['warehouseA']->id)->firstOrFail();
        $this->assertTrue(str_contains($warehouse->imei_number ?? '', $this->prefix . '-IMEI-1'), 'S23 IMEI stored on purchase');

        $this->createSale($this->ctx['imei'], 1, 30, 30, 'S23-IMEI-SALE', $this->ctx['warehouseA'], $this->ctx['imei']->code, null, $this->prefix . '-IMEI-1');
        $warehouse->refresh();
        $this->assertFalse(str_contains($warehouse->imei_number ?? '', $this->prefix . '-IMEI-1'), 'S23 sold IMEI removed');
        $this->assertTrue(str_contains($warehouse->imei_number ?? '', $this->prefix . '-IMEI-2'), 'S23 unsold IMEI remains');
    }

    private function scenario24AccountingReconciliation(): void
    {
        $this->info('Scenario 24: accounting journal reconciliation');
        $purchase = $this->createPurchase($this->ctx['standard'], 1, 5, 'S24-ACC-PUR', 0);
        $sale = $this->createSale($this->ctx['standard'], 1, 10, 10, 'S24-ACC-SALE');

        foreach ([$purchase, $sale] as $source) {
            $entry = JournalEntry::where('source_type', get_class($source))->where('source_id', $source->id)->latest('id')->first();
            $this->assertTrue((bool) $entry, 'S24 journal exists for ' . class_basename($source));
            $debit = $entry->lines()->sum('debit');
            $credit = $entry->lines()->sum('credit');
            $this->assertEquals(round($debit, 2), round($credit, 2), 'S24 balanced journal for ' . class_basename($source));
        }

        app(\App\Services\AccountingService::class)->recordSale($sale, 'sale_created');
        $saleJournalCount = JournalEntry::where('source_type', get_class($sale))
            ->where('source_id', $sale->id)
            ->where('event_type', 'sale_created')
            ->count();
        $this->assertEquals(1, $saleJournalCount, 'S24 repeated sale journal call remains idempotent');

        $paymentIds = Payment::where('sale_id', $sale->id)->pluck('id');
        $this->assertTrue(
            JournalEntry::where('source_type', Payment::class)
                ->whereIn('source_id', $paymentIds)
                ->where('event_type', 'sale_payment_created')
                ->exists(),
            'S24 sale payment journal remains separate payment source'
        );
        $this->assertFalse(
            JournalEntry::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->where('event_type', 'sale_payment_created')
                ->exists(),
            'S24 sale source does not contain payment journal event'
        );

        $saleOriginal = $this->journalFor($sale, 'sale_created');
        $saleOriginalLines = $this->journalLineSignature($saleOriginal);
        $purchaseOriginal = $this->journalFor($purchase, 'purchase_created');
        $purchaseOriginalLines = $this->journalLineSignature($purchaseOriginal);

        $saleUpdate = $this->saleRequest($this->ctx['standard'], 1, 14, 14, $sale->reference_no);
        $saleUpdate->merge(['paid_amount' => 14, 'paying_amount' => 14]);
        $this->callController(SaleController::class, 'update', $saleUpdate, [$sale->id]);

        $purchaseUpdate = $this->purchaseUpdateRequest($this->ctx['standard'], 1, 7, $purchase->reference_no, 0);
        $this->callController(PurchaseController::class, 'update', $purchaseUpdate, [$purchase->id]);

        $this->assertEquals($saleOriginalLines, $this->journalLineSignature($saleOriginal->fresh('lines')), 'S24 sale original journal lines unchanged');
        $this->assertEquals($purchaseOriginalLines, $this->journalLineSignature($purchaseOriginal->fresh('lines')), 'S24 purchase original journal lines unchanged');

        $this->assertReversalMirrors($saleOriginal, $this->journalFor($sale, 'sale_created_reversed'), 'S24 sale reversal mirrors original');
        $this->assertReversalMirrors($purchaseOriginal, $this->journalFor($purchase, 'purchase_created_reversed'), 'S24 purchase reversal mirrors original');

        $saleUpdated = $this->journalFor($sale, 'sale_updated');
        $purchaseUpdated = $this->journalFor($purchase, 'purchase_updated');
        $this->assertEquals(14, $saleUpdated->lines()->sum('debit'), 'S24 sale corrected journal matches first update');
        $this->assertEquals(7, $purchaseUpdated->lines()->sum('debit'), 'S24 purchase corrected journal matches first update');

        $saleUpdateTwo = $this->saleRequest($this->ctx['standard'], 1, 18, 18, $sale->reference_no);
        $saleUpdateTwo->merge(['paid_amount' => 18, 'paying_amount' => 18]);
        $this->callController(SaleController::class, 'update', $saleUpdateTwo, [$sale->id]);

        $purchaseUpdateTwo = $this->purchaseUpdateRequest($this->ctx['standard'], 1, 9, $purchase->reference_no, 0);
        $this->callController(PurchaseController::class, 'update', $purchaseUpdateTwo, [$purchase->id]);

        $this->assertReversalMirrors($saleUpdated, $this->journalFor($sale, 'sale_updated_reversed'), 'S24 sale second update reverses prior correction');
        $this->assertReversalMirrors($purchaseUpdated, $this->journalFor($purchase, 'purchase_updated_reversed'), 'S24 purchase second update reverses prior correction');

        $this->assertEquals(18, $this->journalFor($sale, 'sale_updated_2')->lines()->sum('debit'), 'S24 sale second corrected journal matches transaction');
        $this->assertEquals(9, $this->journalFor($purchase, 'purchase_updated_2')->lines()->sum('debit'), 'S24 purchase second corrected journal matches transaction');

        $this->assertEquals(0, $this->duplicateJournalGroups($sale), 'S24 sale second update has no duplicate journal events');
        $this->assertEquals(0, $this->duplicateJournalGroups($purchase), 'S24 purchase second update has no duplicate journal events');

        $this->assertEquals(18, $this->netAccountEffect($sale, $this->roleAccountId(\App\Services\AccountingService::ROLE_ACCOUNTS_RECEIVABLE)), 'S24 sale AR net effect equals latest sale');
        $this->assertEquals(-18, $this->netAccountEffect($sale, $this->roleAccountId(\App\Services\AccountingService::ROLE_SALES_REVENUE)), 'S24 sale revenue net effect equals latest sale');
        $this->assertEquals(9, $this->netAccountEffect($purchase, $this->roleAccountId(\App\Services\AccountingService::ROLE_INVENTORY)), 'S24 purchase inventory net effect equals latest purchase');
        $this->assertEquals(-9, $this->netAccountEffect($purchase, $this->roleAccountId(\App\Services\AccountingService::ROLE_ACCOUNTS_PAYABLE)), 'S24 purchase AP net effect equals latest purchase');
    }

    private function scenario25ReportReconciliation(): void
    {
        $this->info('Scenario 25: report/query reconciliation for scenario references');
        $purchaseTotal = Purchase::whereIn('reference_no', $this->refs)->sum('grand_total');
        $saleTotal = Sale::whereIn('reference_no', $this->refs)->sum('grand_total');
        $paymentTotal = Payment::whereIn('sale_id', Sale::whereIn('reference_no', $this->refs)->pluck('id'))->sum('amount');
        $stockTotal = Product_Warehouse::whereIn('product_id', [
            $this->ctx['standard']->id,
            $this->ctx['variantProduct']->id,
            $this->ctx['batch']->id,
            $this->ctx['imei']->id,
        ])->sum('qty');

        $this->assertTrue($purchaseTotal > 0, 'S25 purchase report total positive');
        $this->assertTrue($saleTotal > 0, 'S25 sale report total positive');
        $this->assertTrue($paymentTotal > 0, 'S25 payment report total positive');
        $this->assertTrue($stockTotal > 0, 'S25 stock report total positive');
    }

    private function scenario26DeleteVoidAccountingAndStock(): void
    {
        $this->info('Scenario 26: delete/void accounting and stock reversal');

        $sale = $this->createSale($this->ctx['standard'], 2, 20, 20, 'S26-DEL-SALE');
        $saleStockAfterCreate = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $saleOriginal = $this->journalFor($sale, 'sale_created');
        $salePayment = Payment::where('sale_id', $sale->id)->firstOrFail();
        $salePaymentOriginal = $this->paymentJournalFor($salePayment, 'sale_payment_created');

        $this->callController(SaleController::class, 'destroy', null, [$sale->id]);

        $this->assertEquals($saleStockAfterCreate + 2, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 sale delete restores stock');
        $this->assertTrue((bool) Sale::withTrashed()->where('id', $sale->id)->whereNotNull('deleted_at')->first(), 'S26 sale is soft-deleted');
        $this->assertReversalMirrors($saleOriginal, $this->journalFor($sale, 'sale_created_deleted'), 'S26 sale delete reversal mirrors sale journal');
        $this->assertReversalMirrors($salePaymentOriginal, $this->paymentJournalForId($salePayment->id, 'sale_payment_created_deleted'), 'S26 sale delete reverses payment journal');
        $this->assertEquals(0, $this->netAccountEffect($sale, $this->roleAccountId(\App\Services\AccountingService::ROLE_ACCOUNTS_RECEIVABLE)), 'S26 deleted sale AR nets to zero');
        $this->assertEquals(0, Sale::where('reference_no', $sale->reference_no)->sum('grand_total'), 'S26 sale reports exclude deleted sale');
        app(\App\Services\AccountingService::class)->reverseTransaction(Sale::class, $sale->id, '_deleted');
        app(\App\Services\AccountingService::class)->reverseTransaction(Payment::class, $salePayment->id, '_deleted');
        $this->assertEquals(0, $this->duplicateJournalGroups($sale), 'S26 sale repeated delete reversal has no duplicate journals');

        $purchase = $this->createPurchase($this->ctx['standard'], 2, 10, 'S26-DEL-PUR', 10);
        $purchaseStockAfterCreate = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $purchaseOriginal = $this->journalFor($purchase, 'purchase_created');
        $purchasePayment = Payment::where('purchase_id', $purchase->id)->firstOrFail();
        $purchasePaymentOriginal = $this->paymentJournalFor($purchasePayment, 'purchase_payment_created');

        $this->callController(PurchaseController::class, 'destroy', null, [$purchase->id]);

        $this->assertEquals($purchaseStockAfterCreate - 2, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 purchase delete restores stock');
        $this->assertTrue((bool) Purchase::withTrashed()->where('id', $purchase->id)->whereNotNull('deleted_at')->first(), 'S26 purchase is soft-deleted');
        $this->assertReversalMirrors($purchaseOriginal, $this->journalFor($purchase, 'purchase_created_deleted'), 'S26 purchase delete reversal mirrors purchase journal');
        $this->assertReversalMirrors($purchasePaymentOriginal, $this->paymentJournalForId($purchasePayment->id, 'purchase_payment_created_deleted'), 'S26 purchase delete reverses payment journal');
        $this->assertEquals(0, $this->netAccountEffect($purchase, $this->roleAccountId(\App\Services\AccountingService::ROLE_INVENTORY)), 'S26 deleted purchase inventory nets to zero');
        $this->assertEquals(0, Purchase::where('reference_no', $purchase->reference_no)->sum('grand_total'), 'S26 purchase reports exclude deleted purchase');

        $return = $this->createPostedSaleReturn();
        $returnOriginal = $this->journalFor($return, 'sale_return_created');
        $returnStockAfterCreate = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $this->callController(ReturnController::class, 'destroy', null, [$return->id]);
        $this->assertEquals($returnStockAfterCreate - 1, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 sale return delete restores stock');
        $this->assertFalse(Returns::where('id', $return->id)->exists(), 'S26 sale return is physically deleted');
        $this->assertReversalMirrors($returnOriginal, $this->journalFor($return, 'sale_return_created_deleted'), 'S26 sale return delete reversal mirrors original');

        $purchaseReturn = $this->createPostedPurchaseReturn();
        $purchaseReturnOriginal = $this->journalFor($purchaseReturn, 'purchase_return_created');
        $purchaseReturnStockAfterCreate = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $this->callController(ReturnPurchaseController::class, 'destroy', null, [$purchaseReturn->id]);
        $this->assertEquals($purchaseReturnStockAfterCreate + 1, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 purchase return delete restores stock');
        $this->assertFalse(ReturnPurchase::where('id', $purchaseReturn->id)->exists(), 'S26 purchase return is physically deleted');
        $this->assertReversalMirrors($purchaseReturnOriginal, $this->journalFor($purchaseReturn, 'purchase_return_created_deleted'), 'S26 purchase return delete reversal mirrors original');

        $transferBeforeA = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $transferBeforeB = $this->stock($this->ctx['standard'], $this->ctx['warehouseB']);
        $this->callController(TransferController::class, 'store', $this->transferRequest($this->ctx['standard'], 3));
        $transfer = Transfer::latest('id')->first();
        $this->callController(TransferController::class, 'destroy', null, [$transfer->id]);
        $this->assertEquals($transferBeforeA, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 transfer delete restores source stock');
        $this->assertEquals($transferBeforeB, $this->stock($this->ctx['standard'], $this->ctx['warehouseB']), 'S26 transfer delete restores destination stock');
        $this->assertFalse(Transfer::where('id', $transfer->id)->exists(), 'S26 transfer is physically deleted');
        $this->assertFalse(JournalEntry::where('source_type', Transfer::class)->where('source_id', $transfer->id)->exists(), 'S26 transfer has no accounting journal to reverse');

        $adjustmentBefore = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $this->callController(AdjustmentController::class, 'store', $this->adjustmentRequest('-', 2));
        $adjustment = Adjustment::latest('id')->first();
        $this->callController(AdjustmentController::class, 'destroy', null, [$adjustment->id]);
        $this->assertEquals($adjustmentBefore, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 adjustment delete restores stock');
        $this->assertFalse(Adjustment::where('id', $adjustment->id)->exists(), 'S26 adjustment is physically deleted');
        $this->assertFalse(JournalEntry::where('source_type', Adjustment::class)->where('source_id', $adjustment->id)->exists(), 'S26 adjustment has no accounting journal to reverse');

        $damageBefore = $this->stock($this->ctx['standard'], $this->ctx['warehouseA']);
        $damage = $this->createDamageStock(2);
        $this->callController(DamageStockController::class, 'destroy', null, [$damage->id]);
        $this->assertEquals($damageBefore, $this->stock($this->ctx['standard'], $this->ctx['warehouseA']), 'S26 damage delete restores stock');
        $this->assertTrue((bool) DamageStock::withTrashed()->where('id', $damage->id)->whereNotNull('deleted_at')->first(), 'S26 damage is soft-deleted');
        $this->assertFalse(JournalEntry::where('source_type', DamageStock::class)->where('source_id', $damage->id)->exists(), 'S26 damage has no accounting journal to reverse');
    }

    private function scenario27PaymentRefundAccounting(): void
    {
        $this->info('Scenario 27: payment add/update/delete/refund accounting');

        $saleUpdate = $this->createSale($this->ctx['standard'], 1, 100, 0, 'S27-SALE-PAY-UPDATE');
        $this->callController(SaleController::class, 'addPayment', $this->salePaymentRequest($saleUpdate, 30));
        $salePayment = Payment::where('sale_id', $saleUpdate->id)->latest('id')->firstOrFail();
        $salePaymentOriginal = $this->paymentJournalFor($salePayment, 'sale_payment_created');
        $this->assertEquals(70, $this->saleDue($saleUpdate), 'S27 sale partial payment updates customer due');

        $this->callController(SaleController::class, 'updatePayment', $this->salePaymentUpdateRequest($salePayment, 45));
        $salePayment->refresh();
        $this->assertReversalMirrors($salePaymentOriginal, $this->paymentJournalForId($salePayment->id, 'sale_payment_created_reversed'), 'S27 sale payment update reverses old journal');
        $this->assertEquals(45, $this->paymentJournalForId($salePayment->id, 'payment_updated')->lines()->sum('debit'), 'S27 sale payment update posts corrected cash journal');
        $this->assertEquals(55, $this->saleDue($saleUpdate), 'S27 sale payment update updates due');
        $this->assertEquals(55, $this->netSaleArEffect($saleUpdate), 'S27 sale AR ledger matches due after payment update');
        $this->assertEquals(0, $this->duplicateJournalGroups($salePayment), 'S27 sale payment update has no duplicate journals');

        $saleDelete = $this->createSale($this->ctx['standard'], 1, 80, 0, 'S27-SALE-PAY-DELETE');
        $this->callController(SaleController::class, 'addPayment', $this->salePaymentRequest($saleDelete, 25));
        $saleDeletePayment = Payment::where('sale_id', $saleDelete->id)->latest('id')->firstOrFail();
        $saleDeletePaymentOriginal = $this->paymentJournalFor($saleDeletePayment, 'sale_payment_created');
        $this->callController(SaleController::class, 'deletePayment', new Request(['id' => $saleDeletePayment->id]));
        $this->assertReversalMirrors($saleDeletePaymentOriginal, $this->paymentJournalForId($saleDeletePayment->id, 'sale_payment_created_deleted'), 'S27 sale payment delete reverses payment journal');
        $this->assertEquals(80, $this->saleDue($saleDelete), 'S27 sale payment delete restores due');
        $this->assertEquals(80, $this->netSaleArEffect($saleDelete), 'S27 sale AR ledger matches due after payment delete');

        $salePaid = $this->createSale($this->ctx['standard'], 2, 100, 100, 'S27-SALE-PAID-RETURN');
        $paidReturn = $this->createSaleReturnViaController($salePaid, 40, 40);
        $paidRefund = Payment::where('return_id', $paidReturn->id)->firstOrFail();
        $this->assertEquals(40, $this->paymentJournalFor($paidRefund, 'sale_refund_created')->lines()->sum('credit'), 'S27 sale paid return refund moves cash out');
        $this->assertEquals(0, $this->netSaleArEffect($salePaid), 'S27 sale paid refund clears AR liability');

        $saleDue = $this->createSale($this->ctx['standard'], 2, 100, 0, 'S27-SALE-DUE-RETURN');
        $dueReturn = $this->createSaleReturnViaController($saleDue, 35, 0);
        $this->assertFalse(Payment::where('return_id', $dueReturn->id)->exists(), 'S27 sale due return creates no cash refund');
        $this->assertEquals(65, $this->netSaleArEffect($saleDue), 'S27 sale due return only reduces AR');

        $salePartialLess = $this->createSale($this->ctx['standard'], 2, 100, 70, 'S27-SALE-PARTIAL-LESS');
        $lessReturn = $this->createSaleReturnViaController($salePartialLess, 40, 10);
        $lessRefund = Payment::where('return_id', $lessReturn->id)->firstOrFail();
        $this->assertEquals(10, $this->paymentJournalFor($lessRefund, 'sale_refund_created')->lines()->sum('credit'), 'S27 sale partial return less than paid refunds overpaid cash');
        $this->assertEquals(0, $this->netSaleArEffect($salePartialLess), 'S27 sale partial less return clears AR after refund');

        $salePartialGreater = $this->createSale($this->ctx['standard'], 2, 100, 30, 'S27-SALE-PARTIAL-GREATER');
        $greaterReturn = $this->createSaleReturnViaController($salePartialGreater, 60, 0);
        $this->assertFalse(Payment::where('return_id', $greaterReturn->id)->exists(), 'S27 sale partial return greater than paid creates no refund');
        $this->assertEquals(10, $this->netSaleArEffect($salePartialGreater), 'S27 sale partial greater return leaves remaining due in AR');

        $purchaseUpdate = $this->createPurchase($this->ctx['standard'], 1, 100, 'S27-PUR-PAY-UPDATE', 0);
        $this->callController(PurchaseController::class, 'addPayment', $this->purchasePaymentRequest($purchaseUpdate, 30));
        $purchasePayment = Payment::where('purchase_id', $purchaseUpdate->id)->latest('id')->firstOrFail();
        $purchasePaymentOriginal = $this->paymentJournalFor($purchasePayment, 'purchase_payment_created');
        $this->callController(PurchaseController::class, 'updatePayment', $this->purchasePaymentUpdateRequest($purchasePayment, 45));
        $purchasePayment->refresh();
        $this->assertReversalMirrors($purchasePaymentOriginal, $this->paymentJournalForId($purchasePayment->id, 'purchase_payment_created_reversed'), 'S27 purchase payment update reverses old journal');
        $this->assertEquals(45, $this->paymentJournalForId($purchasePayment->id, 'payment_updated')->lines()->sum('debit'), 'S27 purchase payment update posts corrected AP journal');
        $this->assertEquals(55, $this->purchaseDue($purchaseUpdate), 'S27 purchase payment update updates supplier due');
        $this->assertEquals(-55, $this->netPurchaseApEffect($purchaseUpdate), 'S27 purchase AP ledger matches due after payment update');

        $purchaseDelete = $this->createPurchase($this->ctx['standard'], 1, 80, 'S27-PUR-PAY-DELETE', 0);
        $this->callController(PurchaseController::class, 'addPayment', $this->purchasePaymentRequest($purchaseDelete, 25));
        $purchaseDeletePayment = Payment::where('purchase_id', $purchaseDelete->id)->latest('id')->firstOrFail();
        $purchaseDeletePaymentOriginal = $this->paymentJournalFor($purchaseDeletePayment, 'purchase_payment_created');
        $this->callController(PurchaseController::class, 'deletePayment', new Request(['id' => $purchaseDeletePayment->id]));
        $this->assertReversalMirrors($purchaseDeletePaymentOriginal, $this->paymentJournalForId($purchaseDeletePayment->id, 'purchase_payment_created_deleted'), 'S27 purchase payment delete reverses payment journal');
        $this->assertEquals(80, $this->purchaseDue($purchaseDelete), 'S27 purchase payment delete restores due');
        $this->assertEquals(-80, $this->netPurchaseApEffect($purchaseDelete), 'S27 purchase AP ledger matches due after payment delete');

        $purchasePaid = $this->createPurchase($this->ctx['standard'], 2, 100, 'S27-PUR-PAID-RETURN', 100);
        $purchaseRefundReturn = $this->createPurchaseReturnViaController($purchasePaid, 40, 40);
        $purchaseRefund = Payment::where('purchase_return_id', $purchaseRefundReturn->id)->firstOrFail();
        $this->assertEquals(40, $this->paymentJournalFor($purchaseRefund, 'purchase_refund_created')->lines()->sum('debit'), 'S27 purchase paid return supplier refund moves cash in');
        $this->assertEquals(0, $this->netPurchaseApEffect($purchasePaid), 'S27 purchase paid supplier refund clears AP asset balance');

        $purchaseDue = $this->createPurchase($this->ctx['standard'], 2, 100, 'S27-PUR-DUE-RETURN', 0);
        $purchaseDueReturn = $this->createPurchaseReturnViaController($purchaseDue, 35, 0);
        $this->assertFalse(Payment::where('purchase_return_id', $purchaseDueReturn->id)->exists(), 'S27 purchase due return creates no cash refund');
        $this->assertEquals(-65, $this->netPurchaseApEffect($purchaseDue), 'S27 purchase due return only reduces AP');
    }

    private function scenario28SaleExchangeAccountingAndStock(): void
    {
        $this->info('Scenario 28: sale exchange stock, due, payment, and accounting');

        $this->runExchangeCase('S28 standard to standard', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, 10, 10);
        $this->runExchangeCase('S28 standard to variant', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['variantProduct'], $this->ctx['productVariant']->item_code, 14, 10, 10, null, $this->ctx['variant']);
        $this->runExchangeCase('S28 variant to standard', $this->ctx['variantProduct'], $this->ctx['productVariant']->item_code, 14, $this->ctx['standard'], $this->ctx['standard']->code, 10, 14, 14, $this->ctx['variant']);
        $this->runExchangeCase('S28 variant to variant', $this->ctx['variantProduct'], $this->ctx['productVariant']->item_code, 14, $this->ctx['variantProduct'], $this->ctx['productVariant']->item_code, 14, 14, 14, $this->ctx['variant'], $this->ctx['variant']);
        $this->runExchangeCase('S28 combo to standard', $this->ctx['combo'], $this->ctx['combo']->code, 18, $this->ctx['standard'], $this->ctx['standard']->code, 10, 18, 18, null, null, true, false);
        $this->runExchangeCase('S28 standard to combo', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['combo'], $this->ctx['combo']->code, 18, 10, 10, null, null, false, true);
        $this->runExchangeCase('S28 partially paid sale', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, 10, 5);
        $this->runExchangeCase('S28 fully paid sale', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, 10, 10);
        $this->runExchangeCase('S28 due sale', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, 10, 0);
        $this->runExchangeCase('S28 new item cheaper', $this->ctx['standard'], $this->ctx['standard']->code, 10, $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, 10, 10);
        $this->runExchangeCase('S28 new item more expensive', $this->ctx['standardTwo'], $this->ctx['standardTwo']->code, 8, $this->ctx['standard'], $this->ctx['standard']->code, 10, 8, 8);
    }

    private function createPurchase(Product $product, float $qty, float $total, string $suffix, float $paid = 0, array $extra = []): Purchase
    {
        $ref = $this->prefix . '-' . $suffix;
        $this->refs[] = $ref;
        $unitCost = $total / $qty;
        $request = new Request(array_merge([
            'reference_no' => $ref,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'supplier_id' => $this->ctx['supplier']->id,
            'status' => 1,
            'payment_status' => $paid >= $total ? 4 : 1,
            'item' => 1,
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'order_tax' => 0,
            'order_tax_rate' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'total_cost' => $total,
            'grand_total' => $total,
            'paid_amount' => $paid,
            'amount' => [$paid],
            'paying_amount' => [$paid],
            'paid_by_id' => [1],
            'account_id' => $this->ctx['account']->id,
            'payment_note' => '',
            'cheque_no' => '',
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty],
            'recieved' => [$qty],
            'purchase_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_cost' => [$unitCost],
            'unit_cost' => [$unitCost],
            'net_unit_margin' => [0],
            'net_unit_margin_type' => [1],
            'net_unit_price' => [$product->price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'imei_number' => [''],
            'subtotal' => [$total],
        ], $extra));
        $this->callController(
            PurchaseController::class,
            'store',
            StorePurchaseRequest::createFromBase($request)
        );
        return Purchase::where('reference_no', $ref)->firstOrFail();
    }

    private function runExchangeCase(
        string $label,
        Product $returnProduct,
        string $returnCode,
        float $returnTotal,
        Product $newProduct,
        string $newCode,
        float $newTotal,
        float $saleTotal,
        float $paid,
        ?Variant $returnVariant = null,
        ?Variant $newVariant = null,
        bool $returnIsCombo = false,
        bool $newIsCombo = false
    ): void {
        $sale = $this->createSale($returnProduct, 1, $saleTotal, $paid, str_replace(' ', '-', strtoupper($label)), $this->ctx['warehouseA'], $returnCode);
        $productSale = Product_Sale::where('sale_id', $sale->id)->firstOrFail();

        $returnStockBefore = $returnIsCombo
            ? $this->stock($this->ctx['comboChild'], $this->ctx['warehouseA'])
            : $this->stock($returnProduct, $this->ctx['warehouseA'], $returnVariant);
        $newStockBefore = $newIsCombo
            ? $this->stock($this->ctx['comboChild'], $this->ctx['warehouseA'])
            : $this->stock($newProduct, $this->ctx['warehouseA'], $newVariant);

        $diff = $newTotal - $returnTotal;
        $request = $this->exchangeRequest($sale, $productSale, $returnProduct, $returnCode, $returnTotal, $newProduct, $newCode, $newTotal);
        $this->callController(ExchangeController::class, 'store', $request);

        $exchange = SaleExchange::where('sale_id', $sale->id)->latest('id')->firstOrFail();
        $exchangeJournal = $this->journalFor($exchange, 'sale_exchange_created');
        $this->assertTrue((bool) $exchangeJournal, "{$label} posts exchange journal");
        $this->assertEquals(0, $this->duplicateJournalGroups($exchange), "{$label} has no duplicate exchange journals");

        $returnExpected = $returnIsCombo ? $returnStockBefore + 2 : $returnStockBefore + 1;
        $newExpected = $newIsCombo ? $newStockBefore - 2 : $newStockBefore - 1;
        $sameSkuExchange = !$returnIsCombo
            && !$newIsCombo
            && $returnProduct->id === $newProduct->id
            && (($returnVariant?->id) === ($newVariant?->id));

        if ($sameSkuExchange) {
            $this->assertEquals($returnStockBefore, $this->stock($returnProduct, $this->ctx['warehouseA'], $returnVariant), "{$label} nets same SKU stock");
        } elseif ($returnIsCombo && $newIsCombo) {
            $this->assertEquals($returnStockBefore, $this->stock($this->ctx['comboChild'], $this->ctx['warehouseA']), "{$label} nets combo component stock");
        } else {
            $this->assertEquals($returnExpected, $returnIsCombo ? $this->stock($this->ctx['comboChild'], $this->ctx['warehouseA']) : $this->stock($returnProduct, $this->ctx['warehouseA'], $returnVariant), "{$label} restores returned stock");
            $this->assertEquals($newExpected, $newIsCombo ? $this->stock($this->ctx['comboChild'], $this->ctx['warehouseA']) : $this->stock($newProduct, $this->ctx['warehouseA'], $newVariant), "{$label} deducts new stock");
        }

        $expectedRefund = 0;
        $expectedAdditionalPayment = 0;
        $expectedPaid = $paid;
        if ($diff > 0) {
            $expectedAdditionalPayment = $diff;
            $expectedPaid += $diff;
        } elseif ($diff < 0) {
            $expectedRefund = min(abs($diff), max(0, $paid - $newTotal));
            $expectedPaid -= $expectedRefund;
        }

        $sale->refresh();
        $this->assertEquals($newTotal, $sale->grand_total, "{$label} adjusts sale grand total to net exchange sale");
        $this->assertEquals($expectedPaid, $sale->paid_amount, "{$label} records only real exchange cash movement");
        $this->assertEquals(max(0, $newTotal - $expectedPaid), $this->saleDue($sale), "{$label} adjusts customer due");
        $this->assertEquals(max(0, $newTotal - $expectedPaid), $this->netSaleArEffect($sale), "{$label} AR ledger matches exchange due");

        $cashEffect = $this->netAccountEffect($exchange, $this->roleAccountId(\App\Services\AccountingService::ROLE_CASH));
        if ($expectedAdditionalPayment > 0) {
            $this->assertEquals($expectedAdditionalPayment, $cashEffect, "{$label} records additional payment only when customer pays more");
        } elseif ($expectedRefund > 0) {
            $this->assertEquals(-$expectedRefund, $cashEffect, "{$label} records refund only when money is paid back");
        } else {
            $this->assertEquals(0, $cashEffect, "{$label} has no cash movement without real payment/refund");
        }

        $this->assertTrue(ProductExchange::where('exchange_id', $exchange->id)->where('type', 'returned')->exists(), "{$label} separates returned exchange portion");
        $this->assertTrue(ProductExchange::where('exchange_id', $exchange->id)->where('type', 'new')->exists(), "{$label} separates new exchange portion");
    }

    private function exchangeRequest(
        Sale $sale,
        Product_Sale $productSale,
        Product $returnProduct,
        string $returnCode,
        float $returnTotal,
        Product $newProduct,
        string $newCode,
        float $newTotal
    ): Request {
        $diff = $newTotal - $returnTotal;

        return new Request([
            'sale_id' => $sale->id,
            'customer_id' => $this->ctx['customer']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'biller_id' => $this->ctx['biller']->id,
            'item' => 2,
            'total_qty' => 2,
            'total_sale_discount' => 0,
            'total_discount' => 0,
            'total_tax' => 0,
            'amount' => abs($diff),
            'payment_type' => $diff > 0 ? 'receive' : ($diff < 0 ? 'pay' : null),
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $newTotal,
            'exchange_note' => 'Scenario exchange',
            'staff_note' => '',
            'type' => ['return', 'new'],
            'is_exchange' => [$returnCode],
            'product_sale_id' => [$productSale->id, null],
            'product_id' => [$returnProduct->id, $newProduct->id],
            'product_code' => [$returnCode, $newCode],
            'product_batch_id' => [null, null],
            'imei_number' => ['', ''],
            'qty' => [1, 1],
            'sale_unit' => [$this->ctx['unit']->unit_name, $this->ctx['unit']->unit_name],
            'net_unit_price' => [$returnTotal, $newTotal],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [$returnTotal, $newTotal],
        ]);
    }

    private function createSale(Product $product, float $qty, float $total, float $paid, string $suffix, ?Warehouse $warehouse = null, ?string $code = null, ?int $batchId = null, string $imei = ''): Sale
    {
        $ref = $this->prefix . '-' . $suffix;
        $this->refs[] = $ref;
        $request = $this->saleRequest($product, $qty, $total, $paid, $ref, $warehouse, $code, $batchId, $imei);
        $this->callController(SaleController::class, 'store', $request);
        return Sale::where('reference_no', $ref)->firstOrFail();
    }

    private function saleRequest(Product $product, float $qty, float $total, float $paid, string $ref, ?Warehouse $warehouse = null, ?string $code = null, ?int $batchId = null, string $imei = ''): StoreSaleRequest
    {
        $warehouse = $warehouse ?: $this->ctx['warehouseA'];
        $request = new StoreSaleRequest();
        $request->replace([
            'reference_no' => $ref,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $this->ctx['customer']->id,
            'biller_id' => $this->ctx['biller']->id,
            'item' => 1,
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'order_tax' => 0,
            'order_tax_rate' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'total_price' => $total,
            'grand_total' => $total,
            'sale_status' => 1,
            'payment_status' => $paid >= $total ? 4 : ($paid > 0 ? 2 : 1),
            'paid_amount' => [$paid],
            'paying_amount' => [$paid],
            'paid_by_id' => [1],
            'account_id' => $this->ctx['account']->id,
            'payment_note' => '',
            'product_id' => [$product->id],
            'product_code' => [$code ?: $product->code],
            'qty' => [$qty],
            'sale_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_price' => [$total / $qty],
            'unit_price' => [$total / $qty],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'imei_number' => [$imei],
            'subtotal' => [$total],
            'coupon_active' => 0,
            'draft' => 0,
            'pos' => 0,
            'product_batch_id' => [$batchId],
        ]);
        return $request;
    }

    private function purchaseUpdateRequest(Product $product, float $qty, float $total, string $ref, float $paid = 0): UpdatePurchaseRequest
    {
        $unitCost = $total / $qty;
        $request = new UpdatePurchaseRequest();
        $request->replace([
            'reference_no' => $ref,
            'created_at' => now()->toDateString(),
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'supplier_id' => $this->ctx['supplier']->id,
            'status' => 1,
            'payment_status' => $paid >= $total ? 4 : 1,
            'item' => 1,
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'order_tax' => 0,
            'order_tax_rate' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'total_cost' => $total,
            'grand_total' => $total,
            'paid_amount' => $paid,
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty],
            'recieved' => [$qty],
            'purchase_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_cost' => [$unitCost],
            'unit_cost' => [$unitCost],
            'net_unit_margin' => [0],
            'net_unit_margin_type' => [1],
            'net_unit_price' => [$product->price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'batch_no' => [''],
            'expired_date' => [''],
            'imei_number' => [''],
            'subtotal' => [$total],
        ]);

        return $request;
    }

    private function transferRequest(Product $product, float $qty): Request
    {
        return new Request([
            'from_warehouse_id' => $this->ctx['warehouseA']->id,
            'to_warehouse_id' => $this->ctx['warehouseB']->id,
            'status' => 1,
            'item' => 1,
            'total_qty' => $qty,
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty],
            'purchase_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_cost' => [$product->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$qty * $product->cost],
            'total_tax' => 0,
            'total_cost' => $qty * $product->cost,
            'shipping_cost' => 0,
            'grand_total' => $qty * $product->cost,
            'imei_number' => [''],
            'product_batch_id' => [null],
            'product_variant_id' => [null],
        ]);
    }

    private function salePaymentRequest(Sale $sale, float $amount): Request
    {
        return new Request([
            'sale_id' => $sale->id,
            'amount' => $amount,
            'paying_amount' => $amount,
            'paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'payment_note' => 'S27 sale payment',
            'payment_receiver' => 'Scenario',
        ]);
    }

    private function salePaymentUpdateRequest(Payment $payment, float $amount): Request
    {
        return new Request([
            'payment_id' => $payment->id,
            'edit_amount' => $amount,
            'edit_paying_amount' => $amount,
            'edit_paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'edit_payment_note' => 'S27 sale payment update',
            'payment_receiver' => 'Scenario',
        ]);
    }

    private function purchasePaymentRequest(Purchase $purchase, float $amount): Request
    {
        return new Request([
            'purchase_id' => $purchase->id,
            'amount' => $amount,
            'paying_amount' => $amount,
            'paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'payment_note' => 'S27 purchase payment',
            'cheque_no' => '',
        ]);
    }

    private function purchasePaymentUpdateRequest(Payment $payment, float $amount): Request
    {
        return new Request([
            'payment_id' => $payment->id,
            'edit_amount' => $amount,
            'edit_paying_amount' => $amount,
            'edit_paid_by_id' => 1,
            'account_id' => $this->ctx['account']->id,
            'edit_payment_note' => 'S27 purchase payment update',
            'edit_cheque_no' => '',
        ]);
    }

    private function createSaleReturnViaController(Sale $sale, float $amount, float $refundAmount): Returns
    {
        $productSale = Product_Sale::where('sale_id', $sale->id)->firstOrFail();
        $request = new Request([
            'sale_id' => $sale->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 1,
            'total_qty' => 1,
            'total_sale_discount' => 0,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $amount,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $amount,
            'return_note' => 'S27 sale return',
            'staff_note' => '',
            'change_sale_status' => 0,
            'refund' => $refundAmount > 0 ? 1 : 0,
            'refund_amount' => $refundAmount,
            'paying_method' => 'Cash',
            'product_sale_id' => [$productSale->id],
            'product_id' => [$this->ctx['standard']->id],
            'product_code' => [$this->ctx['standard']->code],
            'qty' => [1],
            'sale_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_price' => [$amount],
            'product_price' => [$amount],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$amount],
            'imei_number' => [''],
            'product_batch_id' => [null],
        ]);

        $this->callController(ReturnController::class, 'store', $request);
        return Returns::where('sale_id', $sale->id)->latest('id')->firstOrFail();
    }

    private function createPurchaseReturnViaController(Purchase $purchase, float $amount, float $refundAmount): ReturnPurchase
    {
        $productPurchase = ProductPurchase::where('purchase_id', $purchase->id)->firstOrFail();
        $request = new Request([
            'purchase_id' => $purchase->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $amount,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $amount,
            'return_note' => 'S27 purchase return',
            'staff_note' => '',
            'refund_amount' => $refundAmount,
            'paying_method' => 'Cash',
            'is_return' => [$productPurchase->id],
            'product_purchase_id' => [$productPurchase->id],
            'product_id' => [$this->ctx['standard']->id],
            'product_code' => [$this->ctx['standard']->code],
            'qty' => [1],
            'purchase_unit' => [$this->ctx['unit']->unit_name],
            'net_unit_cost' => [$amount],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$amount],
            'imei_number' => [''],
            'product_batch_id' => [null],
        ]);

        $this->callController(ReturnPurchaseController::class, 'store', $request);
        return ReturnPurchase::where('purchase_id', $purchase->id)->latest('id')->firstOrFail();
    }

    private function adjustmentRequest(string $action, float $qty): Request
    {
        return new Request([
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'item' => 1,
            'total_qty' => $qty,
            'product_id' => [$this->ctx['standard']->id],
            'product_code' => [$this->ctx['standard']->code],
            'qty' => [$qty],
            'unit_cost' => [$this->ctx['standard']->cost],
            'action' => [$action],
        ]);
    }

    private function createDamageStock(float $qty): DamageStock
    {
        $damage = DamageStock::create([
            'reference_no' => $this->prefix . '-S26-DAMAGE',
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'damaged_at' => now()->toDateString(),
            'user_id' => Auth::id(),
            'note' => 'S26 damage',
        ]);

        ProductDamageStock::create([
            'damage_stock_id' => $damage->id,
            'product_id' => $this->ctx['standard']->id,
            'qty' => $qty,
            'unit_cost' => $this->ctx['standard']->cost,
        ]);

        $this->ctx['standard']->decrement('qty', $qty);
        Product_Warehouse::where('product_id', $this->ctx['standard']->id)
            ->where('warehouse_id', $this->ctx['warehouseA']->id)
            ->whereNull('variant_id')
            ->decrement('qty', $qty);

        return $damage;
    }

    private function createPostedSaleReturn(): Returns
    {
        $sale = $this->createSale($this->ctx['standard'], 2, 20, 0, 'S26-RET-SALE');
        $return = Returns::create([
            'reference_no' => $this->prefix . '-S26-SALE-RETURN',
            'user_id' => Auth::id(),
            'sale_id' => $sale->id,
            'customer_id' => $this->ctx['customer']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'biller_id' => $this->ctx['biller']->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 10,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 10,
            'return_note' => 'S26 sale return',
        ]);

        ProductReturn::create([
            'return_id' => $return->id,
            'product_id' => $this->ctx['standard']->id,
            'sale_unit_id' => $this->ctx['unit']->id,
            'qty' => 1,
            'net_unit_price' => 10,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 10,
        ]);

        Product_Sale::where('sale_id', $sale->id)
            ->where('product_id', $this->ctx['standard']->id)
            ->increment('return_qty', 1);
        $this->ctx['standard']->increment('qty', 1);
        Product_Warehouse::where('product_id', $this->ctx['standard']->id)
            ->where('warehouse_id', $this->ctx['warehouseA']->id)
            ->whereNull('variant_id')
            ->increment('qty', 1);

        $result = app(\App\Services\AccountingService::class)->recordSaleReturn($return, 'sale_return_created');
        if (!$result->success) {
            throw new \RuntimeException('S26 sale return accounting failed: ' . $result->error);
        }

        return $return;
    }

    private function createPostedPurchaseReturn(): ReturnPurchase
    {
        $purchase = $this->createPurchase($this->ctx['standard'], 2, 10, 'S26-RET-PUR', 0);
        $return = ReturnPurchase::create([
            'reference_no' => $this->prefix . '-S26-PURCHASE-RETURN',
            'purchase_id' => $purchase->id,
            'user_id' => Auth::id(),
            'supplier_id' => $this->ctx['supplier']->id,
            'warehouse_id' => $this->ctx['warehouseA']->id,
            'account_id' => $this->ctx['account']->id,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 5,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 5,
            'return_note' => 'S26 purchase return',
        ]);

        PurchaseProductReturn::create([
            'return_id' => $return->id,
            'product_id' => $this->ctx['standard']->id,
            'purchase_unit_id' => $this->ctx['unit']->id,
            'qty' => 1,
            'net_unit_cost' => 5,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 5,
        ]);

        $this->ctx['standard']->decrement('qty', 1);
        Product_Warehouse::where('product_id', $this->ctx['standard']->id)
            ->where('warehouse_id', $this->ctx['warehouseA']->id)
            ->whereNull('variant_id')
            ->decrement('qty', 1);

        $result = app(\App\Services\AccountingService::class)->recordPurchaseReturn($return, 'purchase_return_created');
        if (!$result->success) {
            throw new \RuntimeException('S26 purchase return accounting failed: ' . $result->error);
        }

        return $return;
    }

    private function callController(string $controllerClass, string $method, ?Request $request = null, array $args = []): mixed
    {
        session()->forget(['message', 'not_permitted', 'errors']);
        $response = $request
            ? app($controllerClass)->{$method}($request, ...$args)
            : app($controllerClass)->{$method}(...$args);
        if ($response instanceof RedirectResponse) {
            $notPermitted = session('not_permitted');
            $errors = session('errors');
            if ($notPermitted && !str_contains(strtolower($notPermitted), 'success')) {
                throw new \RuntimeException($controllerClass . '::' . $method . ' failed: ' . $notPermitted);
            }
            if ($errors) {
                throw new \RuntimeException($controllerClass . '::' . $method . ' validation failed: ' . json_encode($errors->messages()));
            }
        }
        return $response;
    }

    private function stock(Product $product, Warehouse $warehouse, ?Variant $variant = null): float
    {
        $query = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id);
        $variant ? $query->where('variant_id', $variant->id) : $query->whereNull('variant_id');
        return (float) ($query->first()->qty ?? 0);
    }

    private function assertEquals($expected, $actual, string $label): void
    {
        if (is_array($expected) || is_array($actual)) {
            if ($expected !== $actual) {
                throw new \RuntimeException($label . ' expected ' . json_encode($expected) . ', got ' . json_encode($actual));
            }
            $this->line('  PASS ' . $label);
            return;
        }

        if (abs((float) $expected - (float) $actual) > 0.01) {
            throw new \RuntimeException($label . " expected {$expected}, got {$actual}");
        }
        $this->line('  PASS ' . $label);
    }

    private function assertTrue(bool $condition, string $label): void
    {
        if (!$condition) {
            throw new \RuntimeException($label);
        }
        $this->line('  PASS ' . $label);
    }

    private function assertFalse(bool $condition, string $label): void
    {
        $this->assertTrue(!$condition, $label);
    }

    private function journalFor($source, string $eventType): JournalEntry
    {
        return JournalEntry::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->where('event_type', $eventType)
            ->with('lines')
            ->firstOrFail();
    }

    private function paymentJournalFor(Payment $payment, string $eventType): JournalEntry
    {
        return $this->paymentJournalForId($payment->id, $eventType);
    }

    private function paymentJournalForId(int $paymentId, string $eventType): JournalEntry
    {
        return JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $paymentId)
            ->where('event_type', $eventType)
            ->with('lines')
            ->firstOrFail();
    }

    private function journalLineSignature(JournalEntry $entry): array
    {
        return $entry->lines
            ->map(fn ($line) => [
                'account' => (int) $line->accounting_account_id,
                'debit' => number_format((float) $line->debit, 4, '.', ''),
                'credit' => number_format((float) $line->credit, 4, '.', ''),
            ])
            ->sortBy(fn ($line) => $line['account'] . '|' . $line['debit'] . '|' . $line['credit'])
            ->values()
            ->all();
    }

    private function assertReversalMirrors(JournalEntry $original, JournalEntry $reversal, string $label): void
    {
        $expected = $original->fresh('lines')->lines
            ->map(fn ($line) => [
                'account' => (int) $line->accounting_account_id,
                'debit' => number_format((float) $line->credit, 4, '.', ''),
                'credit' => number_format((float) $line->debit, 4, '.', ''),
            ])
            ->sortBy(fn ($line) => $line['account'] . '|' . $line['debit'] . '|' . $line['credit'])
            ->values()
            ->all();

        $this->assertEquals($expected, $this->journalLineSignature($reversal->fresh('lines')), $label);
    }

    private function duplicateJournalGroups($source): int
    {
        return DB::query()
            ->fromSub(
                JournalEntry::select('source_type', 'source_id', 'event_type', DB::raw('COUNT(*) as duplicate_count'))
                    ->where('source_type', get_class($source))
                    ->where('source_id', $source->id)
                    ->groupBy('source_type', 'source_id', 'event_type')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicate_journals'
            )
            ->count();
    }

    private function netAccountEffect($source, int $accountId): float
    {
        return (float) JournalEntry::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.accounting_account_id', $accountId)
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as net_amount')
            ->value('net_amount');
    }

    private function netSaleArEffect(Sale $sale): float
    {
        $entryIds = JournalEntry::where(function ($query) use ($sale) {
            $query->where('source_type', Sale::class)
                ->where('source_id', $sale->id);
        })
            ->orWhere(function ($query) use ($sale) {
                $query->where('source_type', Returns::class)
                    ->whereIn('source_id', Returns::where('sale_id', $sale->id)->pluck('id'));
            })
            ->orWhere(function ($query) use ($sale) {
                $query->where('source_type', Payment::class)
                    ->whereIn('source_id', Payment::where('sale_id', $sale->id)->pluck('id'));
            })
            ->orWhere(function ($query) use ($sale) {
                $query->where('source_type', SaleExchange::class)
                    ->whereIn('source_id', SaleExchange::where('sale_id', $sale->id)->pluck('id'));
            })
            ->pluck('id');

        return $this->netAccountEffectForEntries($entryIds, $this->roleAccountId(\App\Services\AccountingService::ROLE_ACCOUNTS_RECEIVABLE));
    }

    private function netPurchaseApEffect(Purchase $purchase): float
    {
        $entryIds = JournalEntry::where(function ($query) use ($purchase) {
            $query->where('source_type', Purchase::class)
                ->where('source_id', $purchase->id);
        })
            ->orWhere(function ($query) use ($purchase) {
                $query->where('source_type', ReturnPurchase::class)
                    ->whereIn('source_id', ReturnPurchase::where('purchase_id', $purchase->id)->pluck('id'));
            })
            ->orWhere(function ($query) use ($purchase) {
                $query->where('source_type', Payment::class)
                    ->whereIn('source_id', Payment::where('purchase_id', $purchase->id)->pluck('id'));
            })
            ->pluck('id');

        return $this->netAccountEffectForEntries($entryIds, $this->roleAccountId(\App\Services\AccountingService::ROLE_ACCOUNTS_PAYABLE));
    }

    private function netAccountEffectForEntries($entryIds, int $accountId): float
    {
        return (float) DB::table('journal_lines')
            ->whereIn('journal_entry_id', $entryIds)
            ->where('accounting_account_id', $accountId)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net_amount')
            ->value('net_amount');
    }

    private function saleDue(Sale $sale): float
    {
        $sale->refresh();
        $returned = Returns::where('sale_id', $sale->id)->sum('grand_total');
        return (float) max(0, $sale->grand_total - $returned - $sale->paid_amount);
    }

    private function purchaseDue(Purchase $purchase): float
    {
        $purchase->refresh();
        $returned = ReturnPurchase::where('purchase_id', $purchase->id)->sum('grand_total');
        return (float) max(0, $purchase->grand_total - $returned - $purchase->paid_amount);
    }

    private function accountId(string $code): int
    {
        return AccountingAccount::where('code', $code)->firstOrFail()->id;
    }

    private function roleAccountId(string $role): int
    {
        return app(\App\Services\AccountingService::class)->getRoleAccountId($role);
    }

    private function ensureAccountingAccounts(): void
    {
        $accounts = [
            '1100' => ['Accounts Receivable', 'asset'],
            '1200' => ['Inventory', 'asset'],
            '1250' => ['Input VAT', 'asset'],
            '1300' => ['Cash', 'asset'],
            '2100' => ['Accounts Payable', 'liability'],
            '4100' => ['Revenue', 'revenue'],
            '4150' => ['Sales Returns', 'revenue'],
            '5150' => ['Purchase Returns', 'expense'],
            '5200' => ['Freight In', 'expense'],
            '5300' => ['Purchase Discount', 'expense'],
        ];

        foreach ($accounts as $code => [$name, $type]) {
            AccountingAccount::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $this->prefix . '-' . $name,
                    'account_type' => $type,
                    'is_control_account' => true,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
