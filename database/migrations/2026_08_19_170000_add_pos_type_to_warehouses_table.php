<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('warehouses')) {
            Schema::table('warehouses', function (Blueprint $table) {
                if (!Schema::hasColumn('warehouses', 'pos_type')) {
                    $table->string('pos_type', 30)->default('regular')->after('address');
                }
            });

            // If old unreleased is_restaurant column exists, migrate values and drop it
            if (Schema::hasColumn('warehouses', 'is_restaurant')) {
                DB::table('warehouses')->where('is_restaurant', 1)->update(['pos_type' => 'restaurant']);
                DB::table('warehouses')->where('is_restaurant', 0)->orWhereNull('is_restaurant')->update(['pos_type' => 'regular']);

                Schema::table('warehouses', function (Blueprint $table) {
                    $table->dropColumn('is_restaurant');
                });
            }
        }

        // Add pos_workflow to sales table to persist transaction workflow
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'pos_workflow')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('pos_workflow', 30)->nullable()->after('sale_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'pos_type')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('pos_type');
            });
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'pos_workflow')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('pos_workflow');
            });
        }
    }
};
