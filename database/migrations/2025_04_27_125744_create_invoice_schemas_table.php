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
        Schema::create('invoice_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->nullable();
            $table->unsignedInteger('number_of_digit')->nullable();
            $table->unsignedBigInteger('start_number')->nullable();
            $table->unsignedBigInteger('last_invoice_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_schemas');
    }
};
