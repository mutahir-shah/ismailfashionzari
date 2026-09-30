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
        Schema::create('india_gst_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('gstin', 15)->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->foreignId('state_id')->constrained('india_gst_states');
            $table->boolean('is_isd')->default(false);
            $table->boolean('is_ecommerce')->default(false);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_gst_registrations');
    }
};
