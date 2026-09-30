<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDamageStock extends Model
{
    protected $fillable = [
        'damage_stock_id',
        'product_id',
        'variant_id',
        'qty',
        'unit_cost',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function damageStock()
    {
        return $this->belongsTo(DamageStock::class);
    }
}
