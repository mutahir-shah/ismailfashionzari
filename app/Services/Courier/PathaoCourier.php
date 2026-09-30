<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;

class PathaoCourier implements CourierInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $username;
    private string $password;

    public function __construct(Courier $courier)
    {
        $this->baseUrl      = $courier->base_url      ?? 'https://courier-api-sandbox.pathao.com';
        $this->clientId     = $courier->client_id     ?? '';
        $this->clientSecret = $courier->client_secret ?? '';
        $this->username     = $courier->username      ?? '';
        $this->password     = $courier->password      ?? '';
    }

    private function getToken(): string|null
    {
        $response = Http::post($this->baseUrl . '/aladdin/api/v1/issue-token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'password',
            'username'      => $this->username,
            'password'      => $this->password,
        ]);
        return $response->successful() ? $response['access_token'] : null;
    }

    private function getStoreId(string $token): int|null
    {
        $response = Http::withToken($token)
            ->get($this->baseUrl . '/aladdin/api/v1/stores');

        return $response->successful()
            ? ($response->json()['data']['data'][0]['store_id'] ?? null)
            : null;
    }

    public function createOrder(Delivery $delivery, Sale $sale, Customer $customer, array $data): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Pathao Token not found'];
        }

        $storeId = $this->getStoreId($token);
        if (!$storeId) {
            return ['success' => false, 'error' => 'Pathao Store ID not found'];
        }

        $address = strlen($data['address'] ?? '') >= 10
            ? $data['address']
            : 'Halishahar, Chattogram, Bangladesh';

        $requestData = [
            'store_id'          => $storeId,
            'merchant_order_id' => $delivery->reference_no,
            'recipient_name'    => $customer->name,
            'recipient_phone'   => $customer->phone_number,
            'recipient_address' => $address,
            'amount_to_collect' => (int) $sale->grand_total,
            'delivery_type'     => 48,
            'item_quantity'     => 1,
            'item_weight'       => 1,
            'item_type'         => 2,
        ];

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/aladdin/api/v1/orders', $requestData);

        if (!$response->successful()) {
            return [
                'success'      => false,
                'error'        => 'Pathao Order Failed',
                'response'     => $response->json(),
                'request_data' => $requestData,
            ];
        }

        return [
            'success'       => true,
            'tracking_code' => $response->json()['data']['consignment_id'] ?? null,
        ];
    }

    public function trackOrder(string $trackingCode): array
{
    $token = $this->getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'Pathao Token পাওয়া যায়নি'];
    }

    $response = Http::withToken($token)
        ->get($this->baseUrl . '/aladdin/api/v1/orders/' . $trackingCode);

    if (!$response->successful()) {
        return ['success' => false, 'error' => 'Tracking failed', 'response' => $response->json()];
    }


    $data = $response->json()['data'] ?? [];

    return [
        'success'        => true,
        'courier'        => 'Pathao',
        'tracking_code'  => $trackingCode,
        'status'         => $data['order_status'] ?? 'N/A',
        'recipient_name' => $data['recipient_name'] ?? 'N/A',
        'address'        => $data['recipient_address'] ?? 'N/A', 
        'amount'         => $data['order_amount'] ?? 'N/A',
        'updated_at'     => $data['order_status_updated_at'] ?? 'N/A',

        'tracking_url'   => 'https://merchant.pathao.com/tracking?consignment_id='
            . $trackingCode . '&phone=' . ($data['recipient_phone'] ?? ''),
    ];
}
}
