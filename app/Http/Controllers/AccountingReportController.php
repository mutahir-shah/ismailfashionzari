<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountingAccount;
use App\Models\JournalLine;
use App\Services\FinancialReportingService;
use DB;
use App\Models\PeriodicInventoryClose;
use App\Services\PeriodicInventoryCloseService;

class AccountingReportController extends Controller
{
    protected $reportingService;

    public function __construct(FinancialReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of', date('Y-m-d'));
        // For simplicity, assume fiscal year starts on Jan 1st of the asOfDate's year
        // In a real system, this might come from settings.
        $fiscalYearStart = date('Y-01-01', strtotime($asOfDate));
        $warehouseId = $request->input('warehouse_id');

        $data = $this->reportingService->getBalanceSheet($asOfDate, $fiscalYearStart, $warehouseId);
        
        $data['warehouses'] = \App\Models\Warehouse::where('is_active', true)->get();
        $data['warehouse_id'] = $warehouseId;

        return view('backend.accounting.balance_sheet', $data);
    }

    public function profitAndLoss(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-01-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $warehouseId = $request->input('warehouse_id');

        $data = $this->reportingService->getProfitAndLoss($startDate, $endDate, $warehouseId);
        
        $data['warehouses'] = \App\Models\Warehouse::where('is_active', true)->get();
        $data['warehouse_id'] = $warehouseId;

        return view('backend.accounting.profit_loss', $data);
    }

    public function trialBalance(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $warehouseId = $request->input('warehouse_id');

        $balances = $this->reportingService->getAccountBalances($startDate, $endDate, null, $warehouseId);

        $totalDebits = $balances->sum('total_debit');
        $totalCredits = $balances->sum('total_credit');
        $difference = $totalDebits - $totalCredits;
        
        $warehouses = \App\Models\Warehouse::where('is_active', true)->get();

        return view('backend.accounting.trial_balance', compact('balances', 'totalDebits', 'totalCredits', 'difference', 'startDate', 'endDate', 'warehouses', 'warehouseId'));
    }

    public function generalLedger(Request $request)
    {
        $accountId = $request->input('account_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $warehouseId = $request->input('warehouse_id');

        $accounts = AccountingAccount::where('is_active', true)->orderBy('code')->get();
        $warehouses = \App\Models\Warehouse::where('is_active', true)->get();
        $lines = [];
        $openingBalance = 0;
        $selectedAccount = null;

        if ($accountId) {
            $selectedAccount = AccountingAccount::where('is_active', true)->find($accountId);
            if (!$selectedAccount) {
                return redirect()->route('accounting.generalLedger')->with('not_permitted', __('db.account_not_found'));
            }
            $isDebitNormal = $this->reportingService->isDebitNormalBalance($selectedAccount);

            // Calculate opening balance
            if ($startDate) {
                $openingQuery = DB::table('journal_lines')
                    ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                    ->where('journal_lines.accounting_account_id', $accountId)
                    ->whereDate('journal_entries.entry_date', '<', $startDate);
                
                $this->reportingService->applyWarehouseFilter($openingQuery, $warehouseId);
                
                $openDebit = $openingQuery->sum('debit') ?: 0;
                $openCredit = $openingQuery->sum('credit') ?: 0;

                $openingBalance = $isDebitNormal ? ($openDebit - $openCredit) : ($openCredit - $openDebit);
            }

            // Get lines
            $linesQuery = DB::table('journal_lines')
                ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_lines.accounting_account_id', $accountId)
                ->select('journal_entries.entry_date as date', 'journal_entries.reference_no', 'journal_entries.event_type', 'journal_lines.description', 'journal_lines.debit', 'journal_lines.credit')
                ->orderBy('journal_entries.entry_date')
                ->orderBy('journal_entries.id');

            if ($startDate) {
                $linesQuery->whereDate('journal_entries.entry_date', '>=', $startDate);
            }
            if ($endDate) {
                $linesQuery->whereDate('journal_entries.entry_date', '<=', $endDate);
            }
            
            $this->reportingService->applyWarehouseFilter($linesQuery, $warehouseId);

            $lines = $linesQuery->get();

            // Calculate running balance
            $running = $openingBalance;
            foreach ($lines as $line) {
                if ($isDebitNormal) {
                    $running += $line->debit;
                    $running -= $line->credit;
                } else {
                    $running += $line->credit;
                    $running -= $line->debit;
                }
                $line->running_balance = $running;
            }
        }

        return view('backend.accounting.general_ledger', compact('accounts', 'selectedAccount', 'lines', 'openingBalance', 'startDate', 'endDate', 'warehouses', 'warehouseId'));
    }

    public function cashFlowStatement(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-01-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $warehouseId = $request->input('warehouse_id');

        $data = $this->reportingService->generateCashFlowStatement($startDate, $endDate, $warehouseId);
        
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['warehouses'] = \App\Models\Warehouse::where('is_active', true)->get();
        $data['warehouse_id'] = $warehouseId;

        return view('backend.accounting.cash_flow_statement', $data);
    }
}
