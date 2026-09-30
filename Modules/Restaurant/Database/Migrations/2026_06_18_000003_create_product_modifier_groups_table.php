<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Attaches modifier_groups to products.
     * Allows per-product overrides of the group's default min/max/required settings.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('modifier_group_id');
            $table->smallInteger('sort_order')->unsigned()->default(0);

            // Per-product overrides — NULL means "use the modifier_group default"
            $table->tinyInteger('min_selection_override')->unsigned()->nullable()
                  ->comment('NULL = use modifier_groups.min_selection');
            $table->tinyInteger('max_selection_override')->unsigned()->nullable()
                  ->comment('NULL = use modifier_groups.max_selection');
            $table->tinyInteger('is_required_override')->nullable()
                  ->comment('NULL = use modifier_groups.is_required');

            $table->timestamps();

            $table->unique(['product_id', 'modifier_group_id'], 'unique_product_group');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('modifier_group_id')
                  ->references('id')
                  ->on('modifier_groups')
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
        Schema::dropIfExists('product_modifier_groups');
    }
}
