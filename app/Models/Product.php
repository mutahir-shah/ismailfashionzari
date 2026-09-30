<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable =[
        "name", "code", "type", "slug", "barcode_symbology", "brand_id", "category_id", "unit_id", "purchase_unit_id", "sale_unit_id", "cost", "profit_margin", "profit_margin_type", "price", "wholesale_price", "qty", "alert_quantity", "daily_sale_objective", "promotion", "promotion_price", "starting_date", "last_date", "tax_id", "tax_method", "image", "file", "is_embeded", "is_batch", "is_variant", "is_diffPrice", "is_imei", "featured", "product_list", "variant_list", "qty_list", "price_list", "product_details", "short_description", "specification", "related_products", "is_addon", "extras", "menu_type", "variant_option", "variant_value", "is_active", "is_online", "kitchen_id", "in_stock", "track_inventory", "is_sync_disable", "woocommerce_product_id","woocommerce_media_id","tags","meta_title","meta_description", "warranty", "guarantee", "warranty_type", "guarantee_type","wastage_percent","combo_unit_id","production_cost","is_recipe"
    ];

    public function category()
    {
    	return $this->belongsTo('App\Models\Category');
    }

    public function brand()
    {
    	return $this->belongsTo('App\Models\Brand');
    }

    public function tax()
    {
        return $this->belongsTo('App\Models\Tax');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }

    public function variant()
    {
        return $this->belongsToMany('App\Models\Variant', 'product_variants')->withPivot('id', 'item_code', 'additional_cost', 'additional_price', 'qty');
    }

    public function scopeActive($query)
    {
        return $query->where('products.is_active', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('products.is_active', 3);
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereIn('products.is_active', [1, 3]);
    }

    public function scopeActiveStandard($query)
    {
        return $query->where([
            ['products.is_active', 1],
            ['products.type', 'standard']
        ]);
    }

    public function scopeActiveFeatured($query)
    {
        return $query->where([
            ['products.is_active', 1],
            ['products.featured', 1]
        ]);
    }

    public function products()
    {
        return $this->belongsToMany(Warehouse::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class)->withPivot('qty');
    }

    public function purchases()
    {
        return $this->belongsToMany(Purchase::class,'product_purchases')->withPivot('qty','tax','tax_rate','discount','total');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Models\Sale', 'product_sales')->withPivot('qty','product_batch_id', 'return_qty','net_unit_price','tax','discount','tax_rate','total','is_delivered');
    }

    public function returns()
    {
        return $this->belongsToMany('App\Models\ProductReturn', 'product_returns');
    }

    protected $casts = [
        'profit_margin_type' => 'string',
    ];

}
