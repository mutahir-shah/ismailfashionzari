<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreshBusinessResetManifest extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'affected_tables' => 'array', 'before_counts' => 'array', 'after_counts' => 'array',
        'balances_before' => 'array', 'balances_after' => 'array',
        'activation_before' => 'array', 'activation_after' => 'array', 'reset_at' => 'datetime',
    ];
}
