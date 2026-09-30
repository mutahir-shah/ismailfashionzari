<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_product_settings', function (Blueprint $table) {
            $table->unique('product_id', 'social_product_settings_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('social_product_settings', function (Blueprint $table) {
            $table->dropUnique('social_product_settings_product_unique');
        });
    }
};
