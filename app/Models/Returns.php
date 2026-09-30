<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
	use \App\Traits\WarehouseScoped;

	protected $table = 'returns';
    protected $fillable =[
        "reference_no", "user_id", "sale_id", "cash_register_id", "customer_id", "warehouse_id", "biller_id", "account_id", "currency_id", "exchange_rate", "item", "total_qty", "total_discount", "total_tax", "total_price","order_tax_rate", "order_tax", "grand_total", "document", "return_note", "staff_note", "created_at"
    ];

    public function biller()
    {
    	return $this->belongsTo('App\Models\Biller');
    }

    public function customer()
    {
    	return $this->belongsTo('App\Models\Customer');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function products()
    {
         return $this->hasMany('App\Models\ProductReturn','return_id');
    }

    public function refundPayments()
    {
        return $this->hasMany(Payment::class, 'return_id')->whereNotNull('sale_id');
    }

    public function getRefundedAmountAttribute()
    {
        if ($this->relationLoaded('refundPayments')) {
            return $this->refundPayments->sum('amount');
        }

        return Payment::where('return_id', $this->id)->whereNotNull('sale_id')->sum('amount');
    }

    public function getDisplayGrandTotalAttribute()
    {
        return $this->refunded_amount > 0 ? $this->refunded_amount : $this->grand_total;
    }
}
