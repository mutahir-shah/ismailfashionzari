<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario1 extends Command
{
    protected $signature = 'test:scenario1';
    protected $description = 'Execute Scenario 1 Test';

    public function handle()
    {
        $user = User::first();
        Auth::login($user);

        $product = Product::first();
        $supplier = Supplier::first();
        $warehouse = Warehouse::first();
        $account = Account::first();
        
        $unit = Unit::where('id', $product->unit_id)->first() ?? Unit::first();

        // Capture BEFORE state
        $beforeProductQty = $product->qty;
        $beforeSupplierDue = $supplier->total_due ?? 0;
        
        $paymentSentBefore = \App\Models\Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedBefore = \App\Models\Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $beforeAccountBalance = $account->initial_balance + $paymentReceivedBefore - $paymentSentBefore;

        $qty = 10;
        $cost = $product->cost ?: 100;
        $total = $qty * $cost;

        $postData = [
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'status' => 1, // Received
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'qty' => [$qty],
            'recieved' => [$qty],
            'purchase_unit' => [$unit->unit_name],
            'net_unit_cost' => [$cost],
            'net_unit_margin' => [10],
            'net_unit_margin_type' => ['percentage'],
            'net_unit_price' => [$cost * 1.1],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$total],
            'imei_number' => [''],
            'unit_cost' => [$cost],
            'item' => 1,
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $total,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $total,
            'payment_status' => 4, // Paid
            'paid_amount' => $total,
            'amount' => [$total],
            'paying_amount' => [$total],
            'account_id' => $account->id,
            'payment_note' => 'Scenario 1 Test Payment',
            'paid_by_id' => [1], // Cash
            'cheque_no' => '',
            'exchange_rate' => 1,
        ];

        $request = new Request([], $postData);
        $request->setMethod('POST');
        
        // Execute the controller method
        app(\App\Http\Controllers\PurchaseController::class)->store($request);

        // Capture AFTER state
        $product->refresh();
        $supplier->refresh();
        $account->refresh();

        // 1. Stock increased correctly.
        $expectedStock = $beforeProductQty + $qty;
        $actualStock = $product->qty;
        $stockStatus = ($expectedStock == $actualStock) ? 'PASS' : 'FAIL';

        // 2. Supplier balance updated correctly (Full paid -> due should be same).
        $expectedSupplierDue = $beforeSupplierDue;
        $actualSupplierDue = $supplier->total_due ?? 0;
        $supplierStatus = ($expectedSupplierDue == $actualSupplierDue) ? 'PASS' : 'FAIL';

        // 3. Account balance updated correctly (reduced by payment).
        // Since SalePro calculates balance dynamically:
        $paymentSent = \App\Models\Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceived = \App\Models\Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $dynamicBalance = $account->initial_balance + $paymentReceived - $paymentSent;

        $expectedAccountBalance = $beforeAccountBalance - $total; // assuming $beforeAccountBalance was initial_balance + old payments
        $actualAccountBalance = $dynamicBalance;
        $accountStatus = ($expectedAccountBalance == $actualAccountBalance) ? 'PASS' : 'FAIL';

        $this->info("## Scenario 1 Results\n");
        $this->info("### Stock Verification");
        $this->line("Expected Stock: $expectedStock");
        $this->line("Actual Stock: $actualStock");
        $this->line("Result: $stockStatus\n");

        $this->info("### Supplier Balance Verification");
        $this->line("Expected Due: $expectedSupplierDue");
        $this->line("Actual Due: $actualSupplierDue");
        $this->line("Result: $supplierStatus\n");

        $this->info("### Account Balance Verification");
        $this->line("Expected Balance: $expectedAccountBalance");
        $this->line("Actual Balance: $actualAccountBalance");
        $this->line("Result: $accountStatus\n");
    }
}
