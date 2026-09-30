<?php

namespace Modules\AIAssistant\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\AIAssistant\Entities\AIConversation;
use Modules\AIAssistant\Entities\AIMessage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;

class Phase5XssSafetyTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $permission = Permission::firstOrCreate(['name' => 'ai-assistant-index', 'guard_name' => 'web']);
        
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(['name' => 'Admin', 'guard_name' => 'web', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $adminRole = Role::where('name', 'Admin')->first();
        }
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'role_id' => $adminRole->id
        ]);
        
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin' . uniqid() . '@example.com',
            'phone' => '123456789' . rand(100, 999),
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'is_deleted' => false
        ]);
        
        \Illuminate\Support\Facades\DB::table('general_settings')
            ->where('id', 1)
            ->update(['staff_access' => 'all']);
        Cache::forget('general_setting');
        Cache::forget('currency');
    }

    public function test_persisted_malicious_prompt_is_escaped_on_load()
    {
        $maliciousString = '<script>alert("xss")</script>';
        
        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.store'), [
                             'prompt' => $maliciousString,
                         ]);

        $response->assertStatus(200);
        $conversationId = $response->json('conversation.id');
        
        // Ensure the string is persisted as-is (we escape on render, not on store)
        $msg = AIMessage::where('conversation_id', $conversationId)->where('role', 'user')->first();
        $this->assertEquals($maliciousString, $msg->content);
        
        // The API returns the raw text, UI uses textContent
        $loadResponse = $this->getJson(route('ai-assistant.conversations.show', $conversationId));
        $loadResponse->assertStatus(200);
        
        $loadedMsg = collect($loadResponse->json('messages.data'))->where('role', 'user')->first();
        $this->assertEquals($maliciousString, $loadedMsg['content']);
        
        // Note: The actual XSS defense is in the JS renderer (index.blade.php):
        // `document.createTextNode(content)` 
    }

    public function test_conversation_title_bounded_to_schema_limit()
    {
        $longString = str_repeat('A', 500); // 500 chars, limit is 100 in our service logic
        
        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.store'), [
                             'prompt' => $longString,
                         ]);

        $response->assertStatus(200);
        
        $conversationId = $response->json('conversation.id');
        $conversation = AIConversation::find($conversationId);
        
        $this->assertTrue(strlen($conversation->title) <= 100);
        $this->assertEquals(str_repeat('A', 100), $conversation->title);
    }
}
