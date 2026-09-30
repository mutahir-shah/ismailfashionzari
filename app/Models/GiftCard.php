<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
     protected $fillable =[
        "card_no", 
        "amount", 
        "expense", 
        "customer_id", 
        "user_id", 
        "expired_date", 
        "created_by", 
        "is_active"
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
