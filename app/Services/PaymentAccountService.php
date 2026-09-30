<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentAccountService
{
    public function __construct(private AccountingService $accounting, private AccountingModeService $mode) {}

    public function ensureMapping(Account $account): AccountingAccount
    {
        $id = $this->accounting->getMappedAccount(
            Account::class, $account->id, $this->accounting->getRoleAccountId(AccountingService::ROLE_CASH)
        );
        return AccountingAccount::findOrFail($id);
    }

    public function mappedAccountingAccount(Account $account): ?AccountingAccount
    {
        $mapping = AccountMapping::where('mapped_type', Account::class)->where('mapped_id', $account->id)->first();
        return $mapping ? AccountingAccount::find($mapping->accounting_account_id) : null;
    }

    public function isValid(Account $account): bool
    {
        $mapped = $this->mappedAccountingAccount($account);
        return $account->is_active && $mapped && $mapped->is_active
            && $mapped->account_type === 'asset' && (bool) $mapped->is_cash_account;
    }

    public function journalBalance(Account $account, ?string $asOf = null): ?float
    {
        $mapped = $this->mappedAccountingAccount($account);
        if (!$mapped || !$mapped->is_active || $mapped->account_type !== 'asset' || !$mapped->is_cash_account) return null;
        $query = DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.accounting_account_id', $mapped->id);
        if ($asOf) $query->whereDate('journal_entries.entry_date', '<=', $asOf);
        return (float) $query->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) balance')->value('balance');
    }

    public function decorate(Collection $accounts): Collection
    {
        return $accounts->each(function (Account $account) {
            $account->mapped_account = $this->mappedAccountingAccount($account);
            $account->journal_balance = $this->journalBalance($account);
            $account->mapping_valid = $this->isValid($account);
        });
    }

    public function validOperationalAccounts(): Collection
    {
        $accounts = Account::where('is_active', true)->get();
        return $this->mode->isLegacy() ? $accounts : $accounts->filter(fn ($account) => $this->isValid($account))->values();
    }

    public function assertValidId(int $accountId): int
    {
        $account = Account::whereKey($accountId)->where('is_active', true)->first();
        if (!$account || ($this->mode->isDoubleEntryAuthoritative() && !$this->isValid($account))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account_id' => __('db.payment_account_invalid_mapping'),
            ]);
        }
        return $accountId;
    }
}
