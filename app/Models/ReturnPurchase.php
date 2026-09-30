<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnPurchase extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $table = 'return_purchases';
    protected $fillable =[
        "reference_no", "purchase_id", "user_id", "supplier_id", "warehouse_id", "account_id", "currency_id", "exchange_rate", "item", "total_qty", "total_discount", "total_tax", "total_cost","order_tax_rate", "order_tax", "grand_total", "document", "return_note", "staff_note"
    ];

    public function supplier()
    {
    	return $this->belongsTo('App\Models\Supplier');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function refundPayments()
    {
        return $this->hasMany(Payment::class, 'purchase_return_id');
    }

    public function getRefundedAmountAttribute()
    {
        if ($this->relationLoaded('refundPayments')) {
            return $this->refundPayments->sum('amount');
        }

        return Payment::where('purchase_return_id', $this->id)->sum('amount');
    }

    public function getDisplayGrandTotalAttribute()
    {
        return $this->refunded_amount > 0 ? $this->refunded_amount : $this->grand_total;
    }
}
