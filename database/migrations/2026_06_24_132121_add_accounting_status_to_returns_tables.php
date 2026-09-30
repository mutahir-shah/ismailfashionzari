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
        Schema::table('returns', function (Blueprint $table) {
            $table->enum('accounting_status', ['pending', 'posted', 'failed', 'reversed'])->default('pending')->after('grand_total');
        });

        Schema::table('return_purchases', function (Blueprint $table) {
            $table->enum('accounting_status', ['pending', 'posted', 'failed', 'reversed'])->default('pending')->after('grand_total');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });

        Schema::table('return_purchases', function (Blueprint $table) {
            $table->dropColumn('accounting_status');
        });
    }
};
