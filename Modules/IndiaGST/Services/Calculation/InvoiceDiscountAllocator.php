<?php

namespace Modules\IndiaGST\Services\Calculation;

class InvoiceDiscountAllocator
{
    /**
     * @param array $lines Format: [['id' => 1, 'gross_amount' => 100, 'is_eligible' => true], ...]
     * @param float $totalDiscount
     * @return array Mapping of line_id to allocated_discount_amount
     */
    public function allocate(array $lines, float $totalDiscount): array
    {
        $allocated = [];
        $eligibleTotalGross = 0.0;
        
        foreach ($lines as $line) {
            $allocated[$line['id']] = 0.0;
            if ($line['is_eligible'] ?? true) {
                $eligibleTotalGross += $line['gross_amount'];
            }
        }

        if ($eligibleTotalGross <= 0 || $totalDiscount <= 0) {
            return $allocated;
        }

        $remainingDiscount = $totalDiscount;
        $eligibleCount = count(array_filter($lines, fn($l) => $l['is_eligible'] ?? true));
        $i = 0;

        foreach ($lines as $line) {
            if (!($line['is_eligible'] ?? true)) {
                continue;
            }
            $i++;

            if ($i === $eligibleCount) {
                // Last item gets the exact remaining to avoid rounding leaks
                $allocated[$line['id']] = round($remainingDiscount, 2);
            } else {
                $ratio = $line['gross_amount'] / $eligibleTotalGross;
                $lineDiscount = round($totalDiscount * $ratio, 2);
                $allocated[$line['id']] = $lineDiscount;
                $remainingDiscount -= $lineDiscount;
            }
        }

        return $allocated;
    }
}
