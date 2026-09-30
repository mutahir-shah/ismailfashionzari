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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Example: Casual, Sick, Earned
            $table->integer('annual_quota')->default(0); // Total number of leave days allowed per year
            $table->boolean('encashable')->default(false); // Whether the leave can be converted to cash
            $table->integer('carry_forward_limit')->default(0); // Maximum number of days that can be carried forward to the next year
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
