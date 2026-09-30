<?php

use Illuminate\Support\Facades\Route;
use Modules\AIAssistant\Http\Controllers\AIAssistantController;
use Modules\AIAssistant\Http\Controllers\StructuredPromptController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\AIAssistant\Http\Controllers\AIConversationController;

// 1. Determine if this is a SaaS Landlord environment
$isSaaS = (bool) config('database.connections.saleprosaas_landlord');

// 2. Build Tenancy middleware array dynamically
$tenancyMiddleware = $isSaaS ? [InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class] : [];

// ==========================================
// ADMIN/COMMON ROUTES
// ==========================================
$middlewares = array_merge(
    $tenancyMiddleware,
    ['common', 'auth', 'active', 'permission:ai-assistant-index']
); 
 
Route::prefix('ai')->name('ai-assistant.')->middleware($middlewares)->group(function () {
    Route::get('ai-assistant', [AIAssistantController::class, 'index'])->name('index');

    // Structured prompt endpoint — POST /ai-assistant/prompt
    // Accepts a single 'prompt' string. All context is derived server-side.
    Route::post('ai-assistant/prompt', StructuredPromptController::class)->name('prompt');

    // Conversation Endpoints
    Route::prefix('ai-assistant/api/conversations')->group(function () {
        Route::get('/', [AIConversationController::class, 'index'])->name('conversations.index');
        Route::post('/', [AIConversationController::class, 'store'])->name('conversations.store');
        Route::get('{id}', [AIConversationController::class, 'show'])->name('conversations.show');
        Route::post('{id}/prompt', [AIConversationController::class, 'appendPrompt'])->name('conversations.prompt');
        Route::delete('{id}', [AIConversationController::class, 'destroy'])->name('conversations.destroy');
    });
});


