<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseSummarySkill implements AssistantSkill
{
    public function key(): string
    {
        return 'purchase_summary';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_purchase_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_purchase_description');
    }

    public function examples(): array
    {
        return [
            "show today's purchases",
            'purchase summary',
        ];
    }

    /**
     * Matches purchase-summary intent prompts.
     * Normalises to lowercase, strips punctuation, collapses whitespace.
     */
    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'show todays purchases',
            'todays purchases',
            'purchases today',
            'purchase today',
            'purchase summary',
            'purchase summary today',
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = \Modules\AIAssistant\DTO\WarehouseScope::fromContext($context);

        // Fast path for explicitly empty warehouse restriction
        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse(0, 0, 0, 0, 0, $scope->warehouseIds, $scope->ownUserId);
        }

        // Exclude drafts (status=3); SoftDeletes withoutTrashed() excludes soft-deleted
        $query = Purchase::withoutTrashed()
            ->where('status', '!=', 3)
            ->whereDate('created_at', Carbon::today());

        if ($scope->isRestricted) {
            $query->whereIn('warehouse_id', $scope->warehouseIds);
        }

        if ($scope->ownUserId !== null) {
            $query->where('user_id', $scope->ownUserId);
        }

        $totals = $query->select(
            DB::raw('COUNT(*) as purchase_count'),
            DB::raw('COALESCE(SUM(total_qty), 0) as total_qty'),
            DB::raw('COALESCE(SUM(grand_total), 0) as grand_total'),
            DB::raw('COALESCE(SUM(paid_amount), 0) as paid_amount'),
            DB::raw('COALESCE(SUM(CASE WHEN grand_total > paid_amount THEN grand_total - paid_amount ELSE 0 END), 0) as due_amount')
        )->first();

        return $this->buildResponse(
            (int) ($totals->purchase_count ?? 0),
            (float) ($totals->total_qty ?? 0),
            (float) ($totals->grand_total ?? 0),
            (float) ($totals->paid_amount ?? 0),
            (float) ($totals->due_amount ?? 0),
            $scope->isRestricted ? $scope->warehouseIds : null,
            $scope->ownUserId
        );
    }

    private function buildResponse(int $count, float $qty, float $total, float $paid, float $due, ?array $warehouseIds, ?int $ownUserId): AssistantResponseData
    {
        $textSummary = __('db.ai_assistant_purchase_summary', ['count' => $count, 'total' => number_format($total, 2)]);

        $cards = [
            ['title' => __('db.ai_assistant_card_transactions'), 'value' => $count],
            ['title' => __('db.ai_assistant_card_items_purchased'), 'value' => $qty],
            ['title' => __('db.ai_assistant_card_gross_total'), 'value' => $total],
            ['title' => __('db.ai_assistant_card_paid_amount'), 'value' => $paid],
            ['title' => __('db.ai_assistant_card_due_amount'), 'value' => $due],
        ];

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            links: [
                ['label' => __('db.ai_assistant_link_view_purchases'), 'url' => url('/purchases')]
            ],
            metadata: [
                'skill' => $this->key(),
                'date' => Carbon::today()->toDateString(),
                'warehouse_ids' => $warehouseIds,
                'own_user_id' => $ownUserId,
            ]
        );
    }
}
