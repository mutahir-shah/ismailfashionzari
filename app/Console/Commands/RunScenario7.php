<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Http\Controllers\ReturnController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario7 extends Command
{
    protected $signature = 'test:scenario7';
    protected $description = 'Run Scenario 7: Create a sale return where amount was partially due';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        // Get the latest sale with partial payment (paid_amount > 0 and paid_amount < grand_total)
        $sale = Sale::whereColumn('paid_amount', '<', 'grand_total')
                    ->where('paid_amount', '>', 0)
                    ->latest()
                    ->first();
        if (!$sale) {
            $this->error("No partially paid sale found. Please run Scenario 4 first.");
            return;
        }

        $productSale = Product_Sale::where('sale_id', $sale->id)->first();
        $product = Product::find($productSale->product_id);
        $customer = Customer::find($sale->customer_id);
        $account = Account::where('is_default', true)->first();
        $register = CashRegister::where('user_id', 1)->where('status', true)->first();

        // Calculate expected values BEFORE return
        $initialStock = $product->qty;
        
        $initialDue = 0;
        $existing_sales_total = Sale::where('customer_id', $customer->id)
            ->where('payment_status', '!=', 4) // paid বাদে
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $existing_return_total = \App\Models\Returns::where('customer_id', $customer->id)->sum('grand_total');

        $existing_paid_total = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('sales.deleted_at')
            ->sum('payments.amount');

        $initialDue = $existing_sales_total - $existing_return_total - $existing_paid_total;

        $initialAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');

        $returnQty = 1; // Return 1 out of 2
        $returnPrice = $productSale->net_unit_price; // subtotal for 1 qty

        $unit = \App\Models\Unit::find($product->unit_id);
        $unit_name = $unit ? $unit->unit_name : 'piece';

        $postData = [
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $sale->warehouse_id,
            'biller_id' => $sale->biller_id,
            
            'product_sale_id' => [$productSale->id],
            'is_imei' => [null],
            'product_id' => [$product->id],
            'qty' => [$returnQty],
            'product_batch_id' => [$productSale->product_batch_id],
            'product_code' => [$product->code],
            'net_unit_price' => [$returnPrice],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$returnPrice],
            'sale_unit' => [$unit_name],
            'unit_id' => [$product->unit_id],
            'product_variant_id' => [null],
            'imei_number' => [null],
            
            'item' => 1,
            'total_qty' => $returnQty,
            'total_sale_discount' => 0,
            'total_tax' => 0,
            'total_price' => $returnPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $returnPrice,

            'account_id' => $account->id,
            'refund_amount' => 0, // Since due >= return amount, we just offset due, no cash refund
            'refund' => 0, // Unchecked
            'paying_method' => 'Cash',
            'return_note' => 'Scenario 7 Sale Return (Partial Due Sale)',
            'staff_note' => '',
            'change_sale_status' => false
        ];

        $request = Request::create('/return-sale', 'POST', $postData);
        $controller = app(ReturnController::class);

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

        $this->info("## Scenario 7 Results\n");

        // Verify Stock (should increase)
        $expectedStock = $initialStock + $returnQty;
        $actualStock = $product->fresh()->qty;
        $this->info("### Stock Verification");
        $this->info("Expected Stock: $expectedStock");
        $this->info("Actual Stock: $actualStock");
        $this->info("Result: " . ($expectedStock == $actualStock ? "PASS\n" : "FAIL\n"));

        // Verify Customer Due
        $new_existing_sales_total = Sale::where('customer_id', $customer->id)
            ->where('payment_status', '!=', 4) 
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $new_existing_return_total = \App\Models\Returns::where('customer_id', $customer->id)->sum('grand_total');

        $new_existing_paid_total = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('sales.deleted_at')
            ->sum('payments.amount');

        $actualDue = $new_existing_sales_total - $new_existing_return_total - $new_existing_paid_total;
        $expectedDue = $initialDue - $returnPrice; 
        
        $this->info("### Customer Ledger (Due) Verification");
        $this->info("Expected Due: $expectedDue");
        $this->info("Actual Due: $actualDue");
        $this->info("Result: " . ($expectedDue == $actualDue ? "PASS\n" : "FAIL\n"));

        // Verify Account Balance
        // No refund given, so account balance should remain exactly the same
        $actualAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');
        $expectedAccountBalance = $initialAccountBalance;

        $this->info("### Account Balance Verification");
        $this->info("Expected Balance: $expectedAccountBalance");
        $this->info("Actual Balance: $actualAccountBalance");
        $this->info("Result: " . ($expectedAccountBalance == $actualAccountBalance ? "PASS\n" : "FAIL\n"));
    }
}
