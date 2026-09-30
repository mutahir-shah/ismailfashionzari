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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('wa_number')->nullable()->after('phone_number');
            $table->double('opening_balance')->default(0)->after('country');
            $table->double('credit_limit')->nullable()->after('opening_balance');
            $table->integer('pay_term_no')->nullable()->after('deposit');
            $table->string('pay_term_period')->nullable()->after('pay_term_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
