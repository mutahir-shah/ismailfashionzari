<?php

namespace Modules\AIAssistant\Tests\Unit;

use Tests\TestCase;
use Modules\AIAssistant\Services\AssistantOrchestrator;
use Modules\AIAssistant\Services\SkillRegistry;
use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use RuntimeException;

class AIAssistantOrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $translations = collect(include database_path('seeders/Tenant/translations/en.php'))
            ->filter(fn (array $row) => str_starts_with($row['key'], 'ai_assistant'))
            ->mapWithKeys(fn (array $row) => ['db.'.$row['key'] => $row['value']])
            ->all();
        app('translator')->addLines($translations, 'en');
        app()->setLocale('en');
    }

    private function createFakeSkill(string $key, bool $handles, ?callable $handleCallback = null): AssistantSkill
    {
        return new class($key, $handles, $handleCallback) implements AssistantSkill {
            public function __construct(
                private string $key, 
                private bool $handles, 
                private $handleCallback
            ) {}

            public function key(): string { return $this->key; }
            public function name(): string { return $this->key . ' name'; }
            public function description(): string { return 'desc'; }
            public function examples(): array { return []; }

            public function canHandle(AssistantMessageData $message): bool
            {
                return $this->handles;
            }

            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
            {
                if ($this->handleCallback) {
                    return ($this->handleCallback)($message, $context);
                }
                return new AssistantResponseData(textSummary: "Handled by {$this->key}");
            }
        };
    }

    public function test_orchestrator_returns_fallback_when_no_skill_matches()
    {
        $registry = new SkillRegistry();
        $orchestrator = new AssistantOrchestrator($registry);

        $message = new AssistantMessageData(role: 'user', content: 'Do something unknown');
        $context = new AssistantContextData();

        $response = $orchestrator->executeStructured($message, $context);

        $this->assertEquals("I'm not sure how to help with that request.", $response->textSummary);
        $this->assertContains("Please try asking a supported business question.", $response->warnings);
        $this->assertEquals('text', $response->responseType);
    }

    public function test_orchestrator_executes_matched_skill_exactly_once_with_original_dtos()
    {
        $registry = new SkillRegistry();
        
        $message = new AssistantMessageData(role: 'user', content: 'Do something known');
        $context = new AssistantContextData(tenantId: 't1');
        $expectedResponse = new AssistantResponseData(textSummary: "Success");

        $executionCount = 0;
        
        $skill = $this->createFakeSkill('test.skill', true, function($passedMsg, $passedCtx) use ($message, $context, $expectedResponse, &$executionCount) {
            $executionCount++;
            
            // Assert identical DTO instances reach the skill
            $this->assertSame($message, $passedMsg);
            $this->assertSame($context, $passedCtx);
            
            return $expectedResponse;
        });

        $registry->register($skill);
        $orchestrator = new AssistantOrchestrator($registry);

        $response = $orchestrator->executeStructured($message, $context);

        $this->assertEquals(1, $executionCount);
        // Assert the exact AssistantResponseData object is returned unchanged
        $this->assertSame($expectedResponse, $response);
    }

    public function test_orchestrator_allows_skill_handle_exceptions_to_bubble_up()
    {
        $registry = new SkillRegistry();
        
        $crashingSkill = $this->createFakeSkill('crash.skill', true, function() {
            throw new RuntimeException("Skill crashed during handle");
        });

        $registry->register($crashingSkill);
        $orchestrator = new AssistantOrchestrator($registry);

        $message = new AssistantMessageData(role: 'user', content: 'Crash it');
        $context = new AssistantContextData();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Skill crashed during handle");

        $orchestrator->executeStructured($message, $context);
    }

    public function test_orchestrator_allows_skill_can_handle_exceptions_to_bubble_up()
    {
        $registry = new SkillRegistry();
        
        $crashingSkill = new class() implements AssistantSkill {
            public function key(): string { return 'crash.can_handle'; }
            public function name(): string { return 'Crash'; }
            public function description(): string { return 'Crash'; }
            public function examples(): array { return []; }
            public function canHandle(AssistantMessageData $message): bool {
                throw new RuntimeException("Skill crashed during resolution");
            }
            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData {
                return new AssistantResponseData(textSummary: 'never');
            }
        };

        $registry->register($crashingSkill);
        $orchestrator = new AssistantOrchestrator($registry);

        $message = new AssistantMessageData(role: 'user', content: 'Crash it');
        $context = new AssistantContextData();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Skill crashed during resolution");

        $orchestrator->executeStructured($message, $context);
    }

    public function test_orchestrator_records_successful_skill_executions()
    {
        $registry = new SkillRegistry();
        
        $skill = $this->createFakeSkill('test.skill', true);
        $registry->register($skill);

        $recorded = clone (object)[];
        $fakeRecorder = new class($recorded) implements \Modules\AIAssistant\Contracts\AssistantSkillRunRecorder {
            public function __construct(private object $recorded) {}
            public function record(AssistantSkill $skill, AssistantMessageData $message, AssistantContextData $context, AssistantResponseData $response, int $executionMs): void {
                $this->recorded->skill = $skill;
                $this->recorded->message = $message;
                $this->recorded->context = $context;
                $this->recorded->response = $response;
                $this->recorded->executionMs = $executionMs;
            }
        };

        $orchestrator = new AssistantOrchestrator($registry, $fakeRecorder);

        $message = new AssistantMessageData(role: 'user', content: 'test');
        $context = new AssistantContextData();

        $orchestrator->executeStructured($message, $context);

        $this->assertObjectHasProperty('skill', $recorded);
        $this->assertSame($skill, $recorded->skill);
        $this->assertSame($message, $recorded->message);
        $this->assertGreaterThanOrEqual(0, $recorded->executionMs);
    }

    public function test_orchestrator_does_not_record_fallback_or_exceptions()
    {
        $registry = new SkillRegistry();
        
        $crashSkill = $this->createFakeSkill('crash.skill', true, function() {
            throw new RuntimeException("Crash");
        });
        $registry->register($crashSkill);

        $recorded = clone (object)[];
        $fakeRecorder = new class($recorded) implements \Modules\AIAssistant\Contracts\AssistantSkillRunRecorder {
            public function __construct(private object $recorded) {}
            public function record(AssistantSkill $skill, AssistantMessageData $message, AssistantContextData $context, AssistantResponseData $response, int $executionMs): void {
                $this->recorded->called = true;
            }
        };

        // Fallback scenario (skill won't match)
        $emptyRegistry = new SkillRegistry();
        $orchestratorFallback = new AssistantOrchestrator($emptyRegistry, $fakeRecorder);
        $orchestratorFallback->executeStructured(new AssistantMessageData(role: 'user', content: 'test'), new AssistantContextData());
        $this->assertObjectNotHasProperty('called', $recorded, "Fallback should not produce a skill run record");

        // Crash scenario
        $orchestratorCrash = new AssistantOrchestrator($registry, $fakeRecorder);
        try {
            $orchestratorCrash->executeStructured(new AssistantMessageData(role: 'user', content: 'test'), new AssistantContextData());
        } catch (RuntimeException $e) {}
        
        $this->assertObjectNotHasProperty('called', $recorded, "Crashed skill execution should not produce a skill run record");
    }
}
