<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            
            // Core identity context
            $table->string('tenant_id', 191)->nullable();
            
            // Note: users table in this repository uses increments('id') (unsigned 32-bit integer)
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            
            // AI Context
            $table->string('provider', 50)->nullable();
            $table->string('skill_key', 100)->nullable();
            $table->string('request_type', 50); // 'structured' or 'provider'
            
            // Usage metrics
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            
            // Financial tracking (using 12,6 precision for small API fractions)
            $table->decimal('estimated_cost', 12, 6)->nullable();
            
            // Outcome tracking
            $table->string('status', 50); // 'success' or 'failed'
            $table->text('error_message')->nullable();
            
            // Append-only log only requires created_at. Nullable per Laravel default.
            $table->timestamp('created_at')->nullable();

            // Minimal query-oriented indexes
            // 1. Audit / Billing queries: tenant -> user -> time
            $table->index(['tenant_id', 'user_id', 'created_at'], 'ai_usage_logs_audit_idx');
            
            // 2. Analytics queries: provider -> skill
            $table->index(['provider', 'skill_key'], 'ai_usage_logs_analytics_idx');
            
            // 3. System Health queries: type -> status
            $table->index(['request_type', 'status'], 'ai_usage_logs_health_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
