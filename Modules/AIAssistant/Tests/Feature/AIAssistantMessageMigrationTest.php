<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAssistantMessageMigrationTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure the migrations are run before the test
        $this->artisan('migrate', ['--path' => 'Modules/AIAssistant/Database/Migrations']);
    }

    /**
     * Test the ai_messages table schema contract.
     */
    public function test_ai_messages_schema_contract()
    {
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Table ai_messages was not created.');
        
        $columns = collect(Schema::getColumns('ai_messages'))->keyBy('name');
        
        // Assert exactly expected columns exist
        $expectedColumns = ['id', 'conversation_id', 'role', 'content', 'response_type', 'metadata', 'created_at', 'updated_at'];
        $this->assertEquals($expectedColumns, $columns->keys()->toArray(), 'Schema columns do not strictly match the architecture.');

        // Nullability & Types
        $this->assertFalse($columns['conversation_id']['nullable'], 'conversation_id must not be nullable.');
        $this->assertEquals('bigint', $columns['conversation_id']['type_name'], 'conversation_id must be a big integer to match ai_conversations.id.');
        
        $this->assertFalse($columns['role']['nullable'], 'role must not be nullable.');
        $this->assertEquals('varchar', $columns['role']['type_name'], 'role should be a string for compatibility.');
        
        $this->assertFalse($columns['content']['nullable'], 'content must not be nullable.');
        $this->assertEquals('longtext', $columns['content']['type_name'], 'content must be longtext.');
        
        $this->assertFalse($columns['response_type']['nullable'], 'response_type must not be nullable.');
        $this->assertEquals('text', $columns['response_type']['default'], 'response_type default must be text.');
        $this->assertEquals('varchar', $columns['response_type']['type_name'], 'response_type should be a string for compatibility.');
        
        $this->assertTrue($columns['metadata']['nullable'], 'metadata must be nullable.');
        $this->assertEquals('json', $columns['metadata']['type_name'], 'metadata must be a json column.');

        // Indexes
        $indexes = collect(Schema::getIndexes('ai_messages'))->keyBy('name');
        
        $timeCompositeExists = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['conversation_id', 'created_at'];
        });
        
        $this->assertTrue($timeCompositeExists, 'Missing composite index for ordered message retrieval within a conversation.');

        // Foreign Keys
        $foreignKeys = collect(Schema::getForeignKeys('ai_messages'));
        
        $convFk = $foreignKeys->first(function ($fk) {
            return $fk['columns'] === ['conversation_id'] && $fk['foreign_table'] === 'ai_conversations' && $fk['foreign_columns'] === ['id'];
        });
        
        $this->assertNotNull($convFk, 'conversation_id foreign key constraint is missing or incorrect.');
        $this->assertEquals('cascade', strtolower($convFk['on_delete']), 'conversation_id foreign key must cascade on delete.');
    }

    /**
     * Test migration rollback perfectly cleans up and preserves parent table.
     */
    public function test_ai_messages_table_can_rollback_without_affecting_parent()
    {
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::getDatabaseName(), 'Rollback tests must only run in the testing database.');
        
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Table ai_messages was not created.');
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Parent table ai_conversations missing before rollback.');
        
        // Find the specific migration step for ai_messages
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_181649_create_ai_messages_table.php',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertFalse(Schema::hasTable('ai_messages'), 'Table ai_messages was not dropped during rollback.');
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Parent table ai_conversations was accidentally dropped during rollback!');
        
        // Re-run migration to leave DB in clean state for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_181649_create_ai_messages_table.php',
            '--force' => true
        ])->assertExitCode(0);
    }
}
