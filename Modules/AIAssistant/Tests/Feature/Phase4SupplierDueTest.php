<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\ReturnPurchase;
use App\Models\Supplier;

class Phase4SupplierDueTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function createSupplier(float $ob = 0): Supplier
    {
        return Supplier::create([
            'name'              => 'Sup ' . uniqid(),
            'company_name'      => 'Co',
            'email'             => 'sup' . uniqid() . '@example.com',
            'phone_number'      => '123',
            'address'           => 'Addr',
            'city'              => 'City',
            'is_active'         => true,
            'opening_balance'   => $ob,
        ]);
    }

    private function mockSkill(array $businessContext = [])
    {
        $skill = new \Modules\AIAssistant\Skills\SupplierDueSkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'supplier due summary',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: $businessContext,
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    // 1. Draft purchase is excluded.
    public function test_draft_purchase_is_excluded()
    {
        $supplier = $this->createSupplier();
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 3, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]); // status 3 = draft
        $response = $this->mockSkill();
        $this->assertNull(collect($response->table['rows'] ?? [])->firstWhere('0', $supplier->name));
    }

    // 2. Payment attached to a draft purchase is excluded.
    public function test_payment_attached_to_draft_purchase_is_excluded()
    {
        $supplier = $this->createSupplier(500); // 500 opening balance
        $purchase = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 3, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Payment::create(['payment_reference' => 'Pay1', 'user_id' => 1, 'purchase_id' => $purchase->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);
        
        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(500, $tableRow[1]); // Not 450, because payment on draft purchase is ignored
    }

    // 3. Soft-deleted purchase is excluded.
    public function test_soft_deleted_purchase_is_excluded()
    {
        $supplier = $this->createSupplier();
        $purchase = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $purchase->delete();

        $response = $this->mockSkill();
        $this->assertNull(collect($response->table['rows'] ?? [])->firstWhere('0', $supplier->name));
    }

    // 4. Payment attached to a deleted purchase is excluded.
    public function test_payment_attached_to_deleted_purchase_is_excluded()
    {
        $supplier = $this->createSupplier(500);
        $purchase = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Payment::create(['payment_reference' => 'Pay1', 'user_id' => 1, 'purchase_id' => $purchase->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);
        $purchase->delete();

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(500, $tableRow[1]);
    }

    // 5. Two equal-valued purchases each count.
    public function test_two_equal_valued_purchases_each_count()
    {
        $supplier = $this->createSupplier();
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Purchase::create(['reference_no' => 'P2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(200, $tableRow[1]);
    }

    // 6. Two equal-valued payments each count.
    public function test_two_equal_valued_payments_each_count()
    {
        $supplier = $this->createSupplier();
        $purchase = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Payment::create(['payment_reference' => 'Pay1', 'user_id' => 1, 'purchase_id' => $purchase->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'Pay2', 'user_id' => 1, 'purchase_id' => $purchase->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(100, $tableRow[1]); // 200 - 50 - 50
    }

    // 7. Two equal-valued purchase returns each count.
    public function test_two_equal_valued_returns_each_count()
    {
        $supplier = $this->createSupplier();
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        ReturnPurchase::create(['reference_no' => 'R1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 30, 'grand_total' => 30, 'created_at' => now(), 'updated_at' => now()]);
        ReturnPurchase::create(['reference_no' => 'R2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 30, 'grand_total' => 30, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(140, $tableRow[1]); // 200 - 30 - 30
    }

    // 8. Multiple purchases/payments/returns do not multiply.
    public function test_multiple_transactions_do_not_multiply()
    {
        $supplier = $this->createSupplier();
        $p1 = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $p2 = Purchase::create(['reference_no' => 'P2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        Payment::create(['payment_reference' => 'Pay1', 'user_id' => 1, 'purchase_id' => $p1->id, 'account_id' => 1, 'amount' => 20, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'Pay2', 'user_id' => 1, 'purchase_id' => $p2->id, 'account_id' => 1, 'amount' => 40, 'change' => 0, 'paying_method' => 'Cash']);
        
        ReturnPurchase::create(['reference_no' => 'R1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 10, 'grand_total' => 10, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        
        // 100 + 200 = 300 purchases
        // 20 + 40 = 60 payments
        // 10 returns
        // 300 - 60 - 10 = 230
        $this->assertEquals(230, $tableRow[1]);
    }

    // 9. Warehouse restriction applies identically to purchases, payments, and returns.
    public function test_warehouse_restriction_applies_identically()
    {
        $supplier = $this->createSupplier();
        $p1 = Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $p2 = Purchase::create(['reference_no' => 'P2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 2, 'item' => 1, 'total_qty' => 1, 'total_cost' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        Payment::create(['payment_reference' => 'Pay1', 'user_id' => 1, 'purchase_id' => $p1->id, 'account_id' => 1, 'amount' => 20, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'Pay2', 'user_id' => 1, 'purchase_id' => $p2->id, 'account_id' => 1, 'amount' => 40, 'change' => 0, 'paying_method' => 'Cash']);
        
        ReturnPurchase::create(['reference_no' => 'R1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 10, 'grand_total' => 10, 'created_at' => now(), 'updated_at' => now()]);
        ReturnPurchase::create(['reference_no' => 'R2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 2, 'item' => 1, 'total_qty' => 1, 'total_cost' => 50, 'grand_total' => 50, 'created_at' => now(), 'updated_at' => now()]);

        // Restrict to warehouse 1
        $response = $this->mockSkill(['warehouse_ids' => [1]]);
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        
        // Wh 1: 100 purchase, 20 payment, 10 return -> 70
        $this->assertEquals(70, $tableRow[1]);
    }

    // 10. Global supplier opening balance is omitted from warehouse-restricted results and an explicit warning is returned.
    public function test_global_supplier_ob_omitted_warehouse_restriction()
    {
        $supplier = $this->createSupplier(500);
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        // Restrict to warehouse 1
        $response = $this->mockSkill(['warehouse_ids' => [1]]);
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        
        // Wh 1: 100 purchase. OB 500 should be omitted.
        $this->assertEquals(100, $tableRow[1]);
        $this->assertContains('Global supplier opening balances are excluded from warehouse-restricted views. Showing warehouse activity only.', $response->warnings);
        $this->assertEquals('warehouse_activity_only', $response->metadata['reason']);
        $this->assertFalse($response->metadata['failed_closed']);
    }

    // 11. Opening balance is counted exactly once for unrestricted results.
    public function test_opening_balance_counted_once_unrestricted()
    {
        $supplier = $this->createSupplier(500);
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Purchase::create(['reference_no' => 'P2', 'user_id' => 1, 'supplier_id' => $supplier->id, 'warehouse_id' => 2, 'item' => 1, 'total_qty' => 1, 'total_cost' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $supplier->name);
        $this->assertEquals(700, $tableRow[1]); // 500 + 100 + 100
    }

    // 12. More than 10 positive suppliers: total/count include all while table is limited to 10.
    public function test_more_than_10_suppliers()
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createSupplier(100);
        }
        
        $response = $this->mockSkill();
        
        // Assert total count and due include all
        $this->assertEquals(15, collect($response->cards)->firstWhere('title', 'Suppliers with Due')['value']);
        $this->assertEquals(1500, collect($response->cards)->firstWhere('title', 'Total Payable')['value']);
        
        // Assert table is limited to 10
        $this->assertCount(10, $response->table['rows']);
    }

    // 13. own access fails closed.
    public function test_own_access_fails_closed()
    {
        $response = $this->mockSkill(['own_user_id' => 1]);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('own_access_restriction', $response->metadata['reason']);
        $this->assertContains('Supplier debt view is restricted. User-restricted scopes are not permitted to view this data.', $response->warnings);
    }

    // 14. Explicit empty warehouse access fails closed.
    public function test_explicit_empty_warehouse_access_fails_closed()
    {
        $response = $this->mockSkill(['warehouse_ids' => []]);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('empty_warehouse_scope', $response->metadata['reason']);
        $this->assertContains('No warehouse access is available for this request.', $response->warnings);
    }
}
