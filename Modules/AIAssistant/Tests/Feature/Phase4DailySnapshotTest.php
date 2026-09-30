<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use Carbon\Carbon;

class Phase4DailySnapshotTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function mockSkill(array $businessContext = [])
    {
        $skill = new \Modules\AIAssistant\Skills\DailySnapshotSkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'daily snapshot',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: $businessContext,
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    public function test_daily_snapshot_combines_metrics()
    {
        // Sale
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 50, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => Carbon::today(), 'updated_at' => Carbon::today()]);
        
        // Purchase
        Purchase::create(['reference_no' => 'P1', 'user_id' => 1, 'supplier_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_cost' => 200, 'grand_total' => 200, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => Carbon::today(), 'updated_at' => Carbon::today()]);
        
        // Expense
        $cat = \App\Models\ExpenseCategory::create(['code' => 'EC1', 'name' => 'General', 'is_active' => true]);
        Expense::create(['reference_no' => 'E1', 'expense_category_id' => $cat->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 1, 'amount' => 150, 'created_at' => Carbon::today()]);

        $response = $this->mockSkill(); // Admin / Unrestricted
        
        $this->assertEquals(100, collect($response->cards)->firstWhere('title', 'Today\'s Sales')['value']);
        $this->assertEquals(200, collect($response->cards)->firstWhere('title', 'Today\'s Purchases')['value']);
        $this->assertEquals(150, collect($response->cards)->firstWhere('title', 'Today\'s Expenses')['value']);
        $this->assertEquals(50, collect($response->cards)->firstWhere('title', 'Sales Due Created')['value']);
        $this->assertEquals(200, collect($response->cards)->firstWhere('title', 'Purchases Due Created')['value']);
        $this->assertEquals(3, collect($response->cards)->firstWhere('title', 'Total Transactions')['value']);
        $this->assertNotNull(collect($response->cards)->firstWhere('title', 'Low Stock Items'));
        
        $this->assertFalse($response->metadata['failed_closed']);
    }

    // Daily Snapshot explicit empty scope must zero every card and warn.
    public function test_daily_snapshot_explicit_empty_scope_warning()
    {
        $response = $this->mockSkill(['warehouse_ids' => []]);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('empty_warehouse_scope', $response->metadata['reason']);
        $this->assertContains('No warehouse access is available for this request.', $response->warnings);
        
        foreach ($response->cards as $card) {
            $this->assertEquals(0, $card['value']);
        }
    }

    // Daily Snapshot under own must retain scoped Sales/Purchase/Expense, omit/zero unavailable Low Stock, and warn.
    public function test_daily_snapshot_under_own_omits_low_stock()
    {
        // Sale
        Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 50, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => Carbon::today(), 'updated_at' => Carbon::today()]);
        
        $response = $this->mockSkill(['own_user_id' => 1]);
        
        $this->assertEquals(100, collect($response->cards)->firstWhere('title', 'Today\'s Sales')['value']);
        
        // Low Stock is omitted
        $this->assertNull(collect($response->cards)->firstWhere('title', 'Low Stock Items'));
        
        $this->assertContains('Low stock metric is global and unavailable for user-restricted scopes.', $response->warnings);
        $this->assertFalse($response->metadata['failed_closed']); // overall it succeeds
        $this->assertEquals('partial_own_access_restriction', $response->metadata['reason']);
    }
}
