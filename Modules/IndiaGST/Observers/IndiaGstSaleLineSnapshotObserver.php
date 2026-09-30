<?php

namespace Modules\IndiaGST\Observers;

use Modules\IndiaGST\Entities\IndiaGstSaleLineSnapshot;
use Illuminate\Validation\ValidationException;

class IndiaGstSaleLineSnapshotObserver
{
    /**
     * Handle the IndiaGstSaleLineSnapshot "updating" event.
     */
    public function updating(IndiaGstSaleLineSnapshot $snapshot): void
    {
        throw ValidationException::withMessages([
            'india_gst' => __('india-gst::messages.snapshot_immutable_update')
        ]);
    }

    /**
     * Handle the IndiaGstSaleLineSnapshot "deleting" event.
     */
    public function deleting(IndiaGstSaleLineSnapshot $snapshot): void
    {
        throw ValidationException::withMessages([
            'india_gst' => __('india-gst::messages.snapshot_immutable_delete')
        ]);
    }
}
