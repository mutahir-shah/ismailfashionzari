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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('social_channel')->nullable()->after('sale_type');
            $table->string('social_campaign')->nullable()->after('social_channel');
            $table->unsignedBigInteger('social_click_id')->nullable()->after('social_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['social_channel', 'social_campaign', 'social_click_id']);
        });
    }
};
