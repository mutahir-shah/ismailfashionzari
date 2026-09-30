<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyTransfer extends Model
{
    protected $fillable = [
        'reference_no',
        'from_account_id',
        'to_account_id',
        'amount',
        'currency_id',
        'exchange_rate',
        'note',
        'created_at',
    ];

    public function fromAccount()
    {
    	return $this->belongsTo('App\Models\Account');
    }

    public function toAccount()
    {
    	return $this->belongsTo('App\Models\Account');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency');
    }
}
