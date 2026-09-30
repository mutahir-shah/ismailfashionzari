@extends('backend.layout.main')

@section('content')
<style>
.ai-layout { display: flex; height: calc(100vh - 150px); min-height: 500px; border: 1px solid #e4e6fc; border-radius: 5px; overflow: hidden; background: #fff; position: relative; }
.ai-sidebar { width: 280px; border-right: 1px solid #e4e6fc; background: #f9f9f9; display: flex; flex-direction: column; z-index: 10; }
.ai-main { flex: 1; display: flex; flex-direction: column; position: relative; min-width: 0; }
.ai-history-header { padding: 15px; border-bottom: 1px solid #e4e6fc; display: flex; justify-content: space-between; align-items: center; }
.ai-history-list { flex: 1; overflow-y: auto; padding: 10px; }
.ai-history-item { padding: 10px 15px; cursor: pointer; border-radius: 5px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; color: #444; border: 1px solid transparent; }
.ai-history-item:hover { background: #f0f0f0; }
.ai-history-item.active { background: #e3eaef; font-weight: 500; border-color: #d1dfeb; }
.ai-history-item-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; }
.ai-history-item-delete { color: #dc3545; cursor: pointer; padding: 5px; border: none; background: none; border-radius: 3px; display: flex; align-items: center; justify-content: center; }
.ai-history-item-delete:hover { background: #f8d7da; }
.ai-history-load-more { text-align: center; padding: 10px; margin-top: 5px; cursor: pointer; color: #007bff; font-size: 0.9rem; }
.ai-history-load-more:hover { text-decoration: underline; }

.ai-chat-header { padding: 15px; border-bottom: 1px solid #e4e6fc; display: flex; align-items: center; background: #fff; }
.ai-drawer-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 15px; display: none; padding: 0; color: #333; }
.ai-chat-title { font-weight: 600; font-size: 1.1rem; margin: 0; }
.ai-chat-area { flex: 1; overflow-y: auto; padding: 20px; background: #fff; }

.ai-message { margin-bottom: 20px; display: flex; flex-direction: column; }
.ai-message-user { align-items: flex-end; }
.ai-message-assistant { align-items: flex-start; }
.ai-bubble { padding: 12px 18px; max-width: 85%; border-radius: 10px; font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }
.ai-message-user .ai-bubble { background: #7c5cc4; color: #fff; border-bottom-right-radius: 2px; }
.ai-message-assistant .ai-bubble { background: #f4f6f9; color: #333; border: 1px solid #e4e6fc; border-bottom-left-radius: 2px; }

.ai-structured-content { margin-top: 15px; width: 100%; }
.ai-structured-content h5 { font-size: 1rem; font-weight: 600; margin-bottom: 10px; }

.ai-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 15px; }
.ai-card { background: #fff; border: 1px solid #e4e6fc; padding: 12px; border-radius: 5px; text-align: center; }
.ai-card-title { font-size: 0.8rem; color: #6c757d; margin-bottom: 5px; text-transform: uppercase; }
.ai-card-value { font-size: 1.2rem; font-weight: 700; color: #333; }

.ai-table-wrapper { overflow-x: auto; margin-bottom: 15px; border: 1px solid #e4e6fc; border-radius: 5px; }
.ai-table { width: 100%; border-collapse: collapse; margin: 0; font-size: 0.9rem; }
.ai-table th, .ai-table td { padding: 8px 12px; border-bottom: 1px solid #e4e6fc; text-align: left; }
.ai-table th { background: #f9f9f9; font-weight: 600; color: #555; }
.ai-table tr:last-child td { border-bottom: none; }

.ai-warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #ffeeba; font-size: 0.9rem; display: flex; gap: 10px; align-items: flex-start; }
.ai-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #f5c6cb; font-size: 0.9rem; display: flex; gap: 10px; align-items: flex-start; }

.ai-link { margin-top: 10px; }
.ai-link a { display: inline-flex; align-items: center; gap: 5px; background: #e3eaef; color: #212529; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 500; }
.ai-link a:hover { background: #d1dfeb; }

.ai-composer { padding: 15px; border-top: 1px solid #e4e6fc; background: #fff; }
.ai-composer-form { display: flex; gap: 10px; align-items: flex-end; position: relative; }
.ai-composer-textarea { flex: 1; resize: none; border-radius: 20px; padding: 12px 45px 12px 15px; border: 1px solid #ced4da; outline: none; height: 48px; min-height: 48px; max-height: 150px; font-family: inherit; font-size: 0.95rem; overflow-y: auto; background: #f9f9f9; }
.ai-composer-textarea:focus { border-color: #7c5cc4; background: #fff; box-shadow: 0 0 0 0.2rem rgba(124, 92, 196, 0.25); }
.ai-composer-textarea:disabled { background: #e9ecef; cursor: not-allowed; }
.ai-composer-btn { position: absolute; right: 8px; bottom: 8px; border-radius: 50%; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; background: #7c5cc4; color: white; cursor: pointer; transition: background 0.2s; }
.ai-composer-btn:hover:not(:disabled) { background: #5a428c; }
.ai-composer-btn:disabled { background: #adb5bd; cursor: not-allowed; }

.ai-empty-state { text-align: center; margin-top: 40px; color: #6c757d; }
.ai-empty-state h3 { color: #333; margin-bottom: 10px; font-weight: 600; }
.ai-empty-state p { margin-bottom: 30px; font-size: 0.95rem; max-width: 600px; margin-left: auto; margin-right: auto; }
.ai-suggestions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; max-width: 800px; margin: 0 auto; text-align: left; }
.ai-suggestion-group h5 { font-size: 0.9rem; font-weight: 600; color: #555; margin-bottom: 10px; border-bottom: 1px solid #e4e6fc; padding-bottom: 5px; }
.ai-suggestion-btn { width: 100%; text-align: left; background: #fff; border: 1px solid #e4e6fc; padding: 10px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s; color: #444; font-size: 0.9rem; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
.ai-suggestion-btn:hover { border-color: #7c5cc4; background: #f8f6fd; color: #7c5cc4; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

.ai-overlay { display: none; position: absolute; inset: 0; background: rgba(0,0,0,0.4); z-index: 5; }

.ai-loading-indicator { display: flex; align-items: center; gap: 8px; padding: 10px 15px; color: #6c757d; font-style: italic; font-size: 0.9rem; }
.ai-spinner { width: 16px; height: 16px; border: 2px solid #ced4da; border-top-color: #7c5cc4; border-radius: 50%; animation: ai-spin 1s linear infinite; }
@keyframes ai-spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
    .ai-layout { height: calc(100vh - 80px); }
    .ai-sidebar { position: absolute; left: -280px; height: 100%; transition: left 0.3s ease; box-shadow: 2px 0 8px rgba(0,0,0,0.1); }
    .ai-sidebar.open { left: 0; }
    .ai-overlay.open { display: block; }
    .ai-drawer-btn { display: block; }
    .ai-bubble { max-width: 95%; }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .ai-sidebar { transition: none; }
    .ai-suggestion-btn:hover { transform: none; box-shadow: none; }
    .ai-spinner { animation-duration: 2s; }
}

.ai-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body p-0">
                <div class="ai-layout">
                    
                    <!-- Overlay for mobile -->
                    <div class="ai-overlay" id="aiOverlay" aria-hidden="true"></div>

                    <!-- Sidebar (History) -->
                    <aside class="ai-sidebar" id="aiSidebar" aria-label="{{ __('db.ai_assistant_conversation_history') }}">
                        <div class="ai-history-header">
                            <h5 class="m-0" style="font-size: 1rem; font-weight: 600;">{{ __('db.ai_assistant_history') }}</h5>
                            <button id="aiNewChatBtn" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" aria-label="{{ __('db.ai_assistant_new_conversation') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                {{ __('db.ai_assistant_new') }}
                            </button>
                        </div>
                        <div class="ai-history-list" id="aiHistoryList" role="list">
                            <!-- History items injected here -->
                        </div>
                    </aside>

                    <!-- Main Chat Panel -->
                    <main class="ai-main">
                        <div class="ai-chat-header">
                            <button class="ai-drawer-btn" id="aiDrawerBtn" aria-label="{{ __('db.ai_assistant_open_history') }}" aria-expanded="false" aria-controls="aiSidebar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                            </button>
                            <h2 class="ai-chat-title" id="aiChatTitle">{{ __('db.ai_assistant') }} <small style="font-size: 0.75em; color: #6c757d; font-weight: normal;">{{ __('db.ai_assistant_structured_subtitle') }}</small></h2>
                        </div>
                        
                        <!-- Accessible Live Region for Screen Readers -->
                        <div id="aiAriaLive" class="ai-sr-only" aria-live="polite" aria-atomic="true"></div>

                        <div class="ai-chat-area" id="aiChatArea" role="log" aria-live="polite">
                            <!-- Empty State & Suggestions -->
                            <div class="ai-empty-state" id="aiEmptyState">
                                <h3>{{ __('db.ai_assistant') }}</h3>
                                <p>{{ __('db.ai_assistant_intro') }}</p>
                                
                                <div class="ai-suggestions-grid">
                                    <div class="ai-suggestion-group">
                                        <h5>{{ __('db.ai_assistant_group_daily_sales') }}</h5>
                                        <button class="ai-suggestion-btn" data-prompt="Today's sales">
                                            {{ __('db.ai_assistant_suggestion_todays_sales') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Today's purchases">
                                            {{ __('db.ai_assistant_suggestion_todays_purchases') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Daily business snapshot">
                                            {{ __('db.ai_assistant_suggestion_daily_snapshot') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Today's expenses">
                                            {{ __('db.ai_assistant_suggestion_todays_expenses') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                    </div>
                                    <div class="ai-suggestion-group">
                                        <h5>{{ __('db.ai_assistant_group_inventory_products') }}</h5>
                                        <button class="ai-suggestion-btn" data-prompt="Low stock">
                                            {{ __('db.ai_assistant_suggestion_low_stock') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Top selling products">
                                            {{ __('db.ai_assistant_suggestion_top_products') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Slow moving products">
                                            {{ __('db.ai_assistant_suggestion_slow_products') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                    </div>
                                    <div class="ai-suggestion-group">
                                        <h5>{{ __('db.ai_assistant_group_finance_dues') }}</h5>
                                        <button class="ai-suggestion-btn" data-prompt="Customer due summary">
                                            {{ __('db.ai_assistant_suggestion_customer_dues') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Supplier due summary">
                                            {{ __('db.ai_assistant_suggestion_supplier_dues') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                        <button class="ai-suggestion-btn" data-prompt="Cash bank summary">
                                            {{ __('db.ai_assistant_suggestion_cash_bank') }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="aiTranscript"></div>
                            
                            <div id="aiLoading" class="ai-loading-indicator" style="display: none;" aria-hidden="true">
                                <div class="ai-spinner"></div>
                                <span>{{ __('db.ai_assistant_generating') }}</span>
                            </div>
                        </div>

                        <div class="ai-composer">
                            <form id="aiPromptForm" class="ai-composer-form">
                                <label for="aiPromptInput" class="ai-sr-only">{{ __('db.ai_assistant_message_label') }}</label>
                                <textarea id="aiPromptInput" class="ai-composer-textarea" placeholder="{{ __('db.ai_assistant_prompt_placeholder') }}" maxlength="500" required aria-required="true" aria-label="{{ __('db.ai_assistant_message_text') }}"></textarea>
                                <button type="submit" id="aiSubmitBtn" class="ai-composer-btn" aria-label="{{ __('db.ai_assistant_send_message') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                </button>
                            </form>
                            <div style="text-align: center; font-size: 0.75rem; color: #999; margin-top: 5px;">{{ __('db.ai_assistant_composer_help') }}</div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SVGs for re-use in JS -->
<template id="aiSvgTrash">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
</template>
<template id="aiSvgAlert">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
</template>
<template id="aiSvgLink">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
</template>
<template id="aiSvgX">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
</template>
@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@php
    $aiRendererPath = public_path($asset_prefix .'js/ai-renderer.js');
    $aiRendererVersion = is_file($aiRendererPath) ? filemtime($aiRendererPath) : null;
    $aiI18n = [
        'assistant' => __('db.ai_assistant'),
        'subtitle' => __('db.ai_assistant_structured_subtitle'),
        'conversation' => __('db.ai_assistant_conversation'),
        'deleteConversation' => __('db.ai_assistant_delete_conversation'),
        'confirmDelete' => __('db.ai_assistant_confirm_delete'),
        'loadMore' => __('db.ai_assistant_load_more'),
        'loadOlder' => __('db.ai_assistant_load_older'),
        'unexpectedError' => __('db.ai_assistant_error_unexpected'),
        'tooManyRequests' => __('db.ai_assistant_error_rate_limit'),
        'sessionExpired' => __('db.ai_assistant_error_session'),
        'serverError' => __('db.ai_assistant_error_server'),
        'networkError' => __('db.ai_assistant_error_network'),
        'conversationLoaded' => __('db.ai_assistant_conversation_loaded'),
        'failedLoadMessages' => __('db.ai_assistant_failed_load_messages'),
        'error' => __('db.ai_assistant_error_title'),
        'loading' => __('db.ai_assistant_loading'),
        'newChatStarted' => __('db.ai_assistant_new_chat_started'),
        'failedDelete' => __('db.ai_assistant_failed_delete'),
        'generating' => __('db.ai_assistant_generating'),
        'responseGenerated' => __('db.ai_assistant_response_generated'),
        'requestBlocked' => __('db.ai_assistant_request_blocked'),
        'restrictedScope' => __('db.ai_assistant_restricted_scope'),
    ];
@endphp
<script src="{{ asset($asset_prefix . 'js/ai-renderer.js') }}{{ $aiRendererVersion ? '?v='.$aiRendererVersion : '' }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = "{{ route('ai-assistant.conversations.index') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const i18n = @json($aiI18n);
    
    // UI Elements
    const elements = {
        sidebar: document.getElementById('aiSidebar'),
        overlay: document.getElementById('aiOverlay'),
        drawerBtn: document.getElementById('aiDrawerBtn'),
        historyList: document.getElementById('aiHistoryList'),
        newChatBtn: document.getElementById('aiNewChatBtn'),
        chatTitle: document.getElementById('aiChatTitle'),
        chatArea: document.getElementById('aiChatArea'),
        emptyState: document.getElementById('aiEmptyState'),
        transcript: document.getElementById('aiTranscript'),
        loading: document.getElementById('aiLoading'),
        form: document.getElementById('aiPromptForm'),
        input: document.getElementById('aiPromptInput'),
        submitBtn: document.getElementById('aiSubmitBtn'),
        ariaLive: document.getElementById('aiAriaLive')
    };

    const svgs = {
        trash: document.getElementById('aiSvgTrash').innerHTML,
        alert: document.getElementById('aiSvgAlert').innerHTML,
        link: document.getElementById('aiSvgLink').innerHTML,
        error: document.getElementById('aiSvgX').innerHTML
    };

    // State
    let state = {
        conversations: [],
        currentConversationId: null,
        isSubmitting: false,
        historyPage: 1,
        historyHasMore: true,
        loadingHistory: false,
        msgPage: 1,
        msgHasMore: true,
        loadingMessages: false
    };

    // --- Core Network functions ---
    async function apiRequest(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        if (body) options.body = JSON.stringify(body);

        try {
            const response = await fetch(apiBase + endpoint, options);
            const data = await response.json().catch(() => null);
            
            if (!response.ok) {
                let errorMsg = i18n.unexpectedError;
                if (response.status === 422 && data && data.errors && data.errors.prompt) {
                    errorMsg = data.errors.prompt[0];
                } else if (response.status === 429) {
                    errorMsg = i18n.tooManyRequests;
                } else if (response.status === 403 || response.status === 401 || response.status === 419) {
                    errorMsg = i18n.sessionExpired;
                } else if (data && data.error) {
                    errorMsg = data.error;
                } else if (response.status >= 500) {
                    errorMsg = i18n.serverError;
                }
                throw new Error(errorMsg);
            }
            return data;
        } catch (error) {
            // Throw generic network error if fetch itself fails
            if (error.message === 'Failed to fetch' || error.message.includes('NetworkError')) {
                throw new Error(i18n.networkError);
            }
            throw error;
        }
    }
    
    // --- Rendering Helpers ---
    function announce(msg) {
        elements.ariaLive.textContent = '';
        setTimeout(() => elements.ariaLive.textContent = msg, 50);
    }

    function scrollToBottom() {
        elements.chatArea.scrollTo({ top: elements.chatArea.scrollHeight, behavior: 'smooth' });
    }

    // --- History functions ---
    function renderHistoryList(append = false) {
        if (!append) elements.historyList.innerHTML = '';
        
        state.conversations.forEach(conv => {
            // Check if element already exists (if appending)
            if (document.getElementById('conv-' + conv.id)) return;

            const item = document.createElement('div');
            item.className = 'ai-history-item';
            item.id = 'conv-' + conv.id;
            item.setAttribute('role', 'listitem');
            item.tabIndex = 0;
            if (conv.id === state.currentConversationId) {
                item.classList.add('active');
            }
            
            const title = document.createElement('div');
            title.className = 'ai-history-item-title';
            title.appendChild(document.createTextNode(conv.title || i18n.conversation));
            
            const delBtn = document.createElement('button');
            delBtn.className = 'ai-history-item-delete';
            delBtn.setAttribute('aria-label', i18n.deleteConversation);
            delBtn.innerHTML = svgs.trash;
            delBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (confirm(i18n.confirmDelete)) {
                    await deleteConversation(conv.id);
                }
            });

            item.appendChild(title);
            item.appendChild(delBtn);
            
            item.addEventListener('click', () => loadConversation(conv.id));
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') loadConversation(conv.id);
            });
            
            elements.historyList.appendChild(item);
        });

        // Add Load More button if needed
        const existingLoadMore = document.getElementById('aiLoadMoreHist');
        if (existingLoadMore) existingLoadMore.remove();

        if (state.historyHasMore) {
            const loadMore = document.createElement('div');
            loadMore.className = 'ai-history-load-more';
            loadMore.textContent = i18n.loadMore;
            loadMore.id = 'aiLoadMoreHist';
            loadMore.addEventListener('click', loadHistory);
            elements.historyList.appendChild(loadMore);
        }
    }

    async function loadHistory() {
        if (state.loadingHistory || !state.historyHasMore) return;
        state.loadingHistory = true;
        try {
            const data = await apiRequest(`?page=${state.historyPage}`);
            if (data && data.data) {
                if (state.historyPage === 1) state.conversations = [];
                state.conversations = state.conversations.concat(data.data);
                state.historyHasMore = data.current_page < data.last_page;
                state.historyPage++;
                renderHistoryList(state.historyPage > 2);
            }
        } catch (e) {
            console.error('Failed to load history', e);
            if (state.historyPage === 1) {
                elements.historyList.innerHTML = '';
                const historyError = document.createElement('div');
                historyError.className = 'ai-history-load-more';
                historyError.style.cursor = 'default';
                historyError.appendChild(document.createTextNode(e.message));
                elements.historyList.appendChild(historyError);

                if (!state.currentConversationId && typeof window.renderAIError === 'function') {
                    elements.emptyState.style.display = 'none';
                    elements.transcript.appendChild(window.renderAIError(e.message, svgs));
                }
            }
        } finally {
            state.loadingHistory = false;
        }
    }

    async function loadConversationMessages(prepend = false) {
        if (state.loadingMessages || !state.msgHasMore || !state.currentConversationId) return;
        state.loadingMessages = true;
        const previousHeight = elements.chatArea.scrollHeight;
        
        try {
            const data = await apiRequest(`/${state.currentConversationId}?page=${state.msgPage}`);
            if (!prepend) {
                elements.chatTitle.textContent = data.conversation.title || i18n.conversation;
            }
            
            if (data.messages && data.messages.data) {
                state.msgHasMore = data.messages.current_page < data.messages.last_page;
                
                // Messages are returned desc by controller, we want to display chronologically (oldest at top).
                // But for prepending, we just insert them in chronological order at the top.
                const reversed = data.messages.data.reverse();
                
                const fragment = document.createDocumentFragment();
                reversed.forEach(msg => {
                    const msgEl = window.renderAIMessage(msg.role, msg.content, msg.response_type, msg.metadata, svgs, i18n);
                    fragment.appendChild(msgEl);
                });
                
                if (prepend) {
                    elements.transcript.insertBefore(fragment, elements.transcript.firstChild);
                    // Maintain scroll position
                    const newHeight = elements.chatArea.scrollHeight;
                    elements.chatArea.scrollTop = newHeight - previousHeight;
                } else {
                    elements.transcript.appendChild(fragment);
                    scrollToBottom();
                }

                state.msgPage++;

                // Render "Load older messages" button if has more
                const existingLoadMore = document.getElementById('aiLoadMoreMsg');
                if (existingLoadMore) existingLoadMore.remove();

                if (state.msgHasMore) {
                    const loadMore = document.createElement('div');
                    loadMore.className = 'ai-history-load-more';
                    loadMore.style.marginBottom = '15px';
                    loadMore.textContent = i18n.loadOlder;
                    loadMore.id = 'aiLoadMoreMsg';
                    loadMore.addEventListener('click', () => loadConversationMessages(true));
                    elements.transcript.insertBefore(loadMore, elements.transcript.firstChild);
                }
            }
            if (!prepend) announce(i18n.conversationLoaded);
        } catch (e) {
            const errEl = window.renderAIError(i18n.failedLoadMessages + ' ' + e.message, svgs);
            elements.transcript.appendChild(errEl);
            if (!prepend) elements.chatTitle.textContent = i18n.error;
        } finally {
            state.loadingMessages = false;
        }
    }

    async function loadConversation(id) {
        if (state.isSubmitting) return; // Block switching while generating
        
        state.currentConversationId = id;
        state.msgPage = 1;
        state.msgHasMore = true;
        
        elements.transcript.innerHTML = '';
        elements.emptyState.style.display = 'none';
        elements.chatTitle.textContent = i18n.loading;
        
        // Update active class in sidebar
        document.querySelectorAll('.ai-history-item').forEach(el => el.classList.remove('active'));
        const activeItem = document.getElementById('conv-' + id);
        if (activeItem) activeItem.classList.add('active');
        
        // Close drawer on mobile
        closeDrawer();

        await loadConversationMessages(false);
    }

    function startNewChat() {
        if (state.isSubmitting) return;
        state.currentConversationId = null;
        state.msgPage = 1;
        state.msgHasMore = true;
        elements.transcript.innerHTML = '';
        elements.emptyState.style.display = 'block';
        elements.chatTitle.textContent = i18n.assistant + ' — ' + i18n.subtitle;
        document.querySelectorAll('.ai-history-item').forEach(el => el.classList.remove('active'));
        closeDrawer();
        elements.input.focus();
        announce(i18n.newChatStarted);
    }

    async function deleteConversation(id) {
        try {
            await apiRequest(`/${id}`, 'DELETE');
            // Remove from state
            state.conversations = state.conversations.filter(c => c.id !== id);
            renderHistoryList();
            if (state.currentConversationId === id) {
                startNewChat();
            }
        } catch (e) {
            alert(i18n.failedDelete + ' ' + e.message);
        }
    }

    // --- Submission ---
    async function submitPrompt(promptStr) {
        if (!promptStr.trim() || state.isSubmitting) return;
        
        const currentId = state.currentConversationId;
        
        // Setup UI
        state.isSubmitting = true;
        elements.input.value = '';
        elements.input.disabled = true;
        elements.submitBtn.disabled = true;
        elements.emptyState.style.display = 'none';
        elements.loading.style.display = 'flex';
        
        const msgUser = window.renderAIMessage('user', promptStr, 'text', null, svgs, i18n);
        elements.transcript.appendChild(msgUser);
        scrollToBottom();
        announce(i18n.generating);

        try {
            let endpoint = currentId ? `/${currentId}/prompt` : '';
            const data = await apiRequest(endpoint, 'POST', { prompt: promptStr });
            
            // Stale check (did user click New Chat while waiting?)
            if (state.currentConversationId !== currentId && state.currentConversationId !== null) {
                // Just discard UI rendering, it persisted successfully on server
                return;
            }

            // If it was a new conversation, update state
            if (!currentId) {
                state.currentConversationId = data.conversation.id;
                elements.chatTitle.textContent = data.conversation.title;
                // Add to top of history
                state.conversations.unshift(data.conversation);
                renderHistoryList();
            }

            elements.loading.style.display = 'none';
            const msgAssistant = window.renderAIMessage('assistant', data.response.text_summary, data.response.response_type, data.response, svgs, i18n);
            elements.transcript.appendChild(msgAssistant);
            scrollToBottom();
            announce(i18n.responseGenerated);

        } catch (e) {
            // Stale check
            if (state.currentConversationId !== currentId) return;
            
            elements.loading.style.display = 'none';
            const errEl = window.renderAIError(e.message, svgs);
            elements.transcript.appendChild(errEl);
            scrollToBottom();
            
            // Restore input text so user doesn't lose it
            elements.input.value = promptStr;
        } finally {
            state.isSubmitting = false;
            elements.input.disabled = false;
            elements.submitBtn.disabled = false;
            if (state.currentConversationId === currentId) {
                elements.input.focus();
            }
        }
    }

    // --- Event Listeners ---
    elements.form.addEventListener('submit', (e) => {
        e.preventDefault();
        submitPrompt(elements.input.value);
    });

    elements.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submitPrompt(elements.input.value);
        }
    });
    
    // Auto-resize textarea
    elements.input.addEventListener('input', function() {
        this.style.height = '48px';
        this.style.height = (this.scrollHeight) + 'px';
    });

    elements.newChatBtn.addEventListener('click', startNewChat);

    // Suggested prompts
    document.querySelectorAll('.ai-suggestion-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt');
            if (prompt) submitPrompt(prompt);
        });
    });

    // Mobile Drawer
    function openDrawer() {
        elements.sidebar.classList.add('open');
        elements.overlay.classList.add('open');
        elements.drawerBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        elements.sidebar.classList.remove('open');
        elements.overlay.classList.remove('open');
        elements.drawerBtn.setAttribute('aria-expanded', 'false');
    }
    elements.drawerBtn.addEventListener('click', openDrawer);
    elements.overlay.addEventListener('click', closeDrawer);

    // Init
    loadHistory();
    elements.input.focus();
});
</script>
@endsection
