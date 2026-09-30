<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'status')) {
                $table->string('status', 50)->default('draft')->after('note');
            }

            Schema::table('payrolls', function (Blueprint $table) {
                $table->string('month')->after('created_at')->nullable();
            });

            if (!Schema::hasColumn('payrolls', 'amount_array')) {
                $table->json('amount_array')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount_array','month']);
        });
    }
};
