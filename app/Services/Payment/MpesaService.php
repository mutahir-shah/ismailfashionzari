<?php

namespace App\Services\Payment;

use App\Models\ExternalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MpesaService implements PaymentGatewayInterface
{
    private function getCredentials(): array
    {
        $service = ExternalService::where('name', 'Mpesa')->first();
        if (!$service) {
            abort(500, 'M-Pesa gateway not configured.');
        }
        $lines = explode(';', $service->details);
        $keys  = explode(',', $lines[0]);
        $vals  = explode(',', $lines[1]);
        return array_combine($keys, $vals);
    }

    private function getAccessToken(array $creds): string
    {
        $consumerKey    = trim($creds['consumer_Key']    ?? '');
        $consumerSecret = trim($creds['consumer_Secret'] ?? '');
        $mode           = trim($creds['Mode']            ?? 'sandbox');

        $cacheKey    = 'mpesa_access_token_' . md5($consumerKey . '_' . $mode);
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        $baseUrl = $mode === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Log::info('M-Pesa OAuth Response', [
            'http_code'  => $httpCode,
            'curl_error' => $curlError,
            'mode'       => $mode,
        ]);

        $data  = json_decode($response, true);
        $token = $data['access_token'] ?? '';

        if ($token) {
            Cache::put($cacheKey, $token, 3300); // 55 মিনিট ক্যাশে রাখো
        }

        return $token;
    }

    private function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters (+, spaces, dashes)
        $phone = preg_replace('/[^0-9]/', '', trim($phone));
        
        // Remove leading double zeros (e.g. 00254 -> 254)
        $phone = preg_replace('/^00/', '', $phone);

        // If it starts with '0' (e.g. 07XXXXXXXX), remove the '0'
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }

        // If it starts with '254', remove the '254' to isolate the main 9 digits
        if (strpos($phone, '254') === 0) {
            $phone = substr($phone, 3);
            
            // Just in case someone typed '25407XXXXXXXX', remove the extra '0'
            if (strpos($phone, '0') === 0) {
                $phone = substr($phone, 1);
            }
        }

        // Now we should be left with exactly 9 digits (e.g. 7XXXXXXXX or 1XXXXXXXX)
        // Prepend the required '254'
        return '254' . $phone;
    }

    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $creds = $this->getCredentials();

        $required = ['consumer_Key', 'consumer_Secret', 'shortcode', 'passkey'];
        foreach ($required as $field) {
            if (empty(trim($creds[$field] ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => "M-Pesa credential missing: [{$field}]. Please configure it in Settings → Payment Gateways → Mpesa.",
                ], 422);
            }
        }

        $mode      = trim($creds['Mode']      ?? 'sandbox');
        $shortcode = trim($creds['shortcode'] ?? '');
        $passkey   = trim($creds['passkey']   ?? '');
        $baseUrl   = $mode === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
        $token     = $this->getAccessToken($creds);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'M-Pesa Authentication failed. Please check your Consumer Key/Secret.',
            ], 500);
        }

        $timestamp   = now()->format('YmdHis');
        $password    = base64_encode($shortcode . $passkey . $timestamp);
        $phone       = $this->formatPhone($request->phone);
        $amount      = (int) ceil($request->amount);
        $callbackUrl = route('payment.callback', ['gateway' => 'mpesa']);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => 'SalePro',
            'TransactionDesc'   => 'Payment',
        ];

        $ch = curl_init($baseUrl . '/mpesa/stkpush/v1/processrequest');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        Log::info('M-Pesa STK Push Response', ['response' => $result, 'http_code' => $httpCode]);

        if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return response()->json([
                'success'           => true,
                'CheckoutRequestID' => $result['CheckoutRequestID'],
                'message'           => 'STK Push has been sent. Please enter your PIN on your phone.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK Push failed.',
            'raw'     => $result,
        ], 400);
    }

    /**
     * Safaricom Dynamic QR Code generate করো
     * কাস্টমার M-Pesa App দিয়ে স্ক্যান করে সরাসরি পেমেন্ট করতে পারবে
     */
    public function generateDynamicQr(Request $request): JsonResponse
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'order_id' => 'required|string',
        ]);

        $creds     = $this->getCredentials();
        $mode      = trim($creds['Mode'] ?? 'sandbox');
        $shortcode = trim($creds['shortcode'] ?? '');
        $token     = $this->getAccessToken($creds);

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'M-Pesa authentication failed.'], 500);
        }

        $baseUrl = $mode === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $amount  = (int) ceil($request->amount);
        $payload = [
            'MerchantName' => 'SalePro POS',
            'RefNo'        => $request->order_id,
            'Amount'       => $amount,
            'TrxCode'      => 'BG',        // BG = Buy Goods (Till Number); PB = PayBill
            'CPI'          => $shortcode,  // Till Number or PayBill Shortcode
            'Size'         => '400',       // QR image size in pixels
        ];

        $ch = curl_init($baseUrl . '/mpesa/qrcode/v1/generate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        Log::info('M-Pesa Dynamic QR Response', ['response' => $result, 'http_code' => $httpCode]);

        if (isset($result['QRCode'])) {
            return response()->json([
                'success' => true,
                'qr_code' => $result['QRCode'], // base64 encoded image
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'QR Code generation failed.',
        ], 400);
    }

    public function queryStatus(Request $request): JsonResponse
    {
        $request->validate(['reference_id' => 'required|string']);
        $cached = Cache::get('mpesa_' . $request->reference_id);

        if ($cached) {
            $code = (string) $cached['result_code'];
            if ($code === '0') {
                return response()->json(['status' => 'success']);
            } elseif ($code === '1032') {
                return response()->json(['status' => 'cancelled']);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => $cached['result_desc'] ?? 'Payment failed.',
                ]);
            }
        }

        return response()->json(['status' => 'pending']);
    }

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('M-Pesa Callback Received', $payload);

        $body = $payload['Body']['stkCallback'] ?? null;
        if ($body) {
            $checkoutId = $body['CheckoutRequestID'] ?? null;
            $resultCode = (string) ($body['ResultCode'] ?? '-1');

            Cache::put('mpesa_' . $checkoutId, [
                'result_code' => $resultCode,
                'result_desc' => $body['ResultDesc'] ?? '',
            ], now()->addMinutes(10));

            Log::info('M-Pesa Callback Cached', [
                'checkout_id' => $checkoutId,
                'result_code' => $resultCode,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
