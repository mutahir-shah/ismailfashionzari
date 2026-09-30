<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopProductsSkill implements AssistantSkill
{
    /** Maximum number of products returned in the response table. */
    private const RESULT_LIMIT = 10;

    public function key(): string
    {
        return 'top_products';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_top_products_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_top_products_description');
    }

    public function examples(): array
    {
        return [
            'show top selling products',
            'top products',
        ];
    }

    /**
     * Matches top-products intent prompts.
     * Normalises to lowercase, strips punctuation, collapses whitespace.
     */
    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'show top selling products',
            'top selling products',
            'best selling products today',
            'top products',
            'what sold the most today',
        ];

        return in_array($prompt, $validIntents, true);
    }

    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = \Modules\AIAssistant\DTO\WarehouseScope::fromContext($context);

        // Fast path: explicitly empty warehouse restriction returns empty
        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse([], $scope->warehouseIds);
        }

        /*
         * Top-products query:
         * Join product_sales with sales (to check status, date, warehouse).
         * Join products to get name and code.
         * Group by product_id, sum qty.
         * Order by qty DESC (most sold first), then product name ASC (tie-breaking).
         * Limit to RESULT_LIMIT rows.
         * Exclude: drafts (sale_status=3), soft-deleted sales, opening-balance sales.
         */
        $query = DB::table('product_sales as ps')
            ->join('sales', 'ps.sale_id', '=', 'sales.id')
            ->join('products', 'ps.product_id', '=', 'products.id')
            ->select(
                'ps.product_id',
                'products.name as product_name',
                'products.code as product_code',
                DB::raw('SUM(ps.qty) as qty_sold'),
                DB::raw('SUM(ps.total) as sales_value')
            )
            ->whereNull('sales.deleted_at')
            ->where('sales.sale_status', '!=', 3)
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'Opening balance')
                  ->orWhereNull('sales.sale_type');
            })
            ->whereDate('sales.created_at', Carbon::today())
            ->groupBy('ps.product_id', 'products.name', 'products.code')
            ->orderByRaw('SUM(ps.qty) DESC')
            ->orderBy('products.name', 'ASC')
            ->limit(self::RESULT_LIMIT);

        if ($scope->isRestricted) {
            $query->whereIn('sales.warehouse_id', $scope->warehouseIds);
        }

        if ($scope->ownUserId !== null) {
            $query->where('sales.user_id', $scope->ownUserId);
        }

        $rows = $query->get();

        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'product' => $row->product_name . ($row->product_code ? " ({$row->product_code})" : ''),
                'qty_sold' => (float) $row->qty_sold,
                'sales_value' => (float) $row->sales_value,
            ];
        }

        return $this->buildResponse($tableRows, $scope->isRestricted ? $scope->warehouseIds : null, $scope->ownUserId);
    }

    private function buildResponse(array $tableRows, ?array $warehouseIds, ?int $ownUserId = null): AssistantResponseData
    {
        $productCount = count($tableRows);

        $textSummary = $productCount === 0
            ? __('db.ai_assistant_top_products_empty')
            : __('db.ai_assistant_top_products_summary', ['count' => $productCount]);

        $cards = [
            ['title' => __('db.ai_assistant_card_products_sold_today'), 'value' => $productCount],
        ];

        $table = [];
        if (!empty($tableRows)) {
            $table = [
                'columns' => [__('db.ai_assistant_column_product'), __('db.ai_assistant_column_qty_sold'), __('db.ai_assistant_column_sales_value')],
                'rows' => array_map(fn($r) => [
                    $r['product'],
                    $r['qty_sold'],
                    $r['sales_value'],
                ], $tableRows),
            ];
        }

        return new AssistantResponseData(
            textSummary: $textSummary,
            responseType: 'card',
            cards: $cards,
            table: $table,
            links: [
                ['label' => __('db.ai_assistant_link_view_all_sales'), 'url' => url('/sales')]
            ],
            metadata: [
                'skill' => $this->key(),
                'date' => Carbon::today()->toDateString(),
                'result_limit' => self::RESULT_LIMIT,
                'warehouse_ids' => $warehouseIds,
                'own_user_id' => $ownUserId,
            ]
        );
    }
}
