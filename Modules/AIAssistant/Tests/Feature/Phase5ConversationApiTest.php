<?php

namespace Modules\AIAssistant\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\AIAssistant\Entities\AIConversation;
use Modules\AIAssistant\Entities\AIMessage;
use Modules\AIAssistant\Entities\AISkillRun;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Phase5ConversationApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;
    protected $normalUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoles();
    }

    protected function setUpRoles()
    {
        $permission = Permission::firstOrCreate(['name' => 'ai-assistant-index', 'guard_name' => 'web']);
        $saleIndexPerm = Permission::firstOrCreate(['name' => 'sales-index', 'guard_name' => 'web']);
        
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(['name' => 'Admin', 'guard_name' => 'web', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $adminRole = Role::where('name', 'Admin')->first();
        }
        
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->insertOrIgnore([
            ['permission_id' => $permission->id, 'role_id' => $adminRole->id],
            ['permission_id' => $saleIndexPerm->id, 'role_id' => $adminRole->id]
        ]);

        $staffRole = Role::where('name', 'Staff')->first();
        if (!$staffRole) {
            \Illuminate\Support\Facades\DB::table('roles')->insert(['name' => 'Staff', 'guard_name' => 'web', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $staffRole = Role::where('name', 'Staff')->first();
        }
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'role_id' => $staffRole->id
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

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('role_has_permissions_list'.$adminRole->id);
        Cache::forget('role_has_permissions_list'.$staffRole->id);

        $this->normalUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff' . uniqid() . '@example.com',
            'phone' => '123456789' . rand(100, 999),
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
            'is_active' => true,
            'is_deleted' => false
        ]);
        
        DB::table('general_settings')->where('id', 1)->update(['staff_access' => 'all']);
        Cache::forget('general_setting');
        Cache::forget('currency');
    }

    public function test_requires_auth_and_permission()
    {
        $this->getJson(route('ai-assistant.conversations.index'))
             ->assertStatus(401);

        DB::table('roles')->updateOrInsert(['id' => 100], [
            'name' => 'No Permission Role',
            'guard_name' => 'web',
            'is_active' => 1,
        ]);
        Cache::forget('role_has_permissions_list100');

        $noPermUser = User::create([
            'name' => 'No Perm User',
            'email' => 'noperm' . time() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => 100,
            'phone' => '1234567890',
            'is_active' => true,
            'is_deleted' => false,
        ]);
        $response = $this->actingAs($noPermUser)
                         ->getJson(route('ai-assistant.conversations.index'));
                         
        $this->assertTrue(in_array($response->status(), [403, 302]), 'Status should be 403 or 302');
    }

    public function test_can_list_conversations_with_pagination()
    {
        // Create 25 conversations
        for ($i = 0; $i < 25; $i++) {
            AIConversation::create([
                'user_id' => $this->adminUser->id,
                'provider' => 'structured',
                'mode' => 'structured',
                'title' => 'Title ' . $i,
            ]);
        }
        
        // Give normal user some conversations too, they shouldn't show up for admin
        AIConversation::create([
            'user_id' => $this->normalUser->id,
            'provider' => 'structured',
            'mode' => 'structured',
            'title' => 'Other Title',
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->getJson(route('ai-assistant.conversations.index'));
        
        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(25, $response->json('total'));
        
        // None should belong to the normal user (we just assert IDs are returned)
        foreach ($response->json('data') as $conv) {
            $this->assertArrayHasKey('id', $conv);
            $this->assertArrayHasKey('title', $conv);
            $this->assertArrayNotHasKey('user_id', $conv);
        }
    }

    public function test_can_start_conversation_and_store_message()
    {
        $initialRunCount = DB::table('ai_skill_runs')->count();
        $initialMsgCount = DB::table('ai_messages')->count();

        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.store'), [
                             'prompt' => 'show todays sales',
                         ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['conversation' => ['id', 'title'], 'response' => ['text_summary', 'cards', 'table']]);
        
        $conversationId = $response->json('conversation.id');
        $this->assertEquals('show todays sales', $response->json('conversation.title'));

        // Assert persistence
        $msgs = AIMessage::where('conversation_id', $conversationId)->orderBy('id')->get();
        $this->assertCount(2, $msgs);
        $this->assertEquals('user', $msgs[0]->role);
        $this->assertEquals('show todays sales', $msgs[0]->content);
        $this->assertEquals('assistant', $msgs[1]->role);
        
        // Ensure only one skill run was created
        $this->assertEquals($initialRunCount + 1, DB::table('ai_skill_runs')->count());
        $this->assertEquals($initialMsgCount + 2, DB::table('ai_messages')->count());
    }

    public function test_can_append_prompt_to_conversation()
    {
        $conversation = AIConversation::create([
            'user_id' => $this->adminUser->id,
            'provider' => 'structured',
            'mode' => 'structured',
            'title' => 'Title',
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.prompt', $conversation->id), [
                             'prompt' => 'Today\'s purchases',
                         ]);

        $response->assertStatus(200, $response->getContent());
        
        $msgs = AIMessage::where('conversation_id', $conversation->id)->get();
        $this->assertCount(2, $msgs);
    }

    public function test_tenant_and_user_isolation_on_show_and_append()
    {
        $conversation = AIConversation::create([
            'user_id' => $this->adminUser->id,
            'provider' => 'structured',
            'mode' => 'structured',
            'title' => 'Title',
        ]);

        // Normal user attempts to access admin's conversation
        $this->actingAs($this->normalUser)
             ->getJson(route('ai-assistant.conversations.show', $conversation->id))
             ->assertStatus(404);

        $this->actingAs($this->normalUser)
             ->postJson(route('ai-assistant.conversations.prompt', $conversation->id), [
                 'prompt' => 'x',
             ])
             ->assertStatus(404);
             
        $this->actingAs($this->normalUser)
             ->deleteJson(route('ai-assistant.conversations.destroy', $conversation->id))
             ->assertStatus(404);
    }

    public function test_fallback_does_not_create_run_but_persists_messages()
    {
        $initialRunCount = DB::table('ai_skill_runs')->count();

        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.store'), [
                             'prompt' => 'What is the meaning of life?',
                         ]);

        $response->assertStatus(200);
        
        $conversationId = $response->json('conversation.id');
        $msgs = AIMessage::where('conversation_id', $conversationId)->get();
        
        $this->assertCount(2, $msgs);
        $this->assertStringContainsString('not sure how to help', $response->json('response.text_summary'));
        
        // Run count should NOT increase for fallback
        $this->assertEquals($initialRunCount, DB::table('ai_skill_runs')->count());
    }

    public function test_transaction_rollback_on_failed_validation()
    {
        $initialMsgCount = DB::table('ai_messages')->count();

        $response = $this->actingAs($this->adminUser)
                         ->postJson(route('ai-assistant.conversations.store'), [
                             'prompt' => '', // Fails validation
                         ]);

        $response->assertStatus(422);
        
        // Assert no partial messages were persisted
        $this->assertEquals($initialMsgCount, DB::table('ai_messages')->count());
    }

    public function test_cascading_delete()
    {
        $conversation = AIConversation::create([
            'user_id' => $this->adminUser->id,
            'provider' => 'structured',
            'mode' => 'structured',
            'title' => 'Delete Me',
        ]);
        
        AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'hello',
        ]);

        $this->assertEquals(1, AIMessage::where('conversation_id', $conversation->id)->count());

        $this->actingAs($this->adminUser)
             ->deleteJson(route('ai-assistant.conversations.destroy', $conversation->id))
             ->assertStatus(200);

        $this->assertEquals(0, AIConversation::where('id', $conversation->id)->count());
        $this->assertEquals(0, AIMessage::where('conversation_id', $conversation->id)->count());
    }
}
