<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\PurchaseRequest;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Account;
use App\Models\Purchase;
use App\Models\ProductPurchase;
use App\Models\Product_Warehouse;
use App\Models\Payment;
use App\Models\PaymentWithCheque;
use App\Models\PaymentWithCreditCard;
use App\Models\PosSetting;
use App\Models\Currency;
use App\Models\CustomField;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralSetting;
use Stripe\Stripe;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Variant;
use App\Models\Product_Sale;
use App\Traits\StaffAccess;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Traits\TenantInfo;
use App\Traits\APIPaginationTrait;

class PurchaseController extends Controller
{
    use TenantInfo, StaffAccess, APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchases-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Purchase::with(['supplier', 'warehouse']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $query = $query->orderBy('created_at', 'desc');

            $purchases = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format purchases for datatable
            $purchasesTable = $purchases->map(function ($purchase) {
                // Purchase Status HTML
                $purchaseStatusHtml = '';
                switch ($purchase->status) {
                    case 1:
                        $purchaseStatusHtml = "<span style='color: green; font-weight: bold;'>Received</span>";
                        break;
                    case 2:
                        $purchaseStatusHtml = "<span style='color: orange; font-weight: bold;'>Partial</span>";
                        break;
                    case 3:
                        $purchaseStatusHtml = "<span style='color: #ffc107; font-weight: bold;'>Pending</span>";
                        break;
                    case 4:
                        $purchaseStatusHtml = "<span style='color: #007bff; font-weight: bold;'>Ordered</span>";
                        break;
                    default:
                        $purchaseStatusHtml = "<span style='color: #6c757d; font-weight: bold;'>Unknown</span>";
                }

                // Payment Status HTML
                $paymentStatusHtml = '';
                switch ($purchase->payment_status) {
                    case 1:
                        $paymentStatusHtml = "<span style='color: #ffc107; font-weight: bold;'>Due</span>";
                        break;
                    case 2:
                        $paymentStatusHtml = "<span style='color: green; font-weight: bold;'>Paid</span>";
                        break;
                    default:
                        $paymentStatusHtml = "<span style='color: #6c757d; font-weight: bold;'>Unknown</span>";
                }

                return [
                    'id' => $purchase->id,
                    'date' => date(config('date_format'), strtotime($purchase->created_at)),
                    'reference_no' => $purchase->reference_no,
                    'supplier' => $purchase->supplier->name ?? 'N/A',
                    'purchase_status' => $purchaseStatusHtml,
                    'purchase_status_value' => $purchase->status,
                    'grand_total' => number_format($purchase->grand_total, config('decimal')),
                    'returned_amount' => number_format($purchase->return_id ? \App\Models\Returns::find($purchase->return_id)->grand_total ?? 0 : 0, config('decimal')),
                    'paid' => number_format($purchase->paid_amount ?? 0, config('decimal')),
                    'due' => number_format(($purchase->grand_total - ($purchase->paid_amount ?? 0)), config('decimal')),
                    'payment_status' => $paymentStatusHtml,
                    'payment_status_value' => $purchase->payment_status,
                ];
            });

            return $this->withDashBackground([
                'title' => "Purchases",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Purchase',
                'add_url' => '/purchases/create',
                'columns' => [
                    [
                        'label' => 'Date',
                        'field' => 'date',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Reference',
                        'field' => 'reference_no',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Supplier',
                        'field' => 'supplier',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Purchase Status',
                        'field' => 'purchase_status',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Grand Total',
                        'field' => 'grand_total',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Returned Amount',
                        'field' => 'returned_amount',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Paid',
                        'field' => 'paid',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Due',
                        'field' => 'due',
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Payment Status',
                        'field' => 'payment_status',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'button',
                                'label' => 'View Payment',
                                'action' => [
                                    'api_url' => '/purchases/{id}/payments',
                                    'type' => 'datatable'
                                ]
                            ],
                            [
                                'type' => 'button',
                                'label' => 'Add Payment',
                                'action' => [
                                    'api_url' => '/purchases/{id}/add-payment',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/purchases/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/purchases/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $purchasesTable,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving purchase data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchases-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $suppliers = Supplier::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name . ' (' . $item->company_name . ')'
                ];
            });

            $warehouses = Warehouse::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $taxes = Tax::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->rate,
                    'label' => $item->name
                ];
            });

            $currencies = Currency::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->code,
                    'exchange_rate' => $item->exchange_rate
                ];
            });

            $defaultCurrency = Currency::where('exchange_rate', 1)->first();

            // Get custom fields
            $customFields = CustomField::where('belongs_to', 'purchase')->get();
            $customFieldsArray = [];

            foreach ($customFields as $field) {
                $field_name = str_replace(' ', '_', strtolower($field->name));

                if (!$field->is_admin || $user->role_id == 1) {
                    $fieldData = [
                        'type' => $field->type,
                        'name' => $field_name,
                        'label' => $field->name,
                    ];

                    if ($field->is_required) {
                        $fieldData['required'] = true;
                    }

                    if ($field->type === 'number') {
                        $fieldData['keyboard_type'] = 'number';
                    } elseif ($field->type === 'textarea') {
                        $fieldData['type'] = 'editor';
                    } elseif ($field->type === 'checkbox') {
                        $fieldData['type'] = 'checkbox';
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                    } elseif ($field->type === 'radio_button') {
                        $fieldData['type'] = 'select';
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                    } elseif ($field->type === 'select') {
                        $fieldData['type'] = 'select';
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                    } elseif ($field->type === 'multi_select') {
                        $fieldData['type'] = 'select';
                        $fieldData['multiple'] = true;
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                    } elseif ($field->type === 'date_picker') {
                        $fieldData['type'] = 'datepicker';
                        $fieldData['format_specifier'] = 'dd MMMM, yyyy';
                    }

                    $customFieldsArray[] = $fieldData;
                }
            }

            $formSchema = [
                "title" => "Add Purchase",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/purchases",
                "method" => "POST",
                "navigate_url" => "/purchases",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Purchase Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Choose date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => now()->format('d F, Y'),
                            ],
                            [
                                "type" => "datagenerator",
                                "name" => "reference_no",
                                "label" => "Reference No",
                                "placeholder" => "Enter reference number",
                                "generator_url" => "/generate/purchase-reference",
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouses,
                                "required" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "supplier_id",
                                "label" => "Supplier",
                                "placeholder" => "Select supplier",
                                "options" => $suppliers,
                                "new_screen" => "/suppliers/create",
                            ],
                            [
                                "type" => "select",
                                "name" => "status",
                                "label" => "Purchase Status",
                                "placeholder" => "Select status",
                                "options" => [
                                    ["value" => 1, "label" => "Received"],
                                    ["value" => 2, "label" => "Partial"],
                                    ["value" => 3, "label" => "Pending"],
                                    ["value" => 4, "label" => "Ordered"],
                                ],
                                "value" => 1,
                            ],
                            [
                                "type" => "file",
                                "name" => "document",
                                "label" => "Attach Document",
                                "info" => "Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported",
                                "show_info_icon" => true,
                                "allowed_extensions" => ["jpg", "jpeg", "png", "gif", "pdf", "csv", "docx", "xlsx", "txt"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "select",
                                "name" => "currency_id",
                                "label" => "Currency *",
                                "placeholder" => "Select currency",
                                "options" => $currencies,
                                "value" => $defaultCurrency ? $defaultCurrency->id : null,
                                "required" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "exchange_rate",
                                "label" => "Exchange Rate *",
                                "placeholder" => "1.00",
                                "keyboard_type" => "number",
                                "value" => $defaultCurrency ? $defaultCurrency->exchange_rate : "1.00",
                                "info" => "Currency exchange rate",
                                "show_info_icon" => true,
                                "required" => true,
                            ],
                        ]
                    ],
                ]
            ];

            // Add custom fields if they exist
            if (!empty($customFieldsArray)) {
                $formSchema["fields"][] = [
                    "type" => "group",
                    "label" => "Custom Fields",
                    "items" => $customFieldsArray
                ];
            }

            // Add product selection section with table generator
            $formSchema["fields"][] = [
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
                                "name" => "batch_no",
                                "label" => "Batch No",
                                "type" => "text",
                                "editable" => true,
                                "width" => 120,
                            ],
                            [
                                "name" => "expired_date",
                                "label" => "Expired Date",
                                "type" => "date",
                                "editable" => true,
                                "width" => 140,
                            ],
                            [
                                "name" => "qty",
                                "label" => "Quantity",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 0,
                                "default" => 1,
                            ],
                            [
                                "name" => "cost",
                                "label" => "Unit Cost",
                                "type" => "number",
                                "editable" => true,
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "discount",
                                "label" => "Discount",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 2,
                                "default" => 0,
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
                                "name" => "profit_margin",
                                "label" => "Profit Margin %",
                                "type" => "number",
                                "editable" => true,
                                "width" => 120,
                                "decimal_places" => 2,
                                "default" => 0,
                            ],
                            [
                                "name" => "product_price",
                                "label" => "Product Price",
                                "type" => "formula",
                                "formula" => "cost * (1 + profit_margin / 100)",
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "subtotal",
                                "label" => "Subtotal",
                                "type" => "formula",
                                "formula" => "(qty * cost * (1 + tax / 100)) - discount",
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
                                "label" => "Total Discount",
                                "formula" => "SUM(discount)",
                                "position" => "left",
                                "prefix" => config('currency'),
                                "decimal_places" => 2,
                            ],
                            [
                                "label" => "Total Cost",
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
            ];

            // Add final calculations section
            $formSchema["fields"][] = [
                "type" => "group",
                "label" => "Order Summary",
                "items" => [
                    [
                        "type" => "select",
                        "name" => "order_tax_rate",
                        "label" => "Order Tax",
                        "placeholder" => "Select tax",
                        "options" => array_merge([["value" => 0, "label" => "No Tax"]], $taxes->toArray()),
                        "value" => 0,
                    ],
                    [
                        "type" => "text",
                        "name" => "order_discount",
                        "label" => "Discount",
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
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter any additional notes",
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the purchase creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchases-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $purchase = Purchase::find($id);
            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase not found.',
                ], 404);
            }

            $suppliers = Supplier::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name . ' (' . $item->company_name . ')'
                ];
            });

            $warehouses = Warehouse::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $taxes = Tax::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->rate,
                    'label' => $item->name
                ];
            });

            // Get custom fields
            $customFields = CustomField::where('belongs_to', 'purchase')->get();
            $customFieldsArray = [];

            foreach ($customFields as $field) {
                $field_name = str_replace(' ', '_', strtolower($field->name));

                if (!$field->is_admin || $user->role_id == 1) {
                    $fieldData = [
                        'type' => $field->type,
                        'name' => $field_name,
                        'label' => $field->name,
                        'value' => $purchase->$field_name ?? '',
                    ];

                    if ($field->is_required) {
                        $fieldData['required'] = true;
                    }

                    if ($field->type === 'number') {
                        $fieldData['keyboard_type'] = 'number';
                    } elseif ($field->type === 'textarea') {
                        $fieldData['type'] = 'editor';
                    } elseif ($field->type === 'checkbox') {
                        $fieldData['type'] = 'checkbox';
                        $option_values = explode(',', $field->option_value);
                        $field_values = explode(',', $purchase->$field_name ?? '');
                        $fieldData['options'] = array_map(function ($option) use ($field_values) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option),
                                'checked' => in_array(trim($option), $field_values)
                            ];
                        }, $option_values);
                    } elseif ($field->type === 'radio_button') {
                        $fieldData['type'] = 'select';
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                        $fieldData['value'] = $purchase->$field_name;
                    } elseif ($field->type === 'select') {
                        $fieldData['type'] = 'select';
                        $option_values = explode(',', $field->option_value);
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                        $fieldData['value'] = $purchase->$field_name;
                    } elseif ($field->type === 'multi_select') {
                        $fieldData['type'] = 'select';
                        $fieldData['multiple'] = true;
                        $option_values = explode(',', $field->option_value);
                        $field_values = explode(',', $purchase->$field_name ?? '');
                        $fieldData['options'] = array_map(function ($option) {
                            return [
                                'value' => trim($option),
                                'label' => trim($option)
                            ];
                        }, $option_values);
                        $fieldData['value'] = $field_values;
                    } elseif ($field->type === 'date_picker') {
                        $fieldData['type'] = 'datepicker';
                        $fieldData['format_specifier'] = 'dd MMMM, yyyy';
                    }

                    $customFieldsArray[] = $fieldData;
                }
            }

            // Get purchase items for editing
            $purchaseItems = ProductPurchase::where('purchase_id', $id)->get();
            $purchaseItemsData = [];

            foreach ($purchaseItems as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                $tax = Tax::where('rate', $item->tax_rate)->first();
                $productBatch = ProductBatch::find($item->product_batch_id);

                $purchaseItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'qty' => $item->qty,
                    'net_unit_cost' => $item->net_unit_cost,
                    'discount' => $item->discount,
                    'tax_rate' => $item->tax_rate,
                    'tax' => $item->tax,
                    'total' => $item->total,
                    'net_unit_margin' => $item->net_unit_margin ?? 0,
                    'net_unit_price' => $item->net_unit_price ?? 0,
                    'batch_no' => $productBatch ? $productBatch->batch_no : '',
                    'expired_date' => $productBatch ? $productBatch->expired_date : '',
                    'tax_name' => $tax ? $tax->name : 'No Tax',
                ];
            }

            $formSchema = [
                "title" => "Edit Purchase",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/purchases/" . $id,
                "method" => "PUT",
                "navigate_url" => "/purchases",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Purchase Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => $purchase->created_at ? $purchase->created_at->format('d F, Y') : now()->format('d F, Y'),
                            ],
                            [
                                "type" => "text",
                                "name" => "reference_no_display",
                                "label" => "Reference No",
                                "value" => $purchase->reference_no,
                                "disabled" => true,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "reference_no",
                                "value" => $purchase->reference_no,
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouses,
                                "value" => $purchase->warehouse_id,
                                "required" => true,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "warehouse_id_hidden",
                                "value" => $purchase->warehouse_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "supplier_id",
                                "label" => "Supplier",
                                "placeholder" => "Select supplier",
                                "options" => $suppliers,
                                "value" => $purchase->supplier_id,
                                "new_screen" => "/suppliers/create",
                            ],
                            [
                                "type" => "hidden",
                                "name" => "supplier_id_hidden",
                                "value" => $purchase->supplier_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "status",
                                "label" => "Purchase Status",
                                "placeholder" => "Select status",
                                "options" => [
                                    ["value" => 1, "label" => "Received"],
                                    ["value" => 2, "label" => "Partial"],
                                    ["value" => 3, "label" => "Pending"],
                                    ["value" => 4, "label" => "Ordered"],
                                ],
                                "value" => $purchase->status,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "status_hidden",
                                "value" => $purchase->status,
                            ],
                            [
                                "type" => "file",
                                "name" => "document",
                                "label" => "Attach Document",
                                "info" => "Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported",
                                "show_info_icon" => true,
                                "allowed_extensions" => ["jpg", "jpeg", "png", "gif", "pdf", "csv", "docx", "xlsx", "txt"],
                                "multiple" => false,
                            ],
                        ]
                    ],
                ]
            ];

            // Add custom fields if they exist
            if (!empty($customFieldsArray)) {
                $formSchema["fields"][] = [
                    "type" => "group",
                    "label" => "Custom Fields",
                    "items" => $customFieldsArray
                ];
            }

            // Prepare product rows for table generator
            $productRows = [];
            foreach ($purchaseItemsData as $item) {
                $productRows[] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['product_name'],
                    'code' => $item['product_code'],
                    'batch_no' => $item['batch_no'],
                    'expired_date' => $item['expired_date'],
                    'qty' => (int)$item['qty'],
                    'cost' => number_format((float)$item['net_unit_cost'], 2, '.', ''),
                    'discount' => number_format((float)$item['discount'], 2, '.', ''),
                    'tax' => number_format((float)$item['tax_rate'], 2, '.', ''),
                    'profit_margin' => number_format((float)$item['net_unit_margin'], 2, '.', ''),
                    'product_price' => number_format((float)$item['net_unit_price'], 2, '.', ''),
                    'subtotal' => number_format((float)$item['total'], 2, '.', ''),
                ];
            }

            // Add product selection section with table generator
            $formSchema["fields"][] = [
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
                        "value" => $productRows,
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
                                "name" => "batch_no",
                                "label" => "Batch No",
                                "type" => "text",
                                "editable" => true,
                                "width" => 120,
                            ],
                            [
                                "name" => "expired_date",
                                "label" => "Expired Date",
                                "type" => "date",
                                "editable" => true,
                                "width" => 140,
                            ],
                            [
                                "name" => "qty",
                                "label" => "Quantity",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 0,
                                "default" => 1,
                            ],
                            [
                                "name" => "cost",
                                "label" => "Unit Cost",
                                "type" => "number",
                                "editable" => true,
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "discount",
                                "label" => "Discount",
                                "type" => "number",
                                "editable" => true,
                                "width" => 100,
                                "decimal_places" => 2,
                                "default" => 0,
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
                                "name" => "profit_margin",
                                "label" => "Profit Margin %",
                                "type" => "number",
                                "editable" => true,
                                "width" => 120,
                                "decimal_places" => 2,
                                "default" => 0,
                            ],
                            [
                                "name" => "product_price",
                                "label" => "Product Price",
                                "type" => "formula",
                                "formula" => "cost * (1 + profit_margin / 100)",
                                "width" => 120,
                                "decimal_places" => 2,
                            ],
                            [
                                "name" => "subtotal",
                                "label" => "Subtotal",
                                "type" => "formula",
                                "formula" => "(qty * cost * (1 + tax / 100)) - discount",
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
                                "label" => "Total Discount",
                                "formula" => "SUM(discount)",
                                "position" => "left",
                                "prefix" => config('currency'),
                                "decimal_places" => 2,
                            ],
                            [
                                "label" => "Total Cost",
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
            ];

            // Add hidden fields for totals
            $hiddenFields = [
                ["type" => "hidden", "name" => "total_qty", "value" => $purchase->total_qty],
                ["type" => "hidden", "name" => "total_discount", "value" => $purchase->total_discount],
                ["type" => "hidden", "name" => "total_tax", "value" => $purchase->total_tax],
                ["type" => "hidden", "name" => "total_cost", "value" => $purchase->total_cost],
                ["type" => "hidden", "name" => "item", "value" => $purchase->item],
                ["type" => "hidden", "name" => "order_tax", "value" => $purchase->order_tax],
                ["type" => "hidden", "name" => "grand_total", "value" => $purchase->grand_total],
                ["type" => "hidden", "name" => "paid_amount", "value" => $purchase->paid_amount],
            ];

            // Add final calculations section
            $formSchema["fields"][] = [
                "type" => "group",
                "label" => "Order Summary",
                "items" => array_merge([
                    [
                        "type" => "select",
                        "name" => "order_tax_rate",
                        "label" => "Order Tax",
                        "placeholder" => "Select tax",
                        "options" => array_merge([["value" => 0, "label" => "No Tax"]], $taxes->toArray()),
                        "value" => $purchase->order_tax_rate,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "order_tax_rate_hidden",
                        "value" => $purchase->order_tax_rate,
                    ],
                    [
                        "type" => "text",
                        "name" => "order_discount",
                        "label" => "Discount",
                        "placeholder" => "0.00",
                        "keyboard_type" => "number",
                        "value" => $purchase->order_discount ? number_format($purchase->order_discount, 2) : "0.00",
                    ],
                    [
                        "type" => "text",
                        "name" => "shipping_cost",
                        "label" => "Shipping Cost",
                        "placeholder" => "0.00",
                        "keyboard_type" => "number",
                        "value" => $purchase->shipping_cost ? number_format($purchase->shipping_cost, 2) : "0.00",
                    ],
                    [
                        "type" => "editor",
                        "name" => "note",
                        "label" => "Note",
                        "value" => $purchase->note,
                    ],
                ], $hiddenFields)
            ];

            // Add purchase items data for the app to display/edit
            $formSchema["purchase_items"] = $purchaseItemsData;
            $formSchema["purchase_totals"] = [
                "total_qty" => $purchase->total_qty,
                "total_discount" => number_format($purchase->total_discount, 2),
                "total_tax" => number_format($purchase->total_tax, 2),
                "total_cost" => number_format($purchase->total_cost, 2),
                "order_tax" => number_format($purchase->order_tax, 2),
                "order_discount" => number_format($purchase->order_discount, 2),
                "shipping_cost" => number_format($purchase->shipping_cost, 2),
                "grand_total" => number_format($purchase->grand_total, 2),
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the purchase edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isImeiExist(string $imei, string $product_id): bool
    {
        $product_warehouses = Product_Warehouse::where('product_id', $product_id)->get();
        foreach ($product_warehouses as $p) {
            $imeis = explode(',', $p->imei_number);
            if (in_array(trim($imei), array_map('trim', $imeis))) {
                return true;
            }
        }

        return false;
    }

    public function store(PurchaseRequest $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->except('document', 'token');
            $data['user_id'] = Auth::id();

            if (!isset($data['reference_no'])) {
                $data['reference_no'] = 'pr-' . date("Ymd") . '-' . date("his");
            }

            $document = $request->file('document');
            // return dd($data);
            if ($document) {
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move(public_path('documents/purchase'), $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move(public_path('documents/purchase'), $documentName);
                }
                $data['document'] = $documentName;
            }

            if (isset($data['created_at'])) {
                $data['created_at'] = str_replace("/", "-", $data['created_at']);
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            } else
                $data['created_at'] = date("Y-m-d H:i:s");

            // Calculate required fields
            $data['item'] = count($data['product_id'] ?? []);
            $data['total_qty'] = array_sum($data['qty'] ?? []);
            $data['total_discount'] = array_sum($data['discount'] ?? []);
            $data['total_tax'] = array_sum($data['tax'] ?? []);
            $data['total_cost'] = array_sum($data['subtotal'] ?? []);
            $data['grand_total'] = $data['total_cost'] + ($data['order_tax'] ?? 0) + ($data['shipping_cost'] ?? 0) - ($data['order_discount'] ?? 0);
            $data['paid_amount'] = 0; // Default to 0, can be updated later
            $data['payment_status'] = 1; // 1 = Due

            $lims_purchase_data = Purchase::create($data);
            // return $lims_purchase_data;
            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'purchase')->select('name', 'type')->get();
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
                DB::table('purchases')->where('id', $lims_purchase_data->id)->update($custom_field_data);
            $product_id = $data['product_id'];
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $recieved = $data['recieved'];
            $batch_no = $data['batch_no'];
            $expired_date = $data['expired_date'];
            $purchase_unit = $data['purchase_unit'];
            $net_unit_cost = $data['net_unit_cost'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $profit_margin = $data['profit_margin'] ?? [];
            $product_price = $data['product_price'] ?? [];
            $imei_numbers = $data['imei_number'];
            $product_purchase = [];

            foreach ($product_id as $i => $id) {
                $lims_purchase_unit_data  = Unit::where('unit_name', $purchase_unit[$i])->first();
                // return $lims_purchase_unit_data;
                if ($lims_purchase_unit_data->operator == '*') {
                    $quantity = $recieved[$i] * $lims_purchase_unit_data->operation_value;
                } else {
                    $quantity = $recieved[$i] / $lims_purchase_unit_data->operation_value;
                }
                $lims_product_data = Product::find($id);
                $price = $lims_product_data->price;
                //dealing with product barch
                if ($batch_no[$i]) {
                    $product_batch_data = ProductBatch::where([
                        ['product_id', $lims_product_data->id],
                        ['batch_no', $batch_no[$i]]
                    ])->first();
                    if ($product_batch_data) {
                        $product_batch_data->expired_date = $expired_date[$i];
                        $product_batch_data->qty += $quantity;
                        $product_batch_data->save();
                    } else {
                        $product_batch_data = ProductBatch::create([
                            'product_id' => $lims_product_data->id,
                            'batch_no' => $batch_no[$i],
                            'expired_date' => $expired_date[$i],
                            'qty' => $quantity
                        ]);
                    }
                    $product_purchase['product_batch_id'] = $product_batch_data->id;
                } else
                    $product_purchase['product_batch_id'] = null;

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($lims_product_data->id, $product_code[$i])->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $id],
                        ['variant_id', $lims_product_variant_data->variant_id],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;
                    //add quantity to product variant table
                    $lims_product_variant_data->qty += $quantity;
                    $lims_product_variant_data->save();

                    // Update product name with variant
                    // if (strpos($lims_product_data->name, ")")) {
                    //     continue;
                    // }
                    // $variant = Variant::where('id', $lims_product_variant_data->variant_id)->select('name')->first();
                    // $lims_product_data->name = $lims_product_data->name . '(' . $variant->name . ')';
                    // $lims_product_data->save();
                } else {
                    $product_purchase['variant_id'] = null;
                    if ($product_purchase['product_batch_id']) {
                        //checking for price
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])
                            ->whereNotNull('price')
                            ->select('price')
                            ->first();
                        if ($lims_product_warehouse_data)
                            $price = $lims_product_warehouse_data->price;
                        else
                            $price = null;
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['product_batch_id', $product_purchase['product_batch_id']],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    }
                }
                //add quantity to product table
                $lims_product_data->qty = $lims_product_data->qty + $quantity;
                $lims_product_data->save();
                //add quantity to warehouse
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty = $lims_product_warehouse_data->qty + $quantity;
                    $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                } else {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $id;
                    $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                    $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                    $lims_product_warehouse_data->qty = $quantity;
                    if ($price)
                        $lims_product_warehouse_data->price = $price;
                    if ($lims_product_data->is_variant)
                        $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
                }

                if ($imei_numbers[$i]) {
                    // prevent duplication
                    $imeis = explode(',', $imei_numbers[$i]);
                    $imeis = array_map('trim', $imeis);
                    if (count($imeis) !== count(array_unique($imeis))) {
                        DB::rollBack();
                        return redirect('purchases/create')->with('not_permitted', 'Duplicate IMEI not allowed!');
                    }
                    foreach ($imeis as $imei) {
                        if ($this->isImeiExist($imei, $id)) {
                            DB::rollBack();
                            return redirect('purchases/create')->with('not_permitted', 'Duplicate IMEI not allowed!');
                        }
                    }
                    //added imei numbers to product_warehouse table
                    if ($lims_product_warehouse_data->imei_number)
                        $lims_product_warehouse_data->imei_number .= ',' . $imei_numbers[$i];
                    else
                        $lims_product_warehouse_data->imei_number = $imei_numbers[$i];
                }
                $lims_product_warehouse_data->save();

                $product_purchase['purchase_id'] = $lims_purchase_data->id;
                $product_purchase['product_id'] = $id;
                $product_purchase['imei_number'] = $imei_numbers[$i];
                $product_purchase['qty'] = $qty[$i];
                $product_purchase['recieved'] = $recieved[$i];
                $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
                $product_purchase['net_unit_cost'] = $net_unit_cost[$i];
                $product_purchase['discount'] = $discount[$i];
                $product_purchase['tax_rate'] = $tax_rate[$i];
                $product_purchase['tax'] = $tax[$i];
                $product_purchase['total'] = $total[$i];
                $product_purchase['net_unit_margin'] = $profit_margin[$i] ?? 0;
                $product_purchase['net_unit_price'] = $product_price[$i] ?? 0;
                ProductPurchase::create($product_purchase);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if an error occurs
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update(PurchaseRequest $request, $id)
    {
        $lims_purchase_data = Purchase::find($id);
        $data = $request->except('document', 'token');
        $document = $request->file('document');
        if ($document) {

            $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $documentName = $documentName . '.' . $ext;
                $document->move(public_path('documents/purchase'), $documentName);
            } else {
                $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                $document->move(public_path('documents/purchase'), $documentName);
            }
            $data['document'] = $documentName;
        }
        //return dd($data);
        DB::beginTransaction();

        try {
            $balance = (float)$data['grand_total'] - (float)$data['paid_amount'];
            if ($balance < 0 || $balance > 0) {
                $data['payment_status'] = 1;
            } else {
                $data['payment_status'] = 2;
            }
            $lims_product_purchase_data = ProductPurchase::where('purchase_id', $id)->get();

            $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at']))) . ' ' . date("H:i:s");
            $product_id = $data['product_id'];
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $recieved = $data['recieved'];
            $batch_no = $data['batch_no'];
            $expired_date = $data['expired_date'];
            $purchase_unit = $data['purchase_unit'];
            $net_unit_cost = $data['net_unit_cost'];
            $discount = $data['discount'];
            $tax_rate = $data['tax_rate'];
            $tax = $data['tax'];
            $total = $data['subtotal'];
            $profit_margin = $data['profit_margin'] ?? [];
            $product_price = $data['product_price'] ?? [];
            $imei_number = $new_imei_number = $data['imei_number'];
            $product_purchase = [];

            foreach ($lims_product_purchase_data as $product_purchase_data) {

                $old_recieved_value = $product_purchase_data->recieved;
                $lims_purchase_unit_data = Unit::find($product_purchase_data->purchase_unit_id);

                if ($lims_purchase_unit_data->operator == '*') {
                    $old_recieved_value = $old_recieved_value * $lims_purchase_unit_data->operation_value;
                } else {
                    $old_recieved_value = $old_recieved_value / $lims_purchase_unit_data->operation_value;
                }
                $lims_product_data = Product::find($product_purchase_data->product_id);
                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProduct($lims_product_data->id, $product_purchase_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $lims_product_data->id],
                        ['variant_id', $product_purchase_data->variant_id],
                        ['warehouse_id', $lims_purchase_data->warehouse_id]
                    ])->first();
                    $lims_product_variant_data->qty -= $old_recieved_value;
                    $lims_product_variant_data->save();
                } elseif ($product_purchase_data->product_batch_id) {
                    $product_batch_data = ProductBatch::find($product_purchase_data->product_batch_id);
                    $product_batch_data->qty -= $old_recieved_value;
                    $product_batch_data->save();

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_purchase_data->product_id],
                        ['product_batch_id', $product_purchase_data->product_batch_id],
                        ['warehouse_id', $lims_purchase_data->warehouse_id],
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_purchase_data->product_id],
                        ['warehouse_id', $lims_purchase_data->warehouse_id],
                    ])->first();
                }
                if ($product_purchase_data->imei_number) {
                    $position = array_search($lims_product_data->id, $product_id);
                    if ($imei_number[$position]) {
                        $prev_imei_numbers = explode(",", $product_purchase_data->imei_number);
                        $new_imei_numbers = explode(",", $imei_number[$position]);
                        $temp_imeis = explode(',', $lims_product_warehouse_data->imei_number);
                        foreach ($prev_imei_numbers as $prev_imei_number) {
                            // $pos = array_search($prev_imei_number, $new_imei_numbers);
                            // if ($pos !== false) {
                            //     unset($new_imei_numbers[$pos]);
                            // }
                            $pos = array_search($prev_imei_number, $temp_imeis);
                            if ($pos !== false) {
                                unset($temp_imeis[$pos]);
                            }
                        }

                        // return dd($prev_imei_number, $temp_imeis);
                        $lims_product_warehouse_data->imei_number = !empty($temp_imeis) ? implode(',', $temp_imeis) : null;

                        $new_imei_number[$position] = implode(",", $new_imei_numbers);
                    }
                }
                $lims_product_data->qty -= $old_recieved_value;
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty -= $old_recieved_value;
                    $lims_product_warehouse_data->save();
                }
                $lims_product_data->save();
                $product_purchase_data->delete();
            }

            foreach ($product_id as $key => $pro_id) {
                $lims_purchase_unit_data = Unit::where('unit_name', $purchase_unit[$key])->first();
                if ($lims_purchase_unit_data->operator == '*') {
                    $new_recieved_value = $recieved[$key] * $lims_purchase_unit_data->operation_value;
                } else {
                    $new_recieved_value = $recieved[$key] / $lims_purchase_unit_data->operation_value;
                }

                $lims_product_data = Product::find($pro_id);
                $price = null;
                //dealing with product barch
                if ($batch_no[$key]) {
                    $product_batch_data = ProductBatch::where([
                        ['product_id', $lims_product_data->id],
                        ['batch_no', $batch_no[$key]]
                    ])->first();
                    if ($product_batch_data) {
                        $product_batch_data->qty += $new_recieved_value;
                        $product_batch_data->expired_date = $expired_date[$key];
                        $product_batch_data->save();
                    } else {
                        $product_batch_data = ProductBatch::create([
                            'product_id' => $lims_product_data->id,
                            'batch_no' => $batch_no[$key],
                            'expired_date' => $expired_date[$key],
                            'qty' => $new_recieved_value
                        ]);
                    }
                    $product_purchase['product_batch_id'] = $product_batch_data->id;
                } else
                    $product_purchase['product_batch_id'] = null;

                if ($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['variant_id', $lims_product_variant_data->variant_id],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    $product_purchase['variant_id'] = $lims_product_variant_data->variant_id;
                    //add quantity to product variant table
                    $lims_product_variant_data->qty += $new_recieved_value;
                    $lims_product_variant_data->save();
                } else {
                    $product_purchase['variant_id'] = null;
                    if ($product_purchase['product_batch_id']) {
                        //checking for price
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])
                            ->whereNotNull('price')
                            ->select('price')
                            ->first();
                        if ($lims_product_warehouse_data)
                            $price = $lims_product_warehouse_data->price;

                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['product_batch_id', $product_purchase['product_batch_id']],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                        ])->first();
                    }
                }

                $lims_product_data->qty += $new_recieved_value;
                if ($lims_product_warehouse_data) {
                    $lims_product_warehouse_data->qty += $new_recieved_value;
                    $lims_product_warehouse_data->save();
                } else {
                    $lims_product_warehouse_data = new Product_Warehouse();
                    $lims_product_warehouse_data->product_id = $pro_id;
                    $lims_product_warehouse_data->product_batch_id = $product_purchase['product_batch_id'];
                    if ($lims_product_data->is_variant)
                        $lims_product_warehouse_data->variant_id = $lims_product_variant_data->variant_id;
                    $lims_product_warehouse_data->warehouse_id = $data['warehouse_id'];
                    $lims_product_warehouse_data->qty = $new_recieved_value;
                    if ($price)
                        $lims_product_warehouse_data->price = $price;
                }
                //dealing with imei numbers
                if ($new_imei_number[$key]) {
                    // prevent duplication
                    $imeis = explode(',', $new_imei_number[$key]);
                    $imeis = array_map('trim', $imeis);
                    if (count($imeis) !== count(array_unique($imeis))) {
                        DB::rollBack();
                        return redirect()->route('purchases.edit', $id)->with('not_permitted', 'Duplicate IMEI not allowed!');
                    }
                    foreach ($imeis as $imei) {
                        if ($this->isImeiExist($imei, $product_purchase_data->product_id)) {
                            DB::rollBack();
                            return redirect()->route('purchases.edit', $id)->with('not_permitted', 'Duplicate IMEI not allowed!');
                        }
                    }

                    if (isset($lims_product_warehouse_data->imei_number)) {
                        $lims_product_warehouse_data->imei_number .= ',' . $new_imei_number[$key];
                    } else {
                        $lims_product_warehouse_data->imei_number = $new_imei_number[$key];
                    }
                }

                $lims_product_data->save();
                $lims_product_warehouse_data->save();

                $product_purchase['purchase_id'] = $id;
                $product_purchase['product_id'] = $pro_id;
                $product_purchase['qty'] = $qty[$key];
                $product_purchase['recieved'] = $recieved[$key];
                $product_purchase['purchase_unit_id'] = $lims_purchase_unit_data->id;
                $product_purchase['net_unit_cost'] = $net_unit_cost[$key];
                $product_purchase['discount'] = $discount[$key];
                $product_purchase['tax_rate'] = $tax_rate[$key];
                $product_purchase['tax'] = $tax[$key];
                $product_purchase['total'] = $total[$key];
                $product_purchase['net_unit_margin'] = $profit_margin[$key] ?? 0;
                $product_purchase['net_unit_price'] = $product_price[$key] ?? 0;
                $product_purchase['imei_number'] = $imei_number[$key] ?? null;
                ProductPurchase::create($product_purchase);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Purchase updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
        $lims_purchase_data->update($data);
        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'purchase')->select('name', 'type')->get();
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
            DB::table('purchases')->where('id', $lims_purchase_data->id)->update($custom_field_data);
        return redirect('purchases')->with('message', 'Purchase updated successfully');
    }

    public function show(Purchase $purchase)
    {
        return response()->json(
            new PurchaseResource($purchase)
        );
    }

    /**
     * Get payment list for a purchase
     */
    public function getPayments($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-payment-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $purchase = Purchase::findOrFail($id);
            $payments = Payment::where('purchase_id', $id)->get();

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
                'title' => 'Purchase Payments - ' . $purchase->reference_no,
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
     * Show form for adding payment to purchase
     */
    public function addPaymentForm($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-payment-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $purchase = Purchase::findOrFail($id);

            // Calculate balance
            $paidAmount = Payment::where('purchase_id', $id)->sum('amount');
            $balance = $purchase->grand_total - $paidAmount;

            if ($balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This purchase is already fully paid.',
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
                'title' => 'Add Payment - ' . $purchase->reference_no,
                'submit_url' => '/purchases/' . $id . '/add-payment',
                'navigate_url' => '/purchases',
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
                        'name' => 'purchase_id',
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
            $role = Role::find(Auth::user()->role_id);

            if (!$role->hasPermissionTo('purchases-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $lims_purchase_data = Purchase::find($id);

            if (!$lims_purchase_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase not found.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            $lims_product_purchase_data = ProductPurchase::where('purchase_id', $id)->get();

            if ($this->purchaseHasSale($lims_product_purchase_data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete, purchase has sale!',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 422);
            }

            $lims_payment_data = Payment::where('purchase_id', $id)->get();

            foreach ($lims_product_purchase_data as $product_purchase_data) {
                $lims_purchase_unit_data = Unit::find($product_purchase_data->purchase_unit_id);
                if ($lims_purchase_unit_data->operator == '*')
                    $recieved_qty = $product_purchase_data->recieved * $lims_purchase_unit_data->operation_value;
                else
                    $recieved_qty = $product_purchase_data->recieved / $lims_purchase_unit_data->operation_value;

                $lims_product_data = Product::find($product_purchase_data->product_id);

                if ($product_purchase_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($lims_product_data->id, $product_purchase_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithVariant($product_purchase_data->product_id, $product_purchase_data->variant_id, $lims_purchase_data->warehouse_id)
                        ->first();
                    $lims_product_variant_data->qty -= $recieved_qty;
                    $lims_product_variant_data->save();
                } elseif ($product_purchase_data->product_batch_id) {
                    $lims_product_batch_data = ProductBatch::find($product_purchase_data->product_batch_id);
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_batch_id', $product_purchase_data->product_batch_id],
                        ['warehouse_id', $lims_purchase_data->warehouse_id]
                    ])->first();

                    $lims_product_batch_data->qty -= $recieved_qty;
                    $lims_product_batch_data->save();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($product_purchase_data->product_id, $lims_purchase_data->warehouse_id)
                        ->first();
                }

                //deduct imei number if available
                if ($product_purchase_data->imei_number && !str_contains($product_purchase_data->imei_number, "null")) {
                    $imei_numbers = explode(",", $product_purchase_data->imei_number);
                    $all_imei_numbers = explode(",", $lims_product_warehouse_data->imei_number);
                    foreach ($imei_numbers as $number) {
                        if (($j = array_search($number, $all_imei_numbers)) !== false) {
                            unset($all_imei_numbers[$j]);
                        }
                    }
                    $lims_product_warehouse_data->imei_number = !empty($all_imei_numbers) ? implode(",", $all_imei_numbers) : null;
                }

                $lims_product_data->qty -= $recieved_qty;
                $lims_product_warehouse_data->qty -= $recieved_qty;

                $lims_product_warehouse_data->save();
                $lims_product_data->save();
                $product_purchase_data->delete();
            }

            $lims_pos_setting_data = PosSetting::latest()->first();

            foreach ($lims_payment_data as $payment_data) {
                if ($payment_data->paying_method == "Cheque") {
                    $payment_with_cheque_data = PaymentWithCheque::where('payment_id', $payment_data->id)->first();
                    if ($payment_with_cheque_data) {
                        $payment_with_cheque_data->delete();
                    }
                } elseif ($payment_data->paying_method == "Credit Card" && $lims_pos_setting_data && $lims_pos_setting_data->stripe_secret_key) {
                    $payment_with_credit_card_data = PaymentWithCreditCard::where('payment_id', $payment_data->id)->first();
                    if ($payment_with_credit_card_data) {
                        \Stripe\Stripe::setApiKey($lims_pos_setting_data->stripe_secret_key);
                        \Stripe\Refund::create(array(
                            "charge" => $payment_with_credit_card_data->charge_id,
                        ));
                        $payment_with_credit_card_data->delete();
                    }
                }
                $payment_data->delete();
            }

            $lims_purchase_data->deleted_by = Auth::id();
            $lims_purchase_data->save();

            $lims_purchase_data->delete();
            $this->fileDelete(public_path('documents/purchase/'), $lims_purchase_data->document);

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted successfully.',
                'navigate_url' => '/purchases',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the purchase.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    private function purchaseHasSale($lims_product_purchase_data)
    {
        $has_sale = false;
        foreach ($lims_product_purchase_data as $product_purchase_data) {
            $product_sale = Product_Sale::where('product_id', $product_purchase_data->product_id)
                ->select('updated_at')
                ->latest('updated_at')
                ->first();

            if (!$product_sale) {
                continue;
            }

            if ($product_sale->updated_at->gt($product_purchase_data->updated_at)) {
                $has_sale = true;
            }
        }

        return $has_sale;
    }

    protected function fileDelete($path, $filename)
    {
        $file_path = $path . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchases-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import purchases.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            // For now, return a simple form schema
            // TODO: Implement full CSV import logic from web controller
            return response()->json([
                "title" => "Import Purchases",
                "submit_url" => "/purchases/import",
                "navigate_url" => "/purchases",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV file with purchase data",
                        "file_link" => url('sample_file/sample_purchase.csv'),
                        "sample_file_name" => "sample_purchase.csv",
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
