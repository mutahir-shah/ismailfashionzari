<?php

namespace Modules\SocialCommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocialProductSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'is_published',
        'social_title',
        'social_description',
        'social_image'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
