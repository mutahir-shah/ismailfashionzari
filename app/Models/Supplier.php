<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable =[
        "name", "image", "company_name", "vat_number", "email", "phone_number", "address", "city", "state", "postal_code", "country", "opening_balance", "pay_term_no", "pay_term_period", "bank_details", "is_active"
    ];

    public function product()
    {
    	return $this->hasMany('App\Models\Product');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function returnPurchases()
    {
        return $this->hasMany(ReturnPurchase::class, 'supplier_id');
    }
}
