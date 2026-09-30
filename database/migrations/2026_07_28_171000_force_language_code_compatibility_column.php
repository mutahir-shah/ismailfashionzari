<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->hasLanguagesTable() || $this->hasCodeColumn()) {
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
        if (!$this->hasLanguagesTable() || !$this->hasCodeColumn()) {
            return;
        }

        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    private function hasLanguagesTable(): bool
    {
        return count(DB::select("SHOW TABLES LIKE 'languages'")) > 0;
    }

    private function hasCodeColumn(): bool
    {
        return count(DB::select("SHOW COLUMNS FROM languages LIKE 'code'")) > 0;
    }
};
