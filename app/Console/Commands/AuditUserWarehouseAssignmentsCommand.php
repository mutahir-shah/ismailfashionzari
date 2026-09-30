<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditUserWarehouseAssignmentsCommand extends Command
{
    protected $signature = 'warehouse:audit-user-assignments';
    protected $description = 'Read-only audit of invalid, legacy, and mismatched user warehouse assignments';

    public function handle(): int
    {
        $checks = [
            'Operational users without warehouse' => DB::table('users')->where('role_id', '>', 2)->where('role_id', '!=', 5)->whereNull('warehouse_id')->count(),
            'Users assigned to inactive warehouse' => DB::table('users')->join('warehouses', 'warehouses.id', '=', 'users.warehouse_id')->where('warehouses.is_active', false)->count(),
            'Users assigned to missing warehouse' => DB::table('users')->leftJoin('warehouses', 'warehouses.id', '=', 'users.warehouse_id')->whereNotNull('users.warehouse_id')->whereNull('warehouses.id')->count(),
            'Role-5 users without active linked customer' => DB::table('users')->leftJoin('customers', function ($join) { $join->on('customers.user_id', '=', 'users.id')->where('customers.is_active', true); })->where('users.role_id', 5)->whereNull('customers.id')->count(),
            'Employee/User warehouse mismatch' => DB::table('employees')->join('users', 'users.id', '=', 'employees.user_id')->whereRaw('NOT (employees.warehouse_id <=> users.warehouse_id)')->count(),
            'Users with missing/unknown role' => DB::table('users')->leftJoin('roles', 'roles.id', '=', 'users.role_id')->whereNull('roles.id')->count(),
        ];

        $this->table(['Check', 'Count'], collect($checks)->map(fn ($count, $check) => [$check, $count])->values()->all());
        $this->info('Read-only audit complete. No records were changed.');

        return self::SUCCESS;
    }
}
