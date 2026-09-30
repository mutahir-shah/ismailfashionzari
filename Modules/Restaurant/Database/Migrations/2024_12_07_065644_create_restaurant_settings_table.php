<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRestaurantSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id(); 
            $table->string('site_title')->nullable(); 
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('store_phone')->nullable();
            $table->string('store_email')->nullable();
            $table->string('store_address')->nullable();
            $table->bigInteger('home_page')->nullable();
            $table->integer('warehouse_id');
            $table->integer('biller_id');
            $table->integer('is_rtl')->default(0);
            $table->string('contact_form_email')->nullable();
            $table->double('flat_rate_shipping')->nullable();
            $table->json('checkout_pages')->nullable();
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            $table->text('chat_code')->nullable();
            $table->text('analytics_code')->nullable();
            $table->text('fb_pixel_code')->nullable();
            $table->string('theme')->default('default');
            $table->string('theme_color')->default('#111');
            $table->string('theme_font')->default('Inter');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_settings');
    }
}
