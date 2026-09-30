<?php

namespace Modules\IndiaGST\Services\Calculation\Resolvers;

use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;

class IndiaGstPlaceOfSupplyResolver
{
    public function resolve(TaxCalculationInput $input): array
    {
        dump('RESOLVER INPUT RULE: ' . $input->supply_rule_code);
        if (!empty($input->place_of_supply_override) && !empty($input->override_reason)) {
            return [
                'state_code' => $input->place_of_supply_override,
                'rule_code' => 'manual_override',
                'basis' => 'Manual Override: ' . $input->override_reason,
                'is_automatic' => false,
                'requires_manual_review' => false,
                'unsupported_rule_code' => null,
                'is_successful' => true,
            ];
        }

        $classification = $input->goods_service_classification ?? 'goods';
        $rule = $input->supply_rule_code;

        if ($classification === 'goods') {
            if ($rule === 'goods_movement') {
                if (empty($input->supplier_state_code) || empty($input->delivery_destination_state_code)) {
                    return $this->failure('goods_movement', 'Missing supplier or delivery destination state for goods movement.');
                }
                return $this->success($input->delivery_destination_state_code, 'goods_movement', 'Delivery Destination State');
            } elseif ($rule === 'goods_bill_to_ship_to') {
                if (empty($input->supplier_state_code) || empty($input->bill_to_party_state_code) || empty($input->ship_to_party_state_code)) {
                    return $this->failure('goods_bill_to_ship_to', 'Missing supplier, bill-to, or ship-to state for bill-to/ship-to rule.');
                }
                // Under Section 10(1)(b) of IGST Act, Place of Supply is the principal place of business of the third person (bill-to party)
                return $this->success($input->bill_to_party_state_code, 'goods_bill_to_ship_to', 'Bill-to Party State');
            } elseif ($rule === 'goods_no_movement') {
                if (empty($input->location_of_goods_state_code)) {
                    return $this->failure('goods_no_movement', 'Missing location of goods state.');
                }
                return $this->success($input->location_of_goods_state_code, 'goods_no_movement', 'Location of Goods State');
            } elseif ($rule === 'goods_installation') {
                if (empty($input->installation_site_state_code)) {
                    return $this->failure('goods_installation', 'Missing installation site state.');
                }
                return $this->success($input->installation_site_state_code, 'goods_installation', 'Installation Site State');
            }
        } elseif ($classification === 'service') {
            if ($rule === 'service_default_b2b') {
                if (empty($input->billing_state_code)) {
                    return $this->failure('service_default_b2b', 'Missing recipient billing state for B2B service.');
                }
                return $this->success($input->billing_state_code, 'service_default_b2b', 'Recipient Billing State');
            } elseif ($rule === 'service_default_b2c') {
                if (!empty($input->billing_state_code)) {
                    return $this->success($input->billing_state_code, 'service_default_b2c', 'Recipient Billing State (Available)');
                } elseif (!empty($input->supplier_state_code)) {
                    return $this->success($input->supplier_state_code, 'service_default_b2c', 'Supplier State (Recipient Unavailable)');
                } else {
                    return $this->failure('service_default_b2c', 'Missing both recipient billing state and supplier state.');
                }
            }
        }

        $unsupportedRules = [
            'export_with_tax' => 'Export with payment of tax',
            'export_without_tax' => 'Export without payment of tax',
            'sez_with_tax' => 'SEZ supply with payment of tax',
            'sez_without_tax' => 'SEZ supply without payment of tax',
            'deemed_export' => 'Deemed export',
            'import_goods' => 'Import of goods',
            'import_services' => 'Import of services',
            'service_immovable_property' => 'Services related to immovable property',
            'service_restaurant_catering_special' => 'Restaurant and catering services',
            'service_event_admission' => 'Admission to an event',
            'service_event_organization' => 'Organization of an event',
            'service_goods_transport' => 'Transportation of goods',
            'service_passenger_transport' => 'Passenger transportation',
            'service_telecom' => 'Telecommunication services',
            'service_banking_financial' => 'Banking and financial services',
            'service_insurance' => 'Insurance services',
            'supply_onboard_conveyance' => 'Supply on board a conveyance',
            'oidar' => 'OIDAR services',
            'ecommerce_operator_special' => 'E-commerce operator special cases',
            'multiple_establishment_review' => 'Multiple establishment review required',
            'manual_review_required' => 'Manual review required'
        ];

        if (array_key_exists($rule, $unsupportedRules)) {
            return [
                'state_code' => null,
                'rule_code' => $rule,
                'basis' => 'Explicitly unsupported rule',
                'is_automatic' => false,
                'requires_manual_review' => true,
                'unsupported_rule_code' => $rule,
                'is_successful' => false,
                'error_message' => __('indiagst::app.unsupported_pos_rule', ['rule' => $unsupportedRules[$rule]])
            ];
        }

        // Catch genuinely unknown rules
        return [
            'state_code' => null,
            'rule_code' => $rule,
            'basis' => 'Unknown supply rule',
            'is_automatic' => false,
            'requires_manual_review' => true,
            'unsupported_rule_code' => 'unknown_supply_rule',
            'is_successful' => false,
            'error_message' => __('indiagst::app.unknown_supply_rule')
        ];
    }

    private function success(string $stateCode, string $ruleCode, string $basis): array
    {
        return [
            'state_code' => $stateCode,
            'rule_code' => $ruleCode,
            'basis' => $basis,
            'is_automatic' => true,
            'requires_manual_review' => false,
            'unsupported_rule_code' => null,
            'is_successful' => true,
        ];
    }

    private function failure(string $ruleCode, string $errorMessage): array
    {
        return [
            'state_code' => null,
            'rule_code' => $ruleCode,
            'basis' => null,
            'is_automatic' => false,
            'requires_manual_review' => false,
            'unsupported_rule_code' => null,
            'is_successful' => false,
            'error_message' => $errorMessage
        ];
    }
}
