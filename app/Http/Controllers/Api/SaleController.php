<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\CustomerTypeEnum;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\Sale\UpdateSaleRequest;
use Illuminate\Support\Facades\Redirect;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\PosSetting;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithPaypal;
use App\Models\User;
use App\Models\Variant;
use App\Models\ProductVariant;
use App\Models\CashRegister;
use App\Models\Returns;
use App\Models\ProductReturn;
use App\Models\Expense;
use App\Models\ProductPurchase;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\RewardPointSetting;
use App\Models\RewardPoint;
use App\Models\PackingSlip;
use App\Models\CustomField;
use App\Models\Table;
use App\Models\Courier;
use App\Models\ExternalService;
use Illuminate\Support\Facades\DB;
use Cache;
use App\Models\GeneralSetting;
use App\Models\MailSetting;
use Stripe\Stripe;
use NumberToWords\NumberToWords;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\SaleDetails;
use App\Mail\LogMessage;
use App\Mail\PaymentDetails;
use Illuminate\Support\Facades\Mail;
use Srmklive\PayPal\Services\ExpressCheckout;
use Srmklive\PayPal\Services\AdaptivePayments;
use GeniusTS\HijriDate\Date;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use App\Models\SaleWarrantyGuarantee;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\SMSProviders\TonkraSms;
use App\ViewModels\ISmsModel;
use DateTime;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;
use App\Traits\APIPaginationTrait;

class SaleController extends Controller
{
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;
    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    private $_smsModel;

    public function __construct(ISmsModel $smsModel)
    {
        $this->_smsModel = $smsModel;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sales-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Sale::with(['customer', 'biller', 'warehouse']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $query = $query->orderBy('created_at', 'desc');
            $sales = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format sales for datatable
            $salesTable = $sales->map(function ($sale) {
                // Sale Status HTML
                $saleStatusHtml = '';
                switch ($sale->sale_status) {
                    case 1:
                        $saleStatusHtml = "<div class='badge badge-success'>Completed</div>";
                        break;
                    case 2:
                        $saleStatusHtml = "<div class='badge badge-danger'>Pending</div>";
                        break;
                    case 3:
                        $saleStatusHtml = "<div class='badge badge-warning'>Draft</div>";
                        break;
                    case 4:
                        $saleStatusHtml = "<div class='badge badge-danger'>Returned</div>";
                        break;
                    case 5:
                        $saleStatusHtml = "<div class='badge badge-info'>Processing</div>";
                        break;
                    case 6:
                        $saleStatusHtml = "<div class='badge badge-danger'>Cooked</div>";
                        break;
                    case 7:
                        $saleStatusHtml = "<div class='badge badge-primary'>Served</div>";
                        break;
                    default:
                        $saleStatusHtml = "<div class='badge badge-secondary'>Unknown</div>";
                }

                // Payment Status HTML
                $paymentStatusHtml = '';
                switch ($sale->payment_status) {
                    case 1:
                        $paymentStatusHtml = "<div class='badge badge-danger'>Pending</div>";
                        break;
                    case 2:
                        $paymentStatusHtml = "<div class='badge badge-danger'>Due</div>";
                        break;
                    case 3:
                        $paymentStatusHtml = "<div class='badge badge-warning'>Partial</div>";
                        break;
                    case 4:
                        $paymentStatusHtml = "<div class='badge badge-success'>Paid</div>";
                        break;
                    default:
                        $paymentStatusHtml = "<div class='badge badge-secondary'>Unknown</div>";
                }

                // Delivery Status HTML
                $deliveryData = DB::table('deliveries')->select('status')->where('sale_id', $sale->id)->first();
                $deliveryStatusHtml = 'N/A';
                if ($deliveryData) {
                    switch ($deliveryData->status) {
                        case 1:
                            $deliveryStatusHtml = "<div class='badge badge-primary'>Packing</div>";
                            break;
                        case 2:
                            $deliveryStatusHtml = "<div class='badge badge-info'>Delivering</div>";
                            break;
                        case 3:
                            $deliveryStatusHtml = "<div class='badge badge-success'>Delivered</div>";
                            break;
                    }
                }

                // Payment Method
                $paymentData = Payment::where('sale_id', $sale->id)->first();
                $paymentMethod = $paymentData ? ($paymentData->paying_method ?? 'N/A') : 'N/A';

                // Returned Amount
                $returnedAmount = DB::table('returns')->where('sale_id', $sale->id)->sum('grand_total');

                return [
                    'id' => $sale->id,
                    'date' => date("d-m-Y", strtotime($sale->created_at)),
                    'reference_no' => $sale->reference_no,
                    'customer' => $sale->customer->name ?? 'N/A',
                    'warehouse' => $sale->warehouse->name ?? 'N/A',
                    'sale_status' => $saleStatusHtml,
                    'sale_status_value' => $sale->sale_status,
                    'payment_status' => $paymentStatusHtml,
                    'payment_status_value' => $sale->payment_status,
                    'payment_method' => $paymentMethod,
                    'delivery_status' => $deliveryStatusHtml,
                    'grand_total' => number_format($sale->grand_total, config('decimal')),
                    'returned_amount' => number_format($returnedAmount, config('decimal')),
                    'paid' => number_format($sale->paid_amount ?? 0, config('decimal')),
                    'due' => number_format($sale->grand_total - $returnedAmount - ($sale->paid_amount ?? 0), config('decimal')),
                ];
            });

            return $this->withDashBackground([
                'title' => "Sales",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Sale',
                'add_url' => '/sales/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Sale Status', 'field' => 'sale_status', 'type' => 'html'],
                    ['label' => 'Payment Status', 'field' => 'payment_status', 'type' => 'html'],
                    ['label' => 'Payment Method', 'field' => 'payment_method', 'type' => 'text'],
                    ['label' => 'Delivery Status', 'field' => 'delivery_status', 'type' => 'html'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Returned Amount', 'field' => 'returned_amount', 'type' => 'text'],
                    ['label' => 'Paid', 'field' => 'paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'button',
                                'label' => 'View Payment',
                                'action' => [
                                    'api_url' => '/sales/{id}/payments',
                                    'type' => 'datatable'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'sale_status_value',
                                        'values' => [1, 2, 4, 5, 6, 7], // Not draft (3)
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'label' => 'Add Payment',
                                'action' => [
                                    'api_url' => '/sales/{id}/add-payment',
                                    'type' => 'form'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'payment_status_value',
                                        'values' => [1, 2, 3], // Not paid (4)
                                    ],
                                    [
                                        'field' => 'sale_status_value',
                                        'values' => [1, 2, 4, 5, 6, 7], // Not draft (3)
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'label' => 'Add Return',
                                'action' => [
                                    'api_url' => '/return-sale/create?sale_id={id}',
                                    'type' => 'form'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'sale_status_value',
                                        'values' => [1, 2, 3, 5, 6, 7], // Not returned (4)
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'label' => 'Add Delivery',
                                'action' => [
                                    'api_url' => '/delivery/create/{id}',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/sales/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/sales/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $salesTable,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving sales data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add sales
            if (!$role->hasPermissionTo('sales-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get customers
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id
                ];
            })->toArray();

            // Get warehouses based on user role
            if ($user->role_id > 2) {
                $warehouses = Warehouse::where([
                    ['is_active', true],
                    ['id', $user->warehouse_id]
                ])->get();
                $billers = Biller::where([
                    ['is_active', true],
                    ['id', $user->biller_id]
                ])->get();
            } else {
                $warehouses = Warehouse::where('is_active', true)->get();
                $billers = Biller::where('is_active', true)->get();
            }

            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $billerOptions = $billers->map(function ($biller) {
                return [
                    'label' => $biller->name,
                    'value' => $biller->id
                ];
            })->toArray();

            // Get taxes
            $taxes = Tax::where('is_active', true)->get();
            $taxOptions = $taxes->map(function ($tax) {
                return [
                    'label' => $tax->name . ' (' . $tax->rate . '%)',
                    'value' => $tax->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Create Sale",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/sales",
                "method" => "POST",
                "navigate_url" => "/sales",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Sale Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => now()->format('d F, Y'),
                            ],
                            [
                                "type" => "datagenerator",
                                "name" => "reference_no",
                                "label" => "Reference No",
                                "placeholder" => "Enter reference number",
                                "generator_url" => "/generate/sale-reference",
                            ],
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Customer",
                                "placeholder" => "Select customer",
                                "options" => $customerOptions,
                                "new_screen" => "/customers/create",
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouseOptions,
                                "required" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "biller_id",
                                "label" => "Biller *",
                                "placeholder" => "Select biller",
                                "options" => $billerOptions,
                                "required" => true,
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Product Information",
                        "items" => [
                            [
                                "type" => "table_generator",
                                "name" => "products",
                                "label" => "Select Products",
                                "search_url" => "/products/search",
                                "search_placeholder" => "Search products by name, code or scan barcode",
                                "info" => "Use barcode scanner or type product code/name to add products",
                                "show_info_icon" => true,
                                "duplicate_handling" => [
                                    "strategy" => "update_quantity",
                                    "identifier_field" => "id",
                                    "update_fields" => ["qty"],
                                    "error_message" => "This product is already added to the table",
                                ],
                                "style" => [
                                    "header_background" => "#f8f9fa",
                                    "header_text_color" => "#212529",
                                    "row_background" => "#ffffff",
                                    "alternate_row_background" => "#f8f9fa",
                                    "border_color" => "#dee2e6",
                                    "input_border_color" => "#ced4da",
                                ],
                                "columns" => [
                                    [
                                        "name" => "name",
                                        "label" => "Product Name",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 200,
                                    ],
                                    [
                                        "name" => "code",
                                        "label" => "Code",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 120,
                                    ],
                                    [
                                        "name" => "qty",
                                        "label" => "Quantity",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                    [
                                        "name" => "price",
                                        "label" => "Unit Price",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "tax",
                                        "label" => "Tax %",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "discount",
                                        "label" => "Discount",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "subtotal",
                                        "label" => "Subtotal",
                                        "type" => "formula",
                                        "formula" => "qty * price * (1 + tax / 100) - discount",
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                ],
                                "formula_engine" => [
                                    "enabled" => true,
                                    "auto_calculate" => true,
                                ],
                                "totals" => [
                                    [
                                        "label" => "Total Items",
                                        "formula" => "COUNT(*)",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total Quantity",
                                        "formula" => "SUM(qty)",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total",
                                        "formula" => "SUM(subtotal)",
                                        "position" => "right",
                                        "prefix" => config('currency'),
                                    ],
                                ],
                                "totals_style" => [
                                    "background" => "#e9ecef",
                                    "text_color" => "#212529",
                                    "font_weight" => "bold",
                                ],
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Additional Information",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "tax_id",
                                "label" => "Order Tax",
                                "placeholder" => "Select tax",
                                "options" => array_merge([["value" => 0, "label" => "No Tax"]], $taxOptions),
                                "value" => 0,
                            ],
                            [
                                "type" => "text",
                                "name" => "discount",
                                "label" => "Order Discount",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => "0.00",
                            ],
                            [
                                "type" => "text",
                                "name" => "shipping_cost",
                                "label" => "Shipping Cost",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => "0.00",
                            ],
                            [
                                "type" => "editor",
                                "name" => "sale_note",
                                "label" => "Sale Note",
                                "placeholder" => "Enter sale note",
                            ],
                            [
                                "type" => "editor",
                                "name" => "staff_note",
                                "label" => "Staff Note",
                                "placeholder" => "Enter staff note",
                            ],
                            [
                                "type" => "hidden",
                                "name" => "sale_status",
                                "value" => 1,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "payment_status",
                                "value" => 2,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the sale form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit sales
            if (!$role->hasPermissionTo('sales-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get the sale with related data
            $sale = Sale::with(['customer', 'biller', 'warehouse'])->findOrFail($id);
            $productSales = Product_Sale::where('sale_id', $id)->get();

            // Get customers
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id
                ];
            })->toArray();

            // Get warehouses based on user role
            if ($user->role_id > 2) {
                $warehouses = Warehouse::where([
                    ['is_active', true],
                    ['id', $user->warehouse_id]
                ])->get();
                $billers = Biller::where([
                    ['is_active', true],
                    ['id', $user->biller_id]
                ])->get();
            } else {
                $warehouses = Warehouse::where('is_active', true)->get();
                $billers = Biller::where('is_active', true)->get();
            }

            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $billerOptions = $billers->map(function ($biller) {
                return [
                    'label' => $biller->name,
                    'value' => $biller->id
                ];
            })->toArray();

            // Get taxes
            $taxes = Tax::where('is_active', true)->get();
            $taxOptions = $taxes->map(function ($tax) {
                return [
                    'label' => $tax->name . ' (' . $tax->rate . '%)',
                    'value' => $tax->id
                ];
            })->toArray();

            // Prepare existing products for table generator
            $existingProducts = $productSales->map(function ($ps) {
                $product = Product::find($ps->product_id);
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'qty' => $ps->qty,
                    'price' => $ps->net_unit_price,
                    'tax' => $ps->tax_rate,
                    'discount' => $ps->discount,
                    'subtotal' => $ps->total,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Edit Sale",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/sales/" . $id,
                "method" => "PUT",
                "navigate_url" => "/sales",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Sale Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => date('d F, Y', strtotime($sale->created_at)),
                            ],
                            [
                                "type" => "text",
                                "name" => "reference_no",
                                "label" => "Reference No",
                                "placeholder" => "Enter reference number",
                                "value" => $sale->reference_no,
                            ],
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Customer",
                                "placeholder" => "Select customer",
                                "options" => $customerOptions,
                                "new_screen" => "/customers/create",
                                "value" => $sale->customer_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouseOptions,
                                "required" => true,
                                "value" => $sale->warehouse_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "biller_id",
                                "label" => "Biller *",
                                "placeholder" => "Select biller",
                                "options" => $billerOptions,
                                "required" => true,
                                "value" => $sale->biller_id,
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Product Information",
                        "items" => [
                            [
                                "type" => "table_generator",
                                "name" => "products",
                                "label" => "Select Products",
                                "search_url" => "/products/search",
                                "search_placeholder" => "Search products by name, code or scan barcode",
                                "info" => "Use barcode scanner or type product code/name to add products",
                                "show_info_icon" => true,
                                "value" => $existingProducts,
                                "duplicate_handling" => [
                                    "strategy" => "update_quantity",
                                    "identifier_field" => "id",
                                    "update_fields" => ["qty"],
                                    "error_message" => "This product is already added to the table",
                                ],
                                "style" => [
                                    "header_background" => "#f8f9fa",
                                    "header_text_color" => "#212529",
                                    "row_background" => "#ffffff",
                                    "alternate_row_background" => "#f8f9fa",
                                    "border_color" => "#dee2e6",
                                    "input_border_color" => "#ced4da",
                                ],
                                "columns" => [
                                    [
                                        "name" => "name",
                                        "label" => "Product Name",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 200,
                                    ],
                                    [
                                        "name" => "code",
                                        "label" => "Code",
                                        "type" => "text",
                                        "editable" => false,
                                        "width" => 120,
                                    ],
                                    [
                                        "name" => "qty",
                                        "label" => "Quantity",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                    [
                                        "name" => "price",
                                        "label" => "Unit Price",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "tax",
                                        "label" => "Tax %",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "discount",
                                        "label" => "Discount",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "subtotal",
                                        "label" => "Subtotal",
                                        "type" => "formula",
                                        "formula" => "qty * price * (1 + tax / 100) - discount",
                                        "width" => 120,
                                        "decimal_places" => 2,
                                    ],
                                ],
                                "formula_engine" => [
                                    "enabled" => true,
                                    "auto_calculate" => true,
                                ],
                                "totals" => [
                                    [
                                        "label" => "Total Items",
                                        "formula" => "COUNT(*)",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total Quantity",
                                        "formula" => "SUM(qty)",
                                        "position" => "left",
                                    ],
                                    [
                                        "label" => "Total",
                                        "formula" => "SUM(subtotal)",
                                        "position" => "right",
                                        "prefix" => config('currency'),
                                    ],
                                ],
                                "totals_style" => [
                                    "background" => "#e9ecef",
                                    "text_color" => "#212529",
                                    "font_weight" => "bold",
                                ],
                            ],
                        ]
                    ],
                    [
                        "type" => "group",
                        "label" => "Additional Information",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "tax_id",
                                "label" => "Order Tax",
                                "placeholder" => "Select tax",
                                "options" => array_merge([["value" => 0, "label" => "No Tax"]], $taxOptions),
                                "value" => $sale->tax_id ?? 0,
                            ],
                            [
                                "type" => "text",
                                "name" => "discount",
                                "label" => "Order Discount",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => $sale->discount ?? "0.00",
                            ],
                            [
                                "type" => "text",
                                "name" => "shipping_cost",
                                "label" => "Shipping Cost",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => $sale->shipping_cost ?? "0.00",
                            ],
                            [
                                "type" => "editor",
                                "name" => "sale_note",
                                "label" => "Sale Note",
                                "placeholder" => "Enter sale note",
                                "value" => $sale->sale_note ?? "",
                            ],
                            [
                                "type" => "editor",
                                "name" => "staff_note",
                                "label" => "Staff Note",
                                "placeholder" => "Enter staff note",
                                "value" => $sale->staff_note ?? "",
                            ],
                            [
                                "type" => "hidden",
                                "name" => "sale_status",
                                "value" => $sale->sale_status,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "payment_status",
                                "value" => $sale->payment_status,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the sale for editing.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreSaleRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = Auth::id();

        $cash_register_data = CashRegister::where([
            ['user_id', $data['user_id']],
            ['warehouse_id', $data['warehouse_id']],
            ['status', true]
        ])->first();

        if ($cash_register_data)
            $data['cash_register_id'] = $cash_register_data->id;

        if (isset($data['created_at']))
            $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
        else
            $data['created_at'] = date("Y-m-d H:i:s");

        //set the paid_amount value to $new_data variable
        $new_data['paid_amount'] = $data['paid_amount'];

        if (is_array($data['paid_amount'])) {
            $data['paid_amount'] = array_sum($data['paid_amount']);
        }

        // Sale from POS page
        if ($data['pos']) {
            if (!isset($data['reference_no']))
                $data['reference_no'] = 'posr-' . date("Ymd") . '-' . date("his");

            $balance = $data['grand_total'] - $data['paid_amount'];

            if (is_array($data['paid_amount'])) {
                $data['paid_amount'] = array_sum($data['paid_amount']);
            }
            if ($balance > 0 || $balance < 0)
                $data['payment_status'] = 2;
            else
                $data['payment_status'] = 4;

            if ($data['draft']) {
                $lims_sale_data = Sale::find($data['sale_id']);
                $lims_product_sale_data = Product_Sale::where('sale_id', $data['sale_id'])->get();
                foreach ($lims_product_sale_data as $product_sale_data) {
                    $product_sale_data->delete();
                }
                $lims_sale_data->delete();
            }
        } else {
            if (!isset($data['reference_no']))
                $data['reference_no'] = 'sr-' . date("Ymd") . '-' . date("his");
        }

        //process document
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/sale'), $documentName);
            }
            $data['document'] = $documentName;
        }

        if ($data['coupon_active']) {
            $lims_coupon_data = Coupon::find($data['coupon_id']);
            $lims_coupon_data->used += 1;
            $lims_coupon_data->save();
        }

        if (isset($data['table_id'])) {
            $latest_sale = Sale::whereNotNull('table_id')->whereDate('created_at', date('Y-m-d'))->where('warehouse_id', $data['warehouse_id'])->select('queue')->orderBy('id', 'desc')->first();
            if ($latest_sale)
                $data['queue'] = $latest_sale->queue + 1;
            else
                $data['queue'] = 1;
        }

        //inserting data to sales table
        $lims_sale_data = Sale::create($data);

        // add the $new_data variable value to $data['paid_amount'] variable
        $data['paid_amount'] = $new_data['paid_amount'];

        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($data[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                else
                    $custom_field_data[$field_name] = $data[$field_name];
            }
        }
        if (count($custom_field_data))
            DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);
        $lims_customer_data = Customer::find($data['customer_id']);
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();

        // Check if reward points system is active and order total is eligible
        if (
            $lims_reward_point_setting_data
            && $lims_reward_point_setting_data->is_active
            && !request()->has('redeem_point')
            && $data['grand_total'] >= $lims_reward_point_setting_data->minimum_amount
        ) {

            // Check if customer is regular
            if ($lims_customer_data->type == CustomerTypeEnum::REGULAR->value) {

                // Check if sale is not a draft and not paid using points
                $isDraft = isset($data['draft']) && $data['draft'] == '0';
                $isNotPaidBy7 = !in_array('7', $data['paid_by_id'] ?? []);

                if ($isDraft && $isNotPaidBy7) {
                    // Calculate points based on grand total
                    $point = (int)($data['grand_total'] / $lims_reward_point_setting_data->per_point_amount);

                    // Add points to customer
                    $lims_customer_data->points += $point;
                    $lims_customer_data->save();

                    // Log reward points
                    $expiredAt = null;
                    if ($lims_reward_point_setting_data->duration && $lims_reward_point_setting_data->type) {
                        switch ($lims_reward_point_setting_data->type) {
                            case 'days':
                                $expiredAt = now()->addDays($lims_reward_point_setting_data->duration);
                                break;
                            case 'months':
                                $expiredAt = now()->addMonths($lims_reward_point_setting_data->duration);
                                break;
                            case 'years':
                                $expiredAt = now()->addYears($lims_reward_point_setting_data->duration);
                                break;
                        }
                    }

                    RewardPoint::create([
                        'points' => $point,
                        'customer_id' => $lims_customer_data->id,
                        'note' => 'Earn Point for sale #' . $lims_sale_data->id,
                        'sale_id' => $lims_sale_data->id,
                        'expired_at' => $expiredAt,
                    ]);
                }
            }
        }

        //collecting male data
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

        $product_id = $data['product_id'];
        $product_batch_id = $data['product_batch_id'];
        $imei_number = $data['imei_number'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $sale_unit = $data['sale_unit'];
        $net_unit_price = $data['net_unit_price'];
        $discount = $data['discount'];
        $tax_rate = $data['tax_rate'];
        $tax = $data['tax'];
        $total = $data['subtotal'];
        $product_sale = [];

        foreach ($product_id as $i => $id) {
            $lims_product_data = Product::where('id', $id)->first();
            // DB::rollback();
            $product_sale['variant_id'] = null;
            $product_sale['product_batch_id'] = null;
            if ($lims_product_data->type == 'combo' && $data['sale_status'] == 1) {
                if (!in_array('manufacturing', explode(',', config('addons')))) {
                    $product_list = explode(",", $lims_product_data->product_list);
                    $variant_list = explode(",", $lims_product_data->variant_list);
                    if ($lims_product_data->variant_list)
                        $variant_list = explode(",", $lims_product_data->variant_list);
                    else
                        $variant_list = [];
                    $qty_list = explode(",", $lims_product_data->qty_list);
                    $price_list = explode(",", $lims_product_data->price_list);

                    foreach ($product_list as $key => $child_id) {
                        $child_data = Product::find($child_id);
                        if (count($variant_list) && $variant_list[$key]) {
                            $child_product_variant_data = ProductVariant::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]]
                            ])->first();

                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['variant_id', $variant_list[$key]],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            $child_product_variant_data->qty -= $qty[$i] * $qty_list[$key];
                            $child_product_variant_data->save();
                        } else {
                            $child_warehouse_data = Product_Warehouse::where([
                                ['product_id', $child_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();
                        }

                        $child_data->qty -= $qty[$i] * $qty_list[$key];
                        $child_warehouse_data->qty -= $qty[$i] * $qty_list[$key];

                        $child_data->save();
                        $child_warehouse_data->save();
                    }
                }
            }

            if ($sale_unit[$i] != 'n/a') {
                $lims_sale_unit_data  = Unit::where('unit_name', $sale_unit[$i])->first();
                $sale_unit_id = $lims_sale_unit_data->id;
                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($id, $product_code[$i])->first();
                    $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                }
                if ($lims_product_data->is_batch && $product_batch_id[$i]) {
                    $product_sale['product_batch_id'] = $product_batch_id[$i];
                }

                if ($data['sale_status'] == 1) {
                    if ($lims_sale_unit_data->operator == '*')
                        $quantity = $qty[$i] * $lims_sale_unit_data->operation_value;
                    elseif ($lims_sale_unit_data->operator == '/')
                        $quantity = $qty[$i] / $lims_sale_unit_data->operation_value;
                    //deduct quantity
                    $lims_product_data->qty = $lims_product_data->qty - $quantity;
                    $lims_product_data->save();
                    //deduct product variant quantity if exist
                    if ($lims_product_data->is_variant) {
                        $lims_product_variant_data->qty -= $quantity;
                        $lims_product_variant_data->save();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($id, $lims_product_variant_data->variant_id, $data['warehouse_id'])->first();
                    } elseif ($product_batch_id[$i]) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_batch_id[$i]],
                            ['warehouse_id', $data['warehouse_id']]
                        ])->first();
                        $lims_product_batch_data = ProductBatch::find($product_batch_id[$i]);
                        //deduct product batch quantity
                        $lims_product_batch_data->qty -= $quantity;
                        $lims_product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($id, $data['warehouse_id'])->first();
                    }
                    //deduct quantity from warehouse
                    $lims_product_warehouse_data->qty -= $quantity;
                    $lims_product_warehouse_data->save();
                }
            } else
                $sale_unit_id = 0;

            if ($product_sale['variant_id']) {
                $variant_data = Variant::select('name')->find($product_sale['variant_id']);
                $mail_data['products'][$i] = $lims_product_data->name . ' [' . $variant_data->name . ']';
            } else
                $mail_data['products'][$i] = $lims_product_data->name;
            //deduct imei number if available
            if ($imei_number[$i] && !str_contains($imei_number[$i], "null") && $data['sale_status'] == 1) {
                $imei_numbers = explode(",", $imei_number[$i]);
                $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                foreach ($imei_numbers as $number) {
                    if (($j = array_search($number, $all_imei_numbers)) !== false) {
                        unset($all_imei_numbers[$j]);
                    }
                }

                $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                $lims_product_warehouse_data->save();
            }
            if ($lims_product_data->type == 'digital')
                $mail_data['file'][$i] = url('/product/files') . '/' . $lims_product_data->file;
            else
                $mail_data['file'][$i] = '';
            if ($sale_unit_id)
                $mail_data['unit'][$i] = $lims_sale_unit_data->unit_code;
            else
                $mail_data['unit'][$i] = '';

            $product_sale['sale_id'] = $lims_sale_data->id;
            $product_sale['product_id'] = $id;
            $product_sale['imei_number'] = $imei_number[$i];
            $product_sale['qty'] = $mail_data['qty'][$i] = $qty[$i];
            $product_sale['sale_unit_id'] = $sale_unit_id;
            $product_sale['net_unit_price'] = $net_unit_price[$i];
            $product_sale['discount'] = $discount[$i];
            $product_sale['tax_rate'] = $tax_rate[$i];
            $product_sale['tax'] = $tax[$i];
            $product_sale['total'] = $mail_data['total'][$i] = $total[$i];

            $general_setting = DB::table('general_settings')->select('modules')->first();
            if (in_array('restaurant', explode(',', $general_setting->modules))) {
                $product_sale['topping_id'] = $data['topping_product'][$i];
            };

            Product_Sale::create($product_sale);
        }
        if ($data['sale_status'] == 3)
            $message = 'Sale successfully added to draft';
        else
            $message = ' Sale created successfully';
        $mail_setting = MailSetting::latest()->first();
        if ($mail_data['email'] && $data['sale_status'] == 1 && $mail_setting) {
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($mail_data['email'])->send(new SaleDetails($mail_data));
                /*$log_data['message'] = Auth::user()->name . ' has created a sale. Reference No: ' .$lims_sale_data->reference_no;
                $admin_email = 'ashfaqdev.php@gmail.com';
                Mail::to($admin_email)->send(new LogMessage($log_data));*/
            } catch (\Exception $e) {
                $message = ' Sale created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        if ($data['payment_status'] == 3 || $data['payment_status'] == 4 || ($data['payment_status'] == 2 && $data['pos'] && $data['paid_amount'] > 0)) {
            foreach ($data['paid_by_id'] as $key => $value) {
                if ($data['paid_amount'][$key] > 0) {
                    $lims_payment_data = new Payment();
                    $lims_payment_data->user_id = Auth::id();
                    $paying_method = '';

                    if ($data['paid_by_id'][$key] == 1)
                        $paying_method = 'Cash';
                    elseif ($data['paid_by_id'][$key] == 2) {
                        $paying_method = 'Gift Card';
                    } elseif ($data['paid_by_id'][$key] == 3)
                        $paying_method = 'Credit Card';
                    elseif ($data['paid_by_id'][$key] == 4)
                        $paying_method = 'Cheque';
                    elseif ($data['paid_by_id'][$key] == 5)
                        $paying_method = 'Paypal';
                    elseif ($data['paid_by_id'][$key] == 6)
                        $paying_method = 'Deposit';
                    elseif ($data['paid_by_id'][$key] == 7) {
                        $paying_method = 'Points';
                        $lims_payment_data->used_points = $data['used_points'];
                    } elseif ($data['paid_by_id'][$key] == 8) {
                        $paying_method = 'Pesapal';
                    } else {

                        $paying_method = ucfirst($data['paid_by_id_select'][0]); // For string values like 'Pesapal', 'Stripe', etc.
                    }

                    if ($cash_register_data)
                        $lims_payment_data->cash_register_id = $cash_register_data->id;
                    $lims_account_data = Account::where('is_default', true)->first();
                    $lims_payment_data->account_id = $lims_account_data->id;
                    $lims_payment_data->sale_id = $lims_sale_data->id;
                    $data['payment_reference'] = 'spr-' . date("Ymd") . '-' . date("his");
                    $lims_payment_data->payment_reference = $data['payment_reference'];
                    $lims_payment_data->amount = $data['paid_amount'][$key];
                    $lims_payment_data->change = $data['paying_amount'][$key] - $data['paid_amount'][$key];
                    $lims_payment_data->paying_method = $paying_method;
                    $lims_payment_data->payment_note = $data['payment_note'];
                    if (isset($data['payment_receiver'])) {
                        $lims_payment_data->payment_receiver = $data['payment_receiver'];
                    }
                    $lims_payment_data->save();

                    if (isset($data['cash']) && $data['cash'] > 0 &&  isset($data['bank']) && $data['bank'])

                        $lims_payment_data = Payment::latest()->first();
                    $data['payment_id'] = $lims_payment_data->id;
                    $lims_pos_setting_data = PosSetting::latest()->first();
                    // Check Payment Method is Card
                    if ($paying_method == 'Credit Card') {
                        $cardDetails = [];
                        $cardDetails['card_number'] = $data['card_number'];
                        $cardDetails['card_holder_name'] = $data['card_holder_name'];
                        $cardDetails['card_type'] = $data['card_type'];
                        $data['charge_id'] = '12345';
                        $data['data'] = json_encode($cardDetails);

                        PaymentWithCreditCard::create($data);
                    } else if ($paying_method == 'Gift Card') {
                        $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
                        $lims_gift_card_data->expense += $data['paid_amount'][$key];
                        $lims_gift_card_data->save();
                        PaymentWithGiftCard::create($data);
                    } else if ($paying_method == 'Cheque') {
                        PaymentWithCheque::create($data);
                    } else if ($paying_method == 'Deposit') {
                        $lims_customer_data->expense += $data['paid_amount'][$key];
                        $lims_customer_data->save();
                    } else if ($paying_method == 'Points') {
                        $lims_customer_data->points -= $data['used_points'];
                        $lims_customer_data->save();
                    } else if ($paying_method == 'Pesapal') {
                        $redirectUrl = $this->submitOrderRequest($lims_customer_data, $data['paid_amount'][$key]); // Assume this returns a URL
                        $lims_customer_data->save();

                        return response()->json([
                            'payment_method' => 'pesapal',
                            'redirect_url' => $redirectUrl,
                        ]);
                    }
                }
            }
        }
        /*}
        catch(Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }*/

        //sms send start
        $smsData = [];

        $smsTemplate = SmsTemplate::where('is_default', 1)->latest()->first();
        $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();
        if ($smsProvider && $smsTemplate && $lims_pos_setting_data['send_sms'] == 1) {
            $smsData['type'] = 'onsite';
            $smsData['template_id'] = $smsTemplate['id'];
            $smsData['sale_status'] = $data['sale_status'];
            $smsData['payment_status'] = $data['payment_status'];
            $smsData['customer_id'] = $data['customer_id'];
            $smsData['reference_no'] = $data['reference_no'];
            $this->_smsModel->initialize($smsData);
        }
        //sms send end

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data' => $lims_sale_data->id
        ], 201);
    }

    public function update(UpdateSaleRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sales-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to edit sales.',
                ], 403);
            }

            $data = $request->except('document', 'token');
            $document = $request->document;
            $lims_sale_data = Sale::find($id);

            if (!$lims_sale_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found.',
                ], 404);
            }

            // Handle document upload
            if ($document) {
                $v = Validator::make(
                    [
                        'extension' => strtolower($request->document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid document format.',
                        'errors' => $v->errors()
                    ], 422);
                }

                $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/sale'), $documentName);
                $data['document'] = $documentName;
            }

            // Calculate payment status
            $balance = $data['grand_total'] - $data['paid_amount'];
            if ($balance < 0 || $balance > 0)
                $data['payment_status'] = 2;
            else
                $data['payment_status'] = 4;

            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();

            // Handle created_at
            if (isset($data['created_at'])) {
                $date = DateTime::createFromFormat('Y-m-d', $data['created_at']);
                if ($date) {
                    $data['created_at'] = $date->format('Y-m-d H:i:s');
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s');
                }
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            $product_id = $data['product_id'];
            $imei_number = $data['imei_number'];
            $product_batch_id = $data['product_batch_id'] ?? null;
            $product_code = $data['product_code'];
            $product_variant_id = $data['product_variant_id'] ?? null;
            $qty = $data['qty'];
            $sale_unit = $data['sale_unit'];
            $net_unit_price = $data['net_unit_price'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];

            $old_product_id = [];
            $old_product_variant_id = [];

            // Restore old inventory first
            foreach ($lims_product_sale_data as $key => $product_sale_data) {
                $old_product_id[] = $product_sale_data->product_id;
                $old_product_variant_id[] = null;
                $lims_product_data = Product::find($product_sale_data->product_id);

                // Handle combo products
                if (($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo')) {
                    if (!in_array('manufacturing', explode(',', config('addons')))) {
                        $product_list = explode(",", $lims_product_data->product_list);
                        $variant_list = $lims_product_data->variant_list ? explode(",", $lims_product_data->variant_list) : [];
                        $qty_list = explode(",", $lims_product_data->qty_list);

                        foreach ($product_list as $index => $child_id) {
                            $child_data = Product::find($child_id);
                            if (count($variant_list) && $variant_list[$index]) {
                                $child_product_variant_data = ProductVariant::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]]
                                ])->first();

                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();

                                $child_product_variant_data->qty += $product_sale_data->qty * $qty_list[$index];
                                $child_product_variant_data->save();
                            } else {
                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();
                            }

                            $child_data->qty += $product_sale_data->qty * $qty_list[$index];
                            $child_warehouse_data->qty += $product_sale_data->qty * $qty_list[$index];

                            $child_data->save();
                            $child_warehouse_data->save();
                        }
                    }
                }

                // Restore inventory for regular products
                if (($lims_sale_data->sale_status == 1) && ($product_sale_data->sale_unit_id != 0)) {
                    $old_product_qty = $product_sale_data->qty;
                    $lims_sale_unit_data = Unit::find($product_sale_data->sale_unit_id);
                    if ($lims_sale_unit_data->operator == '*')
                        $old_product_qty = $old_product_qty * $lims_sale_unit_data->operation_value;
                    else
                        $old_product_qty = $old_product_qty / $lims_sale_unit_data->operation_value;

                    if ($product_sale_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)->first();
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                        $lims_product_variant_data->qty += $old_product_qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_sale_data->product_batch_id) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_sale_data->product_id],
                            ['product_batch_id', $product_sale_data->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $product_batch_data = ProductBatch::find($product_sale_data->product_batch_id);
                        $product_batch_data->qty += $old_product_qty;
                        $product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_sale_data->product_id, $lims_sale_data->warehouse_id)->first();
                    }

                    $lims_product_data->qty += $old_product_qty;
                    $lims_product_warehouse_data->qty += $old_product_qty;

                    // Return IMEI numbers
                    if ($product_sale_data->imei_number && !str_contains($product_sale_data->imei_number, "null")) {
                        if ($lims_product_warehouse_data->imei_number)
                            $lims_product_warehouse_data->imei_number .= ',' . $product_sale_data->imei_number;
                        else
                            $lims_product_warehouse_data->imei_number = $product_sale_data->imei_number;
                    }

                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                } else {
                    if ($product_sale_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_sale_data->product_id, $product_sale_data->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_sale_data->product_id, $product_sale_data->variant_id, $lims_sale_data->warehouse_id)->first();
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                    }
                }

                // Delete old product sales that are not in new data
                if ($product_sale_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id))) {
                    $product_sale_data->delete();
                } elseif (!(in_array($old_product_id[$key], $product_id))) {
                    $product_sale_data->delete();
                }
            }

            // Process new products
            $product_variant_id_array = [];
            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);
                $product_sale = [];
                $product_sale['variant_id'] = null;

                // Handle combo products
                if ($lims_product_data->type == 'combo' && $data['sale_status'] == 1) {
                    if (!in_array('manufacturing', explode(',', config('addons')))) {
                        $product_list = explode(",", $lims_product_data->product_list);
                        $variant_list = $lims_product_data->variant_list ? explode(",", $lims_product_data->variant_list) : [];
                        $qty_list = explode(",", $lims_product_data->qty_list);

                        foreach ($product_list as $index => $child_id) {
                            $child_data = Product::find($child_id);
                            if (count($variant_list) && $variant_list[$index]) {
                                $child_product_variant_data = ProductVariant::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                ])->first();

                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                    ['warehouse_id', $data['warehouse_id']],
                                ])->first();

                                $child_product_variant_data->qty -= $qty[$key] * $qty_list[$index];
                                $child_product_variant_data->save();
                            } else {
                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['warehouse_id', $data['warehouse_id']],
                                ])->first();
                            }

                            $child_data->qty -= $qty[$key] * $qty_list[$index];
                            $child_warehouse_data->qty -= $qty[$key] * $qty_list[$index];

                            $child_data->save();
                            $child_warehouse_data->save();
                        }
                    }
                }

                if ($sale_unit[$key] != 'n/a') {
                    $lims_sale_unit_data = Unit::where('unit_name', $sale_unit[$key])->first();
                    $sale_unit_id = $lims_sale_unit_data->id;

                    if ($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($pro_id, $lims_product_variant_data->variant_id, $data['warehouse_id'])->first();
                        $product_sale['variant_id'] = $lims_product_variant_data->variant_id;
                        $product_variant_id_array[$key] = $lims_product_variant_data->id;
                    } else {
                        $product_variant_id_array[$key] = null;
                    }

                    // Deduct new inventory
                    if ($data['sale_status'] == 1) {
                        $new_product_qty = $qty[$key];
                        if ($lims_sale_unit_data->operator == '*') {
                            $new_product_qty = $new_product_qty * $lims_sale_unit_data->operation_value;
                        } else {
                            $new_product_qty = $new_product_qty / $lims_sale_unit_data->operation_value;
                        }

                        if ($product_sale['variant_id']) {
                            $lims_product_variant_data->qty -= $new_product_qty;
                            $lims_product_variant_data->save();
                        } elseif ($product_batch_id != null && $product_batch_id[$key]) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['product_batch_id', $product_batch_id[$key]],
                                ['warehouse_id', $data['warehouse_id']]
                            ])->first();

                            $product_batch_data = ProductBatch::find($product_batch_id[$key]);
                            $product_batch_data->qty -= $new_product_qty;
                            $product_batch_data->save();
                        } else {
                            $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($pro_id, $data['warehouse_id'])->first();
                        }

                        $lims_product_data->qty -= $new_product_qty;
                        $lims_product_warehouse_data->qty -= $new_product_qty;

                        // Deduct IMEI numbers
                        if ($imei_number[$key] && !str_contains($imei_number[$key], "null")) {
                            $imei_numbers = explode(",", $imei_number[$key]);
                            $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                            foreach ($imei_numbers as $number) {
                                if (($j = array_search($number, $all_imei_numbers)) !== false) {
                                    unset($all_imei_numbers[$j]);
                                }
                            }
                            $lims_product_warehouse_data->imei_number = implode(",", $all_imei_numbers);
                            $lims_product_warehouse_data->save();
                        }

                        $lims_product_data->save();
                        $lims_product_warehouse_data->save();
                    }
                } else {
                    $sale_unit_id = 0;
                }

                // Create or update product sale record
                $product_sale['sale_id'] = $id;
                $product_sale['product_id'] = $pro_id;
                $product_sale['imei_number'] = ($imei_number[$key] && !str_contains($imei_number[$key], "null")) ? $imei_number[$key] : null;
                $product_sale['product_batch_id'] = $product_batch_id[$key] ?? null;
                $product_sale['qty'] = $qty[$key];
                $product_sale['sale_unit_id'] = $sale_unit_id;
                $product_sale['net_unit_price'] = $net_unit_price[$key];
                $product_sale['discount'] = $discount[$key];
                $product_sale['tax_rate'] = $tax_rate[$key];
                $product_sale['tax'] = $tax[$key];
                $product_sale['total'] = $total[$key];

                if ($product_sale['variant_id'] && in_array($product_variant_id_array[$key], $old_product_variant_id)) {
                    Product_Sale::where([
                        ['product_id', $pro_id],
                        ['variant_id', $product_sale['variant_id']],
                        ['sale_id', $id]
                    ])->update($product_sale);
                } elseif ($product_sale['variant_id'] === null && (in_array($pro_id, $old_product_id))) {
                    Product_Sale::where([
                        ['sale_id', $id],
                        ['product_id', $pro_id]
                    ])->update($product_sale);
                } else {
                    Product_Sale::create($product_sale);
                }
            }

            // Update sale record
            $lims_sale_data->update($data);

            // Handle custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'sale')->select('name', 'type')->get();
            foreach ($custom_fields as $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if (isset($data[$field_name])) {
                    if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if (count($custom_field_data))
                DB::table('sales')->where('id', $lims_sale_data->id)->update($custom_field_data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully.',
                'navigate_url' => '/sales',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    /**
     * Get payment list for a sale
     */
    public function getPayments($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sale-payment-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $sale = Sale::findOrFail($id);
            $payments = Payment::where('sale_id', $id)->get();

            $paymentRows = $payments->map(function ($payment) {
                $account = Account::find($payment->account_id);
                $chequeNo = 'N/A';

                if ($payment->paying_method == 'Cheque') {
                    $chequeData = PaymentWithCheque::where('payment_id', $payment->id)->first();
                    if ($chequeData) {
                        $chequeNo = $chequeData->cheque_no;
                    }
                }

                return [
                    'id' => $payment->id,
                    'date' => date(config('date_format'), strtotime($payment->created_at)) . ' ' . $payment->created_at->format('H:i:s'),
                    'reference' => $payment->payment_reference,
                    'amount' => number_format($payment->amount, config('decimal')),
                    'paying_method' => $payment->paying_method,
                    'account' => $account ? $account->name : 'N/A',
                    'cheque_no' => $chequeNo,
                    'note' => $payment->payment_note ?? '',
                ];
            });

            return $this->withDashBackground([
                'title' => 'Sale Payments',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Paying Method', 'field' => 'paying_method', 'type' => 'text'],
                    ['label' => 'Account', 'field' => 'account', 'type' => 'text'],
                    ['label' => 'Cheque No', 'field' => 'cheque_no', 'type' => 'text'],
                    ['label' => 'Note', 'field' => 'note', 'type' => 'text'],
                ],
                'rows' => $paymentRows,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving payment data.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Show form for adding payment to sale
     */
    public function addPaymentForm($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sale-payment-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $sale = Sale::findOrFail($id);

            // Calculate balance
            $paidAmount = Payment::where('sale_id', $id)->sum('amount');
            $returnedAmount = Returns::where('sale_id', $id)->sum('grand_total');
            $balance = $sale->grand_total - $returnedAmount - $paidAmount;

            if ($balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This sale is already fully paid.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $accounts = Account::where('is_active', 1)->get()->map(function ($account) {
                return [
                    'label' => $account->name . ' (' . $account->account_no . ')',
                    'value' => $account->id,
                ];
            })->toArray();

            $payingMethods = [
                ['value' => 'Cash', 'label' => 'Cash'],
                ['value' => 'Gift Card', 'label' => 'Gift Card'],
                ['value' => 'Credit Card', 'label' => 'Credit Card'],
                ['value' => 'Cheque', 'label' => 'Cheque'],
                ['value' => 'Paypal', 'label' => 'Paypal'],
                ['value' => 'Deposit', 'label' => 'Deposit'],
            ];

            $formSchema = [
                'title' => 'Add Payment',
                'submit_url' => '/sales/' . $id . '/add-payment',
                'navigate_url' => '/sales',
                'method' => 'POST',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Payment Information',
                        'items' => [
                            [
                                'type' => 'text',
                                'name' => 'balance',
                                'label' => 'Balance Due',
                                'value' => number_format($balance, config('decimal')),
                                'readonly' => true,
                            ],
                            [
                                'type' => 'text',
                                'name' => 'paying_amount',
                                'label' => 'Paying Amount *',
                                'placeholder' => 'Enter paying amount',
                                'keyboard_type' => 'number',
                                'required' => true,
                                'value' => number_format($balance, config('decimal'), '.', ''),
                            ],
                            [
                                'type' => 'text',
                                'name' => 'amount',
                                'label' => 'Amount *',
                                'placeholder' => 'Enter amount',
                                'keyboard_type' => 'number',
                                'required' => true,
                                'value' => number_format($balance, config('decimal'), '.', ''),
                                'info' => 'Amount to be recorded (after change)',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'paid_by_id',
                                'label' => 'Paid By *',
                                'placeholder' => 'Select payment method',
                                'options' => $payingMethods,
                                'required' => true,
                                'value' => 'Cash',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'account_id',
                                'label' => 'Account *',
                                'placeholder' => 'Select account',
                                'options' => $accounts,
                                'required' => true,
                            ],
                            [
                                'type' => 'text',
                                'name' => 'cheque_no',
                                'label' => 'Cheque Number',
                                'placeholder' => 'Enter cheque number',
                                'logics' => [
                                    [
                                        'field' => 'paid_by_id',
                                        'values' => ['Cheque'],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'text',
                                'name' => 'payment_note',
                                'label' => 'Payment Note',
                                'placeholder' => 'Enter payment note',
                                'multiline' => true,
                            ],
                        ]
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'sale_id',
                        'value' => $id,
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the payment form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sales-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to delete sales.',
                ], 403);
            }

            $lims_sale_data = Sale::find($id);

            if (!$lims_sale_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found.',
                ], 404);
            }

            // Remove reward points
            $lims_reward_point = RewardPoint::where('sale_id', $lims_sale_data->id)->first();
            if ($lims_reward_point) {
                $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                $lims_customer_data->points -= $lims_reward_point->points;
                $lims_customer_data->save();
                $lims_reward_point->delete();
            }

            // Delete returns related to this sale
            $return_ids = Returns::where('sale_id', $id)->pluck('id')->toArray();
            if (count($return_ids)) {
                ProductReturn::whereIn('return_id', $return_ids)->delete();
                Returns::whereIn('id', $return_ids)->delete();
            }

            $lims_product_sale_data = Product_Sale::where('sale_id', $id)->get();
            $lims_delivery_data = Delivery::where('sale_id', $id)->get();
            $lims_packing_slip_data = PackingSlip::where('sale_id', $id)->get();

            // Restore inventory
            foreach ($lims_product_sale_data as $product_sale) {
                $lims_product_data = Product::find($product_sale->product_id);

                // Handle combo products
                if (($lims_sale_data->sale_status == 1) && ($lims_product_data->type == 'combo')) {
                    if (!in_array('manufacturing', explode(',', config('addons')))) {
                        $product_list = explode(",", $lims_product_data->product_list);
                        $variant_list = $lims_product_data->variant_list ? explode(",", $lims_product_data->variant_list) : [];
                        $qty_list = explode(",", $lims_product_data->qty_list);

                        foreach ($product_list as $index => $child_id) {
                            $child_data = Product::find($child_id);
                            if (count($variant_list) && $variant_list[$index]) {
                                $child_product_variant_data = ProductVariant::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]]
                                ])->first();

                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['variant_id', $variant_list[$index]],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();

                                $child_product_variant_data->qty += $product_sale->qty * $qty_list[$index];
                                $child_product_variant_data->save();
                            } else {
                                $child_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $child_id],
                                    ['warehouse_id', $lims_sale_data->warehouse_id],
                                ])->first();
                            }

                            $child_data->qty += $product_sale->qty * $qty_list[$index];
                            $child_warehouse_data->qty += $product_sale->qty * $qty_list[$index];

                            $child_data->save();
                            $child_warehouse_data->save();
                        }
                    }
                }

                // Restore inventory for regular products
                if (($lims_sale_data->sale_status == 1) && ($product_sale->sale_unit_id != 0)) {
                    $lims_sale_unit_data = Unit::find($product_sale->sale_unit_id);
                    $restore_qty = $product_sale->qty;

                    if ($lims_sale_unit_data->operator == '*')
                        $restore_qty = $restore_qty * $lims_sale_unit_data->operation_value;
                    else
                        $restore_qty = $restore_qty / $lims_sale_unit_data->operation_value;

                    if ($product_sale->variant_id) {
                        $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_sale->variant_id)->first();
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($lims_product_data->id, $product_sale->variant_id, $lims_sale_data->warehouse_id)->first();
                        $lims_product_variant_data->qty += $restore_qty;
                        $lims_product_variant_data->save();
                    } elseif ($product_sale->product_batch_id) {
                        $lims_product_batch_data = ProductBatch::find($product_sale->product_batch_id);
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_batch_id', $product_sale->product_batch_id],
                            ['warehouse_id', $lims_sale_data->warehouse_id]
                        ])->first();

                        $lims_product_batch_data->qty += $restore_qty;
                        $lims_product_batch_data->save();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $lims_sale_data->warehouse_id)->first();
                    }

                    $lims_product_data->qty += $restore_qty;
                    $lims_product_warehouse_data->qty += $restore_qty;
                    $lims_product_data->save();

                    // Restore IMEI numbers
                    if ($product_sale->imei_number && !str_contains($product_sale->imei_number, "null")) {
                        if ($lims_product_warehouse_data->imei_number)
                            $lims_product_warehouse_data->imei_number .= ',' . $product_sale->imei_number;
                        else
                            $lims_product_warehouse_data->imei_number = $product_sale->imei_number;
                    }

                    $lims_product_warehouse_data->save();
                }

                $product_sale->delete();
            }

            // Handle payments
            $lims_payment_data = Payment::where('sale_id', $id)->get();
            foreach ($lims_payment_data as $payment) {
                if ($payment->paying_method == 'Gift Card') {
                    $lims_payment_with_gift_card_data = PaymentWithGiftCard::where('payment_id', $payment->id)->first();
                    if ($lims_payment_with_gift_card_data) {
                        $lims_gift_card_data = GiftCard::find($lims_payment_with_gift_card_data->gift_card_id);
                        $lims_gift_card_data->expense -= $payment->amount;
                        $lims_gift_card_data->save();
                        $lims_payment_with_gift_card_data->delete();
                    }
                } elseif ($payment->paying_method == 'Cheque') {
                    $lims_payment_cheque_data = PaymentWithCheque::where('payment_id', $payment->id)->first();
                    if ($lims_payment_cheque_data)
                        $lims_payment_cheque_data->delete();
                } elseif ($payment->paying_method == 'Credit Card') {
                    $lims_payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment->id)->first();
                    if ($lims_payment_with_credit_card_data)
                        $lims_payment_with_credit_card_data->delete();
                } elseif ($payment->paying_method == 'Paypal') {
                    $lims_payment_paypal_data = PaymentWithPaypal::where('payment_id', $payment->id)->first();
                    if ($lims_payment_paypal_data)
                        $lims_payment_paypal_data->delete();
                } elseif ($payment->paying_method == 'Deposit') {
                    $lims_customer_data = Customer::find($lims_sale_data->customer_id);
                    $lims_customer_data->expense -= $payment->amount;
                    $lims_customer_data->save();
                }
                $payment->delete();
            }

            // Delete deliveries and packing slips
            if ($lims_delivery_data->isNotEmpty()) {
                $lims_delivery_data->each->delete();
            }
            if ($lims_packing_slip_data->isNotEmpty()) {
                $lims_packing_slip_data->each->delete();
            }

            // Handle coupon usage
            if ($lims_sale_data->coupon_id) {
                $lims_coupon_data = Coupon::find($lims_sale_data->coupon_id);
                if ($lims_coupon_data) {
                    $lims_coupon_data->used -= 1;
                    $lims_coupon_data->save();
                }
            }

            // Delete document
            $this->fileDelete(public_path('documents/sale/'), $lims_sale_data->document);

            // Soft delete sale
            $lims_sale_data->deleted_by = Auth::id();
            $lims_sale_data->save();
            $lims_sale_data->delete();

            DB::commit();

            $message = $lims_sale_data->sale_status == 3 ? 'Draft deleted successfully' : 'Sale deleted successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sales-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $sale = Sale::with(['customer', 'warehouse', 'biller', 'products'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $sale,
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sales-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import sales.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            // For now, return a simple form schema
            // TODO: Implement full CSV import logic from web controller
            return response()->json([
                "title" => "Import Sales",
                "submit_url" => "/sales/import",
                "navigate_url" => "/sales",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV file with sale data",
                        "file_link" => url('sample_file/sample_sale.csv'),
                        "sample_file_name" => "sample_sale.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the import form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }
}
