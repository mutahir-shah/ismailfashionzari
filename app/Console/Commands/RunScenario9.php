<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\Unit;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\ReturnPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario9 extends Command
{
    protected $signature = 'test:scenario9';
    protected $description = 'Run Scenario 9: Create a purchase where amount was full due';

    public function handle()
    {
        $user = User::first();
        Auth::loginUsingId(1); // Admin

        $product = Product::first();
        $supplier = Supplier::first();
        $warehouse = Warehouse::first();
        $account = Account::where('is_default', true)->first() ?? Account::first();
        
        $unit = Unit::where('id', $product->unit_id)->first() ?? Unit::first();

        // Calculate expected values BEFORE purchase
        $initialStock = $product->qty;
        
        $opening_balance = $supplier->opening_balance ?? 0;
        
        $existing_purchase_total = Purchase::where('supplier_id', $supplier->id)
            ->where(function ($q) {
                $q->where('purchase_type', '!=', 'opening balance')
                    ->orWhereNull('purchase_type');
            })
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $existing_paid_total = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
            ->where('purchases.supplier_id', $supplier->id)
            ->whereNull('purchases.deleted_at')
            ->sum('payments.amount');

        $existing_return_total = ReturnPurchase::where('supplier_id', $supplier->id)->sum('grand_total');

        $initialSupplierDue = $opening_balance + $existing_purchase_total - $existing_return_total - $existing_paid_total;
        
        $paymentSentBefore = Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedBefore = Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $initialAccountBalance = $account->initial_balance + $paymentReceivedBefore - $paymentSentBefore;

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
            'payment_status' => 2, // Full Due
            'paid_amount' => 0,
            'amount' => [0],
            'paying_amount' => [0],
            'account_id' => $account->id,
            'payment_note' => 'Scenario 9 Purchase Full Due',
            'paid_by_id' => [1], // Cash
            'cheque_no' => '',
            'exchange_rate' => 1,
        ];

        $request = new Request([], $postData);
        $request->setMethod('POST');
        
        // Execute the controller method
        app(\App\Http\Controllers\PurchaseController::class)->store($request);

        $this->info("## Scenario 9 Results\n");

        // Capture AFTER state
        $product->refresh();

        // 1. Stock increased correctly.
        $expectedStock = $initialStock + $qty;
        $actualStock = $product->qty;
        $this->info("### Stock Verification");
        $this->line("Expected Stock: $expectedStock");
        $this->line("Actual Stock: $actualStock");
        $this->line("Result: " . ($expectedStock == $actualStock ? 'PASS' : 'FAIL') . "\n");

        // 2. Supplier balance updated correctly (Full due -> due should increase by $total).
        $new_existing_purchase_total = Purchase::where('supplier_id', $supplier->id)
            ->where(function ($q) {
                $q->where('purchase_type', '!=', 'opening balance')
                    ->orWhereNull('purchase_type');
            })
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $new_existing_paid_total = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
            ->where('purchases.supplier_id', $supplier->id)
            ->whereNull('purchases.deleted_at')
            ->sum('payments.amount');

        $new_existing_return_total = ReturnPurchase::where('supplier_id', $supplier->id)->sum('grand_total');

        $actualSupplierDue = $opening_balance + $new_existing_purchase_total - $new_existing_return_total - $new_existing_paid_total;
        $expectedSupplierDue = $initialSupplierDue + $total;
        
        $this->info("### Supplier Ledger (Due) Verification");
        $this->line("Expected Due: $expectedSupplierDue");
        $this->line("Actual Due: $actualSupplierDue");
        $this->line("Result: " . ($expectedSupplierDue == $actualSupplierDue ? 'PASS' : 'FAIL') . "\n");

        // 3. Account balance updated correctly (No change since amount paid = 0).
        $paymentSentAfter = Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedAfter = Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $actualAccountBalance = $account->initial_balance + $paymentReceivedAfter - $paymentSentAfter;

        $expectedAccountBalance = $initialAccountBalance; // No cash out
        
        $this->info("### Account Balance Verification");
        $this->line("Expected Balance: $expectedAccountBalance");
        $this->line("Actual Balance: $actualAccountBalance");
        $this->line("Result: " . ($expectedAccountBalance == $actualAccountBalance ? 'PASS' : 'FAIL') . "\n");
    }
}
