<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('warehouse_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();      // employee
            $table->integer('created_by')->unsigned()->nullable();   // who created
            $table->string('status')->default('Booked');             // Booked, Waiting, Completed, Cancelled
            $table->integer('product_id')->unsigned()->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
