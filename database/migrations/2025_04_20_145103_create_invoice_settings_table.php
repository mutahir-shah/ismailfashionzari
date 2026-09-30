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
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('template_name');
            $table->string('invoice_name')->nullable();
            $table->string('invoice_logo')->nullable();
            $table->string('file_type')->nullable();
            $table->string('prefix')->nullable();
            $table->string('number_of_digit')->nullable();
            $table->string('numbering_type')->nullable();
            $table->unsignedBigInteger('start_number')->nullable();
            $table->unsignedBigInteger('last_invoice_number')->nullable();
            $table->string('header_text')->nullable();
            $table->string('header_title')->nullable();
            $table->string('footer_text')->nullable();
            $table->string('footer_title')->nullable();
            $table->string('preview_invoice')->nullable();
            $table->string('size')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('logo_height')->nullable();
            $table->string('logo_width')->nullable();
            $table->boolean('is_default')->default(0)->comment('0=not defoult, 1= defoult');
            $table->boolean('status')->default(0);
            $table->string('invoice_date_format')->default('Y-M-d h:m:s');
            // $table->boolean('show_customer_details')->nullable();
            // $table->boolean('show_shipping_details')->nullable();
            // $table->boolean('show_payment_info')->nullable();
            // $table->boolean('show_discount')->nullable();
            // $table->boolean('show_tax_info')->nullable();
            // $table->boolean('show_description')->nullable();
            // $table->boolean('show_billing_info')->nullable();
            // $table->boolean('show_in_words')->nullable();
            // $table->boolean('show_barcode')->nullable();
            // $table->boolean('show_qr_code')->nullable();
            $table->json('show_column')->nullable();
            $table->json('extra')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
