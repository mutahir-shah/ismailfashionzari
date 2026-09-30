<?php

namespace App\Services;

use DB;
use App\Models\Account;
use Carbon\Carbon;

class FinancialReportingService
{
    public function applyWarehouseFilter($query, $warehouseId)
    {
        if ($warehouseId) {
            $query->whereExists(function ($q) use ($warehouseId) {
                $q->select(DB::raw(1))
                  ->from('journal_entries as je_sub')
                  ->whereColumn('je_sub.id', 'journal_entries.id')
                  ->where(function ($q2) use ($warehouseId) {
                      $q2->where(function($q3) use ($warehouseId) {
                              $q3->where('je_sub.source_type', 'App\\Models\\Sale')
                                 ->whereExists(function($q4) use ($warehouseId) {
                                     $q4->select(DB::raw(1))->from('sales')->whereColumn('sales.id', 'je_sub.source_id')->where('sales.warehouse_id', $warehouseId);
                                 });
                          })
                         ->orWhere(function($q3) use ($warehouseId) {
                              $q3->where('je_sub.source_type', 'App\\Models\\Purchase')
                                 ->whereExists(function($q4) use ($warehouseId) {
                                     $q4->select(DB::raw(1))->from('purchases')->whereColumn('purchases.id', 'je_sub.source_id')->where('purchases.warehouse_id', $warehouseId);
                                 });
                          })
                         ->orWhere(function($q3) use ($warehouseId) {
                              $q3->where('je_sub.source_type', 'App\\Models\\Returns')
                                 ->whereExists(function($q4) use ($warehouseId) {
                                     $q4->select(DB::raw(1))->from('returns')->whereColumn('returns.id', 'je_sub.source_id')->where('returns.warehouse_id', $warehouseId);
                                 });
                          })
                         ->orWhere(function($q3) use ($warehouseId) {
                              $q3->where('je_sub.source_type', 'App\\Models\\ReturnPurchase')
                                 ->whereExists(function($q4) use ($warehouseId) {
                                     $q4->select(DB::raw(1))->from('return_purchases')->whereColumn('return_purchases.id', 'je_sub.source_id')->where('return_purchases.warehouse_id', $warehouseId);
                                 });
                          })
                         ->orWhere(function($q3) use ($warehouseId) {
                              $q3->where('je_sub.source_type', 'App\\Models\\Expense')
                                 ->whereExists(function($q4) use ($warehouseId) {
                                     $q4->select(DB::raw(1))->from('expenses')->whereColumn('expenses.id', 'je_sub.source_id')->where('expenses.warehouse_id', $warehouseId);
                                 });
                          });
                  });
            });
        }
        return $query;
    }

    /**
     * Get real-time aggregated account balances.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array|null $accountPrefixes Deprecated compatibility filter. New reporting callers should prefer $accountTypes.
     * @param int|null $warehouseId
     * @param array|null $accountTypes e.g. ['asset', 'liability'] or null for all
     * @return \Illuminate\Support\Collection
     */
    public function getAccountBalances($startDate = null, $endDate = null, $accountPrefixes = null, $warehouseId = null, $accountTypes = null)
    {
        $query = DB::table('journal_lines')
            ->join('accounting_accounts', 'journal_lines.accounting_account_id', '=', 'accounting_accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.code as account_no',
                'accounting_accounts.name',
                'accounting_accounts.account_type as type',
                'accounting_accounts.is_cash_account',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit')
            );

        if ($startDate) {
            $query->whereDate('journal_entries.entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('journal_entries.entry_date', '<=', $endDate);
        }

        if ($accountPrefixes) {
            $query->where(function ($q) use ($accountPrefixes) {
                foreach ($accountPrefixes as $prefix) {
                    $q->orWhere('accounting_accounts.code', 'like', $prefix . '%');
                }
            });
        }

        if ($accountTypes) {
            $query->whereIn('accounting_accounts.account_type', $accountTypes);
        }
        
        $this->applyWarehouseFilter($query, $warehouseId);

        $balances = $query->groupBy('accounting_accounts.id', 'accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.account_type', 'accounting_accounts.is_cash_account')
            ->orderBy('accounting_accounts.code')
            ->get();

        foreach ($balances as $balance) {
            if ($this->isDebitNormalBalance($balance)) {
                $balance->net_balance = $balance->total_debit - $balance->total_credit;
            } else {
                $balance->net_balance = $balance->total_credit - $balance->total_debit;
            }
        }

        return $balances;
    }

    /**
     * Calculate Net Profit (Current Year Earnings) for a given date range.
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $warehouseId
     * @return float
     */
    public function getCurrentYearEarnings($startDate = null, $endDate = null, $warehouseId = null)
    {
        $pnl = $this->getProfitAndLoss($startDate, $endDate, $warehouseId);
        return $pnl['net_profit'];
    }

    /**
     * Calculate Retained Earnings (Historical Revenues - Historical Expenses)
     * for all periods prior to the current fiscal year start.
     * 
     * @param string $currentFiscalYearStart
     * @param int|null $warehouseId
     * @return float
     */
    public function getRetainedEarnings($currentFiscalYearStart, $warehouseId = null)
    {
        // Get P&L for everything BEFORE the start date
        // Note: passing null for start date, and day before currentFiscalYearStart for end date
        $endDate = Carbon::parse($currentFiscalYearStart)->subDay()->toDateString();
        $pnl = $this->getProfitAndLoss(null, $endDate, $warehouseId);
        
        return $pnl['net_profit'];
    }

    /**
     * Get Profit and Loss Statement data.
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int|null $warehouseId
     * @return array
     */
    public function getProfitAndLoss($startDate = null, $endDate = null, $warehouseId = null)
    {
        $balances = $this->getAccountBalances($startDate, $endDate, null, $warehouseId, ['revenue', 'expense', 'cogs']);

        $revenues = [];
        $contraRevenues = [];
        $expenses = [];
        $costOfGoodsSold = [];

        $grossRevenue = 0;
        $totalContraRevenue = 0;
        $totalExpenses = 0;
        $totalCostOfGoodsSold = 0;

        foreach ($balances as $balance) {
            if ($this->isContraRevenueAccount($balance)) {
                $contraRevenues[] = $balance;
                $totalContraRevenue += $balance->net_balance;
            } elseif ($this->isAccountType($balance, ['revenue'])) {
                $revenues[] = $balance;
                $grossRevenue += $balance->net_balance;
            } elseif ($this->isAccountType($balance, ['cogs'])) {
                $costOfGoodsSold[] = $balance;
                $totalCostOfGoodsSold += $balance->net_balance;
            } elseif ($this->isAccountType($balance, ['expense'])) {
                $expenses[] = $balance;
                $totalExpenses += $balance->net_balance;
            }
        }

        $netRevenue = $grossRevenue - $totalContraRevenue;
        $grossProfit = $netRevenue - $totalCostOfGoodsSold;
        $netProfit = $grossProfit - $totalExpenses;
        $inventoryRelevant = DB::table('journal_lines')
            ->join('accounting_accounts', 'accounting_accounts.id', '=', 'journal_lines.accounting_account_id')
            ->where('accounting_accounts.code', '1200')->exists();
        $inventoryCloseRequired = $inventoryRelevant && !DB::table('periodic_inventory_closes')
            ->where('period_start', $startDate)->where('period_end', $endDate)
            ->where('scope', 'company')->whereIn('status', ['posted', 'zero'])->exists();

        return [
            'revenues' => $revenues,
            'contra_revenues' => $contraRevenues,
            'expenses' => $expenses,
            'cost_of_goods_sold' => $costOfGoodsSold,
            'gross_revenue' => $grossRevenue,
            'total_contra_revenue' => $totalContraRevenue,
            'net_revenue' => $netRevenue,
            'total_expenses' => $totalExpenses,
            'total_cost_of_goods_sold' => $totalCostOfGoodsSold,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'inventory_close_required' => $inventoryCloseRequired,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Get Balance Sheet data as of a specific date.
     * 
     * @param string $asOfDate
     * @param string $fiscalYearStart
     * @param int|null $warehouseId
     * @return array
     */
    public function getBalanceSheet($asOfDate, $fiscalYearStart, $warehouseId = null)
    {
        $balances = $this->getAccountBalances(null, $asOfDate, null, $warehouseId, ['asset', 'liability', 'equity']);

        $assets = [];
        $liabilities = [];
        $equities = [];

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($balances as $balance) {
            if ($this->isAccountType($balance, ['asset'])) {
                $assets[] = $balance;
                $totalAssets += $balance->net_balance;
            } elseif ($this->isAccountType($balance, ['liability'])) {
                $liabilities[] = $balance;
                $totalLiabilities += $balance->net_balance;
            } elseif ($this->isAccountType($balance, ['equity'])) {
                $equities[] = $balance;
                $totalEquity += $balance->net_balance;
            }
        }

        // Calculate Retained Earnings statically for anything before $fiscalYearStart
        $retainedEarningsValue = $this->getRetainedEarnings($fiscalYearStart, $warehouseId);
        
        // Add Retained Earnings to Equity block if it's non-zero
        if ($retainedEarningsValue != 0) {
            $totalEquity += $retainedEarningsValue;
        }

        // Calculate Current Year Earnings
        $currentYearEarnings = $this->getCurrentYearEarnings($fiscalYearStart, $asOfDate, $warehouseId);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'retained_earnings' => $retainedEarningsValue,
            'current_year_earnings' => $currentYearEarnings,
            'as_of_date' => $asOfDate,
        ];
    }

    public function validateBalanceSheet($asOfDate, $fiscalYearStart)
    {
        $bs = $this->getBalanceSheet($asOfDate, $fiscalYearStart);
        $assets = $bs['total_assets'];
        $liabilitiesAndEquity = $bs['total_liabilities'] + $bs['total_equity'] + $bs['current_year_earnings'];
        
        $variance = round($assets - $liabilitiesAndEquity, 2);
        return [
            'status' => $variance == 0 ? 'PASS' : 'FAIL',
            'calculated_value' => $assets,
            'expected_value' => $liabilitiesAndEquity,
            'variance' => $variance,
        ];
    }

    public function validateCurrentYearEarnings($startDate, $endDate)
    {
        $bs = $this->getBalanceSheet($endDate, $startDate);
        $pnl = $this->getProfitAndLoss($startDate, $endDate);
        
        $bsEarnings = $bs['current_year_earnings'];
        $pnlNetProfit = $pnl['net_profit'];
        
        $variance = round($bsEarnings - $pnlNetProfit, 2);
        return [
            'status' => $variance == 0 ? 'PASS' : 'FAIL',
            'calculated_value' => $bsEarnings,
            'expected_value' => $pnlNetProfit,
            'variance' => $variance,
        ];
    }

    public function validateRetainedEarnings($currentFiscalYearStart)
    {
        $bsRetainedEarnings = $this->getRetainedEarnings($currentFiscalYearStart);
        
        // Let's get ALL historical revenues and expenses before currentFiscalYearStart
        $endDate = Carbon::parse($currentFiscalYearStart)->subDay()->toDateString();
        $pnl = $this->getProfitAndLoss(null, $endDate);
        
        $variance = round($bsRetainedEarnings - $pnl['net_profit'], 2);
        return [
            'status' => $variance == 0 ? 'PASS' : 'FAIL',
            'calculated_value' => $bsRetainedEarnings,
            'expected_value' => $pnl['net_profit'],
            'variance' => $variance,
        ];
    }
    
    public function validateRetainedEarningsRollforward($currentFiscalYearStart, $previousFiscalYearStart)
    {
        // Opening RE = RE at the start of PREVIOUS fiscal year
        $openingRetainedEarnings = $this->getRetainedEarnings($previousFiscalYearStart);
        
        // Prior Year Net Income = P&L between previousFiscalYearStart and day before currentFiscalYearStart
        $priorYearEnd = Carbon::parse($currentFiscalYearStart)->subDay()->toDateString();
        $priorYearPnl = $this->getProfitAndLoss($previousFiscalYearStart, $priorYearEnd);
        $priorYearNetIncome = $priorYearPnl['net_profit'];
        
        // Calculated RE = Opening RE + Prior Year Net Income
        $expectedRE = $openingRetainedEarnings + $priorYearNetIncome;
        
        // Actual RE
        $actualRE = $this->getRetainedEarnings($currentFiscalYearStart);
        
        $variance = round($actualRE - $expectedRE, 2);
        return [
            'status' => $variance == 0 ? 'PASS' : 'FAIL',
            'calculated_value' => $actualRE,
            'expected_value' => $expectedRE,
            'variance' => $variance,
        ];
    }

    public function validateTrialBalanceConsistency($asOfDate, $fiscalYearStart)
    {
        // 1. Get Trial Balance
        $tbBalances = $this->getAccountBalances(null, $asOfDate);
        
        $tbAssets = 0;
        $tbLiabilities = 0;
        $tbEquity = 0;
        $tbRevenues = 0;
        $tbOperatingExpenses = 0;
        $tbCostOfGoodsSold = 0;
        
        foreach ($tbBalances as $balance) {
            if ($this->isAccountType($balance, ['asset'])) $tbAssets += $balance->net_balance;
            elseif ($this->isAccountType($balance, ['liability'])) $tbLiabilities += $balance->net_balance;
            elseif ($this->isAccountType($balance, ['equity'])) $tbEquity += $balance->net_balance;
            elseif ($this->isContraRevenueAccount($balance)) $tbRevenues -= $balance->net_balance;
            elseif ($this->isAccountType($balance, ['revenue'])) $tbRevenues += $balance->net_balance;
            elseif ($this->isAccountType($balance, ['cogs'])) $tbCostOfGoodsSold += $balance->net_balance;
            elseif ($this->isAccountType($balance, ['expense'])) $tbOperatingExpenses += $balance->net_balance;
        }

        // 2. Get BS
        $bs = $this->getBalanceSheet($asOfDate, $fiscalYearStart);
        $rawBsEquity = array_reduce($bs['equities'], fn($carry, $item) => $carry + $item->net_balance, 0);

        // 3. Get P&L for all time (since TB revenues/expenses are all time)
        $pnl = $this->getProfitAndLoss(null, $asOfDate);
        
        $varianceAssets = round($tbAssets - $bs['total_assets'], 2);
        $varianceLiabilities = round($tbLiabilities - $bs['total_liabilities'], 2);
        $varianceEquity = round($tbEquity - $rawBsEquity, 2);
        $varianceRevenue = round($tbRevenues - $pnl['net_revenue'], 2);
        $varianceOperatingExpenses = round($tbOperatingExpenses - $pnl['total_expenses'], 2);
        $varianceCostOfGoodsSold = round($tbCostOfGoodsSold - $pnl['total_cost_of_goods_sold'], 2);
        $varianceExpenses = round(
            ($tbOperatingExpenses + $tbCostOfGoodsSold)
            - ($pnl['total_expenses'] + $pnl['total_cost_of_goods_sold']),
            2
        );
        
        $totalVariance = abs($varianceAssets) + abs($varianceLiabilities) + abs($varianceEquity)
            + abs($varianceRevenue) + abs($varianceOperatingExpenses) + abs($varianceCostOfGoodsSold);

        return [
            'status' => $totalVariance == 0 ? 'PASS' : 'FAIL',
            'variances' => [
                'assets' => $varianceAssets,
                'liabilities' => $varianceLiabilities,
                'equity' => $varianceEquity,
                'revenue' => $varianceRevenue,
                'expenses' => $varianceExpenses,
                'operating_expenses' => $varianceOperatingExpenses,
                'cost_of_goods_sold' => $varianceCostOfGoodsSold,
            ],
            'variance' => $totalVariance,
        ];
    }

    public function getAccountCashFlowCategory($account)
    {
        if ($account->is_cash_account) {
            return 'Internal Transfer';
        }
        
        $type = strtolower($account->account_type ?? $account->type ?? '');

        // Operating Working Capital & P&L
        if (in_array($type, ['revenue', 'expense', 'cogs'])) {
            return 'Operating';
        }
        
        if (in_array($this->resolveAccountingAccountId($account), $this->operatingWorkingCapitalAccountIds(), true)) {
            return 'Operating';
        }

        // Investing: Non-current assets
        if ($type === 'asset') {
            return 'Investing';
        }

        // Financing: Equity, Long-term liabilities
        if ($type === 'equity' || $type === 'liability') {
            return 'Financing';
        }

        return 'Uncategorized';
    }

    public function isDebitNormalBalance($account): bool
    {
        $type = strtolower((string) ($account->account_type ?? $account->type ?? ''));

        if ($this->isContraRevenueAccount($account)) {
            return true;
        }

        return in_array($type, ['asset', 'expense', 'cogs'], true);
    }

    private function resolveAccountingAccountId($account): int
    {
        if (isset($account->accounting_account_id)) {
            return (int) $account->accounting_account_id;
        }

        if (isset($account->account_id)) {
            return (int) $account->account_id;
        }

        return (int) ($account->id ?? 0);
    }

    private function isAccountType($account, array $types): bool
    {
        return in_array(strtolower((string) ($account->account_type ?? $account->type ?? '')), $types, true);
    }

    private function isContraRevenueAccount($account): bool
    {
        $accountId = $this->resolveAccountingAccountId($account);
        $type = strtolower((string) ($account->account_type ?? $account->type ?? ''));

        return in_array($accountId, $this->semanticAccountIds([
            AccountingService::ROLE_SALES_RETURNS,
            AccountingService::ROLE_SALES_DISCOUNT,
        ]), true) || str_contains($type, 'contra');
    }

    private function operatingWorkingCapitalAccountIds(): array
    {
        return $this->semanticAccountIds([
            AccountingService::ROLE_ACCOUNTS_RECEIVABLE,
            AccountingService::ROLE_INVENTORY,
            AccountingService::ROLE_INPUT_VAT,
            AccountingService::ROLE_ACCOUNTS_PAYABLE,
            AccountingService::ROLE_CUSTOMER_DEPOSIT,
            AccountingService::ROLE_GIFT_CARD_LIABILITY,
            AccountingService::ROLE_REWARDS_LIABILITY,
            AccountingService::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE,
        ]);
    }

    private function semanticAccountIds(array $roles): array
    {
        $service = app(AccountingService::class);
        $ids = [];

        foreach ($roles as $role) {
            try {
                $id = $service->getRoleAccountId($role);
                if ($id) {
                    $ids[] = (int) $id;
                }
            } catch (\Throwable $e) {
                // Financial reports should still render when optional roles are not yet mapped;
                // certification/activation layers report the missing role explicitly.
            }
        }

        return array_values(array_unique($ids));
    }

    public function generateCashFlowStatement($startDate = null, $endDate = null, $warehouseId = null)
    {
        $cashAccountRows = $this->eligibleCashAccounts();
        $cashAccounts = $cashAccountRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($cashAccounts)) {
            return array_merge($this->emptyCashFlow(), [
                'cash_accounts' => collect(),
                'reconciliation_variance' => 0,
                'classification_fallbacks' => collect(),
                'invalid_cash_accounts' => $this->invalidCashAccounts(),
                'warehouse_filter_applied' => false,
                'warehouse_scope_warning' => (bool) $warehouseId,
            ]);
        }

        // Opening state consists of cash before the period plus any activation
        // journal inside the selected range. Activation establishes the ledger's
        // initial state and is never operating, investing, or financing activity.
        $openingCashQuery = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.accounting_account_id', $cashAccounts);

        $openingCash = 0;
        if ($startDate) {
            $openingCashQuery->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('journal_entries.entry_date', '<', $startDate)
                    ->orWhere(function ($opening) use ($startDate, $endDate) {
                        $opening->whereDate('journal_entries.entry_date', '>=', $startDate)
                            ->when($endDate, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $endDate))
                            ->where(fn ($q) => $q->where('journal_entries.source_type', 'activation')
                                ->orWhere('journal_entries.event_type', 'opening_balance'));
                    });
            });
            $openingCashLine = $openingCashQuery
                ->selectRaw('SUM(journal_lines.debit) as debits, SUM(journal_lines.credit) as credits')->first();
            $openingCash = ($openingCashLine->debits ?? 0) - ($openingCashLine->credits ?? 0);
        }

        // Fetch period entries touching eligible cash, excluding opening state.
        $journalEntryIdsQuery = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.accounting_account_id', $cashAccounts)
            ->where(fn ($query) => $query->whereNull('journal_entries.source_type')
                ->orWhere('journal_entries.source_type', '!=', 'activation'))
            ->where(fn ($query) => $query->whereNull('journal_entries.event_type')
                ->orWhere('journal_entries.event_type', '!=', 'opening_balance'))
            ->select('journal_lines.journal_entry_id')
            ->distinct();
            
        if ($startDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '>=', $startDate);
        if ($endDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '<=', $endDate);
        
        $entryIds = $journalEntryIdsQuery->pluck('journal_entry_id')->toArray();
        
        $operating = [];
        $investing = [];
        $financing = [];
        
        $netOperating = 0;
        $netInvesting = 0;
        $netFinancing = 0;
        $fallbacks = [];

        if (!empty($entryIds)) {
            $lines = DB::table('journal_lines')
                ->join('accounting_accounts', 'journal_lines.accounting_account_id', '=', 'accounting_accounts.id')
                ->whereIn('journal_lines.journal_entry_id', $entryIds)
                ->select('journal_lines.*', 'accounting_accounts.name', 'accounting_accounts.code', 'accounting_accounts.account_type', 'accounting_accounts.is_cash_account')
                ->get();

            $groupedLines = $lines->groupBy('journal_entry_id');

            foreach ($groupedLines as $entryId => $entryLines) {
                $netCashChange = 0;
                foreach ($entryLines as $line) {
                    if ($line->is_cash_account) {
                        $netCashChange += ($line->debit - $line->credit);
                    }
                }
                
                if (round($netCashChange, 4) == 0) {
                    continue; // Internal transfer
                }

                foreach ($entryLines as $line) {
                    if ($line->is_cash_account) continue;
                    
                    $category = $this->getAccountCashFlowCategory($line);
                    if (in_array(strtolower((string) $line->account_type), ['asset', 'liability', 'equity'], true)
                        && !in_array($this->resolveAccountingAccountId($line), $this->operatingWorkingCapitalAccountIds(), true)) {
                        $fallbacks[$line->accounting_account_id] = (object) [
                            'account_id' => (int) $line->accounting_account_id,
                            'account' => $line->name,
                            'category' => $category,
                        ];
                    }
                    $impact = $line->credit - $line->debit; // Increase in Non-Cash Credit means Cash inflow

                    if ($category === 'Operating') {
                        if (!isset($operating[$line->name])) $operating[$line->name] = 0;
                        $operating[$line->name] += $impact;
                        $netOperating += $impact;
                    } elseif ($category === 'Investing') {
                        if (!isset($investing[$line->name])) $investing[$line->name] = 0;
                        $investing[$line->name] += $impact;
                        $netInvesting += $impact;
                    } elseif ($category === 'Financing') {
                        if (!isset($financing[$line->name])) $financing[$line->name] = 0;
                        $financing[$line->name] += $impact;
                        $netFinancing += $impact;
                    }
                }
            }
        }
        
        $operating = array_filter($operating, fn($v) => round($v, 4) != 0);
        $investing = array_filter($investing, fn($v) => round($v, 4) != 0);
        $financing = array_filter($financing, fn($v) => round($v, 4) != 0);

        $mapToObjects = function($arr) {
            $res = [];
            foreach ($arr as $name => $amount) {
                $res[] = (object)['name' => $name, 'amount' => $amount];
            }
            return collect($res);
        };

        $netChange = $netOperating + $netInvesting + $netFinancing;
        $equationClosing = $openingCash + $netChange;
        $cashBreakdown = $this->cashBalancesAsOf($cashAccountRows, $endDate);
        $closingCash = (float) $cashBreakdown->sum('balance');

        return [
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'operating' => $mapToObjects($operating),
            'investing' => $mapToObjects($investing),
            'financing' => $mapToObjects($financing),
            'net_operating_cash' => $netOperating,
            'net_investing_cash' => $netInvesting,
            'net_financing_cash' => $netFinancing,
            'net_change_cash' => $netChange,
            'cash_accounts' => $cashBreakdown,
            'reconciliation_variance' => round($closingCash - $equationClosing, 4),
            'classification_fallbacks' => collect(array_values($fallbacks)),
            'invalid_cash_accounts' => $this->invalidCashAccounts(),
            // Cash is company-level because journal entries have no authoritative,
            // universal warehouse dimension (notably Payment and activation entries).
            'warehouse_filter_applied' => false,
            'warehouse_scope_warning' => (bool) $warehouseId,
        ];
    }

    private function eligibleCashAccounts()
    {
        return DB::table('accounting_accounts')
            ->where('is_cash_account', true)
            ->where('is_active', true)
            ->where('account_type', 'asset')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function invalidCashAccounts()
    {
        return DB::table('accounting_accounts')
            ->where('is_cash_account', true)
            ->where(fn ($query) => $query->where('is_active', false)->orWhere('account_type', '!=', 'asset'))
            ->get(['id', 'code', 'name', 'account_type', 'is_active']);
    }

    private function cashBalancesAsOf($cashAccounts, $endDate)
    {
        return $cashAccounts->map(function ($account) use ($endDate) {
            $query = DB::table('journal_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_lines.accounting_account_id', $account->id);
            if ($endDate) $query->whereDate('journal_entries.entry_date', '<=', $endDate);
            $totals = $query->selectRaw('SUM(journal_lines.debit) as debits, SUM(journal_lines.credit) as credits')->first();

            return (object) [
                'id' => (int) $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float) (($totals->debits ?? 0) - ($totals->credits ?? 0)),
            ];
        });
    }

    private function emptyCashFlow()
    {
        return [
            'opening_cash' => 0,
            'closing_cash' => 0,
            'operating' => collect(),
            'investing' => collect(),
            'financing' => collect(),
            'net_operating_cash' => 0,
            'net_investing_cash' => 0,
            'net_financing_cash' => 0,
            'net_change_cash' => 0,
            'cash_accounts' => collect(),
            'reconciliation_variance' => 0,
            'classification_fallbacks' => collect(),
            'invalid_cash_accounts' => collect(),
            'warehouse_filter_applied' => false,
            'warehouse_scope_warning' => false,
        ];
    }

    public function validateCashFlowReconciliation($startDate, $endDate)
    {
        $cf = $this->generateCashFlowStatement($startDate, $endDate);
        $calculatedClosing = $cf['opening_cash'] + $cf['net_change_cash'];
        $actualClosing = $cf['closing_cash'];
        $bsCashActual = (float) $this->cashBalancesAsOf($this->eligibleCashAccounts(), $endDate)->sum('balance');

        $varianceEquation = round($actualClosing - $calculatedClosing, 2);
        $varianceBS = round($actualClosing - $bsCashActual, 2);
        
        return [
            'status' => (abs($varianceEquation) <= 0.01
                && abs($varianceBS) <= 0.01
                && $cf['invalid_cash_accounts']->isEmpty()) ? 'PASS' : 'FAIL',
            'calculated_closing' => $actualClosing,
            'expected_equation' => $calculatedClosing,
            'expected_bs' => $bsCashActual,
            'variance_equation' => $varianceEquation,
            'variance_bs' => $varianceBS,
            'invalid_cash_accounts' => $cf['invalid_cash_accounts'],
            'classification_fallbacks' => $cf['classification_fallbacks'],
            'opening_activity_excluded' => true,
        ];
    }

    public function validateCashCoverage($startDate, $endDate)
    {
        $cashAccounts = DB::table('accounting_accounts')->where('is_cash_account', 1)->pluck('id')->toArray();
        if (empty($cashAccounts)) return ['status' => 'PASS', 'uncategorized' => []];

        $journalEntryIdsQuery = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.accounting_account_id', $cashAccounts)
            ->select('journal_lines.journal_entry_id')
            ->distinct();
            
        if ($startDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '>=', $startDate);
        if ($endDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '<=', $endDate);
        
        $entryIds = $journalEntryIdsQuery->pluck('journal_entry_id')->toArray();
        if (empty($entryIds)) return ['status' => 'PASS', 'uncategorized' => []];

        $lines = DB::table('journal_lines')
            ->join('accounting_accounts', 'journal_lines.accounting_account_id', '=', 'accounting_accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.journal_entry_id', $entryIds)
            ->select('journal_lines.*', 'accounting_accounts.name', 'accounting_accounts.code', 'accounting_accounts.account_type', 'accounting_accounts.is_cash_account', 'journal_entries.reference_no', 'journal_entries.entry_date')
            ->get();

        $groupedLines = $lines->groupBy('journal_entry_id');
        $uncategorized = [];

        foreach ($groupedLines as $entryId => $entryLines) {
            foreach ($entryLines as $line) {
                if ($line->is_cash_account) continue;
                $category = $this->getAccountCashFlowCategory($line);
                if ($category === 'Uncategorized') {
                    $uncategorized[] = [
                        'entry_id' => $entryId,
                        'reference_no' => $line->reference_no,
                        'date' => $line->entry_date,
                        'account' => $line->name . ' (' . $line->code . ')'
                    ];
                }
            }
        }

        return [
            'status' => count($uncategorized) === 0 ? 'PASS' : 'FAIL',
            'uncategorized' => $uncategorized
        ];
    }

    public function validateInternalTransfers($startDate, $endDate)
    {
        $cashAccounts = DB::table('accounting_accounts')->where('is_cash_account', 1)->pluck('id')->toArray();
        if (empty($cashAccounts)) return ['status' => 'PASS', 'failed_entries' => []];

        $journalEntryIdsQuery = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereIn('journal_lines.accounting_account_id', $cashAccounts)
            ->select('journal_lines.journal_entry_id')
            ->distinct();
            
        if ($startDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '>=', $startDate);
        if ($endDate) $journalEntryIdsQuery->whereDate('journal_entries.entry_date', '<=', $endDate);
        
        $entryIds = $journalEntryIdsQuery->pluck('journal_entry_id')->toArray();
        if (empty($entryIds)) return ['status' => 'PASS', 'failed_entries' => []];

        $lines = DB::table('journal_lines')
            ->join('accounting_accounts', 'journal_lines.accounting_account_id', '=', 'accounting_accounts.id')
            ->whereIn('journal_lines.journal_entry_id', $entryIds)
            ->select('journal_lines.*', 'accounting_accounts.is_cash_account')
            ->get();

        $groupedLines = $lines->groupBy('journal_entry_id');
        $failed = [];

        foreach ($groupedLines as $entryId => $entryLines) {
            $allCash = true;
            $netCashChange = 0;
            foreach ($entryLines as $line) {
                if (!$line->is_cash_account) {
                    $allCash = false;
                } else {
                    $netCashChange += ($line->debit - $line->credit);
                }
            }
            
            if ($allCash && round($netCashChange, 4) != 0) {
                $failed[] = $entryId;
            }
        }

        return [
            'status' => count($failed) === 0 ? 'PASS' : 'FAIL',
            'failed_entries' => $failed
        ];
    }
}
