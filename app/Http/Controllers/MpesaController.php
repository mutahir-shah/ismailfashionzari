<?php

namespace App\Http\Controllers;

use App\Models\ExternalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    /**
     * Get M-Pesa credentials from DB
     */
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

    /**
     * Get OAuth Access Token from Safaricom
     */
    private function getAccessToken(array $creds): string
    {
        $consumerKey    = trim($creds['consumer_Key']    ?? '');
        $consumerSecret = trim($creds['consumer_Secret'] ?? '');
        $mode           = trim($creds['Mode']            ?? 'sandbox');

        $baseUrl = $mode === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $url = $baseUrl . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Log::info('M-Pesa OAuth Response', [
            'http_code'  => $httpCode,
            'curl_error' => $curlError,
            'response'   => $response,
            'mode'       => $mode,
            'key_length' => strlen($consumerKey),
        ]);

        $data = json_decode($response, true);

        return $data['access_token'] ?? '';
    }

    /**
     * Validate credentials are all set
     */
    private function validateCredentials(array $creds): ?string
    {
        $required = ['consumer_Key', 'consumer_Secret', 'shortcode', 'passkey'];
        foreach ($required as $field) {
            if (empty(trim($creds[$field] ?? ''))) {
                return "M-Pesa credential missing: [{$field}]. Please go to Settings → Payment Gateways → Mpesa and fill all fields.";
            }
        }
        return null;
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online)
     */
    public function stkPush(Request $request)
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $creds = $this->getCredentials();

        // Validate all credentials are filled
        $credError = $this->validateCredentials($creds);
        if ($credError) {
            return response()->json(['success' => false, 'message' => $credError], 422);
        }

        $mode      = trim($creds['Mode']      ?? 'sandbox');
        $shortcode = trim($creds['shortcode'] ?? '');
        $passkey   = trim($creds['passkey']   ?? '');

        $baseUrl = $mode === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $token = $this->getAccessToken($creds);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa. Check Consumer Key/Secret.',
            ], 500);
        }

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);

        // Format phone to 254XXXXXXXXX format
        $phone = trim($request->phone);
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters

        if (strpos($phone, '0') === 0) {
            $phone = '254' . substr($phone, 1);
        } elseif (strpos($phone, '254') !== 0 && strlen($phone) === 9) {
            $phone = '254' . $phone;
        }

        $amount = (int) ceil($request->amount);

        $callbackUrl = route('mpesa.callback');

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
                'success'       => true,
                'CheckoutRequestID' => $result['CheckoutRequestID'],
                'message'       => 'STK Push sent. Please check your phone.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK Push failed.',
            'raw'     => $result,
        ], 400);
    }

    /**
     * Query STK Push status (polling)
     */
    public function queryStatus(Request $request)
    {
        $request->validate(['checkout_request_id' => 'required|string']);

        $checkoutId = $request->checkout_request_id;

        // প্রথমে Cache চেক করো (callback এসেছে কিনা)
        $cached = \Illuminate\Support\Facades\Cache::get('mpesa_' . $checkoutId);

        if ($cached) {
            $resultCode = (string)$cached['result_code'];
            if ($resultCode === '0') {
                return response()->json(['status' => 'success']);
            } elseif ($resultCode === '1032') {
                return response()->json(['status' => 'cancelled']);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'result' => [
                        'ResultDesc' => $cached['result_desc'] ?? 'Payment failed.'
                    ]
                ]);
            }
        }

        // Callback না আসলে pending ধরো
        return response()->json(['status' => 'pending']);
    }

    /**
     * M-Pesa Callback (called by Safaricom servers)
     */
    public function callback(Request $request)
    {
        $payload = $request->all();
        Log::info('M-Pesa Callback Received', $payload);

        $body = $payload['Body']['stkCallback'] ?? null;

        if ($body) {
            $checkoutId = $body['CheckoutRequestID'] ?? null;
            $resultCode = (string)($body['ResultCode'] ?? '-1');

            // Cache তে save করো যাতে frontend poll করতে পারে
            \Illuminate\Support\Facades\Cache::put('mpesa_' . $checkoutId, [
                'result_code' => $resultCode,
                'result_desc' => $body['ResultDesc'] ?? '',
            ], now()->addMinutes(10));

            Log::info('M-Pesa STK Callback Cached', [
                'checkout_id' => $checkoutId,
                'result_code' => $resultCode,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
