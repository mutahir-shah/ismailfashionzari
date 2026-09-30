<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductSalesForRestaurantModifiers extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds kitchen_status for item-level KDS tracking.
     * NOTE: topping_id is intentionally NOT dropped here.
     * It will be dropped in a separate cleanup migration once the
     * SaleController, pos.blade.php, and KDS views have been updated
     * to use the new product_sale_modifiers system.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->tinyInteger('kitchen_status')->unsigned()->default(0)->after('is_packing')
                  ->comment('0=pending, 1=preparing, 2=ready, 3=served. Item-level KDS status.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_sales', function (Blueprint $table) {
            if (Schema::hasColumn('product_sales', 'kitchen_status')) {
                $table->dropColumn('kitchen_status');
            }
        });
    }
}
