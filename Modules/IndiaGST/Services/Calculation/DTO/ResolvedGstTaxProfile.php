<?php

namespace Modules\IndiaGST\Services\Calculation\DTO;

class ResolvedGstTaxProfile
{
    public function __construct(
        public int $tax_profile_id,
        public string $tax_profile_code,
        public string $taxability_type,
        public float $total_gst_rate,
        public string $cess_calculation_type,
        public ?float $cess_rate,
        public ?float $cess_amount_per_unit,
        public ?string $effective_from,
        public ?string $effective_to,
        public string $resolution_source,
        public ?int $generic_tax_mapping_id = null
    ) {
    }
}
