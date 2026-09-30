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
        Schema::create('gst_generic_tax_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tax_id')->index(); // FK to core taxes
            $table->foreignId('gst_tax_profile_id')->constrained('gst_tax_profiles')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_generic_tax_mappings');
    }
};
