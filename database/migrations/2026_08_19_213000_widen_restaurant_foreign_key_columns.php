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
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'kitchen_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('kitchen_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('kitchens') && Schema::hasColumn('kitchens', 'user_id')) {
            Schema::table('kitchens', function (Blueprint $table) {
                $table->unsignedInteger('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'kitchen_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->tinyInteger('kitchen_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('kitchens') && Schema::hasColumn('kitchens', 'user_id')) {
            Schema::table('kitchens', function (Blueprint $table) {
                $table->tinyInteger('user_id')->nullable()->change();
            });
        }
    }
};
