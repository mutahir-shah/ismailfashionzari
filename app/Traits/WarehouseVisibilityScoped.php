<?php

namespace App\Traits;

use App\Services\WarehouseAccessService;
use Illuminate\Database\Eloquent\Builder;

trait WarehouseVisibilityScoped
{
    public static function bootWarehouseVisibilityScoped(): void
    {
        static::addGlobalScope('authorized_warehouse', function (Builder $query): void {
            $access = app(WarehouseAccessService::class);
            if ($access->isPortalIdentity()) {
                $query->whereRaw('1 = 0');
                return;
            }
            if ($access->isRestricted()) {
                $warehouseId = $access->warehouseId();
                $warehouseId
                    ? $query->where($query->getModel()->qualifyColumn('id'), $warehouseId)
                    : $query->whereRaw('1 = 0');
            }
        });
    }
}
