<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * A designation can have many employees.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }

    /**
     * Scope for only active designations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
