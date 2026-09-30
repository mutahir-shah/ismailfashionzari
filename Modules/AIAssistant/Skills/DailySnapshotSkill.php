<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Modules\AIAssistant\DTO\WarehouseScope;
use Modules\AIAssistant\Skills\SalesSummarySkill;
use Modules\AIAssistant\Skills\PurchaseSummarySkill;
use Modules\AIAssistant\Skills\ExpenseSummarySkill;
use Modules\AIAssistant\Skills\LowStockSkill;

class DailySnapshotSkill implements AssistantSkill
{
    public function key(): string
    {
        return 'daily_snapshot';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_daily_snapshot_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_daily_snapshot_description');
    }

    public function examples(): array
    {
        return [
            'daily business snapshot',
            'today\'s business summary',
            'show today\'s snapshot',
            'how is business today',
            'daily snapshot'
        ];
    }

    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'daily business snapshot',
            'todays business summary',
            'today business summary',
            'show todays snapshot',
            'show today snapshot',
            'how is business today',
            'daily snapshot',
            'todays snapshot'
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        // 1. Sales Summary
        $salesSkill = new SalesSummarySkill();
        $salesResponse = $salesSkill->handle($message, $context);
        $salesTotal = $salesResponse->cards[2]['value'] ?? 0; // Gross Total is index 2
        $salesDue = $salesResponse->cards[4]['value'] ?? 0; // Due Amount is index 4
        $salesCount = $salesResponse->cards[0]['value'] ?? 0;

        // 2. Purchase Summary
        $purchaseSkill = new PurchaseSummarySkill();
        $purchaseResponse = $purchaseSkill->handle($message, $context);
        $purchaseTotal = $purchaseResponse->cards[2]['value'] ?? 0; // Gross Total is index 2
        $purchaseDue = $purchaseResponse->cards[4]['value'] ?? 0; // Due Amount is index 4
        $purchaseCount = $purchaseResponse->cards[0]['value'] ?? 0;

        // 3. Expense Summary
        $expenseSkill = new ExpenseSummarySkill();
        $expenseResponse = $expenseSkill->handle($message, $context);
        $expenseTotal = $expenseResponse->cards[1]['value'] ?? 0;
        $expenseCount = $expenseResponse->cards[0]['value'] ?? 0;

        // 4. Low Stock
        $warnings = [];
        $lowStockSkill = new LowStockSkill();
        $lowStockResponse = $lowStockSkill->handle($message, $context);
        $lowStockCount = $lowStockResponse->cards[0]['value'] ?? 0;
        
        $scope = WarehouseScope::fromContext($context);

        $cards = [
            ['title' => __('db.ai_assistant_card_todays_sales'), 'value' => $salesTotal],
            ['title' => __('db.ai_assistant_card_todays_purchases'), 'value' => $purchaseTotal],
            ['title' => __('db.ai_assistant_card_todays_expenses'), 'value' => $expenseTotal],
            ['title' => __('db.ai_assistant_card_sales_due_created'), 'value' => $salesDue],
            ['title' => __('db.ai_assistant_card_purchases_due_created'), 'value' => $purchaseDue],
            ['title' => __('db.ai_assistant_card_total_transactions'), 'value' => $salesCount + $purchaseCount + $expenseCount],
        ];

        $failedClosed = false;
        $reason = null;

        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            $warnings[] = __('db.ai_assistant_warning_no_warehouse');
            $failedClosed = true;
            $reason = 'empty_warehouse_scope';
            foreach ($cards as &$card) {
                $card['value'] = 0;
            }
        } elseif ($scope->ownUserId !== null) {
            $warnings[] = __('db.ai_assistant_warning_low_stock_scope');
            $reason = 'partial_own_access_restriction';
        } else {
            $cards[] = ['title' => __('db.ai_assistant_card_low_stock_items'), 'value' => $lowStockCount];
        }

        $textSummary = __('db.ai_assistant_daily_snapshot_summary', [
            'sales' => number_format($salesTotal, 2),
            'purchases' => number_format($purchaseTotal, 2),
            'expenses' => number_format($expenseTotal, 2),
        ]);

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            table: [],
            links: [
                ['label' => __('db.ai_assistant_link_view_sales'), 'url' => url('/sales')],
                ['label' => __('db.ai_assistant_link_view_purchases'), 'url' => url('/purchases')],
                ['label' => __('db.ai_assistant_link_view_expenses'), 'url' => url('/expenses')]
            ],
            warnings: $warnings,
            metadata: [
                'skill' => $this->key(),
                'date' => \Carbon\Carbon::today()->format('Y-m-d'),
                'failed_closed' => $failedClosed,
                'reason' => $reason,
                'warehouse_ids' => $scope->isRestricted ? $scope->warehouseIds : null,
                'own_user_id' => $scope->ownUserId,
                'sub_skills' => ['sales_summary', 'purchase_summary', 'expense_summary', 'low_stock']
            ]
        );
    }
}
