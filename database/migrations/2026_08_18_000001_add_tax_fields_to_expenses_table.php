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
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'tax_id')) {
                $table->unsignedInteger('tax_id')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('expenses', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('expenses', 'tax_rate')) {
                $table->double('tax_rate', 8, 2)->default(0)->nullable()->after('tax_name');
            }
            if (!Schema::hasColumn('expenses', 'tax')) {
                $table->double('tax', 8, 2)->default(0)->nullable()->after('tax_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'tax')) {
                $table->dropColumn('tax');
            }
            if (Schema::hasColumn('expenses', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
            if (Schema::hasColumn('expenses', 'tax_name')) {
                $table->dropColumn('tax_name');
            }
            if (Schema::hasColumn('expenses', 'tax_id')) {
                $table->dropColumn('tax_id');
            }
        });
    }
};
