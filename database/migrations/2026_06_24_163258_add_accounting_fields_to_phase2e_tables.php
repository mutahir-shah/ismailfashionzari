<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('payment_note');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->nullable()->after('amount');
            $table->string('accounting_status')->default('pending')->after('note');
        });

        Schema::table('money_transfers', function (Blueprint $table) {
            $table->string('accounting_status')->default('pending')->after('amount');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source_subtype')->nullable()->after('source_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'accounting_status']);
        });

        Schema::table('money_transfers', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('source_subtype');
        });
    }
};
