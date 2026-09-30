<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayTermToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->integer('pay_term_no')->nullable()->after('paid_amount');
            $table->string('pay_term_period')->nullable()->after('pay_term_no');
            $table->date('due_date')->nullable()->after('pay_term_period');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['pay_term_no', 'pay_term_period', 'due_date']);
        });
    }
}
