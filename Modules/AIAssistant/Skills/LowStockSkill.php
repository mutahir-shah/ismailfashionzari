<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Illuminate\Support\Facades\DB;

class LowStockSkill implements AssistantSkill
{
    /** Maximum number of products returned in the response table. */
    private const RESULT_LIMIT = 15;

    public function key(): string
    {
        return 'low_stock';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_low_stock_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_low_stock_description');
    }

    public function examples(): array
    {
        return [
            'show low stock products',
            'stock alerts',
        ];
    }

    /**
     * Matches low-stock intent prompts.
     * Normalises to lowercase, strips punctuation, collapses whitespace.
     */
    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'show low stock products',
            'which products are low in stock',
            'low stock',
            'stock alerts',
            'low stock alert',
            'low stock alerts',
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = \Modules\AIAssistant\DTO\WarehouseScope::fromContext($context);

        // Fast path: explicitly empty warehouse restriction returns empty
        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse(0, [], $scope->warehouseIds);
        }

        // Fast path: own access cannot view global stock, fail closed
        if ($scope->ownUserId !== null) {
            return $this->buildResponse(0, [], null, $scope->ownUserId);
        }

        /*
         * Low-stock query:
         * Join product_warehouse with products.
         * Group by product_id, warehouse_id to get per-warehouse stock.
         * Return products whose SUM(qty) <= alert_quantity for active products.
         * Order deterministically: by qty ASC, then product name ASC.
         * Limit to RESULT_LIMIT rows.
         */
        $query = DB::table('product_warehouse as pw')
            ->join('products', 'pw.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.code as product_code',
                'products.alert_quantity',
                'pw.warehouse_id',
                DB::raw('SUM(pw.qty) as current_qty')
            )
            ->where('products.is_active', true)
            ->groupBy('pw.product_id', 'pw.warehouse_id', 'products.id', 'products.name', 'products.code', 'products.alert_quantity')
            ->havingRaw('SUM(pw.qty) <= products.alert_quantity');

        if ($scope->isRestricted) {
            $query->whereIn('pw.warehouse_id', $scope->warehouseIds);
        }

        // Deterministic ordering: lowest stock first, then alphabetical
        $rows = $query
            ->orderByRaw('SUM(pw.qty) ASC')
            ->orderBy('products.name', 'ASC')
            ->limit(self::RESULT_LIMIT)
            ->get();

        // Count total low-stock products (distinct product IDs)
        $countQuery = DB::table('product_warehouse as pw')
            ->join('products', 'pw.product_id', '=', 'products.id')
            ->select('pw.product_id')
            ->where('products.is_active', true)
            ->groupBy('pw.product_id', 'products.alert_quantity')
            ->havingRaw('SUM(pw.qty) <= products.alert_quantity');

        if ($scope->isRestricted) {
            $countQuery->whereIn('pw.warehouse_id', $scope->warehouseIds);
        }

        $totalCount = (int) $countQuery->get()->count();

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'product' => $row->product_name . ($row->product_code ? " ({$row->product_code})" : ''),
                'warehouse_id' => $row->warehouse_id,
                'current_qty' => (float) $row->current_qty,
                'alert_qty' => (float) $row->alert_quantity,
            ];
        }

        return $this->buildResponse($totalCount, $tableRows, $scope->isRestricted ? $scope->warehouseIds : null, $scope->ownUserId);
    }

    private function buildResponse(int $count, array $tableRows, ?array $warehouseIds, ?int $ownUserId = null): AssistantResponseData
    {
        $textSummary = $count === 0
            ? __('db.ai_assistant_low_stock_empty')
            : __('db.ai_assistant_low_stock_summary', ['count' => $count]);

        $cards = [
            ['title' => __('db.ai_assistant_card_low_stock_products'), 'value' => $count],
        ];

        $table = [];
        if (!empty($tableRows)) {
            $table = [
                'columns' => [__('db.ai_assistant_column_product'), __('db.ai_assistant_column_warehouse_id'), __('db.ai_assistant_column_current_qty'), __('db.ai_assistant_column_alert_qty')],
                'rows' => array_map(fn($r) => [
                    $r['product'],
                    $r['warehouse_id'],
                    $r['current_qty'],
                    $r['alert_qty'],
                ], $tableRows),
            ];
        }

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            table: $table,
            links: [
                ['label' => __('db.ai_assistant_link_view_stock_alert'), 'url' => route('report.qtyAlert')]
            ],
            metadata: [
                'skill' => $this->key(),
                'result_limit' => self::RESULT_LIMIT,
                'warehouse_ids' => $warehouseIds,
                'own_user_id' => $ownUserId,
            ]
        );
    }
}
