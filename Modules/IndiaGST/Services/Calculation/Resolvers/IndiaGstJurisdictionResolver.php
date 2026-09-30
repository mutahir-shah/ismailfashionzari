<?php

namespace Modules\IndiaGST\Services\Calculation\Resolvers;

use Modules\IndiaGST\Entities\IndiaGstState;

class IndiaGstJurisdictionResolver
{
    public function resolve(string $supplierStateCode, string $posStateCode): array
    {
        if (empty($supplierStateCode) || empty($posStateCode)) {
            return [
                'is_successful' => false,
                'jurisdiction' => null,
                'error_message' => 'Supplier state and Place of Supply state are required.'
            ];
        }

        if ($supplierStateCode !== $posStateCode) {
            return [
                'is_successful' => true,
                'jurisdiction' => 'inter_state',
            ];
        }

        $state = IndiaGstState::where('state_code', $posStateCode)->first();
        if (!$state) {
            return [
                'is_successful' => false,
                'jurisdiction' => null,
                'error_message' => 'Invalid state code provided.'
            ];
        }

        $jurisdiction = $state->gst_jurisdiction_type === 'utgst' 
            ? 'intra_state_utgst' 
            : 'intra_state_sgst';

        return [
            'is_successful' => true,
            'jurisdiction' => $jurisdiction,
        ];
    }
}
