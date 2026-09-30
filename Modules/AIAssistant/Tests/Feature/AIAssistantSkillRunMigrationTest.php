<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAssistantSkillRunMigrationTest extends TestCase
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
     * Test the ai_skill_runs table schema contract.
     */
    public function test_ai_skill_runs_schema_contract()
    {
        $this->assertTrue(Schema::hasTable('ai_skill_runs'), 'Table ai_skill_runs was not created.');
        
        $columns = collect(Schema::getColumns('ai_skill_runs'))->keyBy('name');
        
        // Assert exactly expected columns exist
        $expectedColumns = [
            'id', 'tenant_id', 'user_id', 'skill_key', 'input', 
            'output_summary', 'execution_ms', 'created_at'
        ];
        $this->assertEquals($expectedColumns, $columns->keys()->toArray(), 'Schema columns do not strictly match the architecture.');

        // Nullability, Types & Lengths
        $this->assertTrue($columns['tenant_id']['nullable'], 'tenant_id must be nullable.');
        $this->assertEquals('varchar(191)', $columns['tenant_id']['type'], 'tenant_id must be varchar(191).');
        
        $this->assertTrue($columns['user_id']['nullable'], 'user_id must be nullable to support deleted users or system actions.');
        
        $this->assertFalse($columns['skill_key']['nullable'], 'skill_key must not be nullable.');
        $this->assertEquals('varchar(100)', $columns['skill_key']['type'], 'skill_key must be varchar(100).');
        
        $this->assertTrue($columns['input']['nullable'], 'input must be nullable.');
        $this->assertEquals('json', $columns['input']['type_name'], 'input must be json.');
        
        $this->assertTrue($columns['output_summary']['nullable'], 'output_summary must be nullable.');
        $this->assertEquals('text', $columns['output_summary']['type_name'], 'output_summary must be text.');
        
        $this->assertTrue($columns['execution_ms']['nullable'], 'execution_ms must be nullable.');
        $this->assertEquals('int unsigned', $columns['execution_ms']['type'], 'execution_ms must be unsigned integer.');
        
        $this->assertTrue($columns['created_at']['nullable'], 'created_at must be nullable.');

        // Indexes
        $indexes = collect(Schema::getIndexes('ai_skill_runs'))->keyBy('name');
        
        $hasAuditIdx = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['tenant_id', 'user_id', 'created_at'];
        });
        $this->assertTrue($hasAuditIdx, 'Missing composite index for audit (tenant -> user -> time).');
        
        $hasAnalyticsIdx = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['skill_key', 'created_at'];
        });
        $this->assertTrue($hasAnalyticsIdx, 'Missing composite index for analytics (skill -> time).');
        
        // Foreign Keys
        $foreignKeys = collect(Schema::getForeignKeys('ai_skill_runs'))->keyBy('name');
        $hasUserFk = $foreignKeys->contains(function ($fk) {
            return in_array('user_id', $fk['columns']) 
                && in_array('id', $fk['foreign_columns']) 
                && $fk['foreign_table'] === 'users'
                && $fk['on_delete'] === 'set null';
        });
        
        $this->assertTrue($hasUserFk, 'Missing foreign key constraint on user_id referencing users with ON DELETE SET NULL.');
    }

    /**
     * Test migration rollback safely drops the table without affecting prior tables.
     */
    public function test_ai_skill_runs_table_can_rollback()
    {
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::getDatabaseName(), 'Rollback tests must only run in the testing database.');
        
        // Execute rollback specifically for this migration
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_205000_create_ai_skill_runs_table.php',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertFalse(Schema::hasTable('ai_skill_runs'), 'Table ai_skill_runs was not dropped during rollback.');
        
        // Assert prior phase 2 tables are completely untouched
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Table ai_conversations was improperly dropped.');
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Table ai_messages was improperly dropped.');
        $this->assertTrue(Schema::hasTable('ai_provider_settings'), 'Table ai_provider_settings was improperly dropped.');
        $this->assertTrue(Schema::hasTable('ai_usage_logs'), 'Table ai_usage_logs was improperly dropped.');

        // Remigrate to leave DB clean for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_205000_create_ai_skill_runs_table.php',
            '--force' => true
        ])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('ai_skill_runs'), 'Table ai_skill_runs was not recreated.');
    }
}
