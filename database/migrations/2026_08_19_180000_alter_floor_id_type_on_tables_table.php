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
        if (Schema::hasTable('tables') && Schema::hasColumn('tables', 'floor_id')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->integer('floor_id')->default(1)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tables') && Schema::hasColumn('tables', 'floor_id')) {
            Schema::table('tables', function (Blueprint $table) {
                $table->tinyInteger('floor_id')->default(1)->change();
            });
        }
    }
};
