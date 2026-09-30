<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Services\Courier\CourierInterface;

class CourierManager
{
    public static function resolve(Courier $courier): CourierInterface|null
    {
        return match (strtolower($courier->type ?? '')) {
            'pathao'    => new PathaoCourier($courier),
            'steadfast' => new SteadfastCourier($courier),
            // 'ecourier' => new ECourierCourier($courier),
            default     => null,
        };
    }
}
