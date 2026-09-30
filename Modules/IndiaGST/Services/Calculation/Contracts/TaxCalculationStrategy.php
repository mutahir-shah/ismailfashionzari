<?php

namespace Modules\IndiaGST\Services\Calculation\Contracts;

use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationInput;
use Modules\IndiaGST\Services\Calculation\DTO\TaxCalculationResult;

interface TaxCalculationStrategy
{
    public function calculate(TaxCalculationInput $input): TaxCalculationResult;
}
