<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\ProductPurchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\Payment;
use App\Models\ReturnPurchase;
use App\Models\Expense;
use App\Models\Payroll;
use App\Http\Controllers\ReturnPurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario10 extends Command
{
    protected $signature = 'test:scenario10';
    protected $description = 'Run Scenario 10: Purchase Return Against Full Due Purchase';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        // Find the purchase created in Scenario 9 (payment_status = 2 Full Due)
        $purchase = Purchase::where('payment_status', 2)->latest()->first();
        if (!$purchase) {
            $this->error("No full due purchase found. Please run Scenario 9 first.");
            return;
        }

        $productPurchase = ProductPurchase::where('purchase_id', $purchase->id)->first();
        $product = Product::find($productPurchase->product_id);
        $supplier = Supplier::find($purchase->supplier_id);
        $account = Account::where('is_default', true)->first();

        // Calculate expected values BEFORE return
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
        $returnPaymentSentBefore = Payment::whereNotNull('return_id')->where('account_id', $account->id)->sum('amount');
        $returnPurchasePaymentReceivedBefore = Payment::whereNotNull('purchase_return_id')->where('account_id', $account->id)->sum('amount');
        $expensesBefore = Expense::where('account_id', $account->id)->sum('amount');
        $payrollsBefore = Payroll::where('account_id', $account->id)->sum('amount');
        
        $moneyTransferSentBefore = \App\Models\MoneyTransfer::where('from_account_id', $account->id)->sum('amount');
        $moneyTransferReceivedBefore = \App\Models\MoneyTransfer::where('to_account_id', $account->id)->sum('amount');

        $initialAccountBalance = $account->initial_balance 
            + $paymentReceivedBefore 
            - $paymentSentBefore 
            - $returnPaymentSentBefore 
            + $returnPurchasePaymentReceivedBefore 
            - $expensesBefore 
            - $payrollsBefore 
            - $moneyTransferSentBefore 
            + $moneyTransferReceivedBefore;

        $returnQty = 1;
        $returnPrice = $productPurchase->net_unit_cost; // or net_unit_price depending on how sale pro names it, wait Scenario 3 used net_unit_price, but productPurchase uses net_unit_cost. Let's use net_unit_cost
        
        // Ensure unit is correct
        $unit = \App\Models\Unit::find($product->unit_id);
        $unit_name = $unit ? $unit->unit_name : 'piece';

        $totalReturnPrice = $returnQty * $returnPrice;

        $postData = [
            'purchase_id' => $purchase->id,
            'is_return' => [$productPurchase->id],
            'product_purchase_id' => [$productPurchase->id],
            'is_imei' => [null],
            'product_id' => [$product->id],
            'qty' => [$returnQty],
            'product_batch_id' => [$productPurchase->product_batch_id],
            'product_code' => [$product->code],
            'product_price' => [$returnPrice],
            'net_unit_cost' => [$returnPrice],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$totalReturnPrice],
            'purchase_unit' => [$unit_name],
            'unit_id' => [$product->unit_id],
            'product_variant_id' => [null],
            'imei_number' => [null],

            'item' => 1,
            'total_qty' => $returnQty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $totalReturnPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $totalReturnPrice,

            'account_id' => $account->id,
            'refund_amount' => $totalReturnPrice,
            'paying_method' => 'Cash',
            'return_note' => 'Scenario 10 Purchase Return Against Full Due',
            'staff_note' => '',
        ];

        $request = Request::create('/return-purchase', 'POST', $postData);
        $controller = app(ReturnPurchaseController::class);

        try {
            $response = $controller->store($request);
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                if (session()->has('not_permitted')) {
                    $this->error("Return failed: " . session('not_permitted'));
                }
                if (session()->has('message')) {
                    $this->info("Return success: " . session('message'));
                }
                if (session()->has('errors')) {
                    $this->error("Validation Errors: " . json_encode(session('errors')->all()));
                }
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }

        $this->info("\n## Scenario 10 Results\n");

        $product->refresh();

        // 1. Verify Stock (should decrease since we returned items to supplier)
        $expectedStock = $initialStock - $returnQty;
        $actualStock = $product->qty;
        $this->info("### Stock Verification");
        $this->line("Database Stock Before Return: $initialStock");
        $this->line("Returned Quantity: $returnQty");
        $this->line("Database Stock After Return: $actualStock");
        $this->line("Expected Stock: $expectedStock");
        $this->line("Result: " . ($expectedStock == $actualStock ? "PASS\n" : "FAIL\n"));

        // 2. Verify Supplier Due (should decrease because we owe them less)
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
        
        // Liability decreases by the return price since we haven't paid them for it
        $expectedSupplierDue = $initialSupplierDue - $totalReturnPrice; 

        $this->info("### Supplier Ledger (Due) Verification");
        $this->line("Purchase Amount: " . $purchase->grand_total);
        $this->line("Purchase Due Before Return: $initialSupplierDue");
        $this->line("Return Amount: $totalReturnPrice");
        $this->line("Purchase Due After Return (Expected): $expectedSupplierDue");
        $this->line("Purchase Due After Return (Actual): $actualSupplierDue");
        $this->line("Result: " . ($expectedSupplierDue == $actualSupplierDue ? "PASS\n" : "FAIL\n"));

        // 3. Verify Account Balance (should NOT change, as we didn't get cash back for an unpaid purchase)
        $paymentSentAfter = Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
        $paymentReceivedAfter = Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
        $returnPaymentSentAfter = Payment::whereNotNull('return_id')->where('account_id', $account->id)->sum('amount');
        $returnPurchasePaymentReceivedAfter = Payment::whereNotNull('purchase_return_id')->where('account_id', $account->id)->sum('amount');
        $expensesAfter = Expense::where('account_id', $account->id)->sum('amount');
        $payrollsAfter = Payroll::where('account_id', $account->id)->sum('amount');
        
        $moneyTransferSentAfter = \App\Models\MoneyTransfer::where('from_account_id', $account->id)->sum('amount');
        $moneyTransferReceivedAfter = \App\Models\MoneyTransfer::where('to_account_id', $account->id)->sum('amount');

        $actualAccountBalance = $account->initial_balance 
            + $paymentReceivedAfter 
            - $paymentSentAfter 
            - $returnPaymentSentAfter 
            + $returnPurchasePaymentReceivedAfter 
            - $expensesAfter 
            - $payrollsAfter 
            - $moneyTransferSentAfter 
            + $moneyTransferReceivedAfter;

        $expectedAccountBalance = $initialAccountBalance;

        $this->info("### Accounting Verification");
        $this->line("Expected Account Balance: $expectedAccountBalance");
        $this->line("Actual Account Balance: $actualAccountBalance");
        $this->line("Result: " . ($expectedAccountBalance == $actualAccountBalance ? "PASS\n" : "FAIL\n"));
    }
}
