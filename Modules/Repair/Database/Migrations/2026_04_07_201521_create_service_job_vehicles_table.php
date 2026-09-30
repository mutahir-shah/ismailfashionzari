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
        Schema::create('service_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();

            $table->string('vehicle_type');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();

            $table->string('registration_no')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('chassis_no')->nullable();

            $table->integer('mileage')->nullable();
            $table->string('fuel_level')->nullable();

            $table->text('condition_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_job_vehicles');
    }
};
