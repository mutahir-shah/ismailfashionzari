<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use App\Services\Courier\CourierInterface;

class RedxCourier implements CourierInterface
{
    private string $apiToken;
    private string $baseUrl = 'https://openapi.redx.com.bd/v1.0.0-beta';

    public function __construct(Courier $courier)
    {
        $this->apiToken = $courier->api_token ?? '';
    }

    public function createOrder(Delivery $delivery, Sale $sale, Customer $customer, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . '/parcel', [
            'customer_name'    => $customer->name,
            'customer_phone'   => $customer->phone_number,
            'delivery_area'    => $data['address'] ?? '',
            'delivery_area_id' => 1,  // ✅ আপনার area id অনুযায়ী পরিবর্তন করুন
            'merchant_invoice_id' => $delivery->reference_no,
            'cash_collection_amount' => (int) $sale->grand_total,
            'parcel_weight'    => 500, // gram
            'instruction'      => $data['note'] ?? '',
        ]);

        if (!$response->successful()) {
            return [
                'success'  => false,
                'error'    => 'Redx Order Failed',
                'response' => $response->json(),
            ];
        }

        return [
            'success'       => true,
            'tracking_code' => $response->json()['tracking_id'] ?? null,
        ];
    }

    public function trackOrder(string $trackingCode): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type'  => 'application/json',
        ])->get('https://openapi.redx.com.bd/v1.0.0-beta/parcel/track/' . $trackingCode);

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Tracking failed', 'response' => $response->json()];
        }

        $data = $response->json() ?? [];

        return [
            'success'        => true,
            'courier'        => 'Redx',
            'tracking_code'  => $trackingCode,
            'status'         => $data['parcel_status']      ?? 'N/A',
            'recipient_name' => $data['customer_name']      ?? 'N/A',
            'address'        => $data['delivery_area']      ?? 'N/A',
            'amount'         => $data['cash_collection_amount'] ?? 'N/A',
            'updated_at'     => $data['updated_at']         ?? 'N/A',
            'tracking_url'   => 'https://redx.com.bd/track-parcel/?trackingId=' . $trackingCode,
        ];
    }
}
