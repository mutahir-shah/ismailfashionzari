<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

interface PaymentGatewayInterface
{
    /**
     * Payment push/initiate করো (STK Push / Request to Pay / Checkout)
     */
    public function push(Request $request): JsonResponse;

    /**
     * Payment status চেক করো (Cache-based polling)
     */
    public function queryStatus(Request $request): JsonResponse;

    /**
     * Payment gateway-এর Callback/Webhook হ্যান্ডেল করো
     */
    public function callback(Request $request): JsonResponse;
}
