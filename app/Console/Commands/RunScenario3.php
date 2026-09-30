<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\ProductPurchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\Payment;
use App\Http\Controllers\ReturnPurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario3 extends Command
{
    protected $signature = 'test:scenario3';
    protected $description = 'Run Scenario 3: Create a purchase return';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        $purchase = Purchase::latest()->first();
        if (!$purchase) {
            $this->error("No purchase found. Please run Scenario 1 first.");
            return;
        }

        $productPurchase = ProductPurchase::where('purchase_id', $purchase->id)->first();
        $product = Product::find($productPurchase->product_id);
        $supplier = Supplier::find($purchase->supplier_id);
        $account = Account::where('is_default', true)->first();

        // Calculate expected values BEFORE return
        $initialStock = $product->qty;
        
        $initialDue = 0;
        $existing_purchases_total = Purchase::where('supplier_id', $supplier->id)
            ->where('payment_status', '!=', 4) // paid বাদে
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $existing_paid_total = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
            ->where('purchases.supplier_id', $supplier->id)
            ->whereNull('purchases.deleted_at')
            ->sum('payments.amount');

        $initialDue = $existing_purchases_total - $existing_paid_total;

        $initialAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');

        $returnQty = 1;
        $returnPrice = $productPurchase->net_unit_price;
        $unit = \App\Models\Unit::find($product->unit_id);
        $unit_name = $unit ? $unit->unit_name : 'piece';

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
            'subtotal' => [$returnPrice],
            'purchase_unit' => [$unit_name],
            'unit_id' => [$product->unit_id],
            'product_variant_id' => [null],
            'imei_number' => [null],

            'item' => 1,
            'total_qty' => $returnQty,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $returnPrice,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => $returnPrice,

            'account_id' => $account->id,
            'refund_amount' => $returnPrice,
            'paying_method' => 'Cash',
            'return_note' => 'Scenario 3 Purchase Return',
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

        $this->info("## Scenario 3 Results\n");

        // Verify Stock (should decrease since we returned to supplier)
        $expectedStock = $initialStock - $returnQty;
        $actualStock = $product->fresh()->qty;
        $this->info("### Stock Verification");
        $this->info("Expected Stock: $expectedStock");
        $this->info("Actual Stock: $actualStock");
        $this->info("Result: " . ($expectedStock == $actualStock ? "PASS\n" : "FAIL\n"));

        // Verify Supplier Due
        $new_existing_purchases_total = Purchase::where('supplier_id', $supplier->id)
            ->where('payment_status', '!=', 4) 
            ->whereNull('deleted_at')
            ->sum('grand_total');

        $new_existing_paid_total = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
            ->where('purchases.supplier_id', $supplier->id)
            ->whereNull('purchases.deleted_at')
            ->sum('payments.amount');

        $actualDue = $new_existing_purchases_total - $new_existing_paid_total;
        $expectedDue = $initialDue; // If it was fully paid before, refunding doesn't change due (supplier gives us cash)
        $this->info("### Supplier Ledger (Due) Verification");
        $this->info("Expected Due: $expectedDue");
        $this->info("Actual Due: $actualDue");
        $this->info("Result: " . ($expectedDue == $actualDue ? "PASS\n" : "FAIL\n"));

        // Verify Account Balance
        // Cash payment means supplier gave us cash, so our account balance INCREASES
        $actualAccountBalance = $account->initial_balance 
            + Payment::where('account_id', $account->id)->sum('amount');
        $expectedAccountBalance = $initialAccountBalance + $returnPrice;

        $this->info("### Account Balance Verification");
        $this->info("Expected Balance: $expectedAccountBalance");
        $this->info("Actual Balance: $actualAccountBalance");
        $this->info("Result: " . ($expectedAccountBalance == $actualAccountBalance ? "PASS\n" : "FAIL\n"));
    }
}
