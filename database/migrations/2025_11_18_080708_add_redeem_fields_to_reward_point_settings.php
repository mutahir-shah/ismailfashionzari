<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('reward_point_settings', function (Blueprint $table) {
        $table->decimal('redeem_amount_per_unit_rp', 10, 2)->nullable();
        $table->decimal('min_order_total_for_redeem', 10, 2)->nullable();
        $table->integer('min_redeem_point')->nullable();
        $table->integer('max_redeem_point')->nullable(); 
    });
}

public function down()
{
    Schema::table('reward_point_settings', function (Blueprint $table) {
        $table->dropColumn([
            'redeem_amount_per_unit_rp',
            'min_order_total_for_redeem',
            'min_redeem_point',
            'max_redeem_point'
        ]);
    });
}

};
