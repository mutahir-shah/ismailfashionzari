<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;

class Phase4ExpenseSummaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function createExpenseCategory($name): ExpenseCategory
    {
        return ExpenseCategory::create([
            'code' => 'EC' . uniqid(),
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function mockSkill(array $businessContext = [])
    {
        $skill = new \Modules\AIAssistant\Skills\ExpenseSummarySkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'expense summary',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: $businessContext,
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    public function test_expense_summary_aggregates_todays_expenses()
    {
        $cat1 = $this->createExpenseCategory('Office');
        $cat2 = $this->createExpenseCategory('Travel');
        
        // Today
        Expense::create(['reference_no' => 'E1', 'expense_category_id' => $cat1->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 1, 'amount' => 150, 'created_at' => Carbon::today()]);
        Expense::create(['reference_no' => 'E2', 'expense_category_id' => $cat2->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 1, 'amount' => 200, 'created_at' => Carbon::today()]);
        
        // Yesterday
        Expense::create(['reference_no' => 'E3', 'expense_category_id' => $cat1->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 1, 'amount' => 300, 'created_at' => Carbon::yesterday()]);

        $response = $this->mockSkill();
        
        $this->assertEquals(350, $response->cards[1]['value']); // 150 + 200
        $this->assertEquals(2, $response->cards[0]['value']);
        $this->assertFalse($response->metadata['failed_closed']);
        
        $tableCat1 = collect($response->table['rows'])->firstWhere('0', $cat1->name);
        $this->assertEquals(150, $tableCat1[1]);
    }

    public function test_expense_summary_restricts_by_own_access()
    {
        $cat1 = $this->createExpenseCategory('Office');
        
        // User 1
        Expense::create(['reference_no' => 'E1', 'expense_category_id' => $cat1->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 1, 'amount' => 150, 'created_at' => Carbon::today()]);
        // User 2
        Expense::create(['reference_no' => 'E2', 'expense_category_id' => $cat1->id, 'warehouse_id' => 1, 'account_id' => 1, 'user_id' => 2, 'amount' => 500, 'created_at' => Carbon::today()]);

        $response = $this->mockSkill(['own_user_id' => 1]);
        
        $this->assertEquals(150, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['own_user_id'] === 1);
        $this->assertFalse($response->metadata['failed_closed']);
    }

    // 1. Expense explicit empty scope must include a warning and zero results.
    public function test_expense_explicit_empty_scope_warning()
    {
        $response = $this->mockSkill(['warehouse_ids' => []]);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('empty_warehouse_scope', $response->metadata['reason']);
        $this->assertContains('No warehouse access is available for this request.', $response->warnings);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertEmpty($response->table);
    }
}
