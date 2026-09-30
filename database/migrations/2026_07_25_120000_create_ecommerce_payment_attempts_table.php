<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_payment_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_id')->unique();
            $table->unsignedInteger('sale_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('provider', 50);
            $table->decimal('expected_amount', 20, 4);
            $table->unsignedBigInteger('currency_id');
            $table->string('currency_code', 10);
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('provider_amount', 20, 4);
            $table->unsignedTinyInteger('minor_unit')->default(2);
            $table->string('provider_reference')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('state', 20)->default('pending');
            $table->string('idempotency_key', 100)->unique();
            $table->json('verification_data')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'provider', 'state']);
            $table->unique(['provider', 'provider_transaction_id'], 'ecom_attempt_provider_txn_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_payment_attempts');
    }
};
