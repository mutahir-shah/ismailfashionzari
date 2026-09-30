<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\FinancialReportingService;
use Carbon\Carbon;

class FinancialStatementTest extends TestCase
{
    use DatabaseTransactions;

    protected $service;
    protected $currentYearStart;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new FinancialReportingService();
        $this->currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        
        // Ensure test data avoids missing fields for relationships by using factories if available,
        // or just insert raw accounts/journals if model factories aren't fully set up.
        $this->seedAccounts();
    }

    private function seedAccounts()
    {
        // Setup minimal COA
        AccountingAccount::create(['code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'is_active' => true]);
        AccountingAccount::create(['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'is_active' => true]);
        AccountingAccount::create(['code' => '2210', 'name' => 'Customer Deposits', 'account_type' => 'liability', 'is_active' => true]);
        AccountingAccount::create(['code' => '2250', 'name' => 'Gift Card Liability', 'account_type' => 'liability', 'is_active' => true]);
        AccountingAccount::create(['code' => '3000', 'name' => 'Owner Equity', 'account_type' => 'equity', 'is_active' => true]);
        AccountingAccount::create(['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'revenue', 'is_active' => true]);
        AccountingAccount::create(['code' => '4150', 'name' => 'Sales Returns', 'account_type' => 'revenue', 'is_active' => true]);
        AccountingAccount::create(['code' => '4160', 'name' => 'Sales Discounts', 'account_type' => 'revenue', 'is_active' => true]);
        AccountingAccount::create(['code' => '5100', 'name' => 'Payroll Expense', 'account_type' => 'expense', 'is_active' => true]);
    }

    private function createJournalEntry($date, $lines)
    {
        static $sourceIdCounter = 1;
        $entry = JournalEntry::create([
            'reference_no' => 'JE-' . time() . rand(1000, 9999),
            'entry_date' => $date,
            'source_type' => 'Manual',
            'source_id' => $sourceIdCounter++,
            'event_type' => 'manual',
            'created_by' => 1,
            'warehouse_id' => 1,
        ]);

        foreach ($lines as $line) {
            $account = AccountingAccount::where('code', $line['account'])->first();
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'accounting_account_id' => $account->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ]);
        }
    }

    public function test_profit_and_loss_calculates_net_profit()
    {
        // 100 Revenue, 20 Expense
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 100, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 100],
        ]);
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '5100', 'debit' => 20, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 20],
        ]);

        $pnl = $this->service->getProfitAndLoss($this->currentYearStart, Carbon::now()->endOfYear()->format('Y-m-d'));
        
        $this->assertEquals(100, $pnl['gross_revenue']);
        $this->assertEquals(20, $pnl['total_expenses']);
        $this->assertEquals(80, $pnl['net_profit']);
    }

    public function test_sales_returns_reduce_revenue()
    {
        // 100 Revenue, 15 Return
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 100, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 100],
        ]);
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '4150', 'debit' => 15, 'credit' => 0], // Contra Revenue
            ['account' => '1000', 'debit' => 0, 'credit' => 15],
        ]);

        $pnl = $this->service->getProfitAndLoss($this->currentYearStart, Carbon::now()->endOfYear()->format('Y-m-d'));
        
        $this->assertEquals(100, $pnl['gross_revenue']);
        $this->assertEquals(15, $pnl['total_contra_revenue']);
        $this->assertEquals(85, $pnl['net_revenue']);
        $this->assertEquals(85, $pnl['net_profit']);
    }

    public function test_sales_discounts_reduce_revenue()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 100, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 100],
        ]);
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '4160', 'debit' => 10, 'credit' => 0], // Contra Revenue
            ['account' => '1000', 'debit' => 0, 'credit' => 10],
        ]);

        $pnl = $this->service->getProfitAndLoss($this->currentYearStart, Carbon::now()->endOfYear()->format('Y-m-d'));
        
        $this->assertEquals(100, $pnl['gross_revenue']);
        $this->assertEquals(10, $pnl['total_contra_revenue']);
        $this->assertEquals(90, $pnl['net_revenue']);
    }

    public function test_payroll_expense_appears_correctly()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '5100', 'debit' => 200, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 200],
        ]);

        $pnl = $this->service->getProfitAndLoss($this->currentYearStart, Carbon::now()->endOfYear()->format('Y-m-d'));
        $this->assertEquals(200, $pnl['total_expenses']);
        $this->assertEquals(-200, $pnl['net_profit']);
    }

    public function test_customer_deposits_appear_on_balance_sheet()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 500, 'credit' => 0],
            ['account' => '2210', 'debit' => 0, 'credit' => 500],
        ]);

        $bs = $this->service->getBalanceSheet(Carbon::now()->endOfYear()->format('Y-m-d'), $this->currentYearStart);
        $this->assertEquals(500, $bs['total_liabilities']);
    }

    public function test_gift_card_liability_appears_on_balance_sheet()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 300, 'credit' => 0],
            ['account' => '2250', 'debit' => 0, 'credit' => 300],
        ]);

        $bs = $this->service->getBalanceSheet(Carbon::now()->endOfYear()->format('Y-m-d'), $this->currentYearStart);
        $this->assertEquals(300, $bs['total_liabilities']);
    }

    public function test_current_year_earnings_equals_pnl_net_profit()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 1000, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 1000],
        ]);
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '5100', 'debit' => 400, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 400],
        ]);

        $pnl = $this->service->getProfitAndLoss($this->currentYearStart, Carbon::now()->endOfYear()->format('Y-m-d'));
        $bs = $this->service->getBalanceSheet(Carbon::now()->endOfYear()->format('Y-m-d'), $this->currentYearStart);
        
        $this->assertEquals(600, $pnl['net_profit']);
        $this->assertEquals(600, $bs['current_year_earnings']);
    }

    public function test_retained_earnings_excludes_current_year_activity()
    {
        // Previous year income
        $lastYear = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        $this->createJournalEntry($lastYear, [
            ['account' => '1000', 'debit' => 5000, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 5000],
        ]);
        
        // Current year income
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 1000, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 1000],
        ]);

        $bs = $this->service->getBalanceSheet(Carbon::now()->endOfYear()->format('Y-m-d'), $this->currentYearStart);
        
        $this->assertEquals(5000, $bs['retained_earnings']);
        $this->assertEquals(1000, $bs['current_year_earnings']);
    }

    public function test_balance_sheet_balances()
    {
        $lastYear = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
        // Historic Equity
        $this->createJournalEntry($lastYear, [
            ['account' => '1000', 'debit' => 10000, 'credit' => 0],
            ['account' => '3000', 'debit' => 0, 'credit' => 10000],
        ]);
        // Historic Profit
        $this->createJournalEntry($lastYear, [
            ['account' => '1000', 'debit' => 5000, 'credit' => 0],
            ['account' => '4000', 'debit' => 0, 'credit' => 5000],
        ]);
        // Current Year Liability
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 1000, 'credit' => 0],
            ['account' => '2000', 'debit' => 0, 'credit' => 1000],
        ]);
        // Current Year Expense
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '5100', 'debit' => 300, 'credit' => 0],
            ['account' => '1000', 'debit' => 0, 'credit' => 300],
        ]);

        $bs = $this->service->getBalanceSheet(Carbon::now()->endOfYear()->format('Y-m-d'), $this->currentYearStart);
        
        // Assets = 10000 + 5000 + 1000 - 300 = 15700
        $this->assertEquals(15700, $bs['total_assets']);
        
        // Liab = 1000
        $this->assertEquals(1000, $bs['total_liabilities']);
        
        // Equity = 10000 (Owner Equity) + 5000 (Retained Earnings) = 15000
        $this->assertEquals(15000, $bs['total_equity']);
        
        // Current Year Earnings = 0 (Rev) - 300 (Exp) = -300
        $this->assertEquals(-300, $bs['current_year_earnings']);
        
        // Assets = Liab + Equity + Current Year Earnings
        // 15700 = 1000 + 15000 - 300 = 15700
        $this->assertEquals($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'] + $bs['current_year_earnings']);
    }

    public function test_report_totals_agree_with_trial_balance_balances()
    {
        $this->createJournalEntry($this->currentYearStart, [
            ['account' => '1000', 'debit' => 10000, 'credit' => 0],
            ['account' => '3000', 'debit' => 0, 'credit' => 10000],
        ]);
        
        $tb = $this->service->getAccountBalances(null, Carbon::now()->endOfYear()->format('Y-m-d'));
        $totalDebits = $tb->sum('total_debit');
        $totalCredits = $tb->sum('total_credit');
        
        $this->assertEquals(10000, $totalDebits);
        $this->assertEquals(10000, $totalCredits);
    }
}
