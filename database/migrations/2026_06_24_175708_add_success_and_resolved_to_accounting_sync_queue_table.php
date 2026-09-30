<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_sync_queue', function (Blueprint $table) {
            $table->timestamp('last_success_at')->nullable()->after('last_attempt_at');
            $table->timestamp('resolved_at')->nullable()->after('last_success_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_sync_queue', function (Blueprint $table) {
            $table->dropColumn(['last_success_at', 'resolved_at']);
        });
    }
};
