<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sale_exchanges', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('sale_id'); // মূল sale
            $table->string('reference_no')->unique();

            $table->integer('customer_id');
            $table->integer('user_id');
            $table->integer('warehouse_id');
            $table->integer('biller_id');

            $table->integer('item');
            $table->double('total_qty');

            $table->double('total_discount')->default(0);
            $table->double('total_tax')->default(0);
            $table->double('amount');

            $table->enum('payment_type', ['pay', 'receive'])->nullable();


            $table->double('order_tax_rate')->nullable();
            $table->double('order_tax')->nullable();

            $table->double('grand_total');

            $table->string('document')->nullable();
            $table->text('exchange_note')->nullable();
            $table->text('staff_note')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_exchanges');
    }
};
