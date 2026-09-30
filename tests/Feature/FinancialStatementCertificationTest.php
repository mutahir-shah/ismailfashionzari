<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\AccountingAccount;
use App\Models\FinancialReportSnapshot;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class FinancialStatementCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $this->reportingService = app(FinancialReportingService::class);
    }

    private function createManualJournalEntry($date, $debitAccountId, $creditAccountId, $amount)
    {
        $entry = JournalEntry::create([
            'entry_date' => $date,
            'source_type' => 'Manual',
            'source_id' => rand(1000, 9999),
            'reference_no' => 'TEST-' . uniqid(),
            'notes' => 'Test entry',
            'event_type' => 'manual_entry'
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $debitAccountId,
            'debit' => $amount,
            'credit' => 0,
            'memo' => 'Debit'
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $amount,
            'memo' => 'Credit'
        ]);

        return $entry;
    }

    public function test_balanced_balance_sheet_passes_validation()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        $equityAccount = AccountingAccount::where('code', '3000')->first();

        $this->createManualJournalEntry(Carbon::now()->toDateString(), $assetAccount->id, $equityAccount->id, 5000);

        $result = $this->reportingService->validateBalanceSheet(Carbon::now()->toDateString(), Carbon::now()->startOfYear()->toDateString());
        
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals(5000, $result['calculated_value']);
        $this->assertEquals(5000, $result['expected_value']);
        $this->assertEquals(0, $result['variance']);
    }

    public function test_artificial_imbalance_fails_validation()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        
        // Artificially create an unbalanced journal entry directly to simulate data corruption
        $entry = JournalEntry::create([
            'entry_date' => Carbon::now()->toDateString(),
            'source_type' => 'Manual',
            'source_id' => rand(1000, 9999),
            'reference_no' => 'TEST-UNBALANCED',
            'notes' => 'Test entry',
            'event_type' => 'manual_entry'
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $assetAccount->id,
            'debit' => 5000,
            'credit' => 0,
            'memo' => 'Debit'
        ]);

        $result = $this->reportingService->validateBalanceSheet(Carbon::now()->toDateString(), Carbon::now()->startOfYear()->toDateString());
        
        $this->assertEquals('FAIL', $result['status']);
        $this->assertEquals(5000, $result['calculated_value']);
        $this->assertEquals(0, $result['expected_value']);
        $this->assertEquals(5000, $result['variance']);
    }

    public function test_current_year_earnings_equals_net_profit()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        $revenueAccount = AccountingAccount::where('code', '4000')->first();
        $expenseAccount = AccountingAccount::where('code', '5000')->first();

        // 5000 Revenue
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $assetAccount->id, $revenueAccount->id, 5000);
        
        // 2000 Expense
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $expenseAccount->id, $assetAccount->id, 2000);

        $result = $this->reportingService->validateCurrentYearEarnings(Carbon::now()->startOfYear()->toDateString(), Carbon::now()->toDateString());
        
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals(3000, $result['calculated_value']);
        $this->assertEquals(3000, $result['expected_value']);
        $this->assertEquals(0, $result['variance']);
    }

    public function test_retained_earnings_excludes_current_year_activity()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        $revenueAccount = AccountingAccount::where('code', '4000')->first();
        $expenseAccount = AccountingAccount::where('code', '5000')->first();

        $fiscalYearStart = Carbon::now()->startOfYear();
        $lastYear = $fiscalYearStart->copy()->subMonths(6);

        // Last Year: 5000 Revenue
        $this->createManualJournalEntry($lastYear->toDateString(), $assetAccount->id, $revenueAccount->id, 5000);
        
        // Current Year: 2000 Expense
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $expenseAccount->id, $assetAccount->id, 2000);

        $result = $this->reportingService->validateRetainedEarnings($fiscalYearStart->toDateString());
        
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals(5000, $result['calculated_value']);
        $this->assertEquals(5000, $result['expected_value']);
        $this->assertEquals(0, $result['variance']);
    }

    public function test_trial_balance_totals_equal_report_totals()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        $liabilityAccount = AccountingAccount::where('code', '2000')->first();
        $revenueAccount = AccountingAccount::where('code', '4000')->first();
        $expenseAccount = AccountingAccount::where('code', '5000')->first();

        $this->createManualJournalEntry(Carbon::now()->toDateString(), $assetAccount->id, $liabilityAccount->id, 10000);
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $assetAccount->id, $revenueAccount->id, 5000);
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $expenseAccount->id, $assetAccount->id, 2000);

        $result = $this->reportingService->validateTrialBalanceConsistency(Carbon::now()->toDateString(), Carbon::now()->startOfYear()->toDateString());
        
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals(0, $result['variance']);
    }
    
    public function test_retained_earnings_rollforward()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        $revenueAccount = AccountingAccount::where('code', '4000')->first();

        $currentFiscalYearStart = Carbon::now()->startOfYear();
        $previousFiscalYearStart = $currentFiscalYearStart->copy()->subYear();
        
        // Year - 2: 10000 Revenue (Opening RE)
        $this->createManualJournalEntry($previousFiscalYearStart->copy()->subMonths(6)->toDateString(), $assetAccount->id, $revenueAccount->id, 10000);

        // Year - 1: 5000 Revenue (Prior Year Net Income)
        $this->createManualJournalEntry($previousFiscalYearStart->copy()->addMonths(6)->toDateString(), $assetAccount->id, $revenueAccount->id, 5000);
        
        // Year 0 (Current): 2000 Revenue (Should not affect rollforward check for start of year)
        $this->createManualJournalEntry(Carbon::now()->toDateString(), $assetAccount->id, $revenueAccount->id, 2000);

        $result = $this->reportingService->validateRetainedEarningsRollforward($currentFiscalYearStart->toDateString(), $previousFiscalYearStart->toDateString());
        
        $this->assertEquals('PASS', $result['status']);
        $this->assertEquals(15000, $result['calculated_value']); // Total up to Year 0 start
        $this->assertEquals(15000, $result['expected_value']); // 10000 + 5000
        $this->assertEquals(0, $result['variance']);
    }

    public function test_snapshot_checksum_remains_unchanged()
    {
        $snapshot = new FinancialReportSnapshot([
            'report_type' => 'balance_sheet',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'metadata' => ['total_assets' => 5000, 'total_liabilities' => 2000, 'total_equity' => 3000]
        ]);

        $checksum1 = $snapshot->generateChecksum();
        
        // It should be deterministic
        $checksum2 = $snapshot->generateChecksum();
        $this->assertEquals($checksum1, $checksum2);
        
        // Modifying data should change checksum
        $snapshot->metadata = ['total_assets' => 5000, 'total_liabilities' => 1000, 'total_equity' => 4000];
        $checksum3 = $snapshot->generateChecksum();
        $this->assertNotEquals($checksum1, $checksum3);
    }

    public function test_certification_command_exits_non_zero_on_critical_failure()
    {
        $assetAccount = AccountingAccount::where('code', '1300')->first();
        
        // Create an unbalanced journal entry
        $entry = JournalEntry::create([
            'entry_date' => Carbon::now()->toDateString(),
            'source_type' => 'Manual',
            'source_id' => rand(1000, 9999),
            'reference_no' => 'TEST-UNBALANCED',
            'notes' => 'Test entry',
            'event_type' => 'manual_entry'
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'accounting_account_id' => $assetAccount->id,
            'debit' => 5000,
            'credit' => 0,
            'memo' => 'Debit'
        ]);

        $exitCode = Artisan::call('accounting:certify');
        
        // Because there's a critical failure (unbalanced journal AND BS out of balance)
        $this->assertEquals(1, $exitCode);
    }
}

