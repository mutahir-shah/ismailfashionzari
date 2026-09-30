<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardPointSetting extends Model
{
    protected $fillable = [
        "per_point_amount",
        "minimum_amount",
        "duration",
        "type",
        "is_active",
        "redeem_amount_per_unit_rp",
        "min_order_total_for_redeem",
        "min_redeem_point",
        "max_redeem_point"
    ];
}
