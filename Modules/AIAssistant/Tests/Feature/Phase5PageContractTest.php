<?php

namespace Modules\AIAssistant\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;

class Phase5PageContractTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $permission = Permission::firstOrCreate(['name' => 'ai-assistant-index', 'guard_name' => 'web']);
        
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(['name' => 'Admin', 'guard_name' => 'web', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $adminRole = Role::where('name', 'Admin')->first();
        }
        
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->insertOrIgnore([
            ['permission_id' => $permission->id, 'role_id' => $adminRole->id],
        ]);

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin' . uniqid() . '@example.com',
            'phone' => '123456789' . rand(100, 999),
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'is_deleted' => false
        ]);
        \Illuminate\Support\Facades\DB::table('general_settings')
            ->where('id', 1)
            ->update(['staff_access' => 'warehouse']);
        Cache::forget('general_setting');
        Cache::forget('currency');
        
        $role_has_permissions_list = \Illuminate\Support\Facades\DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_id', $adminRole->id)
                ->get();
        Cache::put("role_has_permissions_list{$adminRole->id}", $role_has_permissions_list);
        \Illuminate\Support\Facades\View::share('role_has_permissions_list', $role_has_permissions_list);
        \Illuminate\Support\Facades\View::share('alert_product', 0);
        \Illuminate\Support\Facades\View::share('dso_alert_product_no', 0);
        \Illuminate\Support\Facades\View::share('expire_alert_products', 0);
        \Illuminate\Support\Facades\View::share('languages', collect());
        \Illuminate\Support\Facades\View::share('theme_font', 'Nunito');
        \Illuminate\Support\Facades\View::share('theme', 'light');
        \Illuminate\Support\Facades\View::share('theme_color', '#7c5cc4');
        \Illuminate\Support\Facades\View::share('currency', (object)['code' => 'USD']);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            $permissions = app(\App\Services\PermissionService::class)->getAuthenticatedUserPermissions();
            return in_array($ability, $permissions, true) ? true : null;
        });
    }

    public function test_page_contract_and_suggestions()
    {
        $response = $this->actingAs($this->adminUser)
                         ->withCookie('theme_font', 'Nunito')
                         ->withCookie('theme', 'light')
                         ->withCookie('theme_color', '#7c5cc4')
                         ->get(route('ai-assistant.index'));

        $response->assertStatus(200);
        
        // Assert Suggestions exist (10 suggestions)
        $response->assertSee('Today\'s sales', false);
        $response->assertSee('Today\'s purchases', false);
        $response->assertSee('Daily business snapshot', false);
        $response->assertSee('Today\'s expenses', false);
        $response->assertSee('Low stock');
        $response->assertSee('Top selling products');
        $response->assertSee('Slow moving products');
        $response->assertSee('Customer due summary');
        $response->assertSee('Supplier due summary');
        $response->assertSee('Cash bank summary');

        // Assert Accessibility Regions
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-label="Conversation History"', false);

        // Assert NO Icon fonts (no ti ti- or fa fa- inside the Vue/Widget or main JS renderer).
        // Since we are using SVG directly in the blade template...
        // We will assert no generic icon font references in the suggestion buttons
        $response->assertDontSee('<i class="ti ti');
    }
}
