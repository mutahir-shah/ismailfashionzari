<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table = 'leaves';
    protected $fillable = [
        'employee_id',
        'leave_types',
        'start_date',
        'end_date',
        'days',
        'status',
        'approver_id'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class,'leave_types');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
