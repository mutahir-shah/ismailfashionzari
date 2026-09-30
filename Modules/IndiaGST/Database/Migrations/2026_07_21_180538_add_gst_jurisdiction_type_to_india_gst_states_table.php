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
        Schema::table('india_gst_states', function (Blueprint $table) {
            $table->string('gst_jurisdiction_type')->default('sgst'); // sgst or utgst
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('india_gst_states', function (Blueprint $table) {
            $table->dropColumn('gst_jurisdiction_type');
        });
    }
};
