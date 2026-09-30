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
        Schema::table('social_commerce_settings', function (Blueprint $table) {
            $table->boolean('feed_pending_refresh')->default(false)->after('feed_last_generated');
            $table->timestamp('feed_last_failed')->nullable()->after('feed_pending_refresh');
            $table->text('feed_latest_error')->nullable()->after('feed_last_failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_commerce_settings', function (Blueprint $table) {
            $table->dropColumn(['feed_pending_refresh', 'feed_last_failed', 'feed_latest_error']);
        });
    }
};
