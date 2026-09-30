<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('returns', 'account_id')) {
            DB::statement('ALTER TABLE `returns` MODIFY `account_id` INT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('returns', 'account_id')) {
            DB::statement('ALTER TABLE `returns` MODIFY `account_id` INT NOT NULL');
        }
    }
};
