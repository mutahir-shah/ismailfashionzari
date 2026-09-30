<?php

namespace App\Http\Controllers;

use App\Models\ExternalService;
use Razorpay\Api\Api;
use Illuminate\Http\Request;

class RazorpayController extends Controller
{
    public function createOrder(Request $request)
    {
        $credentials = ExternalService::where('name', 'Razorpay')->first();
        $cred = explode(';', $credentials->details);
        [$key, $secret] = explode(',', $cred[1]);

        $api = new Api($key, $secret);

        // Convert amount to paise
        $amount = $request->amount * 100;

        $order = $api->order->create([
            'receipt' => 'order_rcptid_' . time(),
            'amount' => $amount,
            'currency' => 'INR',
        ]);

        return response()->json([
            'key' => $key,
            'amount' => $amount,
            'order_id' => $order['id']
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $credentials = ExternalService::where('name', 'Razorpay')->first();
        $cred = explode(';', $credentials->details);
        [$key, $secret] = explode(',', $cred[1]);

        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;

        $generated_signature = hash_hmac(
            'sha256',
            $razorpay_order_id . '|' . $razorpay_payment_id,
            $secret
        );

        if ($generated_signature === $razorpay_signature) {
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'failed'], 400);
        }
    }
}
