<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleExchange extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $table = 'sale_exchanges';

    protected $fillable = [
        'sale_id',
        'reference_no',
        'customer_id',
        'user_id',
        'warehouse_id',
        'biller_id',
        'item',
        'total_qty',
        'total_discount',
        'total_tax',
        'amount',              // Fixed: was 'total_price' but migration has 'amount'
        'payment_type',
        'order_tax_rate',
        'order_tax',
        'grand_total',
        'document',
        'exchange_note',
        'staff_note',
    ];

    protected $casts = [
        'total_qty' => 'float',
        'total_discount' => 'float',
        'total_tax' => 'float',
        'amount' => 'float',
        'order_tax_rate' => 'float',
        'order_tax' => 'float',
        'grand_total' => 'float',
    ];

    /**
     * Relationships
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function biller()
    {
        return $this->belongsTo(Biller::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(ProductExchange::class, 'exchange_id');
    }

    /**
     * Get new products in this exchange
     */
    public function newProducts()
    {
        return $this->hasMany(ProductExchange::class, 'exchange_id')
            ->where('type', 'new');
    }

    /**
     * Get returned products in this exchange
     */
    public function returnedProducts()
    {
        return $this->hasMany(ProductExchange::class, 'exchange_id')
            ->where('type', 'returned');
    }

    /**
     * Scopes
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'sale',
            'customer',
            'warehouse',
            'biller',
            'user',
            'products.product'
        ]);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
