<?php

namespace App\Http\Middleware;

use App\Services\WarehouseAccessService;
use Closure;
use Illuminate\Http\Request;

class EnforceWarehouseAccess
{
    public function handle(Request $request, Closure $next)
    {
        $access = app(WarehouseAccessService::class);
        $classification = $access->classification();

        if ($classification === WarehouseAccessService::PORTAL_IDENTITY) {
            $action = $request->route()?->getActionName();
            $allowed = [
                \App\Http\Controllers\HomeController::class.'@dashboard',
                \App\Http\Controllers\HomeController::class.'@index',
                \App\Http\Controllers\HomeController::class.'@switchTheme',
                \App\Http\Controllers\LanguageController::class.'@switchLanguage',
                \App\Http\Controllers\UserController::class.'@profile',
                \App\Http\Controllers\UserController::class.'@profileUpdate',
                \App\Http\Controllers\UserController::class.'@changePassword',
            ];
            abort_unless(in_array($action, $allowed, true), 403, 'Customer portal identities cannot access operational routes.');

            if (str_starts_with((string) $action, \App\Http\Controllers\UserController::class.'@')) {
                abort_unless(
                    (int) $request->route('id') === (int) $access->user()?->id,
                    403,
                    'Customer portal identities may access only their own profile.'
                );
            }

            return $next($request);
        }

        abort_if($classification === WarehouseAccessService::INVALID_OPERATIONAL, 403, 'A valid active warehouse assignment is required.');
        $warehouseId = $access->warehouseId();

        if ($warehouseId) {
            if ($request->route('warehouse_id') !== null) {
                $request->route()->setParameter('warehouse_id', $warehouseId);
            }

            $forced = [];
            if ($request->exists('warehouse_id')) {
                $forced['warehouse_id'] = $warehouseId;
            }
            if ($request->exists('from_warehouse_id')) {
                $forced['from_warehouse_id'] = $warehouseId;
            }
            if ($forced) {
                $request->merge($forced);
            }
        }

        return $next($request);
    }
}
