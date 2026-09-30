<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ReturnSaleResource;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Tax;
use App\Models\Product_Warehouse;
use App\Models\ProductBatch;
use DB;
use App\Models\Returns;
use App\Models\Account;
use App\Models\ProductReturn;
use App\Models\ProductVariant;
use App\Models\Variant;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\ReturnDetails;
use Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\MailSetting;
use App\Traits\MailInfo;
use App\Traits\StaffAccess;
use App\Traits\TenantInfo;
use App\Traits\APIPaginationTrait;

class ReturnSaleController extends Controller
{
    use TenantInfo, MailInfo, StaffAccess, APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('returns-index')) {
            $permissions = $role->permissions;
            $all_permission = [];
            foreach ($permissions as $permission) {
                $all_permission[] = $permission->name;
            }
            if (empty($all_permission)) {
                $all_permission[] = 'dummy text';
            }

            $warehouse_id = $request->input('warehouse_id', 0);

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime('-1 year'));
                $ending_date = date("Y-m-d");
            }

            $query = Returns::with(['biller', 'customer', 'warehouse', 'user', 'sale'])
                ->where('is_active', 1)
                ->when($warehouse_id, function ($query, $warehouse_id) {
                    return $query->where('warehouse_id', $warehouse_id);
                })
                ->when($starting_date, function ($query, $starting_date) {
                    return $query->whereDate('created_at', '>=', $starting_date);
                })
                ->when($ending_date, function ($query, $ending_date) {
                    return $query->whereDate('created_at', '<=', $ending_date);
                })
                ->orderBy('created_at', 'desc');
            $returns = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            $rows = $returns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'date' => $return->created_at ? $return->created_at->format('d M, Y') : '',
                    'reference_no' => $return->reference_no,
                    'sale_reference' => $return->sale ? $return->sale->reference_no : 'N/A',
                    'warehouse_name' => $return->warehouse ? $return->warehouse->name : '',
                    'biller_name' => $return->biller ? $return->biller->name : '',
                    'customer_name' => $return->customer ? $return->customer->name : '',
                    'grand_total' => number_format($return->grand_total, config('decimal')),
                ];
            });

            return $this->withDashBackground([
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'title' => 'Return Sales',
                'row_height' => 5,
                'add_text' => 'Add Return Sale',
                'add_url' => '/returns/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Sale Reference', 'field' => 'sale_reference', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse_name', 'type' => 'text'],
                    ['label' => 'Biller', 'field' => 'biller_name', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer_name', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/returns/' . '{id}' . '/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/returns/' . '{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $rows,
                'pagination' => $pagination
            ], 'app');
        } else
            return [
                'success' => false,
                'message' => 'You are not authorized to access this page.'
            ];
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('returns-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $return = Returns::findOrFail($id);
            $productReturns = ProductReturn::where('return_id', $id)->get();

            $customers = Customer::where('is_active', 1)->get()->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id,
                ];
            })->toArray();

            $warehouses = Warehouse::where('is_active', 1)->get()->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            $billers = Biller::where('is_active', 1)->get()->map(function ($biller) {
                return [
                    'label' => $biller->name,
                    'value' => $biller->id,
                ];
            })->toArray();

            $accounts = Account::where('is_active', 1)->get()->map(function ($account) {
                return [
                    'label' => $account->name,
                    'value' => $account->id,
                ];
            })->toArray();

            // Prepare existing products for table generator
            $existingProducts = $productReturns->map(function ($pr) {
                $product = Product::find($pr->product_id);
                return [
                    'product_id' => $product->id,
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'imei_number' => $pr->imei_number ?? '',
                    'batch_no' => $pr->product_batch_id ?? '',
                    'qty' => $pr->qty,
                    'sale_unit_id' => $pr->sale_unit_id,
                    'net_unit_price' => $pr->net_unit_price,
                    'discount' => $pr->discount,
                    'tax_rate' => $pr->tax_rate,
                    'tax' => $pr->tax,
                    'subtotal' => $pr->total,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Edit Return Sale',
                'submit_url' => '/returns/' . $id,
                'navigate_url' => '/returns',
                'method' => 'PUT',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Return Information',
                        'items' => [
                            [
                                'type' => 'datepicker',
                                'name' => 'date',
                                'label' => 'Date',
                                'placeholder' => 'Select date',
                                'format_specifier' => 'dd MMMM, yyyy',
                                'value' => date('d F, Y', strtotime($return->created_at)),
                            ],
                            [
                                'type' => 'text',
                                'name' => 'reference_no',
                                'label' => 'Reference No',
                                'placeholder' => 'Enter reference number',
                                'value' => $return->reference_no,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'warehouse_id',
                                'label' => 'Warehouse *',
                                'placeholder' => 'Select warehouse',
                                'options' => $warehouses,
                                'required' => true,
                                'value' => $return->warehouse_id,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'biller_id',
                                'label' => 'Biller *',
                                'placeholder' => 'Select biller',
                                'options' => $billers,
                                'required' => true,
                                'new_screen' => '/billers/create',
                                'value' => $return->biller_id,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'customer_id',
                                'label' => 'Customer *',
                                'placeholder' => 'Select customer',
                                'options' => $customers,
                                'required' => true,
                                'new_screen' => '/customers/create',
                                'value' => $return->customer_id,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'account_id',
                                'label' => 'Account',
                                'placeholder' => 'Select account',
                                'options' => $accounts,
                                'value' => $return->account_id,
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Product Information',
                        'items' => [
                            [
                                'type' => 'table_generator',
                                'name' => 'products',
                                'label' => 'Select Products to Return',
                                'search_url' => '/products/search',
                                'search_placeholder' => 'Search products by name, code or scan barcode',
                                'info' => 'Use barcode scanner or type product code/name to add products',
                                'show_info_icon' => true,
                                'value' => $existingProducts,
                                'duplicate_handling' => [
                                    'strategy' => 'update_quantity',
                                    'identifier_field' => 'id',
                                    'update_fields' => ['qty'],
                                    'error_message' => 'This product is already added to the table',
                                ],
                                'columns' => [
                                    [
                                        'name' => 'name',
                                        'label' => 'Product Name',
                                        'type' => 'text',
                                        'editable' => false,
                                        'width' => 200,
                                    ],
                                    [
                                        'name' => 'code',
                                        'label' => 'Code',
                                        'type' => 'text',
                                        'editable' => false,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'imei_number',
                                        'label' => 'IMEI/Serial',
                                        'type' => 'text',
                                        'editable' => true,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'batch_no',
                                        'label' => 'Batch No',
                                        'type' => 'text',
                                        'editable' => true,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'qty',
                                        'label' => 'Quantity',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 0,
                                        'default' => 1,
                                    ],
                                    [
                                        'name' => 'net_unit_price',
                                        'label' => 'Unit Price',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 120,
                                        'decimal_places' => 2,
                                    ],
                                    [
                                        'name' => 'discount',
                                        'label' => 'Discount',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 2,
                                        'default' => 0,
                                    ],
                                    [
                                        'name' => 'tax_rate',
                                        'label' => 'Tax %',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 2,
                                        'default' => 0,
                                    ],
                                    [
                                        'name' => 'subtotal',
                                        'label' => 'Subtotal',
                                        'type' => 'formula',
                                        'formula' => 'qty * net_unit_price * (1 + tax_rate / 100) - discount',
                                        'width' => 120,
                                        'decimal_places' => 2,
                                    ],
                                ],
                                'formula_engine' => [
                                    'enabled' => true,
                                    'auto_calculate' => true,
                                ],
                                'totals' => [
                                    [
                                        'label' => 'Total Items',
                                        'formula' => 'COUNT(*)',
                                        'position' => 'left',
                                    ],
                                    [
                                        'label' => 'Total Quantity',
                                        'formula' => 'SUM(qty)',
                                        'position' => 'left',
                                    ],
                                    [
                                        'label' => 'Grand Total',
                                        'formula' => 'SUM(subtotal)',
                                        'position' => 'right',
                                        'prefix' => config('currency'),
                                        'decimal_places' => 2,
                                    ],
                                ],
                                'validation' => [
                                    'min_rows' => 1,
                                    'error_message' => 'Please add at least one product',
                                ],
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Additional Information',
                        'items' => [
                            [
                                'type' => 'text',
                                'name' => 'staff_note',
                                'label' => 'Staff Note',
                                'placeholder' => 'Enter staff note',
                                'multiline' => true,
                                'value' => $return->staff_note,
                            ],
                            [
                                'type' => 'file',
                                'name' => 'document',
                                'label' => 'Attach Document',
                                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'docx', 'xlsx', 'txt'],
                                'multiple' => false,
                            ],
                        ]
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the return sale edit form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('returns-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $customers = Customer::where('is_active', 1)->get()->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id,
                ];
            })->toArray();

            $warehouses = Warehouse::where('is_active', 1)->get()->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            $billers = Biller::where('is_active', 1)->get()->map(function ($biller) {
                return [
                    'label' => $biller->name,
                    'value' => $biller->id,
                ];
            })->toArray();

            $accounts = Account::where('is_active', 1)->get()->map(function ($account) {
                return [
                    'label' => $account->name,
                    'value' => $account->id,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Add Return Sale',
                'submit_url' => '/return-sale',
                'navigate_url' => '/return-sale',
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'group',
                        'label' => 'Return Information',
                        'items' => [
                            [
                                'type' => 'datepicker',
                                'name' => 'date',
                                'label' => 'Date',
                                'placeholder' => 'Select date',
                                'format_specifier' => 'dd MMMM, yyyy',
                                'value' => now()->format('d F, Y'),
                            ],
                            [
                                'type' => 'datagenerator',
                                'name' => 'reference_no',
                                'label' => 'Reference No',
                                'placeholder' => 'Enter reference number',
                                'generator_url' => '/generate/return-reference',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'warehouse_id',
                                'label' => 'Warehouse *',
                                'placeholder' => 'Select warehouse',
                                'options' => $warehouses,
                                'required' => true,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'biller_id',
                                'label' => 'Biller *',
                                'placeholder' => 'Select biller',
                                'options' => $billers,
                                'required' => true,
                                'new_screen' => '/billers/create',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'customer_id',
                                'label' => 'Customer *',
                                'placeholder' => 'Select customer',
                                'options' => $customers,
                                'required' => true,
                                'new_screen' => '/customers/create',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'account_id',
                                'label' => 'Account',
                                'placeholder' => 'Select account',
                                'options' => $accounts,
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Product Information',
                        'items' => [
                            [
                                'type' => 'table_generator',
                                'name' => 'products',
                                'label' => 'Select Products to Return',
                                'search_url' => '/products/search',
                                'search_placeholder' => 'Search products by name, code or scan barcode',
                                'info' => 'Use barcode scanner or type product code/name to add products',
                                'show_info_icon' => true,
                                'duplicate_handling' => [
                                    'strategy' => 'update_quantity',
                                    'identifier_field' => 'id',
                                    'update_fields' => ['qty'],
                                    'error_message' => 'This product is already added to the table',
                                ],
                                'columns' => [
                                    [
                                        'name' => 'name',
                                        'label' => 'Product Name',
                                        'type' => 'text',
                                        'editable' => false,
                                        'width' => 200,
                                    ],
                                    [
                                        'name' => 'code',
                                        'label' => 'Code',
                                        'type' => 'text',
                                        'editable' => false,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'imei_number',
                                        'label' => 'IMEI/Serial',
                                        'type' => 'text',
                                        'editable' => true,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'batch_no',
                                        'label' => 'Batch No',
                                        'type' => 'text',
                                        'editable' => true,
                                        'width' => 120,
                                    ],
                                    [
                                        'name' => 'qty',
                                        'label' => 'Quantity',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 0,
                                        'default' => 1,
                                    ],
                                    [
                                        'name' => 'net_unit_price',
                                        'label' => 'Unit Price',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 120,
                                        'decimal_places' => 2,
                                    ],
                                    [
                                        'name' => 'discount',
                                        'label' => 'Discount',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 2,
                                        'default' => 0,
                                    ],
                                    [
                                        'name' => 'tax_rate',
                                        'label' => 'Tax %',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 2,
                                        'default' => 0,
                                    ],
                                    [
                                        'name' => 'subtotal',
                                        'label' => 'Subtotal',
                                        'type' => 'formula',
                                        'formula' => 'qty * net_unit_price * (1 + tax_rate / 100) - discount',
                                        'width' => 120,
                                        'decimal_places' => 2,
                                    ],
                                ],
                                'formula_engine' => [
                                    'enabled' => true,
                                    'auto_calculate' => true,
                                ],
                                'totals' => [
                                    [
                                        'label' => 'Total Items',
                                        'formula' => 'COUNT(*)',
                                        'position' => 'left',
                                    ],
                                    [
                                        'label' => 'Total Quantity',
                                        'formula' => 'SUM(qty)',
                                        'position' => 'left',
                                    ],
                                    [
                                        'label' => 'Grand Total',
                                        'formula' => 'SUM(subtotal)',
                                        'position' => 'right',
                                        'prefix' => config('currency'),
                                        'decimal_places' => 2,
                                    ],
                                ],
                                'validation' => [
                                    'min_rows' => 1,
                                    'error_message' => 'Please add at least one product',
                                ],
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Additional Information',
                        'items' => [
                            [
                                'type' => 'text',
                                'name' => 'staff_note',
                                'label' => 'Staff Note',
                                'placeholder' => 'Enter staff note',
                                'multiline' => true,
                            ],
                            [
                                'type' => 'file',
                                'name' => 'document',
                                'label' => 'Attach Document',
                                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'docx', 'xlsx', 'txt'],
                                'multiple' => false,
                            ],
                        ]
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the return sale form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('returns-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->all();
            $data['user_id'] = $user->id;

            // Generate reference number if not provided
            if (empty($data['reference_no'])) {
                $data['reference_no'] = 'rr-' . date("Ymd") . '-' . date("his");
            }

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
                $document->move(public_path('documents/sale_return'), $documentName);
                $data['document'] = $documentName;
            }

            // Calculate totals from products
            $products = $data['products'] ?? [];
            $total_qty = 0;
            $total_discount = 0;
            $total_tax = 0;
            $total_price = 0;
            $grand_total = 0;

            foreach ($products as $product) {
                $total_qty += $product['qty'];
                $total_discount += $product['discount'] ?? 0;
                $total_tax += ($product['qty'] * $product['net_unit_price'] * ($product['tax_rate'] ?? 0) / 100);
                $subtotal = $product['qty'] * $product['net_unit_price'] * (1 + ($product['tax_rate'] ?? 0) / 100) - ($product['discount'] ?? 0);
                $grand_total += $subtotal;
            }

            $data['total_qty'] = $total_qty;
            $data['total_discount'] = $total_discount;
            $data['total_tax'] = $total_tax;
            $data['total_price'] = $grand_total - $total_tax;
            $data['grand_total'] = $grand_total;
            $data['item'] = count($products);

            // Create return record
            $return = Returns::create($data);

            // Process each product
            foreach ($products as $product) {
                $product_id = $product['product_id'] ?? $product['id'];
                $product_data = Product::find($product_id);

                if (!$product_data) continue;

                $qty = $product['qty'];
                $sale_unit_id = $product['sale_unit_id'] ?? 0;

                // Calculate actual quantity based on unit
                $quantity = $qty;
                if ($sale_unit_id) {
                    $unit_data = Unit::find($sale_unit_id);
                    if ($unit_data) {
                        if ($unit_data->operator == '*') {
                            $quantity = $qty * $unit_data->operation_value;
                        } elseif ($unit_data->operator == '/') {
                            $quantity = $qty / $unit_data->operation_value;
                        }
                    }
                }

                // Update product warehouse quantity
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $product_id],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();

                if ($product_warehouse) {
                    $product_warehouse->qty += $quantity;
                    $product_warehouse->save();
                }

                // Update product quantity
                $product_data->qty += $quantity;
                $product_data->save();

                // Create product return record
                ProductReturn::create([
                    'return_id' => $return->id,
                    'product_id' => $product_id,
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'imei_number' => $product['imei_number'] ?? null,
                    'qty' => $qty,
                    'sale_unit_id' => $sale_unit_id,
                    'net_unit_price' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => ($qty * $product['net_unit_price'] * ($product['tax_rate'] ?? 0) / 100),
                    'total' => $qty * $product['net_unit_price'] * (1 + ($product['tax_rate'] ?? 0) / 100) - ($product['discount'] ?? 0),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Return sale created successfully.',
                'navigate_url' => '/return-sale',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the return sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('returns-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $return = Returns::findOrFail($id);
            $data = $request->all();

            // Handle document upload
            if ($request->hasFile('document')) {
                // Delete old document if exists
                if ($return->document) {
                    $this->fileDelete(public_path('documents/sale_return/'), $return->document);
                }

                $document = $request->file('document');
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/sale_return'), $documentName);
                $data['document'] = $documentName;
            }

            // Get old product returns to reverse inventory
            $old_product_returns = ProductReturn::where('return_id', $id)->get();

            foreach ($old_product_returns as $old_return) {
                $product_data = Product::find($old_return->product_id);
                if (!$product_data) continue;

                $qty = $old_return->qty;
                $sale_unit_id = $old_return->sale_unit_id;

                // Calculate actual quantity
                $quantity = $qty;
                if ($sale_unit_id) {
                    $unit_data = Unit::find($sale_unit_id);
                    if ($unit_data) {
                        if ($unit_data->operator == '*') {
                            $quantity = $qty * $unit_data->operation_value;
                        } elseif ($unit_data->operator == '/') {
                            $quantity = $qty / $unit_data->operation_value;
                        }
                    }
                }

                // Reverse the warehouse quantity
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $old_return->product_id],
                    ['warehouse_id', $return->warehouse_id]
                ])->first();

                if ($product_warehouse) {
                    $product_warehouse->qty -= $quantity;
                    $product_warehouse->save();
                }

                // Reverse product quantity
                $product_data->qty -= $quantity;
                $product_data->save();
            }

            // Delete old product returns
            ProductReturn::where('return_id', $id)->delete();

            // Calculate new totals
            $products = $data['products'] ?? [];
            $total_qty = 0;
            $total_discount = 0;
            $total_tax = 0;
            $total_price = 0;
            $grand_total = 0;

            foreach ($products as $product) {
                $total_qty += $product['qty'];
                $total_discount += $product['discount'] ?? 0;
                $total_tax += ($product['qty'] * $product['net_unit_price'] * ($product['tax_rate'] ?? 0) / 100);
                $subtotal = $product['qty'] * $product['net_unit_price'] * (1 + ($product['tax_rate'] ?? 0) / 100) - ($product['discount'] ?? 0);
                $grand_total += $subtotal;
            }

            $data['total_qty'] = $total_qty;
            $data['total_discount'] = $total_discount;
            $data['total_tax'] = $total_tax;
            $data['total_price'] = $grand_total - $total_tax;
            $data['grand_total'] = $grand_total;
            $data['item'] = count($products);

            // Update return record
            $return->update($data);

            // Process new products
            foreach ($products as $product) {
                $product_id = $product['product_id'] ?? $product['id'];
                $product_data = Product::find($product_id);

                if (!$product_data) continue;

                $qty = $product['qty'];
                $sale_unit_id = $product['sale_unit_id'] ?? 0;

                // Calculate actual quantity
                $quantity = $qty;
                if ($sale_unit_id) {
                    $unit_data = Unit::find($sale_unit_id);
                    if ($unit_data) {
                        if ($unit_data->operator == '*') {
                            $quantity = $qty * $unit_data->operation_value;
                        } elseif ($unit_data->operator == '/') {
                            $quantity = $qty / $unit_data->operation_value;
                        }
                    }
                }

                // Update warehouse quantity
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $product_id],
                    ['warehouse_id', $data['warehouse_id']]
                ])->first();

                if ($product_warehouse) {
                    $product_warehouse->qty += $quantity;
                    $product_warehouse->save();
                }

                // Update product quantity
                $product_data->qty += $quantity;
                $product_data->save();

                // Create new product return record
                ProductReturn::create([
                    'return_id' => $return->id,
                    'product_id' => $product_id,
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'imei_number' => $product['imei_number'] ?? null,
                    'qty' => $qty,
                    'sale_unit_id' => $sale_unit_id,
                    'net_unit_price' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => ($qty * $product['net_unit_price'] * ($product['tax_rate'] ?? 0) / 100),
                    'total' => $qty * $product['net_unit_price'] * (1 + ($product['tax_rate'] ?? 0) / 100) - ($product['discount'] ?? 0),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Return sale updated successfully.',
                'navigate_url' => '/return-sale',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the return sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('returns-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $return = Returns::findOrFail($id);

            // Get product returns to reverse inventory
            $product_returns = ProductReturn::where('return_id', $id)->get();

            foreach ($product_returns as $product_return) {
                $product_data = Product::find($product_return->product_id);
                if (!$product_data) continue;

                $qty = $product_return->qty;
                $sale_unit_id = $product_return->sale_unit_id;

                // Calculate actual quantity
                $quantity = $qty;
                if ($sale_unit_id) {
                    $unit_data = Unit::find($sale_unit_id);
                    if ($unit_data) {
                        if ($unit_data->operator == '*') {
                            $quantity = $qty * $unit_data->operation_value;
                        } elseif ($unit_data->operator == '/') {
                            $quantity = $qty / $unit_data->operation_value;
                        }
                    }
                }

                // Reverse warehouse quantity
                $product_warehouse = Product_Warehouse::where([
                    ['product_id', $product_return->product_id],
                    ['warehouse_id', $return->warehouse_id]
                ])->first();

                if ($product_warehouse) {
                    $product_warehouse->qty -= $quantity;
                    $product_warehouse->save();
                }

                // Reverse product quantity
                $product_data->qty -= $quantity;
                $product_data->save();
            }

            // Delete product returns
            ProductReturn::where('return_id', $id)->delete();

            // Delete document if exists
            if ($return->document) {
                $this->fileDelete(public_path('documents/sale_return/'), $return->document);
            }

            // Soft delete return
            $return->is_active = false;
            $return->save();

            return response()->json([
                'success' => true,
                'message' => 'Return sale deleted successfully.',
                'navigate_url' => '/return-sale',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the return sale.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    protected function fileDelete($path, $filename)
    {
        $file_path = $path . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    public function generateReference()
    {
        try {
            $reference = 'rr-' . date("Ymd") . '-' . date("his");

            return response()->json([
                'success' => true,
                'reference' => $reference,
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating reference.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }
}
