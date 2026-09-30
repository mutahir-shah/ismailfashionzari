<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'user_id',
        'device'
    ];
}
