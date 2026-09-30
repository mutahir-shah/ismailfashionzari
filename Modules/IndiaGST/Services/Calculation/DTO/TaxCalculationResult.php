<?php

namespace Modules\IndiaGST\Services\Calculation\DTO;

class TaxCalculationResult
{
    public function __construct(
        public bool $is_successful = false,
        public string $taxability_type = 'Non-GST',
        public float $taxable_value = 0.0,
        public float $total_tax = 0.0,
        public float $total_cess = 0.0,
        public float $total_value = 0.0,
        public ?string $jurisdiction = null, // intra_state_sgst, intra_state_utgst, inter_state, etc.
        public ?string $supplier_state_code = null,
        public ?string $place_of_supply_state_code = null,
        public ?string $supply_rule_code = null,
        public ?string $tax_profile_code = null,
        public ?string $tax_profile_version = null,
        /** @var TaxComponentResult[] */
        public array $components = [],
        public float $rounding_adjustment = 0.0,
        public array $warnings = [],
        public array $validation_errors = [],
        public ?string $unsupported_rule_code = null,
        public ?string $profile_resolution_source = null,
        public ?int $tax_profile_id = null,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
        public array $resolution_warnings = [],
        public bool $requires_manual_review = false
    ) {
    }

    public function addComponent(TaxComponentResult $component)
    {
        $this->components[] = $component;
    }

    public function addWarning(string $warning)
    {
        $this->warnings[] = $warning;
    }

    public function addValidationError(string $error)
    {
        $this->validation_errors[] = $error;
    }
}
