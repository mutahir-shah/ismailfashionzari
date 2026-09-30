<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class PermissionService
{
    /**
     * Resolve permissions for a specific User instance based on users.role_id.
     */
    public function permissionsForUser(User $user): array
    {
        $roleId = $user->role_id ?? null;
        if (!$roleId) {
            return [];
        }

        $tenantPrefix = function_exists('tenant') && tenant('id') ? tenant('id') . ':' : '';
        $cacheKey = "{$tenantPrefix}salepro.permissions.role.{$roleId}.web";

        if (app()->environment('testing') || app()->runningUnitTests()) {
            Cache::forget($cacheKey);
        }

        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions') || !Schema::hasTable('roles')) {
            return [];
        }

        $permissions = Cache::remember($cacheKey, 60 * 60 * 24 * 365, function () use ($roleId) {
            return DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->where('role_has_permissions.role_id', $roleId)
                ->where(function ($query) {
                    $query->where('permissions.guard_name', 'web')
                          ->orWhereNull('permissions.guard_name');
                })
                ->where(function ($query) {
                    $query->where('roles.guard_name', 'web')
                          ->orWhereNull('roles.guard_name');
                })
                ->pluck('permissions.name')
                ->toArray();
        });

        if ($permissions instanceof Collection) {
            $permissions = $permissions->toArray();
        }

        $perms = array_filter(array_map(function ($item) {
            return is_object($item) ? ($item->name ?? '') : (string) $item;
        }, (array) $permissions));

        if (empty($perms) && (app()->environment('testing') || app()->runningUnitTests())) {
            Cache::forget($cacheKey);
            $permissions = DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->where('role_has_permissions.role_id', $roleId)
                ->where(function ($query) {
                    $query->where('permissions.guard_name', 'web')
                          ->orWhereNull('permissions.guard_name');
                })
                ->where(function ($query) {
                    $query->where('roles.guard_name', 'web')
                          ->orWhereNull('roles.guard_name');
                })
                ->pluck('permissions.name')
                ->toArray();

            if ($permissions instanceof Collection) {
                $permissions = $permissions->toArray();
            }

            $perms = array_filter(array_map(function ($item) {
                return is_object($item) ? ($item->name ?? '') : (string) $item;
            }, (array) $permissions));
        }

        if (!empty($perms)) {
            return array_values($perms);
        }

        return $this->fallbackPermissions();
    }

    /**
     * Check if a permission exists in the database for the given guard.
     * Uses a cached set of all permissions for fast, zero-query Gate checks.
     */
    public function permissionExists(string $ability, string $guard = 'web'): bool
    {
        $tenantPrefix = function_exists('tenant') && tenant('id') ? tenant('id') . ':' : '';
        $cacheKey = "{$tenantPrefix}salepro.permissions.all.{$guard}";

        if (app()->environment('testing') || app()->runningUnitTests()) {
            Cache::forget($cacheKey);
        }

        if (!Schema::hasTable('permissions')) {
            return false;
        }

        $allPermissions = Cache::remember($cacheKey, 60 * 60 * 24 * 365, function () use ($guard) {
            return DB::table('permissions')
                ->where(function ($query) use ($guard) {
                    $query->where('guard_name', $guard)
                          ->orWhereNull('guard_name');
                })
                ->pluck('name')
                ->toArray();
        });

        if (!in_array($ability, (array) $allPermissions, true) && (app()->environment('testing') || app()->runningUnitTests())) {
            Cache::forget($cacheKey);
            $allPermissions = DB::table('permissions')
                ->where(function ($query) use ($guard) {
                    $query->where('guard_name', $guard)
                          ->orWhereNull('guard_name');
                })
                ->pluck('name')
                ->toArray();
        }

        return in_array($ability, (array) $allPermissions, true);
    }

    /**
     * Check if a specific user has a permission.
     */
    public function userHasPermission(User $user, string $ability): bool
    {
        return in_array($ability, $this->permissionsForUser($user), true);
    }

    /**
     * Check persisted role assignment only, without view-data fallbacks.
     * Use this for endpoint authorization where a cached UI fallback must never grant access.
     */
    public function userHasExplicitPermission(User $user, string $ability): bool
    {
        return DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $user->role_id)
            ->where('permissions.name', $ability)
            ->where(function ($query) {
                $query->where('permissions.guard_name', 'web')
                    ->orWhereNull('permissions.guard_name');
            })
            ->exists();
    }

    /**
     * Convenience method for current authenticated user.
     */
    public function getAuthenticatedUserPermissions(): array
    {
        $user = Auth::user();

        return $user instanceof User
            ? $this->permissionsForUser($user)
            : [];
    }

    /**
     * Clear all cached permission keys for a given role (and all permissions cache).
     */
    public function clearRolePermissionsCache($roleId = null): void
    {
        $tenantPrefix = function_exists('tenant') && tenant('id') ? tenant('id') . ':' : '';

        Cache::forget("{$tenantPrefix}salepro.permissions.all.web");

        if ($roleId) {
            Cache::forget("{$tenantPrefix}salepro.permissions.role.{$roleId}.web");
            Cache::forget("{$tenantPrefix}role_has_permissions_list{$roleId}");
            Cache::forget("{$tenantPrefix}role_has_permissions_{$roleId}");
            Cache::forget("role_has_permissions_list{$roleId}");
            Cache::forget("role_has_permissions_{$roleId}");
        }

        Cache::forget('role_has_permissions');
        Cache::forget('role_has_permissions_list');
    }

    private function fallbackPermissions(): array
    {
        $shared = View::shared('all_permission');
        if (is_array($shared)) {
            return array_values(array_filter($shared));
        }

        $sharedList = View::shared('role_has_permissions_list');
        if ($sharedList) {
            return collect($sharedList)->pluck('name')->filter()->map(fn ($n) => (string) $n)->values()->toArray();
        }

        return [];
    }
}
