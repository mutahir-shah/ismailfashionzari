<?php

namespace App\Services\Profitability;

use Carbon\Carbon;

class ProfitabilityFilter
{
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?int $warehouseId = null,
        public readonly ?int $productId = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $brandId = null,
        public readonly ?int $customerId = null,
        public readonly bool $groupVariants = false,
        public readonly bool $ownDataOnly = false,
        public readonly ?int $userId = null,
        public readonly ?string $search = null,
        public readonly string $sortBy = 'gross_profit',
        public readonly string $sortDirection = 'desc',
        public readonly int $offset = 0,
        public readonly int $limit = 10,
    ) {
    }

    public static function normalizeDate(?string $date, ?string $fallback = null): string
    {
        $value = $date ?: $fallback ?: now()->toDateString();

        return Carbon::parse($value)->toDateString();
    }

    public function startDateTime(): string
    {
        return $this->startDate . ' 00:00:00';
    }

    public function endDateTime(): string
    {
        return $this->endDate . ' 23:59:59';
    }

    public function hasWarehouseFilter(): bool
    {
        return ! empty($this->warehouseId);
    }

    public function hasSearch(): bool
    {
        return trim((string) $this->search) !== '';
    }

    public function normalizedSearch(): string
    {
        return mb_strtolower(trim((string) $this->search));
    }
}
