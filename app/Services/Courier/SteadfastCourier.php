<?php

namespace App\Services\Courier;

use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastCourier implements CourierInterface
{
    private string $apiKey;
    private string $secretKey;
    private string $baseUrl = 'https://portal.packzy.com/api/v1';

    public function __construct(Courier $courier)
    {
        $this->apiKey    = trim($courier->api_key    ?? '');
        $this->secretKey = trim($courier->secret_key ?? '');
    }

    private function headers(): array
    {
        return [
            'Api-Key'      => $this->apiKey,
            'Secret-Key'   => $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    private function statusLabel(string $status): string
    {
        $labels = [
            'pending'                            => 'Pending',
            'delivered_approval_pending'         => 'Delivered (Approval Pending)',
            'partial_delivered_approval_pending' => 'Partially Delivered (Approval Pending)',
            'cancelled_approval_pending'         => 'Cancelled (Approval Pending)',
            'unknown_approval_pending'           => 'Unknown (Approval Pending)',
            'delivered'                          => 'Delivered',
            'partial_delivered'                  => 'Partially Delivered',
            'cancelled'                          => 'Cancelled',
            'hold'                               => 'On Hold',
            'in_review'                          => 'In Review',
            'unknown'                            => 'Unknown',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public function createOrder(Delivery $delivery, Sale $sale, Customer $customer, array $data): array
    {
        $address = $data['address'] ?? '';
        if (strlen($address) < 10) {
            $address = "Halishahar, Chattogram, Bangladesh";
        }

        $requestData = [
            'invoice'           => $delivery->reference_no,
            'recipient_name'    => $customer->name,
            'recipient_phone'   => $customer->phone_number,
            'recipient_address' => $address,
            'cod_amount'        => (int) $sale->grand_total,
            'note'              => $data['note'] ?? '',
        ];

        $response = Http::withHeaders($this->headers())
            ->asJson()
            ->post($this->baseUrl . '/create_order', $requestData);

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Order Failed' => true,
                'Status'                   => $response->status(),
                'Response'                 => $response->body(),
                'Request Data'             => $requestData,
                'API Key'                  => $this->apiKey,
            ];
            Log::error('Steadfast Order Failed', $errorData);
            Log::info('Steadfast Order Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        $resData = $response->json();

        return [
            'success'        => true,
            'tracking_code'  => $resData['consignment']['tracking_code']  ?? null,
            'consignment_id' => $resData['consignment']['consignment_id'] ?? null,
            'status'         => $resData['consignment']['status']          ?? null,
        ];
    }

    public function checkBalance(): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/get_balance');

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Balance Failed' => true,
                'Status'                     => $response->status(),
                'Response'                   => $response->body(),
            ];
            Log::error('Steadfast Balance Failed', $errorData);
            Log::info('Steadfast Balance Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        return [
            'success'         => true,
            'current_balance' => $response->json()['current_balance'] ?? 0,
        ];
    }

    public function trackOrder(string $trackingCode): array
    {
        return $this->trackByTrackingCode($trackingCode);
    }

    public function trackByTrackingCode(string $trackingCode): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/status_by_trackingcode/' . $trackingCode);

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Track Failed' => true,
                'Status'                   => $response->status(),
                'Response'                 => $response->body(),
                'Tracking Code'            => $trackingCode,
            ];
            Log::error('Steadfast Track Failed', $errorData);
            Log::info('Steadfast Track Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        $status = $response->json()['delivery_status'] ?? 'unknown';

        return [
            'success'       => true,
            'courier'       => 'Steadfast',
            'tracking_code' => $trackingCode,
            'status'        => $status,
            'status_label'  => $this->statusLabel($status),
            'tracking_url'  => 'https://steadfast.com.bd/t/' . $trackingCode,
        ];
    }

    public function trackByConsignmentId(int $consignmentId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/status_by_cid/' . $consignmentId);

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Track by CID Failed' => true,
                'Status'                           => $response->status(),
                'Response'                         => $response->body(),
                'Consignment ID'                   => $consignmentId,
            ];
            Log::error('Steadfast Track by CID Failed', $errorData);
            Log::info('Steadfast Track by CID Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        $status = $response->json()['delivery_status'] ?? 'unknown';

        return [
            'success'      => true,
            'status'       => $status,
            'status_label' => $this->statusLabel($status),
        ];
    }

    public function trackByInvoice(string $invoice): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/status_by_invoice/' . $invoice);

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Track by Invoice Failed' => true,
                'Status'                               => $response->status(),
                'Response'                             => $response->body(),
                'Invoice'                              => $invoice,
            ];
            Log::error('Steadfast Track by Invoice Failed', $errorData);
            Log::info('Steadfast Track by Invoice Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        $status = $response->json()['delivery_status'] ?? 'unknown';

        return [
            'success'      => true,
            'status'       => $status,
            'status_label' => $this->statusLabel($status),
        ];
    }

    public function createReturnRequest(string $trackingCode, string $reason = ''): array
    {
        $response = Http::withHeaders($this->headers())
            ->asJson()
            ->post($this->baseUrl . '/create_return_request', [
                'tracking_code' => $trackingCode,
                'reason'        => $reason,
            ]);

        if (!$response->successful()) {
            $errorData = [
                '❌ Steadfast Return Request Failed' => true,
                'Status'                            => $response->status(),
                'Response'                          => $response->body(),
                'Tracking Code'                     => $trackingCode,
            ];
            Log::error('Steadfast Return Request Failed', $errorData);
            Log::info('Steadfast Return Request Failed', $errorData);
            return ['success' => false, 'error' => $response->body()];
        }

        return [
            'success' => true,
            'data'    => $response->json(),
        ];
    }
}
