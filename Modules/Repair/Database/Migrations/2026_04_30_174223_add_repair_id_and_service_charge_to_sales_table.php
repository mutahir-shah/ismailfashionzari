<?php
// database/migrations/xxxx_add_repair_columns_to_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'service_id')) {
                $table->unsignedBigInteger('service_id')
                    ->nullable()
                    ->after('sale_note')
                    ->index();
            }
            if (!Schema::hasColumn('sales', 'repair_id')) {
                $table->unsignedBigInteger('repair_id')
                      ->nullable()
                      ->after('service_id')
                      ->index();
            }
            if (!Schema::hasColumn('sales', 'service_charge')) {
                $table->decimal('service_charge', 15, 4)
                    ->default(0)
                    ->after('shipping_cost');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'service_job_id')) {
                $table->unsignedBigInteger('service_job_id')
                      ->nullable()
                      ->after('purchase_return_id')
                      ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['service_id', 'repair_id']);
            $table->dropColumn(['service_id', 'repair_id', 'service_charge']);
        });
    }
};
