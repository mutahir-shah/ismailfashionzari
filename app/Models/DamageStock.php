<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DamageStock extends Model
{
    use SoftDeletes, \App\Traits\WarehouseScoped;

    protected $fillable = [
        'reference_no',
        'warehouse_id',
        'user_id',
        'damaged_at',
        'note',
        'document',
    ];

    protected $casts = [
        'damaged_at' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(DamageStockProduct::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    public static function generateReferenceNo(): string
    {
        $prefix = 'DMG-' . now()->format('Ymd') . '-';
        $last   = static::withTrashed()
                        ->where('reference_no', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->value('reference_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->products->sum('total_cost');
    }

    public function getTotalQtyAttribute(): float
    {
        return $this->products->sum('qty');
    }
}
