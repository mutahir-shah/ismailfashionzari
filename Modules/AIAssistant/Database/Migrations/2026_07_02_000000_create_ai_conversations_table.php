<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 191)->nullable();
            $table->unsignedInteger('user_id');
            $table->string('title', 191)->nullable();
            $table->string('mode', 50)->default('structured'); // structured, provider, mixed
            $table->string('provider', 191)->nullable();
            $table->timestamps();

            // Establish the foreign key following SalePro convention
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Composite index for time-based conversation history lookups by tenant and user
            $table->index(['tenant_id', 'user_id', 'created_at'], 'ai_conv_tenant_user_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('ai_conversations');
    }
}
