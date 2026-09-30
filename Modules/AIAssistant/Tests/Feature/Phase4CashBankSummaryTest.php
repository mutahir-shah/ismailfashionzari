<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Income;
use App\Models\MoneyTransfer;

class Phase4CashBankSummaryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function createAccount($initialBalance): Account
    {
        return Account::create([
            'account_no' => 'ACC' . uniqid(),
            'name'       => 'Test Account ' . uniqid(),
            'initial_balance' => $initialBalance,
            'total_balance' => $initialBalance,
            'note'       => 'Test',
            'is_default' => false,
            'is_active'  => true,
        ]);
    }

    private function mockSkill(array $businessContext = [])
    {
        $skill = new \Modules\AIAssistant\Skills\CashBankSummarySkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'cash bank summary',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: $businessContext,
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    // 1. Unrestricted/admin result
    public function test_cash_bank_summary_unrestricted()
    {
        $acc = $this->createAccount(1000);
        
        // Credits
        Payment::create(['payment_reference' => 'P1', 'user_id' => 1, 'sale_id' => 1, 'account_id' => $acc->id, 'amount' => 500, 'change' => 0, 'paying_method' => 'Cash']);
        Income::create(['reference_no' => 'INC1', 'income_category_id' => 1, 'account_id' => $acc->id, 'user_id' => 1, 'amount' => 200]);
        
        // Debits
        Expense::create(['reference_no' => 'EXP1', 'expense_category_id' => 1, 'warehouse_id' => 1, 'account_id' => $acc->id, 'user_id' => 1, 'amount' => 300]);
        
        // Balance = 1000 + 500 + 200 - 300 = 1400

        $response = $this->mockSkill(); // No restriction
        $tableRow = collect($response->table['rows'])->firstWhere('0', $acc->name . ' (' . $acc->account_no . ')');
        
        $this->assertNotNull($tableRow);
        $this->assertEquals(1400, $tableRow[1]);
        $this->assertFalse($response->metadata['failed_closed']);
    }

    // 2. own access fails closed
    public function test_cash_bank_summary_fails_closed_for_own_access()
    {
        $response = $this->mockSkill(['own_user_id' => 1]);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('own_access_restriction', $response->metadata['reason']);
        $this->assertContains('Account balances are global. Warehouse or user-restricted scopes are not permitted to view this data.', $response->warnings);
        $this->assertEmpty($response->table);
    }

    // 3. explicit empty warehouse scope fails closed
    public function test_explicit_empty_warehouse_scope_fails_closed()
    {
        $response = $this->mockSkill(['warehouse_ids' => []]);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('empty_warehouse_scope', $response->metadata['reason']);
        $this->assertContains('No warehouse access is available for this request.', $response->warnings);
        $this->assertEmpty($response->table);
    }

    // 4. non-empty assigned warehouse scope fails closed
    public function test_non_empty_warehouse_scope_fails_closed()
    {
        $response = $this->mockSkill(['warehouse_ids' => [1]]);
        $this->assertEquals(0, $response->cards[1]['value']);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('global_data_restriction', $response->metadata['reason']);
        $this->assertContains('Account balances are global. Warehouse or user-restricted scopes are not permitted to view this data.', $response->warnings);
        $this->assertEmpty($response->table);
    }
}
