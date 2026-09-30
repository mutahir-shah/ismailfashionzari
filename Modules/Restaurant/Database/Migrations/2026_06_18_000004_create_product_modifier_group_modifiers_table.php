<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGroupModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * 3-way pivot: product + modifier_group + modifier.
     *
     * Stores the per-product price and ingredient list for each modifier.
     * This is the critical table that allows "Pizza Large" and "Coffee Large"
     * to have different prices and different inventory impacts.
     *
     * Ingredient deduction uses the same CSV pattern as SaleController combo
     * deduction (lines 1115-1197) and RecipeController — zero new deduction
     * infrastructure required.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_group_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('modifier_group_id');
            $table->unsignedBigInteger('modifier_id');

            // Per-product price for this modifier (e.g. Pizza Large = +2.50, Coffee Large = +0.50)
            $table->decimal('price_adjustment', 25, 4)->default(0);

            // Ingredient deduction — same CSV pattern as products.product_list / qty_list
            // Used by SaleController combo deduction loop (identical code path)
            $table->string('product_list', 500)->nullable()
                  ->comment('CSV of ingredient product IDs. NULL = no inventory impact');
            $table->string('qty_list', 500)->nullable()
                  ->comment('CSV of ingredient quantities matching product_list');
            $table->string('variant_list', 500)->nullable()
                  ->comment('CSV of variant IDs (0 or empty = no variant)');
            $table->string('wastage_percent', 500)->nullable()
                  ->comment('CSV of wastage % matching product_list. Matches Manufacturing pattern');

            $table->tinyInteger('is_active')->default(1);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->unique(
                ['product_id', 'modifier_group_id', 'modifier_id'],
                'unique_product_modifier'
            );

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('modifier_group_id')
                  ->references('id')
                  ->on('modifier_groups')
                  ->onDelete('cascade');

            $table->foreign('modifier_id')
                  ->references('id')
                  ->on('modifiers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_group_modifiers');
    }
}
