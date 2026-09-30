<?php

namespace App\Services\Courier;

class CourierStatusMapper
{
    // Courier status → Delivery status (1=Packing, 2=Delivering, 3=Delivered)
    private static array $pathaoMap = [
        'Pending'           => 2, // Delivering
        'Pickup_Requested'  => 2,
        'Picked_Up'         => 2,
        'In_Transit'        => 2,
        'At_Hub'            => 2,
        'Out_For_Delivery'  => 2,
        'Delivered'         => 3,
        'Partial_Delivered' => 3,
        'Cancelled'         => 1, // Back to Packing
        'On_Hold'           => 2,
        'Unknown'           => 2,
    ];

    private static array $steadfastMap = [
        'in_review'                          => 2, // Delivering
        'pending'                            => 2,
        'hold'                               => 2,
        'delivered_approval_pending'         => 3,
        'partial_delivered_approval_pending' => 3,
        'delivered'                          => 3,
        'partial_delivered'                  => 3,
        'cancelled_approval_pending'         => 1,
        'cancelled'                          => 1,
        'unknown_approval_pending'           => 2,
        'unknown'                            => 2,
    ];

    public static function toDeliveryStatus(string $courierType, string $courierStatus): int
    {
        return match (strtolower($courierType)) {
            'pathao'    => self::$pathaoMap[$courierStatus]    ?? 2,
            'steadfast' => self::$steadfastMap[$courierStatus] ?? 2,
            default     => 2,
        };
    }
}
