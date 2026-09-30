<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\AIAssistant\Entities\AIUsageLog;
use Modules\AIAssistant\Entities\AISkillRun;
use App\Models\User;

class AIAssistantUsageSkillModelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure AI tables are present for this test without a full DB wipe.
        $this->artisan('migrate', ['--path' => 'Modules/AIAssistant/Database/Migrations', '--force' => true]);
    }

    protected function createUser()
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'phone' => '1234567890' . rand(10, 99),
            'password' => bcrypt('password'),
            'is_active' => 1,
            'is_deleted' => 0,
            'role_id' => 1,
        ]);
    }

    public function test_usage_log_model_resolves_table_and_can_be_created()
    {
        $user = $this->createUser();

        $log = AIUsageLog::create([
            'tenant_id' => 'tenant_123',
            'user_id' => $user->id,
            'provider' => 'openai',
            'skill_key' => 'generate_invoice',
            'request_type' => 'structured',
            'prompt_tokens' => 1500,
            'completion_tokens' => 300,
            'total_tokens' => 1800,
            'estimated_cost' => 0.035125,
            'status' => 'success',
            'error_message' => null,
        ]);

        $this->assertEquals('ai_usage_logs', $log->getTable());
        $this->assertNotNull($log->id);
        $this->assertEquals('tenant_123', $log->tenant_id);
        $this->assertEquals($user->id, $log->user_id);
        
        // Casts verification
        $this->assertIsInt($log->prompt_tokens);
        $this->assertEquals(1500, $log->prompt_tokens);
        
        $this->assertIsInt($log->total_tokens);
        $this->assertEquals(1800, $log->total_tokens);
        
        // Financial tracking (using 12,6 precision for small API fractions)
        // Note: eloquent decimal cast returns string by default in many versions,
        // but since we specified decimal:6 it casts to string with 6 decimals.
        // Let's assert the raw underlying numeric value matches
        $this->assertEquals(0.035125, (float) $log->estimated_cost);

        // Appends-only timestamps
        $this->assertNotNull($log->created_at);
        $this->assertArrayNotHasKey('updated_at', $log->getAttributes(), 'updated_at should not be present in append-only model.');
        
        // Relationship
        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_skill_run_model_resolves_table_and_can_be_created()
    {
        $user = $this->createUser();

        $run = AISkillRun::create([
            'tenant_id' => 'tenant_abc',
            'user_id' => $user->id,
            'skill_key' => 'financial_analysis',
            'input' => ['period' => '2026-Q1', 'include_forecast' => true],
            'output_summary' => 'Generated 5 page report.',
            'execution_ms' => 4500,
        ]);

        $this->assertEquals('ai_skill_runs', $run->getTable());
        $this->assertNotNull($run->id);
        $this->assertEquals('tenant_abc', $run->tenant_id);
        
        // Casts verification
        $this->assertIsArray($run->input);
        $this->assertTrue($run->input['include_forecast']);
        $this->assertEquals('2026-Q1', $run->input['period']);
        
        $this->assertIsInt($run->execution_ms);
        $this->assertEquals(4500, $run->execution_ms);

        // Appends-only timestamps
        $this->assertNotNull($run->created_at);
        $this->assertArrayNotHasKey('updated_at', $run->getAttributes(), 'updated_at should not be present in append-only model.');
        
        // Relationship
        $this->assertInstanceOf(User::class, $run->user);
        $this->assertEquals($user->id, $run->user->id);
    }

    public function test_usage_and_skill_models_support_nullable_users()
    {
        $log = AIUsageLog::create([
            'tenant_id' => 'tenant_sys',
            'user_id' => null, // Background / system task
            'provider' => 'gemini',
            'request_type' => 'provider',
            'status' => 'success',
        ]);

        $this->assertNull($log->user_id);
        $this->assertNull($log->user);

        $run = AISkillRun::create([
            'tenant_id' => 'tenant_sys',
            'user_id' => null, // Background / system task
            'skill_key' => 'system_maintenance',
            'input' => [],
        ]);

        $this->assertNull($run->user_id);
        $this->assertNull($run->user);
    }
}
