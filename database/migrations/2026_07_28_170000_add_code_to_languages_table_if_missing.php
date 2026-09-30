<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('languages') || Schema::hasColumn('languages', 'code')) {
            return;
        }

        Schema::table('languages', function (Blueprint $table) {
            $table->string('code')->nullable()->after('language');
        });

        DB::table('languages')
            ->whereNull('code')
            ->update(['code' => DB::raw('language')]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('languages') || !Schema::hasColumn('languages', 'code')) {
            return;
        }

        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
