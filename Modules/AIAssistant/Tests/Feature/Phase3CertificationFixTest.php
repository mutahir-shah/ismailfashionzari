<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\Returns;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;

class Phase3CertificationFixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-23 12:00:00');
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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

    private function mockOrchestrator(string $skillClass): void
    {
        $skill = app()->make($skillClass);
        $orchestrator = \Mockery::mock(\Modules\AIAssistant\Services\AssistantOrchestrator::class);
        $orchestrator->shouldReceive('executeStructured')
            ->andReturnUsing(function($message, $context) use ($skill) {
                return $skill->handle($message, $context);
            });
        $this->app->instance(\Modules\AIAssistant\Services\AssistantOrchestrator::class, $orchestrator);
    }

    public function test_endpoint_own_access_restricts_sales_summary()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $user2 = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $this->mockPermission($user1);
        $this->mockOrchestrator(\Modules\AIAssistant\Skills\SalesSummarySkill::class);

        $customer = $this->createCustomer();

        Sale::create([
            'reference_no' => 'SALE-OWN',
            'user_id' => $user1->id,
            'customer_id' => $customer->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'grand_total' => 100,
            'paid_amount' => 100,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 4,
            'sale_status' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Sale::create([
            'reference_no' => 'SALE-OTHER',
            'user_id' => $user2->id, // Another user
            'customer_id' => $customer->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 500,
            'grand_total' => 500,
            'paid_amount' => 500,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 4,
            'sale_status' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response->assertStatus(200);

        // Should only see the 100 sale, not the 500 sale
        $data = $response->json();
        if (!isset($data['cards'])) {
            dump($data);
        }
        $this->assertEquals(100, collect($data['cards'])->firstWhere('title', 'Gross Total')['value'], json_encode($data));
        $this->assertEquals(1, collect($data['cards'])->firstWhere('title', 'Transactions')['value']);
        $this->assertEquals($user1->id, $data['metadata']['own_user_id']);
    }

    public function test_endpoint_own_access_fails_closed_for_low_stock()
    {
        $this->seedGeneralSetting('own');
        $user1 = $this->createUser(['role_id' => 3]);
        $this->mockPermission($user1);
        $this->mockOrchestrator(\Modules\AIAssistant\Skills\LowStockSkill::class);

        $response = $this->actingAs($user1)->postJson(route('ai-assistant.prompt'), ['prompt' => 'low stock']);
        $response->assertStatus(200);

        $data = $response->json();
        // Fails closed returning 0 due to inability to scope stock to a single staff user
        $this->assertEquals(0, collect($data['cards'])->firstWhere('title', 'Low Stock Products')['value']);
    }

    public function test_customer_due_aggregates_equal_payments_and_excludes_draft_sale_payments()
    {
        $customer = $this->createCustomer();
        $user = $this->createUser(['role_id' => 1]); // Admin

        $validSale = Sale::create([
            'reference_no' => 'SALE-VALID',
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'grand_total' => 100,
            'paid_amount' => 20,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 2,
            'sale_status' => 1, // Valid
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Payment::create([
            'payment_reference' => 'PAY-1',
            'user_id' => $user->id,
            'sale_id' => $validSale->id,
            'account_id' => 1,
            'amount' => 10, // Equal value
            'change' => 0,
            'paying_method' => 'Cash',
        ]);
        Payment::create([
            'payment_reference' => 'PAY-2',
            'user_id' => $user->id,
            'sale_id' => $validSale->id,
            'account_id' => 1,
            'amount' => 10, // Equal value
            'change' => 0,
            'paying_method' => 'Cash',
        ]);

        $draftSale = Sale::create([
            'reference_no' => 'SALE-DRAFT',
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 200,
            'grand_total' => 200,
            'paid_amount' => 50,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 1,
            'sale_status' => 3, // Draft
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Payment::create([
            'payment_reference' => 'PAY-DRAFT',
            'user_id' => $user->id,
            'sale_id' => $draftSale->id,
            'account_id' => 1,
            'amount' => 50,
            'change' => 0,
            'paying_method' => 'Cash',
        ]);

        $this->seedGeneralSetting('all');
        $this->mockPermission($user);
        $this->mockOrchestrator(\Modules\AIAssistant\Skills\CustomerDueSkill::class);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'customer due summary']);
        $response->assertStatus(200);

        $data = $response->json();
        // Valid sale = 100
        // Payments on valid sale = 10 + 10 = 20
        // Draft sale = ignored
        // Payments on draft sale = ignored
        // Due = 100 - 20 = 80
        $this->assertEquals(80, collect($data['cards'])->firstWhere('title', 'Total Outstanding')['value']);
        $this->assertCount(1, $data['table']['rows']);
        $this->assertEquals(80, $data['table']['rows'][0][1]);
    }

    public function test_customer_due_more_than_ten_debtors_totals_all()
    {
        for ($i = 0; $i < 15; $i++) {
            $customer = $this->createCustomer();
            Sale::create([
                'reference_no' => 'SALE-'.$i,
                'user_id' => 1,
                'customer_id' => $customer->id,
                'warehouse_id' => 1,
                'biller_id' => 1,
                'item' => 1,
                'total_qty' => 1,
                'total_discount' => 0,
                'total_tax' => 0,
                'total_price' => 10,
                'grand_total' => 10,
                'paid_amount' => 0,
                'order_tax_rate' => 0,
                'order_tax' => 0,
                'order_discount' => 0,
                'payment_status' => 1,
                'sale_status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->seedGeneralSetting('all');
        $user = $this->createUser(['role_id' => 1]);
        $this->mockPermission($user);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'customer due summary']);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(150, collect($data['cards'])->firstWhere('title', 'Total Outstanding')['value']);
        $this->assertEquals(15, collect($data['cards'])->firstWhere('title', 'Customers with Due')['value']);
        $this->assertCount(10, $data['table']['rows']); // Limits to 10 rows
    }

    public function test_tenant_spoofing_is_ignored()
    {
        $this->seedGeneralSetting('all');
        $user = $this->createUser(['role_id' => 1]);
        $this->mockPermission($user);
        $this->mockOrchestrator(\Modules\AIAssistant\Skills\SalesSummarySkill::class);

        // Attempt to pass malicious system context
        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), [
            'prompt' => 'sales summary',
            'system_context' => [
                'tenant_id' => 'malicious_tenant'
            ]
        ]);
        $response->assertStatus(200);

        // Server derives tenant, so malicious_tenant is ignored and defaults to null (for non-SaaS)
        $this->assertNull($response->json('metadata.tenant_id'));
    }
}
