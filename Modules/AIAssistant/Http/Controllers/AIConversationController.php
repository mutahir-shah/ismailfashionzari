<?php

namespace Modules\AIAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AIAssistant\Entities\AIConversation;
use Modules\AIAssistant\Http\Requests\StructuredPromptRequest;
use Modules\AIAssistant\Services\AssistantExecutionService;
use Throwable;

class AIConversationController extends Controller
{
    /**
     * Get the active tenant ID if applicable.
     */
    private function getTenantId(): ?string
    {
        return function_exists('tenant') && tenant('id') ? (string) tenant('id') : null;
    }

    /**
     * Verify the AI Assistant persistence tables exist on the active connection.
     *
     * In SaaS mode this runs after tenancy middleware, so it checks the tenant
     * database rather than the landlord database.
     */
    private function missingSchemaResponse(): ?JsonResponse
    {
        $requiredTables = [
            'ai_conversations',
            'ai_messages',
            'ai_skill_runs',
        ];

        try {
            $missingTables = array_values(array_filter(
                $requiredTables,
                fn ($table) => ! Schema::hasTable($table)
            ));
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Assistant schema check failed', [
                'user_id' => Auth::id(),
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('db.ai_assistant_error_schema_check'),
                'code' => 'ai_assistant_schema_check_failed',
            ], 503);
        }

        if (empty($missingTables)) {
            return null;
        }

        \Illuminate\Support\Facades\Log::warning('AI Assistant tables are missing on active database connection', [
            'user_id' => Auth::id(),
            'tenant_id' => $this->getTenantId(),
            'missing_tables' => $missingTables,
        ]);

        return response()->json([
            'error' => __('db.ai_assistant_error_migrations_missing'),
            'code' => 'ai_assistant_migrations_missing',
            'missing_tables' => $missingTables,
        ], 503);
    }

    /**
     * Retrieve a paginated list of conversations for the authenticated user/tenant.
     */
    public function index(): JsonResponse
    {
        if ($schemaError = $this->missingSchemaResponse()) {
            return $schemaError;
        }

        try {
            $conversations = AIConversation::forUserAndTenant(
                    Auth::id(),
                    $this->getTenantId()
                )
                ->select('id', 'title', 'updated_at')
                ->orderByDesc('updated_at')
                ->paginate(20);

            return response()->json($conversations);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Assistant conversation index failed', [
                'user_id' => Auth::id(),
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('db.ai_assistant_error_history'),
            ], 500);
        }
    }

    /**
     * Create a new conversation and execute the first prompt.
     */
    public function store(
        StructuredPromptRequest $request,
        AssistantExecutionService $executionService
    ): JsonResponse {
        try {
            if ($schemaError = $this->missingSchemaResponse()) {
                return $schemaError;
            }

            $user = Auth::user();
            $prompt = $request->validated('prompt');

            $result = $executionService->executeAndPersist($prompt, $user, null);

            return response()->json([
                'conversation' => $result['conversation'],
                'response'     => $result['response']->toArray(),
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Assistant conversation store failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('db.ai_assistant_error_processing'),
            ], 500);
        }
    }

    /**
     * Load a specific conversation and its paginated messages.
     */
    public function show($id): JsonResponse
    {
        if ($schemaError = $this->missingSchemaResponse()) {
            return $schemaError;
        }

        $conversation = AIConversation::forUserAndTenant(
            Auth::id(),
            $this->getTenantId()
        )->findOrFail($id);

        // Load messages with deterministic ordering, paginated.
        // We order by latest first to bound the page, then the frontend should reverse them for display.
        $messages = $conversation->messages()
                                 ->orderBy('created_at', 'desc')
                                 ->orderBy('id', 'desc')
                                 ->paginate(50);

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }

    /**
     * Submit a new prompt to an existing conversation.
     */
    public function appendPrompt(
        $id,
        StructuredPromptRequest $request,
        AssistantExecutionService $executionService
    ): JsonResponse {
        try {
            if ($schemaError = $this->missingSchemaResponse()) {
                return $schemaError;
            }

            $user = Auth::user();
            $prompt = $request->validated('prompt');

            $query = AIConversation::forUserAndTenant(
                $user->id,
                $this->getTenantId()
            );

            $conversation = $query->findOrFail($id);

            $result = $executionService->executeAndPersist($prompt, $user, $conversation);

            return response()->json([
                'conversation' => $result['conversation'],
                'response'     => $result['response']->toArray(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => __('db.ai_assistant_error_conversation_not_found')], 404);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Assistant conversation append failed', [
                'user_id'         => Auth::id(),
                'conversation_id' => $id,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => __('db.ai_assistant_error_processing'),
            ], 500);
        }
    }

    /**
     * Safely delete a conversation and its messages.
     */
    public function destroy($id): JsonResponse
    {
        if ($schemaError = $this->missingSchemaResponse()) {
            return $schemaError;
        }

        $conversation = AIConversation::forUserAndTenant(
            Auth::id(),
            $this->getTenantId()
        )->findOrFail($id);

        DB::transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        return response()->json(['success' => true]);
    }
}
