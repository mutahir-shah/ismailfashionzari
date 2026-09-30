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
        Schema::create('financial_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // e.g. balance_sheet, profit_loss
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->string('checksum', 64);
            $table->json('metadata'); // JSON serialization of the report content
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_report_snapshots');
    }
};
