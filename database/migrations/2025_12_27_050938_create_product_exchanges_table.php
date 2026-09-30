<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductExchangesTable extends Migration
{
    public function up()
    {
        Schema::create('product_exchanges', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('exchange_id');   // sale_exchanges.id
            $table->integer('product_id');

            $table->double('qty');
            $table->integer('sale_unit_id')->nullable();

            $table->double('net_unit_price');
            $table->double('discount')->default(0);

            $table->double('tax_rate')->default(0);
            $table->double('tax')->default(0);

            $table->double('total');

            $table->enum('type', ['new', 'returned'])->default('new');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_exchanges');
    }
}
