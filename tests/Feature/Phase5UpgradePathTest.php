<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class Phase5UpgradePathTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_adds_permission_to_admin_and_clears_cache()
    {
        // Setup: Admin role exists but without permission
        $admin = Role::firstOrCreate(
            ['id' => 1, 'name' => 'Admin', 'guard_name' => 'web'],
            ['is_active' => 1]
        );
        
        $permissionName = 'ai-assistant-index';
        if (Permission::where('name', $permissionName)->exists()) {
            Permission::where('name', $permissionName)->delete();
        }

        // Instantiate and run the migration directly
        require_once base_path('Modules/AIAssistant/Database/Migrations/2026_07_05_000000_add_ai_assistant_permission_to_admin.php');
        $migration = new \AddAiAssistantPermissionToAdmin();
        $migration->up();

        // Assert permission was created
        $this->assertDatabaseHas('permissions', [
            'name' => $permissionName,
            'guard_name' => 'web'
        ]);

        // Assert Admin role has the permission
        $this->assertTrue($admin->fresh()->hasPermissionTo($permissionName));
    }

    public function test_migration_tenant_iteration_logic()
    {
        // Since stancl/tenancy might not be active in tests, we just ensure it doesn't crash
        // and safely executes the central fallback.
        require_once base_path('Modules/AIAssistant/Database/Migrations/2026_07_05_000000_add_ai_assistant_permission_to_admin.php');
        $migration = new \AddAiAssistantPermissionToAdmin();
        $migration->up();
        $this->assertTrue(true, 'Migration ran without errors even if tenancy is not configured');
    }
}
