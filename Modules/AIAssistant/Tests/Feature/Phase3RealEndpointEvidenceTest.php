<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class Phase3RealEndpointEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Carbon::setTestNow('2026-08-23 12:00:00');
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function createUser(array $override = []): User
    {
        return User::create(array_merge([
            'name'       => 'Test User',
            'email'      => 'user' . uniqid() . '@example.com',
            'phone'      => '123456789',
            'password'   => bcrypt('password'),
            'role_id'    => 1,
            'is_active'  => true,
            'is_deleted' => false,
        ], $override));
    }

    private function createCustomer(array $override = []): Customer
    {
        return Customer::create(array_merge([
            'customer_group_id' => 1,
            'name'              => 'Cust ' . uniqid(),
            'company_name'      => 'Co',
            'email'             => 'cust' . uniqid() . '@example.com',
            'phone_number'      => '123',
            'address'           => 'Addr',
            'city'              => 'City',
            'is_active'         => true,
            'opening_balance'   => 0,
        ], $override));
    }

    private function seedGeneralSetting(string $staffAccess = 'all'): void
    {
        \Illuminate\Support\Facades\DB::table('general_settings')->updateOrInsert(['id' => 1], [
            'site_title' => 'SalePro',
            'site_logo' => 'logo.png',
            'currency' => 1,
            'staff_access' => $staffAccess
        ]);
        \Illuminate\Support\Facades\Cache::forget('general_setting');
        \Illuminate\Support\Facades\Cache::forget('currency');
    }

    private function mockPermission(User $user): void
    {
        $roleId = $user->role_id;
        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(['id' => $roleId], ['name' => 'Role'.$roleId, 'guard_name' => 'web', 'is_active' => true]);
        $permissionId = \Illuminate\Support\Facades\DB::table('permissions')
            ->where('name', 'ai-assistant-index')
            ->where('guard_name', 'web')
            ->value('id');
        if (!$permissionId) {
            $permissionId = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId([
                'name' => 'ai-assistant-index',
                'guard_name' => 'web'
            ]);
        }
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->updateOrInsert([
            'permission_id' => $permissionId,
            'role_id' => $roleId
        ]);
        \Illuminate\Support\Facades\Cache::forget('role_has_permissions_list'.$roleId);
    }

    public function test_no_external_http_calls_are_made_by_skills()
    {
        $this->seedGeneralSetting('all');
        $user = $this->createUser();
        $this->mockPermission($user);
        
        // Prevent all outgoing HTTP requests. If any are made, this will throw an exception.
        Http::preventStrayRequests();

        $prompts = [
            'sales summary',
            'purchase summary',
            'top products',
            'low stock',
            'customer due summary'
        ];

        foreach ($prompts as $prompt) {
            $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => $prompt]);
            $response->assertStatus(200);
            $this->assertArrayHasKey('cards', $response->json());
        }
    }

    public function test_endpoint_own_access_restricts_sales_summary()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]); // Staff
        $user2 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $this->mockPermission($user1);
        $customer = $this->createCustomer();

        Sale::create(['reference_no' => 'SALE-1', 'user_id' => $user1->id, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Sale::create(['reference_no' => 'SALE-2', 'user_id' => $user2->id, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 500, 'grand_total' => 500, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(100, collect($data['cards'])->firstWhere('title', 'Gross Total')['value'], json_encode($data));
        $this->assertEquals($user1->id, $data['metadata']['own_user_id']);
    }

    public function test_endpoint_own_access_restricts_purchase_summary()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $user2 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $this->mockPermission($user1);

        Purchase::create(['reference_no' => 'PUR-1', 'user_id' => $user1->id, 'supplier_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_discount' => 0, 'total_tax' => 0, 'total_cost' => 100, 'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0, 'grand_total' => 100, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Purchase::create(['reference_no' => 'PUR-2', 'user_id' => $user2->id, 'supplier_id' => 1, 'warehouse_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_discount' => 0, 'total_tax' => 0, 'total_cost' => 500, 'order_tax_rate' => 0, 'order_tax' => 0, 'order_discount' => 0, 'shipping_cost' => 0, 'grand_total' => 500, 'paid_amount' => 0, 'status' => 1, 'payment_status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'purchase summary']);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(100, collect($data['cards'])->firstWhere('title', 'Gross Total')['value'], json_encode($data));
        $this->assertEquals($user1->id, $data['metadata']['own_user_id']);
    }

    public function test_endpoint_own_access_restricts_top_products()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3]);
        $user2 = $this->createUser(['role_id' => 3]);
        $this->mockPermission($user1);
        $customer = $this->createCustomer();

        $product = Product::create(['name' => 'Prod A', 'code' => 'PA', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 20, 'qty' => 100, 'alert_quantity' => 5, 'is_active' => true]);

        $sale1 = Sale::create(['reference_no' => 'SALE-1', 'user_id' => $user1->id, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\DB::table('product_sales')->insert(['sale_id' => $sale1->id, 'product_id' => $product->id, 'qty' => 5, 'sale_unit_id' => 1, 'net_unit_price' => 20, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 100]);

        $sale2 = Sale::create(['reference_no' => 'SALE-2', 'user_id' => $user2->id, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 500, 'grand_total' => 500, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\DB::table('product_sales')->insert(['sale_id' => $sale2->id, 'product_id' => $product->id, 'qty' => 10, 'sale_unit_id' => 1, 'net_unit_price' => 50, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 500]);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'top products']);
        $response->assertStatus(200);
        $data = $response->json();
        
        // User 1 only sold 5 qty, while user 2 sold 10. The endpoint should only return User 1's 5 qty.
        $this->assertEquals(5, $data['table']['rows'][0][1]);
        $this->assertEquals($user1->id, $data['metadata']['own_user_id']);
    }

    public function test_endpoint_own_access_fails_closed_for_low_stock()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3]);
        $this->mockPermission($user1);
        
        // Create product with low stock (should trigger if global)
        $product = Product::create(['name' => 'Prod B', 'code' => 'PB', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 20, 'qty' => 2, 'alert_quantity' => 5, 'is_active' => true]);
        \Illuminate\Support\Facades\DB::table('product_warehouse')->insert(['product_id' => $product->id, 'warehouse_id' => 1, 'qty' => 2]);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'low stock']);
        $response->assertStatus(200);
        $data = $response->json();
        
        // Should return 0 since staff cannot see global stock
        $this->assertEquals(0, collect($data['cards'])->firstWhere('title', 'Low Stock Products')['value']);
        $this->assertEquals($user1->id, $data['metadata']['own_user_id']);
    }

    public function test_endpoint_own_access_fails_closed_for_customer_due_and_proves_user_isolation()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3]);
        $user2 = $this->createUser(['role_id' => 3]);
        $this->mockPermission($user1);
        $this->mockPermission($user2);
        
        $customer = Customer::create([
            'customer_group_id' => 1, 'name' => 'Cust Due', 'company_name' => 'Co', 'email' => 'due@example.com', 'phone_number' => '123', 'address' => 'Addr', 'city' => 'City', 'is_active' => true,
            'opening_balance' => 1000 // Huge opening balance
        ]);
        
        Sale::create(['reference_no' => 'S-1', 'user_id' => $user2->id, 'customer_id' => $customer->id, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 100, 'grand_total' => 100, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // User 1 requests due (User 2 created the debt). Both should get 0 because own access fails closed.
        $response1 = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'customer due summary']);
        $response1->assertStatus(200);
        $this->assertEquals(0, collect($response1->json('cards'))->firstWhere('title', 'Total Outstanding')['value']);

        $response2 = $this->actingAs($user2)->postJson(route('ai-assistant.prompt'), ['prompt' => 'customer due summary']);
        $response2->assertStatus(200);
        $this->assertEquals(0, collect($response2->json('cards'))->firstWhere('title', 'Total Outstanding')['value']);
        
        // This implicitly proves User A cannot see debt created by User B, as both see 0.
    }

    public function test_endpoint_context_spoofing_is_ignored()
    {
        $this->seedGeneralSetting('all');
        $user = $this->createUser(['role_id' => 1]);
        $this->mockPermission($user);
        
        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), [
            'prompt' => 'sales summary',
            'system_context' => [
                'tenant_id' => 'malicious_tenant',
                'user_id' => 999
            ]
        ]);
        $response->assertStatus(200);
        
        $this->assertNull($response->json('metadata.tenant_id')); // Spoof ignored
        $this->assertNotEquals(999, collect($response->json('metadata'))->get('own_user_id'));
    }

    public function test_endpoint_runs_exactly_one_skill_and_not_for_fallback()
    {
        $this->seedGeneralSetting('all');
        $user = $this->createUser();
        $this->mockPermission($user);

        // Run successful skill
        $response1 = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response1->assertStatus(200);
        
        // Check run records (assuming there's a table 'ai_skill_runs')
        $this->assertDatabaseCount('ai_skill_runs', 1);
        $this->assertDatabaseHas('ai_skill_runs', [
            'user_id' => $user->id,
            'skill_key' => 'sales_summary'
        ]);

        // Run fallback skill
        $response2 = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'do my taxes']);
        $response2->assertStatus(200);
        
        // Count should still be 1 because fallback doesn't record a run (or records it differently, but the requirement is "zero for fallback" records)
        $this->assertDatabaseCount('ai_skill_runs', 1);
    }
}
