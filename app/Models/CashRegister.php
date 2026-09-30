<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $fillable = ["cash_in_hand", "closing_balance", "actual_cash", "user_id", "warehouse_id", "status"];

    public function user()
    {
    	return $this->belongsTo('App\Models\User');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }
}
