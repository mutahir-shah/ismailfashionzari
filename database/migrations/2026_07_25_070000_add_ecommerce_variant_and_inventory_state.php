<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('variant_id')->index();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('ecommerce_inventory_deducted_at')->nullable()->after('sale_type');
            $table->timestamp('ecommerce_inventory_restored_at')->nullable()->after('ecommerce_inventory_deducted_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropColumn('product_variant_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'ecommerce_inventory_deducted_at',
                'ecommerce_inventory_restored_at',
            ]);
        });
    }
};
