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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('favicon')->nullable()->after('site_logo');
            $table->text('font_css')->nullable();
            $table->longText('auth_css')->nullable();
            $table->longText('pos_css')->nullable();
            $table->longText('custom_css')->nullable();
            $table->integer('disable_signup')->default(0);
            $table->integer('disable_forgot_password')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            //
        });
    }
};
