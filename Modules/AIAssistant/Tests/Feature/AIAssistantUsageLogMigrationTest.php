<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAssistantUsageLogMigrationTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run all migrations to ensure the table is ready
        $this->artisan('migrate', ['--path' => 'Modules/AIAssistant/Database/Migrations', '--force' => true]);
    }

    /**
     * Test the ai_usage_logs table schema contract.
     */
    public function test_ai_usage_logs_schema_contract()
    {
        $this->assertTrue(Schema::hasTable('ai_usage_logs'), 'Table ai_usage_logs was not created.');
        
        $columns = collect(Schema::getColumns('ai_usage_logs'))->keyBy('name');
        
        // Assert exactly expected columns exist
        $expectedColumns = [
            'id', 'tenant_id', 'user_id', 'provider', 'skill_key', 
            'request_type', 'prompt_tokens', 'completion_tokens', 
            'total_tokens', 'estimated_cost', 'status', 'error_message', 
            'created_at'
        ];
        $this->assertEquals($expectedColumns, $columns->keys()->toArray(), 'Schema columns do not strictly match the architecture.');

        // Nullability, Types & Lengths
        $this->assertTrue($columns['tenant_id']['nullable'], 'tenant_id must be nullable.');
        $this->assertEquals('varchar(191)', $columns['tenant_id']['type'], 'tenant_id must be varchar(191).');
        
        $this->assertTrue($columns['user_id']['nullable'], 'user_id must be nullable to support deleted users or system actions.');
        
        $this->assertTrue($columns['provider']['nullable'], 'provider must be nullable.');
        $this->assertEquals('varchar(50)', $columns['provider']['type'], 'provider must be varchar(50).');
        
        $this->assertTrue($columns['skill_key']['nullable'], 'skill_key must be nullable.');
        $this->assertEquals('varchar(100)', $columns['skill_key']['type'], 'skill_key must be varchar(100).');
        
        $this->assertFalse($columns['request_type']['nullable'], 'request_type must not be nullable.');
        $this->assertEquals('varchar(50)', $columns['request_type']['type'], 'request_type must be varchar(50).');
        
        $this->assertTrue($columns['prompt_tokens']['nullable'], 'prompt_tokens must be nullable.');
        $this->assertEquals('int unsigned', $columns['prompt_tokens']['type'], 'prompt_tokens must be unsigned integer.');
        
        $this->assertTrue($columns['completion_tokens']['nullable'], 'completion_tokens must be nullable.');
        $this->assertEquals('int unsigned', $columns['completion_tokens']['type'], 'completion_tokens must be unsigned integer.');
        
        $this->assertTrue($columns['total_tokens']['nullable'], 'total_tokens must be nullable.');
        $this->assertEquals('int unsigned', $columns['total_tokens']['type'], 'total_tokens must be unsigned integer.');
        
        $this->assertTrue($columns['estimated_cost']['nullable'], 'estimated_cost must be nullable.');
        $this->assertEquals('decimal(12,6)', $columns['estimated_cost']['type'], 'estimated_cost must be exactly decimal(12, 6).');
        
        $this->assertFalse($columns['status']['nullable'], 'status must not be nullable.');
        $this->assertEquals('varchar(50)', $columns['status']['type'], 'status must be varchar(50).');
        
        $this->assertTrue($columns['error_message']['nullable'], 'error_message must be nullable.');
        $this->assertEquals('text', $columns['error_message']['type_name'], 'error_message should be text type.');

        // Indexes
        $indexes = collect(Schema::getIndexes('ai_usage_logs'))->keyBy('name');
        
        $hasAuditIdx = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['tenant_id', 'user_id', 'created_at'];
        });
        $this->assertTrue($hasAuditIdx, 'Missing composite index for audit/billing (tenant -> user -> time).');
        
        $hasAnalyticsIdx = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['provider', 'skill_key'];
        });
        $this->assertTrue($hasAnalyticsIdx, 'Missing composite index for analytics (provider -> skill).');
        
        $hasHealthIdx = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['request_type', 'status'];
        });
        $this->assertTrue($hasHealthIdx, 'Missing composite index for system health (request_type -> status).');

        // Foreign Keys
        $foreignKeys = collect(Schema::getForeignKeys('ai_usage_logs'))->keyBy('name');
        $hasUserFk = $foreignKeys->contains(function ($fk) {
            return in_array('user_id', $fk['columns']) 
                && in_array('id', $fk['foreign_columns']) 
                && $fk['foreign_table'] === 'users'
                && $fk['on_delete'] === 'set null';
        });
        
        $this->assertTrue($hasUserFk, 'Missing foreign key constraint on user_id referencing users with ON DELETE SET NULL.');
    }

    /**
     * Test migration rollback safely drops the table without affecting parents.
     */
    public function test_ai_usage_logs_table_can_rollback()
    {
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::getDatabaseName(), 'Rollback tests must only run in the testing database.');
        
        // Execute rollback specifically for this migration
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_192839_create_ai_usage_logs_table.php',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertFalse(Schema::hasTable('ai_usage_logs'), 'Table ai_usage_logs was not dropped during rollback.');
        
        // Assert prior phase 2 tables are completely untouched
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Table ai_conversations was improperly dropped.');
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Table ai_messages was improperly dropped.');
        $this->assertTrue(Schema::hasTable('ai_provider_settings'), 'Table ai_provider_settings was improperly dropped.');

        // Remigrate to leave DB clean for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_192839_create_ai_usage_logs_table.php',
            '--force' => true
        ])->assertExitCode(0);
    }
}
