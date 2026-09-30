<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    protected $table = 'modifier_groups';

    protected $fillable = [
        'name',
        'selection_type',
        'min_selection',
        'max_selection',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_selection' => 'integer',
        'max_selection' => 'integer',
        'is_required'   => 'boolean',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * All modifiers belonging to this group (global, price-agnostic).
     */
    public function modifiers()
    {
        return $this->hasMany(Modifier::class, 'modifier_group_id')
                    ->orderBy('sort_order');
    }

    /**
     * Products this group is attached to.
     */
    public function products()
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            'product_modifier_groups',
            'modifier_group_id',
            'product_id'
        )->withPivot([
            'sort_order',
            'min_selection_override',
            'max_selection_override',
            'is_required_override',
        ])->withTimestamps();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve effective min_selection for a given product, respecting overrides.
     */
    public function effectiveMinSelection(?int $override): int
    {
        return $override ?? $this->min_selection;
    }

    /**
     * Resolve effective max_selection for a given product, respecting overrides.
     */
    public function effectiveMaxSelection(?int $override): int
    {
        return $override ?? $this->max_selection;
    }

    /**
     * Resolve effective is_required for a given product, respecting overrides.
     */
    public function effectiveIsRequired(?bool $override): bool
    {
        return $override ?? (bool) $this->is_required;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
