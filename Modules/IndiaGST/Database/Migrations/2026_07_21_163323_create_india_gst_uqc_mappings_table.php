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
        Schema::create('india_gst_uqc_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('salepro_unit_id')->unique();
            $table->string('uqc_code');
            $table->timestamps();

            $table->foreign('salepro_unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_gst_uqc_mappings');
    }
};
