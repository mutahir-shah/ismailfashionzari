<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();

            // Employee reference (foreign key to employees table)
            // $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id'); // match employees.id

            // Leave type reference (foreign key to leave_types table)
            $table->unsignedBigInteger('leave_types'); // match
            // $table->foreignId('leave_types')->constrained('leave_types')->onDelete('cascade');

            // Leave start and end dates
            $table->date('start_date');
            $table->date('end_date');

            // Total number of leave days
            $table->integer('days');

            // Leave status (Pending, Approved, Rejected)
            $table->string('status')->default('Pending');

            // Approver reference (nullable)
            $table->unsignedBigInteger('approver_id')->nullable();

            $table->timestamps();

            // Optional: Add approver foreign key if linked to employees table
            // $table->foreign('approver_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('leaves');
    }
}
