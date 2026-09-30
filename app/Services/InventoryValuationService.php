<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    /**
     * Current company-wide inventory valuation. Historical reconstruction is
     * intentionally unsupported because product_warehouse stores current state.
     */
    public function currentValuation(): array
    {
        $costs = DB::table('product_purchases as pp')
            ->join('purchases as p', 'p.id', '=', 'pp.purchase_id')
            ->whereNull('p.deleted_at')
            ->groupBy('pp.product_id', DB::raw('COALESCE(pp.variant_id, 0)'))
            ->selectRaw('pp.product_id, COALESCE(pp.variant_id, 0) variant_id,
                SUM((pp.net_unit_cost * pp.qty) / COALESCE(NULLIF(p.exchange_rate, 0), 1)) / NULLIF(SUM(pp.qty), 0) average_cost')
            ->get()
            ->keyBy(fn ($row) => $row->product_id . ':' . $row->variant_id);

        $rows = DB::table('product_warehouse as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->join('warehouses as w', 'w.id', '=', 'pw.warehouse_id')
            ->select('p.id as product_id', 'p.name', 'p.code', 'p.type', 'p.qty as product_qty',
                'p.cost as fallback_cost', 'pw.warehouse_id', 'w.name as warehouse', 'pw.qty',
                DB::raw('COALESCE(pw.variant_id, 0) variant_id'))
            ->get();

        $items = collect();
        $missingCost = collect();
        $negativeStock = collect();
        $warehouseTotals = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $key = $row->product_id . ':' . $row->variant_id;
            $average = isset($costs[$key]) ? (float) $costs[$key]->average_cost : null;
            $cost = $average ?? (float) $row->fallback_cost;
            $source = $average !== null ? 'weighted_purchase_average' : 'product_cost_fallback';
            $quantity = (float) $row->qty;
            $value = $quantity * $cost;

            if ($quantity != 0.0 && $cost <= 0.0) $missingCost->push($row);
            if ($quantity < 0.0) $negativeStock->push($row);

            $items->push((object) array_merge((array) $row, compact('cost', 'source', 'value')));
            $warehouseTotals[$row->warehouse_id] = ($warehouseTotals[$row->warehouse_id] ?? [
                'warehouse_id' => (int) $row->warehouse_id, 'warehouse' => $row->warehouse, 'value' => 0.0,
            ]);
            $warehouseTotals[$row->warehouse_id]['value'] += $value;
            $total += $value;
        }

        $warehouseQty = $rows->groupBy('product_id')->map(fn ($group) => (float) $group->sum('qty'));
        $quantityMismatches = DB::table('products')->get(['id', 'name', 'code', 'qty'])
            ->filter(fn ($product) => abs((float) $product->qty - (float) ($warehouseQty[$product->id] ?? 0)) > 0.0001)
            ->values();

        return [
            'value' => round($total, 4),
            'items' => $items,
            'warehouses' => collect(array_values($warehouseTotals)),
            'missing_cost' => $missingCost,
            'negative_stock' => $negativeStock,
            'quantity_mismatches' => $quantityMismatches,
            'method' => 'weighted_purchase_average_with_product_cost_fallback',
            'cutoff_limitation' => 'current_state_only',
        ];
    }
}
