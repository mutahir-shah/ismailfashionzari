<?php

namespace Modules\Restaurant\Services;

use App\Models\Product;
use App\Models\Product_Warehouse;
use Modules\Restaurant\Entities\ProductSaleModifier;

class ModifierInventoryService
{
    /**
     * Apply a persisted modifier snapshot to inventory.
     *
     * Direction -1 deducts stock; direction +1 restores stock.
     */
    public function adjustSnapshot(
        ProductSaleModifier $modifier,
        float $saleQuantity,
        int $warehouseId,
        int $direction
    ): void {
        if (!in_array($direction, [-1, 1], true) || !$modifier->hasIngredients()) {
            return;
        }

        $productIds = $modifier->ingredientProductIds();
        $quantities = $modifier->ingredientQtys();

        foreach ($productIds as $index => $productId) {
            $ingredientQuantity = (float) ($quantities[$index] ?? 1);
            $adjustment = $direction * $ingredientQuantity * $saleQuantity;

            $product = Product::where('id', $productId)
                ->where('type', 'standard')
                ->lockForUpdate()
                ->first();

            if (!$product) {
                continue;
            }

            $product->increment('qty', $adjustment);

            $warehouse = Product_Warehouse::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->whereNull('variant_id')
                ->whereNull('product_batch_id')
                ->lockForUpdate()
                ->first();

            if ($warehouse) {
                $warehouse->increment('qty', $adjustment);
            }
        }
    }
}
