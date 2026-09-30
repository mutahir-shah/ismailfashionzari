<?php

namespace Modules\AIAssistant\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AIAssistantRouteTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('general_settings')->updateOrInsert(['id' => 1], [
            'site_title' => 'SalePro Test',
            'currency' => 1,
            'currency_position' => 'prefix',
            'staff_access' => 'own',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'updated_at' => now(),
        ]);
        Cache::flush();
    }

    /**
     * Test guest cannot access the assistant route.
     */
    public function test_guest_cannot_access_assistant_index()
    {
        $response = $this->get(route('ai-assistant.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test authenticated user without permission is denied.
     */
    public function test_authenticated_user_without_permission_cannot_access()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test_'.rand().'@example.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
            'role_id' => 3,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        DB::table('roles')->updateOrInsert(['id' => 3], [
            'name' => 'Restricted AI test role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('ai-assistant.index'));

        // Verify the exact redirect and session behavior imposed by App\Http\Middleware\PermissionMiddleware
        $response->assertRedirect('/');
        $response->assertSessionHas('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    /**
     * Test the ai-assistant.index route has correct URI, methods, and middleware.
     */
    public function test_route_has_correct_middleware()
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('ai-assistant.index');
        
        $this->assertNotNull($route, 'Route ai-assistant.index does not exist.');
        $this->assertEquals(['GET', 'HEAD'], $route->methods());
        $this->assertEquals('ai/ai-assistant', $route->uri());
        
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('permission:ai-assistant-index', $middleware);
    }

    /**
     * Test authenticated user with permission can access the assistant route.
     */
    public function test_authenticated_user_with_permission_can_access()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test_'.rand().'@example.com',
            'password' => bcrypt('password'),
            'phone' => '123456789',
            'role_id' => 1,
            'is_active' => true,
            'is_deleted' => false,
        ]);
        
        // Prepend our mock views directory so backend.layout.main resolves to our empty dummy layout
        // This avoids missing variable exceptions (e.g. $alert_product, $theme) in the real layout during testing
        \Illuminate\Support\Facades\View::prependLocation(__DIR__ . '/mock_views');

        $response = $this->actingAs($user)->get(route('ai-assistant.index'));

        // View rendering fails in tests due to missing DB-populated global variables in the main layout.
        // Asserts verify route and translation string inside the module view.
        $response->assertStatus(200);
        $response->assertSee('AI Assistant');
    }

    /**
     * Test the production sidebar contains the required AI Assistant UI configuration.
     * Rendering the full layout is unsafe in tests due to unrelated modules, so we assert
     * against the raw Blade file structure to verify the permission gate, route, and translation.
     */
    public function test_sidebar_contains_ai_assistant_configuration()
    {
        $sidebarContent = file_get_contents(resource_path('views/backend/layout/sidebar.blade.php'));
        
        // Fails if it loses the permission gate or uses the wrong permission
        $this->assertStringContainsString("@can('ai-assistant-index')", $sidebarContent, 'Sidebar is missing the correct permission gate.');
        
        // Fails if it loses the route link
        $this->assertStringContainsString("route('ai-assistant.index')", $sidebarContent, 'Sidebar is missing the route link.');
        
        // Fails if it stops using the exact translation key
        $this->assertStringContainsString("__('aiassistant::app.ai_assistant')", $sidebarContent, 'Sidebar is missing the translation key.');
    }
}
