<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Modules\AIAssistant\Services\SkillRegistry;
use Modules\AIAssistant\Services\AssistantOrchestrator;
use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;

class AIAssistantOrchestratorContainerTest extends TestCase
{
    public function test_orchestrator_is_resolved_with_singleton_registry()
    {
        // Resolve the registry singleton directly from the container
        $registry = $this->app->make(SkillRegistry::class);
        
        $fakeSkill = new class() implements AssistantSkill {
            public function key(): string { return 'test.container_skill'; }
            public function name(): string { return 'Container Test'; }
            public function description(): string { return 'Container desc'; }
            public function examples(): array { return []; }
            public function canHandle(AssistantMessageData $message): bool {
                return $message->content === 'trigger_container_test';
            }
            public function handle(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData {
                return new AssistantResponseData(textSummary: 'Resolved via container');
            }
        };

        // Register the skill into the singleton registry before resolving the orchestrator
        $registry->register($fakeSkill);

        // Resolve the orchestrator from the container. It should automatically receive the same registry singleton.
        $orchestrator = $this->app->make(AssistantOrchestrator::class);

        $message = new AssistantMessageData(role: 'user', content: 'trigger_container_test');
        $context = new AssistantContextData();

        $response = $orchestrator->executeStructured($message, $context);

        // Prove the orchestrator had visibility to the previously registered skill
        $this->assertEquals('Resolved via container', $response->textSummary);
    }
}
