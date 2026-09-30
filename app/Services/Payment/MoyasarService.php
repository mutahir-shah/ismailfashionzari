<?php

namespace App\Services\Payment;

use App\Models\ExternalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarService implements PaymentGatewayInterface
{
    private const API_BASE = 'https://api.moyasar.com/v1';

    private function getCredentials(): array
    {
        $service = ExternalService::where('name', 'Moyasar')->first();
        if (!$service) {
            abort(500, 'Moyasar gateway not configured.');
        }
        $lines = explode(';', $service->details);
        $keys  = explode(',', $lines[0]);
        $vals  = explode(',', $lines[1] ?? '');
        return array_combine($keys, $vals);
    }

    /**
     * Moyasar Hosted Invoice-এর জন্য push = invoice তৈরি করা
     * Frontend এই URL এ redirect করবে (QR/popup দিয়ে)
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'order_id' => 'nullable|string',
        ]);

        $creds = $this->getCredentials();

        if (empty(trim($creds['Secret Key'] ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Moyasar credential missing: [Secret Key]. Please configure it in Settings → Payment Gateways → Moyasar.',
            ], 422);
        }

        $secretKey = trim($creds['Secret Key']);

        $baseCurrencyId = \Illuminate\Support\Facades\DB::table('general_settings')->first()->currency ?? 1;
        $currency       = \Illuminate\Support\Facades\DB::table('currencies')->where('id', $baseCurrencyId)->value('code') ?? 'SAR';
        $currency       = strtoupper($currency ?: 'SAR');

        $orderId = $request->order_id ?? ('MYS-' . time());
        $amount  = (float) $request->amount;

        // Moyasar amounts are expressed in the smallest currency unit (e.g. halalas for SAR)
        $amountInMinorUnits = (int) round($amount * 100);

        $callbackUrl = route('payment.callback', ['gateway' => 'moyasar']);

        try {
            $response = Http::withBasicAuth($secretKey, '')
                ->asJson()
                ->post(self::API_BASE . '/invoices', [
                    'amount'       => $amountInMinorUnits,
                    'currency'     => $currency,
                    'description'  => 'SalePro POS Payment - ' . $orderId,
                    'callback_url' => $callbackUrl,
                    'success_url'  => route('payment.success', ['gateway' => 'moyasar', 'order_id' => $orderId]),
                    'back_url'     => route('payment.failed'),
                    'metadata'     => ['order_id' => $orderId],
                ]);

            if (!$response->successful()) {
                Log::error('Moyasar invoice creation failed', ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => $response->json('message') ?? 'Failed to create Moyasar invoice.',
                ], 422);
            }

            $invoice = $response->json();

            Cache::put('moyasar_pending_' . $invoice['id'], true, now()->addMinutes(15));

            return response()->json([
                'success'         => true,
                'order_id'        => $invoice['id'],
                'sandbox'         => str_starts_with($secretKey, 'sk_test_'),
                'qr_checkout_url' => $invoice['url'],
            ]);
        } catch (\Exception $e) {
            Log::error('Moyasar push error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function queryStatus(Request $request): JsonResponse
    {
        $request->validate(['reference_id' => 'required|string']);
        $invoiceId = $request->reference_id;

        $cached = Cache::get('moyasar_' . $invoiceId);
        if ($cached) {
            return response()->json($this->mapStatus($cached['status'] ?? '', $cached['message'] ?? ''));
        }

        // Fallback: ask Moyasar directly in case the webhook hasn't arrived yet (e.g. local dev)
        try {
            $creds     = $this->getCredentials();
            $secretKey = trim($creds['Secret Key'] ?? '');

            $response = Http::withBasicAuth($secretKey, '')->get(self::API_BASE . '/invoices/' . $invoiceId);

            if ($response->successful()) {
                $invoice = $response->json();
                return response()->json($this->mapStatus($invoice['status'] ?? '', ''));
            }
        } catch (\Exception $e) {
            Log::error('Moyasar queryStatus error: ' . $e->getMessage());
        }

        return response()->json(['status' => 'pending']);
    }

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Moyasar Callback (Webhook) Received', $payload);

        try {
            $creds        = $this->getCredentials();
            $webhookToken = trim($creds['Webhook Secret'] ?? '');

            if (!empty($webhookToken)) {
                $received = $request->header('X-Moyasar-Token') ?? str_replace('Bearer ', '', (string) $request->header('Authorization'));

                if (!hash_equals($webhookToken, (string) $received)) {
                    Log::warning('Moyasar Webhook token mismatch');
                    return response()->json(['status' => 'signature_mismatch'], 400);
                }
            }
        } catch (\Exception $e) {
            Log::error('Moyasar Webhook verification error', ['error' => $e->getMessage()]);
        }

        $data      = $payload['data'] ?? $payload;
        $invoiceId = $data['invoice_id'] ?? $data['id'] ?? null;
        $status    = $data['status'] ?? 'failed';
        $message   = $data['source']['message'] ?? '';

        if ($invoiceId) {
            Cache::put('moyasar_' . $invoiceId, [
                'status'  => $status,
                'message' => $message,
            ], now()->addMinutes(10));

            Log::info('Moyasar Webhook Cached', ['invoice_id' => $invoiceId, 'status' => $status]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function mapStatus(string $status, string $message): array
    {
        return match ($status) {
            'paid', 'authorized', 'verified' => ['status' => 'success'],
            'failed'    => ['status' => 'failed', 'message' => $message ?: 'Payment failed.'],
            'refunded'  => ['status' => 'failed', 'message' => 'Payment refunded.'],
            'initiated' => ['status' => 'pending'],
            default     => ['status' => 'pending'],
        };
    }
}
