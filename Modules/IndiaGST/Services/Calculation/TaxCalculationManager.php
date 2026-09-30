<?php

namespace Modules\IndiaGST\Services\Calculation;

use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationResult;

class TaxCalculationManager
{
    public function __construct(
        protected IndiaGstCalculationStrategy $indiaGstStrategy
    ) {
    }

    /**
     * Executes the India GST calculation strategy.
     * This method must ONLY be called when India GST is explicitly enabled and requested
     * (e.g., Preview UI, Shadow Comparison, Phase 1C Integrations).
     * It does not wrap or interfere with standard SalePro generic tax calculation.
     */
    public function calculateIndiaGst(TaxCalculationInput $input): TaxCalculationResult
    {
        return $this->indiaGstStrategy->calculate($input);
    }
}
