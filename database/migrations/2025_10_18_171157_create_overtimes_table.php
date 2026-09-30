<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOvertimesTable extends Migration
{
    public function up()
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED
            $table->unsignedBigInteger('employee_id'); // match employees.id
            $table->date('date');
            $table->decimal('hours', 5, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->timestamps();

            // $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

    }

    public function down()
    {
        Schema::dropIfExists('overtimes');
    }
}
