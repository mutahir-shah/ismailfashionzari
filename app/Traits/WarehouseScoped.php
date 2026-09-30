<?php

namespace App\Traits;

use App\Services\WarehouseAccessService;
use Illuminate\Database\Eloquent\Builder;

trait WarehouseScoped
{
    public static function bootWarehouseScoped(): void
    {
        static::addGlobalScope('authorized_warehouse', function (Builder $query): void {
            $access = app(WarehouseAccessService::class);
            if ($customerId = $access->portalCustomerId()) {
                $portalModels = [\App\Models\Sale::class, \App\Models\Returns::class, \App\Models\Quotation::class];
                in_array(get_class($query->getModel()), $portalModels, true)
                    ? $query->where($query->getModel()->qualifyColumn('customer_id'), $customerId)
                    : $query->whereRaw('1 = 0');
                return;
            }
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                $warehouseId
                    ? $query->where($query->getModel()->qualifyColumn('warehouse_id'), $warehouseId)
                    : $query->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                abort_unless($warehouseId, 403, 'A warehouse assignment is required.');
                $model->warehouse_id = $warehouseId;
            }
        });

        static::updating(function ($model): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                abort_unless($warehouseId, 403, 'A warehouse assignment is required.');
                abort_if((int) $model->getOriginal('warehouse_id') !== $warehouseId, 403, 'Warehouse access denied.');
                $model->warehouse_id = $warehouseId;
            }
        });
    }
}
