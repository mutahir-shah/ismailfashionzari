<?php

namespace Modules\Restaurant\Services;

use App\Models\Sale;
use App\Models\Table;
use Modules\Restaurant\Entities\Floors;
use Illuminate\Support\Facades\DB;

class TableOccupancyService
{
    /**
     * Active sale statuses that occupy a Restaurant Dine-In table:
     * 3 = Draft (saved table draft)
     * 5 = Processing / Cooking (sent to kitchen)
     * 6 = Ready for Service / Cooked (awaiting server delivery)
     */
    public const ACTIVE_OCCUPANCY_STATUSES = [3, 5, 6];

    /**
     * Get query builder for active sales currently occupying tables.
     */
    public static function getActiveOccupiedSalesQuery(?int $warehouseId = null, ?int $excludeSaleId = null)
    {
        return Sale::whereNull('sales.deleted_at')
            ->whereNotNull('sales.table_id')
            ->whereIn('sales.sale_status', self::ACTIVE_OCCUPANCY_STATUSES)
            ->when($warehouseId, function ($query, $warehouseId) {
                return $query->where('sales.warehouse_id', $warehouseId);
            })
            ->when($excludeSaleId, function ($query, $excludeSaleId) {
                return $query->where('sales.id', '!=', $excludeSaleId);
            });
    }

    /**
     * Get a map of table statuses (available, occupied, readiness, active sale details).
     */
    public static function getTableStatusMap(?int $warehouseId = null): array
    {
        $tablesQuery = Table::where('tables.is_active', 1);

        if ($warehouseId) {
            $tablesQuery->whereIn('tables.floor_id', function ($query) use ($warehouseId) {
                $query->select('id')->from('floors')->where('warehouse_id', $warehouseId);
            });
        }

        $tables = $tablesQuery->get();

        $activeSales = self::getActiveOccupiedSalesQuery($warehouseId)
            ->leftJoin('users as waiters', 'sales.waiter_id', '=', 'waiters.id')
            ->select(
                'sales.id',
                'sales.table_id',
                'sales.grand_total',
                'sales.item',
                'sales.sale_status',
                'sales.waiter_id',
                'waiters.name as waiter_name',
                'sales.created_at'
            )
            ->orderBy('sales.id', 'desc')
            ->get();

        $salesByTable = $activeSales->groupBy('table_id');
        $tableStatus = [];

        foreach ($tables as $table) {
            $salesForTable = $salesByTable->get($table->id);
            $count = $salesForTable ? $salesForTable->count() : 0;
            $primarySale = $count > 0 ? $salesForTable->first() : null;

            $status = 'available';
            $readiness = null;

            if ($primarySale) {
                $status = 'occupied';
                if ((int)$primarySale->sale_status === 6) {
                    $readiness = 'ready'; // Ready for Service
                } elseif ((int)$primarySale->sale_status === 5) {
                    $readiness = 'preparing'; // Processing / Cooking
                } else {
                    $readiness = 'draft'; // Draft
                }
            }

            $tableStatus[$table->id] = [
                'id'               => $table->id,
                'name'             => $table->name,
                'status'           => $status,
                'readiness'        => $readiness,
                'open_order_count' => $count,
                'sale_id'          => $primarySale ? $primarySale->id : null,
                'item_count'       => $primarySale ? $primarySale->item : null,
                'total'            => $primarySale ? $primarySale->grand_total : null,
                'waiter_id'        => $primarySale ? $primarySale->waiter_id : null,
                'waiter_name'      => $primarySale ? $primarySale->waiter_name : null,
                'opened_at'        => $primarySale ? $primarySale->created_at->format('Y-m-d H:i:s') : null,
            ];
        }

        return $tableStatus;
    }

    /**
     * Validate that a table is available for a new or updated Dine-In order.
     * Must be called within a database transaction with table row locked.
     *
     * @return string|null Error message if invalid/unavailable, or null if available.
     */
    public static function validateTableAvailability(int $tableId, int $warehouseId, ?int $excludeSaleId = null): ?string
    {
        $table = Table::lockForUpdate()->find($tableId);

        if (!$table || !$table->is_active) {
            return __('db.Selected table is invalid or inactive.');
        }

        // Validate table belongs to the selected warehouse via floors
        $floor = Floors::find($table->floor_id);
        if (!$floor || (int)$floor->warehouse_id !== (int)$warehouseId) {
            return __('db.Selected table does not belong to the selected warehouse.');
        }

        // Check for conflicting active Dine-In sale
        $conflictingSale = self::getActiveOccupiedSalesQuery($warehouseId, $excludeSaleId)
            ->where('sales.table_id', $tableId)
            ->first(['sales.id', 'sales.reference_no']);

        if ($conflictingSale) {
            return __('db.Table :name currently has an active order (:ref).', [
                'name' => $table->name,
                'ref'  => $conflictingSale->reference_no ?: ('#' . $conflictingSale->id),
            ]);
        }

        return null; // Available and valid
    }
}
