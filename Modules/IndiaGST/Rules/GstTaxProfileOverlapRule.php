<?php

namespace Modules\IndiaGST\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\IndiaGST\Entities\GstTaxProfile;
use Carbon\Carbon;

class GstTaxProfileOverlapRule implements Rule
{
    protected $code;
    protected $ignoreId;
    protected $effectiveTo;

    public function __construct($code, $effectiveTo = null, $ignoreId = null)
    {
        $this->code = $code;
        $this->effectiveTo = $effectiveTo ? Carbon::parse($effectiveTo)->startOfDay() : null;
        $this->ignoreId = $ignoreId;
    }

    public function passes($attribute, $value)
    {
        $effectiveFrom = Carbon::parse($value)->startOfDay();

        $query = GstTaxProfile::where('code', $this->code);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        $overlappingRecords = $query->get()->filter(function ($profile) use ($effectiveFrom) {
            $profileFrom = Carbon::parse($profile->effective_from)->startOfDay();
            $profileTo = $profile->effective_to ? Carbon::parse($profile->effective_to)->startOfDay() : null;

            // Scenario 1: New range is open-ended
            if (!$this->effectiveTo) {
                // If existing is open-ended, any start date overlaps eventually
                if (!$profileTo) {
                    return true;
                }
                // Overlaps if the existing range ends on or after the new start date
                return $profileTo->gte($effectiveFrom);
            }

            // Scenario 2: New range is closed
            // If existing is open-ended, overlaps if the existing start is on or before the new end date
            if (!$profileTo) {
                return $profileFrom->lte($this->effectiveTo);
            }

            // Scenario 3: Both are closed
            // Overlaps if they intersect
            return $profileFrom->lte($this->effectiveTo) && $profileTo->gte($effectiveFrom);
        });

        return $overlappingRecords->isEmpty();
    }

    public function message()
    {
        return __('indiagst::app.tax_profile_date_overlap_error');
    }
}
