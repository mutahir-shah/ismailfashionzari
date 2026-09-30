<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAssistantConversationMigrationTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure the migration is run before the test
        $this->artisan('migrate', ['--path' => 'Modules/AIAssistant/Database/Migrations']);
    }

    /**
     * Test the ai_conversations table schema contract matches Architecture specifications exactly.
     */
    public function test_ai_conversations_schema_contract()
    {
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Table ai_conversations was not created.');
        
        $columns = collect(Schema::getColumns('ai_conversations'))->keyBy('name');
        
        // Assert exactly expected columns exist (no extra ones)
        $expectedColumns = ['id', 'tenant_id', 'user_id', 'title', 'mode', 'provider', 'created_at', 'updated_at'];
        $this->assertEquals($expectedColumns, $columns->keys()->toArray(), 'Schema columns do not strictly match the architecture.');

        // Nullability & Types
        $this->assertTrue($columns['tenant_id']['nullable'], 'tenant_id must be nullable for cross-mode compatibility.');
        $this->assertEquals('varchar', $columns['tenant_id']['type_name'], 'tenant_id must be string for compatibility.');
        
        $this->assertFalse($columns['user_id']['nullable'], 'user_id must not be nullable.');
        // Type could be int or integer based on DB driver, but unsigned is required
        
        $this->assertTrue($columns['title']['nullable'], 'title must be nullable.');
        
        $this->assertFalse($columns['mode']['nullable'], 'mode must not be nullable.');
        $this->assertEquals('structured', $columns['mode']['default'], 'mode default must be structured.');
        
        $this->assertTrue($columns['provider']['nullable'], 'provider must be nullable.');

        // Indexes
        $indexes = collect(Schema::getIndexes('ai_conversations'))->keyBy('name');
        
        $timeCompositeExists = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['tenant_id', 'user_id', 'created_at'];
        });
        
        $this->assertTrue($timeCompositeExists, 'Missing composite index for time-based history lookups.');

        // Verify it doesn't over-index (mode, provider should not have separate indexes now)
        $hasModeIndex = $indexes->contains(function($idx) { return $idx['columns'] === ['mode']; });
        $hasProviderIndex = $indexes->contains(function($idx) { return $idx['columns'] === ['provider']; });
        $this->assertFalse($hasModeIndex, 'mode column should not be individually indexed.');
        $this->assertFalse($hasProviderIndex, 'provider column should not be individually indexed.');

        // Foreign Keys
        $foreignKeys = collect(Schema::getForeignKeys('ai_conversations'));
        
        $userFk = $foreignKeys->first(function ($fk) {
            return $fk['columns'] === ['user_id'] && $fk['foreign_table'] === 'users' && $fk['foreign_columns'] === ['id'];
        });
        
        $this->assertNotNull($userFk, 'user_id foreign key constraint is missing or incorrect.');
        $this->assertEquals('cascade', strtolower($userFk['on_delete']), 'user_id foreign key must cascade on delete.');
    }

    /**
     * Test migration rollback safely drops the table after dependent tables are rolled back.
     */
    public function test_ai_conversations_table_can_rollback()
    {
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::getDatabaseName(), 'Rollback tests must only run in the testing database.');
        
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Table ai_conversations was not created.');
        
        // Due to foreign key constraints, we must rollback child tables first.
        // Unrelated tables (usage logs, skill runs, provider settings) do not reference conversations and need not be removed.
        
        // Step 1: Rollback messages (dependent on conversations)
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_181649_create_ai_messages_table.php',
            '--force' => true
        ])->assertExitCode(0);

        // Finally: Rollback conversations
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_000000_create_ai_conversations_table.php',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertFalse(Schema::hasTable('ai_conversations'), 'Table ai_conversations was not dropped during rollback.');
        
        // Re-run all migrations to restore state for other tests
        $this->artisan('migrate', [
            '--path' => 'Modules/AIAssistant/Database/Migrations',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Table ai_conversations was not restored properly.');
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Table ai_messages was not restored properly.');
    }
}
