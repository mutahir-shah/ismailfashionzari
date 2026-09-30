<?php

namespace Modules\IndiaGST\Services\Calculation\DTO;

class TaxCalculationInput
{
    public function __construct(
        public ?string $transaction_date = null,
        public ?int $gst_registration_id = null,
        public ?string $supplier_state_code = null,
        public ?int $customer_gst_profile_id = null,
        public ?string $customer_registration_category = null,
        public ?int $product_gst_profile_id = null,
        public ?int $gst_tax_profile_id = null,
        public ?string $goods_service_classification = null, // goods, service
        public ?string $supply_rule_code = null,
        public ?string $supply_type = null, // b2b, b2c, etc.
        public ?string $billing_state_code = null,
        public ?string $shipping_state_code = null,
        public ?string $delivery_destination_state_code = null,
        public ?string $location_of_goods_state_code = null,
        public ?string $installation_site_state_code = null,
        public ?string $bill_to_party_state_code = null,
        public ?string $ship_to_party_state_code = null,
        public ?string $place_of_supply_override = null,
        public ?string $override_reason = null,
        public float $unit_price = 0.0,
        public float $quantity = 0.0,
        public bool $is_tax_inclusive = false,
        public float $line_discount = 0.0,
        public float $allocated_invoice_discount = 0.0,
        public float $taxable_additional_charges = 0.0,
        public float $non_taxable_additional_charges = 0.0,
        public float $cess_quantity_context = 0.0, // used for per unit cess calculation if different from general quantity
        public ?string $currency = null,
        public float $exchange_rate = 1.0,
        public ?ResolvedGstTaxProfile $resolved_tax_profile = null,
        public array $profile_resolution_warnings = []
    ) {
    }
}
