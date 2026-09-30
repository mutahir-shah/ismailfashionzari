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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('qrable_id');
            $table->string('qrable_type');

            $table->text('url');
            $table->string('path')->nullable();

            $table->string('code')->unique();

            $table->json('meta')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['qrable_id', 'qrable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
