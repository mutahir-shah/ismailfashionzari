<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use \App\Traits\WarehouseScoped;

    protected $fillable =[
        "reference_no", "expense_category_id", "warehouse_id", "account_id",
        "user_id", "cash_register_id", "employee_id", "type",
        "amount", "tax_id", "tax_name", "tax_rate", "tax", "note","document", "created_at", "accounting_status"
    ];


    public function warehouse()
    {
    	return $this->belongsTo('App\Models\Warehouse');
    }

    public function expenseCategory() {
    	return $this->belongsTo('App\Models\ExpenseCategory');
    }

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee');
    }

    public function tax()
    {
        return $this->belongsTo('App\Models\Tax');
    }

}
