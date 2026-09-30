<?php

namespace Modules\Manufacturing\Entities;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Production extends Model
{
    use HasFactory, \App\Traits\WarehouseScoped;
    protected $fillable =[
        "reference_no",
        "user_id",
        "warehouse_id",
        "item",
        "total_qty",
        "total_tax",
        "total_cost",
        "shipping_cost",
        "grand_total",
        "status",
        "document",
        "note",
        "created_at",
        "production_cost",
        'production_units_ids',
        'wastage_percent',
        'product_list',
        'product_id',
        'variant_list',
        'qty_list',
        'price_list',
    ];

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function product()
    {
    	return $this->belongsTo(Product::class,'product_id');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    protected static function newFactory()
    {
        return \Modules\Manufacturing\Database\factories\ProductionFactory::new();
    }
}
