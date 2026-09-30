<?php

namespace App\Models;

use App\Traits\WarehouseScoped;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use WarehouseScoped;

    protected $fillable = [
        "name",
        "image",
        "department_id",
        "designation_id",
        "shift_id",
        "basic_salary",
        "email",
        "phone_number",
        "user_id",
        "staff_id",
        "address",
        "city",
        "country",
        "is_active",
        "is_sale_agent",
        "sale_commission_percent",
        "sales_target",
        'warehouse_id',
        'company_id'
    ];

    protected $casts = [
        'sales_target' => 'array',
    ];

    public function payroll()
    {
        return $this->hasMany('App\Models\Payroll');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function projects()
    {
        return $this->belongsToMany(\Modules\Project\Entities\Project::class, 'employee_project', 'employee_id', 'project_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(\Modules\Project\Entities\Task::class, 'employee_task', 'employee_id', 'task_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
