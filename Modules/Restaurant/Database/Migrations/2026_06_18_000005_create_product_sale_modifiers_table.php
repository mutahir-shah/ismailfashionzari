<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductSaleModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores modifier selections per order line item.
     * All name/price fields are snapshots — historical orders remain
     * accurate even when modifier definitions change later.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sale_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_sale_id');

            // References (for queries/joins — NOT relied on for display)
            $table->unsignedBigInteger('modifier_group_id');
            $table->unsignedBigInteger('modifier_id');

            // Snapshots — used for display, invoice, KDS, reports
            // These do NOT change if modifier definitions are later edited
            $table->string('modifier_group_name')
                  ->comment('Snapshot of modifier_groups.name at time of sale');
            $table->string('modifier_name')
                  ->comment('Snapshot of modifiers.name at time of sale');
            $table->decimal('price_adjustment', 25, 4)->default(0)
                  ->comment('Snapshot of price at time of sale');

            // Ingredient snapshot — for inventory audit trail
            // Allows reconstruction of what was deducted per order
            $table->string('product_list', 500)->nullable()
                  ->comment('Snapshot of ingredient CSV at time of sale');
            $table->string('qty_list', 500)->nullable()
                  ->comment('Snapshot of ingredient qty CSV at time of sale');

            // Kitchen routing — NULL = inherit from parent product_sale's product.kitchen_id
            $table->unsignedBigInteger('kitchen_id')->nullable()
                  ->comment('Override kitchen for this modifier. NULL = use parent product kitchen');

            $table->timestamps();

            $table->foreign('product_sale_id')
                  ->references('id')
                  ->on('product_sales')
                  ->onDelete('cascade');

            // Soft FK references — no cascade (snapshot values remain even if group/modifier deleted)
            $table->index('product_sale_id');
            $table->index(['modifier_group_id', 'modifier_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_sale_modifiers');
    }
}
