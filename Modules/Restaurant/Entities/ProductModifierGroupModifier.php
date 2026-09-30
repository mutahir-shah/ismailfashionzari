<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierGroupModifier extends Model
{
    protected $table = 'product_modifier_group_modifiers';

    protected $fillable = [
        'product_id',
        'modifier_group_id',
        'modifier_id',
        'price_adjustment',
        'product_list',
        'qty_list',
        'variant_list',
        'wastage_percent',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_adjustment' => 'float',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The product this entry belongs to.
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * The modifier group.
     */
    public function modifierGroup()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    /**
     * The modifier (global definition — name only, no price).
     */
    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse ingredient product IDs from the CSV column.
     * Returns an empty array when no ingredients are configured.
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
     * Parse ingredient quantities from the CSV column.
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
     * Parse wastage percentages from the CSV column.
     *
     * @return float[]
     */
    public function wastagePercents(): array
    {
        if (empty($this->wastage_percent)) {
            return [];
        }
        return array_map('floatval', explode(',', $this->wastage_percent));
    }

    /**
     * Parse variant IDs from the CSV column.
     * Returns 0 for positions without a variant.
     *
     * @return int[]
     */
    public function variantIds(): array
    {
        if (empty($this->variant_list)) {
            return [];
        }
        return array_map('intval', explode(',', $this->variant_list));
    }

    /**
     * Returns true when this modifier has at least one ingredient to deduct.
     */
    public function hasIngredients(): bool
    {
        return !empty($this->product_list);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to a specific product+group combination.
     */
    public function scopeForProductGroup($query, int $productId, int $groupId)
    {
        return $query->where('product_id', $productId)
                     ->where('modifier_group_id', $groupId);
    }
}
