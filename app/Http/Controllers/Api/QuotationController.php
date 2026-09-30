<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Http\Requests\StoreQuotationRequest;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Quotation;
use App\Models\Delivery;
use App\Models\PosSetting;
use App\Models\ProductQuotation;
use App\Models\Product_Warehouse;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Variant;
use DB;
use NumberToWords\NumberToWords;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\QuotationDetails;
use Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\MailSetting;
use App\Traits\MailInfo;
use App\Traits\StaffAccess;
use App\Traits\TenantInfo;
use App\Traits\APIPaginationTrait;

class QuotationController extends Controller
{
    use TenantInfo, MailInfo, StaffAccess, APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the quotations module
            if (!$role->hasPermissionTo('quotes-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Quotation::with(['biller', 'customer', 'supplier', 'user', 'warehouse']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $quotations = $this->resolveCollection($query->orderBy('created_at', 'desc'), $request);
            $pagination = $this->resolvePagination($query->orderBy('created_at', 'desc'), $request);
            // Format quotations for list schema
            $quotationsTable = $quotations->map(function ($quotation) {
                // Quotation Status HTML
                $statusHtml = '';
                if ($quotation->quotation_status == 1) {
                    $statusHtml = "<div class='badge badge-success'>Sent</div>";
                } elseif ($quotation->quotation_status == 2) {
                    $statusHtml = "<div class='badge badge-primary'>Approved</div>";
                } elseif ($quotation->quotation_status == 3) {
                    $statusHtml = "<div class='badge badge-info'>Converted</div>";
                } else {
                    $statusHtml = "<div class='badge badge-warning'>Pending</div>";
                }

                return [
                    'id' => $quotation->id,
                    'date' => date("d-m-Y", strtotime($quotation->created_at)),
                    'reference_no' => $quotation->reference_no,
                    'warehouse' => $quotation->warehouse->name ?? 'N/A',
                    'biller' => $quotation->biller->name ?? 'N/A',
                    'customer' => $quotation->customer->name ?? 'N/A',
                    'supplier' => $quotation->supplier->name ?? 'N/A',
                    'quotation_status' => $statusHtml,
                    'quotation_status_value' => $quotation->quotation_status,
                    'grand_total' => number_format($quotation->grand_total ?? $quotation->total_cost, config('decimal')),
                ];
            });

            return $this->withDashBackground([
                'title' => "Quotations",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Quotation',
                'add_url' => '/quotations/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Biller', 'field' => 'biller', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Supplier', 'field' => 'supplier', 'type' => 'text'],
                    ['label' => 'Quotation Status', 'field' => 'quotation_status', 'type' => 'html'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'field' => 'manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/quotations/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/quotations/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $quotationsTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quotation data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add quotations
            if (!$role->hasPermissionTo('quotes-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get customers and suppliers
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id
                ];
            })->toArray();

            $suppliers = Supplier::where('is_active', true)->get();
            $supplierOptions = $suppliers->map(function ($supplier) {
                return [
                    'label' => $supplier->name,
                    'value' => $supplier->id
                ];
            })->toArray();

            // Get warehouses and billers
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $billers = Biller::where('is_active', true)->get();
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
                "title" => "Create Quotation",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/quotations",
                "method" => "POST",
                "navigate_url" => "/quotations",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Quotation Information",
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
                                "generator_url" => "/generate/quotation-reference",
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
                                "name" => "supplier_id",
                                "label" => "Supplier",
                                "placeholder" => "Select supplier",
                                "options" => $supplierOptions,
                                "new_screen" => "/suppliers/create",
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
                                        "name" => "batch_no",
                                        "label" => "Batch No",
                                        "type" => "text",
                                        "editable" => true,
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
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter quotation note",
                            ],
                            [
                                "type" => "hidden",
                                "name" => "quotation_status",
                                "value" => 1,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the quotation form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreQuotationRequest $request)
    {
        try {
            $data = $request->except('document', 'token');
            $data['user_id'] = Auth::id();

            // Handle document upload
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/quotation'), $documentName);
                $data['document'] = $documentName;
            }

            // Generate reference number
            $data['reference_no'] = 'qr-' . date("Ymd") . '-' . date("his");

            // Calculate totals from products
            $products = $request->products;
            $data['item'] = count($products);
            $data['total_qty'] = 0;
            $data['total_price'] = 0;
            $data['total_discount'] = 0;
            $data['total_tax'] = 0;

            foreach ($products as $product) {
                $data['total_qty'] += $product['qty'];
                $data['total_price'] += $product['subtotal'];
                $data['total_discount'] += $product['discount'] ?? 0;
                $data['total_tax'] += $product['tax'] ?? 0;
            }

            // Calculate grand total
            $data['order_tax'] = ($data['tax_id'] ?? 0) * $data['total_price'] / 100;
            $data['grand_total'] = $data['total_price'] + $data['order_tax'] + ($data['shipping_cost'] ?? 0) - ($data['discount'] ?? 0);

            // Create quotation
            $quotation = Quotation::create($data);

            // Process products from table_generator
            $products = $request->products;
            foreach ($products as $product) {
                $productQuotation = [
                    'quotation_id' => $quotation->id,
                    'product_id' => $product['id'],
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'qty' => $product['qty'],
                    'sale_unit_id' => $product['sale_unit_id'] ?? null,
                    'net_unit_price' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => $product['tax'] ?? 0,
                    'total' => $product['subtotal'],
                ];

                ProductQuotation::create($productQuotation);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation created successfully',
                'navigate_url' => '/quotations',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the quotation.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'trace' => env('APP_DEBUG', false) ? $e->getTraceAsString() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit quotations
            if (!$role->hasPermissionTo('quotes-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get the quotation with related data
            $quotation = Quotation::findOrFail($id);
            $productQuotations = ProductQuotation::where('quotation_id', $id)->get();

            // Get customers and suppliers
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id
                ];
            })->toArray();

            $suppliers = Supplier::where('is_active', true)->get();
            $supplierOptions = $suppliers->map(function ($supplier) {
                return [
                    'label' => $supplier->name,
                    'value' => $supplier->id
                ];
            })->toArray();

            // Get warehouses and billers
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $billers = Biller::where('is_active', true)->get();
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
            $existingProducts = $productQuotations->map(function ($pq) {
                $product = Product::find($pq->product_id);
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'qty' => $pq->qty,
                    'net_unit_price' => $pq->net_unit_price,
                    'discount' => $pq->discount,
                    'tax_rate' => $pq->tax_rate,
                    'subtotal' => $pq->total,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Edit Quotation",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/quotations/" . $id,
                "method" => "PUT",
                "navigate_url" => "/quotations",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Quotation Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => date('d F, Y', strtotime($quotation->created_at)),
                            ],
                            [
                                "type" => "text",
                                "name" => "reference_no",
                                "label" => "Reference No",
                                "placeholder" => "Enter reference number",
                                "value" => $quotation->reference_no,
                            ],
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Customer",
                                "placeholder" => "Select customer",
                                "options" => $customerOptions,
                                "new_screen" => "/customers/create",
                                "value" => $quotation->customer_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "supplier_id",
                                "label" => "Supplier",
                                "placeholder" => "Select supplier",
                                "options" => $supplierOptions,
                                "new_screen" => "/suppliers/create",
                                "value" => $quotation->supplier_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouseOptions,
                                "required" => true,
                                "value" => $quotation->warehouse_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "biller_id",
                                "label" => "Biller *",
                                "placeholder" => "Select biller",
                                "options" => $billerOptions,
                                "required" => true,
                                "value" => $quotation->biller_id,
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
                                        "name" => "net_unit_price",
                                        "label" => "Unit Price",
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
                                    ],
                                    [
                                        "name" => "tax_rate",
                                        "label" => "Tax %",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 2,
                                    ],
                                    [
                                        "name" => "subtotal",
                                        "label" => "Subtotal",
                                        "type" => "formula",
                                        "formula" => "qty * net_unit_price * (1 + tax_rate / 100) - discount",
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
                                "value" => $quotation->tax_id ?? 0,
                            ],
                            [
                                "type" => "text",
                                "name" => "discount",
                                "label" => "Order Discount",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => $quotation->discount ?? "0.00",
                            ],
                            [
                                "type" => "text",
                                "name" => "shipping_cost",
                                "label" => "Shipping Cost",
                                "placeholder" => "0.00",
                                "keyboard_type" => "number",
                                "value" => $quotation->shipping_cost ?? "0.00",
                            ],
                            [
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter note",
                                "value" => $quotation->note ?? "",
                            ],
                            [
                                "type" => "hidden",
                                "name" => "quotation_status",
                                "value" => $quotation->quotation_status,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the quotation for editing.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit quotations
            if (!$role->hasPermissionTo('quotes-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->except('document', 'products', 'token');
            $quotation = Quotation::findOrFail($id);

            // Handle document upload if present
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $v = Validator::make(
                    ['extension' => strtolower($document->getClientOriginalExtension())],
                    ['extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt']
                );

                if ($v->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid document format.',
                        'errors' => $v->errors(),
                        'debug_bar' => env('APP_DEBUG', false) ? true : false,
                    ], 422);
                }

                // Delete old document
                if ($quotation->document) {
                    $this->fileDelete(public_path('documents/quotation/'), $quotation->document);
                }

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/quotation'), $documentName);
                $data['document'] = $documentName;
            }

            // Get old product quotations
            $oldProductQuotations = ProductQuotation::where('quotation_id', $id)->get();
            $oldProductIds = $oldProductQuotations->pluck('product_id')->toArray();
            $oldVariantIds = $oldProductQuotations->pluck('variant_id')->toArray();

            // Delete old product quotations that are not in the new list
            $products = $request->products ?? [];
            $newProductIds = array_column($products, 'id');

            foreach ($oldProductQuotations as $oldPQ) {
                if ($oldPQ->variant_id) {
                    // Check if variant still exists in new list
                    $found = false;
                    foreach ($products as $product) {
                        if (
                            $product['id'] == $oldPQ->product_id &&
                            ($product['variant_id'] ?? null) == $oldPQ->variant_id
                        ) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $oldPQ->delete();
                    }
                } else {
                    // Product without variant
                    if (!in_array($oldPQ->product_id, $newProductIds)) {
                        $oldPQ->delete();
                    }
                }
            }

            // Process products from table_generator
            foreach ($products as $product) {
                $productQuotationData = [
                    'quotation_id' => $id,
                    'product_id' => $product['id'],
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'qty' => $product['qty'],
                    'sale_unit_id' => $product['sale_unit_id'] ?? null,
                    'net_unit_price' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => ($product['qty'] * $product['net_unit_price'] * ($product['tax_rate'] ?? 0)) / 100,
                    'total' => $product['subtotal'],
                ];

                // Check if this product already exists
                if ($product['variant_id'] ?? null) {
                    $existing = ProductQuotation::where([
                        ['product_id', $product['id']],
                        ['variant_id', $product['variant_id']],
                        ['quotation_id', $id]
                    ])->first();
                } else {
                    $existing = ProductQuotation::where([
                        ['product_id', $product['id']],
                        ['quotation_id', $id]
                    ])->whereNull('variant_id')->first();
                }

                if ($existing) {
                    $existing->update($productQuotationData);
                } else {
                    ProductQuotation::create($productQuotationData);
                }
            }

            // Update quotation
            $quotation->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Quotation updated successfully',
                'navigate_url' => '/quotations',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the quotation.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'trace' => env('APP_DEBUG', false) ? $e->getTraceAsString() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete quotations
            if (!$role->hasPermissionTo('quotes-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $quotation = Quotation::findOrFail($id);

            // Delete associated product quotations
            ProductQuotation::where('quotation_id', $id)->delete();

            // Delete document if exists
            if ($quotation->document) {
                $this->fileDelete(public_path('documents/quotation/'), $quotation->document);
            }

            // Delete quotation
            $quotation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Quotation deleted successfully',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the quotation.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }
}
