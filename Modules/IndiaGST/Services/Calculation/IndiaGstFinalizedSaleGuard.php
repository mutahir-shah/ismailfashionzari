<?php

namespace Modules\IndiaGST\Services\Calculation;

use App\Models\Sale;
use Illuminate\Validation\ValidationException;
use Modules\IndiaGST\Entities\IndiaGstSaleSnapshot;

class IndiaGstFinalizedSaleGuard
{
    /**
     * Blocks update if the sale has an immutable India GST snapshot and the update affects tax.
     * Note: A true robust check would diff the payload with the snapshot. For Phase 1C, 
     * SalePro's update typically replaces all lines, so we block any update to a finalized GST sale.
     *
     * @param Sale $sale
     * @param array $payload
     * @throws ValidationException
     */
    public function guard(Sale $sale, array $payload = []): void
    {
        // 1. Is India GST enabled?
        if (!config('india-gst.is_india_gst_enabled', true)) {
            return;
        }

        // 2. Is this sale snapshotted?
        $snapshotCount = IndiaGstSaleSnapshot::where('sale_id', $sale->id)->count();
        if ($snapshotCount === 0) {
            return; // Not a finalized GST sale
        }

        // 3. For Phase 1C, any update attempt on a snapshotted sale through the generic UI is blocked
        // because the generic UI does not support differential GST updates and replacing lines
        // breaks the immutable snapshot.
        // If it's a status-only update (e.g. payment status, delivery status), it might be allowed if 
        // the payload doesn't contain product lines.
        
        $hasLineChanges = isset($payload['product_id']) || isset($payload['qty']) || isset($payload['net_unit_price']);
        $hasHeaderTaxChanges = isset($payload['customer_id']) || isset($payload['warehouse_id']) || isset($payload['order_discount']);

        if ($hasLineChanges || $hasHeaderTaxChanges) {
            throw ValidationException::withMessages([
                'india_gst' => __('india-gst::messages.cannot_update_finalized_gst_sale')
            ]);
        }
    }

    /**
     * Blocks deletion of a finalized GST sale.
     */
    public function guardDelete(Sale $sale): void
    {
        if (!config('india-gst.is_india_gst_enabled', true)) {
            return;
        }

        $snapshotCount = IndiaGstSaleSnapshot::where('sale_id', $sale->id)->count();
        if ($snapshotCount > 0) {
            throw ValidationException::withMessages([
                'india_gst' => __('india-gst::messages.cannot_delete_finalized_gst_sale')
            ]);
        }
    }
}
