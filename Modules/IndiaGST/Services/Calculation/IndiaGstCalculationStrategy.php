<?php

namespace Modules\IndiaGST\Services\Calculation;

use Modules\IndiaGST\Services\Calculation\Contracts\TaxCalculationStrategy;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationResult;
use Modules\IndiaGST\Services\Calculation\DTO\TaxComponentResult;
use Modules\IndiaGST\Services\Calculation\Resolvers\IndiaGstJurisdictionResolver;
use Modules\IndiaGST\Services\Calculation\Resolvers\IndiaGstPlaceOfSupplyResolver;
use Modules\IndiaGST\Services\Calculation\Resolvers\IndiaGstTaxProfileResolver;

class IndiaGstCalculationStrategy implements TaxCalculationStrategy
{
    public function __construct(
        protected IndiaGstTaxProfileResolver $taxProfileResolver,
        protected IndiaGstPlaceOfSupplyResolver $posResolver,
        protected IndiaGstJurisdictionResolver $jurisdictionResolver
    ) {
    }

    public function calculate(TaxCalculationInput $input): TaxCalculationResult
    {
        $result = new TaxCalculationResult();

        // 1. Resolve Profile
        $profileRes = $this->taxProfileResolver->resolve($input);
        if (!$profileRes['is_successful']) {
            $result->addValidationError($profileRes['error_message']);
            return $result;
        }
        $profile = $profileRes['profile'];
        $result->taxability_type = $profile->taxability_type;
        $result->tax_profile_code = $profile->tax_profile_code;
        $result->tax_profile_id = $profile->tax_profile_id;
        $result->tax_profile_version = $profile->effective_from;
        $result->effective_from = $profile->effective_from;
        $result->effective_to = $profile->effective_to;
        $result->profile_resolution_source = $profile->resolution_source;
        $result->resolution_warnings = $input->profile_resolution_warnings;

        if ($profile->taxability_type !== 'Taxable') {
            $result->is_successful = true;
            // No tax calculated for exempt/nil-rated/zero-rated/non-GST
            $this->calculateValue($input, $result, 0, 0, 0, 'none');
            return $result;
        }

        // 2. Resolve POS
        $posRes = $this->posResolver->resolve($input);
        if (!$posRes['is_successful']) {
            $result->addValidationError($posRes['error_message']);
            if ($posRes['unsupported_rule_code']) {
                $result->unsupported_rule_code = $posRes['unsupported_rule_code'];
                $result->requires_manual_review = true;
            }
            return $result;
        }
        $result->place_of_supply_state_code = $posRes['state_code'];
        $result->supply_rule_code = $posRes['rule_code'];

        // 3. Resolve Jurisdiction
        $jurRes = $this->jurisdictionResolver->resolve($input->supplier_state_code ?? '', $posRes['state_code']);
        if (!$jurRes['is_successful']) {
            $result->addValidationError($jurRes['error_message']);
            return $result;
        }
        $result->supplier_state_code = $input->supplier_state_code;
        $result->jurisdiction = $jurRes['jurisdiction'];

        // 4. Component Allocation & Calculation
        $this->calculateValue(
            $input, 
            $result, 
            $profile->total_gst_rate, 
            $profile->cess_rate ?? 0.0, 
            $profile->cess_amount_per_unit ?? 0.0, 
            $profile->cess_calculation_type
        );

        $result->is_successful = true;
        return $result;
    }

    protected function calculateValue(TaxCalculationInput $input, TaxCalculationResult $result, float $gstRate, float $cessRate, float $cessPerUnit, string $cessType)
    {
        $grossValue = $input->unit_price * $input->quantity;
        $discountedValue = $grossValue - $input->line_discount - $input->allocated_invoice_discount;
        $baseValue = $discountedValue + $input->taxable_additional_charges;

        if ($baseValue < 0) {
            $result->addValidationError('Taxable value cannot be negative in a standard sale.');
            $result->is_successful = false;
            return;
        }

        $taxableValue = 0.0;
        $totalGstAmount = 0.0;
        $totalCessAmount = 0.0;

        if ($input->is_tax_inclusive) {
            // Formula: BaseValue = TaxableValue + (TaxableValue * GstRate/100) + Cess
            $cessPerUnitTotal = ($cessType === 'per_unit') ? ($cessPerUnit * ($input->cess_quantity_context ?: $input->quantity)) : 0;
            $remainingForPct = $baseValue - $cessPerUnitTotal;

            $totalPctRate = $gstRate + (($cessType === 'percentage') ? $cessRate : 0);
            
            $taxableValue = $remainingForPct / (1 + ($totalPctRate / 100));
            $taxableValue = round($taxableValue, 4);

            $totalGstAmount = $taxableValue * ($gstRate / 100);
            if ($cessType === 'percentage') {
                $totalCessAmount = $taxableValue * ($cessRate / 100);
            } else {
                $totalCessAmount = $cessPerUnitTotal;
            }
        } else {
            $taxableValue = $baseValue;
            $totalGstAmount = $taxableValue * ($gstRate / 100);
            
            if ($cessType === 'percentage') {
                $totalCessAmount = $taxableValue * ($cessRate / 100);
            } elseif ($cessType === 'per_unit') {
                $totalCessAmount = $cessPerUnit * ($input->cess_quantity_context ?: $input->quantity);
            }
        }

        $result->taxable_value = round($taxableValue, 2);
        
        $cgstAmount = 0.0;
        $sgstAmount = 0.0;
        $utgstAmount = 0.0;
        $igstAmount = 0.0;

        if ($gstRate > 0) {
            if ($result->jurisdiction === 'intra_state_sgst') {
                $cgstAmount = $totalGstAmount / 2;
                $sgstAmount = $totalGstAmount / 2;
                $result->addComponent(new TaxComponentResult('cgst', 'indiagst::app.cgst', $gstRate / 2, $result->taxable_value, round($cgstAmount, 2), 'percentage', 'central', $input->is_tax_inclusive));
                $result->addComponent(new TaxComponentResult('sgst', 'indiagst::app.sgst', $gstRate / 2, $result->taxable_value, round($sgstAmount, 2), 'percentage', 'state', $input->is_tax_inclusive));
            } elseif ($result->jurisdiction === 'intra_state_utgst') {
                $cgstAmount = $totalGstAmount / 2;
                $utgstAmount = $totalGstAmount / 2;
                $result->addComponent(new TaxComponentResult('cgst', 'indiagst::app.cgst', $gstRate / 2, $result->taxable_value, round($cgstAmount, 2), 'percentage', 'central', $input->is_tax_inclusive));
                $result->addComponent(new TaxComponentResult('utgst', 'indiagst::app.utgst', $gstRate / 2, $result->taxable_value, round($utgstAmount, 2), 'percentage', 'union_territory', $input->is_tax_inclusive));
            } elseif ($result->jurisdiction === 'inter_state') {
                $igstAmount = $totalGstAmount;
                $result->addComponent(new TaxComponentResult('igst', 'indiagst::app.igst', $gstRate, $result->taxable_value, round($igstAmount, 2), 'percentage', 'integrated', $input->is_tax_inclusive));
            }
        }

        if ($totalCessAmount > 0) {
            $result->addComponent(new TaxComponentResult(
                'cess', 
                'indiagst::app.cess', 
                $cessType === 'percentage' ? $cessRate : $cessPerUnit, 
                $result->taxable_value, 
                round($totalCessAmount, 2), 
                $cessType, 
                'compensation', 
                $input->is_tax_inclusive
            ));
        }

        $result->total_tax = round($totalGstAmount, 2);
        $result->total_cess = round($totalCessAmount, 2);
        
        if ($input->is_tax_inclusive) {
            $result->total_value = $baseValue;
        } else {
            $result->total_value = round($baseValue + $result->total_tax + $result->total_cess, 2);
        }
        
        $result->rounding_adjustment = 0; // Handled dynamically if needed
    }
}
