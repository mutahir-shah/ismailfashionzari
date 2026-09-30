<?php

namespace App\Http\Controllers;

use App\Services\Payment\MoyasarService;
use App\Services\Payment\MpesaService;
use App\Services\Payment\MtnMoMoService;
use App\Services\Payment\PayHereService;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class PaymentGatewayController extends Controller
{
    /**
     * Gateway slug থেকে সঠিক Service রিটার্ন করো
     */
    private function resolveService(string $gateway): PaymentGatewayInterface
    {
        return match($gateway) {
            'mpesa'    => new MpesaService(),
            'mtnmomo'  => new MtnMoMoService(),
            'payhere'  => new PayHereService(),
            'stripe'   => new \App\Services\Payment\StripeService(),
            'moyasar'  => new MoyasarService(),
            default    => abort(404, "Payment gateway [{$gateway}] not found."),
        };
    }

    /**
     * STK Push / Request to Pay / Checkout Initiate
     * Route: POST /payment/{gateway}/push
     */
    public function push(Request $request, string $gateway): JsonResponse
    {
        $service = $this->resolveService($gateway);
        return $service->push($request);
    }

    /**
     * Payment status polling (Cache-based)
     * Route: POST /payment/{gateway}/query-status
     */
    public function queryStatus(Request $request, string $gateway): JsonResponse
    {
        $service = $this->resolveService($gateway);
        return $service->queryStatus($request);
    }

    /**
     * Webhook Callback — gateway server থেকে আসে
     * Route: POST /payment/{gateway}/callback
     */
    public function callback(Request $request, string $gateway): JsonResponse
    {
        $service = $this->resolveService($gateway);
        return $service->callback($request);
    }

    /**
     * M-Pesa Dynamic QR Code generate করো
     * Route: POST /payment/mpesa/generate-qr
     */
    public function generateQr(Request $request): JsonResponse
    {
        $service = new MpesaService();
        return $service->generateDynamicQr($request);
    }

    /**
     * MTN MoMo USSD QR Code generate করো
     * Route: POST /payment/mtnmomo/generate-qr
     */
    public function generateMtnQr(Request $request): JsonResponse
    {
        $service = new MtnMoMoService();
        return $service->generateUssdQr($request);
    }

    public function fixLiveCache()
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = str_replace('CACHE_DRIVER=array', 'CACHE_DRIVER=file', $env);
            file_put_contents($envPath, $env);
        }
        Artisan::call('optimize:clear');
        return 'Live cache and ENV fixed successfully!';
    }

    public function payhereCheckout($order_id)
    {
        $cached = Cache::get('payhere_payload_' . $order_id);
        if (!$cached) {
            abort(404, 'Payment session expired or invalid.');
        }
        return view('backend.payment.payhere_checkout', $cached);
    }

    public function paymentSuccess(Request $request)
    {
        $gateway = $request->query('gateway');
        $orderId = $request->query('order_id');

        if ($gateway === 'payhere' && $orderId) {
            // SECURITY MEASURE: Only allow this fallback on local/testing environments.
            // On a live server, anyone could forge this URL to bypass payment!
            $host = $request->getHost();
            $isLocal = app()->environment('local') || in_array($host, ['localhost', '127.0.0.1', 'salepro.test']);
            
            if ($isLocal) {
                \Illuminate\Support\Facades\Cache::put('payhere_' . $orderId, [
                    'status'     => '2',
                    'payment_id' => 'direct-success-local',
                    'message'    => 'Success by return url (Local Mode)',
                ], now()->addMinutes(10));
            }
        }

        return '<div style="font-family:sans-serif;text-align:center;padding:50px;color:#15803d;">
                    <h1 style="font-size:50px;margin-bottom:10px;">✅</h1>
                    <h2>Payment Successful</h2>
                    <p style="color:#64748b;">You can now close this window. Status has been updated automatically.</p>
                    <script>
                        // Try to close the popup if it was opened by window.open
                        setTimeout(function() {
                            window.close();
                        }, 2000);
                    </script>
                </div>';
    }

    public function paymentFailed()
    {
        return '<div style="font-family:sans-serif;text-align:center;padding:50px;color:#dc2626;">
                    <h1 style="font-size:50px;margin-bottom:10px;">❌</h1>
                    <h2>Payment Failed or Cancelled</h2>
                    <p style="color:#64748b;">You can close this window and try scanning again.</p>
                </div>';
    }
}
