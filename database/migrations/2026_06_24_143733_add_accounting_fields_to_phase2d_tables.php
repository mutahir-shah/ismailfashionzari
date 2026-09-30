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
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_account_id')->nullable()->after('is_active');
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_account_id')->nullable()->after('is_active');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('document');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('note');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('accounting_account_id');
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->dropColumn('accounting_account_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });
    }
};
