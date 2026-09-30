<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissionName = 'tax-report';
        $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);

        // Assign to Admin (1) and Owner (2)
        $adminRoles = Role::whereIn('id', [1, 2])->get();
        foreach ($adminRoles as $role) {
            if (!$role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permission);
            }
        }

        app('cache')->forget('spatie.permission.cache');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'tax-report')->first();
        if ($permission) {
            $permission->delete();
        }
        app('cache')->forget('spatie.permission.cache');
    }
};
