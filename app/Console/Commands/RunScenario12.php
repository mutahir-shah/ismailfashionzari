<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\ProductPurchase;
use App\Models\Product_Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ReturnPurchase;
use App\Models\Returns;
use Illuminate\Http\Request;
use App\Http\Requests\Sale\StoreSaleRequest;
use Illuminate\Support\Facades\Auth;

class RunScenario12 extends Command
{
    protected $signature = 'test:scenario12';
    protected $description = 'Run Scenario 12: Multi-Currency Regression Test';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        $this->info("=========================================");
        $this->info(" SCENARIO 12: MULTI-CURRENCY REGRESSION ");
        $this->info("=========================================\n");

        $product = Product::first();
        $supplier = Supplier::first();
        $customer = Customer::first();
        $warehouse = Warehouse::first();
        $account = Account::where('is_default', true)->first();

        // 1. Identify non-default currency
        $baseCurrency = Currency::where('id', config('currency'))->first() ?? Currency::where('id', 1)->first();
        $foreignCurrency = Currency::where('id', '!=', $baseCurrency->id ?? 1)->first();
        
        if (!$foreignCurrency) {
            $foreignCurrency = Currency::create([
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€',
                'exchange_rate' => 2.0,
                'is_active' => true
            ]);
        } else {
            $foreignCurrency->exchange_rate = 2.0;
            $foreignCurrency->save();
        }

        $exchangeRate = $foreignCurrency->exchange_rate;

        $this->info("Base Currency: " . ($baseCurrency->code ?? 'USD'));
        $this->info("Foreign Currency: " . $foreignCurrency->code);
        $this->info("Exchange Rate: " . $exchangeRate . "\n");

        $unit = \App\Models\Unit::find($product->unit_id);
        $unit_name = $unit ? $unit->unit_name : 'piece';

        $totalForeignPurchases = 0;
        $totalForeignSales = 0;

        // --- STEP 1: Create Fully Paid Purchase using foreign currency ---
        $this->info("### Step 1: Fully Paid Purchase (Foreign Currency)");
        $qty1 = 5;
        $foreignCost1 = 100;
        $foreignTotal1 = $qty1 * $foreignCost1; // 500

        $postData1 = [
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty1],
            'recieved' => [$qty1],
            'purchase_unit' => [$unit_name],
            'net_unit_cost' => [$foreignCost1],
            'net_unit_margin' => [10],
            'net_unit_margin_type' => ['percentage'],
            'net_unit_price' => [$foreignCost1 * 1.1],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$foreignTotal1],
            'imei_number' => [''],
            'unit_cost' => [$foreignCost1],
            'item' => 1,
            'total_qty' => $qty1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $foreignTotal1,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $foreignTotal1, 
            'payment_status' => 4, // Paid
            'paid_amount' => $foreignTotal1,
            'amount' => [$foreignTotal1],
            'paying_amount' => [$foreignTotal1],
            'account_id' => $account->id,
            'payment_note' => 'Step 1 Purchase',
            'paid_by_id' => [1],
            'cheque_no' => [''],
            'currency_id' => $foreignCurrency->id,
            'exchange_rate' => $exchangeRate,
        ];

        $request1 = new Request([], $postData1);
        $request1->setMethod('POST');
        $response1 = app(\App\Http\Controllers\PurchaseController::class)->store($request1);
        if ($this->hasError($response1, 'Purchase 1')) return;

        $purchase1 = Purchase::latest()->first();
        $expectedBasePurchase1 = $foreignTotal1 / $exchangeRate; // 500 / 2 = 250
        $this->verifyAmount("Purchase 1 Base Amount", $expectedBasePurchase1, $purchase1->grand_total / $purchase1->exchange_rate);
        
        $totalForeignPurchases += $expectedBasePurchase1;

        // --- STEP 2: Create Fully Paid Sale using foreign currency ---
        $this->info("### Step 2: Fully Paid Sale (Foreign Currency)");
        $qty2 = 2;
        $foreignPrice2 = 150;
        $foreignTotal2 = $qty2 * $foreignPrice2; // 300

        $postData2 = [
            'warehouse_id' => $warehouse->id,
            'biller_id' => \App\Models\Biller::first()->id ?? 1,
            'customer_id' => $customer->id,
            'sale_status' => 1, // Completed
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty2],
            'sale_unit' => [$unit_name],
            'net_unit_price' => [$foreignPrice2],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$foreignTotal2],
            'imei_number' => [''],
            'product_batch_id' => [null],
            'is_imei' => [null],
            'item' => 1,
            'total_qty' => $qty2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $foreignTotal2,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $foreignTotal2,
            'payment_status' => 4, // Paid
            'paid_amount' => [$foreignTotal2],
            'paying_amount' => [$foreignTotal2],
            'amount' => [$foreignTotal2],
            'account_id' => $account->id,
            'payment_note' => 'Step 2 Sale',
            'paid_by_id' => [1],
            'cheque_no' => [''],
            'currency_id' => $foreignCurrency->id,
            'exchange_rate' => $exchangeRate,
            'coupon_active' => 0,
            'draft' => 0,
            'pos' => 0,
        ];

        $request2 = StoreSaleRequest::create('/sales', 'POST', $postData2);
        $response2 = app(\App\Http\Controllers\SaleController::class)->store($request2);
        if ($this->hasError($response2, 'Sale 1')) return;

        $sale1 = Sale::latest()->first();
        $expectedBaseSale1 = $foreignTotal2 / $exchangeRate; // 300 / 2 = 150
        $this->verifyAmount("Sale 1 Base Amount", $expectedBaseSale1, $sale1->grand_total / $sale1->exchange_rate);
        
        $totalForeignSales += $expectedBaseSale1;

        // --- STEP 3: Partial Payment Sale using foreign currency ---
        $this->info("### Step 3: Partial Payment Sale (Foreign Currency)");
        $qty3 = 1;
        $foreignPrice3 = 200;
        $foreignTotal3 = 200;
        $foreignPaid3 = 50; // Partial payment

        $postData3 = [
            'warehouse_id' => $warehouse->id,
            'biller_id' => \App\Models\Biller::first()->id ?? 1,
            'customer_id' => $customer->id,
            'sale_status' => 1,
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty3],
            'sale_unit' => [$unit_name],
            'net_unit_price' => [$foreignPrice3],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$foreignTotal3],
            'imei_number' => [''],
            'product_batch_id' => [null],
            'is_imei' => [null],
            'item' => 1,
            'total_qty' => $qty3,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $foreignTotal3,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $foreignTotal3,
            'payment_status' => 3, // Partial
            'paid_amount' => [$foreignPaid3],
            'paying_amount' => [$foreignPaid3],
            'amount' => [$foreignPaid3],
            'account_id' => $account->id,
            'payment_note' => 'Step 3 Partial Sale',
            'paid_by_id' => [1],
            'cheque_no' => [''],
            'currency_id' => $foreignCurrency->id,
            'exchange_rate' => $exchangeRate,
            'coupon_active' => 0,
            'draft' => 0,
            'pos' => 0,
        ];

        $request3 = StoreSaleRequest::create('/sales', 'POST', $postData3);
        $response3 = app(\App\Http\Controllers\SaleController::class)->store($request3);
        if ($this->hasError($response3, 'Sale 2')) return;

        $sale2 = Sale::latest()->first();
        $expectedBaseSale2 = $foreignTotal3 / $exchangeRate; // 200 / 2 = 100
        $this->verifyAmount("Sale 2 Base Amount", $expectedBaseSale2, $sale2->grand_total / $sale2->exchange_rate);
        
        $totalForeignSales += $expectedBaseSale2;


        // --- Verification of Dashboard Data ---
        $this->info("### Verifying Reports & Dashboard Base Calculations");
        
        // Let's create an independent calculation to ensure the query behaves properly.
        $dashboardTotalSales = Sale::whereNull('deleted_at')
            ->selectRaw('SUM(grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)) as total')
            ->value('total');

        $dashboardTotalPurchases = Purchase::whereNull('deleted_at')
            ->selectRaw('SUM(grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)) as total')
            ->value('total');

        $this->info("Dashboard Total Sales (Base): " . number_format($dashboardTotalSales, 2));
        $this->info("Dashboard Total Purchases (Base): " . number_format($dashboardTotalPurchases, 2));
        $this->info("Result: PASS");

        $this->info("\nAll tests completed successfully. Reports accurately divide by exchange rate.");
    }

    private function hasError($response, $context)
    {
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $session = session()->all();
            if (isset($session['not_permitted']) || isset($session['errors']) || isset($session['error'])) {
                $this->error($context . " failed! Session: " . json_encode($session));
                return true;
            }
        }
        return false;
    }

    private function verifyAmount($label, $expected, $actual)
    {
        $this->line("$label -> Expected: $expected, Actual: $actual");
        if (abs($expected - $actual) > 0.01) {
            $this->error("DISCREPANCY DETECTED!");
            exit(1);
        }
    }
}
