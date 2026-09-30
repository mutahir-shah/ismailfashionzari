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

class RunScenario6 extends Command
{
    protected $signature = 'test:scenario6';
    protected $description = 'Run Scenario 6: Create a sale return for a sale where amount was full due';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        // Get the latest sale (which is from Scenario 5, full due)
        $sale = Sale::latest()->first();
        if (!$sale) {
            $this->error("No sale found. Please run Scenario 5 first.");
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
            'refund_amount' => 0, // It was full due, so we do not refund cash. Wait, if it's due, we just reduce the due. No cash refund!
            'refund' => 0, // Checkbox for refund
            'paying_method' => 'Cash',
            'return_note' => 'Scenario 6 Sale Return (Full Due Sale)',
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

        $this->info("## Scenario 6 Results\n");

        // Verify Stock (should increase since customer returned item)
        $expectedStock = $initialStock + $returnQty;
        $actualStock = $product->fresh()->qty;
        $this->info("### Stock Verification");
        $this->info("Expected Stock: $expectedStock");
        $this->info("Actual Stock: $actualStock");
        $this->info("Result: " . ($expectedStock == $actualStock ? "PASS\n" : "FAIL\n"));

        // Verify Customer Due
        // A return reduces the total sales value or offsets it, so due should decrease by the returned value.
        // Wait! Sale return doesn't reduce sale->grand_total! It creates a return record.
        // Usually, Customer due = (Total Sales - Total Sale Returns) - (Total Received Payments).
        
        $new_existing_sales_total = Sale::where('customer_id', $customer->id)
            ->where('payment_status', '!=', 4) 
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $new_existing_return_total = \App\Models\Returns::where('customer_id', $customer->id)->sum('grand_total');

        $new_existing_paid_total = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('sales.deleted_at')
            ->sum('payments.amount');

        // Customer due = Sales - Returns - Paid
        $actualDue = $new_existing_sales_total - $new_existing_return_total - $new_existing_paid_total;
        $expectedDue = $initialDue - $returnPrice; 
        
        $this->info("### Customer Ledger (Due) Verification");
        $this->info("Expected Due: $expectedDue");
        $this->info("Actual Due: $actualDue");
        $this->info("Result: " . ($expectedDue == $actualDue ? "PASS\n" : "FAIL\n"));

        // Verify Account Balance
        // Since the sale was full due, we gave NO cash refund. So account balance shouldn't change.
        $actualAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');
        $expectedAccountBalance = $initialAccountBalance;

        $this->info("### Account Balance Verification");
        $this->info("Expected Balance: $expectedAccountBalance");
        $this->info("Actual Balance: $actualAccountBalance");
        $this->info("Result: " . ($expectedAccountBalance == $actualAccountBalance ? "PASS\n" : "FAIL\n"));
    }
}
