<?php

namespace Modules\AIAssistant\Services;

use Modules\AIAssistant\Contracts\AssistantSkillRunRecorder;
use Modules\AIAssistant\Contracts\AssistantSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\DTO\AssistantResponseData;
use Modules\AIAssistant\Entities\AISkillRun;
use Illuminate\Support\Facades\Log;

class EloquentSkillRunRecorder implements AssistantSkillRunRecorder
{
    /**
     * Records a successful skill execution to the database.
     * 
     * Policy:
     * - Input Representation: Privacy-minimal JSON containing only the role and content length.
     * - Output Representation: Plain text summary strictly bounded to 1000 characters.
     * - Execution Timing: Non-negative integer milliseconds bounded to unsigned integer limit.
     * - Persistence Error Policy: If a database error occurs (QueryException), catch it and emit a sanitized log event without raw SQL, exception traces, or user inputs.
     */
    public function record(
        AssistantSkill $skill,
        AssistantMessageData $message,
        AssistantContextData $context,
        AssistantResponseData $response,
        int $executionMs
    ): void {
        try {
            $input = [
                'role' => $message->role,
                'content_length' => mb_strlen($message->content),
            ];

            // Normalize output to plain text, then bound to 1000 characters
            $plainTextSummary = strip_tags($response->textSummary);
            $outputSummary = mb_substr($plainTextSummary, 0, 1000);
            
            // Guarantee non-negative execution time, bounded to max unsigned int 4294967295
            $executionMs = max(0, min($executionMs, 4294967295));

            AISkillRun::create([
                'tenant_id' => $context->tenantId,
                'user_id' => $context->userId,
                'skill_key' => $skill->key(),
                'input' => $input,
                'output_summary' => $outputSummary,
                'execution_ms' => $executionMs,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('AI skill run persistence failed', [
                'skill_key' => mb_substr($skill->key(), 0, 100),
                'tenant_id' => $context->tenantId,
            ]);
        }
    }
}
