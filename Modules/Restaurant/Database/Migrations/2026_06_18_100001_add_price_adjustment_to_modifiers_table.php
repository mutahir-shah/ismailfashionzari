<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceAdjustmentToModifiersTable extends Migration
{
    public function up()
    {
        Schema::table('modifiers', function (Blueprint $table) {
            // Default price added to the modifier itself — used when no per-product override exists.
            $table->decimal('price_adjustment', 10, 4)->default(0)->after('sort_order');
        });
    }

    public function down()
    {
        Schema::table('modifiers', function (Blueprint $table) {
            $table->dropColumn('price_adjustment');
        });
    }
}
