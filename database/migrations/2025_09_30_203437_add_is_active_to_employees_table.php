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
        Schema::table('employees', function (Blueprint $table) {
             $table->boolean('is_sale_agent')->default(false)->after('is_active');
            $table->decimal('sale_commission_percent')->nullable()->after('is_sale_agent');
            $table->json('sales_target')->nullable()->after('sale_commission_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
             $table->dropColumn(['is_sale_agent', 'sale_commission_percent', 'sales_target']);
        });
    }
};
