<?php

namespace Modules\IndiaGST\Services\Calculation;

use App\Models\Sale;
use Illuminate\Validation\ValidationException;
use Modules\IndiaGST\Entities\IndiaGstSaleSnapshot;

class IndiaGstReturnEligibilityService
{
    /**
     * Prevent returning a finalized GST sale in Phase 1C.
     * Full GST credit note (Return) support is slated for Phase 1E.
     *
     * @param Sale $sale
     * @throws ValidationException
     */
    public function blockReturnIfFinalizedGstSale(Sale $sale): void
    {
        if (!config('india-gst.is_india_gst_enabled', true)) {
            return;
        }

        $snapshotExists = IndiaGstSaleSnapshot::where('sale_id', $sale->id)->exists();

        if ($snapshotExists) {
            throw ValidationException::withMessages([
                'india_gst' => __('india-gst::messages.returns_not_yet_supported_for_gst_sales')
            ]);
        }
    }
}
