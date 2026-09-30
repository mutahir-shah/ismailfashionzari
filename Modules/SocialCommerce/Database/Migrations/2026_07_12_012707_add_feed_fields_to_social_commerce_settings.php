<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('social_commerce_settings', function (Blueprint $table) {
            $table->string('feed_token')->nullable()->after('share_template');
            $table->timestamp('feed_last_generated')->nullable()->after('feed_token');
        });

        // Generate token for existing setting if it exists
        $setting = \Modules\SocialCommerce\Entities\SocialCommerceSetting::first();
        if ($setting && !$setting->feed_token) {
            $setting->feed_token = Str::random(40);
            $setting->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_commerce_settings', function (Blueprint $table) {
            $table->dropColumn(['feed_token', 'feed_last_generated']);
        });
    }
};
