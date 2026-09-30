<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\AIAssistant\Services\SkillRegistry;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\Skills\SalesSummarySkill;
use Modules\AIAssistant\Skills\PurchaseSummarySkill;
use Modules\AIAssistant\Skills\LowStockSkill;
use Modules\AIAssistant\Skills\TopProductsSkill;
use Modules\AIAssistant\Skills\CustomerDueSkill;
use Modules\AIAssistant\Skills\SlowMovingProductsSkill;
use Modules\AIAssistant\Skills\SupplierDueSkill;
use Modules\AIAssistant\Skills\CashBankSummarySkill;
use Modules\AIAssistant\Skills\ExpenseSummarySkill;
use Modules\AIAssistant\Skills\DailySnapshotSkill;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class Phase4EndpointCollisionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
        Http::preventStrayRequests(); // Prevent external network calls
    }

    private function seedGeneralSetting(string $staffAccess = 'all'): void
    {
        \Illuminate\Support\Facades\DB::table('general_settings')
            ->where('id', 1)
            ->update(['staff_access' => $staffAccess]);
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
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createUser(int $roleId, ?int $warehouseId = null): User
    {
        $user = User::create([
            'name' => 'Test', 
            'email' => 'test' . uniqid() . '@example.com', 
            'password' => bcrypt('password'), 
            'role_id' => $roleId, 
            'warehouse_id' => $warehouseId,
            'is_active' => true, 
            'is_deleted' => false
        ]);
        $this->mockPermission($user);
        return $user;
    }

    public function test_registry_contains_all_ten_structured_skills_exactly_once()
    {
        $registry = app(SkillRegistry::class);
        $skills = $registry->all();
        
        $this->assertCount(10, $skills);
        
        $keys = array_map(fn($skill) => $skill->key(), $skills);
        $this->assertEquals(count($keys), count(array_unique($keys)));
    }

    public function test_prompts_resolve_to_correct_skill()
    {
        $registry = app(SkillRegistry::class);
        
        $cases = [
            'sales summary' => SalesSummarySkill::class,
            'purchase summary' => PurchaseSummarySkill::class,
            'low stock' => LowStockSkill::class,
            'top products' => TopProductsSkill::class,
            'customer due summary' => CustomerDueSkill::class,
            'slow moving products' => SlowMovingProductsSkill::class,
            'supplier due summary' => SupplierDueSkill::class,
            'cash bank summary' => CashBankSummarySkill::class,
            'expense summary' => ExpenseSummarySkill::class,
            'daily snapshot' => DailySnapshotSkill::class,
        ];
        
        foreach ($cases as $prompt => $expectedClass) {
            $message = new AssistantMessageData('user', $prompt);
            $resolved = $registry->resolve($message);
            $this->assertInstanceOf($expectedClass, $resolved, "Prompt '$prompt' did not resolve to $expectedClass");
        }
    }

    public function test_fallback_records_zero_runs()
    {
        $user = $this->createUser(1); // admin
        $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
        
        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), [
            'prompt' => 'do something random'
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals("I'm not sure how to help with that request.", $response->json('text_summary'));
        $this->assertEquals($initialCount, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
    }

    public function test_daily_snapshot_records_no_nested_runs()
    {
        $user = $this->createUser(1);
        $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
        
        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), [
            'prompt' => 'daily snapshot'
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals('daily_snapshot', $response->json('metadata.skill'));
        
        // Assert exactly ONE skill run recorded (daily_snapshot), not nested calls for sales/purchase/expense
        $this->assertEquals($initialCount + 1, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
        $latest = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->orderByDesc('id')->first();
        $this->assertEquals('daily_snapshot', $latest->skill_key);
    }

    public function test_endpoint_matrix_and_spoofing()
    {
        // 1. Admin/unrestricted (role 1)
        // 2. Assigned warehouse restriction (role 3, warehouse 1)
        // 3. Unassigned warehouse restriction (role 3, warehouse null) -> explicitly empty
        // 4. Own restriction (role 3, staff_access = own)
        
        $admin = $this->createUser(1);
        $assignedWarehouse = $this->createUser(3, 1);
        $unassignedWarehouse = $this->createUser(3, null);
        $ownUser = $this->createUser(3);
        
        $prompts = [
            'cash bank summary' => 'cash_bank_summary',
            'supplier due summary' => 'supplier_due',
            'slow moving products' => 'slow_moving_products',
            'expense summary' => 'expense_summary',
            'daily snapshot' => 'daily_snapshot',
        ];

        // 1. Admin Test
        $this->seedGeneralSetting('all');
        foreach ($prompts as $prompt => $skillKey) {
            $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
            $response = $this->actingAs($admin)->postJson(route('ai-assistant.prompt'), [
                'prompt' => $prompt,
                'context' => ['warehouse_id' => 999, 'user_id' => 999, 'business_context' => ['warehouse_ids' => [999]], 'system_context' => ['own_user_id' => 999]] // Spoof attempt
            ]);
            $response->assertStatus(200);
            $this->assertEquals($initialCount + 1, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
            
            $metadata = $response->json('metadata');
            $this->assertTrue(empty($metadata['warehouse_ids']), "Failed for {$skillKey} warehouse_ids");
            $this->assertTrue(empty($metadata['own_user_id']), "Failed for {$skillKey} own_user_id");
            $this->assertFalse($metadata['failed_closed'] ?? false);
        }

        // 2. Assigned warehouse Test
        $this->seedGeneralSetting('warehouse');
        foreach ($prompts as $prompt => $skillKey) {
            $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
            $response = $this->actingAs($assignedWarehouse)->postJson(route('ai-assistant.prompt'), [
                'prompt' => $prompt,
                'context' => ['warehouse_id' => 999, 'user_id' => 999, 'business_context' => ['warehouse_ids' => [999]]] // Spoof attempt
            ]);
            $response->assertStatus(200);
            $this->assertEquals($initialCount + 1, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
            
            $metadata = $response->json('metadata');
            if ($skillKey === 'cash_bank_summary') {
                $this->assertTrue($metadata['failed_closed']);
                $this->assertEquals('global_data_restriction', $metadata['reason']);
            } else {
                $this->assertEquals([1], $metadata['warehouse_ids']);
                $this->assertNull($metadata['own_user_id'] ?? null);
                if ($skillKey === 'supplier_due') {
                    $this->assertEquals('warehouse_activity_only', $metadata['reason']);
                }
            }
        }

        // 3. Unassigned warehouse (empty scope) Test
        $this->seedGeneralSetting('warehouse');
        foreach ($prompts as $prompt => $skillKey) {
            $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
            $response = $this->actingAs($unassignedWarehouse)->postJson(route('ai-assistant.prompt'), [
                'prompt' => $prompt,
                'context' => ['warehouse_id' => 999, 'business_context' => ['warehouse_ids' => [999]]]
            ]);
            $response->assertStatus(200);
            $this->assertEquals($initialCount + 1, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
            
            $metadata = $response->json('metadata');
            $this->assertTrue($metadata['failed_closed']);
            $this->assertEquals('empty_warehouse_scope', $metadata['reason']);
            $this->assertTrue(empty($metadata['warehouse_ids']), "Failed asserting warehouse_ids is empty for {$skillKey}");
        }

        // 4. Own restriction Test
        $this->seedGeneralSetting('own');
        foreach ($prompts as $prompt => $skillKey) {
            $initialCount = \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count();
            $response = $this->actingAs($ownUser)->postJson(route('ai-assistant.prompt'), [
                'prompt' => $prompt,
                'context' => ['user_id' => 999, 'system_context' => ['own_user_id' => null]]
            ]);
            $response->assertStatus(200);
            $this->assertEquals($initialCount + 1, \Illuminate\Support\Facades\DB::table('ai_skill_runs')->count());
            
            $metadata = $response->json('metadata');
            if ($skillKey !== 'cash_bank_summary') {
                $this->assertEquals($ownUser->id, $metadata['own_user_id']);
            }
            
            if (in_array($skillKey, ['supplier_due', 'cash_bank_summary', 'slow_moving_products'])) {
                $this->assertTrue($metadata['failed_closed']);
                $this->assertEquals('own_access_restriction', $metadata['reason']);
            } elseif ($skillKey === 'daily_snapshot') {
                $this->assertFalse($metadata['failed_closed'] ?? false);
                $this->assertEquals('partial_own_access_restriction', $metadata['reason']);
            }
        }
    }
}
