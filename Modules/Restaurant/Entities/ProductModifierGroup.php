<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierGroup extends Model
{
    protected $table = 'product_modifier_groups';

    protected $fillable = [
        'product_id',
        'modifier_group_id',
        'sort_order',
        'min_selection_override',
        'max_selection_override',
        'is_required_override',
    ];

    protected $casts = [
        'sort_order'             => 'integer',
        'min_selection_override' => 'integer',
        'max_selection_override' => 'integer',
        'is_required_override'   => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The product this assignment belongs to.
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * The modifier group being attached.
     */
    public function modifierGroup()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    /**
     * Per-product modifier pricing/ingredient entries for this product+group combination.
     */
    public function modifierEntries()
    {
        return $this->hasMany(
            ProductModifierGroupModifier::class,
            'modifier_group_id',
            'modifier_group_id'
        )->where('product_id', $this->product_id)
         ->orderBy('sort_order');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve effective min_selection, respecting the per-product override.
     */
    public function effectiveMinSelection(): int
    {
        return $this->min_selection_override
            ?? $this->modifierGroup->min_selection;
    }

    /**
     * Resolve effective max_selection, respecting the per-product override.
     */
    public function effectiveMaxSelection(): int
    {
        return $this->max_selection_override
            ?? $this->modifierGroup->max_selection;
    }

    /**
     * Resolve effective is_required, respecting the per-product override.
     */
    public function effectiveIsRequired(): bool
    {
        return $this->is_required_override
            ?? (bool) $this->modifierGroup->is_required;
    }
}
