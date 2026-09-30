<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\ReturnPurchase;
use App\Models\Returns;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TaxReportService
{
    public function __construct(
        protected WarehouseAccessService $warehouseAccess
    ) {}

    /**
     * Resolve and validate warehouse filter.
     */
    public function resolveWarehouseId(?int $requestedWarehouseId): ?int
    {
        if ($this->warehouseAccess->isRestricted()) {
            return $this->warehouseAccess->warehouseId();
        }

        return ($requestedWarehouseId && $requestedWarehouseId > 0) ? $requestedWarehouseId : null;
    }

    /**
     * Apply common date & warehouse filters.
     */
    public function applyCommonFilters($query, array $filters, string $tablePrefix = '', string $warehouseColumn = 'warehouse_id', string $dateColumn = 'created_at')
    {
        $whCol = $tablePrefix ? "{$tablePrefix}.{$warehouseColumn}" : $warehouseColumn;
        $dtCol = $tablePrefix ? "{$tablePrefix}.{$dateColumn}" : $dateColumn;

        $warehouseId = $this->resolveWarehouseId(isset($filters['warehouse_id']) ? (int)$filters['warehouse_id'] : null);
        if ($warehouseId) {
            $query->where($whCol, $warehouseId);
        } elseif ($this->warehouseAccess->isRestricted()) {
            $query->whereRaw('1 = 0');
        }

        if (!empty($filters['starting_date'])) {
            $query->whereDate($dtCol, '>=', $filters['starting_date']);
        }
        if (!empty($filters['ending_date'])) {
            $query->whereDate($dtCol, '<=', $filters['ending_date']);
        }

        return $query;
    }

    /**
     * Build query for Sales (Output Tax).
     */
    public function getSalesQuery(array $filters)
    {
        $query = Sale::with(['customer', 'warehouse', 'currency'])
            ->whereNull('deleted_at')
            ->where('sale_status', '!=', 3) // Exclude Drafts
            ->where(function ($q) {
                $q->where('sale_type', '!=', 'opening balance')
                  ->orWhereNull('sale_type');
            });

        $this->applyCommonFilters($query, $filters, 'sales');

        if (!empty($filters['customer_id']) && (int)$filters['customer_id'] > 0) {
            $query->where('sales.customer_id', (int)$filters['customer_id']);
        }

        return $query;
    }

    /**
     * Build query for Sale Returns (Output Tax Reversals).
     */
    public function getSaleReturnsQuery(array $filters)
    {
        $query = Returns::with(['customer', 'warehouse']);

        $this->applyCommonFilters($query, $filters, 'returns');

        if (!empty($filters['customer_id']) && (int)$filters['customer_id'] > 0) {
            $query->where('returns.customer_id', (int)$filters['customer_id']);
        }

        return $query;
    }

    /**
     * Build query for Purchases (Input Tax).
     */
    public function getPurchasesQuery(array $filters)
    {
        $query = Purchase::with(['supplier', 'warehouse', 'currency'])
            ->whereNull('deleted_at')
            ->where('status', '!=', 3) // Exclude Draft/Pending if unreceived
            ->excludeNonSupplierOpening('purchases.purchase_type');

        $this->applyCommonFilters($query, $filters, 'purchases');

        if (!empty($filters['supplier_id']) && (int)$filters['supplier_id'] > 0) {
            $query->where('purchases.supplier_id', (int)$filters['supplier_id']);
        }

        return $query;
    }

    /**
     * Build query for Purchase Returns (Input Tax Reversals).
     */
    public function getPurchaseReturnsQuery(array $filters)
    {
        $query = ReturnPurchase::with(['supplier', 'warehouse']);

        $this->applyCommonFilters($query, $filters, 'return_purchases');

        if (!empty($filters['supplier_id']) && (int)$filters['supplier_id'] > 0) {
            $query->where('return_purchases.supplier_id', (int)$filters['supplier_id']);
        }

        return $query;
    }

    /**
     * Build query for Expenses.
     */
    public function getExpensesQuery(array $filters)
    {
        $query = Expense::with(['warehouse', 'expenseCategory', 'employee'])
            ->where(function ($q) {
                $q->whereNull('accounting_status')
                  ->orWhere('accounting_status', '!=', 'reversed');
            });

        $this->applyCommonFilters($query, $filters, 'expenses');

        if (!empty($filters['expense_category_id']) && (int)$filters['expense_category_id'] > 0) {
            $query->where('expenses.expense_category_id', (int)$filters['expense_category_id']);
        }

        return $query;
    }

    /**
     * Get Normalized Output Tax Transactions (Sales - Returns).
     */
    public function getOutputTaxTransactions(array $filters): array
    {
        $sales = $this->getSalesQuery($filters)->get();
        $returns = $this->getSaleReturnsQuery($filters)->get();

        $rows = [];

        foreach ($sales as $sale) {
            $rate = (float) ($sale->exchange_rate ?: 1);
            $taxAmount = ((float) $sale->total_tax + (float) $sale->order_tax) / $rate;
            $totalAmount = (float) $sale->grand_total / $rate;
            $taxableAmount = ((float) $sale->grand_total - ((float) $sale->total_tax + (float) $sale->order_tax + (float) $sale->shipping_cost)) / $rate;
            $discount = ((float) $sale->total_discount + (float) $sale->order_discount) / $rate;

            $taxLabel = $this->formatTaxLabel((float) $sale->order_tax_rate, $taxAmount);

            $rows[] = [
                'id' => 'sale_' . $sale->id,
                'source_type' => 'sale',
                'source_id' => $sale->id,
                'date' => Carbon::parse($sale->created_at)->format(config('date_format') ?: 'Y-m-d'),
                'raw_date' => $sale->created_at ? $sale->created_at->toDateTimeString() : '',
                'reference' => $sale->reference_no,
                'warehouse_id' => $sale->warehouse_id,
                'warehouse_name' => $sale->warehouse->name ?? 'N/A',
                'contact_id' => $sale->customer_id,
                'contact_name' => $sale->customer->name ?? 'Walk-in Customer',
                'tax_number' => $sale->customer->tax_no ?? '-',
                'taxable_amount' => round($taxableAmount, 4),
                'discount' => round($discount, 4),
                'tax_name_rate' => $taxLabel,
                'tax_amount' => round($taxAmount, 4),
                'total_amount' => round($totalAmount, 4),
                'payment_method' => $this->resolveSalePaymentMethod($sale),
                'currency' => $sale->currency->code ?? config('currency'),
                'exchange_rate' => $rate,
                'direction' => 'output',
                'is_return' => false,
                'type_badge' => '<span class="badge badge-success">' . __('db.Sale') . '</span>',
                'components' => [
                    [
                        'name' => 'Tax',
                        'rate' => (float) $sale->order_tax_rate,
                        'amount' => round($taxAmount, 4),
                    ]
                ],
            ];
        }

        foreach ($returns as $ret) {
            $rate = (float) ($ret->exchange_rate ?: 1);
            $taxAmount = -1 * (((float) $ret->total_tax + (float) $ret->order_tax) / $rate);
            $totalAmount = -1 * ((float) $ret->grand_total / $rate);
            $taxableAmount = -1 * (((float) $ret->grand_total - ((float) $ret->total_tax + (float) $ret->order_tax)) / $rate);
            $discount = -1 * ((float) $ret->total_discount / $rate);

            $taxLabel = $this->formatTaxLabel((float) $ret->order_tax_rate, abs($taxAmount));

            $rows[] = [
                'id' => 'return_' . $ret->id,
                'source_type' => 'sale_return',
                'source_id' => $ret->id,
                'date' => Carbon::parse($ret->created_at)->format(config('date_format') ?: 'Y-m-d'),
                'raw_date' => $ret->created_at ? $ret->created_at->toDateTimeString() : '',
                'reference' => $ret->reference_no,
                'warehouse_id' => $ret->warehouse_id,
                'warehouse_name' => $ret->warehouse->name ?? 'N/A',
                'contact_id' => $ret->customer_id,
                'contact_name' => $ret->customer->name ?? 'Walk-in Customer',
                'tax_number' => $ret->customer->tax_no ?? '-',
                'taxable_amount' => round($taxableAmount, 4),
                'discount' => round($discount, 4),
                'tax_name_rate' => $taxLabel,
                'tax_amount' => round($taxAmount, 4),
                'total_amount' => round($totalAmount, 4),
                'payment_method' => '-',
                'currency' => config('currency'),
                'exchange_rate' => $rate,
                'direction' => 'output',
                'is_return' => true,
                'type_badge' => '<span class="badge badge-danger">' . __('db.Return') . '</span>',
                'components' => [
                    [
                        'name' => 'Tax Reversal',
                        'rate' => (float) $ret->order_tax_rate,
                        'amount' => round($taxAmount, 4),
                    ]
                ],
            ];
        }

        // Sort descending by created_at date
        usort($rows, fn($a, $b) => strcmp($b['raw_date'], $a['raw_date']));

        return $rows;
    }

    /**
     * Get Normalized Input Tax Transactions (Purchases - Purchase Returns).
     */
    public function getInputTaxTransactions(array $filters): array
    {
        $purchases = $this->getPurchasesQuery($filters)->get();
        $returns = $this->getPurchaseReturnsQuery($filters)->get();

        $rows = [];

        foreach ($purchases as $purchase) {
            $rate = (float) ($purchase->exchange_rate ?: 1);
            $taxAmount = ((float) $purchase->total_tax + (float) $purchase->order_tax) / $rate;
            $totalAmount = (float) $purchase->grand_total / $rate;
            $taxableAmount = ((float) $purchase->grand_total - ((float) $purchase->total_tax + (float) $purchase->order_tax + (float) $purchase->shipping_cost)) / $rate;
            $discount = ((float) $purchase->total_discount + (float) $purchase->order_discount) / $rate;

            $taxLabel = $this->formatTaxLabel((float) $purchase->order_tax_rate, $taxAmount);

            $rows[] = [
                'id' => 'purchase_' . $purchase->id,
                'source_type' => 'purchase',
                'source_id' => $purchase->id,
                'date' => Carbon::parse($purchase->created_at)->format(config('date_format') ?: 'Y-m-d'),
                'raw_date' => $purchase->created_at ? $purchase->created_at->toDateTimeString() : '',
                'reference' => $purchase->reference_no,
                'warehouse_id' => $purchase->warehouse_id,
                'warehouse_name' => $purchase->warehouse->name ?? 'N/A',
                'contact_id' => $purchase->supplier_id,
                'contact_name' => $purchase->supplier->name ?? 'N/A',
                'tax_number' => $purchase->supplier->vat_number ?? '-',
                'taxable_amount' => round($taxableAmount, 4),
                'discount' => round($discount, 4),
                'tax_name_rate' => $taxLabel,
                'tax_amount' => round($taxAmount, 4),
                'total_amount' => round($totalAmount, 4),
                'payment_method' => $this->resolvePurchasePaymentStatus($purchase),
                'currency' => $purchase->currency->code ?? config('currency'),
                'exchange_rate' => $rate,
                'direction' => 'input',
                'is_return' => false,
                'type_badge' => '<span class="badge badge-success">' . __('db.Purchase') . '</span>',
                'components' => [
                    [
                        'name' => 'Tax',
                        'rate' => (float) $purchase->order_tax_rate,
                        'amount' => round($taxAmount, 4),
                    ]
                ],
            ];
        }

        foreach ($returns as $ret) {
            $rate = (float) ($ret->exchange_rate ?: 1);
            $taxAmount = -1 * (((float) $ret->total_tax + (float) $ret->order_tax) / $rate);
            $totalAmount = -1 * ((float) $ret->grand_total / $rate);
            $taxableAmount = -1 * (((float) $ret->grand_total - ((float) $ret->total_tax + (float) $ret->order_tax)) / $rate);
            $discount = -1 * ((float) $ret->total_discount / $rate);

            $taxLabel = $this->formatTaxLabel((float) $ret->order_tax_rate, abs($taxAmount));

            $rows[] = [
                'id' => 'return_purchase_' . $ret->id,
                'source_type' => 'purchase_return',
                'source_id' => $ret->id,
                'date' => Carbon::parse($ret->created_at)->format(config('date_format') ?: 'Y-m-d'),
                'raw_date' => $ret->created_at ? $ret->created_at->toDateTimeString() : '',
                'reference' => $ret->reference_no,
                'warehouse_id' => $ret->warehouse_id,
                'warehouse_name' => $ret->warehouse->name ?? 'N/A',
                'contact_id' => $ret->supplier_id,
                'contact_name' => $ret->supplier->name ?? 'N/A',
                'tax_number' => $ret->supplier->vat_number ?? '-',
                'taxable_amount' => round($taxableAmount, 4),
                'discount' => round($discount, 4),
                'tax_name_rate' => $taxLabel,
                'tax_amount' => round($taxAmount, 4),
                'total_amount' => round($totalAmount, 4),
                'payment_method' => '-',
                'currency' => config('currency'),
                'exchange_rate' => $rate,
                'direction' => 'input',
                'is_return' => true,
                'type_badge' => '<span class="badge badge-danger">' . __('db.Return') . '</span>',
                'components' => [
                    [
                        'name' => 'Tax Reversal',
                        'rate' => (float) $ret->order_tax_rate,
                        'amount' => round($taxAmount, 4),
                    ]
                ],
            ];
        }

        // Sort descending by created_at date
        usort($rows, fn($a, $b) => strcmp($b['raw_date'], $a['raw_date']));

        return $rows;
    }

    /**
     * Get Normalized Expense Tax Transactions.
     */
    public function getExpenseTaxTransactions(array $filters): array
    {
        $expenses = $this->getExpensesQuery($filters)->get();

        $rows = [];

        foreach ($expenses as $exp) {
            $taxAmount = (float) ($exp->tax ?? 0);
            $totalAmount = (float) $exp->amount;
            $taxableAmount = $totalAmount - $taxAmount;

            $taxLabel = $exp->tax_name
                ? ($exp->tax_name . ' (' . (float)$exp->tax_rate . '%)')
                : ($taxAmount > 0 ? ((float)$exp->tax_rate . '%') : 'No Tax');

            $categoryName = $exp->expense_category_id == 0
                ? __('db.employee_advance')
                : ($exp->expenseCategory->name ?? 'General Expense');

            $rows[] = [
                'id' => 'expense_' . $exp->id,
                'source_type' => 'expense',
                'source_id' => $exp->id,
                'date' => Carbon::parse($exp->created_at)->format(config('date_format') ?: 'Y-m-d'),
                'raw_date' => $exp->created_at ? $exp->created_at->toDateTimeString() : '',
                'reference' => $exp->reference_no,
                'warehouse_id' => $exp->warehouse_id,
                'warehouse_name' => $exp->warehouse->name ?? 'N/A',
                'category_name' => $categoryName,
                'contact_id' => $exp->employee_id,
                'contact_name' => $exp->employee->name ?? '-',
                'tax_number' => '-',
                'taxable_amount' => round($taxableAmount, 4),
                'discount' => 0.0,
                'tax_name_rate' => $taxLabel,
                'tax_amount' => round($taxAmount, 4),
                'total_amount' => round($totalAmount, 4),
                'payment_method' => $exp->type === 'advance' ? __('db.advance') : __('db.Expense'),
                'currency' => config('currency'),
                'exchange_rate' => 1.0,
                'direction' => 'expense',
                'is_return' => false,
                'type_badge' => '<span class="badge badge-info">' . __('db.Expense') . '</span>',
                'components' => [
                    [
                        'name' => $exp->tax_name ?: 'Tax',
                        'rate' => (float) ($exp->tax_rate ?? 0),
                        'amount' => round($taxAmount, 4),
                    ]
                ],
            ];
        }

        // Sort descending by created_at date
        usort($rows, fn($a, $b) => strcmp($b['raw_date'], $a['raw_date']));

        return $rows;
    }

    /**
     * Calculate Totals for Output Tax (Sales - Sale Returns).
     */
    public function getOutputTaxTotals(array $filters): array
    {
        $transactions = $this->getOutputTaxTransactions($filters);

        $taxable = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($transactions as $row) {
            $taxable += $row['taxable_amount'];
            $discount += $row['discount'];
            $tax += $row['tax_amount'];
            $total += $row['total_amount'];
        }

        return [
            'count' => count($transactions),
            'taxable_amount' => round($taxable, 4),
            'discount' => round($discount, 4),
            'tax_amount' => round($tax, 4),
            'total_amount' => round($total, 4),
        ];
    }

    /**
     * Calculate Totals for Input Tax (Purchases - Purchase Returns).
     */
    public function getInputTaxTotals(array $filters): array
    {
        $transactions = $this->getInputTaxTransactions($filters);

        $taxable = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($transactions as $row) {
            $taxable += $row['taxable_amount'];
            $discount += $row['discount'];
            $tax += $row['tax_amount'];
            $total += $row['total_amount'];
        }

        return [
            'count' => count($transactions),
            'taxable_amount' => round($taxable, 4),
            'discount' => round($discount, 4),
            'tax_amount' => round($tax, 4),
            'total_amount' => round($total, 4),
        ];
    }

    /**
     * Calculate Totals for Expense Tax.
     */
    public function getExpenseTaxTotals(array $filters): array
    {
        $transactions = $this->getExpenseTaxTransactions($filters);

        $taxable = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($transactions as $row) {
            $taxable += $row['taxable_amount'];
            $tax += $row['tax_amount'];
            $total += $row['total_amount'];
        }

        return [
            'count' => count($transactions),
            'taxable_amount' => round($taxable, 4),
            'tax_amount' => round($tax, 4),
            'total_amount' => round($total, 4),
        ];
    }

    /**
     * Consolidated Summary for Cards and Net Tax Position.
     */
    public function getConsolidatedSummary(array $filters): array
    {
        $outputTotals = $this->getOutputTaxTotals($filters);
        $inputTotals = $this->getInputTaxTotals($filters);
        $expenseTotals = $this->getExpenseTaxTotals($filters);

        $outputTax = $outputTotals['tax_amount'];
        $inputTax = $inputTotals['tax_amount'];
        $expenseTax = $expenseTotals['tax_amount'];

        // Net tax payable to authority = Output Tax - Input Tax
        $netTaxPosition = $outputTax - $inputTax;

        return [
            'output_tax' => $outputTax,
            'output_taxable' => $outputTotals['taxable_amount'],
            'output_total' => $outputTotals['total_amount'],
            'input_tax' => $inputTax,
            'input_taxable' => $inputTotals['taxable_amount'],
            'input_total' => $inputTotals['total_amount'],
            'expense_tax' => $expenseTax,
            'expense_taxable' => $expenseTotals['taxable_amount'],
            'expense_total' => $expenseTotals['total_amount'],
            'net_tax_position' => $netTaxPosition,
            'decimal' => config('decimal') ?: 2,
        ];
    }

    protected function formatTaxLabel(float $rate, float $amount): string
    {
        if ($rate > 0) {
            return $rate . '%';
        }
        if ($amount > 0) {
            return __('db.Tax Applied');
        }
        return __('db.No Tax');
    }

    protected function resolveSalePaymentMethod(Sale $sale): string
    {
        if ($sale->payment_status == 4) {
            return __('db.Paid');
        } elseif ($sale->payment_status == 2) {
            return __('db.Due');
        } elseif ($sale->payment_status == 1) {
            return __('db.Pending');
        } elseif ($sale->payment_status == 3) {
            return __('db.Partial');
        }
        return '-';
    }

    protected function resolvePurchasePaymentStatus(Purchase $purchase): string
    {
        if ($purchase->payment_status == 2) {
            return __('db.Paid');
        } elseif ($purchase->payment_status == 1) {
            return __('db.Due');
        } elseif ($purchase->payment_status == 3) {
            return __('db.Partial');
        }
        return '-';
    }
}
