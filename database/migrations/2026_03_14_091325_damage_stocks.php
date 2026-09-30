<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference_no')->unique();
            $table->integer('warehouse_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->date('damaged_at');
            $table->text('note')->nullable();
            $table->string('document')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_damage_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('damage_stock_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('variant_id')->unsigned()->nullable();
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('damage_stock_id')->references('id')->on('damage_stocks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_damage_stocks');
        Schema::dropIfExists('damage_stocks');
    }
};
