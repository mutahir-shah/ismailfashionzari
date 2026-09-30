<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Modules\AIAssistant\DTO\WarehouseScope;
use Illuminate\Support\Facades\DB;

class CashBankSummarySkill implements AssistantSkill
{
    public function key(): string
    {
        return 'cash_bank_summary';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_cash_bank_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_cash_bank_description');
    }

    public function examples(): array
    {
        return [
            'cash and bank summary',
            'show account balances',
            'how much cash do we have',
            'bank balances'
        ];
    }

    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'cash and bank summary',
            'cash bank summary',
            'show account balances',
            'how much cash do we have',
            'bank balances'
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = WarehouseScope::fromContext($context);

        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse(0.0, 0, [], true, 'empty_warehouse_scope');
        }
        
        if ($scope->isRestricted || $scope->ownUserId !== null) {
            return $this->buildResponse(0.0, 0, [], true, $scope->isRestricted ? 'global_data_restriction' : 'own_access_restriction');
        }

        if (app(\App\Services\AccountingModeService::class)->isDoubleEntryAuthoritative()) {
            $accounts = app(\App\Services\PaymentAccountService::class)
                ->decorate(\App\Models\Account::where('is_active', true)->get());
            $rows = $accounts->filter->mapping_valid->sortBy('name')->map(fn ($account) => [
                'name_number' => $account->name . ($account->account_no ? ' (' . $account->account_no . ')' : ''),
                'balance' => (float) $account->journal_balance,
            ])->values()->all();
            return $this->buildResponse((float) collect($rows)->sum('balance'), count($rows), $rows, false);
        }

        // Matches logic in AccountsController::index()
        // Credit = payments (from sales) + purchase returns + income + transfers in + initial balance
        // Debit = payments (for purchases) + sales returns + expenses + payrolls + transfers out

        $creditSql = "(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE account_id = accounts.id AND sale_id IS NOT NULL AND return_id IS NULL) + " .
                     "(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE account_id = accounts.id AND purchase_return_id IS NOT NULL) + " .
                     "(SELECT COALESCE(SUM(amount), 0) FROM money_transfers WHERE to_account_id = accounts.id) + " .
                     "(SELECT COALESCE(SUM(amount), 0) FROM incomes WHERE account_id = accounts.id) + " .
                     "COALESCE(initial_balance, 0)";

        $debitSql = "(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE account_id = accounts.id AND return_id IS NOT NULL) + " .
                    "(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE account_id = accounts.id AND purchase_id IS NOT NULL AND purchase_return_id IS NULL) + " .
                    "(SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE account_id = accounts.id) + " .
                    "(SELECT COALESCE(SUM(amount), 0) FROM payrolls WHERE account_id = accounts.id) + " .
                    "(SELECT COALESCE(SUM(amount), 0) FROM money_transfers WHERE from_account_id = accounts.id)";

        $query = DB::table('accounts')
            ->select('name', 'account_no', DB::raw("($creditSql) - ($debitSql) as balance"))
            ->where('is_active', true);

        $accountsCount = DB::query()->fromSub($query, 'sub')->count();
        $totalBalance = (float) DB::query()->fromSub($query, 'sub')->sum('balance');

        $rows = $query->orderBy('name', 'asc')->get();

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'name_number' => $row->name . ($row->account_no ? ' (' . $row->account_no . ')' : ''),
                'balance' => (float) $row->balance,
            ];
        }

        return $this->buildResponse($totalBalance, $accountsCount, $tableRows, false);
    }

    private function buildResponse(float $totalBalance, int $accountsCount, array $tableRows, bool $failedClosed = false, ?string $reason = null): AssistantResponseData
    {
        $textSummary = __('db.ai_assistant_cash_bank_summary', ['count' => $accountsCount, 'total' => number_format($totalBalance, 2)]);

        $warnings = [];
        if ($failedClosed) {
            $textSummary = __('db.ai_assistant_cash_bank_forbidden');
            if ($reason === 'empty_warehouse_scope') {
                $warnings[] = __('db.ai_assistant_warning_no_warehouse');
            } else {
                $warnings[] = __('db.ai_assistant_warning_account_scope');
            }
        }

        $cards = [
            ['title' => __('db.ai_assistant_card_active_accounts'), 'value' => $accountsCount],
            ['title' => __('db.ai_assistant_card_total_balance'), 'value' => round($totalBalance, 2)],
        ];

        $table = [];
        if (!empty($tableRows)) {
            $table = [
                'columns' => [__('db.ai_assistant_column_account_name_number'), __('db.ai_assistant_column_current_balance')],
                'rows' => array_map(fn($r) => [
                    $r['name_number'],
                    $r['balance'],
                ], $tableRows),
            ];
        }

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            table: $table,
            links: [
                ['label' => __('db.ai_assistant_link_view_accounts'), 'url' => url('/accounts')]
            ],
            warnings: $warnings,
            metadata: [
                'skill' => $this->key(),
                'failed_closed' => $failedClosed,
                'reason' => $reason,
                'note' => 'Account balances are global and not bound to warehouses.'
            ]
        );
    }
}
