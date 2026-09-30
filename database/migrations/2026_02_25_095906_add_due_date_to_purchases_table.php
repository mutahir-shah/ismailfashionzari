<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('due_date')->nullable();
            $table->integer('pay_term_no')->nullable()->after('due_date');
            $table->string('pay_term_period')->nullable()->after('pay_term_no');
        });
    }

    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'pay_term_no', 'pay_term_period']);
        });
    }
};
