<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductExchange extends Model
{
    protected $table = 'product_exchanges';

    protected $fillable = [
        'exchange_id',
        'product_id',
        'variant_id',
        'qty',
        'sale_unit_id',
        'net_unit_price',
        'discount',
        'tax_rate',
        'tax',
        'total',
        'type',
    ];

    protected $casts = [
        'qty' => 'float',
        'net_unit_price' => 'float',
        'discount' => 'float',
        'tax_rate' => 'float',
        'tax' => 'float',
        'total' => 'float',
    ];

    /**
     * Relationships
     */
    public function exchange()
    {
        return $this->belongsTo(SaleExchange::class, 'exchange_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    /**
     * Scopes
     */
    public function scopeNewProducts($query)
    {
        return $query->where('type', 'new');
    }

    public function scopeReturnedProducts($query)
    {
        return $query->where('type', 'returned');
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Accessors
     */
    public function getIsNewAttribute()
    {
        return $this->type === 'new';
    }

    public function getIsReturnedAttribute()
    {
        return $this->type === 'returned';
    }

    /**
     * Calculate subtotal before tax
     */
    public function getSubtotalAttribute()
    {
        return $this->qty * $this->net_unit_price - $this->discount;
    }
}
