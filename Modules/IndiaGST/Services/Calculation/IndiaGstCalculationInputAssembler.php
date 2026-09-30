<?php

namespace Modules\IndiaGST\Services\Calculation;

use Carbon\Carbon;
use Modules\IndiaGST\Entities\GstTaxProfile;
use Modules\IndiaGST\Entities\IndiaGstProductProfile;
use Modules\IndiaGST\Entities\GstGenericTaxMapping;
use Modules\IndiaGST\Services\Calculation\DTO\ResolvedGstTaxProfile;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class IndiaGstCalculationInputAssembler
{
    public function assemble(
        array $rawData,
        ?User $user = null
    ): TaxCalculationInput {
        $txDate = $rawData['transaction_date'] ? Carbon::parse($rawData['transaction_date']) : Carbon::now();
        
        $resolvedProfile = null;
        $warnings = [];
        $source = null;
        $mappingId = null;

        // 1. Authorized explicit transaction GST tax profile
        if (!empty($rawData['gst_tax_profile_id']) && $user && $user->hasPermissionTo('gst_tax_profiles.override')) {
            $profile = GstTaxProfile::where('id', $rawData['gst_tax_profile_id'])
                ->where('is_active', true)
                ->where(function($q) use ($txDate) {
                    $q->where('effective_from', '<=', $txDate)
                      ->where(function($q2) use ($txDate) {
                          $q2->whereNull('effective_to')
                             ->orWhere('effective_to', '>=', $txDate);
                      });
                })->first();

            if ($profile) {
                $resolvedProfile = $profile;
                $source = 'transaction_override';
            } else {
                $warnings[] = 'Explicit transaction profile was provided but is inactive, invalid for the date, or not found.';
            }
        }

        // 2. Product GST profile's assigned tax profile
        if (!$resolvedProfile && !empty($rawData['product_id'])) {
            $productProfile = IndiaGstProductProfile::where('product_id', $rawData['product_id'])->first();
            if ($productProfile && $productProfile->gst_tax_profile_id) {
                $profile = GstTaxProfile::where('id', $productProfile->gst_tax_profile_id)
                    ->where('is_active', true)
                    ->where(function($q) use ($txDate) {
                        $q->where('effective_from', '<=', $txDate)
                          ->where(function($q2) use ($txDate) {
                              $q2->whereNull('effective_to')
                                 ->orWhere('effective_to', '>=', $txDate);
                          });
                    })->first();

                if ($profile) {
                    $resolvedProfile = $profile;
                    $source = 'product_gst_profile';
                }
            }
        }

        // 3. Explicit generic-tax mapping
        if (!$resolvedProfile && !empty($rawData['tax_id'])) {
            $mapping = GstGenericTaxMapping::where('tax_id', $rawData['tax_id'])
                ->where('is_active', true)
                ->first();

            if ($mapping && $mapping->gst_tax_profile_id) {
                $profile = GstTaxProfile::where('id', $mapping->gst_tax_profile_id)
                    ->where('is_active', true)
                    ->where(function($q) use ($txDate) {
                        $q->where('effective_from', '<=', $txDate)
                          ->where(function($q2) use ($txDate) {
                              $q2->whereNull('effective_to')
                                 ->orWhere('effective_to', '>=', $txDate);
                          });
                    })->first();

                if ($profile) {
                    $resolvedProfile = $profile;
                    $source = 'generic_tax_mapping';
                    $mappingId = $mapping->id;
                }
            }
        }

        $resolvedDto = null;
        if ($resolvedProfile) {
            $resolvedDto = new ResolvedGstTaxProfile(
                tax_profile_id: $resolvedProfile->id,
                tax_profile_code: $resolvedProfile->code,
                taxability_type: $resolvedProfile->taxability_type,
                total_gst_rate: (float)$resolvedProfile->total_gst_rate,
                cess_calculation_type: $resolvedProfile->cess_calculation_type,
                cess_rate: $resolvedProfile->cess_rate ? (float)$resolvedProfile->cess_rate : null,
                cess_amount_per_unit: $resolvedProfile->cess_amount_per_unit ? (float)$resolvedProfile->cess_amount_per_unit : null,
                effective_from: $resolvedProfile->effective_from ? clone $resolvedProfile->effective_from : null,
                effective_to: $resolvedProfile->effective_to ? clone $resolvedProfile->effective_to : null,
                resolution_source: $source,
                generic_tax_mapping_id: $mappingId
            );
        }

        return new TaxCalculationInput(
            transaction_date: $rawData['transaction_date'] ?? null,
            gst_registration_id: $rawData['gst_registration_id'] ?? null,
            supplier_state_code: $rawData['supplier_state_code'] ?? null,
            customer_gst_profile_id: $rawData['customer_gst_profile_id'] ?? null,
            customer_registration_category: $rawData['customer_registration_category'] ?? null,
            product_gst_profile_id: $rawData['product_gst_profile_id'] ?? null,
            gst_tax_profile_id: $rawData['gst_tax_profile_id'] ?? null,
            goods_service_classification: $rawData['goods_service_classification'] ?? 'goods',
            supply_rule_code: $rawData['supply_rule_code'] ?? null,
            supply_type: $rawData['supply_type'] ?? null,
            billing_state_code: $rawData['billing_state_code'] ?? null,
            shipping_state_code: $rawData['shipping_state_code'] ?? null,
            delivery_destination_state_code: $rawData['delivery_destination_state_code'] ?? null,
            location_of_goods_state_code: $rawData['location_of_goods_state_code'] ?? null,
            installation_site_state_code: $rawData['installation_site_state_code'] ?? null,
            bill_to_party_state_code: $rawData['bill_to_party_state_code'] ?? null,
            ship_to_party_state_code: $rawData['ship_to_party_state_code'] ?? null,
            place_of_supply_override: $rawData['place_of_supply_override'] ?? null,
            override_reason: $rawData['override_reason'] ?? null,
            unit_price: (float) ($rawData['unit_price'] ?? 0.0),
            quantity: (float) ($rawData['quantity'] ?? 0.0),
            is_tax_inclusive: (bool) ($rawData['is_tax_inclusive'] ?? false),
            line_discount: (float) ($rawData['line_discount'] ?? 0.0),
            allocated_invoice_discount: (float) ($rawData['allocated_invoice_discount'] ?? 0.0),
            taxable_additional_charges: (float) ($rawData['taxable_additional_charges'] ?? 0.0),
            non_taxable_additional_charges: (float) ($rawData['non_taxable_additional_charges'] ?? 0.0),
            cess_quantity_context: (float) ($rawData['cess_quantity_context'] ?? $rawData['quantity'] ?? 0.0),
            currency: $rawData['currency'] ?? null,
            exchange_rate: (float) ($rawData['exchange_rate'] ?? 1.0),
            resolved_tax_profile: $resolvedDto,
            profile_resolution_warnings: $warnings
        );
        dump('supply_rule_code: ' . $input->supply_rule_code, 'classification: ' . $input->goods_service_classification);
        return $input;
    }
}
