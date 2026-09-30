<?php

namespace Modules\IndiaGST\Services\Calculation;

use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Customer;
use Modules\IndiaGST\Entities\IndiaGstSaleSnapshot;
use Modules\IndiaGST\Entities\IndiaGstSaleLineSnapshot;
use Modules\IndiaGST\DTOs\IndiaGstSaleCalculationResult;
use Modules\IndiaGST\Entities\IndiaGstRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndiaGstSaleSnapshotPersistenceService
{
    public function persist(
        Sale $sale, 
        IndiaGstSaleCalculationResult $calcResult, 
        IndiaGstRegistration $registration, 
        array $saleData
    ): void {
        
        $now = Carbon::now();
        $isDraft = (isset($sale->sale_status) && $sale->sale_status == 3);

        // Check if an existing snapshot exists
        $existingSnapshot = IndiaGstSaleSnapshot::where('sale_id', $sale->id)->first();
        if ($existingSnapshot) {
            // Snapshot already exists. The finalize sale flow is idempotent, so we simply return.
            // If values actually differ, we could throw an exception, but returning idempotently is safer for retries.
            return;
        }

        // If it's a draft, do not persist the immutable snapshot according to requirements
        if ($isDraft) {
            return;
        }

        $customer = null;
        if ($sale->customer_id) {
            $customer = Customer::find($sale->customer_id);
        }

        $snapshot = IndiaGstSaleSnapshot::create([
            'sale_id' => $sale->id,
            'gst_registration_id' => $registration->id,
            'invoice_reference' => $sale->reference_no,
            'invoice_date' => $sale->created_at ? $sale->created_at->toDateString() : $now->toDateString(),
            'transaction_date' => $sale->created_at ? $sale->created_at->toDateString() : $now->toDateString(),
            'financial_year' => $this->getFinancialYear($sale->created_at ?? $now),
            
            'supplier_legal_name' => $registration->legal_name,
            'supplier_trade_name' => $registration->trade_name,
            'supplier_gstin' => $registration->gstin,
            'supplier_address' => null, // Typically comes from warehouse or company settings
            'supplier_state_code' => $registration->state->gst_state_code,
            'supplier_state_name' => $registration->state->name,

            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name,
            'customer_legal_name' => $customer?->company_name, // Mapping SalePro company to legal name
            'customer_trade_name' => null,
            'customer_gstin' => $customer?->tax_no, // Or from IndiaGstCustomerProfile
            'customer_registration_type' => null,
            'customer_billing_address' => $customer?->address,
            'customer_shipping_address' => $customer?->address,
            
            // Assume billing state is extracted from input or profile
            'customer_state_code' => $saleData['billing_state_code'] ?? null,
            
            'place_of_supply_state_code' => $saleData['place_of_supply_state_code'] ?? null, // Need to grab this from first line posResult or passed down
            
            // Note: in a multi-line invoice, the POS rule might technically vary if there are services vs goods, but usually POS state is uniform for an invoice.
            // Let's grab the POS info from the first line result.
            'place_of_supply_state_name' => '', 
            'supply_rule_code' => '',
            'jurisdiction_code' => '',
            'is_inter_state' => false,

            'manual_pos_override_used' => !empty($saleData['place_of_supply_override']),
            'manual_pos_override_reason' => $saleData['override_reason'] ?? null,
            'manual_pos_override_user_id' => !empty($saleData['place_of_supply_override']) ? ($saleData['user_id'] ?? auth()->id()) : null,

            'currency_code' => 'INR',
            'exchange_rate' => $sale->exchange_rate ?? 1,

            'total_gross_value' => $calcResult->totalGrossValue,
            'total_line_discount' => $calcResult->totalLineDiscount,
            'total_invoice_discount' => $calcResult->totalInvoiceDiscount,
            'total_taxable_charges' => $calcResult->totalTaxableCharges,
            'total_non_taxable_charges' => $calcResult->totalNonTaxableCharges,
            'total_taxable_value' => $calcResult->totalTaxableValue,
            
            'total_cgst' => $calcResult->totalCgst,
            'total_sgst' => $calcResult->totalSgst,
            'total_utgst' => $calcResult->totalUtgst,
            'total_igst' => $calcResult->totalIgst,
            'total_cess' => $calcResult->totalCess,
            
            'rounding_adjustment' => $calcResult->roundingAdjustment,
            'grand_total' => $calcResult->grandTotal,
            
            'snapshot_version' => 1,
            'locked_at' => $now,
        ]);

        $productSales = Product_Sale::where('sale_id', $sale->id)->get()->keyBy('product_id');

        // We need to map our lineResults (keyed by index from the request) to the actual Product_Sale records inserted.
        // The arrays in SaleController are iterated in the same order as they were requested.
        // Assuming lineResults index matches the Product_Sale insertion sequence (if we sort by ID or keep track).
        // Since SalePro loops `foreach ($product_id as $i => $id)` to create `Product_Sale` records, we can match by product_id or just assume order if we fetch them ordered by ID.
        $productSalesList = Product_Sale::where('sale_id', $sale->id)->orderBy('id')->get();

        foreach ($calcResult->lineResults as $i => $lineRes) {
            $productSale = $productSalesList[$i] ?? null;
            if (!$productSale) continue;

            $input = $lineRes['input'];
            $posResult = $lineRes['posResult'];
            $jurisdiction = $lineRes['jurisdiction'];
            $result = $lineRes['result'];
            $productProfile = $lineRes['product_profile'];

            // Update Invoice POS fields from the first line
            if ($i === 0) {
                $snapshot->update([
                    'place_of_supply_state_code' => $posResult['state_code'],
                    'supply_rule_code' => $posResult['rule_code'],
                    'jurisdiction_code' => $jurisdiction['jurisdiction'],
                    'is_inter_state' => $jurisdiction['jurisdiction'] === 'inter_state',
                ]);
            }

            $comps = [];
            foreach ($result->components as $comp) {
                $comps[strtoupper($comp->component_code)] = $comp->amount;
            }

            IndiaGstSaleLineSnapshot::create([
                'india_gst_sale_snapshot_id' => $snapshot->id,
                'product_sale_id' => $productSale->id,
                
                'product_id' => $productSale->product_id,
                'variant_id' => $productSale->variant_id,
                'product_name' => $productSale->product?->name,
                'product_code' => $productSale->product?->code,
                
                'classification' => $productProfile->type,
                'hsn_sac_code' => $productProfile->hsn_sac_code,
                
                'quantity' => $input->quantity,
                'unit_price' => $input->unit_price,
                
                'gross_value' => ($input->unit_price * $input->quantity),
                'line_discount' => $input->line_discount,
                'allocated_invoice_discount' => $input->allocated_invoice_discount,
                'taxable_charges' => $input->taxable_additional_charges,
                'non_taxable_charges' => $input->non_taxable_additional_charges,
                'taxable_value' => $result->taxable_value,
                
                'taxability_type' => $result->taxability_type,
                'gst_tax_profile_id' => $result->tax_profile_id,
                'gst_tax_profile_code' => $result->tax_profile_code,
                'gst_tax_profile_version' => 1, // Fallback to 1 as it's an integer field, wait I'll just use 1.
                'profile_resolution_source' => $result->profile_resolution_source,
                
                'cgst_rate' => isset($comps['CGST']) ? ($input->resolved_tax_profile?->total_gst_rate / 2) : 0,
                'cgst_amount' => $comps['CGST'] ?? 0,
                'sgst_rate' => isset($comps['SGST']) ? ($input->resolved_tax_profile?->total_gst_rate / 2) : 0,
                'sgst_amount' => $comps['SGST'] ?? 0,
                'utgst_rate' => isset($comps['UTGST']) ? ($input->resolved_tax_profile?->total_gst_rate / 2) : 0,
                'utgst_amount' => $comps['UTGST'] ?? 0,
                'igst_rate' => isset($comps['IGST']) ? $input->resolved_tax_profile?->total_gst_rate : 0,
                'igst_amount' => $comps['IGST'] ?? 0,
                
                'cess_calculation_type' => $input->resolved_tax_profile?->cess_calculation_type,
                'cess_rate' => $input->resolved_tax_profile?->cess_rate ?? 0,
                'cess_amount_per_unit' => $input->resolved_tax_profile?->cess_amount_per_unit ?? 0,
                'cess_amount' => $comps['CESS'] ?? 0,
                
                'rounding_adjustment' => $result->rounding_adjustment,
                'line_total' => $result->total_value,
                'calculation_rule_code' => null,
            ]);
        }
    }

    private function getFinancialYear(Carbon $date): string
    {
        $year = $date->year;
        if ($date->month < 4) {
            return ($year - 1) . '-' . substr($year, -2);
        }
        return $year . '-' . substr($year + 1, -2);
    }
}
