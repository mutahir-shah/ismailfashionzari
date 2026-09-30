<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\Unit;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class Scenario1Test extends TestCase
{
    use WithoutMiddleware; // to bypass CSRF and auth middleware easily

    public function test_scenario_1()
    {
        $user = User::first();
        $this->actingAs($user);

        $product = Product::first();
        $supplier = Supplier::first();
        $warehouse = Warehouse::first();
        $account = Account::first();
        
        $unit = Unit::where('id', $product->unit_id)->first() ?? Unit::first();

        // Capture BEFORE state
        $beforeProductQty = $product->qty;
        $beforeSupplierDue = $supplier->total_due ?? 0;
        $beforeAccountBalance = $account->total_balance;

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

        $response = $this->post('/purchases', $postData);

        // Debug response if it fails
        if ($response->status() !== 302 && $response->status() !== 200) {
            echo "Failed to create purchase. Status: " . $response->status() . "\n";
            echo $response->content();
            $this->fail('Failed to create purchase');
        }

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
        $expectedAccountBalance = $beforeAccountBalance - $total;
        $actualAccountBalance = $account->total_balance;
        $accountStatus = ($expectedAccountBalance == $actualAccountBalance) ? 'PASS' : 'FAIL';

        echo "## Scenario 1 Results\n\n";
        echo "### Stock Verification\n";
        echo "Expected Stock: $expectedStock\n";
        echo "Actual Stock: $actualStock\n";
        echo "Result: $stockStatus\n\n";

        echo "### Supplier Balance Verification\n";
        echo "Expected Due: $expectedSupplierDue\n";
        echo "Actual Due: $actualSupplierDue\n";
        echo "Result: $supplierStatus\n\n";

        echo "### Account Balance Verification\n";
        echo "Expected Balance: $expectedAccountBalance\n";
        echo "Actual Balance: $actualAccountBalance\n";
        echo "Result: $accountStatus\n\n";

        $this->assertTrue($expectedStock == $actualStock, "Stock mismatch");
        $this->assertTrue($expectedSupplierDue == $actualSupplierDue, "Supplier due mismatch");
        $this->assertTrue($expectedAccountBalance == $actualAccountBalance, "Account balance mismatch");
    }
}
