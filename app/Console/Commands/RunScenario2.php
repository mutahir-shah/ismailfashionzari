<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\Biller;
use App\Models\Unit;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SaleController;

class RunScenario2 extends Command
{
    protected $signature = 'test:scenario2';
    protected $description = 'Execute Scenario 2 Test';

    public function handle()
    {
        $user = User::first();
        Auth::login($user);

        $product = Product::first();
        $customer = Customer::first();
        $warehouse = Warehouse::first();
        $account = Account::first();
        $biller = Biller::first();
        
        $unit = Unit::where('id', $product->unit_id)->first() ?? Unit::first();

        // 1. Ensure active Cash Register
        $cashRegister = CashRegister::where('user_id', $user->id)->where('status', 1)->first();
        if (!$cashRegister) {
            $cashRegister = CashRegister::create([
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'cash_in_hand' => 1000,
                'status' => 1,
            ]);
        }

        // Capture BEFORE state
        $beforeProductQty = $product->qty;
        
        // Customer Due Calculation
        $salesTotal = \App\Models\Sale::where('customer_id', $customer->id)->sum('grand_total');
        $salePaid = \App\Models\Sale::where('customer_id', $customer->id)->sum('paid_amount');
        $beforeCustomerDue = $salesTotal - $salePaid;
        
        // Account Balance Dynamic
        $paymentSentBefore = \App\Models\Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedBefore = \App\Models\Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $beforeAccountBalance = $account->initial_balance + $paymentReceivedBefore - $paymentSentBefore;
        
        // Cash Register Payments
        $beforeRegisterPayment = \App\Models\Payment::where('cash_register_id', $cashRegister->id)->sum('amount');

        $qty = 2;
        $price = $product->price ?: 150;
        $total = $qty * $price;

        $postData = [
            'warehouse_id' => $warehouse->id,
            'biller_id' => $biller->id,
            'customer_id' => $customer->id,
            'sale_status' => 1, // Completed
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'product_batch_id' => [null],
            'qty' => [$qty],
            'sale_unit' => [$unit->unit_name],
            'net_unit_price' => [$price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$total],
            'imei_number' => [''],
            'item' => 1,
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $total,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount_type' => 'Flat',
            'order_discount_value' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $total,
            'payment_status' => 4, // Paid
            'paid_amount' => [$total],
            'paying_amount' => [$total],
            'amount' => [$total],
            'account_id' => $account->id,
            'paid_by_id' => [1], // Cash
            'payment_note' => 'Scenario 2 POS Cash Payment',
            'pos' => 1, // Flag for POS Sale
            'draft' => 0,
            'coupon_active' => 0,
        ];

        $request = new \App\Http\Requests\Sale\StoreSaleRequest([], $postData);
        $request->setMethod('POST');
        
        try {
            $response = app(SaleController::class)->store($request);
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                if (session()->has('not_permitted')) {
                    $this->error("Sale failed: " . session('not_permitted'));
                }
                if (session()->has('message')) {
                    $this->info("Sale success: " . session('message'));
                }
                if (session()->has('errors')) {
                    $this->error("Validation Errors: " . json_encode(session('errors')->all()));
                }
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return;
        }

        // Capture AFTER state
        $product->refresh();

        // 1. Stock reduced correctly.
        $expectedStock = $beforeProductQty - $qty;
        $actualStock = $product->qty;
        $stockStatus = ($expectedStock == $actualStock) ? 'PASS' : 'FAIL';

        // 2. Customer ledger updated correctly (Due shouldn't change as it's fully paid).
        $salesTotalAfter = \App\Models\Sale::where('customer_id', $customer->id)->sum('grand_total');
        $salePaidAfter = \App\Models\Sale::where('customer_id', $customer->id)->sum('paid_amount');
        $afterCustomerDue = $salesTotalAfter - $salePaidAfter;
        $customerStatus = ($beforeCustomerDue == $afterCustomerDue) ? 'PASS' : 'FAIL';

        // 3. Cash Register updated correctly.
        $afterRegisterPayment = \App\Models\Payment::where('cash_register_id', $cashRegister->id)->sum('amount');
        $expectedRegisterPayment = $beforeRegisterPayment + $total;
        $registerStatus = ($expectedRegisterPayment == $afterRegisterPayment) ? 'PASS' : 'FAIL';

        // 4. Account balance updated correctly.
        $paymentSentAfter = \App\Models\Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedAfter = \App\Models\Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $afterAccountBalance = $account->initial_balance + $paymentReceivedAfter - $paymentSentAfter;
        $expectedAccountBalance = $beforeAccountBalance + $total;
        $accountStatus = ($expectedAccountBalance == $afterAccountBalance) ? 'PASS' : 'FAIL';

        $this->info("## Scenario 2 Results\n");
        
        $this->info("### Stock Verification");
        $this->line("Expected Stock: $expectedStock");
        $this->line("Actual Stock: $actualStock");
        $this->line("Result: $stockStatus\n");

        $this->info("### Customer Ledger (Due) Verification");
        $this->line("Expected Due: $beforeCustomerDue");
        $this->line("Actual Due: $afterCustomerDue");
        $this->line("Result: $customerStatus\n");
        
        $this->info("### Cash Register Verification");
        $this->line("Expected Register Payment Total: $expectedRegisterPayment");
        $this->line("Actual Register Payment Total: $afterRegisterPayment");
        $this->line("Result: $registerStatus\n");

        $this->info("### Account Balance Verification");
        $this->line("Expected Balance: $expectedAccountBalance");
        $this->line("Actual Balance: $afterAccountBalance");
        $this->line("Result: $accountStatus\n");
    }
}
