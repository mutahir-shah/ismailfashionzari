<?php

namespace Modules\SocialCommerce\Entities;

use Illuminate\Database\Eloquent\Model;

class SocialCommerceClick extends Model
{
    protected $fillable = [
        'product_id',
        'source',
        'campaign',
        'session_id',
        'clicked_at'
    ];
}
