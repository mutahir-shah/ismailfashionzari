<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCatelogSetting extends Model
{
    protected $table = 'qr_catelog_settings';

    protected $fillable = [
        'show_stock_out_product',
        'customer_details',
    ];

    protected $casts = [
        'show_stock_out_product' => 'boolean',
        'customer_details' => 'boolean',
    ];
}
