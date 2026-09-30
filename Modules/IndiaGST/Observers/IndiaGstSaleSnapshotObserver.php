<?php

namespace Modules\IndiaGST\Observers;

use Modules\IndiaGST\Entities\IndiaGstSaleSnapshot;
use Illuminate\Validation\ValidationException;

class IndiaGstSaleSnapshotObserver
{
    /**
     * Handle the IndiaGstSaleSnapshot "updating" event.
     */
    public function updating(IndiaGstSaleSnapshot $snapshot): void
    {
        throw ValidationException::withMessages([
            'india_gst' => __('india-gst::messages.snapshot_immutable_update')
        ]);
    }

    /**
     * Handle the IndiaGstSaleSnapshot "deleting" event.
     */
    public function deleting(IndiaGstSaleSnapshot $snapshot): void
    {
        throw ValidationException::withMessages([
            'india_gst' => __('india-gst::messages.snapshot_immutable_delete')
        ]);
    }
}
