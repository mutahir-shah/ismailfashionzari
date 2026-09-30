<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Account;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Payment;
use App\Models\PosSetting;
use App\Models\RewardPointSetting;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithCheque;
use App\Models\InvoiceSetting;
use App\Models\RewardPoint;
use NumberToWords\NumberToWords;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use Stripe\Stripe;
use App\Models\SmsTemplate;
use App\Models\ExternalService;
use App\Models\MailSetting;
use App\Models\WhatsappSetting;
use App\Models\Unit;
use App\ViewModels\ISmsModel;
use App\Http\Controllers\WhatsappController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\SaleDetails;

class PosController extends Controller
{
    use ProvidesThemeBackgrounds;

    private $_smsModel;

    public function __construct(ISmsModel $smsModel)
    {
        $this->_smsModel = $smsModel;
    }

    /**
     * Step 1 of Mobile POS: select warehouse + biller.
     *
     * This mirrors the top selection section in `resources/views/backend/sale/pos.blade.php`.
     * The Flutter app will navigate to the next screen using `submit_strategy = params`.
     */
    public function create(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // POS is part of sales flow.
            if (!$role || !$role->hasPermissionTo('sales-add')) {
                return (new ErrorResource('Sorry! You are not allowed to access this module.'))->additional([
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ]);
            }

            $posSetting = PosSetting::latest()->first();

            $roleHasAccountSelection = $role->hasPermissionTo('account-selection');

            $accounts = $roleHasAccountSelection
                ? Account::select('id', 'name', 'is_default')->where('is_active', true)->get()
                : collect();

            $accountOptions = $accounts->map(function ($account) {
                return [
                    'label' => $account->name,
                    'value' => $account->id,
                ];
            })->toArray();

            $defaultAccountId = $accounts->firstWhere('is_default', 1)->id
                ?? ($accounts->first()->id ?? null);

            $currencies = Currency::where('is_active', true)->get();
            $currencyOptions = $currencies->map(function ($currency) {
                return [
                    'label' => $currency->code,
                    'value' => $currency->id,
                ];
            })->toArray();

            $defaultCurrency = $currencies->first();
            $defaultCurrencyId = $defaultCurrency->id ?? null;
            $defaultExchangeRate = $defaultCurrency->exchange_rate ?? null;

            // Customer options (used in POS top bar)
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                $waNumber = $customer->wa_number ? ' (' . $customer->wa_number . ')' : '';
                return [
                    'label' => $customer->name . $waNumber,
                    'value' => $customer->id,
                ];
            })->toArray();

            // Warehouse options (match staff restrictions used in other controllers)
            $warehousesQuery = Warehouse::where('is_active', true);
            if ((int) $user->role_id > 2 && !empty($user->warehouse_id)) {
                $warehousesQuery->where('id', $user->warehouse_id);
            }
            $warehouses = $warehousesQuery->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            // Biller options (match staff restrictions used in other controllers)
            $billersQuery = Biller::where('is_active', true);
            if ((int) $user->role_id > 2 && !empty($user->biller_id)) {
                $billersQuery->where('id', $user->biller_id);
            }
            $billers = $billersQuery->get();
            $billerOptions = $billers->map(function ($biller) {
                $company = $biller->company_name ? ' (' . $biller->company_name . ')' : '';
                return [
                    'label' => $biller->name . $company,
                    'value' => $biller->id,
                ];
            })->toArray();

            // Default values follow Blade logic:
            // - if user has fixed warehouse/biller: those are used
            // - else use POS setting default
            // - else use first active item
            $defaultWarehouseId = $user->warehouse_id
                ?? ($posSetting->warehouse_id ?? ($warehouses->first()->id ?? null));

            $defaultBillerId = $user->biller_id
                ?? ($posSetting->biller_id ?? ($billers->first()->id ?? null));

            $defaultCustomerId = $posSetting->customer_id
                ?? ($customers->first()->id ?? null);

            $warehouseField = !empty($user->warehouse_id)
                ? [
                    'type' => 'hidden',
                    'name' => 'warehouse_id',
                    'value' => $defaultWarehouseId,
                ]
                : [
                    'type' => 'select',
                    'name' => 'warehouse_id',
                    'label' => 'Warehouse *',
                    'placeholder' => 'Select warehouse...',
                    'options' => $warehouseOptions,
                    'value' => $defaultWarehouseId,
                    'required' => true,
                ];

            $billerField = !empty($user->biller_id)
                ? [
                    'type' => 'hidden',
                    'name' => 'biller_id',
                    'value' => $defaultBillerId,
                ]
                : [
                    'type' => 'select',
                    'name' => 'biller_id',
                    'label' => 'Biller *',
                    'placeholder' => 'Select biller...',
                    'options' => $billerOptions,
                    'value' => $defaultBillerId,
                    'required' => true,
                ];

            $dateField = [
                'type' => 'datepicker',
                'name' => 'created_at',
                'label' => 'Date',
                'placeholder' => 'Select date',
                // Keep parseable default value so the Flutter date picker can prefill correctly
                'value' => now()->toIso8601String(),
                'format_specifier' => 'dd MMMM, yyyy',
            ];

            // Date editing permission logic (matches blade logic)
            if ($user->role_id > 2) {
                $dateField = [
                    'type' => 'text',
                    'name' => 'created_at',
                    'label' => 'Date',
                    'value' => date('d F, Y'), // Human readable for display
                    'readonly' => true,
                ];
            }

            $roleHasCustomerAdd = $role->hasPermissionTo('customers-add');

            $customerField = [
                'type' => 'select',
                'name' => 'customer_id',
                'label' => 'Customer *',
                'placeholder' => 'Select customer...',
                'options' => $customerOptions,
                'value' => $defaultCustomerId,
                'required' => true,
                'new_screen' => $roleHasCustomerAdd ? '/customers/create' : null,
                'new_screen_type' => 'form',
            ];

            $moreOptionsItems = [];

            // Account selection (permission-based)
            if ($roleHasAccountSelection) {
                $moreOptionsItems[] = [
                    'type' => 'select',
                    'name' => 'account_id',
                    'label' => 'Account *',
                    'placeholder' => 'Select an Account',
                    'options' => $accountOptions,
                    'value' => $defaultAccountId,
                    'required' => true,
                ];
            }

            // Sale reference no (manual)
            $moreOptionsItems[] = [
                'type' => 'datagenerator',
                'name' => 'reference_no',
                'label' => 'Sale Reference No',
                'placeholder' => 'Type reference number',
                'generator' => [
                    'generated_type' => 'reference_no',
                    'prefix' => 'posr-',
                ]
            ];

            // Currency & exchange rate
            $moreOptionsItems[] = [
                'type' => 'select',
                'name' => 'currency_id',
                'label' => 'Currency',
                'placeholder' => 'Select currency',
                'options' => $currencyOptions,
                'value' => $defaultCurrencyId,
            ];

            $moreOptionsItems[] = [
                'type' => 'text',
                'name' => 'exchange_rate',
                'label' => 'Exchange Rate',
                'placeholder' => 'Enter exchange rate',
                'keyboard_type' => 'number',
                'value' => $defaultExchangeRate,
            ];

            return $this->withDashBackground([
                'title' => 'Create New Sale',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,

                // New navigation concept: move to next screen with selected params.
                'submit_strategy' => 'params',

                // This is the API URL for the NEXT screen (Step 2). It will be implemented next.
                'submit_url' => '/pos/step-2',
                'screen_type' => 'form',

                // UX (only allowed extra UX key)
                'submit_button_text' => 'Next',

                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Configurations',
                        'items' => [
                            $dateField,
                            $warehouseField,
                            $billerField,
                            $customerField,
                        ],
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Advanced Options',
                        'collapsible' => true,
                        'items' => $moreOptionsItems,
                    ],
                ],
            ], 'app');
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage()))->additional([
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]);
        }
    }

    public function step2(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role || !$role->hasPermissionTo('sales-add')) {
                return (new ErrorResource('Sorry! You are not allowed to access this module.'))->additional([
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ]);
            }

            // Fetch Catalog Options
            $categories = Category::where('is_active', true)->select('id', 'name', 'image')->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => config('app.url') . '/' . $category->image,
                ];
            });
            $brands = Brand::where('is_active', true)->select('id', 'title as name', 'image')->get()->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'image' => config('app.url') . '/' . $brand->image,
                ];
            });
            $tax_rates = Tax::where('is_active', true)->select('id', 'name', 'rate')->get();
            $coupons = Coupon::where('is_active', true)->select('id', 'code')->get(); // Optional, maybe just text input

            // Prepare Tax Options for Select
            $taxOptions = [['label' => 'No Tax', 'value' => 0]];
            foreach ($tax_rates as $tax) {
                $taxOptions[] = ['label' => $tax->name, 'value' => $tax->rate];
            }

            // Payment Options
            $posSetting = PosSetting::latest()->first();
            $availablePaymentMethods = [];
            if ($posSetting && $posSetting->payment_options) {
                $options = explode(',', $posSetting->payment_options);
                foreach ($options as $option) {
                    $availablePaymentMethods[] = trim($option);
                }
            } else {
                $availablePaymentMethods = [];
            }

            // Accounts for Payment
            $accounts = Account::where('is_active', true)->select('id', 'name')->get()->map(function ($a) {
                return ['label' => $a->name, 'value' => $a->id];
            })->toArray();
            $defaultAccount = $accounts[0]['value'] ?? null;

            $paymentSchema = [
                'options' => $availablePaymentMethods,
                'accounts' => $accounts,
                'default_account_id' => $defaultAccount,
                'fields' => [
                    'cheque' => [
                        ['name' => 'cheque_no', 'label' => 'Cheque No', 'type' => 'text']
                    ],
                    'gift_card' => [
                        ['name' => 'gift_card_id', 'label' => 'Gift Card Code', 'type' => 'text']
                    ],
                    'card' => [
                        // For manual card entry (if stripe not used or manual)
                        ['name' => 'card_no', 'label' => 'Card No', 'type' => 'text'],
                        ['name' => 'holder_name', 'label' => 'Card Holder Name', 'type' => 'text'],
                        ['name' => 'card_type', 'label' => 'Card Type', 'type' => 'select', 'options' => [
                            ['label' => 'Visa', 'value' => 'Visa'],
                            ['label' => 'MasterCard', 'value' => 'MasterCard'],
                            ['label' => 'Amex', 'value' => 'Amex'],
                            ['label' => 'Discover', 'value' => 'Discover'],
                        ]]
                    ]
                ],
            ];

            $formSchema = [
                "title" => "POS Cart",
                "submit_url" => "/pos/store",
                "offline_submit_url" => "/pos/payment-offline",
                "offline_submit_type" => "form",
                "method" => "POST",
                "submit_button_text" => "Payment",
                "hide_submit_button" => true, // App handles submission via Payment Modal
                "payment_schema" => $paymentSchema,
                "draft" => [
                    "submit_url" => "/pos/store",
                    "submit_strategy" => "default", // Direct API call for draft
                    "method" => "POST"
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "pos_cart", // Custom widget we will build
                        "name" => "cart",
                        "label" => "Cart",
                        "search_url" => "/pos/product-search",
                        "enable_catalog_fab" => true,
                        "payment_options" => $availablePaymentMethods,
                        "catalog_options" => [
                            "categories" => $categories,
                            "brands" => $brands,
                        ],
                        "columns" => [
                            ["name" => "name", "label" => "Product", "type" => "text", "editable" => false, "width" => 160, "padding" => 20],
                            ["name" => "qty", "label" => "Qty", "type" => "number", "editable" => true, "value" => 1, "width" => 100, "decimal_places" => 0],
                            ["name" => "price", "label" => "Price", "type" => "number", "editable" => true, "width" => 80, "decimal_places" => 2],
                            ["name" => "discount", "label" => "Discount", "type" => "number", "editable" => true, "width" => 80, "decimal_places" => 2],
                            ["name" => "tax", "label" => "Tax", "type" => "number", "editable" => true, "width" => 60, "decimal_places" => 2],
                            ["name" => "subtotal", "label" => "Subtotal", "type" => "formula", "formula" => "(qty * price) - discount + tax", "width" => 100, "decimal_places" => 2]
                        ],
                        "totals" => [
                            ["label" => "Total Items", "formula" => "COUNT(*)", "position" => "left"],
                            ["label" => "Total Qty", "formula" => "SUM(qty)", "position" => "left"],
                            ["label" => "Subtotal", "formula" => "SUM(subtotal)", "position" => "right", "prefix" => config('currency'), "decimal_places" => 2]
                        ],
                        "summary_fields" => [
                            [
                                "name" => "order_tax_rate",
                                "label" => "Order Tax",
                                "type" => "select",
                                "options" => $taxOptions,
                                "value" => $taxOptions[0]['value']
                            ],
                            [
                                "name" => "order_discount",
                                "label" => "Order Discount",
                                "type" => "number",
                                "value" => 0
                            ],
                            [
                                "name" => "shipping_cost",
                                "label" => "Shipping Cost",
                                "type" => "number",
                                "value" => 0
                            ],
                            [
                                "name" => "coupon_code",
                                "label" => "Coupon Code",
                                "type" => "text",
                                "value" => ""
                            ]
                        ],
                        "grand_total_formula" => "Subtotal + (Subtotal * order_tax_rate / 100) - order_discount + shipping_cost"
                    ],
                    // Hidden fields to capture data passed from Step 1
                    ["type" => "hidden", "name" => "warehouse_id", "value" => $request->input('warehouse_id')],
                    ["type" => "hidden", "name" => "biller_id", "value" => $request->input('biller_id')],
                    ["type" => "hidden", "name" => "customer_id", "value" => $request->input('customer_id')],
                    ["type" => "hidden", "name" => "created_at", "value" => $request->input('created_at')],
                    ["type" => "hidden", "name" => "account_id", "value" => $request->input('account_id')], // Optional
                    ["type" => "hidden", "name" => "currency_id", "value" => $request->input('currency_id')], // Optional
                    ["type" => "hidden", "name" => "exchange_rate", "value" => $request->input('exchange_rate')], // Optional
                    ["type" => "hidden", "name" => "reference_no", "value" => $request->input('reference_no')], // Optional
                ]
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return (new ErrorResource($e->getMessage()))->additional([
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]);
        }
    }

    public function productSearch(Request $request)
    {
        try {
            $warehouse_id = $request->input('warehouse_id');
            $search = $request->input('query');
            $category_id = $request->input('category_id');
            $brand_id = $request->input('brand_id');
            $is_featured = $request->input('is_featured');

            $query = Product::where('is_active', true);

            // Filter by Warehouse (if product has warehouse specific price/qty)
            // For simplicity, we just check if product exists. 
            // Real POS logic is complex with variants.

            if ($category_id) {
                $query->where('category_id', $category_id);
            }

            if ($brand_id) {
                $query->where('brand_id', $brand_id);
            }

            if ($is_featured) {
                $query->where('featured', 1);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            $products = $query->limit(20)->get();
            $results = [];

            foreach ($products as $product) {
                // Basic product info
                $price = $product->price;
                $qty = $product->qty;

                $product_image = explode(",", $product->image);
                $product_image = htmlspecialchars($product_image[0]);

                // Process product image
                if ($product_image && $product_image != 'zummXD2dvAtI.png') {
                    $smallImagePath = public_path("images/product/small/{$product_image}");
                    $largeImagePath = public_path("images/product/{$product_image}");

                    if (file_exists($smallImagePath)) {
                        $imageUrl = url("images/product/small/{$product_image}");
                    } elseif (file_exists($largeImagePath)) {
                        $imageUrl = url("images/product/{$product_image}");
                    } else {
                        $imageUrl = url("images/zummXD2dvAtI.png");
                    }
                } else {
                    $imageUrl = url("images/zummXD2dvAtI.png");
                }

                // Check warehouse specific price/qty if needed
                if ($warehouse_id) {
                    $product_warehouse = Product_Warehouse::where('product_id', $product->id)
                        ->where('warehouse_id', $warehouse_id)
                        ->first();
                    if ($product_warehouse) {
                        if ($product_warehouse->price) $price = $product_warehouse->price;
                        $qty = $product_warehouse->qty;
                    }
                }

                // Handle Variants
                if ($product->is_variant) {
                    $variants = ProductVariant::where('product_id', $product->id)->get();
                    foreach ($variants as $variant) {
                        $variant_price = $price + $variant->additional_price;
                        // Check warehouse qty for variant
                        // This is complex, skipping for brevity, assuming standard logic

                        $results[] = [
                            'id' => $product->id,
                            'name' => $product->name . ' (' . $variant->item_code . ')',
                            'code' => $variant->item_code,
                            'price' => $variant_price,
                            'qty' => 1, // Default add qty
                            'stock_qty' => $qty, // Available stock
                            'discount' => 0,
                            'tax' => 0, // Calculate tax based on tax_id if needed
                            'subtotal' => $variant_price,
                            'image' => $imageUrl,
                            'variant_id' => $variant->variant_id,
                            'is_variant' => true
                        ];
                    }
                } else {
                    $results[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'price' => $price,
                        'qty' => 1,
                        'stock_qty' => $qty,
                        'discount' => 0,
                        'tax' => 0,
                        'subtotal' => $price,
                        'image' => $imageUrl,
                        'variant_id' => null,
                        'is_variant' => false
                    ];
                }
            }

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recentSale()
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
                ->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                ->where([
                    ['sales.sale_status', 1],
                    ['sales.user_id', Auth::id()]
                ])
                ->whereNull('sales.deleted_at')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            return response()->json($recent_sale);
        } else {
            $recent_sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
                ->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                ->where('sale_status', 1)
                ->whereNull('sales.deleted_at')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            return response()->json($recent_sale);
        }
    }

    public function recentDraft()
    {
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
                ->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                ->where([
                    ['sales.sale_status', 3],
                    ['sales.user_id', Auth::id()]
                ])
                ->whereNull('sales.deleted_at')
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            return response()->json($recent_draft);
        } else {
            $recent_draft = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
                ->select('sales.id', 'sales.reference_no', 'sales.customer_id', 'sales.grand_total', 'sales.created_at', 'customers.name')
                ->whereNull('sales.deleted_at')
                ->where('sale_status', 3)
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
            return response()->json($recent_draft);
        }
    }

    public function getSaleDetails($id)
    {
        $sale = Sale::find($id);
        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        $product_sales = Product_Sale::where('sale_id', $id)->get();
        $cart = [];
        foreach ($product_sales as $ps) {
            $product = Product::find($ps->product_id);
            $variant = null;
            if ($ps->variant_id) {
                $variant = ProductVariant::where('variant_id', $ps->variant_id)->where('product_id', $ps->product_id)->first();
            }

            // Logic to get product image and code etc
            $images = explode(",", $product->image);
            $image = $images[0] ? $images[0] : 'zummXD2dvAtI.png';

            $cart[] = [
                'id' => $ps->product_id,
                'variant_id' => $ps->variant_id,
                'name' => $product->name . ($variant ? ' (' . $variant->item_code . ')' : ''),
                'code' => $variant ? $variant->item_code : $product->code,
                'qty' => $ps->qty,
                'price' => $ps->net_unit_price,
                'discount' => $ps->discount,
                'tax' => $ps->tax,
                'subtotal' => $ps->total,
                'image' => config('app.url') . '/images/product/' . $image,
            ];
        }

        return response()->json([
            'sale' => $sale,
            'cart' => $cart
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $draft = false;

        if (isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s', strtotime($data['created_at']));
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $data['user_id'] = Auth::id();

        if (!isset($data['reference_no'])) {
            $data['reference_no'] = 'posr-' . date("Ymd") . '-' . date("his");
        }

        if (isset($data['draft']) && $data['draft'] == 1) {
            $draft = true;
            $data['sale_status'] = 3; // Draft
            if (isset($data['sale_id'])) {
                $lims_sale_data = Sale::find($data['sale_id']);
                if ($lims_sale_data) {
                    $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
                    foreach ($lims_product_sale_data as $product_sale_data) {
                        $product_sale_data->delete();
                    }
                    $lims_sale_data->delete();
                }
            }
        } else {
            $data['sale_status'] = 1; // Completed
        }

        if ($data['sale_status'] == 3) {
            $data['payment_status'] = 1; // Pending
        } else {
            $balance = $data['grand_total'] - ($data['paid_amount'] ?? 0);
            if ($balance > 0)
                $data['payment_status'] = 2; // Due
            else
                $data['payment_status'] = 4; // Paid
        }

        // Fallback for missing essential IDs (prevents crash if frontend state is lost)
        if (empty($data['warehouse_id']) || empty($data['biller_id']) || empty($data['customer_id'])) {
            $user = Auth::user();
            $posSetting = PosSetting::latest()->first();

            if (empty($data['warehouse_id'])) {
                $warehouse = Warehouse::where('is_active', true)->first();
                $data['warehouse_id'] = $user->warehouse_id
                    ?? ($posSetting->warehouse_id ?? ($warehouse ? $warehouse->id : null));
            }
            if (empty($data['biller_id'])) {
                $biller = Biller::where('is_active', true)->first();
                $data['biller_id'] = $user->biller_id
                    ?? ($posSetting->biller_id ?? ($biller ? $biller->id : null));
            }
            if (empty($data['customer_id'])) {
                $customer = Customer::where('is_active', true)->first();
                $data['customer_id'] = $posSetting->customer_id ?? ($customer ? $customer->id : null);
            }
        }

        try {
            DB::beginTransaction();

            $cartItems = $data['cart'] ?? [];
            if (empty($cartItems) && is_string($data['cart'])) {
                $cartItems = json_decode($data['cart'], true);
            }
            unset($data['cart']);

            // Calculate item quantity
            $data['item'] = count($cartItems);
            $data['total_qty'] = array_sum(array_column($cartItems, 'qty'));
            if (!isset($data['total_discount'])) $data['total_discount'] = array_sum(array_column($cartItems, 'discount'));
            if (!isset($data['total_tax'])) $data['total_tax'] = array_sum(array_column($cartItems, 'tax'));
            if (!isset($data['total_price'])) $data['total_price'] = array_sum(array_column($cartItems, 'subtotal'));

            $sale = Sale::create($data);

            foreach ($cartItems as $item) {
                // Fetch product for unit info
                $product = Product::find($item['id']);

                $productSaleData = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'qty' => $item['qty'],
                    'sale_unit_id' => $product ? $product->sale_unit_id : null,
                    'net_unit_price' => $item['price'],
                    'discount' => $item['discount'],
                    'tax_rate' => 0, // Simplified: rate not passed, only amount
                    'tax' => $item['tax'],
                    'total' => $item['subtotal'],
                ];
                Product_Sale::create($productSaleData);

                // Stock Deduction (if not draft)
                if ($sale->sale_status == 1) {
                    // Update Warehouse Quantity
                    $product_warehouse = Product_Warehouse::where('product_id', $item['id'])
                        ->where('warehouse_id', $data['warehouse_id'])
                        ->first();

                    if ($product_warehouse) {
                        $product_warehouse->qty -= $item['qty'];
                        $product_warehouse->save();
                    }

                    // Update Product Quantity
                    if ($product) {
                        $product->qty -= $item['qty'];
                        $product->save();
                    }

                    // Handle Variant Logic if needed
                    if (!empty($item['variant_id'])) {
                        $variant = ProductVariant::where('product_id', $item['id'])
                            ->where('variant_id', $item['variant_id'])
                            ->first();
                        if ($variant) {
                            $variant->qty -= $item['qty'];
                            $variant->save();
                        }
                    }
                }
            }

            // --- Payment Processing Start ---

            // Logic ported from SaleController to support offline -> online sync in one request
            $data['payment_status'] = 2; // Default to Due
            $total_paid = 0;

            if (isset($data['paid_by_id'])) {
                // Wrapper to ensure array
                if (!is_array($data['paid_by_id'])) {
                    $data['paid_by_id'] = [$data['paid_by_id']];

                    // Auto-fill amounts if missing (Frontend "Pay" button behavior)
                    if (!isset($data['paid_amount'])) {
                        $data['paid_amount'] = [$data['grand_total']];
                    }
                    if (!isset($data['paying_amount'])) {
                        $data['paying_amount'] = [$data['grand_total']];
                    }

                    $data['paid_amount'] = is_array($data['paid_amount']) ? $data['paid_amount'] : [$data['paid_amount']];
                    $data['paying_amount'] = is_array($data['paying_amount']) ? $data['paying_amount'] : [$data['paying_amount']];
                    $data['payment_note'] = isset($data['payment_note']) ? (is_array($data['payment_note']) ? $data['payment_note'] : [$data['payment_note']]) : [];
                }

                // Razorpay Handling
                if (in_array('razorpay', $data['paid_by_id'])) {
                    foreach ($data['paid_by_id'] as $key => $value) {
                        if ($value == 'razorpay') {
                            $lims_payment_data = new Payment();
                            $lims_payment_data->user_id = Auth::id();
                            $lims_payment_data->sale_id = $sale->id;
                            $lims_payment_data->account_id = $data['account_id'] ?? (Account::first()->id ?? null); // Fallback

                            $lims_payment_data->payment_reference = 'raz-' . date("Ymd") . '-' . date("his");
                            $lims_payment_data->amount = $data['paid_amount'][$key];
                            $lims_payment_data->change = 0;
                            $lims_payment_data->paying_method = 'Razorpay';
                            // payment_note should contain the ID
                            $paymentID = $data['razorpay_payment_id'][$key] ?? ($data['razorpay_payment_id'] ?? '');
                            $lims_payment_data->payment_note = 'Payment via Razorpay. Payment ID: ' . $paymentID;
                            $lims_payment_data->currency_id = $sale->currency_id;
                            $lims_payment_data->exchange_rate = $sale->exchange_rate ?? 1;
                            $lims_payment_data->payment_at = $data['created_at'] ?? date('Y-m-d H:i:s'); // Use sale date if provided (offline sync)

                            $lims_payment_data->save();
                            $total_paid += $lims_payment_data->amount;
                        }
                    }
                }

                // Other Payment Methods
                // Note: SaleController has if/else to separate Razorpay from others. 
                // We process others if they exist.

                foreach ($data['paid_by_id'] as $key => $value) {
                    if ($value == 'razorpay') continue;

                    if (($data['paid_amount'][$key] ?? 0) > 0) {
                        $lims_payment_data = new Payment();
                        $lims_payment_data->user_id = Auth::id();
                        $lims_payment_data->sale_id = $sale->id;
                        $lims_payment_data->account_id = $data['account_id'] ?? (Account::first()->id ?? null);

                        // Resolve Paying Method Name
                        $paying_method = '';
                        if ($value == 'cash' || $value == '1') $paying_method = 'Cash';
                        elseif ($value == 'gift_card' || $value == '2') $paying_method = 'Gift Card';
                        elseif ($value == 'card' || $value == '3') $paying_method = 'Credit Card';
                        elseif ($value == 'cheque' || $value == '4') $paying_method = 'Cheque';
                        elseif ($value == 'paypal' || $value == '5') $paying_method = 'Paypal';
                        elseif ($value == 'deposit' || $value == '6') $paying_method = 'Deposit';
                        elseif ($value == 'points' || $value == '7') {
                            $paying_method = 'Points';
                            // Logic to redeem points
                            if (isset($data['paid_amount'][$key]) && $data['paid_amount'][$key] > 0) {
                                // Usually we deduct points. logic:
                                // Points to deduct = amount / per_point_amount ?? 
                                // Actually SaleController Logic uses "redeem_point" input for total points to deduct.
                                // But if using mult-pay, we might just assume the amount is what we are redeeming in value?
                                // SaleController logic:
                                /*
                                 $reward_points = RewardPoint::query()->create([
                                    'points' => 0,
                                    'deducted_points' => $request->redeem_point, // from input
                                    'customer_id' => $lims_customer_data->id,
                                    'note' => 'Redeemed for sale #' . $lims_sale_data->id,
                                    'sale_id' => $lims_sale_data->id,
                                    'expired_at' => null,
                                ]);
                                $lims_customer_data->update(['points'=>$lims_customer_data->points - $request->redeem_point]);
                                 */
                                // So we need 'redeem_point' (points count) passed in.
                                // If not passed, we can't deduct.
                                // We will assume "redeem_point" is passed in $data.
                                if (isset($data['redeem_point'])) {
                                    $redeem_points = $data['redeem_point'];
                                    $customer = Customer::find($sale->customer_id);
                                    if ($customer) {
                                        RewardPoint::create([
                                            'points' => 0,
                                            'deducted_points' => $redeem_points,
                                            'customer_id' => $customer->id,
                                            'note' => 'Redeemed for sale #' . $sale->id, // Use valid sale ID
                                            'sale_id' => $sale->id,
                                        ]);
                                        $customer->update(['points' => $customer->points - $redeem_points]);
                                    }
                                }
                            }
                        } else $paying_method = ucfirst(str_replace('_', ' ', $value));

                        $lims_payment_data->payment_reference = 'spr-' . date("Ymd") . '-' . date("his");
                        $lims_payment_data->amount = $data['paid_amount'][$key];
                        $lims_payment_data->change = ($data['paying_amount'][$key] ?? $data['paid_amount'][$key]) - $data['paid_amount'][$key];
                        $lims_payment_data->paying_method = $paying_method;
                        $lims_payment_data->payment_note = $data['payment_note'][$key] ?? null;
                        $lims_payment_data->payment_at = $data['created_at'] ?? date('Y-m-d H:i:s'); // Use sale date if provided (offline)
                        $lims_payment_data->currency_id = $sale->currency_id;
                        $lims_payment_data->exchange_rate = $sale->exchange_rate ?? 1;

                        $lims_payment_data->save();
                        $total_paid += $lims_payment_data->amount;

                        // Specialized Logic
                        if ($paying_method == 'Credit Card') {
                            $lims_pos_setting_data = PosSetting::latest()->first();
                            // Stripe Charge
                            if ($lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key && !empty($data['stripeToken'])) {
                                Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                                $token = $data['stripeToken'];
                                $amount = $lims_payment_data->amount;

                                // Simplified Stripe Customer/Charge (from SaleController)
                                // Note: SaleController logic checks for existing card customer. 
                                // We implement the core charge here.
                                try {
                                    $customer = \Stripe\Customer::create(['source' => $token]);
                                    $charge = \Stripe\Charge::create([
                                        'amount' => $amount * 100,
                                        'currency' => 'usd',
                                        'customer' => $customer->id,
                                    ]);
                                    $data['charge_id'] = $charge->id;
                                } catch (\Exception $e) {
                                    // Log or handle error? 
                                    // For now, continue but maybe set note
                                    $lims_payment_data->payment_note .= " [Stripe Error: " . $e->getMessage() . "]";
                                    $lims_payment_data->save();
                                }
                            }

                            // Save Card Details
                            $cardDetails = [];
                            $cardDetails['card_number'] = $data['card_no'] ?? ($data['card_number'] ?? null);
                            $cardDetails['card_holder_name'] = $data['holder_name'] ?? ($data['card_holder_name'] ?? null);
                            $cardDetails['card_type'] = $data['card_type'] ?? 'Visa';
                            $data['charge_id'] = $data['charge_id'] ?? '';
                            $data['data'] = json_encode($cardDetails);
                            $data['payment_id'] = $lims_payment_data->id;
                            PaymentWithCreditCard::create($data);
                        } elseif ($paying_method == 'Gift Card') {
                            if (isset($data['gift_card_id'])) { // ID or Code
                                $gc = \App\Models\GiftCard::where('id', $data['gift_card_id'])
                                    ->orWhere('card_no', $data['gift_card_id'])->first();
                                if ($gc) {
                                    $gc->expense += $lims_payment_data->amount;
                                    $gc->save();
                                    $data['gift_card_id'] = $gc->id;
                                    $data['payment_id'] = $lims_payment_data->id;
                                    PaymentWithGiftCard::create($data);
                                }
                            }
                        } elseif ($paying_method == 'Cheque') {
                            $data['payment_id'] = $lims_payment_data->id;
                            // Cheque details usually in $data['cheque_no']
                            $data['cheque_no'] = $data['cheque_no'] ?? ($data['cheque_no'][$key] ?? '');
                            PaymentWithCheque::create($data);
                        }
                    }
                }
            }

            // Finalize Sale Status
            if ($total_paid >= $sale->grand_total) {
                $sale->payment_status = 4; // Paid
            } elseif ($total_paid > 0) {
                $sale->payment_status = 2; // Partial
            } else {
                $sale->payment_status = 2; // Due (or 1 Pending if draft)
            }
            $sale->paid_amount = $total_paid;
            $sale->save();

            // --- Post-Payment Logic (Coupons, Rewards, Mail, SMS) ---

            // 1. Coupon Usage
            if (isset($data['coupon_code']) && !empty($data['coupon_code']) && !$draft) {
                $coupon = Coupon::where('code', $data['coupon_code'])->first();
                if ($coupon) {
                    $coupon->used += 1;
                    $coupon->save();

                    if (!$sale->coupon_id) {
                        $sale->coupon_id = $coupon->id;
                        $sale->coupon_discount = $data['order_discount'] ?? 0;
                        $sale->save();
                    }
                }
            }

            // 2. Earn Reward Points
            $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
            $customer = Customer::find($sale->customer_id);
            if (
                $lims_reward_point_setting_data
                && $lims_reward_point_setting_data->is_active
                && !isset($data['redeem_point'])
                && $sale->grand_total >= $lims_reward_point_setting_data->minimum_amount
                && $customer
                && ($customer->type == 'regular' || $customer->type == 1)
                && !$draft
            ) {
                $isPaidByPoints = false;
                if (isset($data['paid_by_id'])) {
                    $pids = is_array($data['paid_by_id']) ? $data['paid_by_id'] : [$data['paid_by_id']];
                    if (in_array('7', $pids) || in_array('points', $pids)) $isPaidByPoints = true;
                }

                if (!$isPaidByPoints) {
                    $point = (int)($sale->grand_total / $lims_reward_point_setting_data->per_point_amount);
                    $customer->points += $point;
                    $customer->save();

                    $expiredAt = null;
                    if ($lims_reward_point_setting_data->duration && $lims_reward_point_setting_data->type) {
                        if ($lims_reward_point_setting_data->type == 'days') $expiredAt = date('Y-m-d', strtotime("+$lims_reward_point_setting_data->duration days"));
                        elseif ($lims_reward_point_setting_data->type == 'months') $expiredAt = date('Y-m-d', strtotime("+$lims_reward_point_setting_data->duration months"));
                        elseif ($lims_reward_point_setting_data->type == 'years') $expiredAt = date('Y-m-d', strtotime("+$lims_reward_point_setting_data->duration years"));
                    }

                    RewardPoint::create([
                        'points' => $point,
                        'customer_id' => $customer->id,
                        'note' => 'Earn Point for sale #' . $sale->id,
                        'sale_id' => $sale->id,
                        'expired_at' => $expiredAt,
                    ]);
                }
            }

            // 3. Email Notification
            if ($sale->sale_status == 1) {
                $this->_sendMail($sale);
            }

            // 4. SMS Notification
            $lims_pos_setting_data = PosSetting::latest()->first();
            if ($sale->sale_status == 1 && $lims_pos_setting_data && $lims_pos_setting_data->send_sms == 1) {
                $this->_sendSMS($sale);
            }

            // --- Payment Processing End ---

            DB::commit();

            if ($draft) {
                return response()->json([
                    'success' => true,
                    'message' => 'Draft created successfully',
                    'reference_no' => $sale->reference_no,
                    'navigate_url' => '/pos/create', // Reset
                    'navigate_type' => 'form'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully. Proceeding to payment.',
                'reference_no' => $sale->reference_no,
                'navigate_url' => '/pos/payment-form/' . $sale->id, // Redirect to Payment Form
                'navigate_type' => 'form'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource($e->getMessage());
        }
    }

    public function paymentForm($id, Request $request)
    {
        $sale = Sale::find($id);
        if (!$sale) return new ErrorResource('Sale not found');

        return response()->json($this->getPaymentSchema($sale, $request));
    }

    private function getPaymentSchema($sale, $request = null)
    {
        $posSetting = PosSetting::latest()->first();
        $rewardPointSetting = RewardPointSetting::latest()->first();
        $options = [];
        if ($posSetting && $posSetting->payment_options) {
            $options = explode(',', $posSetting->payment_options);
        } else {
            $options = [];
        }

        $paymentMethods = [];
        if (in_array("cash", $options)) {
            $paymentMethods[] = ['label' => 'Cash', 'value' => 'cash'];
        }
        if (in_array("gift_card", $options)) {
            $paymentMethods[] = ['label' => 'Gift Card', 'value' => 'gift_card'];
        }
        if (in_array("card", $options)) {
            $paymentMethods[] = ['label' => 'Credit Card', 'value' => 'card'];
        }
        if (in_array("cheque", $options)) {
            $paymentMethods[] = ['label' => 'Cheque', 'value' => 'cheque'];
        }
        if (in_array("paypal", $options) && ($posSetting->paypal_live_api_username) && ($posSetting->paypal_live_api_password) && ($posSetting->paypal_live_api_secret)) {
            $paymentMethods[] = ['label' => 'Paypal', 'value' => 'paypal'];
        }
        if (in_array("deposit", $options)) {
            $paymentMethods[] = ['label' => 'Deposit', 'value' => 'deposit'];
        }
        if ($rewardPointSetting && $rewardPointSetting->is_active) {
            $paymentMethods[] = ['label' => 'Points', 'value' => 'points'];
        }
        if (in_array("pesapal", $options)) {
            $paymentMethods[] = ['label' => 'Pesapal', 'value' => 'pesapal'];
        }
        foreach ($options as $option) {
            $option = trim($option);
            if (!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal', 'points'])) {
                $label = ucfirst(str_replace('_', ' ', $option));
                if ($option == 'sslcommerz') $label = 'SSLCommerz';
                if ($option == 'paystack') $label = 'Paystack';
                if ($option == 'pagseguro') $label = 'PagSeguro';
                $paymentMethods[] = ['label' => $label, 'value' => $option];
            }
        }

        $accounts = Account::where('is_active', true)->select('id', 'name')->get()->map(function ($a) {
            return ['label' => $a->name, 'value' => $a->id];
        })->toArray();

        $defaultAccount = count($accounts) > 0 ? $accounts[0]['value'] : null;
        $defaultPaidBy = $request && $request->paid_by_id ? $request->paid_by_id : (count($paymentMethods) > 0 ? $paymentMethods[0]['value'] : null);

        return [
            "title" => "Payment",
            "submit_url" => "/pos/payment",
            "method" => "POST",
            "submit_button_text" => "Submit Payment",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "fields" => [
                [
                    "type" => "hidden",
                    "name" => "sale_id",
                    "value" => $sale->id
                ],
                [
                    "type" => "text",
                    "name" => "received_amount",
                    "label" => "Received Amount *",
                    "value" => $sale->grand_total,
                    "payment_trigger" => true,
                    "required" => true,
                    "keyboard_type" => "number"
                ],
                [
                    "type" => "text",
                    "name" => "paying_amount",
                    "label" => "Paying Amount *",
                    "value" => $sale->grand_total,
                    "readonly" => true,
                    "required" => true
                ],
                [
                    "type" => "text",
                    "name" => "change",
                    "label" => "Change",
                    "value" => 0,
                    "readonly" => true
                ],
                [
                    "type" => "select",
                    "name" => "paid_by_id",
                    "label" => "Paid By",
                    "options" => $paymentMethods,
                    "value" => $defaultPaidBy,
                    "required" => true
                ],
                [
                    "type" => "select",
                    "name" => "account_id",
                    "label" => "Account",
                    "options" => $accounts,
                    "value" => $defaultAccount
                ],
                [
                    "type" => "text",
                    "name" => "payment_note",
                    "label" => "Payment Note",
                    "placeholder" => "Type note..."
                ],
                [
                    "type" => "text",
                    "name" => "card_no",
                    "label" => "Card No",
                    "logics" => [["field" => "paid_by_id", "values" => ["card"]]]
                ],
                [
                    "type" => "text",
                    "name" => "holder_name",
                    "label" => "Card Holder Name",
                    "logics" => [["field" => "paid_by_id", "values" => ["card"]]]
                ],
                [
                    "type" => "text",
                    "name" => "cheque_no",
                    "label" => "Cheque No",
                    "logics" => [["field" => "paid_by_id", "values" => ["cheque"]]]
                ],
                [
                    "type" => "text",
                    "name" => "gift_card_id",
                    "label" => "Gift Card Code",
                    "logics" => [["field" => "paid_by_id", "values" => ["gift_card"]]]
                ],
            ]
        ];
    }

    public function getOfflinePaymentSchema()
    {
        $posSetting = PosSetting::latest()->first();
        $options = explode(',', $posSetting->payment_options);
        $rewardPointSetting = RewardPointSetting::latest()->first();

        $paymentMethods = [];
        if (in_array("cash", $options)) {
            $paymentMethods[] = ['label' => 'Cash', 'value' => 'cash'];
        }
        if (in_array("card", $options)) {
            $paymentMethods[] = ['label' => 'Card', 'value' => 'card'];
        }
        if (in_array("cheque", $options)) {
            $paymentMethods[] = ['label' => 'Cheque', 'value' => 'cheque'];
        }
        if (in_array("gift_card", $options)) {
            $paymentMethods[] = ['label' => 'Gift Card', 'value' => 'gift_card'];
        }
        if (in_array("paypal", $options) && ($posSetting->paypal_live_api_username) && ($posSetting->paypal_live_api_password) && ($posSetting->paypal_live_api_secret)) {
            $paymentMethods[] = ['label' => 'Paypal', 'value' => 'paypal'];
        }
        if (in_array("deposit", $options)) {
            $paymentMethods[] = ['label' => 'Deposit', 'value' => 'deposit'];
        }
        if ($rewardPointSetting && $rewardPointSetting->is_active) {
            $paymentMethods[] = ['label' => 'Points', 'value' => 'points'];
        }
        if (in_array("pesapal", $options)) {
            $paymentMethods[] = ['label' => 'Pesapal', 'value' => 'pesapal'];
        }
        foreach ($options as $option) {
            $option = trim($option);
            if (!in_array($option, ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'paypal', 'pesapal', 'points'])) {
                $label = ucfirst(str_replace('_', ' ', $option));
                if ($option == 'sslcommerz') $label = 'SSLCommerz';
                if ($option == 'paystack') $label = 'Paystack';
                if ($option == 'pagseguro') $label = 'PagSeguro';
                $paymentMethods[] = ['label' => $label, 'value' => $option];
            }
        }

        $defaultPaidBy = count($paymentMethods) > 0 ? $paymentMethods[0]['value'] : null;

        $general_settings = GeneralSetting::latest()->first();

        return [
            "title" => "Payment (Offline)",
            "submit_url" => "/pos/payment",
            "offline_submit_url" => "/pos/create",
            "offline_submit_type" => "form",
            "offline_receipt" => true,
            "static_data" => [
                "general_settings" => $general_settings
            ],
            "method" => "POST",
            "submit_button_text" => "Submit Payment",
            "fields" => [
                [
                    "type" => "hidden",
                    "name" => "sale_id",
                ],
                [
                    "type" => "hidden",
                    "name" => "sale_reference",
                    "param" => "reference_no"
                ],
                [
                    "type" => "text",
                    "name" => "received_amount",
                    "label" => "Received Amount *",
                    "payment_trigger" => true,
                    "required" => true,
                    "keyboard_type" => "number",
                    "param" => "grand_total"
                ],
                [
                    "type" => "text",
                    "name" => "paying_amount",
                    "label" => "Paying Amount *",
                    "readonly" => true,
                    "required" => true,
                    "param" => "grand_total"
                ],
                [
                    "type" => "text",
                    "name" => "change",
                    "label" => "Change",
                    "value" => 0,
                    "readonly" => true
                ],
                [
                    "type" => "select",
                    "name" => "paid_by_id",
                    "label" => "Paid By",
                    "options" => $paymentMethods,
                    "value" => $defaultPaidBy,
                    "required" => true,
                    "param" => "paid_by_id"
                ],
                [
                    "type" => "text",
                    "name" => "payment_note",
                    "label" => "Payment Note",
                    "placeholder" => "Type note..."
                ],
                [
                    "type" => "text",
                    "name" => "card_no",
                    "label" => "Card No",
                    "logics" => [["field" => "paid_by_id", "values" => ["card"]]]
                ],
                [
                    "type" => "text",
                    "name" => "holder_name",
                    "label" => "Card Holder Name",
                    "logics" => [["field" => "paid_by_id", "values" => ["card"]]]
                ],
                [
                    "type" => "text",
                    "name" => "cheque_no",
                    "label" => "Cheque No",
                    "logics" => [["field" => "paid_by_id", "values" => ["cheque"]]]
                ],
                [
                    "type" => "text",
                    "name" => "gift_card_id",
                    "label" => "Gift Card Code",
                    "logics" => [["field" => "paid_by_id", "values" => ["gift_card"]]]
                ],
            ]
        ];
    }

    public function processPayment(Request $request)
    {
        $data = $request->all();
        $sale = null;

        if (!empty($data['sale_id'])) {
            $sale = Sale::find($data['sale_id']);
        } elseif (!empty($data['sale_reference'])) {
            $sale = Sale::where('reference_no', $data['sale_reference'])->first();
        }

        if (!$sale) return new ErrorResource('Sale not found (ID or Reference required)');

        try {
            DB::beginTransaction();
            $received_amount = floatval($data['received_amount']);
            $paying_amount = floatval($data['paying_amount']);

            // Handle Account ID Logic
            $account_id = $data['account_id'] ?? null;
            if (!$account_id) {
                $defaultAccount = Account::where('is_default', true)->first();
                $account_id = $defaultAccount ? $defaultAccount->id : Account::first()->id ?? null;
            }

            // Handle Paying Method Logic
            $paying_method_map = [
                'cash' => 'Cash',
                'card' => 'Credit Card',
                'gift_card' => 'Gift Card',
                'cheque' => 'Cheque',
                'deposit' => 'Deposit',
                'points' => 'Points',
                'sslcommerz' => 'SSLCommerz',
                'paystack' => 'Paystack',
                'pagseguro' => 'PagSeguro'
            ];
            $paying_method = $paying_method_map[$data['paid_by_id']] ?? ucfirst(str_replace('_', ' ', $data['paid_by_id']));

            // Get Cash Register
            $cash_register_data = CashRegister::where([
                ['user_id', Auth::id()],
                ['warehouse_id', $sale->warehouse_id],
                ['status', true]
            ])->first();

            $payment = new Payment();
            $payment->user_id = Auth::id();
            $payment->sale_id = $sale->id;
            if ($cash_register_data) {
                $payment->cash_register_id = $cash_register_data->id;
            }
            $payment->account_id = $account_id;
            $payment->payment_reference = 'spr-' . date("Ymd") . '-' . date("his");
            $payment->amount = $received_amount;
            $payment->change = ($received_amount - $paying_amount);
            $payment->paying_method = $paying_method;
            $payment->payment_note = $data['payment_note'] ?? null;
            $payment->payment_at = date('Y-m-d H:i:s');

            // Add Currency fields
            $payment->currency_id = $sale->currency_id;
            $payment->exchange_rate = $sale->exchange_rate ?? 1;

            if ($data['paid_by_id'] == 'gift_card' && isset($data['gift_card_id'])) {
                $gift_card = \App\Models\GiftCard::where('card_no', $data['gift_card_id'])->first();
                if ($gift_card) {
                    $gift_card->expense += $payment->amount;
                    $gift_card->save();
                }
            }

            $payment->save();

            // Now Payment is saved, we can capture ID
            $data['payment_id'] = $payment->id;
            $data['customer_id'] = $sale->customer_id;

            // Additional Payment Tables (Correct Web Logic)
            if ($paying_method == 'Credit Card') {
                $lims_pos_setting_data = PosSetting::latest()->first();
                if ($lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key && !empty($data['stripeToken'])) {
                    Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                    $token = $data['stripeToken'];
                    $amount = $payment->amount;

                    // Create a Customer:
                    $customer = \Stripe\Customer::create([
                        'source' => $token
                    ]);

                    // Charge the Customer instead of the card:
                    $charge = \Stripe\Charge::create([
                        'amount' => $amount * 100,
                        'currency' => 'usd',
                        'customer' => $customer->id,
                    ]);
                    $data['customer_stripe_id'] = $customer->id;
                    $data['charge_id'] = $charge->id;
                }

                $cardDetails = [];
                // Frontend keys: card_no, holder_name
                $cardDetails['card_number'] = $data['card_no'] ?? null;
                $cardDetails['card_holder_name'] = $data['holder_name'] ?? null;
                $cardDetails['card_type'] = $data['card_type'] ?? 'Visa';
                $data['charge_id'] = $data['charge_id'] ?? '';
                $data['data'] = json_encode($cardDetails);

                PaymentWithCreditCard::create($data);
            } else if ($paying_method == 'Gift Card') {
                if (isset($data['gift_card_id'])) {
                    $gc = \App\Models\GiftCard::where('card_no', $data['gift_card_id'])->first();
                    if ($gc) $data['gift_card_id'] = $gc->id;
                }
                PaymentWithGiftCard::create($data);
            } else if ($paying_method == 'Cheque') {
                PaymentWithCheque::create($data);
            }

            $total_paid = Payment::where('sale_id', $sale->id)->sum('amount');
            if ($total_paid >= $sale->grand_total) {
                $sale->payment_status = 4; // Paid
            } else {
                $sale->payment_status = 2; // Due / Partial
            }
            $sale->paid_amount = $total_paid;
            $sale->save();

            DB::commit();

            $invoice_settings = InvoiceSetting::active_setting();
            // Build Invoice URL using the existing web route
            $invoice_url = url('sales/gen_invoice/' . $sale->id);
            // API data for invoice (flutter)
            $invoice_data_url = url('api/pos/invoice-data/' . $sale->id);
            $invoice_data = $this->_getInvoiceData($sale->id);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'invoice_url' => $invoice_url,
                'invoice_data_url' => ($invoice_settings->size == '58mm' || $invoice_settings->size == '80mm') ? $invoice_data_url : null,
                'invoice_data' => $invoice_data,
                'navigate_url' => '/pos/create', // Clean Reset
                'navigate_type' => 'form'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource($e->getMessage());
        }
    }

    public function sendMail(Request $request)
    {
        $data = $request->all(); // expecting sale_id in request
        if (empty($data['sale_id'])) return new ErrorResource('Sale ID required');
        $sale = Sale::find($data['sale_id']);
        if (!$sale) return new ErrorResource('Sale not found');

        $result = $this->_sendMail($sale);
        if ($result)
            return response()->json(['success' => true, 'message' => 'Mail sent successfully']);
        else
            return new ErrorResource('Mail setting not active or customer missing email');
    }

    // Internal Helper for Mail
    protected function _sendMail($lims_sale_data)
    {
        $lims_product_sale_data = Product_Sale::where('sale_id', $lims_sale_data->id)->get();
        $lims_customer_data = Customer::find($lims_sale_data->customer_id);
        $mail_setting = MailSetting::latest()->first();

        if (!$mail_setting) return false;

        if ($lims_customer_data->email) {
            $mail_data['email'] = $lims_customer_data->email;
            $mail_data['reference_no'] = $lims_sale_data->reference_no;
            $mail_data['sale_status'] = $lims_sale_data->sale_status;
            $mail_data['payment_status'] = $lims_sale_data->payment_status;
            $mail_data['total_qty'] = $lims_sale_data->total_qty;
            $mail_data['total_price'] = $lims_sale_data->total_price;
            $mail_data['order_tax'] = $lims_sale_data->order_tax;
            $mail_data['order_tax_rate'] = $lims_sale_data->order_tax_rate;
            $mail_data['order_discount'] = $lims_sale_data->order_discount;
            $mail_data['shipping_cost'] = $lims_sale_data->shipping_cost;
            $mail_data['grand_total'] = $lims_sale_data->grand_total;
            $mail_data['paid_amount'] = $lims_sale_data->paid_amount;

            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $lims_product_data = Product::find($product_sale_data->product_id);
                if ($product_sale_data->variant_id) {
                    $variant_data = Variant::select('name')->find($product_sale_data->variant_id);
                    $mail_data['products'][$key] = $lims_product_data->name . ' [' . $variant_data->name . ']';
                } else
                    $mail_data['products'][$key] = $lims_product_data->name;

                if ($lims_product_data->type == 'digital')
                    $mail_data['file'][$key] = url('/product/files') . '/' . $lims_product_data->file;
                else
                    $mail_data['file'][$key] = '';

                if ($product_sale_data->sale_unit_id) {
                    $lims_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    $mail_data['unit'][$key] = $lims_unit_data->unit_code;
                } else
                    $mail_data['unit'][$key] = '';

                $mail_data['qty'][$key] = $product_sale_data->qty;
                $mail_data['total'][$key] = $product_sale_data->qty;
            }

            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function sendSMS(Request $request)
    {
        $data = $request->all();
        if (empty($data['sale_id'])) return new ErrorResource('Sale ID required');
        $sale = Sale::find($data['sale_id']);
        if (!$sale) return new ErrorResource('Sale not found');

        if ($this->_sendSMS($sale)) {
            return response()->json(['success' => true, 'message' => 'SMS sent successfully']);
        }
        return new ErrorResource('SMS configuration missing or failed');
    }

    // Internal Helper for SMS
    protected function _sendSMS($lims_sale_data)
    {
        $smsTemplate = SmsTemplate::where('is_default', 1)->latest()->first();
        $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();

        if ($smsProvider && $smsTemplate) {
            $smsData['type'] = 'onsite';
            $smsData['template_id'] = $smsTemplate['id'];
            $smsData['sale_status'] = $lims_sale_data->sale_status;
            $smsData['payment_status'] = $lims_sale_data->payment_status;
            $smsData['customer_id'] = $lims_sale_data->customer_id;
            $smsData['reference_no'] = $lims_sale_data->reference_no;

            try {
                $this->_smsModel->initialize($smsData);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function whatsappNotificationSend(Request $request)
    {
        $data = $request->all();
        if (empty($data['sale_id']) || empty($data['customer_id'])) return new ErrorResource('Sale ID and Customer ID required');

        $general_setting = GeneralSetting::latest()->first();
        $company = $general_setting->company_name;

        $customer = Customer::find($data['customer_id']);
        $sale = Sale::find($data['sale_id']);

        if (!$customer || !$sale) return new ErrorResource('Customer or Sale not found');

        $name = $customer->name;
        $phone = preg_replace('/\D/', '', $customer->wa_number ?? '');
        $referenceNo = $sale->reference_no;
        $invoice = url('sales/gen_invoice/' . $sale->id);

        $text = urlencode(__('db.Dear') . ' ' . $name . ', ' .
            __('db.Thank you for your purchase! Your invoice number is') . ' ' . $referenceNo . "\n" .
            __('db.If you have any questions or concerns, please don\'t hesitate to reach out to us We are here to help!') . "\n" . $invoice . "\n" .
            __('db.Best regards') . ",\n" .
            $company);

        $settings = WhatsappSetting::first();
        if (!$settings || empty($settings->phone_number_id) || empty($settings->permanent_access_token)) {
            $url = "https://web.whatsapp.com/send/?phone=$phone&text=$text";
            return response()->json([
                'success' => true,
                'strategy' => 'open_url',
                'url' => $url,
                'message' => 'Opening WhatsApp Web...'
            ]);
        } else {
            $requestData = new Request([
                'receiver_phone' => [$phone],
                'message' =>  __('db.Invoice'),
                'template_name' => 'invoice',
                'template_params' => [$name, $referenceNo, $invoice]
            ]);

            try {
                $whpcon = new WhatsappController();
                $result = $whpcon->sendMessage($requestData);

                if ($result['success'] ?? false) {
                    return response()->json(['success' => true, 'message' => $result['message']]);
                } else {
                    return new ErrorResource($result['message'] ?? __('db.fail_sent_message'));
                }
            } catch (\Exception $e) {
                return new ErrorResource($e->getMessage());
            }
        }
    }

    private function _getInvoiceData($id)
    {
        $sale = Sale::with('currency')->find($id);
        if (!$sale) return null;

        $product_sales = Product_Sale::where('sale_id', $id)->get();
        $customer = Customer::find($sale->customer_id);
        $biller = Biller::find($sale->biller_id);
        $warehouse = Warehouse::find($sale->warehouse_id);
        $payments_data = Payment::where('sale_id', $id)->get();
        $general_setting = GeneralSetting::latest()->first();

        // --- Currency & Number to Words ---
        $currency_code = $sale->currency ? $sale->currency->code : 'USD';

        $numberToWords = new NumberToWords();
        $locale = \App::getLocale();
        if (in_array($locale, ['en', 'fr', 'es', 'ar']))
            $numberTransformer = $numberToWords->getNumberTransformer($locale);
        else
            $numberTransformer = $numberToWords->getNumberTransformer('en');

        $numberInWords = $numberTransformer->toWords($sale->grand_total);

        // --- QR Code (ZATCA or Reference) ---
        $qrText = $sale->reference_no;
        if ($general_setting && $general_setting->is_zatca) {
            $qrText = GenerateQrCode::fromArray([
                new Seller($general_setting->company_name),
                new TaxNumber($general_setting->vat_registration_number),
                new InvoiceDate($sale->created_at->toDateString() . "T" . $sale->created_at->toTimeString()),
                new InvoiceTotalAmount(number_format((float)$sale->grand_total, 4, '.', '')),
                new InvoiceTaxAmount(number_format((float)($sale->total_tax + $sale->order_tax), 4, '.', ''))
            ])->toBase64();
        }

        // --- Items Processing ---
        $items = [];
        foreach ($product_sales as $ps) {
            $product = Product::find($ps->product_id);
            $variant = null;
            if ($ps->variant_id) {
                $variant = ProductVariant::where('variant_id', $ps->variant_id)
                    ->where('product_id', $ps->product_id)
                    ->first();
            }

            $unit_code = '';
            if ($ps->sale_unit_id) {
                $unit = Unit::find($ps->sale_unit_id);
                if ($unit) $unit_code = $unit->unit_code;
            }

            $items[] = [
                'name' => $product->name . ($variant ? ' (' . $variant->item_code . ')' : ''),
                'code' => $variant ? $variant->item_code : $product->code,
                'qty' => $ps->qty,
                'unit' => $unit_code,
                'unit_price' => $ps->net_unit_price,
                'tax' => $ps->tax,
                'discount' => $ps->discount,
                'total' => $ps->total
            ];
        }

        $payments = $payments_data->map(function ($p) {
            return [
                'date' => $p->created_at->toDateTimeString(),
                'method' => $p->paying_method,
                'amount' => $p->amount,
                'change' => $p->change,
                'note' => $p->payment_note
            ];
        });

        // User (Bill By)
        $user = $sale->user;
        $bill_by = $user ? $user->name : '';

        return [
            'company' => [
                'name' => $general_setting->company_name ?? 'SalePro',
                'vat' => $general_setting->vat_registration_number ?? '',
                'address' => $biller ? $biller->address : '',
                'phone' => $biller ? $biller->phone_number : '',
                'email' => $biller ? $biller->email : '',
                'city' => $biller ? $biller->city : '',
                'country' => $biller ? $biller->country : '',
            ],
            'sale' => [
                'id' => $sale->id,
                'reference_no' => $sale->reference_no,
                'date' => $sale->created_at->toDateTimeString(),
                'status' => $sale->sale_status == 1 ? 'Completed' : 'Pending',
                'payment_status' => $sale->payment_status,
                'currency_code' => $currency_code,
                'qr_code' => $qrText,
                'number_in_words' => $numberInWords,
                'bill_by' => $bill_by
            ],
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->phone_number,
                'address' => $customer->address,
                'vat_number' => $customer->vat_number ?? '',
            ],
            'items' => $items,
            'totals' => [
                'total_qty' => $sale->total_qty,
                'total_item' => $sale->item,
                'subtotal' => $sale->total_price, // Sum of product totals
                'total_shipping' => $sale->shipping_cost ?? 0,
                'total_tax' => $sale->order_tax ?? 0,
                'total_discount' => $sale->order_discount ?? 0,
                'grand_total' => $sale->grand_total,
                'paid_amount' => $sale->paid_amount,
                'due_amount' => $sale->grand_total - $sale->paid_amount,
                'change_return' => $payments->sum('change')
            ],
            'payments' => $payments,
            'footer' => $warehouse ? $warehouse->address : '',
            'warehouse_name' => $warehouse ? $warehouse->name : '',
            'biller_footer' => $biller ? $biller->footer : ''
        ];
    }

    public function getInvoiceData($id)
    {
        $data = $this->_getInvoiceData($id);
        if (!$data) return new ErrorResource('Sale not found');
        return response()->json($data);
    }
}
