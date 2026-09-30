<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Account;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Sale\StoreSaleRequest;

class RunScenario8 extends Command
{
    protected $signature = 'test:scenario8';
    protected $description = 'Run Scenario 8: Create a sale where amount was full paid';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        $product = Product::first();
        $customer = Customer::first();
        $account = Account::where('is_default', true)->first();

        // Ensure active cash register for Admin
        $register = CashRegister::where('user_id', 1)->where('status', true)->first();
        if (!$register) {
            $register = CashRegister::create([
                'user_id' => 1,
                'warehouse_id' => 1,
                'status' => true,
                'cash_in_hand' => 1000,
            ]);
        }

        // Calculate expected values BEFORE sale
        $initialStock = $product->qty;
        
        $opening_balance_amount = $customer->opening_balance ?? 0;
        
        $existing_sales_total = Sale::where(function ($q) {
                $q->where('sale_type', '!=', 'opening balance')
                  ->orWhereNull('sale_type');
            })
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $existing_paid_total = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('return_id')
            ->whereNull('sales.deleted_at')
            ->sum('payments.amount');

        $existing_refund_total = Payment::join('returns', 'returns.id', '=', 'payments.return_id')
            ->where('returns.customer_id', $customer->id)
            ->sum('payments.amount');

        $existing_return_total = \App\Models\Returns::where('customer_id', $customer->id)->sum('grand_total');

        $initialDue = ($opening_balance_amount + $existing_sales_total + $existing_refund_total)
                    - ($existing_paid_total + $existing_return_total);

        $qty = 2;
        $price = $product->price;
        $total = $qty * $price;
        $paid = $total; // Full payment

        $unit = \App\Models\Unit::find($product->unit_id);
        $unit_name = $unit ? $unit->unit_name : 'piece';

        $postData = [
            'warehouse_id' => 1,
            'biller_id' => 1,
            'customer_id' => $customer->id,
            'product_id' => [$product->id],
            'product_code' => [$product->code],
            'is_imei' => [null],
            'qty' => [$qty],
            'product_batch_id' => [null],
            'sale_unit' => [$unit_name],
            'net_unit_price' => [$price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$total],
            'imei_number' => [null],
            'topping_product' => [null], 
            'sale_status' => 1, 
            'total_qty' => $qty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $total,
            'item' => 1,
            'order_tax_rate' => 0,
            'order_discount_value' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => $total,
            'payment_status' => 4, // Paid
            'paid_amount' => [$paid],
            'paying_amount' => [$paid],
            'amount' => [$paid],
            'account_id' => $account->id,
            'paid_by_id' => [1], // Cash
            'payment_note' => 'Scenario 8 Full Paid Standard Sale',
            'pos' => 0, // Flag for POS Sale (0 for standard sale)
            'draft' => 0,
            'coupon_active' => 0,
        ];

        $request = StoreSaleRequest::create('/sales', 'POST', $postData);
        $controller = app(SaleController::class);

        try {
            $response = $controller->store($request);
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
        }

        $this->info("## Scenario 8 Results\n");

        // Verify Stock
        $expectedStock = $initialStock - $qty;
        $actualStock = $product->fresh()->qty;
        $this->info("### Stock Verification");
        $this->info("Expected Stock: $expectedStock");
        $this->info("Actual Stock: $actualStock");
        $this->info("Result: " . ($expectedStock == $actualStock ? "PASS\n" : "FAIL\n"));

        // Verify Customer Due 
        $new_existing_sales_total = Sale::where(function ($q) {
                $q->where('sale_type', '!=', 'opening balance')
                  ->orWhereNull('sale_type');
            })
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $new_existing_paid_total = Payment::join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->whereNull('return_id')
            ->whereNull('sales.deleted_at')
            ->sum('payments.amount');

        $new_existing_refund_total = Payment::join('returns', 'returns.id', '=', 'payments.return_id')
            ->where('returns.customer_id', $customer->id)
            ->sum('payments.amount');

        $new_existing_return_total = \App\Models\Returns::where('customer_id', $customer->id)->sum('grand_total');

        $actualDue = ($opening_balance_amount + $new_existing_sales_total + $new_existing_refund_total)
                   - ($new_existing_paid_total + $new_existing_return_total);
        $expectedDue = $initialDue; 
        $this->info("### Customer Ledger (Due) Verification");
        $this->info("Expected Due: $expectedDue");
        $this->info("Actual Due: $actualDue");
        $this->info("Result: " . ($expectedDue == $actualDue ? "PASS\n" : "FAIL\n"));

        // Verify Account Balance
        $actualAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');
        $expectedAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->where('id', '!=', Payment::latest()->first()->id)->sum('amount') 
            + $paid;

        $this->info("### Account Balance Verification");
        $this->info("Expected Balance: $expectedAccountBalance");
        $this->info("Actual Balance: $actualAccountBalance");
        $this->info("Result: " . ($expectedAccountBalance == $actualAccountBalance ? "PASS\n" : "FAIL\n"));
    }
}
