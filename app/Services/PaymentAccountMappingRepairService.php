<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PaymentAccountMappingRepairService
{
    public function __construct(
        private AccountingModeService $mode,
        private AccountingService $accounting,
        private PaymentAccountService $paymentAccounts
    ) {}

    public function inspect(): array
    {
        $schemaIssues = $this->schemaIssues();
        $parent = $this->cashParent();
        $missing = [];
        $invalid = [];
        $accounts = Account::where('is_active', true)->orderBy('id')->get();

        foreach ($accounts as $account) {
            $mapping = AccountMapping::where('mapped_type', Account::class)
                ->where('mapped_id', $account->id)
                ->first();

            if (!$mapping) {
                $item = $this->missingItem($account, $parent);
                $missing[] = $item;
                if (!$parent) {
                    $invalid[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'mapping_id' => null,
                        'accounting_account_id' => null,
                        'reason' => 'The configured Cash & Bank parent could not be determined; manual review is required.',
                    ];
                }
            } elseif (!$this->paymentAccounts->isValid($account)) {
                $invalid[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'mapping_id' => $mapping->id,
                    'accounting_account_id' => $mapping->accounting_account_id,
                    'reason' => 'Existing mapping is not an active cash asset account and requires manual review.',
                ];
            }
        }

        $repairable = $this->mode->isDoubleEntryAuthoritative() && empty($schemaIssues)
            ? array_values(array_filter($missing, fn (array $item) => !empty($item['expected']['parent_id'])))
            : [];

        return [
            'authoritative' => $this->mode->isDoubleEntryAuthoritative(),
            'schema_safe' => empty($schemaIssues),
            'schema_issues' => $schemaIssues,
            'inspected_count' => $accounts->count(),
            'missing_count' => count($missing),
            'invalid_count' => count($invalid),
            'repairable_count' => count($repairable),
            'missing' => $missing,
            'repairable' => $repairable,
            'ambiguous' => $invalid,
        ];
    }

    public function apply(): array
    {
        $inspection = $this->inspect();
        $result = $inspection + ['repaired_count' => 0, 'repaired' => [], 'skipped' => [], 'errors' => []];

        if (!$inspection['authoritative']) {
            $result['errors'][] = 'Double-entry accounting is not authoritative; no mappings were changed.';
            return $result;
        }

        if (!$inspection['schema_safe']) {
            $result['errors'][] = 'Required AUTO_INCREMENT metadata is missing from: ' . implode(', ', $inspection['schema_issues']) . '.';
            return $result;
        }

        foreach ($inspection['repairable'] as $candidate) {
            try {
                $outcome = DB::transaction(function () use ($candidate) {
                    $account = Account::whereKey($candidate['account_id'])->lockForUpdate()->firstOrFail();
                    $mapping = AccountMapping::where('mapped_type', Account::class)
                        ->where('mapped_id', $account->id)
                        ->first();

                    if ($mapping) {
                        return ['skipped' => true, 'account_id' => $account->id, 'reason' => 'A mapping now exists; it was left unchanged.'];
                    }

                    $mapped = $this->paymentAccounts->ensureMapping($account);

                    return [
                        'skipped' => false,
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'accounting_account_id' => $mapped->id,
                        'accounting_code' => $mapped->code,
                        'accounting_name' => $mapped->name,
                        'parent_id' => $mapped->parent_id,
                    ];
                });

                if ($outcome['skipped']) {
                    $result['skipped'][] = $outcome;
                } else {
                    $result['repaired'][] = $outcome;
                    $result['repaired_count']++;
                }
            } catch (Throwable $e) {
                report($e);
                $result['errors'][] = "Account {$candidate['account_id']} [{$candidate['account_name']}]: {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function missingItem(Account $account, ?AccountingAccount $parent): array
    {
        return [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'reason' => 'No operational payment-account mapping exists.',
            'expected' => [
                'code' => $this->nextCode($account->id),
                'name' => $account->name,
                'account_type' => 'asset',
                'is_cash_account' => true,
                'parent_id' => $parent?->id,
                'parent_code' => $parent?->code,
                'parent_name' => $parent?->name,
            ],
        ];
    }

    private function cashParent(): ?AccountingAccount
    {
        if (!$this->mode->isDoubleEntryAuthoritative()) {
            return null;
        }

        try {
            return AccountingAccount::find($this->accounting->getRoleAccountId(AccountingService::ROLE_CASH));
        } catch (Throwable) {
            return null;
        }
    }

    private function nextCode(int $accountId): string
    {
        $base = '10A' . $accountId;
        $code = $base;
        $suffix = 1;
        while (AccountingAccount::where('code', $code)->exists()) {
            $code = $base . '-' . $suffix++;
        }
        return $code;
    }

    private function schemaIssues(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return collect(['accounting_accounts', 'account_mappings'])
                ->reject(fn (string $table) => Schema::hasColumn($table, 'id'))
                ->values()->all();
        }

        return collect(['accounting_accounts', 'account_mappings'])
            ->reject(function (string $table) {
                $column = DB::selectOne(
                    'SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, 'id']
                );
                return $column && str_contains(strtolower((string) $column->EXTRA), 'auto_increment');
            })->values()->all();
    }
}
