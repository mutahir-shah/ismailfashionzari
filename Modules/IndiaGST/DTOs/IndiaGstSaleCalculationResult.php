<?php

namespace Modules\IndiaGST\DTOs;

class IndiaGstSaleCalculationResult
{
    public function __construct(
        public readonly bool $isSuccessful,
        public readonly array $lineResults,
        public readonly float $totalGrossValue,
        public readonly float $totalLineDiscount,
        public readonly float $totalInvoiceDiscount,
        public readonly float $totalTaxableCharges,
        public readonly float $totalNonTaxableCharges,
        public readonly float $totalTaxableValue,
        public readonly float $totalCgst,
        public readonly float $totalSgst,
        public readonly float $totalUtgst,
        public readonly float $totalIgst,
        public readonly float $totalCess,
        public readonly float $roundingAdjustment,
        public readonly float $grandTotal,
        public readonly array $errors = [],
        public readonly bool $requiresManualReview = false
    ) {
    }
}
