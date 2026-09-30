<?php

namespace Modules\SocialCommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Biller;
use App\Models\Currency;
use Modules\SocialCommerce\Services\SocialCommerceStorefrontCapability;

class PublicSocialController extends Controller
{
    private function dbText(string $key): string
    {
        $translationKey = 'db.' . $key;
        $translated = __($translationKey);

        return $translated === $translationKey ? $key : $translated;
    }

    /**
     * Display the public product landing page.
     */
    public function show($product)
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);
        
        if (!$settings->is_active || !$settings->allow_fallback) {
            abort(404, $this->dbText('Social Commerce is disabled.'));
        }

        $socialSetting = SocialProductSetting::query()
            ->where('product_id', $product)
            ->where('is_published', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with('product')
            ->firstOrFail();
        $product = $socialSetting->product;

        // Track Click
        $click = \Modules\SocialCommerce\Entities\SocialCommerceClick::create([
            'product_id' => $product->id,
            'source' => request()->query('source', 'unknown'),
            'campaign' => request()->query('campaign', null),
            'session_id' => session()->getId(),
        ]);
        
        session()->put('sc_click_id', $click->id);

        // Prepare data for the view
        $title = $socialSetting->social_title ?: $product->name;
        $description = $socialSetting->social_description ?: strip_tags($product->product_details);
        $image = $socialSetting->social_image ? asset('images/product/social/' . $socialSetting->social_image) : asset('images/product/' . explode(',', $product->image)[0]);
        $price = $product->price;
        $whatsapp = $settings->whatsapp_number;
        
        // Generate WhatsApp message
        $template = $settings->share_template ?: $this->dbText("Hi, I'm interested in {product_name}. Is it available?");
        $message = str_replace('{product_name}', $title, $template);
        $canonicalLink = route('socialcommerce.public.show', $product->id);
        $message .= "\n\n" . $canonicalLink;
        $whatsappLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp) . "?text=" . urlencode($message);

        $capability = app(SocialCommerceStorefrontCapability::class);
        $canPurchaseOnline = $capability->canPurchaseOnline($product);
        $ecommerceUrl = $capability->getEcommerceUrl($product);

        return view('socialcommerce::public.show', compact('product', 'title', 'description', 'image', 'price', 'whatsappLink', 'settings', 'canPurchaseOnline', 'ecommerceUrl'));
    }

    /**
     * Handle minimal checkout form submission
     */
    public function placeOrder(Request $request, $product)
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);
        if (!$settings->is_active || !$settings->allow_fallback) {
            abort(404, $this->dbText('Social Commerce is disabled or fallback checkout is not allowed.'));
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'qty' => 'required|integer|min:1',
            'note' => 'nullable|string'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $product) {
            // Lock global product to prevent global overselling
            $publication = SocialProductSetting::query()
                ->where('product_id', $product)
                ->where('is_published', true)
                ->firstOrFail();

            $product = Product::whereKey($publication->product_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            // Validate global stock
            if ($product->qty < $request->qty) {
                return redirect()->back()->with('error', $this->dbText('Requested quantity is not available in stock.'));
            }

            // Find a warehouse that actually has stock for this product, and lock the product_warehouse row
            $productWarehouse = \App\Models\Product_Warehouse::where('product_id', $product->id)
                ->where('qty', '>=', $request->qty)
                ->lockForUpdate()
                ->first();

            if (!$productWarehouse) {
                return redirect()->back()->with('error', $this->dbText('Requested quantity is not available in any warehouse.'));
            }

            // Ensure the warehouse itself is active
            $warehouse = \App\Models\Warehouse::where('id', $productWarehouse->warehouse_id)->where('is_active', true)->first();
            if (!$warehouse) {
                return redirect()->back()->with('error', $this->dbText('Store configuration error: Warehouse is inactive.'));
            }

            // Find or create customer
            $customer = Customer::where('phone_number', $request->phone)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone_number' => $request->phone,
                    'address' => $request->address ?? 'N/A',
                    'city' => 'N/A',
                    'customer_group_id' => 1,
                    'is_active' => 1
                ]);
            }

            $biller = Biller::where('is_active', true)->first();
            if (!$biller) {
                return redirect()->back()->with('error', $this->dbText('Store configuration error: No biller found.'));
            }

            $adminUser = \App\Models\User::where('role_id', 1)->where('is_active', 1)->first();
            $userId = $adminUser ? $adminUser->id : 1;

            $general_setting = cache()->get('general_setting');
            $currency = Currency::find($general_setting->currency ?? 1);

            $total_price = $product->price * $request->qty;
            
            // Use SalePro's invoice service for collision-proof reference
            $reference = app(\App\Services\InvoiceService::class)->generateInvoiceName('sc-');

            $sale = Sale::create([
                'reference_no'         => $reference,
                'user_id'              => $userId,
                'customer_id'          => $customer->id,
                'warehouse_id'         => $warehouse->id,
                'biller_id'            => $biller->id,
                'item'                 => 1,
                'total_qty'            => $request->qty,
                'total_discount'       => 0,
                'total_tax'            => 0,
                'total_price'          => $total_price,
                'grand_total'          => $total_price,
                'sale_status'          => 2, // Pending
                'payment_status'       => 1, // Pending
                'sale_note'            => $request->note,
                'sale_type'            => 'social_commerce',
                'social_channel'       => $request->input('source', 'direct'),
                'social_campaign'      => $request->input('campaign', null),
                'social_click_id'      => session('sc_click_id'), 
                'payment_mode'         => 'Cash on Delivery',
                'created_at'           => date('Y-m-d H:i:s'),
                'currency_id'          => $currency->id ?? 1,
                'exchange_rate'        => $currency->exchange_rate ?? 1
            ]);

            Product_Sale::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'variant_id' => 0,
                'qty' => $request->qty,
                'net_unit_price' => $product->price,
                'sale_unit_id' => $product->sale_unit_id,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $total_price,
            ]);

            // Deduct stock globally
            $product->qty -= $request->qty;
            $product->save();

            // Deduct stock in warehouse
            $productWarehouse->qty -= $request->qty;
            $productWarehouse->save();

            $accountingResult = app(\App\Services\AccountingService::class)
                ->recordSale($sale, 'social_commerce_order_created');
            if (!$accountingResult->success) {
                \Log::error('Accounting failed for social commerce order', [
                    'sale_id' => $sale->id,
                    'error' => $accountingResult->error,
                ]);
            }

            return redirect()->back()->with('success', $this->dbText('Order placed successfully! We will contact you soon.'));
        });
    }
}
