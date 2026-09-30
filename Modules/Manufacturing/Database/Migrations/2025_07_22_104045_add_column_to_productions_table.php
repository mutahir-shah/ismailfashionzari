<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToProductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('production_units_ids')->nullable();
            $table->string('wastage_percent')->nullable();
            $table->string('product_list')->nullable();
            $table->string('product_id')->nullable();
            $table->string('qty_list')->nullable();
            $table->string('price_list')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::table('productions', function (Blueprint $table) {
        $table->dropColumn([
            'production_units_ids', // ✅ ঠিক করা
            'wastage_percent',
            'product_list',
            'qty_list',
            'price_list',
            'product_id'
        ]);
    });
}

}
