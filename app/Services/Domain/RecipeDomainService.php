<?php

namespace App\Services\Domain;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class RecipeDomainService
{
    public const FIELDS = ['is_recipe', 'product_list', 'qty_list', 'price_list', 'wastage_percent', 'combo_unit_id', 'variant_list', 'cost', 'price'];

    public function save(Product $product, array $input): Product
    {
        return DB::transaction(function () use ($product, $input) {
            $quantities = array_values($input['product_qty'] ?? []);
            $unitPrices = array_values($input['unit_price'] ?? []);
            $unitCosts = array_values($input['product_unit_cost'] ?? []);
            $unitIds = array_values($input['combo_unit_id'] ?? []);
            $totalCost = 0.0;
            $totalPrice = 0.0;

            foreach ($quantities as $index => $quantity) {
                $converted = $this->convert((float) $quantity, $unitIds[$index] ?? null);
                $totalCost += $converted * (float) ($unitCosts[$index] ?? 0);
                $totalPrice += $converted * (float) ($unitPrices[$index] ?? 0);
            }

            $product->update([
                'qty_list' => implode(',', $quantities),
                'price_list' => implode(',', $unitPrices),
                'wastage_percent' => implode(',', $input['wastage_percent'] ?? []),
                'combo_unit_id' => implode(',', $unitIds),
                'product_list' => implode(',', $input['product_list'] ?? $input['product_id'] ?? []),
                'variant_list' => implode(',', $input['variant_list'] ?? $input['variant_id'] ?? []),
                'is_recipe' => 1,
                'cost' => $totalCost,
                'price' => $totalPrice,
            ]);

            return $product->refresh();
        });
    }

    public function snapshot(Product $product): array
    {
        return collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => $product->getRawOriginal($field)])->all();
    }

    public function restore(Product $product, array $snapshot): Product
    {
        $product->update(collect($snapshot)->only(self::FIELDS)->all());
        return $product->refresh();
    }

    public function convert(float $quantity, ?int $unitId): float
    {
        $unit = $unitId ? Unit::find($unitId) : null;
        if (!$unit) {
            return $quantity;
        }

        return $unit->operator === '/'
            ? $quantity / (float) $unit->operation_value
            : $quantity * (float) $unit->operation_value;
    }
}
