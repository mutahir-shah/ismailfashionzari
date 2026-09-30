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
        Schema::table('fresh_business_reset_manifests', function (Blueprint $table) {
            $table->string('status')->default('success')->after('reset_type');
            $table->text('error_message')->nullable()->after('status');
            $table->json('affected_tables')->nullable()->change();
            $table->json('before_counts')->nullable()->change();
            $table->json('after_counts')->nullable()->change();
            $table->json('balances_before')->nullable()->change();
            $table->json('balances_after')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fresh_business_reset_manifests', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
            // Reverting nullable changes might fail depending on existing rows, so we don't strictly revert the nullable change.
        });
    }
};
