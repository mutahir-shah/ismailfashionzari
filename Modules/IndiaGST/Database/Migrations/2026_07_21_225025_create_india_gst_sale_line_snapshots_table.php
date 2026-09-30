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
        Schema::create('india_gst_sale_line_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('india_gst_sale_snapshot_id')->constrained('india_gst_sale_snapshots')->onDelete('cascade');
            $table->unsignedInteger('product_sale_id');
            $table->foreign('product_sale_id')->references('id')->on('product_sales')->onDelete('restrict');
            
            // Product info
            $table->unsignedInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->unsignedInteger('variant_id')->nullable();
            $table->foreign('variant_id')->references('id')->on('variants')->onDelete('set null');
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->string('variant_name')->nullable();
            
            // Classification
            $table->string('classification')->nullable();
            $table->string('hsn_sac_code')->nullable();
            $table->string('uqc_code')->nullable();
            
            $table->unsignedInteger('sale_unit_id')->nullable();
            $table->foreign('sale_unit_id')->references('id')->on('units')->onDelete('set null');
            $table->string('sale_unit_name')->nullable();
            
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->boolean('tax_inclusive')->default(false);
            
            // Amounts
            $table->decimal('gross_value', 22, 4)->default(0);
            $table->decimal('line_discount', 22, 4)->default(0);
            $table->decimal('allocated_invoice_discount', 22, 4)->default(0);
            $table->decimal('taxable_charges', 22, 4)->default(0);
            $table->decimal('non_taxable_charges', 22, 4)->default(0);
            $table->decimal('taxable_value', 22, 4)->default(0);
            
            // Tax Profile
            $table->string('taxability_type')->nullable(); // taxable, nil-rated, exempt, non-gst
            $table->foreignId('gst_tax_profile_id')->nullable()->constrained('gst_tax_profiles')->onDelete('set null');
            $table->string('gst_tax_profile_code')->nullable();
            $table->integer('gst_tax_profile_version')->nullable();
            $table->string('profile_resolution_source')->nullable();
            
            // Components
            $table->decimal('cgst_rate', 8, 4)->default(0);
            $table->decimal('cgst_amount', 22, 4)->default(0);
            $table->decimal('sgst_rate', 8, 4)->default(0);
            $table->decimal('sgst_amount', 22, 4)->default(0);
            $table->decimal('utgst_rate', 8, 4)->default(0);
            $table->decimal('utgst_amount', 22, 4)->default(0);
            $table->decimal('igst_rate', 8, 4)->default(0);
            $table->decimal('igst_amount', 22, 4)->default(0);
            
            // Cess
            $table->string('cess_calculation_type')->nullable();
            $table->decimal('cess_rate', 8, 4)->default(0);
            $table->decimal('cess_amount_per_unit', 22, 4)->default(0);
            $table->decimal('cess_amount', 22, 4)->default(0);
            
            // Totals
            $table->decimal('rounding_adjustment', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->string('calculation_rule_code')->nullable();

            $table->timestamps();
            
            // Unique index for idempotency
            $table->unique('product_sale_id', 'idx_unique_product_sale_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_gst_sale_line_snapshots');
    }
};
