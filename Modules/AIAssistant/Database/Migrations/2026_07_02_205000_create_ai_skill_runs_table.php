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
        Schema::create('ai_skill_runs', function (Blueprint $table) {
            $table->id();
            
            // Core identity context
            $table->string('tenant_id', 191)->nullable();
            
            // Note: users table uses increments('id') (unsigned 32-bit integer) in this app
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            
            // Skill context
            $table->string('skill_key', 100);
            
            // Skill parameters / result
            $table->json('input')->nullable();
            $table->text('output_summary')->nullable();
            
            // Execution timing (milliseconds, unsigned integer gives up to ~49 days)
            $table->unsignedInteger('execution_ms')->nullable();
            
            // Append-only timestamp. No updated_at.
            $table->timestamp('created_at')->nullable();

            // Minimal query-oriented indexes
            // 1. Audit queries: tenant -> user -> time
            $table->index(['tenant_id', 'user_id', 'created_at'], 'ai_skill_runs_audit_idx');
            
            // 2. Analytics queries: skill -> time
            $table->index(['skill_key', 'created_at'], 'ai_skill_runs_analytics_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_skill_runs');
    }
};
