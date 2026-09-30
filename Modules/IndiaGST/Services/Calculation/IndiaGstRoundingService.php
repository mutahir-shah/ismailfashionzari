<?php

namespace Modules\IndiaGST\Services\Calculation;

class IndiaGstRoundingService
{
    public function roundAmount(float $amount, int $precision = 2): float
    {
        return round($amount, $precision);
    }
}
