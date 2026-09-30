<?php

namespace Modules\AIAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\AIAssistant\Http\Requests\StructuredPromptRequest;
use Modules\AIAssistant\Services\AssistantExecutionService;
use Throwable;

/**
 * Handles structured prompt execution for the AI Assistant.
 *
 * Security contract:
 * - Route is protected by auth + permission:ai-assistant-index middleware.
 * - All context (user ID, warehouse IDs) is derived server-side from the
 *   authenticated session. The client cannot supply or override context.
 * - No external/paid API calls are made.
 * - No stack traces, SQL, secrets, or configuration is exposed in responses.
 */
class StructuredPromptController extends Controller
{
    /**
     * Execute a structured prompt via the AssistantExecutionService.
     *
     * POST /ai-assistant/prompt
     */
    public function __invoke(
        StructuredPromptRequest $request,
        AssistantExecutionService $executionService
    ): JsonResponse {
        try {
            $user = Auth::user();
            
            // Execute without persisting to a conversation (backward compatible for existing tests)
            $response = $executionService->execute(
                prompt: $request->validated('prompt'),
                user: $user
            );

            return response()->json($response->toArray());

        } catch (Throwable $e) {
            // Log the real error but return a sanitised message — no stack traces exposed
            \Illuminate\Support\Facades\Log::error('AI Assistant structured prompt failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('db.ai_assistant_error_processing'),
            ], 500);
        }
    }
}
