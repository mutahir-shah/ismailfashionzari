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
        Schema::create('gst_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->index();
            $table->string('taxability_type')->default('Taxable'); // Taxable, Zero-rated, Nil-rated, Exempt, Non-GST
            $table->decimal('total_gst_rate', 8, 4)->default(0);
            $table->string('cess_calculation_type')->default('none'); // none, percentage, per_unit
            $table->decimal('cess_rate', 8, 4)->nullable();
            $table->decimal('cess_amount_per_unit', 15, 4)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_tax_profiles');
    }
};
