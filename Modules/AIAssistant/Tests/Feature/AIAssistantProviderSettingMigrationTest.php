<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AIAssistantProviderSettingMigrationTest extends TestCase
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
     * Test the ai_provider_settings table schema contract.
     */
    public function test_ai_provider_settings_schema_contract()
    {
        $this->assertTrue(Schema::hasTable('ai_provider_settings'), 'Table ai_provider_settings was not created.');
        
        $columns = collect(Schema::getColumns('ai_provider_settings'))->keyBy('name');
        
        // Assert exactly expected columns exist (including internal normalization column)
        $expectedColumns = ['id', 'tenant_id', 'provider', 'api_key', 'base_url', 'model', 'is_enabled', 'settings', 'created_at', 'updated_at', 'normalized_tenant_id'];
        $this->assertEquals($expectedColumns, $columns->keys()->toArray(), 'Schema columns do not strictly match the architecture (missing helper column).');

        // Nullability & Types
        $this->assertTrue($columns['tenant_id']['nullable'], 'tenant_id must be nullable.');
        // Virtual/generated columns are often inferred as nullable by DB engines depending on the expression.
        $this->assertTrue(isset($columns['normalized_tenant_id']), 'normalized helper column must exist.');
        
        $this->assertFalse($columns['provider']['nullable'], 'provider must not be nullable.');
        
        $this->assertTrue($columns['api_key']['nullable'], 'api_key must be nullable.');
        $this->assertEquals('text', $columns['api_key']['type_name'], 'api_key must be text to safely hold encrypted ciphertext padding.');
        
        $this->assertTrue($columns['base_url']['nullable'], 'base_url must be nullable.');
        $this->assertTrue($columns['model']['nullable'], 'model must be nullable.');
        
        $this->assertFalse($columns['is_enabled']['nullable'], 'is_enabled must not be nullable.');
        $this->assertEquals('tinyint', $columns['is_enabled']['type_name'], 'is_enabled should be a boolean/tinyint.');
        
        // Assert exact secure default for is_enabled (0/false)
        $this->assertTrue(
            in_array($columns['is_enabled']['default'], ['0', 0, false, 'false'], true),
            'is_enabled must have a secure default of false/0.'
        );
        
        $this->assertTrue($columns['settings']['nullable'], 'settings must be nullable.');
        $this->assertEquals('json', $columns['settings']['type_name'], 'settings must be json type.');

        // Indexes
        $indexes = collect(Schema::getIndexes('ai_provider_settings'))->keyBy('name');
        
        $uniqueCompositeExists = $indexes->contains(function ($idx) {
            return $idx['columns'] === ['normalized_tenant_id', 'provider'] && isset($idx['unique']) && $idx['unique'] === true;
        });
        
        $this->assertTrue($uniqueCompositeExists, 'Missing unique composite index for normalized scope lookups.');
    }

    /**
     * Test the uniqueness strategy correctly handles global and tenant-specific duplicate prevention.
     */
    public function test_ai_provider_settings_enforces_normalized_uniqueness()
    {
        \DB::table('ai_provider_settings')->truncate();

        // 1. Insert a global (NULL tenant) provider
        \DB::table('ai_provider_settings')->insert([
            'tenant_id' => null,
            'provider' => 'openai',
            'is_enabled' => true,
        ]);

        // 2. Reject duplicate global provider
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('ai_provider_settings')->insert([
            'tenant_id' => null,
            'provider' => 'openai',
            'is_enabled' => false,
        ]);
    }

    /**
     * Test valid scopes and tenant duplicates.
     */
    public function test_ai_provider_settings_allows_different_scopes_and_rejects_tenant_duplicates()
    {
        \DB::table('ai_provider_settings')->truncate();

        \DB::table('ai_provider_settings')->insert([
            ['tenant_id' => null, 'provider' => 'gemini', 'is_enabled' => true],
            ['tenant_id' => 'tenant_a', 'provider' => 'gemini', 'is_enabled' => true],
            ['tenant_id' => 'tenant_a', 'provider' => 'openai', 'is_enabled' => true],
            ['tenant_id' => 'tenant_b', 'provider' => 'gemini', 'is_enabled' => true],
        ]);
        
        // Assert they were all inserted (different provider/tenant combos)
        $this->assertEquals(4, \DB::table('ai_provider_settings')->count());

        // Reject duplicate in same tenant
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('ai_provider_settings')->insert([
            'tenant_id' => 'tenant_a',
            'provider' => 'gemini',
            'is_enabled' => false,
        ]);
    }

    /**
     * Test collision-proof encoding prevents __global__ tenant ID from conflicting with global scope.
     */
    public function test_ai_provider_settings_prevents_sentinel_collision()
    {
        \DB::table('ai_provider_settings')->truncate();

        \DB::table('ai_provider_settings')->insert([
            // Global scope
            ['tenant_id' => null, 'provider' => 'openai', 'is_enabled' => true],
            
            // Literal '__global__' tenant scope
            ['tenant_id' => '__global__', 'provider' => 'openai', 'is_enabled' => true],
        ]);
        
        // If this succeeds, the encoding is strictly disjoint!
        $this->assertEquals(2, \DB::table('ai_provider_settings')->count());
    }

    /**
     * Test migration rollback safely drops the table without affecting parents.
     */
    public function test_ai_provider_settings_table_can_rollback()
    {
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::getDatabaseName(), 'Rollback tests must only run in the testing database.');
        
        $this->assertTrue(Schema::hasTable('ai_provider_settings'), 'Table ai_provider_settings was not created.');
        
        // Find the specific migration step for ai_provider_settings
        $this->artisan('migrate:reset', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_181953_create_ai_provider_settings_table.php',
            '--force' => true
        ])->assertExitCode(0);
        
        $this->assertFalse(Schema::hasTable('ai_provider_settings'), 'Table ai_provider_settings was not dropped during rollback.');
        $this->assertTrue(Schema::hasTable('ai_conversations'), 'Prior table ai_conversations was accidentally dropped during rollback!');
        $this->assertTrue(Schema::hasTable('ai_messages'), 'Prior table ai_messages was accidentally dropped during rollback!');
        
        // Re-run migration to leave DB in clean state for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'Modules/AIAssistant/Database/Migrations/2026_07_02_181953_create_ai_provider_settings_table.php',
            '--force' => true
        ])->assertExitCode(0);
    }
}
