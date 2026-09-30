<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'amount',
        'type',
        'description',
        'created_by',
    ];

    // Relation with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relation with User (created_by)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
