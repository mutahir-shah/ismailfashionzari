<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            
            // The referenced ai_conversations table uses id() which generates an unsignedBigInteger.
            // So conversation_id must be unsignedBigInteger.
            $table->unsignedBigInteger('conversation_id');
            
            // Following repository convention, we avoid DB-level ENUMs and use strings 
            // for maximum compatibility, relying on app-level validation.
            $table->string('role', 50); // user, assistant, system
            $table->longText('content');
            $table->string('response_type', 50)->default('text'); // text, card, table, chart, error
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            // Establish the foreign key. Cascade deletion makes sense here since 
            // messages belong strictly to their parent conversation.
            $table->foreign('conversation_id')
                  ->references('id')
                  ->on('ai_conversations')
                  ->onDelete('cascade');
                  
            // Composite index for fast ordered retrieval of a conversation's messages.
            $table->index(['conversation_id', 'created_at'], 'ai_messages_conv_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
        });
        Schema::dropIfExists('ai_messages');
    }
}
