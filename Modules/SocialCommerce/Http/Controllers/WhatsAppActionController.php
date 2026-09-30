<?php

namespace Modules\SocialCommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use App\Models\Sale;
use App\Models\Product;
use App\Models\WhatsappSetting;

class WhatsAppActionController extends Controller
{
    /**
     * Dispatch WhatsApp sending via Web fallback or Cloud API
     */
    private function dispatchWhatsApp($phone, $text, $type = 'text', $htmlContent = null, $title = 'Message')
    {
        $settings = WhatsappSetting::first();
        
        if (!$settings || empty($settings->phone_number_id) || empty($settings->permanent_access_token)) {
            // Fallback to wa.me
            $url = "https://web.whatsapp.com/send/?phone=" . urlencode($phone) . "&text=" . urlencode($text);
            return redirect()->away($url);
        } else {
            // Try via Cloud API using core's controller
            $requestData = [
                'receiver_phone' => [$phone],
                'message' => $text,
            ];
            
            if ($type == 'document' && $htmlContent) {
                $requestData['html_content'] = $htmlContent;
                $requestData['message'] = $title;
            }

            $req = new Request($requestData);
            $req->merge(['_from_form' => true]);

            $whpcon = new \App\Http\Controllers\WhatsappController();
            return $whpcon->sendMessage($req);
        }
    }

    public function shareOrder(Request $request, $id, $action)
    {
        $sale = Sale::with('customer')->findOrFail($id);
        $scSettings = SocialCommerceSetting::firstOrCreate([]);
        $phone = preg_replace('/\D/', '', $sale->customer->wa_number ?? $sale->customer->phone_number ?? '');

        if (empty($phone)) {
            return redirect()->back()->with('not_permitted', __('db.Customer does not have a valid phone number.'));
        }

        $template = '';
        $text = '';

        if ($action == 'confirmation') {
            $template = $scSettings->wa_template_order_confirmation ?? 'Dear {customer_name}, your order {reference_no} for {amount} has been confirmed.';
            $text = str_replace(
                ['{customer_name}', '{reference_no}', '{amount}', '{order_link}'],
                [$sale->customer->name, $sale->reference_no, $sale->grand_total, url('/sales/gen_invoice/' . $sale->id)],
                $template
            );
        } elseif ($action == 'payment') {
            $template = $scSettings->wa_template_payment_request ?? 'Dear {customer_name}, please pay {due_amount} for your order {reference_no}: {payment_link}';
            $due = $sale->grand_total - $sale->paid_amount;
            $text = str_replace(
                ['{customer_name}', '{reference_no}', '{due_amount}', '{payment_link}'],
                [$sale->customer->name, $sale->reference_no, $due, url('/sales/gen_invoice/' . $sale->id)], 
                $template
            );
        } elseif ($action == 'delivery') {
            $template = $scSettings->wa_template_delivery_update ?? 'Dear {customer_name}, the status of order {reference_no} is now {status}.';
            $statuses = [1 => 'Completed', 2 => 'Pending', 3 => 'Draft', 4 => 'Returned', 5 => 'Processing', 6 => 'Cooked', 7 => 'Served'];
            $statusName = $statuses[$sale->sale_status] ?? 'Unknown';
            $text = str_replace(
                ['{customer_name}', '{reference_no}', '{status}'],
                [$sale->customer->name, $sale->reference_no, $statusName],
                $template
            );
        } elseif ($action == 'invoice') {
            // For invoice, we want to send PDF via Cloud API or link via wa.me
            $appSaleController = new \App\Http\Controllers\SaleController();
            return $appSaleController->whatsappNotificationSend(new Request([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id
            ]));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Invalid share action.'));
        }

        return $this->dispatchWhatsApp($phone, $text);
    }
}
