<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiProviderSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_provider_settings', function (Blueprint $table) {
            $table->id();
            
            // Nullable tenant identifier matching previous AI tables for cross-mode compatibility.
            $table->string('tenant_id', 191)->nullable();
            
            // The AI provider (e.g., openai, gemini, claude).
            $table->string('provider', 50);
            
            // Use 'text' instead of 'string' to safely accommodate encrypted payloads (ciphertext + IV + MAC),
            // which can exceed standard varchar(255) limits depending on the cipher and key length.
            $table->text('api_key')->nullable();
            
            $table->string('base_url', 191)->nullable();
            $table->string('model', 191)->nullable();
            
            // Secure default: disabled by default
            $table->boolean('is_enabled')->default(false);
            
            $table->json('settings')->nullable();
            
            $table->timestamps();

            // Internal helper column to normalize the nullable tenant scope for uniqueness.
            // Uses disjoint encoding to prevent collision between global scope and a tenant literally named '__global__'.
            // MySQL/MariaDB compatible CASE statement.
            $table->string('normalized_tenant_id', 220)->virtualAs("CASE WHEN tenant_id IS NULL THEN '__global__' ELSE CONCAT('__tenant__', tenant_id) END");

            // Unique constraint prevents duplicate active configurations (e.g., multiple NULL-tenant 'openai' rows).
            // This replaces the non-unique index and efficiently serves tenant/provider lookups.
            $table->unique(['normalized_tenant_id', 'provider'], 'ai_prov_set_tenant_prov_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_provider_settings');
    }
}
