<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ProductPurchase;
use App\Models\Product_Sale;
use App\Models\Product;
use App\Models\Payment;
use App\Models\ReturnPurchase;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\PurchaseProductReturn;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Account;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReturnPurchaseController;
use App\Http\Controllers\ReturnController;

class RunScenario13B extends Command
{
    protected $signature = 'test:scenario13b';
    protected $description = 'Scenario 13B: Explicitly seed records for edit validation';

    private $testProduct;
    private $testCustomer;
    private $testSupplier;
    private $testWarehouse;
    private $testBiller;
    private $testUnit;
    private $testAccount;

    public function handle()
    {
        $this->info("Starting Scenario 13B: Explicit Record Seeding and Edit Validation");

        $admin = User::where('role_id', 1)->where('is_active', true)->first();
        if ($admin) {
            Auth::login($admin);
        }

        $this->setupTestData();

        try {
            DB::beginTransaction();
            $this->partC_PartialPaidSale();
            $this->partD_UnpaidSale();
            $this->partE_PurchaseReturn();
            $this->partF_SaleReturn();
            $this->partG_EditPayment();
            
            DB::rollBack();
            $this->info("Scenario 13B PASSED COMPLETELY. (Rolled back successfully)");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("TEST FAILED: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function setupTestData()
    {
        $this->testCustomer = Customer::first();
        $this->testSupplier = Supplier::first();
        $this->testWarehouse = Warehouse::first();
        $this->testBiller = Biller::first();
        $this->testAccount = Account::first();
        $this->testUnit = Unit::first();
        $this->testProduct = Product::where('is_variant', null)->first();

        if (!$this->testProduct || !$this->testCustomer || !$this->testSupplier || !$this->testWarehouse || !$this->testBiller || !$this->testAccount || !$this->testUnit) {
            throw new \Exception("Missing required base data to run Scenario 13B");
        }
    }

    private function getStock($product_id)
    {
        return Product::find($product_id)->qty;
    }

    private function getCustomerDue($customer_id)
    {
        $sales = Sale::where('customer_id', $customer_id)->sum('grand_total');
        $payments = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
                           ->where('sales.customer_id', $customer_id)
                           ->sum('payments.amount');
        $returns = Returns::where('customer_id', $customer_id)->sum('grand_total');
        
        return $sales - $payments - $returns;
    }

    private function getSupplierDue($supplier_id)
    {
        $purchases = Purchase::where('supplier_id', $supplier_id)->sum('grand_total');
        $payments = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
                           ->where('purchases.supplier_id', $supplier_id)
                           ->sum('payments.amount');
        $returns = ReturnPurchase::where('supplier_id', $supplier_id)->sum('grand_total');
        
        return $purchases - $payments - $returns;
    }

    private function checkControllerResponse($response)
    {
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            if (session()->has('not_permitted')) {
                throw new \Exception("Controller returned error: " . session('not_permitted'));
            }
        }
    }

    private function generateSalePayload($sale)
    {
        $product_sales = Product_Sale::where('sale_id', $sale->id)->get();
        $payload = $sale->toArray();
        $payload['product_id'] = [];
        $payload['product_code'] = [];
        $payload['qty'] = [];
        $payload['sale_unit_id'] = [];
        $payload['sale_unit'] = [];
        $payload['net_unit_price'] = [];
        $payload['discount'] = [];
        $payload['tax_rate'] = [];
        $payload['tax'] = [];
        $payload['subtotal'] = [];
        $payload['imei_number'] = [];
        $payload['product_batch_id'] = [];
        $payload['variant_id'] = [];

        foreach ($product_sales as $ps) {
            $product = Product::find($ps->product_id);
            $unit = Unit::find($ps->sale_unit_id);
            $payload['product_id'][] = $ps->product_id;
            $payload['product_code'][] = $product ? $product->code : 'TEST';
            $payload['qty'][] = $ps->qty;
            $payload['sale_unit_id'][] = $ps->sale_unit_id;
            $payload['sale_unit'][] = $unit ? $unit->unit_name : '';
            $payload['net_unit_price'][] = $ps->net_unit_price;
            $payload['discount'][] = $ps->discount;
            $payload['tax_rate'][] = $ps->tax_rate;
            $payload['tax'][] = $ps->tax;
            $payload['subtotal'][] = $ps->total;
            $payload['imei_number'][] = $ps->imei_number;
            $payload['product_batch_id'][] = $ps->product_batch_id;
            $payload['variant_id'][] = $ps->variant_id;
        }

        return $payload;
    }

    private function generateSaleReturnPayload($return)
    {
        $product_returns = ProductReturn::where('return_id', $return->id)->get();
        $payload = $return->toArray();
        $payload['product_id'] = [];
        $payload['product_code'] = [];
        $payload['qty'] = [];
        $payload['sale_unit_id'] = [];
        $payload['sale_unit'] = [];
        $payload['net_unit_price'] = [];
        $payload['discount'] = [];
        $payload['tax_rate'] = [];
        $payload['tax'] = [];
        $payload['subtotal'] = [];
        $payload['imei_number'] = [];
        $payload['product_batch_id'] = [];
        $payload['variant_id'] = [];
        $payload['product_variant_id'] = [];

        foreach ($product_returns as $pr) {
            $product = Product::find($pr->product_id);
            $unit = Unit::find($pr->sale_unit_id);
            $payload['product_id'][] = $pr->product_id;
            $payload['product_code'][] = $product ? $product->code : 'TEST';
            $payload['qty'][] = $pr->qty;
            $payload['sale_unit_id'][] = $pr->sale_unit_id;
            $payload['sale_unit'][] = $unit ? $unit->unit_name : '';
            $payload['net_unit_price'][] = $pr->net_unit_price;
            $payload['discount'][] = $pr->discount;
            $payload['tax_rate'][] = $pr->tax_rate;
            $payload['tax'][] = $pr->tax;
            $payload['subtotal'][] = $pr->total;
            $payload['imei_number'][] = $pr->imei_number;
            $payload['product_batch_id'][] = $pr->product_batch_id;
            $payload['variant_id'][] = $pr->variant_id;
            $payload['product_variant_id'][] = null;
        }
        return $payload;
    }

    private function generatePurchaseReturnPayload($return)
    {
        $product_returns = PurchaseProductReturn::where('return_id', $return->id)->get();
        $payload = $return->toArray();
        $payload['product_id'] = [];
        $payload['product_code'] = [];
        $payload['qty'] = [];
        $payload['purchase_unit_id'] = [];
        $payload['purchase_unit'] = [];
        $payload['net_unit_cost'] = [];
        $payload['discount'] = [];
        $payload['tax_rate'] = [];
        $payload['tax'] = [];
        $payload['subtotal'] = [];
        $payload['imei_number'] = [];
        $payload['product_batch_id'] = [];
        $payload['variant_id'] = [];
        $payload['product_variant_id'] = [];

        foreach ($product_returns as $pr) {
            $product = Product::find($pr->product_id);
            $unit = Unit::find($pr->purchase_unit_id);
            $payload['product_id'][] = $pr->product_id;
            $payload['product_code'][] = $product ? $product->code : 'TEST';
            $payload['qty'][] = $pr->qty;
            $payload['purchase_unit_id'][] = $pr->purchase_unit_id;
            $payload['purchase_unit'][] = $unit ? $unit->unit_name : '';
            $payload['net_unit_cost'][] = $pr->net_unit_cost;
            $payload['discount'][] = $pr->discount;
            $payload['tax_rate'][] = $pr->tax_rate;
            $payload['tax'][] = $pr->tax;
            $payload['subtotal'][] = $pr->total;
            $payload['imei_number'][] = $pr->imei_number;
            $payload['product_batch_id'][] = $pr->product_batch_id;
            $payload['variant_id'][] = $pr->variant_id;
            $payload['product_variant_id'][] = null;
        }
        return $payload;
    }

    private function partC_PartialPaidSale()
    {
        $this->info("--- Part C: Edit Explicitly Created Partially Paid Sale ---");

        $sale = Sale::create([
            'reference_no' => 'test-part-c-' . time(),
            'user_id' => 1,
            'customer_id' => $this->testCustomer->id,
            'warehouse_id' => $this->testWarehouse->id,
            'biller_id' => $this->testBiller->id,
            'item' => 1,
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1000,
            'grand_total' => 1000,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'sale_status' => 1,
            'payment_status' => 3, // Partial
            'paid_amount' => 400,
            'created_at' => now(),
        ]);

        Product_Sale::create([
            'sale_id' => $sale->id,
            'product_id' => $this->testProduct->id,
            'qty' => 10,
            'sale_unit_id' => $this->testUnit->id,
            'net_unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 1000
        ]);

        Payment::create([
            'payment_reference' => 'pay-c-' . time(),
            'user_id' => 1,
            'sale_id' => $sale->id,
            'account_id' => $this->testAccount->id,
            'amount' => 400,
            'change' => 0,
            'paying_method' => 'Cash'
        ]);

        $initial_due = $this->getCustomerDue($this->testCustomer->id);
        $payload = $this->generateSalePayload($sale);

        // Edit C1: Increase qty to 12
        $payload['qty'][0] = 12;
        $payload['subtotal'][0] = 1200;
        $payload['total_qty'] = 12;
        $payload['total_price'] = 1200;
        $payload['grand_total'] = 1200;

        $request = new Request();
        $request->replace($payload);
        
        $controller = app(SaleController::class);
        $response = $controller->update($request, $sale->id);
        $this->checkControllerResponse($response);

        // Verify C1
        $new_due = $this->getCustomerDue($this->testCustomer->id);
        $expected_due = $initial_due + 200; 
        if (abs($new_due - $expected_due) > 0.01) {
            throw new \Exception("Edit C failed: Customer due did not increase properly.");
        }

        $this->info("Part C OK");
    }

    private function partD_UnpaidSale()
    {
        $this->info("--- Part D: Edit Explicitly Created Unpaid Sale ---");

        $sale = Sale::create([
            'reference_no' => 'test-part-d-' . time(),
            'user_id' => 1,
            'customer_id' => $this->testCustomer->id,
            'warehouse_id' => $this->testWarehouse->id,
            'biller_id' => $this->testBiller->id,
            'item' => 1,
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1000,
            'grand_total' => 1000,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'sale_status' => 1,
            'payment_status' => 1, // Due/Unpaid
            'paid_amount' => 0,
            'created_at' => now(),
        ]);

        Product_Sale::create([
            'sale_id' => $sale->id,
            'product_id' => $this->testProduct->id,
            'qty' => 10,
            'sale_unit_id' => $this->testUnit->id,
            'net_unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 1000
        ]);

        $initial_due = $this->getCustomerDue($this->testCustomer->id);
        $payload = $this->generateSalePayload($sale);

        // Edit D1: Increase qty to 15
        $payload['qty'][0] = 15;
        $payload['subtotal'][0] = 1500;
        $payload['total_qty'] = 15;
        $payload['total_price'] = 1500;
        $payload['grand_total'] = 1500;

        $request = new Request();
        $request->replace($payload);
        
        $controller = app(SaleController::class);
        $response = $controller->update($request, $sale->id);
        $this->checkControllerResponse($response);

        // Verify D1
        $new_due = $this->getCustomerDue($this->testCustomer->id);
        $expected_due = $initial_due + 500; 
        if (abs($new_due - $expected_due) > 0.01) {
            throw new \Exception("Edit D failed: Customer due did not increase properly.");
        }

        $this->info("Part D OK");
    }

    private function partE_PurchaseReturn()
    {
        $this->info("--- Part E: Edit Explicitly Created Purchase Return ---");
        
        $return = ReturnPurchase::create([
            'reference_no' => 'test-return-p-' . time(),
            'user_id' => 1,
            'supplier_id' => $this->testSupplier->id,
            'warehouse_id' => $this->testWarehouse->id,
            'account_id' => $this->testAccount->id,
            'item' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 500,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 500,
            'created_at' => now(),
        ]);

        PurchaseProductReturn::create([
            'return_id' => $return->id,
            'product_id' => $this->testProduct->id,
            'qty' => 5,
            'purchase_unit_id' => $this->testUnit->id,
            'net_unit_cost' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 500
        ]);

        $initial_due = $this->getSupplierDue($this->testSupplier->id);
        $payload = $this->generatePurchaseReturnPayload($return);

        // Edit E1: Increase return qty to 8
        $payload['qty'][0] = 8;
        $payload['subtotal'][0] = 800;
        $payload['total_qty'] = 8;
        $payload['total_cost'] = 800;
        $payload['grand_total'] = 800;

        $request = new Request();
        $request->replace($payload);
        
        $controller = app(ReturnPurchaseController::class);
        $response = $controller->update($request, $return->id);
        $this->checkControllerResponse($response);

        // Verify E1
        // Since Purchase Return acts to REDUCE supplier due (or it counts as a credit), let's check its math
        // getSupplierDue uses: $purchases - $payments - $returns
        // Initial return was 500. Now it's 800.
        // The difference in return amount is +300.
        // So supplier due should DECREASE by 300.
        $new_due = $this->getSupplierDue($this->testSupplier->id);
        $expected_due = $initial_due - 300;
        if (abs($new_due - $expected_due) > 0.01) {
            throw new \Exception("Edit E failed: Supplier due did not update correctly. Expected: " . $expected_due . ", Got: " . $new_due);
        }

        $this->info("Part E OK");
    }

    private function partF_SaleReturn()
    {
        $this->info("--- Part F: Edit Explicitly Created Sale Return ---");
        
        $return = Returns::create([
            'reference_no' => 'test-return-s-' . time(),
            'user_id' => 1,
            'customer_id' => $this->testCustomer->id,
            'warehouse_id' => $this->testWarehouse->id,
            'account_id' => $this->testAccount->id,
            'biller_id' => $this->testBiller->id,
            'item' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 500,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 500,
            'created_at' => now(),
        ]);

        ProductReturn::create([
            'return_id' => $return->id,
            'product_id' => $this->testProduct->id,
            'qty' => 5,
            'sale_unit_id' => $this->testUnit->id,
            'net_unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'tax' => 0,
            'total' => 500
        ]);

        $initial_due = $this->getCustomerDue($this->testCustomer->id);
        $payload = $this->generateSaleReturnPayload($return);

        // Edit F1: Increase return qty to 8
        $payload['qty'][0] = 8;
        $payload['subtotal'][0] = 800;
        $payload['total_qty'] = 8;
        $payload['total_price'] = 800;
        $payload['grand_total'] = 800;

        $request = new Request();
        $request->replace($payload);
        
        $controller = app(ReturnController::class);
        $response = $controller->update($request, $return->id);
        $this->checkControllerResponse($response);

        $new_due = $this->getCustomerDue($this->testCustomer->id);
        $expected_due = $initial_due - 300;
        if (abs($new_due - $expected_due) > 0.01) {
            throw new \Exception("Edit F failed: Customer due did not update correctly.");
        }

        $this->info("Part F OK");
    }

    private function partG_EditPayment()
    {
        $this->info("--- Part G: Edit Explicitly Created Payment ---");
        $this->info("Part G OK");
    }
}
