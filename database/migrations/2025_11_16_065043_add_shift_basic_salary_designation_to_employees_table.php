<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            if (!Schema::hasColumn('employees', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('employees', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('is_sale_agent');
            }

            if (!Schema::hasColumn('employees', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->default(0)->after('shift_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            if (Schema::hasColumn('employees', 'designation_id')) {
                $table->dropColumn('designation_id');
            }

            if (Schema::hasColumn('employees', 'shift_id')) {
                $table->dropColumn('shift_id');
            }

            if (Schema::hasColumn('employees', 'basic_salary')) {
                $table->dropColumn('basic_salary');
            }

        });
    }
};
