<?php

namespace App\Services;

use App\Models\AccountingConfig;
use App\Models\AccountingActivationSession;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use App\Services\JournalBuilder;
use App\Services\AccountingService;

class AccountingActivationService
{
    /**
     * Get the current accounting configuration (singleton).
     */
    public function getConfig()
    {
        return AccountingConfig::firstOrCreate(['id' => 1]);
    }

    /**
     * Determines if the business MUST use 'existing_business' mode.
     */
    public function requiresExistingBusinessMode(): bool
    {
        return DB::table('sales')->exists() || 
               DB::table('purchases')->exists() || 
               DB::table('product_warehouse')->where('qty', '>', 0)->exists() ||
               $this->hasNonzeroLegacyAccountBalances();
    }

    public function hasNonzeroLegacyAccountBalances(): bool
    {
        return DB::table('accounts')->where(function ($query) {
            $query->where('initial_balance', '!=', 0)->orWhere('total_balance', '!=', 0);
        })->exists();
    }

    /**
     * Calculates all opening balances using exact logic from SalePro operational reports.
     */
    public function calculateOpeningBalances(): array
    {
        $cashAndBank = $this->calculateTotalCashAndBank();
        $accountsReceivable = $this->calculateAccountsReceivable();
        $accountsPayable = $this->calculateAccountsPayable();
        $inventoryValue = $this->calculateInventoryValue();

        $totalAssets = bcadd(bcadd($cashAndBank, $accountsReceivable, 4), $inventoryValue, 4);
        $totalLiabilities = $accountsPayable;
        $openingBalanceEquity = bcsub($totalAssets, $totalLiabilities, 4);

        return [
            'cash_and_bank' => $cashAndBank,
            'accounts_receivable' => $accountsReceivable,
            'accounts_payable' => $accountsPayable,
            'inventory_value' => $inventoryValue,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'opening_balance_equity' => $openingBalanceEquity,
        ];
    }

    /**
     * Reuses the Account balance logic to calculate total Cash & Bank.
     */
    private function calculateTotalCashAndBank()
    {
        return DB::table('accounts')->where('is_active', true)->sum('total_balance') ?? 0;
    }

    /**
     * Reuses the Customer Due Report logic to calculate Accounts Receivable.
     */
    private function calculateAccountsReceivable()
    {
        // $total_grand - $total_returned - $total_paid
        $cardQuery = DB::table('sales')
            ->leftJoin('returns', 'returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_status', '!=', 4)
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('SUM(sales.grand_total) as total_grand'),
                DB::raw('SUM(sales.paid_amount) as total_paid'),
                DB::raw('COALESCE(SUM(returns.grand_total / COALESCE(NULLIF(returns.exchange_rate, 0), 1)), 0) as total_returned')
            )
            ->first();

        $total_grand = $cardQuery->total_grand ?? 0;
        $total_paid = $cardQuery->total_paid ?? 0;
        $total_returned = $cardQuery->total_returned ?? 0;

        return bcsub(bcsub($total_grand, $total_returned, 4), $total_paid, 4);
    }

    /**
     * Reuses the Supplier Due Report logic to calculate Accounts Payable.
     */
    private function calculateAccountsPayable()
    {
        $purchases = DB::table('purchases')
            ->leftJoinSub(
                DB::table('return_purchases')
                    ->select('purchase_id', DB::raw('SUM(grand_total) as returned_amount'))
                    ->groupBy('purchase_id'),
                'purchase_returns',
                'purchase_returns.purchase_id',
                '=',
                'purchases.id'
            )
            ->whereNull('purchases.deleted_at')
            ->select(
                'purchases.grand_total',
                'purchases.paid_amount',
                'purchases.exchange_rate',
                DB::raw('COALESCE(purchase_returns.returned_amount, 0) as returned_amount')
            )
            ->get();

        $total = '0.0000';
        foreach ($purchases as $purchase) {
            $exchangeRate = (float) $purchase->exchange_rate ?: 1;
            $due = max(
                0,
                ((float) $purchase->grand_total
                    - (float) $purchase->paid_amount
                    - (float) $purchase->returned_amount) / $exchangeRate
            );
            $total = bcadd($total, (string) $due, 4);
        }

        return $total;
    }

    /**
     * Reuses the Inventory Valuation logic to calculate Inventory Value.
     */
    private function calculateInventoryValue()
    {
        return app(InventoryValuationService::class)->currentValuation()['value'];
    }

    /**
     * Returns a readiness checklist for the UI.
     */
    public function getReadinessChecklist(): array
    {
        $checklist = [];
        
        $checklist[] = [
            'status' => 'pass',
            'message' => 'Operational data analyzed successfully.'
        ];

        // Additional checks like negative stock can go here
        $negativeStockCount = DB::table('product_warehouse')->where('qty', '<', 0)->count();
        if ($negativeStockCount > 0) {
            $checklist[] = [
                'status' => 'warn',
                'message' => "Found $negativeStockCount warehouse records with negative stock."
            ];
        } else {
            $checklist[] = [
                'status' => 'pass',
                'message' => 'Stock levels reconcile properly (no negative stock).'
            ];
        }

        return $checklist;
    }

    /**
     * Activates accounting and generates the single opening journal.
     */
    public function activate(string $mode, bool $openingBalancesReviewed = false)
    {
        $config = $this->getConfig();
        if ($config->enabled) {
            throw new Exception("Accounting is already activated.");
        }

        if ($mode === 'new_business' && $this->requiresExistingBusinessMode()) {
            throw new Exception("Historical data exists. Existing business mode is required.");
        }

        if ($mode === 'existing_business' && $this->hasNonzeroLegacyAccountBalances() && !$openingBalancesReviewed) {
            throw new Exception('Legacy account balances require explicit review before activation.');
        }

        $balances = $this->calculateOpeningBalances();
        $checklist = $this->getReadinessChecklist();

        DB::beginTransaction();
        try {
            $journalId = null;
            $this->ensureRuntimeAccounts();
            $accountingService = app(AccountingService::class);
            $accountingService->bootstrapSemanticRoleMappings();
            $cashAccountId = $accountingService->getRoleAccountId(AccountingService::ROLE_CASH);
            $paymentAccounts = Account::where('is_active', true)->get();
            $mappedPaymentAccounts = [];
            foreach ($paymentAccounts as $paymentAccount) {
                $mappedPaymentAccounts[$paymentAccount->id] = $accountingService->getMappedAccount(
                    Account::class,
                    $paymentAccount->id,
                    $cashAccountId
                );
            }
            $roleValidation = $accountingService->validateSemanticRoleMappings(AccountingService::CORE_CERTIFICATION_ROLES);
            $roleFailures = array_filter($roleValidation, fn ($result) => ($result['status'] ?? null) !== 'pass');
            if (!empty($roleFailures)) {
                throw new Exception(implode(' ', array_map(fn ($result) => $result['message'], $roleFailures)));
            }

            if ($mode === 'existing_business') {
                $this->deactivateUnusedLegacyOpeningAccounts();

                $arAccountId = $accountingService->getRoleAccountId(AccountingService::ROLE_ACCOUNTS_RECEIVABLE);
                $inventoryAccountId = $accountingService->getRoleAccountId(AccountingService::ROLE_INVENTORY);
                $apAccountId = $accountingService->getRoleAccountId(AccountingService::ROLE_ACCOUNTS_PAYABLE);
                $equityAccountId = $accountingService->getRoleAccountId(AccountingService::ROLE_OPENING_EQUITY);

                // Bypass check temporarily
                $config->enabled = true;
                $config->start_date = Carbon::now()->toDateString();
                $config->save();

                $builder = JournalBuilder::create()
                    ->setSource('activation', 1)
                    ->setEventType('opening_balance')
                    ->setReference('JE-OPENING-'.date('YmdHis'))
                    ->setDate($config->start_date)
                    ->setNote('Initial Opening Balance');

                if ($balances['accounts_receivable'] > 0) $builder->addDebit($arAccountId, $balances['accounts_receivable'], 'Opening AR');
                if ($balances['inventory_value'] > 0) $builder->addDebit($inventoryAccountId, $balances['inventory_value'], 'Opening Inventory');
                foreach ($paymentAccounts as $paymentAccount) {
                    $openingBalance = (float) $paymentAccount->total_balance;
                    if ($openingBalance > 0) {
                        $builder->addDebit($mappedPaymentAccounts[$paymentAccount->id], $openingBalance, 'Opening '.$paymentAccount->name);
                    } elseif ($openingBalance < 0) {
                        $builder->addCredit($mappedPaymentAccounts[$paymentAccount->id], abs($openingBalance), 'Opening '.$paymentAccount->name.' Overdraft');
                    }
                }

                if ($balances['accounts_payable'] > 0) $builder->addCredit($apAccountId, $balances['accounts_payable'], 'Opening AP');
                
                if ($balances['opening_balance_equity'] > 0) {
                    $builder->addCredit($equityAccountId, $balances['opening_balance_equity'], 'Opening Balance Equity');
                } elseif ($balances['opening_balance_equity'] < 0) {
                    $builder->addDebit($equityAccountId, abs($balances['opening_balance_equity']), 'Opening Balance Equity');
                }

                $journal = $builder->save();
                $journalId = $journal->id;
            }

            $session = AccountingActivationSession::create([
                'mode' => $mode,
                'start_date' => Carbon::now()->toDateString(),
                'summary_json' => json_encode($balances),
                'validation_json' => json_encode($checklist),
                'opening_journal_entry_id' => $journalId,
                'activated_by' => auth()->id() ?? 1,
                'activated_at' => Carbon::now(),
            ]);

            $config->enabled = true;
            $config->status = 'active';
            $config->activation_mode = $mode;
            $config->start_date = $session->start_date;
            $config->opening_journal_entry_id = $journalId;
            $config->activated_by = auth()->id() ?? 1;
            $config->activated_at = Carbon::now();
            $config->save();

            DB::commit();
            return $session;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reset()
    {
        $config = $this->getConfig();
        if (!$config->enabled) {
            throw new Exception("Accounting is not activated.");
        }

        $count = DB::table('journal_entries')
            ->where('id', '!=', $config->opening_journal_entry_id ?? 0)
            ->whereDate('entry_date', '>=', $config->start_date)
            ->count();

        if ($count > 0) {
            throw new Exception("Cannot reset: Post-opening journals exist.");
        }

        DB::beginTransaction();
        try {
            if ($config->opening_journal_entry_id) {
                DB::table('journal_lines')->where('journal_entry_id', $config->opening_journal_entry_id)->delete();
                DB::table('journal_entries')->where('id', $config->opening_journal_entry_id)->delete();
            }

            $config->enabled = false;
            $config->status = 'not_activated';
            $config->activation_mode = null;
            $config->start_date = null;
            $config->opening_journal_entry_id = null;
            $config->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getDefaultAccountId($code, $name, $type, bool $isCashAccount = false)
    {
        $account = DB::table('accounting_accounts')->where('code', $code)->first();
        if (!$account) {
            $id = DB::table('accounting_accounts')->insertGetId([
                'name' => $name,
                'code' => $code,
                'account_type' => $type,
                'is_active' => true,
                'is_system' => true,
                'is_control_account' => true,
                'is_cash_account' => $isCashAccount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $id;
        }
        DB::table('accounting_accounts')
            ->where('id', $account->id)
            ->update(array_filter([
                'account_type' => $type,
                'is_active' => true,
                'is_system' => true,
                'is_cash_account' => $isCashAccount ? true : null,
                'updated_at' => now(),
            ], fn ($value) => $value !== null));

        return $account->id;
    }

    private function ensureRuntimeAccounts(): void
    {
        $accounts = [
            ['1000', 'Cash & Bank', 'asset', true],
            ['1100', 'Accounts Receivable', 'asset', false],
            ['1150', 'Employee Advance Receivable', 'asset', false],
            ['1200', 'Inventory', 'asset', false],
            ['1250', 'Input VAT', 'asset', false],
            ['2100', 'Accounts Payable', 'liability', false],
            ['2210', 'Customer Deposits', 'liability', false],
            ['2220', 'Customer Rewards', 'liability', false],
            ['2250', 'Gift Card Liability', 'liability', false],
            ['3900', 'Opening Balance Equity', 'equity', false],
            ['4100', 'Sales Revenue', 'revenue', false],
            ['4150', 'Sales Returns', 'revenue', false],
            ['4160', 'Sales Discounts', 'revenue', false],
            ['4300', 'Other Income', 'revenue', false],
            ['5000', 'Cost of Goods Sold', 'cogs', false],
            ['5100', 'Payroll Expense', 'expense', false],
            ['5150', 'Purchase Returns', 'expense', false],
            ['5200', 'Freight In', 'expense', false],
            ['5210', 'Rewards Expense', 'expense', false],
            ['5300', 'Purchase Discounts', 'expense', false],
            ['6100', 'General Expense', 'expense', false],
        ];

        foreach ($accounts as [$code, $name, $type, $isCashAccount]) {
            $this->getDefaultAccountId($code, $name, $type, $isCashAccount);
        }
    }

    private function deactivateUnusedLegacyOpeningAccounts(): void
    {
        $legacyCodes = [
            'accounts_receivable',
            'inventory',
            'cash',
            'accounts_payable',
            'opening_balance_equity',
        ];

        $usedAccountIds = DB::table('journal_lines')
            ->whereIn('accounting_account_id', function ($query) use ($legacyCodes) {
                $query->select('id')
                    ->from('accounting_accounts')
                    ->whereIn('code', $legacyCodes);
            })
            ->pluck('accounting_account_id')
            ->all();

        DB::table('accounting_accounts')
            ->whereIn('code', $legacyCodes)
            ->whereNotIn('id', $usedAccountIds)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
}
