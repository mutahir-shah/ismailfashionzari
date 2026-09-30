<?php

namespace Modules\AIAssistant\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Models\User;
use Modules\AIAssistant\Entities\AIConversation;
use Modules\AIAssistant\Entities\AIMessage;

class AIAssistantConversationMessageModelTest extends TestCase
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
            'role_id' => 1, // SalePro often requires this
        ]);
    }

    public function test_conversation_model_resolves_table_and_can_be_created()
    {
        $user = $this->createUser();

        $conversation = AIConversation::create([
            'tenant_id' => 'tenant_abc',
            'user_id' => $user->id,
            'provider' => 'openai',
            'mode' => 'structured',
            'title' => 'Test Conversation',
        ]);

        $this->assertEquals('ai_conversations', $conversation->getTable());
        $this->assertNotNull($conversation->id);
        $this->assertEquals('tenant_abc', $conversation->tenant_id);
        $this->assertEquals('structured', $conversation->mode);
        
        // Verify relationship to user
        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    public function test_message_model_metadata_cast_and_relationships()
    {
        $user = $this->createUser();
        $conversation = AIConversation::create([
            'user_id' => $user->id,
            'provider' => 'gemini',
            'mode' => 'mixed',
            'title' => 'Message Test',
        ]);

        // Message with JSON metadata
        $msg1 = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
            'response_type' => 'text',
            'metadata' => ['tokens' => 10, 'latency' => 150],
        ]);

        // Message with null metadata
        $msg2 = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Hi there',
            'response_type' => 'text',
            'metadata' => null,
        ]);

        $this->assertEquals('ai_messages', $msg1->getTable());
        $this->assertIsArray($msg1->metadata);
        $this->assertEquals(10, $msg1->metadata['tokens']);
        
        $this->assertNull($msg2->metadata, 'Metadata should remain strictly null when null is provided.');
        
        // Verify belongsTo relationship
        $this->assertInstanceOf(AIConversation::class, $msg1->conversation);
        $this->assertEquals($conversation->id, $msg1->conversation->id);
    }

    public function test_conversation_messages_are_deterministically_chronological()
    {
        $user = $this->createUser();
        $conversation = AIConversation::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'mode' => 'provider',
            'title' => 'Order Test',
        ]);

        // Create messages slightly out of order in terms of ID vs created_at just to prove sorting,
        // or just rely on the strict orderBy('created_at', 'asc')->orderBy('id', 'asc').
        
        $msg1 = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'First message',
            'response_type' => 'text',
        ]);
        
        // Simulate a message created earlier but inserted later
        $msg2 = new AIMessage([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Historical message',
            'response_type' => 'text',
        ]);
        $msg2->created_at = now()->subMinutes(10);
        $msg2->save();

        $msg3 = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Third message',
            'response_type' => 'text',
        ]);

        // Fetch messages via the relationship
        $messages = $conversation->messages;

        $this->assertCount(3, $messages);
        
        // Due to the sort order (created_at asc, id asc), msg2 should be FIRST because it's 10 minutes older.
        // msg1 should be SECOND. msg3 should be THIRD.
        $this->assertEquals($msg2->id, $messages[0]->id, 'Chronological ordering failed for first position.');
        $this->assertEquals($msg1->id, $messages[1]->id, 'Chronological ordering failed for second position.');
        $this->assertEquals($msg3->id, $messages[2]->id, 'Chronological ordering failed for third position.');
    }
}
