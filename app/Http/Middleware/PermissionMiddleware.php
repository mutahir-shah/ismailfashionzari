<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!$user) {
           return redirect('/')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $role_has_permissions_list = Cache::remember(
            'role_has_permissions_list' . $user->role_id,
            60 * 60 * 24 * 365,
            function () use ($user) {
                return DB::table('permissions')
                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_id', $user->role_id)
                    ->select('permissions.name')
                    ->get();
            }
        );

        $permissions = $role_has_permissions_list->pluck('name')->toArray();

        if (!in_array($permission, $permissions)) {
            return redirect('/')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        return $next($request);
    }
}
