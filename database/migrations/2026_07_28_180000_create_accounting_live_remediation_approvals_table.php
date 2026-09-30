<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_live_remediation_approvals')) {
            return;
        }

        Schema::create('accounting_live_remediation_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status')->index();
            $table->string('target_database_name')->index();
            $table->string('dump_filename');
            $table->string('dump_sha256', 64);
            $table->string('database_fingerprint', 64);
            $table->string('remediation_plan_hash', 64);
            $table->string('expected_application_commit', 64);
            $table->json('expected_key_row_counts')->nullable();
            $table->json('expected_financial_balances')->nullable();
            $table->boolean('backup_restore_test_confirmed')->default(false);
            $table->boolean('maintenance_mode_required')->default(true);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedBigInteger('remediation_manifest_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at'], 'acct_live_appr_status_exp_idx');
            $table->index(['target_database_name', 'consumed_at'], 'acct_live_appr_db_consumed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_live_remediation_approvals');
    }
};
