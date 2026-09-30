<?php

namespace App\Services\Profitability;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitabilityService
{
    public const REVENUE_RECOGNIZED_SALE_STATUSES = [1, 4];

    private const DEFAULT_SORT_COLUMNS = [
        'product' => 'product_name',
        'product_code' => 'product_code',
        'net_quantity' => 'net_quantity',
        'gross_sales' => 'gross_sales',
        'discounts' => 'discounts',
        'returns' => 'returned_revenue',
        'net_sales' => 'net_sales',
        'net_cost' => 'net_cost',
        'gross_profit' => 'gross_profit',
        'gross_margin' => 'gross_margin',
        'invoice_count' => 'invoice_count',
    ];

    private const DEFAULT_GROUP_SORT_COLUMNS = [
        'group' => 'name',
        'product_count' => 'product_count',
        'invoice_count' => 'invoice_count',
        'net_quantity' => 'net_quantity',
        'gross_sales' => 'gross_sales',
        'discounts' => 'discounts',
        'returns' => 'returned_revenue',
        'net_sales' => 'net_sales',
        'net_cost' => 'net_cost',
        'gross_profit' => 'gross_profit',
        'gross_margin' => 'gross_margin',
        'profit_contribution' => 'profit_contribution',
    ];

    private const CUSTOMER_SORT_COLUMNS = [
        'group' => 'name',
        'code' => 'code',
        'location_count' => 'location_count',
        'product_count' => 'product_count',
        'invoice_count' => 'invoice_count',
        'net_quantity' => 'net_quantity',
        'gross_sales' => 'gross_sales',
        'discounts' => 'discounts',
        'returns' => 'returned_revenue',
        'net_sales' => 'net_sales',
        'net_cost' => 'net_cost',
        'gross_profit' => 'gross_profit',
        'gross_margin' => 'gross_margin',
        'profit_contribution' => 'profit_contribution',
    ];

    private const LOCATION_SORT_COLUMNS = [
        'group' => 'name',
        'customer_count' => 'customer_count',
        'product_count' => 'product_count',
        'invoice_count' => 'invoice_count',
        'net_quantity' => 'net_quantity',
        'gross_sales' => 'gross_sales',
        'discounts' => 'discounts',
        'returns' => 'returned_revenue',
        'net_sales' => 'net_sales',
        'net_cost' => 'net_cost',
        'gross_profit' => 'gross_profit',
        'gross_margin' => 'gross_margin',
        'profit_contribution' => 'profit_contribution',
    ];

    private const INVOICE_SORT_COLUMNS = [
        'invoice' => 'invoice',
        'sale_date' => 'sale_date_sort',
        'period_activity' => 'period_activity_sort',
        'customer' => 'customer_name',
        'location' => 'location_name',
        'cashier' => 'cashier_name',
        'product_count' => 'product_count',
        'net_quantity' => 'net_quantity',
        'gross_sales' => 'gross_sales',
        'discounts' => 'discounts',
        'returns' => 'returned_revenue',
        'net_sales' => 'net_sales',
        'net_cost' => 'net_cost',
        'gross_profit' => 'gross_profit',
        'gross_margin' => 'gross_margin',
        'cost_quality' => 'cost_quality_sort',
    ];

    public function productRows(ProfitabilityFilter $filter): Collection
    {
        $contributions = $this->contributionRows($filter);
        $rows = [];

        foreach ($contributions as $row) {
            $displayKey = $this->displayKeyFromParts((int) $row['product_id'], (int) $row['variant_id'], $filter->groupVariants);

            $rows[$displayKey] ??= $this->blankProductRowFromContribution($row, $filter->groupVariants);

            if (! empty($row['invoice_id'])) {
                $rows[$displayKey]['invoice_ids'][(int) $row['invoice_id']] = true;
            }

            $rows[$displayKey]['sold_quantity'] += (float) $row['sold_quantity'];
            $rows[$displayKey]['returned_quantity'] += (float) $row['returned_quantity'];
            $rows[$displayKey]['gross_sales'] += (float) $row['gross_sales'];
            $rows[$displayKey]['line_discounts'] += (float) $row['line_discounts'];
            $rows[$displayKey]['invoice_discounts'] += (float) $row['invoice_discounts'];
            $rows[$displayKey]['returned_revenue'] += (float) $row['returned_revenue'];
            $rows[$displayKey]['sold_cost'] += (float) $row['sold_cost'];
            $rows[$displayKey]['returned_cost'] += (float) $row['returned_cost'];
            $rows[$displayKey]['estimated_cost'] = $rows[$displayKey]['estimated_cost'] || (bool) $row['estimated_cost'];

            foreach ($row['cost_basis_codes'] as $basis) {
                $rows[$displayKey]['cost_basis_codes'][$basis] = true;
            }
        }

        foreach ($rows as $displayKey => &$row) {
            $row['invoice_count'] = count($row['invoice_ids']);
            $row['discounts'] = $row['line_discounts'] + $row['invoice_discounts'];
            $row['net_quantity'] = $row['sold_quantity'] - $row['returned_quantity'];
            $row['net_sales'] = $row['gross_sales'] - $row['discounts'] - $row['returned_revenue'];
            $row['net_cost'] = $row['sold_cost'] - $row['returned_cost'];
            $row['gross_profit'] = $row['net_sales'] - $row['net_cost'];
            $row['gross_margin'] = abs($row['net_sales']) > 0.000001
                ? ($row['gross_profit'] / $row['net_sales']) * 100
                : null;
            $row['cost_basis_codes'] = array_keys($row['cost_basis_codes']);
            unset($row['invoice_ids']);
        }
        unset($row);

        return collect(array_values($rows));
    }

    public function dataTable(ProfitabilityFilter $filter): array
    {
        $rows = $this->productRows($filter);
        $recordsTotal = $rows->count();

        if ($filter->hasSearch()) {
            $search = $filter->normalizedSearch();
            $rows = $rows->filter(function (array $row) use ($search) {
                return str_contains(mb_strtolower($row['product_name']), $search)
                    || str_contains(mb_strtolower($row['product_code']), $search)
                    || str_contains(mb_strtolower($row['variant_name'] ?? ''), $search)
                    || str_contains(mb_strtolower($row['variant_code'] ?? ''), $search);
            })->values();
        }

        $recordsFiltered = $rows->count();
        $totals = $this->totals($rows);
        $sortedRows = $this->sortRows($rows, $filter);
        $pagedRows = $filter->limit === -1
            ? $sortedRows->values()
            : $sortedRows->slice($filter->offset, $filter->limit)->values();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $pagedRows,
            'totals' => $totals,
        ];
    }

    public function categoryRows(ProfitabilityFilter $filter): Collection
    {
        return $this->dimensionRows($filter, 'category');
    }

    public function brandRows(ProfitabilityFilter $filter): Collection
    {
        return $this->dimensionRows($filter, 'brand');
    }

    public function categoryDataTable(ProfitabilityFilter $filter): array
    {
        return $this->dimensionDataTable($filter, 'category');
    }

    public function brandDataTable(ProfitabilityFilter $filter): array
    {
        return $this->dimensionDataTable($filter, 'brand');
    }

    public function customerRows(ProfitabilityFilter $filter): Collection
    {
        return $this->transactionDimensionRows($filter, 'customer');
    }

    public function locationRows(ProfitabilityFilter $filter): Collection
    {
        return $this->transactionDimensionRows($filter, 'location');
    }

    public function customerDataTable(ProfitabilityFilter $filter): array
    {
        return $this->transactionDimensionDataTable($filter, 'customer');
    }

    public function locationDataTable(ProfitabilityFilter $filter): array
    {
        return $this->transactionDimensionDataTable($filter, 'location');
    }

    public function invoiceRows(ProfitabilityFilter $filter): Collection
    {
        return $this->invoiceDimensionRows($filter);
    }

    public function invoiceDataTable(ProfitabilityFilter $filter): array
    {
        $rows = $this->invoiceRows($filter);
        $recordsTotal = $rows->count();

        if ($filter->hasSearch()) {
            $search = $filter->normalizedSearch();
            $rows = $rows->filter(function (array $row) use ($search) {
                return str_contains(mb_strtolower($row['search_text']), $search);
            })->values();
        }

        $recordsFiltered = $rows->count();
        $totals = $this->invoiceTotals($rows);
        $sortedRows = $this->sortInvoiceRows($rows, $filter);
        $pagedRows = $filter->limit === -1
            ? $sortedRows->values()
            : $sortedRows->slice($filter->offset, $filter->limit)->values();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $pagedRows,
            'totals' => $totals,
        ];
    }

    public function contributionRows(ProfitabilityFilter $filter): Collection
    {
        $lineRows = $this->saleContributionLineRows($filter)
            ->merge($this->returnContributionLineRows($filter));

        $productIds = $lineRows
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = $this->products($productIds);
        $costProductIds = $products->keys()->map(fn ($id) => (int) $id)->values()->all();
        $variants = $this->variants($costProductIds);
        $costContext = $this->costContext($costProductIds);
        $invoiceAllocations = $this->invoiceDiscountAllocationsForSales(
            $lineRows->pluck('sale_id')->filter()->unique()->values()->all()
        );

        return $lineRows->map(function ($row) use ($products, $variants, $costContext, $invoiceAllocations) {
            $costKey = $this->costKey($row);
            $product = $products->get((int) $row->product_id);
            $variant = $variants->get($this->variantLookupKey((int) $row->product_id, (int) $row->variant_id));
            $cost = $this->resolveContributionCost($row, $product, $variants, $products, $costContext);
            $type = (string) $row->contribution_type;
            $soldQuantity = $type === 'sale' ? (float) $row->quantity : 0.0;
            $returnedQuantity = $type === 'return' ? (float) $row->quantity : 0.0;
            $invoiceDiscount = 0.0;
            $returnedRevenue = 0.0;

            if ($type === 'sale') {
                $invoiceDiscount = (float) ($invoiceAllocations['by_line_id'][(int) $row->line_id] ?? 0);
            } else {
                $saleId = (int) ($row->sale_id ?? 0);
                $originalLine = $invoiceAllocations['by_sale_cost_key'][$saleId][$costKey] ?? null;

                if ($originalLine && (float) $originalLine['quantity'] > 0) {
                    $invoiceDiscount = $this->roundMoney(
                        ((float) $originalLine['allocation'] * $returnedQuantity) / (float) $originalLine['quantity']
                    );
                }

                $returnedRevenue = (float) $row->line_revenue - $invoiceDiscount;
                $invoiceDiscount = 0.0;
            }

            $grossSales = $type === 'sale' ? (float) $row->gross_sales : 0.0;
            $lineDiscounts = $type === 'sale' ? (float) $row->line_discounts : 0.0;
            $soldCost = $type === 'sale' ? (float) $cost['total_cost'] : 0.0;
            $returnedCost = $type === 'return' ? (float) $cost['total_cost'] : 0.0;
            $discounts = $lineDiscounts + $invoiceDiscount;
            $netSales = $grossSales - $discounts - $returnedRevenue;
            $netCost = $soldCost - $returnedCost;
            $grossProfit = $netSales - $netCost;
            $variantName = $variant->variant_name ?? null;
            $variantCode = $variant->item_code ?? null;

            return [
                'type' => $type,
                'line_id' => (int) $row->line_id,
                'sale_id' => $row->sale_id ? (int) $row->sale_id : null,
                'return_id' => $row->return_id ? (int) $row->return_id : null,
                'invoice_id' => $type === 'sale' && $row->sale_id ? (int) $row->sale_id : null,
                'sale_reference' => $row->sale_reference ?? null,
                'sale_date' => $row->sale_date ?? null,
                'return_reference' => $row->return_reference ?? null,
                'return_date' => $row->return_date ?? null,
                'user_id' => (int) ($row->user_id ?? 0),
                'product_id' => (int) $row->product_id,
                'variant_id' => (int) $row->variant_id,
                'product_batch_id' => (int) $row->product_batch_id,
                'imei_number' => $row->imei_number ?? null,
                'category_id' => $product->category_id ?? null,
                'brand_id' => $product->brand_id ?? null,
                'customer_id' => (int) ($row->customer_id ?? 0),
                'warehouse_id' => (int) ($row->warehouse_id ?? 0),
                'product_name' => $product->name ?? __('db.shift_deleted_successfully'),
                'product_code' => $product->code ?? '',
                'variant_name' => $variantName,
                'variant_code' => $variantCode,
                'sold_quantity' => $soldQuantity,
                'returned_quantity' => $returnedQuantity,
                'net_quantity' => $soldQuantity - $returnedQuantity,
                'gross_sales' => $grossSales,
                'line_discounts' => $lineDiscounts,
                'invoice_discounts' => $invoiceDiscount,
                'discounts' => $discounts,
                'returned_revenue' => $returnedRevenue,
                'sold_cost' => $soldCost,
                'returned_cost' => $returnedCost,
                'net_sales' => $netSales,
                'net_cost' => $netCost,
                'gross_profit' => $grossProfit,
                'gross_margin' => abs($netSales) > 0.000001 ? ($grossProfit / $netSales) * 100 : null,
                'estimated_cost' => (bool) $cost['estimated'],
                'cost_basis_codes' => [$cost['basis']],
            ];
        })->values();
    }

    public function saleNetCost(int $saleId): float
    {
        if ($saleId <= 0) {
            return 0.0;
        }

        $saleRows = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('units as u', 'u.id', '=', 'ps.sale_unit_id')
            ->where('s.id', $saleId)
            ->whereNull('s.deleted_at')
            ->selectRaw(''
                . "'sale' as contribution_type, "
                . 'ps.id as line_id, '
                . 'NULL as return_id, '
                . 'ps.sale_id, '
                . 's.created_at as sale_date, '
                . 'NULL as return_date, '
                . 'ps.product_id, '
                . 'COALESCE(ps.variant_id, 0) as variant_id, '
                . 'COALESCE(ps.product_batch_id, 0) as product_batch_id, '
                . 'ps.imei_number, '
                . 'COALESCE(s.warehouse_id, 0) as warehouse_id, '
                . $this->convertedQuantitySql('ps.qty', 'u') . ' as quantity'
            )
            ->get();

        $returnRows = DB::table('product_returns as pr')
            ->join('returns as r', 'r.id', '=', 'pr.return_id')
            ->leftJoin('sales as s', 's.id', '=', 'r.sale_id')
            ->leftJoin('units as u', 'u.id', '=', 'pr.sale_unit_id')
            ->where('r.sale_id', $saleId)
            ->selectRaw(''
                . "'return' as contribution_type, "
                . 'pr.id as line_id, '
                . 'r.id as return_id, '
                . 'r.sale_id, '
                . 's.created_at as sale_date, '
                . 'r.created_at as return_date, '
                . 'pr.product_id, '
                . 'COALESCE(pr.variant_id, 0) as variant_id, '
                . 'COALESCE(pr.product_batch_id, 0) as product_batch_id, '
                . 'pr.imei_number, '
                . 'COALESCE(s.warehouse_id, r.warehouse_id, 0) as warehouse_id, '
                . $this->convertedQuantitySql('pr.qty', 'u') . ' as quantity'
            )
            ->get();

        $rows = $saleRows->merge($returnRows);
        if ($rows->isEmpty()) {
            return 0.0;
        }

        $productIds = $rows
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = $this->products($productIds);
        $costProductIds = $products->keys()->map(fn ($id) => (int) $id)->values()->all();
        $variants = $this->variants($costProductIds);
        $context = $this->costContext($costProductIds);
        $netCost = 0.0;

        foreach ($rows as $row) {
            $product = $products->get((int) $row->product_id);
            $cost = $this->resolveContributionCost($row, $product, $variants, $products, $context);
            $amount = (float) $cost['total_cost'];
            $netCost += (string) $row->contribution_type === 'return' ? -$amount : $amount;
        }

        return max(0.0, $netCost);
    }

    public function totals(Collection $rows): array
    {
        $netSales = (float) $rows->sum('net_sales');
        $grossProfit = (float) $rows->sum('gross_profit');

        return [
            'net_quantity' => (float) $rows->sum('net_quantity'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'discounts' => (float) $rows->sum('discounts'),
            'returned_revenue' => (float) $rows->sum('returned_revenue'),
            'net_sales' => $netSales,
            'net_cost' => (float) $rows->sum('net_cost'),
            'gross_profit' => $grossProfit,
            'gross_margin' => abs($netSales) > 0.000001 ? ($grossProfit / $netSales) * 100 : null,
            'invoice_count' => (int) $rows->sum('invoice_count'),
            'estimated_cost' => $rows->contains(fn ($row) => (bool) $row['estimated_cost']),
            'cost_basis_codes' => $rows->flatMap(fn ($row) => $row['cost_basis_codes'])->unique()->values()->all(),
        ];
    }

    public function dimensionTotals(Collection $rows): array
    {
        $netSales = (float) $rows->sum('net_sales');
        $grossProfit = (float) $rows->sum('gross_profit');

        return [
            'group_count' => $rows->count(),
            'product_count' => (int) $rows->sum('product_count'),
            'net_quantity' => (float) $rows->sum('net_quantity'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'discounts' => (float) $rows->sum('discounts'),
            'returned_revenue' => (float) $rows->sum('returned_revenue'),
            'net_sales' => $netSales,
            'net_cost' => (float) $rows->sum('net_cost'),
            'gross_profit' => $grossProfit,
            'gross_margin' => abs($netSales) > 0.000001 ? ($grossProfit / $netSales) * 100 : null,
            'invoice_count' => null,
            'estimated_cost' => $rows->contains(fn ($row) => (bool) $row['estimated_cost']),
            'cost_basis_codes' => $rows->flatMap(fn ($row) => $row['cost_basis_codes'])->unique()->values()->all(),
        ];
    }

    private function dimensionDataTable(ProfitabilityFilter $filter, string $dimension): array
    {
        $rows = $this->dimensionRows($filter, $dimension);
        $recordsTotal = $rows->count();

        if ($filter->hasSearch()) {
            $search = $filter->normalizedSearch();
            $rows = $rows->filter(function (array $row) use ($search) {
                return str_contains(mb_strtolower($row['name']), $search);
            })->values();

            $rows = $this->withProfitContributions($rows);
        }

        $recordsFiltered = $rows->count();
        $totals = $this->dimensionTotals($rows);
        $sortedRows = $this->sortDimensionRows($rows, $filter);
        $pagedRows = $filter->limit === -1
            ? $sortedRows->values()
            : $sortedRows->slice($filter->offset, $filter->limit)->values();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $pagedRows,
            'totals' => $totals,
        ];
    }

    private function dimensionRows(ProfitabilityFilter $filter, string $dimension): Collection
    {
        $productRows = $this->productRows($filter);
        $dimensionRecords = $this->dimensionRecords($productRows, $dimension);
        $invoiceCounts = $this->dimensionInvoiceCounts($filter, $dimension);
        $groups = [];

        foreach ($productRows as $row) {
            $groupId = $this->dimensionGroupId($row, $dimensionRecords, $dimension);
            $groupKey = $dimension . ':' . $groupId;

            $groups[$groupKey] ??= [
                'dimension' => $dimension,
                'group_id' => $groupId ?: null,
                'name' => $this->dimensionGroupName($groupId, $dimensionRecords, $dimension),
                'product_ids' => [],
                'product_count' => 0,
                'invoice_count' => 0,
                'sold_quantity' => 0.0,
                'returned_quantity' => 0.0,
                'net_quantity' => 0.0,
                'gross_sales' => 0.0,
                'line_discounts' => 0.0,
                'invoice_discounts' => 0.0,
                'discounts' => 0.0,
                'returned_revenue' => 0.0,
                'sold_cost' => 0.0,
                'returned_cost' => 0.0,
                'net_cost' => 0.0,
                'net_sales' => 0.0,
                'gross_profit' => 0.0,
                'gross_margin' => null,
                'profit_contribution' => null,
                'estimated_cost' => false,
                'cost_basis_codes' => [],
            ];

            $groups[$groupKey]['product_ids'][(int) $row['product_id']] = true;
            $groups[$groupKey]['sold_quantity'] += (float) $row['sold_quantity'];
            $groups[$groupKey]['returned_quantity'] += (float) $row['returned_quantity'];
            $groups[$groupKey]['net_quantity'] += (float) $row['net_quantity'];
            $groups[$groupKey]['gross_sales'] += (float) $row['gross_sales'];
            $groups[$groupKey]['line_discounts'] += (float) $row['line_discounts'];
            $groups[$groupKey]['invoice_discounts'] += (float) $row['invoice_discounts'];
            $groups[$groupKey]['discounts'] += (float) $row['discounts'];
            $groups[$groupKey]['returned_revenue'] += (float) $row['returned_revenue'];
            $groups[$groupKey]['sold_cost'] += (float) $row['sold_cost'];
            $groups[$groupKey]['returned_cost'] += (float) $row['returned_cost'];
            $groups[$groupKey]['net_cost'] += (float) $row['net_cost'];
            $groups[$groupKey]['net_sales'] += (float) $row['net_sales'];
            $groups[$groupKey]['gross_profit'] += (float) $row['gross_profit'];
            $groups[$groupKey]['estimated_cost'] = $groups[$groupKey]['estimated_cost'] || (bool) $row['estimated_cost'];

            foreach ($row['cost_basis_codes'] as $basis) {
                $groups[$groupKey]['cost_basis_codes'][$basis] = true;
            }
        }

        foreach ($groups as $groupKey => &$group) {
            $group['product_count'] = count($group['product_ids']);
            $group['invoice_count'] = $invoiceCounts[$groupKey] ?? 0;
            $group['gross_margin'] = abs($group['net_sales']) > 0.000001
                ? ($group['gross_profit'] / $group['net_sales']) * 100
                : null;
            $group['cost_basis_codes'] = array_keys($group['cost_basis_codes']);
            unset($group['product_ids']);
        }
        unset($group);

        return $this->withProfitContributions(collect(array_values($groups)));
    }

    private function transactionDimensionDataTable(ProfitabilityFilter $filter, string $dimension): array
    {
        $rows = $this->transactionDimensionRows($filter, $dimension);
        $recordsTotal = $rows->count();

        if ($filter->hasSearch()) {
            $search = $filter->normalizedSearch();
            $rows = $rows->filter(function (array $row) use ($search) {
                return str_contains(mb_strtolower($row['search_text']), $search);
            })->values();

            $rows = $this->withProfitContributions($rows);
        }

        $recordsFiltered = $rows->count();
        $totals = $this->transactionDimensionTotals($rows);
        $sortedRows = $this->sortTransactionDimensionRows($rows, $filter, $dimension);
        $pagedRows = $filter->limit === -1
            ? $sortedRows->values()
            : $sortedRows->slice($filter->offset, $filter->limit)->values();

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows' => $pagedRows,
            'totals' => $totals,
        ];
    }

    private function transactionDimensionRows(ProfitabilityFilter $filter, string $dimension): Collection
    {
        $contributions = $this->contributionRows($filter);
        $dimensionRecords = $this->transactionDimensionRecords($contributions, $dimension);
        $groups = [];

        foreach ($contributions as $row) {
            $groupId = $this->transactionDimensionGroupId($row, $dimensionRecords, $dimension);
            $groupKey = $dimension . ':' . $groupId;

            $groups[$groupKey] ??= [
                'dimension' => $dimension,
                'group_id' => $groupId ?: null,
                'name' => $this->transactionDimensionGroupName($groupId, $dimensionRecords, $dimension),
                'code' => $this->transactionDimensionGroupCode($groupId, $dimensionRecords, $dimension),
                'search_text' => $this->transactionDimensionSearchText($groupId, $dimensionRecords, $dimension),
                '_product_ids' => [],
                '_invoice_ids' => [],
                '_customer_ids' => [],
                '_warehouse_ids' => [],
                'customer_count' => 0,
                'location_count' => 0,
                'product_count' => 0,
                'invoice_count' => 0,
                'sold_quantity' => 0.0,
                'returned_quantity' => 0.0,
                'net_quantity' => 0.0,
                'gross_sales' => 0.0,
                'line_discounts' => 0.0,
                'invoice_discounts' => 0.0,
                'discounts' => 0.0,
                'returned_revenue' => 0.0,
                'sold_cost' => 0.0,
                'returned_cost' => 0.0,
                'net_cost' => 0.0,
                'net_sales' => 0.0,
                'gross_profit' => 0.0,
                'gross_margin' => null,
                'profit_contribution' => null,
                'estimated_cost' => false,
                'cost_basis_codes' => [],
            ];

            if ((int) $row['product_id'] > 0) {
                $groups[$groupKey]['_product_ids'][(int) $row['product_id']] = true;
            }

            if (! empty($row['invoice_id'])) {
                $groups[$groupKey]['_invoice_ids'][(int) $row['invoice_id']] = true;
            }

            if ((int) $row['customer_id'] > 0) {
                $groups[$groupKey]['_customer_ids'][(int) $row['customer_id']] = true;
            }

            if ((int) $row['warehouse_id'] > 0) {
                $groups[$groupKey]['_warehouse_ids'][(int) $row['warehouse_id']] = true;
            }

            $groups[$groupKey]['sold_quantity'] += (float) $row['sold_quantity'];
            $groups[$groupKey]['returned_quantity'] += (float) $row['returned_quantity'];
            $groups[$groupKey]['net_quantity'] += (float) $row['net_quantity'];
            $groups[$groupKey]['gross_sales'] += (float) $row['gross_sales'];
            $groups[$groupKey]['line_discounts'] += (float) $row['line_discounts'];
            $groups[$groupKey]['invoice_discounts'] += (float) $row['invoice_discounts'];
            $groups[$groupKey]['discounts'] += (float) $row['discounts'];
            $groups[$groupKey]['returned_revenue'] += (float) $row['returned_revenue'];
            $groups[$groupKey]['sold_cost'] += (float) $row['sold_cost'];
            $groups[$groupKey]['returned_cost'] += (float) $row['returned_cost'];
            $groups[$groupKey]['net_cost'] += (float) $row['net_cost'];
            $groups[$groupKey]['net_sales'] += (float) $row['net_sales'];
            $groups[$groupKey]['gross_profit'] += (float) $row['gross_profit'];
            $groups[$groupKey]['estimated_cost'] = $groups[$groupKey]['estimated_cost'] || (bool) $row['estimated_cost'];

            foreach ($row['cost_basis_codes'] as $basis) {
                $groups[$groupKey]['cost_basis_codes'][$basis] = true;
            }
        }

        foreach ($groups as &$group) {
            $group['product_count'] = count($group['_product_ids']);
            $group['invoice_count'] = count($group['_invoice_ids']);
            $group['customer_count'] = count($group['_customer_ids']);
            $group['location_count'] = count($group['_warehouse_ids']);
            $group['gross_margin'] = abs($group['net_sales']) > 0.000001
                ? ($group['gross_profit'] / $group['net_sales']) * 100
                : null;
            $group['cost_basis_codes'] = array_keys($group['cost_basis_codes']);
        }
        unset($group);

        return $this->withProfitContributions(collect(array_values($groups)));
    }

    private function transactionDimensionTotals(Collection $rows): array
    {
        $netSales = (float) $rows->sum('net_sales');
        $grossProfit = (float) $rows->sum('gross_profit');

        return [
            'group_count' => $rows->count(),
            'customer_count' => count($this->unionRowIds($rows, '_customer_ids')),
            'location_count' => count($this->unionRowIds($rows, '_warehouse_ids')),
            'product_count' => count($this->unionRowIds($rows, '_product_ids')),
            'invoice_count' => count($this->unionRowIds($rows, '_invoice_ids')),
            'net_quantity' => (float) $rows->sum('net_quantity'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'discounts' => (float) $rows->sum('discounts'),
            'returned_revenue' => (float) $rows->sum('returned_revenue'),
            'net_sales' => $netSales,
            'net_cost' => (float) $rows->sum('net_cost'),
            'gross_profit' => $grossProfit,
            'gross_margin' => abs($netSales) > 0.000001 ? ($grossProfit / $netSales) * 100 : null,
            'estimated_cost' => $rows->contains(fn ($row) => (bool) $row['estimated_cost']),
            'cost_basis_codes' => $rows->flatMap(fn ($row) => $row['cost_basis_codes'])->unique()->values()->all(),
        ];
    }

    private function invoiceDimensionRows(ProfitabilityFilter $filter): Collection
    {
        $contributions = $this->contributionRows($filter);
        $groups = [];

        foreach ($contributions as $row) {
            $hasResolvableSale = ! empty($row['sale_id'])
                && ($row['type'] === 'sale' || ! empty($row['sale_reference']) || ! empty($row['sale_date']));
            $groupKey = $hasResolvableSale
                ? 'sale:' . (int) $row['sale_id']
                : 'unresolved_return:' . (int) ($row['return_id'] ?: $row['line_id']);

            $groups[$groupKey] ??= [
                'group_key' => $groupKey,
                'sale_id' => $hasResolvableSale ? (int) $row['sale_id'] : null,
                'return_id' => $hasResolvableSale ? null : (int) ($row['return_id'] ?: $row['line_id']),
                'invoice' => $hasResolvableSale
                    ? ((string) ($row['sale_reference'] ?? '') !== '' ? (string) $row['sale_reference'] : __('db.profitability_invoice'))
                    : $this->unresolvedReturnInvoiceLabel($row),
                'sale_date' => $hasResolvableSale ? ($row['sale_date'] ?? null) : null,
                'sale_date_sort' => $hasResolvableSale ? (string) ($row['sale_date'] ?? '') : '',
                'customer_id' => (int) ($row['customer_id'] ?? 0),
                'warehouse_id' => (int) ($row['warehouse_id'] ?? 0),
                'user_id' => (int) ($row['user_id'] ?? 0),
                'customer_name' => '',
                'location_name' => '',
                'cashier_name' => '',
                'period_activity' => null,
                'period_activity_sort' => 0,
                'product_ids' => [],
                'product_count' => 0,
                'sold_quantity' => 0.0,
                'returned_quantity' => 0.0,
                'net_quantity' => 0.0,
                'gross_sales' => 0.0,
                'line_discounts' => 0.0,
                'invoice_discounts' => 0.0,
                'discounts' => 0.0,
                'returned_revenue' => 0.0,
                'sold_cost' => 0.0,
                'returned_cost' => 0.0,
                'net_cost' => 0.0,
                'net_sales' => 0.0,
                'gross_profit' => 0.0,
                'gross_margin' => null,
                'estimated_cost' => false,
                'cost_quality_sort' => 0,
                'cost_basis_codes' => [],
                'has_sale_contribution' => false,
                'has_return_contribution' => false,
                'return_references' => [],
                'search_text' => '',
            ];

            if (! $groups[$groupKey]['customer_id'] && (int) ($row['customer_id'] ?? 0) > 0) {
                $groups[$groupKey]['customer_id'] = (int) $row['customer_id'];
            }

            if (! $groups[$groupKey]['warehouse_id'] && (int) ($row['warehouse_id'] ?? 0) > 0) {
                $groups[$groupKey]['warehouse_id'] = (int) $row['warehouse_id'];
            }

            if (! $groups[$groupKey]['user_id'] && (int) ($row['user_id'] ?? 0) > 0) {
                $groups[$groupKey]['user_id'] = (int) $row['user_id'];
            }

            if ((int) $row['product_id'] > 0) {
                $groups[$groupKey]['product_ids'][(int) $row['product_id']] = true;
            }

            if ($row['type'] === 'sale') {
                $groups[$groupKey]['has_sale_contribution'] = true;
            }

            if ($row['type'] === 'return') {
                $groups[$groupKey]['has_return_contribution'] = true;

                if (! empty($row['return_reference'])) {
                    $groups[$groupKey]['return_references'][(string) $row['return_reference']] = true;
                }
            }

            $groups[$groupKey]['sold_quantity'] += (float) $row['sold_quantity'];
            $groups[$groupKey]['returned_quantity'] += (float) $row['returned_quantity'];
            $groups[$groupKey]['net_quantity'] += (float) $row['net_quantity'];
            $groups[$groupKey]['gross_sales'] += (float) $row['gross_sales'];
            $groups[$groupKey]['line_discounts'] += (float) $row['line_discounts'];
            $groups[$groupKey]['invoice_discounts'] += (float) $row['invoice_discounts'];
            $groups[$groupKey]['discounts'] += (float) $row['discounts'];
            $groups[$groupKey]['returned_revenue'] += (float) $row['returned_revenue'];
            $groups[$groupKey]['sold_cost'] += (float) $row['sold_cost'];
            $groups[$groupKey]['returned_cost'] += (float) $row['returned_cost'];
            $groups[$groupKey]['net_cost'] += (float) $row['net_cost'];
            $groups[$groupKey]['net_sales'] += (float) $row['net_sales'];
            $groups[$groupKey]['gross_profit'] += (float) $row['gross_profit'];
            $groups[$groupKey]['estimated_cost'] = $groups[$groupKey]['estimated_cost'] || (bool) $row['estimated_cost'];

            foreach ($row['cost_basis_codes'] as $basis) {
                $groups[$groupKey]['cost_basis_codes'][$basis] = true;
            }
        }

        $customers = $this->recordsById('customers', collect($groups)->pluck('customer_id')->all());
        $warehouses = $this->recordsById('warehouses', collect($groups)->pluck('warehouse_id')->all());
        $users = $this->recordsById('users', collect($groups)->pluck('user_id')->all());

        foreach ($groups as &$group) {
            $activity = $this->invoicePeriodActivity($group);
            $group['period_activity'] = $activity;
            $group['period_activity_sort'] = match ($activity) {
                'sale' => 1,
                'return' => 2,
                'sale_and_return' => 3,
                default => 0,
            };
            $group['product_count'] = count($group['product_ids']);
            $group['gross_margin'] = abs($group['net_sales']) > 0.000001
                ? ($group['gross_profit'] / $group['net_sales']) * 100
                : null;
            $group['cost_quality_sort'] = $group['estimated_cost'] ? 1 : 0;
            $group['cost_basis_codes'] = array_keys($group['cost_basis_codes']);
            $group['return_references'] = array_keys($group['return_references']);
            $group['customer_name'] = $this->recordName($customers, $group['customer_id'], __('db.profitability_unassigned_customer'));
            $group['location_name'] = $this->recordName($warehouses, $group['warehouse_id'], __('db.profitability_unassigned_location'));
            $group['cashier_name'] = $this->recordName($users, $group['user_id'], __('db.profitability_not_available'));
            $group['search_text'] = mb_strtolower(implode(' ', array_filter([
                $group['invoice'],
                $group['customer_name'],
                $this->recordSearchText($customers, $group['customer_id']),
                $group['location_name'],
                $this->recordSearchText($warehouses, $group['warehouse_id']),
                $group['cashier_name'],
                $this->recordSearchText($users, $group['user_id']),
                $this->invoicePeriodActivityLabel($activity),
                implode(' ', $group['return_references']),
            ])));
            unset($group['product_ids']);
        }
        unset($group);

        return collect(array_values($groups));
    }

    private function invoiceTotals(Collection $rows): array
    {
        $netSales = (float) $rows->sum('net_sales');
        $grossProfit = (float) $rows->sum('gross_profit');

        return [
            'group_count' => $rows->count(),
            'invoice_count' => $rows->count(),
            'return_only_count' => $rows->filter(fn ($row) => $row['period_activity'] === 'return')->count(),
            'estimated_invoice_count' => $rows->filter(fn ($row) => (bool) $row['estimated_cost'])->count(),
            'product_count' => (int) $rows->sum('product_count'),
            'net_quantity' => (float) $rows->sum('net_quantity'),
            'gross_sales' => (float) $rows->sum('gross_sales'),
            'discounts' => (float) $rows->sum('discounts'),
            'returned_revenue' => (float) $rows->sum('returned_revenue'),
            'net_sales' => $netSales,
            'net_cost' => (float) $rows->sum('net_cost'),
            'gross_profit' => $grossProfit,
            'gross_margin' => abs($netSales) > 0.000001 ? ($grossProfit / $netSales) * 100 : null,
            'estimated_cost' => $rows->contains(fn ($row) => (bool) $row['estimated_cost']),
            'cost_basis_codes' => $rows->flatMap(fn ($row) => $row['cost_basis_codes'])->unique()->values()->all(),
        ];
    }

    private function withProfitContributions(Collection $rows): Collection
    {
        $totalGrossProfit = (float) $rows->sum('gross_profit');

        return $rows->map(function (array $row) use ($totalGrossProfit) {
            $row['profit_contribution'] = abs($totalGrossProfit) > 0.000001
                ? ((float) $row['gross_profit'] / $totalGrossProfit) * 100
                : null;

            return $row;
        })->values();
    }

    private function saleContributionLineRows(ProfitabilityFilter $filter): Collection
    {
        $query = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('units as u', 'u.id', '=', 'ps.sale_unit_id')
            ->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->whereNull('s.deleted_at')
            ->whereIn('s.sale_status', self::REVENUE_RECOGNIZED_SALE_STATUSES)
            ->whereBetween('s.created_at', [$filter->startDateTime(), $filter->endDateTime()])
            ->where(function ($q) {
                $q->where('s.sale_type', '!=', 'opening balance')
                    ->orWhereNull('s.sale_type');
            });

        $this->applyCommonFilters($query, $filter, 's', 'p');

        return $query
            ->selectRaw('
                \'sale\' as contribution_type,
                ps.id as line_id,
                NULL as return_id,
                ps.sale_id,
                s.reference_no as sale_reference,
                s.created_at as sale_date,
                NULL as return_reference,
                NULL as return_date,
                COALESCE(s.user_id, 0) as user_id,
                ps.product_id,
                COALESCE(ps.variant_id, 0) as variant_id,
                COALESCE(ps.product_batch_id, 0) as product_batch_id,
                ps.imei_number,
                COALESCE(s.customer_id, 0) as customer_id,
                COALESCE(s.warehouse_id, 0) as warehouse_id,
                ' . $this->convertedQuantitySql('ps.qty', 'u') . ' as quantity,
                ((COALESCE(ps.net_unit_price, 0) * COALESCE(ps.qty, 0)) + COALESCE(ps.discount, 0)) / ' . $this->exchangeRateSql('s') . ' as gross_sales,
                COALESCE(ps.discount, 0) / ' . $this->exchangeRateSql('s') . ' as line_discounts,
                0 as line_revenue
            ')
            ->get();
    }

    private function returnContributionLineRows(ProfitabilityFilter $filter): Collection
    {
        $returnExchangeRateSql = 'COALESCE(NULLIF(r.exchange_rate, 0), NULLIF(s.exchange_rate, 0), 1)';

        $query = DB::table('product_returns as pr')
            ->join('returns as r', 'r.id', '=', 'pr.return_id')
            ->leftJoin('sales as s', 's.id', '=', 'r.sale_id')
            ->leftJoin('units as ru', 'ru.id', '=', 'pr.sale_unit_id')
            ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
            ->whereBetween('r.created_at', [$filter->startDateTime(), $filter->endDateTime()]);

        $this->applyReturnAttributionFilters($query, $filter, 'p');

        return $query
            ->selectRaw('
                \'return\' as contribution_type,
                pr.id as line_id,
                r.id as return_id,
                r.sale_id,
                s.reference_no as sale_reference,
                s.created_at as sale_date,
                r.reference_no as return_reference,
                r.created_at as return_date,
                COALESCE(s.user_id, r.user_id, 0) as user_id,
                pr.product_id,
                COALESCE(pr.variant_id, 0) as variant_id,
                COALESCE(pr.product_batch_id, 0) as product_batch_id,
                pr.imei_number,
                COALESCE(s.customer_id, r.customer_id, 0) as customer_id,
                COALESCE(s.warehouse_id, r.warehouse_id, 0) as warehouse_id,
                ' . $this->convertedQuantitySql('pr.qty', 'ru') . ' as quantity,
                0 as gross_sales,
                0 as line_discounts,
                (COALESCE(pr.net_unit_price, 0) * COALESCE(pr.qty, 0)) / ' . $returnExchangeRateSql . ' as line_revenue
            ')
            ->get();
    }

    private function saleAggregates(ProfitabilityFilter $filter): Collection
    {
        $query = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('units as u', 'u.id', '=', 'ps.sale_unit_id')
            ->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->whereNull('s.deleted_at')
            ->whereIn('s.sale_status', self::REVENUE_RECOGNIZED_SALE_STATUSES)
            ->whereBetween('s.created_at', [$filter->startDateTime(), $filter->endDateTime()])
            ->where(function ($q) {
                $q->where('s.sale_type', '!=', 'opening balance')
                    ->orWhereNull('s.sale_type');
            });

        $this->applyCommonFilters($query, $filter, 's', 'p');

        $lineRows = $query
            ->selectRaw('
                ps.id as line_id,
                ps.sale_id,
                ps.product_id,
                COALESCE(ps.variant_id, 0) as variant_id,
                COALESCE(ps.product_batch_id, 0) as product_batch_id,
                ' . $this->convertedQuantitySql('ps.qty', 'u') . ' as sold_quantity,
                ((COALESCE(ps.net_unit_price, 0) * COALESCE(ps.qty, 0)) + COALESCE(ps.discount, 0)) / ' . $this->exchangeRateSql('s') . ' as gross_sales,
                COALESCE(ps.discount, 0) / ' . $this->exchangeRateSql('s') . ' as line_discounts
            ')
            ->get();

        $invoiceAllocations = $this->invoiceDiscountAllocationsForSales(
            $lineRows->pluck('sale_id')->unique()->values()->all()
        );

        $aggregates = [];

        foreach ($lineRows as $row) {
            $key = $this->costKey($row);

            $aggregates[$key] ??= (object) [
                'product_id' => (int) $row->product_id,
                'variant_id' => (int) $row->variant_id,
                'product_batch_id' => (int) $row->product_batch_id,
                'sold_quantity' => 0.0,
                'gross_sales' => 0.0,
                'line_discounts' => 0.0,
                'invoice_discounts' => 0.0,
            ];

            $aggregates[$key]->sold_quantity += (float) $row->sold_quantity;
            $aggregates[$key]->gross_sales += (float) $row->gross_sales;
            $aggregates[$key]->line_discounts += (float) $row->line_discounts;
            $aggregates[$key]->invoice_discounts += (float) ($invoiceAllocations['by_line_id'][(int) $row->line_id] ?? 0);
        }

        return collect(array_values($aggregates));
    }

    private function returnAggregates(ProfitabilityFilter $filter): Collection
    {
        $returnQuantitySql = $this->convertedQuantitySql('pr.qty', 'ru');
        $returnExchangeRateSql = 'COALESCE(NULLIF(r.exchange_rate, 0), NULLIF(s.exchange_rate, 0), 1)';

        $query = DB::table('product_returns as pr')
            ->join('returns as r', 'r.id', '=', 'pr.return_id')
            ->leftJoin('sales as s', 's.id', '=', 'r.sale_id')
            ->leftJoin('units as ru', 'ru.id', '=', 'pr.sale_unit_id')
            ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
            ->whereBetween('r.created_at', [$filter->startDateTime(), $filter->endDateTime()]);

        $this->applyCommonFilters($query, $filter, 'r', 'p');

        $lineRows = $query
            ->selectRaw('
                pr.id as line_id,
                r.sale_id,
                pr.product_id,
                COALESCE(pr.variant_id, 0) as variant_id,
                COALESCE(pr.product_batch_id, 0) as product_batch_id,
                ' . $returnQuantitySql . ' as returned_quantity,
                (COALESCE(pr.net_unit_price, 0) * COALESCE(pr.qty, 0)) / ' . $returnExchangeRateSql . ' as returned_line_revenue
            ')
            ->get();

        $invoiceAllocations = $this->invoiceDiscountAllocationsForSales(
            $lineRows->pluck('sale_id')->filter()->unique()->values()->all()
        );

        $aggregates = [];

        foreach ($lineRows as $row) {
            $key = $this->costKey($row);
            $saleId = (int) ($row->sale_id ?? 0);
            $returnedQuantity = (float) $row->returned_quantity;
            $originalLine = $invoiceAllocations['by_sale_cost_key'][$saleId][$key] ?? null;
            $invoiceDiscountReversal = 0.0;

            if ($originalLine && (float) $originalLine['quantity'] > 0) {
                $invoiceDiscountReversal = $this->roundMoney(
                    ((float) $originalLine['allocation'] * $returnedQuantity) / (float) $originalLine['quantity']
                );
            }

            $aggregates[$key] ??= (object) [
                'product_id' => (int) $row->product_id,
                'variant_id' => (int) $row->variant_id,
                'product_batch_id' => (int) $row->product_batch_id,
                'returned_quantity' => 0.0,
                'returned_revenue' => 0.0,
            ];

            $aggregates[$key]->returned_quantity += $returnedQuantity;
            $aggregates[$key]->returned_revenue += (float) $row->returned_line_revenue - $invoiceDiscountReversal;
        }

        return collect(array_values($aggregates));
    }

    private function invoiceCounts(ProfitabilityFilter $filter): array
    {
        $query = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->whereNull('s.deleted_at')
            ->whereIn('s.sale_status', self::REVENUE_RECOGNIZED_SALE_STATUSES)
            ->whereBetween('s.created_at', [$filter->startDateTime(), $filter->endDateTime()])
            ->where(function ($q) {
                $q->where('s.sale_type', '!=', 'opening balance')
                    ->orWhereNull('s.sale_type');
            });

        $this->applyCommonFilters($query, $filter, 's', 'p');

        $variantSelect = $filter->groupVariants ? '0' : 'COALESCE(ps.variant_id, 0)';

        return $query
            ->selectRaw('ps.product_id, ' . $variantSelect . ' as variant_id, COUNT(DISTINCT s.id) as invoice_count')
            ->groupBy('ps.product_id', 'variant_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $this->displayKeyFromParts((int) $row->product_id, (int) $row->variant_id, $filter->groupVariants) => (int) $row->invoice_count,
            ])
            ->all();
    }

    private function applyCommonFilters($query, ProfitabilityFilter $filter, string $headerAlias, string $productAlias): void
    {
        if ($filter->hasWarehouseFilter()) {
            $query->where($headerAlias . '.warehouse_id', $filter->warehouseId);
        }

        if ($filter->ownDataOnly && $filter->userId) {
            $query->where($headerAlias . '.user_id', $filter->userId);
        }

        if ($filter->productId) {
            $query->where($productAlias . '.id', $filter->productId);
        }

        if ($filter->categoryId) {
            $query->where($productAlias . '.category_id', $filter->categoryId);
        }

        if ($filter->brandId) {
            $query->where($productAlias . '.brand_id', $filter->brandId);
        }

        if ($filter->customerId) {
            $query->where($headerAlias . '.customer_id', $filter->customerId);
        }
    }

    private function applyReturnAttributionFilters($query, ProfitabilityFilter $filter, string $productAlias): void
    {
        if ($filter->hasWarehouseFilter()) {
            $query->whereRaw('COALESCE(s.warehouse_id, r.warehouse_id, 0) = ?', [$filter->warehouseId]);
        }

        if ($filter->ownDataOnly && $filter->userId) {
            $query->whereRaw('COALESCE(s.user_id, r.user_id, 0) = ?', [$filter->userId]);
        }

        if ($filter->productId) {
            $query->where($productAlias . '.id', $filter->productId);
        }

        if ($filter->categoryId) {
            $query->where($productAlias . '.category_id', $filter->categoryId);
        }

        if ($filter->brandId) {
            $query->where($productAlias . '.brand_id', $filter->brandId);
        }

        if ($filter->customerId) {
            $query->whereRaw('COALESCE(s.customer_id, r.customer_id, 0) = ?', [$filter->customerId]);
        }
    }

    private function dimensionRecords(Collection $productRows, string $dimension): Collection
    {
        $column = $dimension === 'category' ? 'category_id' : 'brand_id';
        $table = $dimension === 'category' ? 'categories' : 'brands';

        $ids = $productRows
            ->pluck($column)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    private function dimensionGroupId(array $row, Collection $dimensionRecords, string $dimension): int
    {
        $id = (int) ($row[$dimension === 'category' ? 'category_id' : 'brand_id'] ?? 0);

        return $id && $dimensionRecords->has($id) ? $id : 0;
    }

    private function dimensionGroupName(int $groupId, Collection $dimensionRecords, string $dimension): string
    {
        if (! $groupId || ! $dimensionRecords->has($groupId)) {
            return $dimension === 'category'
                ? __('db.profitability_unassigned_category')
                : __('db.profitability_unassigned_brand');
        }

        $record = $dimensionRecords->get($groupId);

        return $dimension === 'category'
            ? (string) ($record->name ?? __('db.profitability_unassigned_category'))
            : (string) ($record->title ?? __('db.profitability_unassigned_brand'));
    }

    private function dimensionInvoiceCounts(ProfitabilityFilter $filter, string $dimension): array
    {
        $joinTable = $dimension === 'category' ? 'categories' : 'brands';
        $joinAlias = $dimension === 'category' ? 'c' : 'b';
        $productColumn = $dimension === 'category' ? 'category_id' : 'brand_id';

        $query = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'ps.product_id')
            ->leftJoin($joinTable . ' as ' . $joinAlias, $joinAlias . '.id', '=', 'p.' . $productColumn)
            ->whereNull('s.deleted_at')
            ->whereIn('s.sale_status', self::REVENUE_RECOGNIZED_SALE_STATUSES)
            ->whereBetween('s.created_at', [$filter->startDateTime(), $filter->endDateTime()])
            ->where(function ($q) {
                $q->where('s.sale_type', '!=', 'opening balance')
                    ->orWhereNull('s.sale_type');
            });

        $this->applyCommonFilters($query, $filter, 's', 'p');

        return $query
            ->selectRaw('CASE WHEN ' . $joinAlias . '.id IS NULL THEN 0 ELSE ' . $joinAlias . '.id END as group_id, COUNT(DISTINCT s.id) as invoice_count')
            ->groupBy('group_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $dimension . ':' . (int) $row->group_id => (int) $row->invoice_count,
            ])
            ->all();
    }

    private function transactionDimensionRecords(Collection $contributions, string $dimension): Collection
    {
        $column = $dimension === 'customer' ? 'customer_id' : 'warehouse_id';
        $table = $dimension === 'customer' ? 'customers' : 'warehouses';

        $ids = $contributions
            ->pluck($column)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    private function transactionDimensionGroupId(array $row, Collection $dimensionRecords, string $dimension): int
    {
        $id = (int) ($row[$dimension === 'customer' ? 'customer_id' : 'warehouse_id'] ?? 0);

        return $id && $dimensionRecords->has($id) ? $id : 0;
    }

    private function transactionDimensionGroupName(int $groupId, Collection $dimensionRecords, string $dimension): string
    {
        if (! $groupId || ! $dimensionRecords->has($groupId)) {
            return $dimension === 'customer'
                ? __('db.profitability_unassigned_customer')
                : __('db.profitability_unassigned_location');
        }

        $record = $dimensionRecords->get($groupId);

        return (string) ($record->name ?? ($dimension === 'customer'
            ? __('db.profitability_unassigned_customer')
            : __('db.profitability_unassigned_location')));
    }

    private function transactionDimensionGroupCode(int $groupId, Collection $dimensionRecords, string $dimension): string
    {
        if ($dimension !== 'customer' || ! $groupId || ! $dimensionRecords->has($groupId)) {
            return '';
        }

        $record = $dimensionRecords->get($groupId);

        return (string) ($record->phone_number ?? '');
    }

    private function transactionDimensionSearchText(int $groupId, Collection $dimensionRecords, string $dimension): string
    {
        $parts = [$this->transactionDimensionGroupName($groupId, $dimensionRecords, $dimension)];

        if ($groupId && $dimensionRecords->has($groupId)) {
            $record = $dimensionRecords->get($groupId);
            $parts[] = (string) ($record->phone_number ?? '');
            $parts[] = (string) ($record->email ?? '');
        }

        return mb_strtolower(trim(implode(' ', array_filter($parts))));
    }

    private function sortDimensionRows(Collection $rows, ProfitabilityFilter $filter): Collection
    {
        $sortColumn = self::DEFAULT_GROUP_SORT_COLUMNS[$filter->sortBy] ?? self::DEFAULT_GROUP_SORT_COLUMNS['gross_profit'];
        $descending = strtolower($filter->sortDirection) === 'desc';

        return $rows->sortBy(function (array $row) use ($sortColumn) {
            $value = $row[$sortColumn] ?? null;

            return is_string($value) ? mb_strtolower($value) : $value;
        }, SORT_REGULAR, $descending)->values();
    }

    private function sortTransactionDimensionRows(Collection $rows, ProfitabilityFilter $filter, string $dimension): Collection
    {
        $columns = $dimension === 'customer' ? self::CUSTOMER_SORT_COLUMNS : self::LOCATION_SORT_COLUMNS;
        $sortColumn = $columns[$filter->sortBy] ?? $columns['gross_profit'];
        $descending = strtolower($filter->sortDirection) === 'desc';

        return $rows->sortBy(function (array $row) use ($sortColumn) {
            $value = $row[$sortColumn] ?? null;

            return is_string($value) ? mb_strtolower($value) : $value;
        }, SORT_REGULAR, $descending)->values();
    }

    private function sortInvoiceRows(Collection $rows, ProfitabilityFilter $filter): Collection
    {
        $sortColumn = self::INVOICE_SORT_COLUMNS[$filter->sortBy] ?? self::INVOICE_SORT_COLUMNS['gross_profit'];
        $descending = strtolower($filter->sortDirection) === 'desc';

        return $rows->sortBy(function (array $row) use ($sortColumn) {
            $value = $row[$sortColumn] ?? null;

            return is_string($value) ? mb_strtolower($value) : $value;
        }, SORT_REGULAR, $descending)->values();
    }

    private function invoicePeriodActivity(array $group): string
    {
        if ($group['has_sale_contribution'] && $group['has_return_contribution']) {
            return 'sale_and_return';
        }

        return $group['has_return_contribution'] ? 'return' : 'sale';
    }

    private function invoicePeriodActivityLabel(string $activity): string
    {
        return match ($activity) {
            'sale_and_return' => __('db.profitability_period_sale_and_return'),
            'return' => __('db.profitability_period_return'),
            default => __('db.profitability_period_sale'),
        };
    }

    private function unresolvedReturnInvoiceLabel(array $row): string
    {
        $reference = trim((string) ($row['return_reference'] ?? ''));

        return trim(__('db.profitability_unresolved_return') . ($reference !== '' ? ' - ' . $reference : ''));
    }

    private function recordsById(string $table, array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (empty($ids)) {
            return collect();
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    private function recordName(Collection $records, int $id, string $fallback): string
    {
        if (! $id || ! $records->has($id)) {
            return $fallback;
        }

        return (string) ($records->get($id)->name ?? $fallback);
    }

    private function recordSearchText(Collection $records, int $id): string
    {
        if (! $id || ! $records->has($id)) {
            return '';
        }

        $record = $records->get($id);

        return implode(' ', array_filter([
            $record->name ?? null,
            $record->code ?? null,
            $record->phone_number ?? null,
            $record->email ?? null,
            $record->company_name ?? null,
        ]));
    }

    private function unionRowIds(Collection $rows, string $key): array
    {
        $ids = [];

        foreach ($rows as $row) {
            foreach (array_keys($row[$key] ?? []) as $id) {
                $id = (int) $id;

                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function invoiceDiscountAllocationsForSales(array $saleIds): array
    {
        $saleIds = array_values(array_unique(array_filter(array_map('intval', $saleIds))));

        if (empty($saleIds)) {
            return ['by_line_id' => [], 'by_sale_cost_key' => []];
        }

        $lineRows = DB::table('product_sales as ps')
            ->join('sales as s', 's.id', '=', 'ps.sale_id')
            ->leftJoin('units as u', 'u.id', '=', 'ps.sale_unit_id')
            ->whereIn('ps.sale_id', $saleIds)
            ->whereNull('s.deleted_at')
            ->selectRaw('
                ps.id as line_id,
                ps.sale_id,
                ps.product_id,
                COALESCE(ps.variant_id, 0) as variant_id,
                COALESCE(ps.product_batch_id, 0) as product_batch_id,
                COALESCE(ps.net_unit_price, 0) * COALESCE(ps.qty, 0) as allocation_basis,
                ' . $this->convertedQuantitySql('ps.qty', 'u') . ' as quantity,
                (COALESCE(s.order_discount, 0) + COALESCE(s.coupon_discount, 0)) / ' . $this->exchangeRateSql('s') . ' as invoice_discount
            ')
            ->get();

        $byLineId = $this->allocateInvoiceDiscounts($lineRows);
        $bySaleCostKey = [];

        foreach ($lineRows as $row) {
            $saleId = (int) $row->sale_id;
            $costKey = $this->costKey($row);

            $bySaleCostKey[$saleId][$costKey]['allocation'] = ($bySaleCostKey[$saleId][$costKey]['allocation'] ?? 0)
                + (float) ($byLineId[(int) $row->line_id] ?? 0);
            $bySaleCostKey[$saleId][$costKey]['quantity'] = ($bySaleCostKey[$saleId][$costKey]['quantity'] ?? 0)
                + (float) $row->quantity;
        }

        return [
            'by_line_id' => $byLineId,
            'by_sale_cost_key' => $bySaleCostKey,
        ];
    }

    private function allocateInvoiceDiscounts(Collection $lineRows): array
    {
        $allocations = [];

        foreach ($lineRows->groupBy('sale_id') as $saleLines) {
            $invoiceDiscount = $this->roundMoney((float) ($saleLines->first()->invoice_discount ?? 0));

            foreach ($saleLines as $line) {
                $allocations[(int) $line->line_id] = 0.0;
            }

            if (abs($invoiceDiscount) <= 0.000001) {
                continue;
            }

            $eligibleLines = $saleLines
                ->filter(fn ($line) => (float) $line->allocation_basis > 0)
                ->sort(function ($left, $right) {
                    $basisComparison = (float) $right->allocation_basis <=> (float) $left->allocation_basis;

                    return $basisComparison ?: ((int) $left->line_id <=> (int) $right->line_id);
                })
                ->values();

            $basisTotal = (float) $eligibleLines->sum('allocation_basis');

            if ($basisTotal <= 0) {
                continue;
            }

            $roundedTotal = 0.0;

            foreach ($eligibleLines as $line) {
                $allocation = $this->roundMoney($invoiceDiscount * ((float) $line->allocation_basis / $basisTotal));
                $allocations[(int) $line->line_id] = $allocation;
                $roundedTotal += $allocation;
            }

            $residue = $this->roundMoney($invoiceDiscount - $roundedTotal);

            if (abs($residue) > 0.000001) {
                $largestStableLine = $eligibleLines->first();
                $allocations[(int) $largestStableLine->line_id] = $this->roundMoney(
                    (float) $allocations[(int) $largestStableLine->line_id] + $residue
                );
            }
        }

        return $allocations;
    }

    private function costContext(array $productIds): array
    {
        if (empty($productIds)) {
            return [
                'purchases' => collect(),
                'purchase_returns' => collect(),
                'serial_purchases' => [],
                'serial_purchase_returns' => [],
            ];
        }

        $purchases = DB::table('product_purchases as pp')
            ->join('purchases as p', 'p.id', '=', 'pp.purchase_id')
            ->leftJoin('units as u', 'u.id', '=', 'pp.purchase_unit_id')
            ->whereIn('pp.product_id', $productIds)
            ->whereNull('p.deleted_at')
            ->selectRaw('
                pp.id as line_id,
                pp.purchase_id,
                pp.product_id,
                COALESCE(pp.variant_id, 0) as variant_id,
                COALESCE(pp.product_batch_id, 0) as product_batch_id,
                pp.imei_number,
                COALESCE(p.warehouse_id, 0) as warehouse_id,
                p.created_at as transaction_date,
                ' . $this->convertedQuantitySql('COALESCE(NULLIF(pp.recieved, 0), pp.qty)', 'u') . ' as quantity,
                (COALESCE(pp.total, 0) - COALESCE(pp.tax, 0)) / ' . $this->exchangeRateSql('p') . ' as cost
            ')
            ->get();

        $purchaseReturns = DB::table('purchase_product_return as ppr')
            ->join('return_purchases as rp', 'rp.id', '=', 'ppr.return_id')
            ->leftJoin('units as u', 'u.id', '=', 'ppr.purchase_unit_id')
            ->whereIn('ppr.product_id', $productIds)
            ->selectRaw('
                ppr.id as line_id,
                rp.purchase_id,
                ppr.product_id,
                COALESCE(ppr.variant_id, 0) as variant_id,
                COALESCE(ppr.product_batch_id, 0) as product_batch_id,
                ppr.imei_number,
                COALESCE(rp.warehouse_id, 0) as warehouse_id,
                rp.created_at as transaction_date,
                ' . $this->convertedQuantitySql('ppr.qty', 'u') . ' as quantity,
                (COALESCE(ppr.total, 0) - COALESCE(ppr.tax, 0)) / ' . $this->exchangeRateSql('rp') . ' as cost
            ')
            ->get();

        return [
            'purchases' => $purchases,
            'purchase_returns' => $purchaseReturns,
            'serial_purchases' => $this->indexRowsByIdentifier($purchases),
            'serial_purchase_returns' => $this->indexRowsByIdentifier($purchaseReturns),
        ];
    }

    private function resolveContributionCost($row, $product, Collection $variants, Collection $products, array $context): array
    {
        $quantity = max(0.0, (float) ($row->quantity ?? 0));

        if (! $product) {
            return $this->costResult(0.0, $quantity, 'missing_product', true);
        }

        $asOf = (string) (
            (string) ($row->contribution_type ?? '') === 'return'
                ? (($row->return_date ?? null) ?: ($row->sale_date ?? null) ?: now())
                : (($row->sale_date ?? null) ?: ($row->return_date ?? null) ?: now())
        );
        $warehouseId = (int) ($row->warehouse_id ?? 0);
        $variantId = (int) ($row->variant_id ?? 0);
        $batchId = (int) ($row->product_batch_id ?? 0);

        if (($product->type ?? null) === 'combo') {
            $combo = $this->comboUnitCostAsOf($product, $variantId, $asOf, $warehouseId, $context, $products, $variants);
            return $this->costResult((float) $combo['unit_cost'], $quantity, $combo['basis'], (bool) $combo['estimated']);
        }

        $identifiers = $this->identifierValues($row->imei_number ?? null);
        if (! empty($identifiers)) {
            return $this->specificIdentifierCost(
                $row,
                $product,
                $identifiers,
                $quantity,
                $asOf,
                $warehouseId,
                $context,
                $products,
                $variants
            );
        }

        $legacy = $this->resolveLegacyUnitCost(
            $product,
            $variantId,
            $batchId,
            $asOf,
            $warehouseId,
            $context,
            $products,
            $variants
        );

        return $this->costResult((float) $legacy['unit_cost'], $quantity, $legacy['basis'], (bool) $legacy['estimated']);
    }

    private function specificIdentifierCost(
        $row,
        $product,
        array $identifiers,
        float $quantity,
        string $asOf,
        int $warehouseId,
        array $context,
        Collection $products,
        Collection $variants
    ): array {
        $specificCost = 0.0;
        $matched = 0;

        foreach ($identifiers as $identifier) {
            $purchaseRow = $this->specificIdentifierPurchaseRow($identifier, $row, $asOf, $context);
            if (! $purchaseRow) {
                continue;
            }

            $purchaseQty = (float) ($purchaseRow->quantity ?? 0);
            if ($purchaseQty <= 0) {
                continue;
            }

            $specificCost += (float) $purchaseRow->cost / $purchaseQty;
            $matched++;
        }

        $expectedIdentifierQty = (float) count($identifiers);
        $identifierQuantityMatches = abs($quantity - $expectedIdentifierQty) <= 0.000001;
        $exact = $identifierQuantityMatches && $matched === count($identifiers);

        if ($exact && $quantity > 0) {
            return [
                'unit_cost' => $specificCost / $quantity,
                'total_cost' => $specificCost,
                'basis' => 'imei_specific_purchase_cost',
                'estimated' => false,
            ];
        }

        $fallback = $this->resolveLegacyUnitCost(
            $product,
            (int) ($row->variant_id ?? 0),
            (int) ($row->product_batch_id ?? 0),
            $asOf,
            $warehouseId,
            $context,
            $products,
            $variants
        );

        if (! $identifierQuantityMatches) {
            $specificCost = 0.0;
            $matched = 0;
        }

        $unmatchedQuantity = max(0.0, $quantity - $matched);
        $totalCost = $specificCost + ($unmatchedQuantity * (float) $fallback['unit_cost']);

        return [
            'unit_cost' => $quantity > 0 ? $totalCost / $quantity : (float) $fallback['unit_cost'],
            'total_cost' => $totalCost,
            'basis' => 'imei_specific_purchase_cost_with_fallback',
            'estimated' => true,
        ];
    }

    private function specificIdentifierPurchaseRow(string $identifier, $saleOrReturnRow, string $asOf, array $context): ?object
    {
        $normalized = $this->normalizeIdentifier($identifier);
        $candidates = $context['serial_purchases'][$normalized] ?? [];
        $asOfTimestamp = strtotime($asOf) ?: PHP_INT_MAX;
        $productId = (int) ($saleOrReturnRow->product_id ?? 0);
        $variantId = (int) ($saleOrReturnRow->variant_id ?? 0);
        $batchId = (int) ($saleOrReturnRow->product_batch_id ?? 0);

        usort($candidates, function ($a, $b) {
            return strcmp((string) $b->transaction_date, (string) $a->transaction_date);
        });

        foreach ($candidates as $candidate) {
            if ((int) $candidate->product_id !== $productId) {
                continue;
            }
            if ($variantId && (int) $candidate->variant_id !== $variantId) {
                continue;
            }
            if ($batchId && (int) $candidate->product_batch_id !== $batchId) {
                continue;
            }
            if ((strtotime((string) $candidate->transaction_date) ?: 0) > $asOfTimestamp) {
                continue;
            }
            if ($this->identifierPurchaseWasReturned($normalized, $candidate, $asOfTimestamp, $context)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function identifierPurchaseWasReturned(string $identifier, object $purchaseRow, int $asOfTimestamp, array $context): bool
    {
        foreach ($context['serial_purchase_returns'][$identifier] ?? [] as $returnRow) {
            $returnTimestamp = strtotime((string) $returnRow->transaction_date) ?: 0;
            $purchaseTimestamp = strtotime((string) $purchaseRow->transaction_date) ?: 0;

            if ($returnTimestamp < $purchaseTimestamp || $returnTimestamp > $asOfTimestamp) {
                continue;
            }

            if ((int) ($returnRow->purchase_id ?? 0) > 0
                && (int) ($purchaseRow->purchase_id ?? 0) > 0
                && (int) $returnRow->purchase_id !== (int) $purchaseRow->purchase_id) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function resolveLegacyUnitCost(
        $product,
        int $variantId,
        int $batchId,
        string $asOf,
        int $warehouseId,
        array $context,
        Collection $products,
        Collection $variants
    ): array {
        if (! $product) {
            return $this->zeroCostBasis('missing_product', true);
        }

        if (($product->type ?? null) === 'combo') {
            return $this->comboUnitCostAsOf($product, $variantId, $asOf, $warehouseId, $context, $products, $variants);
        }

        if ($batchId) {
            $aggregate = $this->aggregatePurchaseCostAsOf(
                (int) $product->id,
                $variantId,
                $batchId,
                $asOf,
                null,
                $context
            );

            if ($aggregate) {
                return $this->averageCost($aggregate, 'batch_average_purchase_cost', false);
            }
        }

        if ($variantId) {
            $aggregate = $this->aggregatePurchaseCostAsOf(
                (int) $product->id,
                $variantId,
                null,
                $asOf,
                $warehouseId ?: null,
                $context
            );

            if ($aggregate) {
                return $this->averageCost($aggregate, 'variant_average_purchase_cost', true);
            }

            $aggregate = $this->aggregatePurchaseCostAsOf(
                (int) $product->id,
                $variantId,
                null,
                $asOf,
                null,
                $context
            );

            if ($aggregate) {
                return $this->averageCost($aggregate, 'variant_average_purchase_cost', true);
            }
        }

        $aggregate = $this->aggregatePurchaseCostAsOf(
            (int) $product->id,
            null,
            null,
            $asOf,
            $warehouseId ?: null,
            $context
        );

        if ($aggregate) {
            return $this->averageCost($aggregate, 'product_average_purchase_cost', true);
        }

        $aggregate = $this->aggregatePurchaseCostAsOf(
            (int) $product->id,
            null,
            null,
            $asOf,
            null,
            $context
        );

        if ($aggregate) {
            return $this->averageCost($aggregate, 'product_average_purchase_cost', true);
        }

        if ((float) ($product->production_cost ?? 0) > 0) {
            return [
                'unit_cost' => (float) $product->production_cost,
                'basis' => 'current_production_cost_estimate',
                'estimated' => true,
            ];
        }

        if (in_array($product->type, ['service', 'digital'], true)) {
            $cost = (float) ($product->cost ?? 0);

            return [
                'unit_cost' => $cost,
                'basis' => $cost > 0 ? 'current_service_or_digital_cost_estimate' : 'zero_service_or_digital_cost',
                'estimated' => true,
            ];
        }

        $variant = $variantId ? $variants->get($this->variantLookupKey((int) $product->id, $variantId)) : null;

        return [
            'unit_cost' => (float) ($product->cost ?? 0) + (float) ($variant->additional_cost ?? 0),
            'basis' => 'current_product_cost_estimate',
            'estimated' => true,
        ];
    }

    private function aggregatePurchaseCostAsOf(
        int $productId,
        ?int $variantId,
        ?int $batchId,
        string $asOf,
        ?int $warehouseId,
        array $context
    ): ?array {
        $asOfTimestamp = strtotime($asOf) ?: PHP_INT_MAX;
        $quantity = 0.0;
        $cost = 0.0;

        foreach ($context['purchases'] as $row) {
            if (! $this->costRowMatches($row, $productId, $variantId, $batchId, $warehouseId, $asOfTimestamp)) {
                continue;
            }

            $quantity += (float) $row->quantity;
            $cost += (float) $row->cost;
        }

        foreach ($context['purchase_returns'] as $row) {
            if (! $this->costRowMatches($row, $productId, $variantId, $batchId, $warehouseId, $asOfTimestamp)) {
                continue;
            }

            $quantity -= (float) $row->quantity;
            $cost -= (float) $row->cost;
        }

        if ($quantity <= 0.000001) {
            return null;
        }

        return [
            'quantity' => $quantity,
            'cost' => max(0.0, $cost),
        ];
    }

    private function costRowMatches(
        object $row,
        int $productId,
        ?int $variantId,
        ?int $batchId,
        ?int $warehouseId,
        int $asOfTimestamp
    ): bool {
        if ((int) $row->product_id !== $productId) {
            return false;
        }
        if ($variantId !== null && (int) $row->variant_id !== $variantId) {
            return false;
        }
        if ($batchId !== null && (int) $row->product_batch_id !== $batchId) {
            return false;
        }
        if ($warehouseId !== null && (int) $row->warehouse_id !== $warehouseId) {
            return false;
        }

        return (strtotime((string) $row->transaction_date) ?: 0) <= $asOfTimestamp;
    }

    private function comboUnitCostAsOf(
        $combo,
        int $variantId,
        string $asOf,
        int $warehouseId,
        array $context,
        Collection $products,
        Collection $variants
    ): array {
        $componentIds = $this->csvValues($combo->product_list ?? '');
        $variantIds = $this->csvValues($combo->variant_list ?? '');
        $quantities = $this->csvValues($combo->qty_list ?? '');
        $comboUnitIds = $this->csvValues($combo->combo_unit_id ?? '');

        if (empty($componentIds) || empty($quantities)) {
            return $this->zeroCostBasis('combo_components_missing', true);
        }

        $total = 0.0;
        $estimated = false;

        foreach ($componentIds as $index => $componentId) {
            $componentId = (int) $componentId;
            $component = $products->get($componentId) ?: DB::table('products')->where('id', $componentId)->first();

            if (! $component) {
                $estimated = true;
                continue;
            }

            $componentVariantId = (int) ($variantIds[$index] ?? 0);
            $componentQty = (float) ($quantities[$index] ?? 0);
            $componentQty = $this->convertQuantityByUnitId($componentQty, (int) ($comboUnitIds[$index] ?? 0));
            $componentCost = $this->resolveLegacyUnitCost(
                $component,
                $componentVariantId,
                0,
                $asOf,
                $warehouseId,
                $context,
                $products,
                $variants
            );

            $total += $componentQty * (float) $componentCost['unit_cost'];
            $estimated = $estimated || (bool) $componentCost['estimated'];
        }

        return [
            'unit_cost' => $total,
            'basis' => $estimated ? 'combo_component_cost_estimate' : 'combo_component_average_purchase_cost',
            'estimated' => $estimated,
        ];
    }

    private function indexRowsByIdentifier(Collection $rows): array
    {
        $index = [];

        foreach ($rows as $row) {
            foreach ($this->identifierValues($row->imei_number ?? null) as $identifier) {
                $index[$this->normalizeIdentifier($identifier)][] = $row;
            }
        }

        return $index;
    }

    private function identifierValues(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,]+/', $value) ?: [];
        $values = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strtolower($part) === 'null') {
                continue;
            }
            $values[$this->normalizeIdentifier($part)] = $part;
        }

        return array_values($values);
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return mb_strtoupper(trim($identifier));
    }

    private function costResult(float $unitCost, float $quantity, string $basis, bool $estimated): array
    {
        return [
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'basis' => $basis,
            'estimated' => $estimated,
        ];
    }

    private function averageCost(array $aggregate, string $basis, bool $estimated = true): array
    {
        $quantity = (float) ($aggregate['quantity'] ?? 0);
        $cost = (float) ($aggregate['cost'] ?? 0);

        if ($quantity <= 0) {
            return $this->zeroCostBasis($basis . '_missing_quantity', true);
        }

        return [
            'unit_cost' => $cost / $quantity,
            'basis' => $basis,
            'estimated' => $estimated,
        ];
    }

    private function products(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        $comboComponentIds = DB::table('products')
            ->whereIn('id', $productIds)
            ->where('type', 'combo')
            ->pluck('product_list')
            ->flatMap(fn ($list) => $this->csvValues($list))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        return DB::table('products')
            ->whereIn('id', array_values(array_unique(array_merge($productIds, $comboComponentIds))))
            ->get()
            ->keyBy('id');
    }

    private function variants(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return DB::table('product_variants')
            ->leftJoin('variants', 'variants.id', '=', 'product_variants.variant_id')
            ->whereIn('product_variants.product_id', $productIds)
            ->select(
                'product_variants.product_id',
                'product_variants.variant_id',
                'product_variants.item_code',
                'product_variants.additional_cost',
                'variants.name as variant_name'
            )
            ->get()
            ->keyBy(fn ($row) => $this->variantLookupKey((int) $row->product_id, (int) $row->variant_id));
    }

    private function blankProductRow($row, $product, $variant, bool $groupVariants): array
    {
        $variantName = $groupVariants ? null : ($variant->variant_name ?? null);
        $variantCode = $groupVariants ? null : ($variant->item_code ?? null);

        return [
            'product_id' => (int) $row->product_id,
            'variant_id' => $groupVariants ? null : (int) $row->variant_id,
            'category_id' => $product->category_id ?? null,
            'brand_id' => $product->brand_id ?? null,
            'product_name' => $product->name ?? __('db.shift_deleted_successfully'),
            'product_code' => $groupVariants ? ($product->code ?? '') : ($variantCode ?: ($product->code ?? '')),
            'variant_name' => $variantName,
            'variant_code' => $variantCode,
            'sold_quantity' => 0.0,
            'returned_quantity' => 0.0,
            'net_quantity' => 0.0,
            'gross_sales' => 0.0,
            'line_discounts' => 0.0,
            'invoice_discounts' => 0.0,
            'discounts' => 0.0,
            'returned_revenue' => 0.0,
            'sold_cost' => 0.0,
            'returned_cost' => 0.0,
            'net_cost' => 0.0,
            'net_sales' => 0.0,
            'gross_profit' => 0.0,
            'gross_margin' => null,
            'invoice_count' => 0,
            'estimated_cost' => false,
            'cost_basis_codes' => [],
        ];
    }

    private function blankProductRowFromContribution(array $row, bool $groupVariants): array
    {
        $variantName = $groupVariants ? null : ($row['variant_name'] ?? null);
        $variantCode = $groupVariants ? null : ($row['variant_code'] ?? null);

        return [
            'product_id' => (int) $row['product_id'],
            'variant_id' => $groupVariants ? null : (int) $row['variant_id'],
            'category_id' => $row['category_id'] ?? null,
            'brand_id' => $row['brand_id'] ?? null,
            'product_name' => $row['product_name'],
            'product_code' => $groupVariants ? ($row['product_code'] ?? '') : ($variantCode ?: ($row['product_code'] ?? '')),
            'variant_name' => $variantName,
            'variant_code' => $variantCode,
            'sold_quantity' => 0.0,
            'returned_quantity' => 0.0,
            'net_quantity' => 0.0,
            'gross_sales' => 0.0,
            'line_discounts' => 0.0,
            'invoice_discounts' => 0.0,
            'discounts' => 0.0,
            'returned_revenue' => 0.0,
            'sold_cost' => 0.0,
            'returned_cost' => 0.0,
            'net_cost' => 0.0,
            'net_sales' => 0.0,
            'gross_profit' => 0.0,
            'gross_margin' => null,
            'invoice_count' => 0,
            'invoice_ids' => [],
            'estimated_cost' => false,
            'cost_basis_codes' => [],
        ];
    }

    private function sortRows(Collection $rows, ProfitabilityFilter $filter): Collection
    {
        $sortColumn = self::DEFAULT_SORT_COLUMNS[$filter->sortBy] ?? self::DEFAULT_SORT_COLUMNS['gross_profit'];
        $descending = strtolower($filter->sortDirection) === 'desc';

        $sorted = $rows->sortBy(function (array $row) use ($sortColumn) {
            $value = $row[$sortColumn] ?? null;

            return is_string($value) ? mb_strtolower($value) : $value;
        }, SORT_REGULAR, $descending);

        return $sorted->values();
    }

    private function displayKey($row, bool $groupVariants): string
    {
        return $this->displayKeyFromParts((int) $row->product_id, (int) $row->variant_id, $groupVariants);
    }

    private function displayKeyFromParts(int $productId, int $variantId, bool $groupVariants): string
    {
        return $groupVariants ? "product:{$productId}" : "product:{$productId}:variant:{$variantId}";
    }

    private function costKey($row): string
    {
        return (int) $row->product_id . ':' . (int) $row->variant_id . ':' . (int) $row->product_batch_id;
    }

    private function variantLookupKey(int $productId, int $variantId): string
    {
        return $productId . ':' . $variantId;
    }

    private function convertedQuantitySql(string $quantityColumn, string $unitAlias): string
    {
        return "CASE
            WHEN {$unitAlias}.operator = '*' THEN COALESCE({$quantityColumn}, 0) * COALESCE({$unitAlias}.operation_value, 1)
            WHEN {$unitAlias}.operator = '/' THEN COALESCE({$quantityColumn}, 0) / NULLIF({$unitAlias}.operation_value, 0)
            ELSE COALESCE({$quantityColumn}, 0)
        END";
    }

    private function exchangeRateSql(string $alias): string
    {
        return "COALESCE(NULLIF({$alias}.exchange_rate, 0), 1)";
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, $this->moneyPrecision());
    }

    private function moneyPrecision(): int
    {
        return max(0, min(6, (int) config('decimal', 2)));
    }

    private function convertQuantityByUnitId(float $quantity, int $unitId): float
    {
        if (! $unitId) {
            return $quantity;
        }

        $unit = DB::table('units')->where('id', $unitId)->first();

        if (! $unit) {
            return $quantity;
        }

        if ($unit->operator === '*') {
            return $quantity * (float) $unit->operation_value;
        }

        if ($unit->operator === '/' && (float) $unit->operation_value != 0.0) {
            return $quantity / (float) $unit->operation_value;
        }

        return $quantity;
    }

    private function csvValues(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    private function zeroCostBasis(string $basis = 'zero_cost', bool $estimated = false): array
    {
        return [
            'unit_cost' => 0.0,
            'basis' => $basis,
            'estimated' => $estimated,
        ];
    }
}
