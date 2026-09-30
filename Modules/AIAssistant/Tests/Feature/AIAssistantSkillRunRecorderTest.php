<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Modules\AIAssistant\Services\EloquentSkillRunRecorder;
use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Modules\AIAssistant\Entities\AISkillRun;
use Illuminate\Support\Facades\Log;
use Mockery;

class AIAssistantSkillRunRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Explicitly assert the active database is the testing database
        $this->assertEquals('salepro_testing', \Illuminate\Support\Facades\DB::connection()->getDatabaseName(), 'Tests must run on salepro_testing database');
        
        // Targeted module migration setup (fast and safe for testing without resetting unrelated tables)
        // This executes DDL which implicitly commits in MySQL, so it MUST run BEFORE the test transaction starts.
        $this->artisan('migrate', ['--path' => 'Modules/AIAssistant/Database/Migrations']);
        
        // Now start the per-test transaction natively to guarantee isolation
        \Illuminate\Support\Facades\DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback the per-test transaction safely regardless of test outcome
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        
        parent::tearDown();
    }

    public function test_eloquent_recorder_persists_valid_data_without_leaking_content()
    {
        // Prove test isolation: DB should be clean from any previous test's inserts
        $this->assertDatabaseCount('ai_skill_runs', 0);

        $recorder = new EloquentSkillRunRecorder();
        
        $skill = new class() implements AssistantSkill {
            public function key(): string { return 'test.persistence'; }
            public function name(): string { return 'name'; }
            public function description(): string { return 'desc'; }
            public function examples(): array { return []; }
            public function canHandle(AssistantMessageData $message): bool { return true; }
            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData {
                return new AssistantResponseData(textSummary: 'summary');
            }
        };

        // Simulated user message containing sensitive credentials
        $sensitiveContent = 'Here is my secret API key: sk-live-1234567890abcdef';
        $message = new AssistantMessageData(role: 'user', content: $sensitiveContent);
        $context = new AssistantContextData(tenantId: 'tenant1', userId: null);
        $response = new AssistantResponseData(textSummary: 'test summary');
        $executionMs = 150;

        $recorder->record($skill, $message, $context, $response, $executionMs);

        $this->assertDatabaseHas('ai_skill_runs', [
            'tenant_id' => 'tenant1',
            'user_id' => null,
            'skill_key' => 'test.persistence',
            'output_summary' => 'test summary',
            'execution_ms' => 150,
        ]);

        $run = AISkillRun::first();
        $this->assertIsArray($run->input);
        
        // Assert role is preserved
        $this->assertEquals('user', $run->input['role']);
        
        // Assert raw content is dropped and only length is stored
        $this->assertArrayNotHasKey('content', $run->input);
        $this->assertEquals(mb_strlen($sensitiveContent), $run->input['content_length']);
        
        // Ensure sensitive content is not in the database run object at all
        $this->assertStringNotContainsString('sk-live-', json_encode($run->toArray()));
    }

    public function test_eloquent_recorder_normalizes_markup_and_bounds_output_and_timing()
    {
        // Prove test isolation: DB should be clean from any previous test's inserts
        $this->assertDatabaseCount('ai_skill_runs', 0);

        $recorder = new EloquentSkillRunRecorder();
        
        $skill = new class() implements AssistantSkill {
            public function key(): string { return 'test.bounds'; }
            public function name(): string { return 'name'; }
            public function description(): string { return 'desc'; }
            public function examples(): array { return []; }
            public function canHandle(AssistantMessageData $message): bool { return true; }
            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData {
                return new AssistantResponseData(textSummary: 'summary');
            }
        };

        // Provide 2000 characters with HTML markup
        $largeOutput = '<h1>Title</h1><p>' . str_repeat('B', 2000) . '</p>';

        $message = new AssistantMessageData(role: 'user', content: 'test');
        $context = new AssistantContextData(tenantId: 'tenant1');
        $response = new AssistantResponseData(textSummary: $largeOutput);
        
        // 1. Large negative execution ms to test unsigned lower clamping
        $recorder->record($skill, $message, $context, $response, -500);

        $run = AISkillRun::orderBy('id', 'desc')->first();
        
        // Assert truncated to exactly 1000 characters (no suffix)
        $this->assertEquals(1000, mb_strlen($run->output_summary));
        // Assert HTML tags are completely removed
        $this->assertStringNotContainsString('<h1>', $run->output_summary);
        $this->assertStringNotContainsString('<p>', $run->output_summary);
        $this->assertStringStartsWith('Title', $run->output_summary);
        // Assert timing is clamped to 0
        $this->assertEquals(0, $run->execution_ms);

        // 2. Extra large positive execution ms to test unsigned upper clamping
        // Max unsigned integer is 4294967295
        $recorder->record($skill, $message, $context, $response, 5000000000);
        $run2 = AISkillRun::orderBy('id', 'desc')->first();
        $this->assertEquals(4294967295, $run2->execution_ms);
    }

    public function test_eloquent_recorder_swallows_persistence_exceptions_and_emits_sanitized_log()
    {
        // Prove test isolation: DB should be clean from any previous test's inserts
        $this->assertDatabaseCount('ai_skill_runs', 0);

        $recorder = new EloquentSkillRunRecorder();
        
        $skill = new class() implements AssistantSkill {
            public function key(): string { return str_repeat('X', 500); } // will crash DB insertion due to max length 100
            public function name(): string { return 'name'; }
            public function description(): string { return 'desc'; }
            public function examples(): array { return []; }
            public function canHandle(AssistantMessageData $message): bool { return true; }
            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData {
                return new AssistantResponseData(textSummary: 'summary');
            }
        };

        $sensitiveContent = 'sk-live-secret';
        $message = new AssistantMessageData(role: 'user', content: $sensitiveContent);
        $context = new AssistantContextData(tenantId: 'tenant1');
        $response = new AssistantResponseData(textSummary: 'test');

        // Expect a sanitized log without the exception object or sensitive user content
        Log::shouldReceive('error')
            ->once()
            ->with('AI skill run persistence failed', Mockery::on(function ($contextArray) use ($sensitiveContent) {
                // Must not contain full unbounded skill key
                if (mb_strlen($contextArray['skill_key']) > 100) return false;
                // Must not leak user inputs/exceptions
                if (isset($contextArray['exception'])) return false;
                if (isset($contextArray['input'])) return false;
                // Assert the string doesn't exist anywhere in the payload
                if (strpos(json_encode($contextArray), $sensitiveContent) !== false) return false;
                
                return $contextArray['tenant_id'] === 'tenant1';
            }));

        // This should trigger a PDOException/QueryException because skill_key is too long.
        // It must not bubble out of the record() method.
        $recorder->record($skill, $message, $context, $response, 100);

        // Prove no record was created (it failed silently)
        $this->assertDatabaseCount('ai_skill_runs', 0);
    }
}
