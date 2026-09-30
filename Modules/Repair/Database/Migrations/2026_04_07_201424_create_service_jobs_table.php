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
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();

            $table->unsignedBigInteger('customer_id');
            $table->enum('service_type', ['device', 'vehicle']);

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('note')->nullable();

            $table->enum('status', [
                'pending',
                'diagnosed',
                'in_progress',
                'completed',
                'delivered',
                'cancelled'
            ])->default('pending');

            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');

            $table->date('expected_delivery_date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->decimal('service_charge', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('due_amount', 15, 4)->default(0);

            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_jobs');
    }
};
