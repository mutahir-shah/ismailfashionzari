<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_product_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->boolean('is_published')->default(0);
            $table->string('social_title')->nullable();
            $table->text('social_description')->nullable();
            $table->string('social_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_product_settings');
    }
};
