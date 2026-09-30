<?php

namespace Modules\IndiaGST\Services\Calculation;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Product;
use App\Models\Customer;
use Modules\IndiaGST\Entities\IndiaGstCustomerProfile;
use Modules\IndiaGST\Entities\IndiaGstProductProfile;
use Modules\IndiaGST\Services\Calculation\IndiaGstSaleRegistrationResolver;
use Modules\IndiaGST\Services\Calculation\InvoiceDiscountAllocator;
use Modules\IndiaGST\Services\Calculation\IndiaGstCalculationInputAssembler;
use Modules\IndiaGST\Services\Calculation\IndiaGstCalculationStrategy;
use Modules\IndiaGST\Services\Calculation\Resolvers\IndiaGstPlaceOfSupplyResolver;
use Modules\IndiaGST\Services\Calculation\Resolvers\IndiaGstJurisdictionResolver;
use Modules\IndiaGST\DTOs\IndiaGstSaleCalculationResult;

class IndiaGstSaleCalculationService
{
    public function __construct(
        private IndiaGstSaleRegistrationResolver $registrationResolver,
        private InvoiceDiscountAllocator $discountAllocator,
        private IndiaGstCalculationInputAssembler $inputAssembler,
        private IndiaGstCalculationStrategy $calculationStrategy,
        private IndiaGstPlaceOfSupplyResolver $posResolver,
        private IndiaGstJurisdictionResolver $jurisdictionResolver
    ) {
    }

    public function calculate(array $saleData, $user = null): IndiaGstSaleCalculationResult
    {
        // 1. Resolve Supplier Registration
        $registration = $this->registrationResolver->resolve($saleData);
        $supplierStateCode = $registration->state->state_code;

        // 2. Resolve Customer Profile
        $customerId = $saleData['customer_id'] ?? null;
        $customerProfile = null;
        if ($customerId) {
            $customerProfile = IndiaGstCustomerProfile::with('state')->where('customer_id', $customerId)->first();
        }

        // 3. Extract Invoice Discount & Lines
        $totalInvoiceDiscount = (float)($saleData['order_discount'] ?? 0);
        if (isset($saleData['order_discount_type']) && $saleData['order_discount_type'] === 'Percentage') {
            // Note: SalePro typically converts percentage to flat amount before this stage or during save.
            // If total_price is provided, we might need to calculate it. We'll trust the payload order_discount flat amount.
            // But just in case:
            $order_discount_val = (float)($saleData['order_discount_value'] ?? 0);
            if ($order_discount_val > 0 && empty($saleData['order_discount'])) {
                // Approximate fallback if not flat calculated yet
                $totalInvoiceDiscount = ((float)($saleData['total_price'] ?? 0) * $order_discount_val) / 100;
            }
        }

        $lines = [];
        $productIds = $saleData['product_id'] ?? [];
        $quantities = $saleData['qty'] ?? [];
        $netUnitPrices = $saleData['net_unit_price'] ?? [];
        $lineDiscounts = $saleData['discount'] ?? [];
        $taxRates = $saleData['tax_rate'] ?? []; // used for fallback mapping
        $taxIds = $saleData['tax_id'] ?? []; // explicit tax_id fallback

        if (empty($productIds)) {
            throw ValidationException::withMessages(['product_id' => 'Products are required for GST calculation.']);
        }

        // Preload Product Profiles
        $productProfiles = IndiaGstProductProfile::whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Prepare line data for discount allocation
        $allocationLines = [];
        foreach ($productIds as $i => $pid) {
            $qty = (float)($quantities[$i] ?? 0);
            $netUnitPrice = (float)($netUnitPrices[$i] ?? 0);
            $lineDiscount = (float)($lineDiscounts[$i] ?? 0);
            $grossAmount = ($qty * $netUnitPrice) - $lineDiscount;
            
            $allocationLines[] = [
                'id' => $i,
                'gross_amount' => max(0, $grossAmount),
                'is_eligible' => true // Assume all eligible for now unless specifically nil-rated handling applies later
            ];
        }

        // 4. Allocate Invoice Discount
        $allocatedDiscounts = $this->discountAllocator->allocate($allocationLines, $totalInvoiceDiscount);

        // 5. Build inputs and Calculate line by line
        $lineResults = [];
        $errors = [];
        $requiresManualReview = false;

        $aggGross = 0.0;
        $aggLineDiscount = 0.0;
        $aggInvDiscount = 0.0;
        $aggTaxableCharges = 0.0;
        $aggNonTaxableCharges = 0.0;
        $aggTaxableValue = 0.0;
        $aggCgst = 0.0;
        $aggSgst = 0.0;
        $aggUtgst = 0.0;
        $aggIgst = 0.0;
        $aggCess = 0.0;
        $aggRounding = 0.0;
        $aggTotal = 0.0;

        foreach ($productIds as $i => $pid) {
            $productProfile = $productProfiles->get($pid);
            if (!$productProfile) {
                $errors[] = "Product ID {$pid} is missing an India GST Profile.";
                continue;
            }

            // Fallback generic tax mapping
            $genericTaxId = $taxIds[$i] ?? null;

            // Assemble input using Phase 1B Assembler
            $rawData = [
                'transaction_date' => $saleData['created_at'] ?? now()->format('Y-m-d H:i:s'),
                'gst_registration_id' => $registration->id,
                'supplier_state_code' => $supplierStateCode,
                
                'customer_gst_profile_id' => $customerProfile?->id,
                'customer_registration_category' => $customerProfile?->registration_type,
                'billing_state_code' => $customerProfile?->state?->state_code,
                'shipping_state_code' => $customerProfile?->state?->state_code, // Defaulting to billing if separate shipping not provided in SalePro payload
                
                'product_id' => $pid,
                'product_gst_profile_id' => $productProfile->id,
                'goods_service_classification' => $productProfile->type ?? 'goods',
                'tax_id' => $genericTaxId,
                
                'supply_rule_code' => !empty($saleData['supply_rule_code']) ? $saleData['supply_rule_code'] : (($productProfile->type ?? 'goods') === 'goods' ? 'goods_movement' : 'service_default_b2b'),
                'place_of_supply_override' => $saleData['place_of_supply_override'] ?? null,
                'override_reason' => $saleData['override_reason'] ?? null,
                'delivery_destination_state_code' => $customerProfile?->state?->state_code,
                
                'unit_price' => (float)($netUnitPrices[$i] ?? 0),
                'quantity' => (float)($quantities[$i] ?? 0),
                'is_tax_inclusive' => false, // SalePro standard sends net prices
            ];
            
            dump('RAW DATA supply_rule_code:', $rawData['supply_rule_code']);
            dump('productProfile->type:', $productProfile->type ?? 'null');
            $rawData['line_discount'] = (float)($lineDiscounts[$i] ?? 0);
            $rawData['allocated_invoice_discount'] = (float)($allocatedDiscounts[$i] ?? 0);

            try {
                $input = $this->inputAssembler->assemble($rawData, $user);
                
                // Determine POS and Jurisdiction
                $posResult = $this->posResolver->resolve($input);
                if (!$posResult['is_successful']) {
                    $errors[] = "Line {$i}: " . ($posResult['error_message'] ?? 'Unknown POS error');
                    $requiresManualReview = true;
                    continue;
                }

                $jurisdiction = $this->jurisdictionResolver->resolve($input->supplier_state_code, $posResult['state_code']);
                if (!$jurisdiction['is_successful']) {
                    $errors[] = "Line {$i}: Jurisdiction error: {$jurisdiction['error_message']}";
                    $requiresManualReview = true;
                    continue;
                }

                // Calculate using Strategy
                $result = $this->calculationStrategy->calculate($input, $posResult, $jurisdiction['jurisdiction']);

                $lineResults[$i] = [
                    'product_id' => $pid,
                    'input' => $input,
                    'posResult' => $posResult,
                    'jurisdiction' => $jurisdiction,
                    'result' => $result,
                    'product_profile' => $productProfile
                ];

                // Aggregate
                $aggGross += ($input->unit_price * $input->quantity);
                $aggLineDiscount += $input->line_discount;
                $aggInvDiscount += $input->allocated_invoice_discount;
                $aggTaxableCharges += $input->taxable_additional_charges;
                $aggNonTaxableCharges += $input->non_taxable_additional_charges;
                $aggTaxableValue += $result->taxable_value;
                
                $comps = [];
                foreach ($result->components as $comp) {
                    $comps[strtoupper($comp->component_code)] = $comp->amount;
                }

                $aggCgst += $comps['CGST'] ?? 0.0;
                $aggSgst += $comps['SGST'] ?? 0.0;
                $aggUtgst += $comps['UTGST'] ?? 0.0;
                $aggIgst += $comps['IGST'] ?? 0.0;
                $aggCess += $comps['CESS'] ?? 0.0;
                
                $aggRounding += $result->rounding_adjustment;
                $aggTotal += $result->total_value;

            } catch (ValidationException $e) {
                $errors[] = "Line {$i}: " . $e->getMessage();
                $requiresManualReview = true;
            } catch (\Exception $e) {
                $errors[] = "Line {$i}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return new IndiaGstSaleCalculationResult(
                isSuccessful: false,
                lineResults: [],
                totalGrossValue: 0,
                totalLineDiscount: 0,
                totalInvoiceDiscount: 0,
                totalTaxableCharges: 0,
                totalNonTaxableCharges: 0,
                totalTaxableValue: 0,
                totalCgst: 0,
                totalSgst: 0,
                totalUtgst: 0,
                totalIgst: 0,
                totalCess: 0,
                roundingAdjustment: 0,
                grandTotal: 0,
                errors: $errors,
                requiresManualReview: $requiresManualReview
            );
        }

        return new IndiaGstSaleCalculationResult(
            isSuccessful: true,
            lineResults: $lineResults,
            totalGrossValue: $aggGross,
            totalLineDiscount: $aggLineDiscount,
            totalInvoiceDiscount: $aggInvDiscount,
            totalTaxableCharges: $aggTaxableCharges,
            totalNonTaxableCharges: $aggNonTaxableCharges,
            totalTaxableValue: $aggTaxableValue,
            totalCgst: $aggCgst,
            totalSgst: $aggSgst,
            totalUtgst: $aggUtgst,
            totalIgst: $aggIgst,
            totalCess: $aggCess,
            roundingAdjustment: $aggRounding,
            grandTotal: $aggTotal
        );
    }
}
