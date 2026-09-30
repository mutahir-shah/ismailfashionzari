<?php

namespace Modules\AIAssistant\Services;

use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;

class AssistantOrchestrator
{
    public function __construct(
        private SkillRegistry $registry,
        private ?\Modules\AIAssistant\Contracts\AssistantSkillRunRecorder $recorder = null
    ) {
    }

    /**
     * Executes the assistant logic in pure structured mode.
     * It resolves a matching skill from the registry and invokes it.
     * If no skill matches, it returns a safe, non-technical fallback response.
     * 
     * Exception Policy: Any exceptions thrown by `resolve()` (e.g., in a skill's `canHandle`) 
     * or by `$skill->handle()` are deliberately allowed to bubble up to the caller. 
     * We do not silently swallow them.
     */
    public function executeStructured(AssistantMessageData $message, AssistantContextData $context): AssistantResponseData
    {
        $skill = $this->registry->resolve($message);

        if ($skill === null) {
            return new AssistantResponseData(
                textSummary: __('db.ai_assistant_fallback_summary'),
                responseType: 'text',
                warnings: [__('db.ai_assistant_fallback_warning')]
            );
        }

        $startTime = microtime(true);
        $response = $skill->handle($message, $context);
        $executionMs = (int) round((microtime(true) - $startTime) * 1000);

        if ($this->recorder !== null) {
            $this->recorder->record($skill, $message, $context, $response, $executionMs);
        }

        return $response;
    }
}
