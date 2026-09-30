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
            $table->text('wa_template_order_confirmation')->nullable()->after('share_template');
            $table->text('wa_template_payment_request')->nullable()->after('wa_template_order_confirmation');
            $table->text('wa_template_delivery_update')->nullable()->after('wa_template_payment_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_commerce_settings', function (Blueprint $table) {
            $table->dropColumn([
                'wa_template_order_confirmation',
                'wa_template_payment_request',
                'wa_template_delivery_update'
            ]);
        });
    }
};
