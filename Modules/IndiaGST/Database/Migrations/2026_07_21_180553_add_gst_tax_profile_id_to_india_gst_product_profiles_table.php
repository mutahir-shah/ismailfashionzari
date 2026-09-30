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
        Schema::table('india_gst_product_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('gst_tax_profile_id')->nullable();
            // Don't set up foreign key directly if it breaks tenant isolation depending on setup, but it should be fine since both are tenant tables.
            $table->foreign('gst_tax_profile_id')->references('id')->on('gst_tax_profiles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('india_gst_product_profiles', function (Blueprint $table) {
            $table->dropForeign(['gst_tax_profile_id']);
            $table->dropColumn('gst_tax_profile_id');
        });
    }
};
