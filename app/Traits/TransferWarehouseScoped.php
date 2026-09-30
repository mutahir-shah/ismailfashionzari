<?php

namespace App\Traits;

use App\Services\WarehouseAccessService;
use Illuminate\Database\Eloquent\Builder;

trait TransferWarehouseScoped
{
    public static function bootTransferWarehouseScoped(): void
    {
        static::addGlobalScope('authorized_warehouse_transfer', function (Builder $query): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isPortalIdentity()) {
                $query->whereRaw('1 = 0');
                return;
            }
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                if (!$warehouseId) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                $model = $query->getModel();
                $query->where(function (Builder $query) use ($model, $warehouseId): void {
                    $query->where($model->qualifyColumn('from_warehouse_id'), $warehouseId)
                        ->orWhere($model->qualifyColumn('to_warehouse_id'), $warehouseId);
                });
            }
        });

        static::creating(function ($model): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                abort_unless($warehouseId, 403, 'A warehouse assignment is required.');
                $model->from_warehouse_id = $warehouseId;
            }
        });

        static::updating(function ($model): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                abort_unless($warehouseId, 403, 'A warehouse assignment is required.');
                abort_if((int) $model->getOriginal('from_warehouse_id') !== $warehouseId, 403, 'Only the source warehouse may modify a transfer.');
                $model->from_warehouse_id = $warehouseId;
            }
        });
    }
}
