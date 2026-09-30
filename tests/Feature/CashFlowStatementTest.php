<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use DB;

class CashFlowStatementTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'ChartOfAccountsSeeder']);
        $this->service = app(FinancialReportingService::class);
    }

    private function postJournal($reference, $date, $lines)
    {
        $entry = JournalEntry::create([
            'reference_no' => $reference,
            'entry_date' => $date,
            'event_type' => 'manual',
            'note' => 'Test',
        ]);

        foreach ($lines as $line) {
            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'accounting_account_id' => $line['account_id'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ]);
        }

        return $entry;
    }

    public function test_cash_flow_statement_operating_investing_financing()
    {
        $cash = AccountingAccount::where('code', '1300')->first()->id;
        $ar = AccountingAccount::where('code', '1100')->first()->id;
        $sales = AccountingAccount::where('code', '4100')->first()->id;
        $equipment = AccountingAccount::create(['code' => '1500', 'name' => 'Equipment', 'account_type' => 'asset', 'is_system' => false])->id;
        $loan = AccountingAccount::create(['code' => '2500', 'name' => 'Bank Loan', 'account_type' => 'liability', 'is_system' => false])->id;
        $equity = AccountingAccount::where('code', '3000')->first()->id;

        // 1. Opening balance on cash (Equity injection) -> Financing
        $this->postJournal('JE-001', Carbon::now()->subDays(10)->toDateString(), [
            ['account_id' => $cash, 'debit' => 10000, 'credit' => 0],
            ['account_id' => $equity, 'debit' => 0, 'credit' => 10000],
        ]);

        // 2. Cash Sale -> Operating Inflow
        $this->postJournal('JE-002', Carbon::now()->toDateString(), [
            ['account_id' => $cash, 'debit' => 500, 'credit' => 0],
            ['account_id' => $sales, 'debit' => 0, 'credit' => 500],
        ]);

        // 3. Buy Equipment with Cash -> Investing Outflow
        $this->postJournal('JE-003', Carbon::now()->toDateString(), [
            ['account_id' => $equipment, 'debit' => 2000, 'credit' => 0],
            ['account_id' => $cash, 'debit' => 0, 'credit' => 2000],
        ]);

        // 4. Get a loan (Cash in) -> Financing Inflow
        $this->postJournal('JE-004', Carbon::now()->toDateString(), [
            ['account_id' => $cash, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $loan, 'debit' => 0, 'credit' => 5000],
        ]);

        $cf = $this->service->generateCashFlowStatement(Carbon::now()->subDays(5)->toDateString(), Carbon::now()->addDays(5)->toDateString());

        // Opening cash should be 10000
        $this->assertEquals(10000, $cf['opening_cash']);
        
        // Operating = +500
        $this->assertEquals(500, $cf['net_operating_cash']);
        
        // Investing = -2000
        $this->assertEquals(-2000, $cf['net_investing_cash']);

        // Financing = +5000
        $this->assertEquals(5000, $cf['net_financing_cash']);

        // Net change = 500 - 2000 + 5000 = 3500
        $this->assertEquals(3500, $cf['net_change_cash']);

        // Closing cash = 10000 + 3500 = 13500
        $this->assertEquals(13500, $cf['closing_cash']);
    }

    public function test_internal_transfer_excluded()
    {
        $cash1 = AccountingAccount::where('code', '1300')->first()->id;
        $cash2 = AccountingAccount::create(['code' => '1301', 'name' => 'Petty Cash', 'account_type' => 'asset', 'is_cash_account' => true])->id;

        $this->postJournal('JE-TRF', Carbon::now()->toDateString(), [
            ['account_id' => $cash1, 'debit' => 0, 'credit' => 100],
            ['account_id' => $cash2, 'debit' => 100, 'credit' => 0],
        ]);

        $cf = $this->service->generateCashFlowStatement(Carbon::now()->subDays(1)->toDateString(), Carbon::now()->addDays(1)->toDateString());

        $this->assertEquals(0, $cf['net_operating_cash']);
        $this->assertEquals(0, $cf['net_investing_cash']);
        $this->assertEquals(0, $cf['net_financing_cash']);
        $this->assertEquals(0, $cf['net_change_cash']);
    }

    public function test_certification_layers()
    {
        // Add a mix of transactions
        $cash = AccountingAccount::where('code', '1300')->first()->id;
        $sales = AccountingAccount::where('code', '4100')->first()->id;
        
        $this->postJournal('JE-001', Carbon::now()->toDateString(), [
            ['account_id' => $cash, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $sales, 'debit' => 0, 'credit' => 1000],
        ]);

        $startDate = Carbon::now()->subDays(5)->toDateString();
        $endDate = Carbon::now()->addDays(5)->toDateString();

        $recon = $this->service->validateCashFlowReconciliation($startDate, $endDate);
        $this->assertEquals('PASS', $recon['status']);

        $coverage = $this->service->validateCashCoverage($startDate, $endDate);
        $this->assertEquals('PASS', $coverage['status']);

        $transfers = $this->service->validateInternalTransfers($startDate, $endDate);
        $this->assertEquals('PASS', $transfers['status']);
    }
}
