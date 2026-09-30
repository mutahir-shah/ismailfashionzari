<?php

namespace App\Services\Payment;

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PosSetting;
use Illuminate\Support\Facades\Log;

class StripeService implements PaymentGatewayInterface
{
    public function push(Request $request): JsonResponse
    {
        try {
            $stripeGateway = \Illuminate\Support\Facades\DB::table('external_services')->where('name', 'Stripe')->first();
            if (!$stripeGateway || !$stripeGateway->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe is not active or configured.'
                ], 400);
            }

            // details format: Mode,Public Key,Secret Key;sandbox,your_public_key,your_secret_key
            $detailsArray = explode(';', $stripeGateway->details);
            $keys = isset($detailsArray[1]) ? explode(',', $detailsArray[1]) : [];
            $secretKey = $keys[1] ?? null;

            if (!$secretKey || $secretKey == 'your_secret_key') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe API keys are not configured properly.'
                ], 400);
            }

            Stripe::setApiKey($secretKey);

            $amount = (float) $request->amount;
            
            $baseCurrencyId = \Illuminate\Support\Facades\DB::table('general_settings')->first()->currency ?? 1;
            $currency       = \Illuminate\Support\Facades\DB::table('currencies')->where('id', $baseCurrencyId)->value('code') ?? 'usd';
            $currency       = strtolower($currency);
            
            // Generate a unique reference
            $orderId = 'STR-' . time() . rand(100, 999);

            // Create a Stripe Checkout Session
            $checkout_session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => (int) round($amount * 100), // Stripe takes amounts in cents
                        'product_data' => [
                            'name' => 'POS Sale Payment',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                // Since this is for a POS screen QR code, the user finishes payment on their phone.
                // We'll just redirect them to a generic success page on their phone.
                'success_url' => route('payment.success'),
                'cancel_url' => route('payment.failed'),
                'client_reference_id' => $orderId,
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $checkout_session->id, // session id is what we poll
                'qr_checkout_url' => $checkout_session->url
            ]);

        } catch (\Exception $e) {
            Log::error("Stripe push error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function queryStatus(Request $request): JsonResponse
    {
        $sessionId = $request->reference_id;
        if (!$sessionId) {
            return response()->json(['status' => 'failed', 'message' => 'No session ID provided.']);
        }

        try {
            $stripeGateway = \Illuminate\Support\Facades\DB::table('external_services')->where('name', 'Stripe')->first();
            if (!$stripeGateway || !$stripeGateway->active) {
                return response()->json(['status' => 'failed', 'message' => 'Stripe is not active.']);
            }

            $detailsArray = explode(';', $stripeGateway->details);
            $keys = isset($detailsArray[1]) ? explode(',', $detailsArray[1]) : [];
            $secretKey = $keys[1] ?? null;

            Stripe::setApiKey($secretKey);

            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment completed.'
                ]);
            } elseif ($session->status === 'expired' || $session->status === 'canceled') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment session expired or canceled.'
                ]);
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Awaiting payment...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
