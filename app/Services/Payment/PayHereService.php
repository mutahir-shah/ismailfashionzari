<?php

namespace App\Services\Payment;

use App\Models\ExternalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PayHereService implements PaymentGatewayInterface
{
    private function getCredentials(): array
    {
        $service = ExternalService::where('name', 'PayHere')->first();
        if (!$service) {
            abort(500, 'PayHere gateway not configured.');
        }
        $lines = explode(';', $service->details);
        $keys  = explode(',', $lines[0]);
        $vals  = explode(',', $lines[1]);
        return array_combine($keys, $vals);
    }

    private function generateHash(string $merchantId, string $orderId, string $amount, string $currency, string $merchantSecret): string
    {
        // PayHere hash formula: md5(merchant_id + order_id + amount + currency + uppercase(md5(merchant_secret)))
        return strtoupper(
            md5(
                $merchantId .
                $orderId .
                number_format((float) $amount, 2, '.', '') .
                $currency .
                strtoupper(md5($merchantSecret))
            )
        );
    }

    /**
     * PayHere এর জন্য push = checkout payload জেনারেট করা
     * Frontend এই payload দিয়ে PayHere checkout পেজে redirect করবে
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'order_id' => 'nullable|string',
        ]);

        $creds = $this->getCredentials();

        $required = ['merchant_id', 'merchant_secret'];
        foreach ($required as $field) {
            if (empty(trim($creds[$field] ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => "PayHere credential missing: [{$field}]. Please configure it in Settings → Payment Gateways → PayHere.",
                ], 422);
            }
        }

        $mode           = trim($creds['Mode']            ?? 'sandbox');
        $merchantId     = trim($creds['merchant_id']     ?? '');
        $merchantSecret = trim($creds['merchant_secret'] ?? '');
        
        $baseCurrencyId = \Illuminate\Support\Facades\DB::table('general_settings')->first()->currency ?? 1;
        $currency       = \Illuminate\Support\Facades\DB::table('currencies')->where('id', $baseCurrencyId)->value('code') ?? 'LKR';

        $supportedCurrencies = ['LKR', 'USD', 'GBP', 'EUR', 'AUD'];
        if (!in_array($currency, $supportedCurrencies)) {
            if ($currency == 'USDT') {
                $currency = 'USD';
            } else {
                $currency = 'LKR';
            }
        }

        $orderId = $request->order_id ?? ('SPO-' . time());
        $amount  = number_format((float) $request->amount, 2, '.', '');

        $hash = $this->generateHash($merchantId, $orderId, $amount, $currency, $merchantSecret);

        $checkoutUrl = $mode === 'live'
            ? 'https://www.payhere.lk/pay/checkout'
            : 'https://sandbox.payhere.lk/pay/checkout';

        $notifyUrl  = route('payment.callback', ['gateway' => 'payhere']);
        $returnUrl  = route('payment.success', ['gateway' => 'payhere', 'order_id' => $orderId]);
        $cancelUrl  = route('payment.failed');

        // Cache এ order_id রেখে দাও যাতে poll করতে পারি
        Cache::put('payhere_pending_' . $orderId, true, now()->addMinutes(15));

        // Cache the payload for the QR code page to read
        $payloadData = [
            'checkoutUrl'  => $checkoutUrl,
            'payload'      => [
                'merchant_id'  => $merchantId,
                'return_url'   => $returnUrl,
                'cancel_url'   => $cancelUrl,
                'notify_url'   => $notifyUrl,
                'order_id'     => $orderId,
                'items'        => 'SalePro POS',
                'amount'       => $amount,
                'currency'     => $currency,
                'hash'         => $hash,
                'first_name'   => 'Walk-in',
                'last_name'    => 'Customer',
                'email'        => 'customer@example.com',
                'phone'        => '0000000000',
                'address'      => 'Store Location',
                'city'         => 'City',
                'country'      => 'Country',
            ]
        ];

        Cache::put('payhere_payload_' . $orderId, $payloadData, now()->addMinutes(15));

        return response()->json([
            'success'         => true,
            'order_id'        => $orderId,
            'sandbox'         => ($mode !== 'live'),
            'qr_checkout_url' => route('payhere.qr_checkout', $orderId),
            'payload'         => $payloadData['payload']
        ]);
    }

    public function queryStatus(Request $request): JsonResponse
    {
        $request->validate(['reference_id' => 'required|string']);
        $cached = Cache::get('payhere_' . $request->reference_id);

        if ($cached) {
            $status = $cached['status'] ?? 'failed';
            if ($status === '2') { // PayHere: 2 = Success
                return response()->json(['status' => 'success']);
            } elseif ($status === '0') { // 0 = Pending
                return response()->json(['status' => 'pending']);
            } elseif ($status === '-1') { // -1 = Cancelled
                return response()->json(['status' => 'cancelled']);
            } else {
                return response()->json([
                    'status'  => 'failed',
                    'message' => $cached['message'] ?? 'Payment failed.',
                ]);
            }
        }

        return response()->json(['status' => 'pending']);
    }

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('PayHere Callback (IPN) Received', $payload);

        $merchantId     = $payload['merchant_id']  ?? '';
        $orderId        = $payload['order_id']      ?? '';
        $paymentId      = $payload['payment_id']    ?? '';
        $payHereAmount  = $payload['payhere_amount'] ?? '';
        $payHereCurrency = $payload['payhere_currency'] ?? '';
        $statusCode     = $payload['status_code']   ?? '-2';
        $md5sig         = $payload['md5sig']         ?? '';

        // Credentials নাও Signature verify করতে
        try {
            $creds          = $this->getCredentials();
            $merchantSecret = trim($creds['merchant_secret'] ?? '');

            // PayHere IPN Signature Verification
            $localMd5 = strtoupper(
                md5(
                    $merchantId .
                    $orderId .
                    $payHereAmount .
                    $payHereCurrency .
                    $statusCode .
                    strtoupper(md5($merchantSecret))
                )
            );

            if ($localMd5 !== strtoupper($md5sig)) {
                Log::warning('PayHere IPN Signature Mismatch', [
                    'expected' => $localMd5,
                    'received' => $md5sig,
                ]);
                return response()->json(['status' => 'signature_mismatch'], 400);
            }
        } catch (\Exception $e) {
            Log::error('PayHere IPN Signature Verification Error', ['error' => $e->getMessage()]);
        }

        // Cache এ সেভ করো
        Cache::put('payhere_' . $orderId, [
            'status'     => $statusCode,
            'payment_id' => $paymentId,
            'message'    => $payload['status_message'] ?? '',
        ], now()->addMinutes(10));

        Log::info('PayHere IPN Cached', [
            'order_id'    => $orderId,
            'status_code' => $statusCode,
        ]);

        // PayHere 200 এর জন্য অপেক্ষা করে, না হলে retry করে
        return response('OK', 200);
    }
}
