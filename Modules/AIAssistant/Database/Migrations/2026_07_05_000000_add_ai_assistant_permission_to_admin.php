<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class AddAiAssistantPermissionToAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->seedPermission();

        // Support for stancl/tenancy if active
        if (function_exists('tenancy') && Schema::hasTable('tenants')) {
            $tenants = DB::table('tenants')->get();
            foreach ($tenants as $tenant) {
                try {
                    tenancy()->initialize($tenant);
                    $this->seedPermission();
                } finally {
                    tenancy()->end();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Safe rollback: do not remove permission to avoid accidental privilege destruction
        // if this was manually assigned.
    }

    protected function seedPermission()
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ai-assistant-index',
            'guard_name' => 'web'
        ]);

        // Attempt to find canonical Admin role (ID = 1), fallback to name 'Admin'
        $adminRole = Role::where('id', 1)->first() ?? Role::where('name', 'Admin')->first();
        
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        $this->clearCaches();
    }

    protected function clearCaches()
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenantPrefix = function_exists('tenant') && tenant('id') ? tenant('id') . ':' : '';
        $roles = Role::all();
        
        foreach ($roles as $role) {
            Cache::forget("{$tenantPrefix}role_has_permissions_{$role->id}");
            Cache::forget("{$tenantPrefix}role_has_permissions_list:{$role->id}");
        }
    }
}
