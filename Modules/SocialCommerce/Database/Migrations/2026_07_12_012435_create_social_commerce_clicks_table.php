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
        Schema::create('social_commerce_clicks', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id')->unsigned();
            $table->string('source')->nullable();
            $table->string('campaign')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('clicked_at')->useCurrent();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_commerce_clicks');
    }
};
