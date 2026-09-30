<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_client_remediation_manifests')) {
            return;
        }

        Schema::create('accounting_client_remediation_manifests', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->string('remediation_key')->index();
            $table->string('status')->index();
            $table->string('database_name');
            $table->string('baseline_dump_sha256');
            $table->string('fingerprint_before', 64);
            $table->string('fingerprint_after', 64)->nullable();
            $table->string('plan_hash', 64);
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->longText('manifest_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_client_remediation_manifests');
    }
};
