<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductSaleModifier extends Model
{
    protected $table = 'product_sale_modifiers';

    protected $fillable = [
        'product_sale_id',
        'modifier_group_id',
        'modifier_id',
        'modifier_group_name',
        'modifier_name',
        'price_adjustment',
        'product_list',
        'qty_list',
        'kitchen_id',
    ];

    protected $casts = [
        'price_adjustment' => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The product_sale line item this modifier belongs to.
     */
    public function productSale()
    {
        return $this->belongsTo(\App\Models\Product_Sale::class, 'product_sale_id');
    }

    /**
     * The modifier group (soft reference — group may be edited after sale).
     * Use snapshot fields (modifier_group_name) for display.
     */
    public function modifierGroup()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    /**
     * The modifier (soft reference — modifier may be edited after sale).
     * Use snapshot fields (modifier_name, price_adjustment) for display.
     */
    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    /**
     * The kitchen this modifier item should be prepared in.
     * NULL = inherit from parent product's kitchen_id.
     */
    public function kitchen()
    {
        return $this->belongsTo(Kitchens::class, 'kitchen_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse ingredient product IDs from the snapshot CSV.
     * Used by SaleController to reverse ingredient deductions on order edit.
     *
     * @return int[]
     */
    public function ingredientProductIds(): array
    {
        if (empty($this->product_list)) {
            return [];
        }
        return array_map('intval', explode(',', $this->product_list));
    }

    /**
     * Parse ingredient quantities from the snapshot CSV.
     *
     * @return float[]
     */
    public function ingredientQtys(): array
    {
        if (empty($this->qty_list)) {
            return [];
        }
        return array_map('floatval', explode(',', $this->qty_list));
    }

    /**
     * Returns true when this modifier selection has ingredients to deduct.
     */
    public function hasIngredients(): bool
    {
        return !empty($this->product_list);
    }

    /**
     * Build a display label for KDS and invoices.
     * Example: "Size: Large"
     */
    public function displayLabel(): string
    {
        return $this->modifier_group_name . ': ' . $this->modifier_name;
    }

    // ── Static Helpers ───────────────────────────────────────────────────────

    /**
     * Build a ProductSaleModifier array ready for bulk insert from a
     * ProductModifierGroupModifier configuration row.
     *
     * @param  int  $productSaleId
     * @param  ProductModifierGroupModifier  $config
     * @return array
     */
    public static function fromConfig(int $productSaleId, ProductModifierGroupModifier $config): array
    {
        return [
            'product_sale_id'     => $productSaleId,
            'modifier_group_id'   => $config->modifier_group_id,
            'modifier_id'         => $config->modifier_id,
            'modifier_group_name' => $config->modifierGroup->name ?? '',
            'modifier_name'       => $config->modifier->name ?? '',
            'price_adjustment'    => $config->price_adjustment,
            'product_list'        => $config->product_list,
            'qty_list'            => $config->qty_list,
            'kitchen_id'          => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];
    }
}
