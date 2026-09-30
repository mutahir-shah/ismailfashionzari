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
        Schema::table('couriers', function (Blueprint $table) {
            if (!Schema::hasColumn('couriers', 'api_key')) {
                $table->string('api_key')->nullable()->after('name');
            }
            if (!Schema::hasColumn('couriers', 'secret_key')) {
                $table->string('secret_key')->nullable()->after('api_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'secret_key']);
        });
    }
};
