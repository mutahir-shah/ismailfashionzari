<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $table = 'device_types';

    protected $fillable = [
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: only active records
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
