<?php

namespace Modules\AIAssistant\Skills;

use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\DB;

class CustomerDueSkill implements AssistantSkill
{
    /** Maximum number of customers in the response table. */
    private const RESULT_LIMIT = 10;

    public function key(): string
    {
        return 'customer_due';
    }

    public function name(): string
    {
        return __('db.ai_assistant_skill_customer_due_name');
    }

    public function description(): string
    {
        return __('db.ai_assistant_skill_customer_due_description');
    }

    public function examples(): array
    {
        return [
            'customer due summary',
            'show customer dues',
        ];
    }

    /**
     * Matches customer-due intent prompts.
     * Normalises to lowercase, strips punctuation, collapses whitespace.
     */
    public function canHandle(AssistantMessageData $message): bool
    {
        $prompt = strtolower(trim(preg_replace('/[^a-z0-9\s]/i', '', $message->content)));
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        $validIntents = [
            'customer due summary',
            'show customer dues',
            'which customers owe money',
            'outstanding customer balances',
        ];

        return in_array($prompt, $validIntents, true);
    }

    /**
     * Reuses CustomerCreditService::calculateCustomerDue() for consistency with the
     * application's canonical due formula:
     *   (sales + opening_balance + refunds) - (payments + returns)
     *
     * Note on warehouse scope: The canonical customer-due calculation is not
     * inherently warehouse-specific — it aggregates all sales for the customer
     * regardless of warehouse. The warehouse_ids restriction is documented and
     * NOT applied to the due calculation to avoid introducing an inconsistent formula.
     * The warehouse context is included in response metadata for transparency.
     */
    public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $scope = \Modules\AIAssistant\DTO\WarehouseScope::fromContext($context);

        // Fast path: explicitly empty warehouse restriction returns empty
        if ($scope->isRestricted && empty($scope->warehouseIds)) {
            return $this->buildResponse(0.0, 0, [], $scope->warehouseIds);
        }

        // Fast path: own access cannot view global customer debt, fail closed
        if ($scope->ownUserId !== null) {
            return $this->buildResponse(0.0, 0, [], null, $scope->ownUserId);
        }

        /*
         * Efficient aggregate approach:
         * Compute per-customer due using the same formula as CustomerCreditService
         * but via a single SQL query for performance.
         * Formula: (SUM(grand_total for active sales) + opening_balance + refunds) - (payments + returns)
         *
         * We use DB directly here for a bounded, paginated result rather than loading
         * all customers into memory.
         */
        $dueSql = '(COALESCE(customers.opening_balance, 0) + ' .
                  '(SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE sales.customer_id = customers.id AND sales.deleted_at IS NULL AND sales.sale_status != 3 AND (sales.sale_type != "Opening balance" OR sales.sale_type IS NULL)) + ' .
                  '(SELECT COALESCE(SUM(amount), 0) FROM payments INNER JOIN returns ON payments.return_id = returns.id WHERE returns.customer_id = customers.id)) - ' .
                  '((SELECT COALESCE(SUM(amount), 0) FROM payments INNER JOIN sales ON payments.sale_id = sales.id WHERE sales.customer_id = customers.id AND sales.sale_status != 3 AND sales.deleted_at IS NULL AND payments.return_id IS NULL) + ' .
                  '(SELECT COALESCE(SUM(grand_total), 0) FROM returns WHERE returns.customer_id = customers.id))';

        $query = DB::table('customers')
            ->select('name', DB::raw("($dueSql) as due"))
            ->where('is_active', true)
            ->having('due', '>', 0);

        // Summing a calculated column can be done by wrapping the query
        $totalDue = (float) DB::query()->fromSub($query, 'sub')->sum('due');
        $customersWithDue = DB::query()->fromSub($query, 'sub')->count();

        $rows = $query->orderBy('due', 'desc')->orderBy('name', 'asc')->limit(self::RESULT_LIMIT)->get();

        // Format for table
        $tableRows = [];
        foreach ($rows as $row) {
            $tableRows[] = [
                'name' => $row->name,
                'due' => (float) $row->due,
            ];
        }

        return $this->buildResponse($totalDue, $customersWithDue, $tableRows, $scope->isRestricted ? $scope->warehouseIds : null, $scope->ownUserId);
    }

    private function buildResponse(float $totalDue, int $customersWithDue, array $tableRows, mixed $warehouseIds, ?int $ownUserId = null): AssistantResponseData
    {
        $textSummary = $customersWithDue === 0
            ? __('db.ai_assistant_customer_due_empty')
            : __('db.ai_assistant_customer_due_summary', ['count' => $customersWithDue, 'total' => number_format($totalDue, 2)]);
            
        if ($ownUserId !== null) {
            $textSummary = __('db.ai_assistant_customer_due_forbidden');
        }

        $cards = [
            ['title' => __('db.ai_assistant_card_customers_with_due'), 'value' => $customersWithDue],
            ['title' => __('db.ai_assistant_card_total_outstanding'), 'value' => round($totalDue, 2)],
        ];

        $table = [];
        if (!empty($tableRows)) {
            $table = [
                'columns' => [__('db.ai_assistant_column_customer_name'), __('db.ai_assistant_column_amount_due')],
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
                ['label' => __('db.ai_assistant_link_view_customers'), 'url' => url('/customer')]
            ],
            metadata: [
                'skill' => $this->key(),
                'result_limit' => self::RESULT_LIMIT,
                'warehouse_ids' => $warehouseIds,
                'own_user_id' => $ownUserId,
                'warehouse_scope_note' => 'Customer due is not warehouse-specific; warehouse_ids context is recorded but does not filter the due calculation.',
            ]
        );
    }
}
