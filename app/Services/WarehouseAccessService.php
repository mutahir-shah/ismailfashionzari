<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class WarehouseAccessService
{
    public const GLOBAL_OPERATIONAL = 'global_operational';
    public const WAREHOUSE_OPERATIONAL = 'warehouse_operational';
    public const PORTAL_IDENTITY = 'portal_identity';
    public const INVALID_OPERATIONAL = 'invalid_operational';

    public function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public function isGlobal(?User $user = null): bool
    {
        $user ??= $this->user();

        return !$user || (int) $user->role_id <= 2;
    }

    public function portalCustomerId(?User $user = null): ?int
    {
        $user ??= $this->user();
        if (!$user || (int) $user->role_id !== 5) {
            return null;
        }

        return Customer::where('user_id', $user->id)->where('is_active', true)->value('id');
    }

    public function isPortalIdentity(?User $user = null): bool
    {
        return (bool) $this->portalCustomerId($user);
    }

    public function isWarehouseOperational(?User $user = null): bool
    {
        $user ??= $this->user();
        return $user && (int) $user->role_id > 2 && (int) $user->role_id !== 5;
    }

    public function hasValidWarehouseAssignment(?User $user = null): bool
    {
        $user ??= $this->user();
        return $this->isWarehouseOperational($user)
            && (int) $user->warehouse_id > 0
            && Warehouse::withoutGlobalScope('authorized_warehouse')
                ->whereKey($user->warehouse_id)->where('is_active', true)->exists();
    }

    public function classification(?User $user = null): string
    {
        $user ??= $this->user();
        if ($this->isGlobal($user)) return self::GLOBAL_OPERATIONAL;
        if ($this->isPortalIdentity($user)) return self::PORTAL_IDENTITY;
        if ($this->hasValidWarehouseAssignment($user)) return self::WAREHOUSE_OPERATIONAL;
        return self::INVALID_OPERATIONAL;
    }

    public function isRestricted(?User $user = null): bool
    {
        $user ??= $this->user();

        return $user && $this->isWarehouseOperational($user);
    }

    public function warehouseId(?User $user = null): ?int
    {
        $user ??= $this->user();

        return $this->hasValidWarehouseAssignment($user)
            ? (int) $user->warehouse_id
            : null;
    }

    public function scope(Builder|QueryBuilder $query, string $column = 'warehouse_id'): Builder|QueryBuilder
    {
        if ($this->isPortalIdentity()) {
            return $query->whereRaw('1 = 0');
        }
        if ($this->isRestricted()) {
            $warehouseId = $this->warehouseId();
            $warehouseId
                ? $query->where($column, $warehouseId)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function authorizeWarehouse(?int $warehouseId): void
    {
        if (!$this->isRestricted()) {
            return;
        }

        $allowed = $this->warehouseId();
        abort_if(!$allowed || (int) $warehouseId !== $allowed, 403, 'Warehouse access denied.');
    }

    public function assertValidUserAssignment(int $roleId, mixed $warehouseId, bool $portalWillBeLinked = false, ?User $existing = null): void
    {
        if ($roleId <= 2) return;

        if ($roleId === 5) {
            if ($portalWillBeLinked || ($existing && $this->isPortalIdentity($existing))) return;
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role_id' => 'A Customer role requires a valid linked customer record.',
            ]);
        }

        if (!(int) $warehouseId || !Warehouse::withoutGlobalScope('authorized_warehouse')->whereKey((int) $warehouseId)->where('is_active', true)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'warehouse_id' => 'An active warehouse is required for operational users.',
            ]);
        }
    }
}
