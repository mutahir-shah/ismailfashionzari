<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;

class PaperflyCourier implements CourierInterface
{
    private string $username;
    private string $password;
    private string $baseUrl = 'https://restapi.paperfly.com.bd/api/merchant';

    public function __construct(Courier $courier)
    {
        $this->username = $courier->username ?? '';
        $this->password = $courier->password ?? '';
    }

    public function createOrder(Delivery $delivery, Sale $sale, Customer $customer, array $data): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/shipment', [
            'CN'   => [[
                'SHIPMENT_ID'       => $delivery->reference_no,
                'RECEIVER_NAME'     => $customer->name,
                'RECEIVER_MOBILE'   => $customer->phone_number,
                'RECEIVER_ADDRESS'  => $data['address'] ?? '',
                'COD_AMOUNT'        => (int) $sale->grand_total,
                'PRODUCT_QUANTITY'  => 1,
            ]],
            'USER_NAME' => $this->username,
            'USER_PASSWORD' => $this->password,
        ]);

        if (!$response->successful()) {
            return [
                'success'  => false,
                'error'    => 'Paperfly Order Failed',
                'response' => $response->json(),
            ];
        }

        return [
            'success'       => true,
            'tracking_code' => $response->json()['CN'][0]['CN_ID'] ?? null,
        ];
    }

    public function trackOrder(string $trackingCode): array
    {
        // Paperfly এর public tracking API নেই, তাই tracking URL দিয়ে redirect করো
        return [
            'success'       => true,
            'courier'       => 'Paperfly',
            'tracking_code' => $trackingCode,
            'status'        => 'Check on Paperfly website',
            'tracking_url'  => 'https://paperfly.com.bd/track?cn=' . $trackingCode,
        ];
    }
}
