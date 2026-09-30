<?php

namespace App\Services\Courier;

use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Customer;

interface CourierInterface
{
    public function createOrder(
        Delivery $delivery,
        Sale $sale,
        Customer $customer,
        array $data
    ): array;


    public function trackOrder(string $trackingCode): array;
}
