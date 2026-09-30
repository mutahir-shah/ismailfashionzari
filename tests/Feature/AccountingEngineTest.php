<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\AccountingPeriod;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Services\JournalBuilder;
use App\Services\AccountingService;
use App\Exceptions\UnbalancedJournalException;
use App\Exceptions\ClosedPeriodException;
use App\Exceptions\DuplicateJournalException;

class AccountingEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    public function test_journal_must_balance()
    {
        $this->expectException(UnbalancedJournalException::class);

        $cashId = AccountingAccount::where('code', '1300')->first()->id;
        $revenueId = AccountingAccount::where('code', '4100')->first()->id;

        JournalBuilder::create()
            ->setSource('TestEvent', 1)
            ->setEventType('test')
            ->setReference('TEST-001')
            ->addDebit($cashId, 100)
            ->addCredit($revenueId, 90) // Unbalanced!
            ->save();
    }

    public function test_successful_balanced_journal()
    {
        $cashId = AccountingAccount::where('code', '1300')->first()->id;
        $revenueId = AccountingAccount::where('code', '4100')->first()->id;

        $entry = JournalBuilder::create()
            ->setSource('TestEvent', 2)
            ->setEventType('test_balanced')
            ->setReference('TEST-002')
            ->addDebit($cashId, 100)
            ->addCredit($revenueId, 100)
            ->save();

        $this->assertDatabaseHas('journal_entries', ['reference_no' => 'TEST-002']);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $entry->id, 'debit' => '100.0000', 'credit' => '0.0000']);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $entry->id, 'debit' => '0.0000', 'credit' => '100.0000']);
    }

    public function test_period_locking()
    {
        AccountingPeriod::create([
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'is_closed' => true,
        ]);

        $this->expectException(ClosedPeriodException::class);

        $cashId = AccountingAccount::where('code', '1300')->first()->id;
        $revenueId = AccountingAccount::where('code', '4100')->first()->id;

        JournalBuilder::create()
            ->setSource('TestEvent', 3)
            ->setEventType('test_period')
            ->setDate('2026-01-15') // Inside closed period
            ->addDebit($cashId, 50)
            ->addCredit($revenueId, 50)
            ->save();
    }

    public function test_idempotency_prevents_duplicate_journals()
    {
        $cashId = AccountingAccount::where('code', '1300')->first()->id;
        $revenueId = AccountingAccount::where('code', '4100')->first()->id;

        // First save should work
        JournalBuilder::create()
            ->setSource('App\Models\Sale', 99)
            ->setEventType('sale_created')
            ->addDebit($cashId, 100)
            ->addCredit($revenueId, 100)
            ->save();

        $this->expectException(DuplicateJournalException::class);

        // Second save of exact same event should fail
        JournalBuilder::create()
            ->setSource('App\Models\Sale', 99)
            ->setEventType('sale_created')
            ->addDebit($cashId, 100)
            ->addCredit($revenueId, 100)
            ->save();
    }

    public function test_reversal_logic()
    {
        $cashId = AccountingAccount::where('code', '1300')->first()->id;
        $revenueId = AccountingAccount::where('code', '4100')->first()->id;

        $entry = JournalBuilder::create()
            ->setSource('App\Models\Sale', 101)
            ->setEventType('sale_created')
            ->setReference('SALE-101')
            ->addDebit($cashId, 250)
            ->addCredit($revenueId, 250)
            ->save();

        $service = new AccountingService();
        $result = $service->reverseTransaction('App\Models\Sale', 101);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'App\Models\Sale',
            'source_id' => 101,
            'event_type' => 'sale_created_reversed',
        ]);

        $reversalEntry = JournalEntry::where('event_type', 'sale_created_reversed')->first();
        
        // Original: DR Cash, CR Revenue
        // Reversal: CR Cash, DR Revenue
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $reversalEntry->id,
            'accounting_account_id' => $cashId,
            'debit' => '0.0000',
            'credit' => '250.0000',
        ]);

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $reversalEntry->id,
            'accounting_account_id' => $revenueId,
            'debit' => '250.0000',
            'credit' => '0.0000',
        ]);
    }
}
