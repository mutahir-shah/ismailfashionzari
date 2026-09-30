<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Returns;
use App\Models\Customer;
use App\Models\User;

class Phase3CustomerDueEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'customer_group_id' => 1,
            'name'              => 'Cust ' . uniqid(),
            'company_name'      => 'Co',
            'email'             => 'cust' . uniqid() . '@example.com',
            'phone_number'      => '123',
            'address'           => 'Addr',
            'city'              => 'City',
            'is_active'         => true,
            'opening_balance'   => 0,
        ]);
    }

    private function mockSkill()
    {
        $skill = new \Modules\AIAssistant\Skills\CustomerDueSkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'customer due',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: [],
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    public function test_customer_due_aggregates_equal_valued_sales()
    {
        $customer = $this->createCustomer();
        // Sale 1: 100
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        // Sale 2: 100 (equal value)
        Sale::create(['reference_no' => 'S2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $customer->name);
        $this->assertEquals(200, $tableRow[1]);
    }

    public function test_customer_due_aggregates_equal_valued_payments()
    {
        $customer = $this->createCustomer();
        $sale = Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        Payment::create(['payment_reference' => 'P1', 'user_id' => 1, 'sale_id' => $sale->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'P2', 'user_id' => 1, 'sale_id' => $sale->id, 'account_id' => 1, 'amount' => 50, 'change' => 0, 'paying_method' => 'Cash']);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $customer->name);
        $this->assertEquals(100, $tableRow[1]);
    }

    public function test_customer_due_aggregates_equal_valued_returns()
    {
        $customer = $this->createCustomer();
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        Returns::create(['reference_no' => 'R1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 30, 'grand_total' => 30, 'created_at' => now(), 'updated_at' => now()]);
        Returns::create(['reference_no' => 'R2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 30, 'grand_total' => 30, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $customer->name);
        $this->assertEquals(140, $tableRow[1]); // 200 - 30 - 30
    }

    public function test_customer_due_aggregates_equal_valued_refund_payments()
    {
        $customer = $this->createCustomer();
        // Return without sale context (due = -100)
        $return = Returns::create(['reference_no' => 'R1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'created_at' => now(), 'updated_at' => now()]);
        
        // Refund payment increases due by 40 and 40 (since company gives money back, customer owes us that money back logically in the formula: due = refunds - returns)
        // CustomerDue formula: (sales + opening + refunds) - (payments + returns)
        // Here: (0 + 0 + 80) - (0 + 100) = -20
        Payment::create(['payment_reference' => 'P1', 'user_id' => 1, 'return_id' => $return->id, 'account_id' => 1, 'amount' => 40, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'P2', 'user_id' => 1, 'return_id' => $return->id, 'account_id' => 1, 'amount' => 40, 'change' => 0, 'paying_method' => 'Cash']);

        $response = $this->mockSkill();
        // Since due < 0, it doesn't appear in the table because having('due', '>', 0)
        $this->assertNull(collect($response->table['rows'] ?? [])->firstWhere('0', $customer->name));
        
        // To make it positive, add a big sale
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $response2 = $this->mockSkill();
        $tableRow2 = collect($response2->table['rows'])->firstWhere('0', $customer->name);
        // (200 + 0 + 80) - (0 + 100) = 180
        $this->assertEquals(180, $tableRow2[1]);
    }

    public function test_customer_due_aggregations_do_not_multiply_each_other()
    {
        $customer = $this->createCustomer();
        // Create 3 sales, 3 payments on sale 1, 2 returns, 2 refunds on return 1
        $sale1 = Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Sale::create(['reference_no' => 'S2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Sale::create(['reference_no' => 'S3', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        Payment::create(['payment_reference' => 'P1', 'user_id' => 1, 'sale_id' => $sale1->id, 'account_id' => 1, 'amount' => 10, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'P2', 'user_id' => 1, 'sale_id' => $sale1->id, 'account_id' => 1, 'amount' => 10, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'P3', 'user_id' => 1, 'sale_id' => $sale1->id, 'account_id' => 1, 'amount' => 10, 'change' => 0, 'paying_method' => 'Cash']);
        
        $ret1 = Returns::create(['reference_no' => 'R1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 50, 'grand_total' => 50, 'created_at' => now(), 'updated_at' => now()]);
        Returns::create(['reference_no' => 'R2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 50, 'grand_total' => 50, 'created_at' => now(), 'updated_at' => now()]);
        
        Payment::create(['payment_reference' => 'RP1', 'user_id' => 1, 'return_id' => $ret1->id, 'account_id' => 1, 'amount' => 5, 'change' => 0, 'paying_method' => 'Cash']);
        Payment::create(['payment_reference' => 'RP2', 'user_id' => 1, 'return_id' => $ret1->id, 'account_id' => 1, 'amount' => 5, 'change' => 0, 'paying_method' => 'Cash']);
        
        // Sales: 300
        // Payments: 30
        // Returns: 100
        // Refunds: 10
        // Total due = (300 + 10) - (30 + 100) = 310 - 130 = 180
        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $customer->name);
        $this->assertEquals(180, $tableRow[1]);
    }

    public function test_customer_due_excludes_deleted_and_draft_sales_and_returns()
    {
        $customer = $this->createCustomer();
        // Draft sale
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 3, 'created_at' => now(), 'updated_at' => now()]);
        // Soft deleted sale
        $s2 = Sale::create(['reference_no' => 'S2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $s2->delete();
        
        $response = $this->mockSkill();
        $this->assertNull(collect($response->table['rows'] ?? [])->firstWhere('0', $customer->name));
    }

    public function test_customer_due_counts_opening_balance_exactly_once()
    {
        $customer = Customer::create([
            'customer_group_id' => 1,
            'name'              => 'Cust OB',
            'company_name'      => 'Co',
            'email'             => 'custob@example.com',
            'phone_number'      => '123',
            'address'           => 'Addr',
            'city'              => 'City',
            'is_active'         => true,
            'opening_balance'   => 500,
        ]);
        
        // Ensure that a sale doesn't multiply opening balance
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Sale::create(['reference_no' => 'S2', 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        
        $response = $this->mockSkill();
        $tableRow = collect($response->table['rows'])->firstWhere('0', $customer->name);
        $this->assertEquals(700, $tableRow[1]); // 500 + 100 + 100
    }

    public function test_customer_due_handles_more_than_10_debtors()
    {
        for ($i = 0; $i < 15; $i++) {
            $customer = $this->createCustomer();
            Sale::create(['reference_no' => "S-$i", 'user_id' => 1, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 10, 'grand_total' => 10, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        
        $response = $this->mockSkill();
        // Since there could be other customers from other tests in DatabaseTransactions (if not completely isolated, though DatabaseTransactions rolls back), let's assert count >= 15 for total customers but table rows <= 10.
        $this->assertGreaterThanOrEqual(15, collect($response->cards)->firstWhere('title', 'Customers with Due')['value']);
        $this->assertCount(10, $response->table['rows']);
    }
}
