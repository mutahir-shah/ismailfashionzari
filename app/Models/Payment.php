<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable =[
        "purchase_id", "user_id", "sale_id", "return_id", "purchase_return_id", "cash_register_id", "account_id","payment_receiver", "payment_reference", "amount", "currency_id", "installment_id", "exchange_rate", "payment_at", "used_points", "change", "paying_method", "payment_proof", "document", "payment_note","service_job_id", "accounting_status"
    ];

    protected $casts = [
        'payment_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(Returns::class, 'return_id');
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(ReturnPurchase::class, 'purchase_return_id');
    }
}
