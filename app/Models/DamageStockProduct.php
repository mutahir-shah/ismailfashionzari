<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageStockProduct extends Model
{
    protected $fillable = [
        'damage_stock_id',
        'product_id',
        'warehouse_id',
        'qty',
        'sale_unit',
        'sale_unit_id',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'qty'        => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function damageStock()
    {
        return $this->belongsTo(DamageStock::class);
    }
}
