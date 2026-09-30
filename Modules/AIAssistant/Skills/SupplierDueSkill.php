<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Modules\AIAssistant\DTO\WarehouseScope;
use Illuminate\Support\Facades\DB;

class SupplierDueSkill implements AssistantSkill
{
    private const RESULT_LIMIT = 10;

    public function key(): string
    {
        return 'supplier_due';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_supplier_due_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_supplier_due_description');
    }

    public function examples(): array
    {
        return [
            'supplier due summary',
            'show supplier dues',
            'which suppliers do we owe',
            'outstanding supplier balances',
            'accounts payable summary'
        ];
    }

    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'supplier due summary',
            'show supplier dues',
            'which suppliers do we owe',
            'outstanding supplier balances',
            'accounts payable summary',
            'supplier due'
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = WarehouseScope::fromContext($context);

        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse(0.0, 0, [], $scope->isRestricted, $scope->warehouseIds, $scope->ownUserId, true, 'empty_warehouse_scope');
        }

        if ($scope->ownUserId !== null) {
            return $this->buildResponse(0.0, 0, [], $scope->isRestricted, null, $scope->ownUserId, true, 'own_access_restriction');
        }

        /*
         * Formula: valid purchases + supplier opening balance (if not restricted) - purchase payments - purchase returns
         */

        $purchasesQuery = DB::table('purchases')
            ->selectRaw('COALESCE(SUM(grand_total), 0)')
            ->whereColumn('purchases.supplier_id', 'suppliers.id')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.status', '!=', 3);

        if ($scope->isRestricted) {
            $purchasesQuery->whereIn('purchases.warehouse_id', $scope->warehouseIds);
        }

        $paymentsQuery = DB::table('payments')
            ->join('purchases', 'payments.purchase_id', '=', 'purchases.id')
            ->selectRaw('COALESCE(SUM(payments.amount), 0)')
            ->whereColumn('purchases.supplier_id', 'suppliers.id')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.status', '!=', 3)
            ->whereNull('payments.purchase_return_id');

        if ($scope->isRestricted) {
            $paymentsQuery->whereIn('purchases.warehouse_id', $scope->warehouseIds);
        }

        $returnsQuery = DB::table('return_purchases')
            ->selectRaw('COALESCE(SUM(grand_total), 0)')
            ->whereColumn('return_purchases.supplier_id', 'suppliers.id');

        if ($scope->isRestricted) {
            $returnsQuery->whereIn('return_purchases.warehouse_id', $scope->warehouseIds);
        }

        $query = DB::table('suppliers')
            ->select('name')
            ->selectSub($purchasesQuery, 'purchases_total')
            ->selectSub($paymentsQuery, 'payments_total')
            ->selectSub($returnsQuery, 'returns_total')
            ->where('is_active', true);

        if ($scope->isRestricted) {
            // Omit opening balance for restricted warehouse queries
            $query->selectRaw('0 as opening_balance');
        } else {
            $query->selectRaw('COALESCE(opening_balance, 0) as opening_balance');
        }
        
        $query->havingRaw('(opening_balance + purchases_total - payments_total - returns_total) > 0');

        $totalDue = (float) DB::query()->fromSub($query, 'sub')->sum(DB::raw('opening_balance + purchases_total - payments_total - returns_total'));
        $suppliersWithDue = DB::query()->fromSub($query, 'sub')->count();

        $rows = $query->orderByRaw('(opening_balance + purchases_total - payments_total - returns_total) desc')->orderBy('name', 'asc')->limit(self::RESULT_LIMIT)->get();

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'name' => $row->name,
                'due' => (float) ($row->opening_balance + $row->purchases_total - $row->payments_total - $row->returns_total),
            ];
        }

        return $this->buildResponse($totalDue, $suppliersWithDue, $tableRows, $scope->isRestricted, $scope->warehouseIds, $scope->ownUserId);
    }

    private function buildResponse(float $totalDue, int $suppliersWithDue, array $tableRows, bool $isRestricted, mixed $warehouseIds, ?int $ownUserId = null, bool $failedClosed = false, ?string $reason = null): AssistantResponseData
    {
        $textSummary = $suppliersWithDue === 0
            ? __('db.ai_assistant_supplier_due_empty')
            : __('db.ai_assistant_supplier_due_summary', ['total' => number_format($totalDue, 2), 'count' => $suppliersWithDue]);

        $warnings = [];

        if ($failedClosed) {
            $textSummary = __('db.ai_assistant_supplier_due_forbidden');
            if ($reason === 'empty_warehouse_scope') {
                $warnings[] = __('db.ai_assistant_warning_no_warehouse');
            } else {
                $warnings[] = __('db.ai_assistant_warning_supplier_scope');
            }
        } elseif ($isRestricted) {
            $reason = 'warehouse_activity_only';
            $warnings[] = __('db.ai_assistant_warning_supplier_opening_balance');
        }

        $cards = [
            ['title' => __('db.ai_assistant_card_suppliers_with_due'), 'value' => $suppliersWithDue],
            ['title' => __('db.ai_assistant_card_total_payable'), 'value' => round($totalDue, 2)],
        ];

        $table = [];
        if (!empty($tableRows)) {
            $table = [
                'columns' => [__('db.ai_assistant_column_supplier_name'), __('db.ai_assistant_column_amount_payable')],
                'rows' => array_map(fn($r) => [
                    $r['name'],
                    $r['due'],
                ], $tableRows),
            ];
        }

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            table: $table,
            links: [
                ['label' => __('db.ai_assistant_link_view_supplier_report'), 'url' => url('/report/supplier_report')]
            ],
            warnings: $warnings,
            metadata: [
                'skill' => $this->key(),
                'result_limit' => self::RESULT_LIMIT,
                'failed_closed' => $failedClosed,
                'reason' => $reason,
                'warehouse_ids' => $warehouseIds,
                'own_user_id' => $ownUserId,
                'warehouse_scope_note' => 'Supplier due applies warehouse restrictions strictly on purchases, returns, and payments.',
            ]
        );
    }
}
