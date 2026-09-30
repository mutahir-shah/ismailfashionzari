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
        Schema::create('india_gst_sale_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sale_id');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            $table->foreignId('gst_registration_id')->constrained('india_gst_registrations')->onDelete('restrict');
            
            // Document identity
            $table->string('invoice_reference')->nullable()->index();
            $table->date('invoice_date')->nullable()->index();
            $table->date('transaction_date')->nullable();
            $table->string('financial_year')->nullable();

            // Supplier snapshot
            $table->string('supplier_legal_name')->nullable();
            $table->string('supplier_trade_name')->nullable();
            $table->string('supplier_gstin')->nullable();
            $table->text('supplier_address')->nullable();
            $table->string('supplier_state_code', 2)->nullable();
            $table->string('supplier_state_name')->nullable();

            // Customer snapshot
            $table->unsignedInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_legal_name')->nullable();
            $table->string('customer_trade_name')->nullable();
            $table->string('customer_gstin')->nullable()->index();
            $table->string('customer_registration_type')->nullable();
            $table->text('customer_billing_address')->nullable();
            $table->text('customer_shipping_address')->nullable();
            $table->string('customer_state_code', 2)->nullable();
            $table->string('customer_state_name')->nullable();

            // Place of supply
            $table->string('place_of_supply_state_code', 2)->nullable()->index();
            $table->string('place_of_supply_state_name')->nullable();
            $table->string('supply_rule_code')->nullable();
            $table->string('jurisdiction_code')->nullable();
            $table->boolean('is_inter_state')->default(false);
            
            // Manual override
            $table->boolean('manual_pos_override_used')->default(false);
            $table->string('manual_pos_override_reason')->nullable();
            $table->unsignedInteger('manual_pos_override_user_id')->nullable();
            $table->foreign('manual_pos_override_user_id')->references('id')->on('users')->onDelete('set null');

            // Currency
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 15, 6)->default(1);

            // Totals (using standard 15,4 or 22,4 for amounts, picking 22,4 as standard safe)
            $table->decimal('total_gross_value', 22, 4)->default(0);
            $table->decimal('total_line_discount', 22, 4)->default(0);
            $table->decimal('total_invoice_discount', 22, 4)->default(0);
            $table->decimal('total_taxable_charges', 22, 4)->default(0);
            $table->decimal('total_non_taxable_charges', 22, 4)->default(0);
            $table->decimal('total_taxable_value', 22, 4)->default(0);
            
            $table->decimal('total_cgst', 22, 4)->default(0);
            $table->decimal('total_sgst', 22, 4)->default(0);
            $table->decimal('total_utgst', 22, 4)->default(0);
            $table->decimal('total_igst', 22, 4)->default(0);
            $table->decimal('total_cess', 22, 4)->default(0);
            
            $table->decimal('rounding_adjustment', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);

            // State
            $table->integer('snapshot_version')->default(1);
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
            
            // Unique index for 1 snapshot per sale
            $table->unique('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('india_gst_sale_snapshots');
    }
};
