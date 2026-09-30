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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event')->unique(); // 'sale_created', 'low_stock', 'purchase_created', etc.
            $table->boolean('notify_in_app')->default(true);
            $table->boolean('notify_whatsapp')->default(false);
            $table->boolean('notify_sms')->default(false); // 👈 Added SMS Channel toggle
            $table->text('whatsapp_message')->nullable();
            $table->text('sms_message')->nullable();       // 👈 Added SMS Template text area field
            $table->boolean('notify_mail')->default(false); // 👈 Email toggle
            $table->text('mail_message')->nullable();       // 👈 Email HTML/Text Template
            $table->string('recipients')->default('admin'); // 'admin', 'customer', 'both'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
