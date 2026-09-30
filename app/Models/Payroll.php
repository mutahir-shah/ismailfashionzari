<?php

namespace App\Models;

use App\Traits\PayrollWarehouseScoped;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use PayrollWarehouseScoped;

    protected $fillable = [
        "reference_no", "employee_id", "account_id", "user_id",
        "amount", "paying_method", "note", "created_at",
        "status", "amount_array","month", "accounting_status"
    ];

   

    public function employee()
    {
    	return $this->belongsTo('App\Models\Employee');
    }
}
