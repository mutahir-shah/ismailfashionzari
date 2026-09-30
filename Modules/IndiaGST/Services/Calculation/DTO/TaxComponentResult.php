<?php

namespace Modules\IndiaGST\Services\Calculation\DTO;

class TaxComponentResult
{
    public function __construct(
        public string $component_code, // cgst, sgst, utgst, igst, cess
        public string $component_name_key, // Translation key
        public float $rate,
        public float $taxable_base,
        public float $amount,
        public string $calculation_type, // percentage, per_unit
        public string $jurisdiction,
        public bool $is_included_in_price
    ) {
    }
}
