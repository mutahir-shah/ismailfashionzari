<?php

use App\Enums\DiscountPlanTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discount_plans', function (Blueprint $table) {
            $table->enum('type', DiscountPlanTypeEnum::toArray())
                ->default(DiscountPlanTypeEnum::LIMITED->value)
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_plans', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
