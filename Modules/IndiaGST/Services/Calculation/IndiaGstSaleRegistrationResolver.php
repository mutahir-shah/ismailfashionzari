<?php

namespace Modules\IndiaGST\Services\Calculation;

use Modules\IndiaGST\Entities\IndiaGstRegistration;
use Illuminate\Validation\ValidationException;

class IndiaGstSaleRegistrationResolver
{
    /**
     * Resolves the authoritative GST registration for a sale.
     *
     * @param array $saleData
     * @return \Modules\IndiaGST\Entities\IndiaGstRegistration
     * @throws ValidationException
     */
    public function resolve(array $saleData): IndiaGstRegistration
    {
        $warehouseId = $saleData['warehouse_id'] ?? null;

        if (!$warehouseId) {
            throw ValidationException::withMessages([
                'warehouse_id' => __('india-gst::messages.warehouse_required_for_gst')
            ]);
        }

        // Phase 1A established that warehouse mapping is the primary linkage.
        $registration = IndiaGstRegistration::with('state')->where('warehouse_id', $warehouseId)->first();

        if (!$registration) {
            throw ValidationException::withMessages([
                'warehouse_id' => __('india-gst::messages.no_gst_registration_for_warehouse')
            ]);
        }

        return $registration;
    }
}
