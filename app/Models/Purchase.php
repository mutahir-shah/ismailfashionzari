<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes, \App\Traits\WarehouseScoped;

    public const NON_SUPPLIER_OPENING_TYPES = [
        'opening balance',
        'Opening balance',
        'initial_stock',
    ];

    protected $fillable =[

        "reference_no", "user_id", "warehouse_id", "supplier_id", "currency_id", "exchange_rate", "item", "total_qty", "total_discount", "total_tax", "total_cost", "order_tax_rate", "order_tax", "order_discount", "shipping_cost", "grand_total","paid_amount", "status", "payment_status", "accounting_status", "document", "note", "purchase_type", "created_at", "deleted_by",'pay_term_no','pay_term_period','due_date',
    ];

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function supplier()
    {
    	return $this->belongsTo('App\Models\Supplier');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnPurchase::class,'purchase_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class,'product_purchases')->withPivot('qty','tax','tax_rate','discount','total');
    }

    public function getCreatedAtFormattedAttribute()
    {
        $dateFormat = GeneralSetting::first()->date_format;
        return Carbon::parse($this->attributes['created_at'])->format($dateFormat);
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withDefault([
            'name' => 'System/Unknown'
        ]);
    }

    public function installmentPlan()
    {
        return $this->morphOne(InstallmentPlan::class, 'reference');
    }

    public function scopeExcludeNonSupplierOpening($query, string $column = 'purchase_type')
    {
        return $query->where(function ($q) use ($column) {
            $q->whereNull($column)
                ->orWhereNotIn($column, self::NON_SUPPLIER_OPENING_TYPES);
        });
    }
}
