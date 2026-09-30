<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;
    protected $table = 'shifts';
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'grace_in',
        'grace_out',
        'total_hours',
        'is_active',
    ];

    /**
     * A shift can have many employees.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'shift_id');
    }
}
