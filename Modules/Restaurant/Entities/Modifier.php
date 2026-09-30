<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    protected $table = 'modifiers';

    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_adjustment',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
        'price_adjustment' => 'decimal:4',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The group this modifier belongs to.
     */
    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    /**
     * Per-product pricing and ingredient rows for this modifier.
     * One row per (product, modifier_group, modifier) combination.
     */
    public function productEntries()
    {
        return $this->hasMany(ProductModifierGroupModifier::class, 'modifier_id');
    }

    /**
     * Products that have this modifier configured (via the 3-way pivot).
     */
    public function products()
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            'product_modifier_group_modifiers',
            'modifier_id',
            'product_id'
        )->withPivot([
            'modifier_group_id',
            'price_adjustment',
            'product_list',
            'qty_list',
            'variant_list',
            'wastage_percent',
            'is_active',
            'sort_order',
        ])->withTimestamps();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
