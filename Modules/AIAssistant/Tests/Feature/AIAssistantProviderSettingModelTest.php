<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Modules\AIAssistant\Entities\AIProviderSetting;

class AIAssistantProviderSettingModelTest extends TestCase
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

    public function test_provider_setting_model_resolves_table_and_can_be_created()
    {
        $setting = AIProviderSetting::create([
            'tenant_id' => 'tenant_123',
            'provider' => 'openai',
            'api_key' => 'sk-test12345',
            'base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4o',
            'settings' => ['temperature' => 0.7],
            'is_enabled' => true,
        ]);

        $this->assertEquals('ai_provider_settings', $setting->getTable());
        $this->assertNotNull($setting->id);
        $this->assertEquals('tenant_123', $setting->tenant_id);
        $this->assertEquals('openai', $setting->provider);
        $this->assertEquals('https://api.openai.com/v1', $setting->base_url);
        $this->assertEquals('gpt-4o', $setting->model);
        $this->assertTrue($setting->is_enabled);
        $this->assertIsArray($setting->settings);
        $this->assertEquals(0.7, $setting->settings['temperature']);
        $setting->refresh();
        $this->assertEquals('__tenant__tenant_123', $setting->normalized_tenant_id);
    }

    public function test_api_key_is_encrypted_at_rest_and_decrypted_on_model()
    {
        $plaintextKey = 'sk-super-secret-key-999';
        
        $setting = AIProviderSetting::create([
            'tenant_id' => 'tenant_enc',
            'provider' => 'anthropic',
            'api_key' => $plaintextKey,
            'is_enabled' => false,
        ]);

        // Model should transparently decrypt.
        // Use assertTrue to prevent PHPUnit from printing the secret on failure.
        $this->assertTrue($setting->api_key === $plaintextKey, 'Model did not transparently return the decrypted key.');

        // Query the raw database row to prove it's encrypted at rest
        $rawRow = DB::table('ai_provider_settings')->where('id', $setting->id)->first();
        
        $this->assertNotNull($rawRow->api_key, 'Raw api_key should not be null.');
        
        // Use assertTrue to prevent PHPUnit from printing the ciphertext/plaintext on failure.
        $this->assertTrue($rawRow->api_key !== $plaintextKey, 'API key is stored in plaintext! Encryption failed.');
        
        // Typical Laravel encrypted payload is a JSON string or a long base64 string, so its length should be considerably larger
        $this->assertTrue(strlen($rawRow->api_key) > strlen($plaintextKey) + 20, 'Raw api_key does not appear to be a standard encrypted payload.');
    }

    public function test_api_key_is_hidden_from_serialization()
    {
        $setting = AIProviderSetting::create([
            'tenant_id' => 'tenant_ser',
            'provider' => 'gemini',
            'api_key' => 'sk-hidden-key-888',
        ]);

        $array = $setting->toArray();
        $json = $setting->toJson();

        $this->assertArrayNotHasKey('api_key', $array, 'api_key is exposed in array serialization!');
        
        // Use strpos with assertTrue to prevent PHPUnit from printing the JSON on failure
        $this->assertTrue(strpos($json, 'sk-hidden-key-888') === false, 'api_key plaintext leaked in JSON!');
        $this->assertTrue(strpos($json, 'api_key') === false, 'api_key key leaked in JSON!');
    }

    public function test_null_api_key_remains_null()
    {
        $setting = AIProviderSetting::create([
            'tenant_id' => 'tenant_null',
            'provider' => 'local',
            'api_key' => null,
        ]);

        $this->assertNull($setting->api_key, 'Null api_key was mutated.');

        $rawRow = DB::table('ai_provider_settings')->where('id', $setting->id)->first();
        $this->assertNull($rawRow->api_key, 'Null api_key was incorrectly encrypted into a string.');
    }

    public function test_normalized_tenant_id_is_not_mass_assignable()
    {
        $setting = AIProviderSetting::create([
            'tenant_id' => 'tenant_scope',
            'provider' => 'openai',
            'normalized_tenant_id' => 'hacked_scope',
        ]);

        // The database should overwrite 'hacked_scope' with the correct '__tenant__tenant_scope'
        // But also it shouldn't even reach the query if it's guarded from mass assignment.
        // Let's retrieve it fresh from the database to see the final stored value.
        $setting->refresh();

        $this->assertEquals('__tenant__tenant_scope', $setting->normalized_tenant_id, 'normalized_tenant_id was either mass-assigned or the DB trigger failed.');
    }

    public function test_eloquent_uniqueness_scenarios()
    {
        // 1. Distinct tenant scopes may use the same provider
        $setting1 = AIProviderSetting::create([
            'tenant_id' => 'tenant_A',
            'provider' => 'openai',
        ]);
        $this->assertNotNull($setting1->id);

        $setting2 = AIProviderSetting::create([
            'tenant_id' => 'tenant_B',
            'provider' => 'openai',
        ]);
        $this->assertNotNull($setting2->id);

        $settingGlobal = AIProviderSetting::create([
            'tenant_id' => null,
            'provider' => 'openai',
        ]);
        $this->assertNotNull($settingGlobal->id);

        // 2. Duplicate same-scope/provider creation must fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionCode('23000'); // SQLSTATE for constraint violation

        AIProviderSetting::create([
            'tenant_id' => 'tenant_A',
            'provider' => 'openai', // Already exists for tenant_A
        ]);
    }
}
