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
        if (!Schema::hasColumn('product_sale_modifiers', 'qty')) {
            Schema::table('product_sale_modifiers', function (Blueprint $table) {
                $table->integer('qty')->default(1)->after('modifier_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_sale_modifiers', 'qty')) {
            Schema::table('product_sale_modifiers', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }
};
