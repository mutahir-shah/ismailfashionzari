<?php

namespace Modules\IndiaGST\Services\Calculation;

use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use Modules\IndiaGST\DTOs\PreparedIndiaGstSale;

class IndiaGstSaleTransactionService
{
    public function __construct(
        private IndiaGstSaleCalculationService $calculationService,
        private IndiaGstSaleSnapshotPersistenceService $persistenceService,
        private IndiaGstSaleRegistrationResolver $registrationResolver
    ) {
    }

    /**
     * Inspects the incoming payload. If India GST is applicable, recalculates all 
     * taxes authoritatively and overrides the generic tax fields in the payload 
     * before standard persistence occurs.
     */
    public function prepareSaleData(array $data, $user = null): PreparedIndiaGstSale
    {
        // 1. Resolve applicability
        // Check if India GST module is enabled globally
        if (!config('india-gst.is_india_gst_enabled', true)) { // Assuming it defaults to true if installed, or grab from general_settings
            // Further check if the general_setting actually has it enabled (if applicable)
            return new PreparedIndiaGstSale(saleData: $data);
        }

        // Check if there is a warehouse provided
        if (empty($data['warehouse_id'])) {
            return new PreparedIndiaGstSale(saleData: $data); // Cannot resolve registration without warehouse
        }

        try {
            $registration = $this->registrationResolver->resolve($data);
        } catch (ValidationException $e) {
            // Not a GST warehouse, continue generic
            return new PreparedIndiaGstSale(saleData: $data);
        }

        // 2. Authoritatively calculate
        $result = $this->calculationService->calculate($data, $user);

        if (!$result->isSuccessful) {
            throw ValidationException::withMessages([
                'india_gst' => $result->errors
            ]);
        }

        // 3. Reconcile generic fields in payload
        // Re-calculate the grand total based on the GST tax and existing other components (like shipping)
        
        $data['total_tax'] = $result->totalCgst + $result->totalSgst + $result->totalUtgst + $result->totalIgst + $result->totalCess;
        $data['order_tax'] = 0; // GST allocates everything at line level, no separate order tax
        
        // Sum up line totals to recalculate grand total.
        $totalPrice = 0;
        foreach ($result->lineResults as $i => $lineRes) {
            $r = $lineRes['result'];
            $input = $lineRes['input'];
            
            $comps = [];
            foreach ($r->components as $comp) {
                $comps[strtoupper($comp->component_code)] = $comp->amount;
            }
            
            // Reconcile line arrays
            $data['tax'][$i] = ($comps['CGST'] ?? 0) + ($comps['SGST'] ?? 0) + ($comps['UTGST'] ?? 0) + ($comps['IGST'] ?? 0) + ($comps['CESS'] ?? 0);
            $data['subtotal'][$i] = $r->total_value;
            $data['discount'][$i] = $input->line_discount;
            
            $totalPrice += $r->total_value;
        }

        $data['total_price'] = $totalPrice;
        
        // Final Grand Total = line subtotals + shipping_cost - order_discount
        $shipping = (float)($data['shipping_cost'] ?? 0);
        // Order discount is already allocated inside line subtotals, so we don't subtract it again from the grand total.
        // Wait, SalePro generic expects grand_total = total_price + order_tax + shipping_cost - order_discount
        // But since we allocated the order_discount into the line totals in Phase 1B, subtracting it again would double-count.
        // Let's explicitly build the grand_total according to our calculation result.
        
        $data['grand_total'] = $result->grandTotal + $shipping;

        return new PreparedIndiaGstSale(
            saleData: $data,
            gstCalculationResult: $result,
            resolvedRegistration: $registration,
            isFinalized: ($data['sale_status'] ?? 0) != 3 // Example, 3 is draft
        );
    }

    /**
     * Called after standard persistence is complete. Persists the immutable snapshot.
     */
    public function finalizeSale(Sale $sale, PreparedIndiaGstSale $preparedSale): void
    {
        if ($preparedSale->isIndiaGstApplicable()) {
            $this->persistenceService->persist(
                $sale, 
                $preparedSale->gstCalculationResult, 
                $preparedSale->resolvedRegistration, 
                $preparedSale->saleData
            );
        }
    }
}
