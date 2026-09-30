<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable =[
        "customer_group_id", "user_id", "name", "company_name",
        "email", "type", "phone_number", "wa_number", "tax_no", "address", "city",
        "state", "postal_code", "country", "opening_balance", "credit_limit", "points", "deposit", "pay_term_no","pay_term_period", "expense", "wishlist", "is_active",
        "zatca_registration_scheme", "zatca_registration_number", "zatca_building_number", "zatca_additional_number", "zatca_district"
    ];

    protected static function booted(): void
    {
        static::saving(function (Customer $customer) {
            if ($customer->type === 'walkin' || $customer->type === \App\Enums\CustomerTypeEnum::WALKIN->value) {
                $customer->credit_limit = 0;
                $customer->pay_term_no = null;
                $customer->pay_term_period = 'days';
            }
        });
    }

    public function customerGroup()
    {
        return $this->belongsTo('App\Models\CustomerGroup');
    }

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function discountPlans()
    {
        return $this->belongsToMany('App\Models\DiscountPlan', 'discount_plan_customers');
    }

    public function points(){
        return $this->hasMany(Point::class,'customer_id');
    }
}
