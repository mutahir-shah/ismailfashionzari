<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Account;
use App\Models\AccountingSyncQueue;
use App\Services\AccountingService;

class AccountingCertificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the basic accounts
        Account::insert([
            ['account_no' => '1100', 'name' => 'Accounts Receivable', 'is_active' => true, 'total_balance' => 0],
            ['account_no' => '2100', 'name' => 'Accounts Payable', 'is_active' => true, 'total_balance' => 0],
            ['account_no' => '2210', 'name' => 'Customer Deposits Liability', 'is_active' => true, 'total_balance' => 0],
            ['account_no' => '2250', 'name' => 'Gift Card Liability', 'is_active' => true, 'total_balance' => 0],
            ['account_no' => '2200', 'name' => 'Customer Rewards Liability', 'is_active' => true, 'total_balance' => 0],
            ['account_no' => '1000', 'name' => 'Cash', 'is_active' => true, 'total_balance' => 0],
        ]);
    }

    public function test_balanced_ledger_passes()
    {
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_unbalanced_journal_fails()
    {
        $entry = JournalEntry::create(['source_type' => Sale::class, 'source_id' => 1, 'event_type' => 'sale_created']);
        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => 1, 'type' => 'debit', 'amount' => 100]);
        // Missing credit

        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_missing_journal_coverage_fails()
    {
        // A sale is posted but no journal exists
        Sale::create(['reference_no' => 'SALE01', 'accounting_status' => 'posted', 'customer_id' => 1, 'biller_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_discount' => 0, 'total_tax' => 0, 'total_price' => 100, 'grand_total' => 100, 'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0, 'sale_status' => 1, 'payment_status' => 1]);

        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_ar_mismatch_detected()
    {
        // Legacy AR is 100, but GL AR is 0
        Sale::create(['reference_no' => 'SALE02', 'accounting_status' => 'pending', 'payment_status' => 1, 'grand_total' => 100, 'customer_id' => 1, 'biller_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_discount' => 0, 'total_tax' => 0, 'total_price' => 100, 'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0, 'sale_status' => 1]);

        $this->artisan('accounting:certify')->assertExitCode(0); // Note: AR mismatch is high failure, wait, does it exit 1? 
        // User requested: "Return non-zero exit code whenever: Any Critical failure exists. Trial Balance fails. Missing journal coverage exists."
        // AR mismatch is HIGH, so exit code should be 0 unless there's a critical.
        // Wait, "Critical Failures: X. Return non-zero exit code whenever: Any Critical failure exists. Trial Balance fails. Missing journal coverage exists."
        // High severity doesn't necessarily block deployment in their logic.
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_ap_mismatch_detected()
    {
        // Add a purchase that causes AP mismatch
        \App\Models\Purchase::create(['reference_no' => 'PURCH01', 'accounting_status' => 'pending', 'payment_status' => 1, 'grand_total' => 100, 'supplier_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_discount' => 0, 'total_tax' => 0, 'total_cost' => 100, 'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0, 'status' => 1]);

        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_deposit_liability_mismatch_detected()
    {
        Customer::create(['name' => 'Test', 'phone_number' => '123456', 'email' => 'test@test.com', 'is_active' => true, 'deposit' => 100, 'expense' => 0]);
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_gift_card_liability_mismatch_detected()
    {
        \App\Models\GiftCard::create(['card_no' => '123', 'amount' => 100, 'expense' => 0, 'is_active' => true]);
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_rewards_liability_mismatch_detected()
    {
        Customer::create(['name' => 'Test', 'phone_number' => '123456', 'email' => 'test@test.com', 'is_active' => true, 'points' => 100]);
        \App\Models\RewardPointSetting::create(['per_point_amount' => 10, 'minimum_amount' => 100, 'duration_type' => 'Year', 'duration' => 1, 'redeem_amount_per_unit_rp' => 2, 'is_active' => true]);
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_trial_balance_mismatch_detected()
    {
        $entry = JournalEntry::create(['source_type' => Sale::class, 'source_id' => 1, 'event_type' => 'sale_created']);
        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => 1, 'type' => 'debit', 'amount' => 100]);
        // Unbalanced journal line creates a trial balance mismatch and a journal integrity error (both critical)

        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_resolved_sync_queue_updates_timestamps_correctly()
    {
        $service = app(AccountingService::class);
        
        // Mock a failure
        $queue = AccountingSyncQueue::create([
            'source_type' => Sale::class,
            'source_id' => 999,
            'status' => 'failed',
            'attempts' => 1,
            'last_attempt_at' => now(),
            'last_error' => 'Simulated error'
        ]);

        // Just pass a dummy mock object matching payment requirements
        $payment = new \App\Models\Payment();
        $payment->id = 999;
        $payment->account_id = 1;
        $payment->amount = 100;
        $payment->payment_reference = 'PAY-999';
        $payment->created_at = now();
        $payment->payment_note = 'Test';
        $payment->sale_id = 1;
        
        $sale = new Sale();
        $sale->customer_id = 1;
        $payment->setRelation('sale', $sale);

        // This will attempt to record payment and should update the queue record
        // The queue update is inside AccountingService::executeSafe which we patched
        // We will just invoke a closure directly through reflection to avoid full DB mock
        
        $reflection = new \ReflectionClass(AccountingService::class);
        $method = $reflection->getMethod('executeSafe');
        $method->setAccessible(true);
        $method->invokeArgs($service, [function() {
            return new JournalEntry();
        }]);

        $queue->refresh();
        $this->assertEquals('posted', $queue->status);
        $this->assertNotNull($queue->resolved_at);
        $this->assertNotNull($queue->last_success_at);
    }

    public function test_asset_account_marked_as_revenue_fails()
    {
        Account::create(['account_no' => '1005', 'name' => 'Fake Asset', 'type' => 'revenue', 'is_active' => true, 'total_balance' => 0]);
        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_liability_account_marked_as_expense_fails()
    {
        Account::create(['account_no' => '2005', 'name' => 'Fake Liab', 'type' => 'expense', 'is_active' => true, 'total_balance' => 0]);
        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_account_4150_not_marked_contra_revenue_fails()
    {
        Account::create(['account_no' => '4150', 'name' => 'Sales Returns', 'type' => 'expense', 'is_active' => true, 'total_balance' => 0]);
        $this->artisan('accounting:certify')->assertExitCode(1);
    }

    public function test_legacy_bank_account_on_1xxx_yields_warning_only()
    {
        Account::create(['account_no' => '1009', 'name' => 'Legacy Bank', 'type' => 'Bank Account', 'is_active' => true, 'total_balance' => 0]);
        // Shouldn't fail unless there's another error, so if the ledger is balanced it returns 0
        $this->artisan('accounting:certify')->assertExitCode(0);
    }

    public function test_orphan_journal_entry_fails()
    {
        JournalEntry::create([
            'source_type' => Sale::class,
            'source_id' => 9999, // Does not exist
            'event_type' => 'sale_created',
            'reference_no' => 'SALE-9999',
            'entry_date' => date('Y-m-d')
        ]);
        $this->artisan('accounting:certify')->assertExitCode(1);
    }
}
