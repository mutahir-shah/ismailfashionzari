<?php

namespace Modules\IndiaGST\Services\Calculation\Resolvers;

use Carbon\Carbon;
use Modules\IndiaGST\Entities\GstTaxProfile;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;

class IndiaGstTaxProfileResolver
{
    public function resolve(TaxCalculationInput $input): array
    {
        if (!$input->resolved_tax_profile) {
            return [
                'is_successful' => false,
                'profile' => null,
                'error_message' => 'gst_tax_profile_missing',
                'resolution_source' => null
            ];
        }

        return [
            'is_successful' => true,
            'profile' => $input->resolved_tax_profile,
            'resolution_source' => $input->resolved_tax_profile->resolution_source
        ];
    }
}
