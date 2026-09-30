<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::create('employee_transactions', function (Blueprint $table) {
    //         $table->id();
    //         $table->unsignedBigInteger('employee_id');
    //         $table->date('date')->nullable();
    //         $table->decimal('amount', 15, 2);
    //         $table->enum('type', ['credit', 'debit'])->default('credit');
    //         $table->text('description')->nullable();
    //         $table->unsignedBigInteger('created_by')->nullable();
    //         $table->timestamps();
    //         $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
    //         $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::dropIfExists('employee_transactions');
    // }
};
