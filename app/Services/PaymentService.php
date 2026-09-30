<?php

namespace App\Services;

use Stripe\Stripe;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PosSetting;
use App\Payment\SslCommerz;
use App\Payment\BkashPayment;
use App\Payment\PaypalPayment;
use App\Payment\StripePayment;
use App\Payment\JazzCashPayment;
use App\Payment\PaydunyaPayment;
use App\Payment\PaystackPayment;
use App\Payment\RazorpayPayment;
use App\Models\PaymentWithCheque;
use Illuminate\Support\Facades\Auth;
use App\Models\PaymentWithCreditCard;
use App\Services\InvoiceService;

class PaymentService
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function initialize($payment_type)
    {
        switch ($payment_type) {
            case 'stripe':
                return new StripePayment();
            case 'paypal':
                return new PaypalPayment();
            case 'jazzcash':
                return new JazzCashPayment();
            case 'razorpay':
                return new RazorpayPayment();
            case 'paystack':
                return new PaystackPayment();
            case 'paydunya':
                return new PaydunyaPayment();
            case 'bkash':
                return new BkashPayment();
            case 'ssl_commerz':
                return new SslCommerz();
            default:
                break;
        }
    }

    public function payForPurchase(array $data)
    {
        $lims_purchase_data = Purchase::find($data['purchase_id']);
        $lims_purchase_data->paid_amount += $data['amount'];
        $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
        // dd($data, $balance, $lims_purchase_data);
        if ($balance > 0)
            $lims_purchase_data->payment_status = 1;
        else
            $lims_purchase_data->payment_status = 2;

        $lims_purchase_data->save();

        if ($data['paid_by_id'] == 1)
            $paying_method = 'Cash';
        elseif ($data['paid_by_id'] == 2)
            $paying_method = 'Gift Card';
        elseif ($data['paid_by_id'] == 3)
            $paying_method = 'Credit Card';
        else
            $paying_method = 'Cheque';

        $lims_payment_data = new Payment();
        $lims_payment_data->user_id = Auth::id();
        $lims_payment_data->purchase_id = $lims_purchase_data->id;
        $lims_payment_data->account_id = $data['account_id'];
        $lims_payment_data->payment_reference = $this->invoiceService->generateInvoiceName('ppr-'); // 'ppr-' . date("Ymd") . '-'. date("his");
        $lims_payment_data->amount = $data['amount'];
        if (isset($data['currency_id'])) {
            $lims_payment_data->currency_id = $data['currency_id'];
        }
        if (isset($data['exchange_rate'])) {
            $lims_payment_data->exchange_rate = $data['exchange_rate'];
        }
        if (isset($data['document'])) {
            $lims_payment_data->document = $data['document'];
        }
        $lims_payment_data->change = $data['paying_amount'] - $data['amount'];
        $lims_payment_data->paying_method = $paying_method;
        $lims_payment_data->payment_note = $data['payment_note'];
        $lims_payment_data->payment_at = $data['payment_at'];
        $lims_payment_data->save();

        $lims_pos_setting_data = PosSetting::latest()->first();
        if ($paying_method == 'Credit Card' && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {

            Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
            $token = $data['stripeToken'];
            $amount = $data['amount'];

            // Charge the Customer
            $charge = \Stripe\Charge::create([
                'amount' => $amount * 100,
                'currency' => 'usd',
                'source' => $token,
            ]);

            $data['charge_id'] = $charge->id;
            PaymentWithCreditCard::create($data);
        } elseif ($paying_method == 'Cheque') {
            PaymentWithCheque::create($data);
        }

        // === ACCOUNTING ENGINE PHASE 2E: PAYMENT ===
        $accountingService = app(\App\Services\AccountingService::class);
        $result = $accountingService->recordPayment($lims_payment_data);
        if (!$result->success) {
            \Log::error('Accounting failed for Purchase Payment', ['payment_id' => $lims_payment_data->id, 'error' => $result->error]);
            if (\Schema::hasColumn($lims_payment_data->getTable(), 'accounting_status')) {
                $lims_payment_data->accounting_status = 'failed';
                $lims_payment_data->save();
            }
        }
        // ===========================================

        return [
            'status' => true,
            'message' => 'Payment created successfully',
            'data' => $lims_payment_data,
        ];
    }

    public function updatePurchasePayment(int $paymentId, array $data, ?Auth $user = null): Payment
    {
        $lims_payment_data = Payment::findOrFail($paymentId);
        $lims_purchase_data = Purchase::findOrFail($lims_payment_data->purchase_id);

        $oldAmount = (float) $lims_payment_data->amount;
        $newAmount = (float) ($data['amount'] ?? $data['edit_amount'] ?? $oldAmount);

        $lims_purchase_data->paid_amount = $lims_purchase_data->paid_amount - $oldAmount + $newAmount;
        $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
        if ($balance > 0) {
            $lims_purchase_data->payment_status = 1;
        } else {
            $lims_purchase_data->payment_status = 2;
        }
        $lims_purchase_data->save();

        if (isset($data['account_id'])) {
            $lims_payment_data->account_id = $data['account_id'];
        }
        $lims_payment_data->amount = $newAmount;
        if (isset($data['payment_note']) || isset($data['edit_payment_note'])) {
            $lims_payment_data->payment_note = $data['payment_note'] ?? $data['edit_payment_note'];
        }
        if (isset($data['paying_method'])) {
            $lims_payment_data->paying_method = $data['paying_method'];
        }
        $lims_payment_data->save();

        $accountingService = app(\App\Services\AccountingService::class);
        $accountingService->reverseTransaction(get_class($lims_payment_data), $lims_payment_data->id, '_reversed');
        $accountingService->recordPayment($lims_payment_data, 'payment_updated');

        return $lims_payment_data;
    }

    public function deletePurchasePayment(int $paymentId): bool
    {
        $lims_payment_data = Payment::findOrFail($paymentId);
        $lims_purchase_data = Purchase::where('id', $lims_payment_data->purchase_id)->first();

        if ($lims_purchase_data) {
            $lims_purchase_data->paid_amount -= $lims_payment_data->amount;
            $balance = $lims_purchase_data->grand_total - $lims_purchase_data->paid_amount;
            if ($balance > 0) {
                $lims_purchase_data->payment_status = 1;
            } else {
                $lims_purchase_data->payment_status = 2;
            }
            $lims_purchase_data->save();
        }

        $accountingService = app(\App\Services\AccountingService::class);
        $accountingService->reverseTransaction(get_class($lims_payment_data), $lims_payment_data->id, '_deleted');

        return (bool) $lims_payment_data->delete();
    }
}

