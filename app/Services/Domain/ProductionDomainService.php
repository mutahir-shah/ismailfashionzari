<?php

namespace App\Services\Domain;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Entities\Production;
use RuntimeException;

class ProductionDomainService
{
    public function create(array $input, int $userId): Production
    {
        return DB::transaction(function () use ($input, $userId) {
            $products = array_values($input['product_list']);
            $quantities = array_values($input['product_qty']);
            $unitIds = array_values($input['production_unit_ids']);
            $prices = array_values($input['unit_price'] ?? []);
            $wastage = array_values($input['wastage_percent'] ?? []);
            $variants = array_values($input['variant_id'] ?? []);
            $movements = $this->componentMovements($products, $quantities, $unitIds, $wastage, $prices, $variants);

            $production = Production::create([
                'reference_no' => $input['reference_no'] ?? 'production-'.now()->format('Ymd-His-u'),
                'user_id' => $userId,
                'warehouse_id' => $input['warehouse_id'],
                'product_id' => $input['product_id'],
                'item' => count($products),
                'total_qty' => $input['total_qty'] ?? 1,
                'status' => $input['status'] ?? 0,
                'product_list' => implode(',', $products),
                'qty_list' => implode(',', $quantities),
                'price_list' => implode(',', $prices),
                'wastage_percent' => implode(',', $wastage),
                'production_units_ids' => implode(',', $unitIds),
                'total_tax' => 0,
                'total_cost' => collect($movements)->sum('extended_cost'),
                'production_cost' => collect($movements)->sum('extended_cost'),
                'shipping_cost' => $input['shipping_cost'] ?? 0,
                'grand_total' => collect($movements)->sum('extended_cost') + (float) ($input['shipping_cost'] ?? 0),
                'note' => $input['note'] ?? null,
                'created_at' => $input['created_at'] ?? now(),
            ]);

            $this->moveFinished((int) $production->product_id, (int) $production->warehouse_id, (float) $production->total_qty);
            foreach ($movements as $movement) {
                $this->moveComponent($movement, (int) $production->warehouse_id, -1);
            }

            return $production->refresh();
        });
    }

    public function delete(Production $production): void
    {
        if ((int) $production->status === 1) {
            throw new RuntimeException('Finalized production cannot be deleted.');
        }

        DB::transaction(function () use ($production) {
            $products = explode(',', $production->product_list);
            $quantities = explode(',', $production->qty_list);
            $unitIds = explode(',', $production->production_units_ids);
            $prices = explode(',', $production->price_list ?? '');
            $wastage = explode(',', $production->wastage_percent ?? '');
            $movements = $this->componentMovements($products, $quantities, $unitIds, $wastage, $prices, []);

            $this->moveFinished((int) $production->product_id, (int) $production->warehouse_id, -(float) $production->total_qty);
            foreach ($movements as $movement) {
                $this->moveComponent($movement, (int) $production->warehouse_id, 1);
            }
            $production->delete();
        });
    }

    public function componentMovements(array $products, array $quantities, array $unitIds, array $wastage, array $prices, array $variants = []): array
    {
        $result = [];
        foreach ($products as $index => $productId) {
            $unit = Unit::findOrFail($unitIds[$index]);
            $base = $unit->operator === '/'
                ? (float) $quantities[$index] / (float) $unit->operation_value
                : (float) $quantities[$index] * (float) $unit->operation_value;
            $waste = $base * (float) ($wastage[$index] ?? 0) / 100;
            $consumed = $base + $waste;
            $cost = (float) ($prices[$index] ?? Product::findOrFail($productId)->cost);
            $result[] = [
                'product_id' => (int) $productId,
                'variant_id' => !empty($variants[$index]) ? (int) $variants[$index] : null,
                'bom_qty' => (float) $quantities[$index],
                'unit_id' => (int) $unit->id,
                'operator' => $unit->operator,
                'operation_value' => (float) $unit->operation_value,
                'converted_qty' => $base,
                'wastage_percent' => (float) ($wastage[$index] ?? 0),
                'wastage_qty' => $waste,
                'consumed_qty' => $consumed,
                'cost_basis' => $cost,
                'extended_cost' => $consumed * $cost,
            ];
        }
        return $result;
    }

    private function moveFinished(int $productId, int $warehouseId, float $quantity): void
    {
        $product = Product::lockForUpdate()->findOrFail($productId);
        $product->increment('qty', $quantity);
        $warehouse = Product_Warehouse::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'variant_id' => null],
            ['qty' => 0]
        );
        $warehouse->increment('qty', $quantity);
    }

    private function moveComponent(array $movement, int $warehouseId, int $direction): void
    {
        $quantity = $direction * $movement['consumed_qty'];
        Product::lockForUpdate()->findOrFail($movement['product_id'])->increment('qty', $quantity);
        if ($movement['variant_id']) {
            ProductVariant::where('product_id', $movement['product_id'])->where('variant_id', $movement['variant_id'])->lockForUpdate()->firstOrFail()->increment('qty', $quantity);
        }
        Product_Warehouse::where('product_id', $movement['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $movement['variant_id'])
            ->lockForUpdate()->firstOrFail()->increment('qty', $quantity);
    }
}
