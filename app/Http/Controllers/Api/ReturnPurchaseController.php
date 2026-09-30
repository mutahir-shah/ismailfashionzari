<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreReturnPurchaseRequest;
use App\Models\ReturnPurchase;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\PurchaseProductReturn;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Traits\APIPaginationTrait;
use App\Traits\TenantInfo;

class ReturnPurchaseController extends Controller
{
    use APIPaginationTrait, TenantInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('return-purchase')) {
                return (new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ]))->response()->setStatusCode(403);
            }

            $search = $request->input('search', '');
            $query = ReturnPurchase::with(['purchase', 'supplier', 'warehouse'])
                ->where('is_active', 1);

            if (!empty($search)) {
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            }

            $returns = $this->resolveCollection($query->orderBy('id', 'desc'), $request);
            $pagination = $this->resolvePagination($query->orderBy('id', 'desc'), $request);

            $rows = $returns->map(function ($return) {
                return [
                    'id' => $return->id,
                    'date' => $return->date,
                    'reference_no' => $return->reference_no,
                    'purchase_reference' => optional($return->purchase)->reference_no,
                    'warehouse' => optional($return->warehouse)->name,
                    'supplier' => optional($return->supplier)->name,
                    'grand_total' => number_format($return->grand_total, config('decimal')),
                ];
            });

            return $this->withDashBackground([
                'title' => 'Return Purchases',
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Return Purchase',
                'add_url' => '/return-purchases/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Purchase Reference', 'field' => 'purchase_reference', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Supplier', 'field' => 'supplier', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Manage', 'type' => 'row', 'children' => [
                        [
                            'type' => 'action',
                            'icon' => 'edit',
                            'action' => [
                                'api_url' => '/return-purchases/{id}/edit',
                                'type' => 'form'
                            ]
                        ],
                        [
                            'type' => 'action',
                            'icon' => 'delete',
                            'action' => [
                                'api_url' => '/return-purchases/{id}',
                                'type' => 'delete'
                            ]
                        ],
                    ]],
                ],
                'rows' => $rows,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return (new ErrorResource([
                'message' => 'An error occurred while retrieving return purchases.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ]))->response()->setStatusCode(500);
        }
    }

    public function create()
    {
        try {
            $suppliers = Supplier::where('is_active', 1)->get()->map(function ($supplier) {
                return [
                    'label' => $supplier->name,
                    'value' => $supplier->id,
                ];
            })->toArray();

            $warehouses = Warehouse::where('is_active', 1)->get()->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Add Return Purchase',
                'submit_url' => '/return-purchase',
                'navigate_url' => '/return-purchase',
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
                                'generator_url' => '/generate/return-purchase-reference',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'supplier_id',
                                'label' => 'Supplier *',
                                'placeholder' => 'Select supplier',
                                'options' => $suppliers,
                                'required' => true,
                                'new_screen' => '/suppliers/create',
                            ],
                            [
                                'type' => 'select',
                                'name' => 'warehouse_id',
                                'label' => 'Warehouse *',
                                'placeholder' => 'Select warehouse',
                                'options' => $warehouses,
                                'required' => true,
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
                                'style' => [
                                    'header_background' => '#f8f9fa',
                                    'header_text_color' => '#212529',
                                    'row_background' => '#ffffff',
                                    'alternate_row_background' => '#f8f9fa',
                                    'border_color' => '#dee2e6',
                                    'input_border_color' => '#ced4da',
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
                                    ],
                                    [
                                        'name' => 'cost',
                                        'label' => 'Unit Cost',
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
                                        'name' => 'tax',
                                        'label' => 'Tax %',
                                        'type' => 'number',
                                        'editable' => true,
                                        'width' => 100,
                                        'decimal_places' => 2,
                                    ],
                                    [
                                        'name' => 'subtotal',
                                        'label' => 'Subtotal',
                                        'type' => 'formula',
                                        'formula' => '(qty * cost * (1 + tax / 100)) - discount',
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
                                        'label' => 'Total Discount',
                                        'formula' => 'SUM(discount)',
                                        'position' => 'left',
                                        'prefix' => config('currency'),
                                        'decimal_places' => 2,
                                    ],
                                    [
                                        'label' => 'Total Return',
                                        'formula' => 'SUM(subtotal)',
                                        'position' => 'right',
                                        'prefix' => config('currency'),
                                    ],
                                ],
                                'totals_style' => [
                                    'background' => '#e9ecef',
                                    'text_color' => '#212529',
                                    'font_weight' => 'bold',
                                ],
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Additional Information',
                        'items' => [
                            [
                                'type' => 'editor',
                                'name' => 'note',
                                'label' => 'Note',
                                'placeholder' => 'Enter note',
                            ],
                            [
                                'type' => 'hidden',
                                'name' => 'is_active',
                                'value' => 1,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return (new ErrorResource([
                'message' => 'An error occurred while loading the form.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ]))->response()->setStatusCode(500);
        }
    }

    public function store(StoreReturnPurchaseRequest $request)
    {
        try {
            $data = $request->except('document', 'token');
            $data['user_id'] = Auth::id();
            $data['reference_no'] = 'prr-' . date("Ymd") . '-' . date("his");

            // Get default account or first active account
            $defaultAccount = \App\Models\Account::where('is_default', 1)->where('is_active', true)->first()
                ?? \App\Models\Account::where('is_active', true)->first();
            $data['account_id'] = $defaultAccount ? $defaultAccount->id : 1;

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
                $document->move(public_path('documents/purchase_return'), $documentName);
                $data['document'] = $documentName;
            }

            // Calculate totals from products
            $products = $request->products;
            $data['item'] = count($products);
            $data['total_qty'] = 0;
            $data['total_price'] = 0;
            $data['total_cost'] = 0;
            $data['total_discount'] = 0;
            $data['total_tax'] = 0;

            foreach ($products as $product) {
                $data['total_qty'] += $product['qty'];
                $data['total_price'] += $product['subtotal'];
                $data['total_cost'] += $product['net_unit_price'] * $product['qty'];
                $data['total_discount'] += $product['discount'] ?? 0;
                $data['total_tax'] += $product['tax'] ?? 0;
            }
            $data['grand_total'] = $data['total_price'] + $data['total_tax'] - $data['total_discount'];

            // Create return purchase
            $returnPurchase = ReturnPurchase::create($data);

            // Process products
            foreach ($products as $product) {
                $productReturn = [
                    'return_id' => $returnPurchase->id,
                    'product_id' => $product['product_id'],
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'imei_number' => $product['imei_number'] ?? null,
                    'qty' => $product['qty'],
                    'purchase_unit_id' => $product['purchase_unit_id'] ?? null,
                    'net_unit_cost' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => $product['tax'] ?? 0,
                    'total' => $product['subtotal'],
                ];

                PurchaseProductReturn::create($productReturn);
            }

            return response()->json([
                'success' => true,
                'message' => 'Return purchase created successfully',
                'navigate_url' => '/return-purchase',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the return purchase.',
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

            if (!$role->hasPermissionTo('purchase-return-edit')) {
                return (new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ]))->response()->setStatusCode(403);
            }

            $returnPurchase = ReturnPurchase::findOrFail($id);
            $productReturns = PurchaseProductReturn::where('return_id', $id)->get();

            $suppliers = Supplier::where('is_active', 1)->get()->map(function ($supplier) {
                return [
                    'label' => $supplier->name,
                    'value' => $supplier->id,
                ];
            })->toArray();

            $warehouses = Warehouse::where('is_active', 1)->get()->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
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
                    'batch_no' => $pr->product_batch_id ?? '',
                    'qty' => $pr->qty,
                    'net_unit_price' => $pr->net_unit_cost,
                    'discount' => $pr->discount,
                    'tax_rate' => $pr->tax_rate,
                    'subtotal' => $pr->total,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Edit Return Purchase',
                'submit_url' => '/return-purchases/' . $id,
                'navigate_url' => '/return-purchase',
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
                                'value' => date('d F, Y', strtotime($returnPurchase->date)),
                            ],
                            [
                                'type' => 'text',
                                'name' => 'reference_no',
                                'label' => 'Reference No',
                                'placeholder' => 'Enter reference number',
                                'value' => $returnPurchase->reference_no,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'supplier_id',
                                'label' => 'Supplier *',
                                'placeholder' => 'Select supplier',
                                'options' => $suppliers,
                                'required' => true,
                                'new_screen' => '/suppliers/create',
                                'value' => $returnPurchase->supplier_id,
                            ],
                            [
                                'type' => 'select',
                                'name' => 'warehouse_id',
                                'label' => 'Warehouse *',
                                'placeholder' => 'Select warehouse',
                                'options' => $warehouses,
                                'required' => true,
                                'value' => $returnPurchase->warehouse_id,
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
                                    ],
                                    [
                                        'name' => 'net_unit_price',
                                        'label' => 'Unit Cost',
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
                                    ],
                                    [
                                        'name' => 'subtotal',
                                        'label' => 'Subtotal',
                                        'type' => 'formula',
                                        'formula' => '(qty * net_unit_price * (1 + tax_rate / 100)) - discount',
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
                                        'label' => 'Total',
                                        'formula' => 'SUM(subtotal)',
                                        'position' => 'right',
                                        'prefix' => config('currency'),
                                    ],
                                ],
                            ],
                        ]
                    ],
                    [
                        'type' => 'group',
                        'label' => 'Additional Information',
                        'items' => [
                            [
                                'type' => 'editor',
                                'name' => 'note',
                                'label' => 'Note',
                                'placeholder' => 'Enter note',
                                'value' => $returnPurchase->note ?? '',
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return (new ErrorResource([
                'message' => 'An error occurred while loading the return purchase for editing.',
                'error' => $e->getMessage(),
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
            ]))->response()->setStatusCode(500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-return-edit')) {
                return (new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ]))->response()->setStatusCode(403);
            }

            $data = $request->except('document', 'products', 'token');
            $returnPurchase = ReturnPurchase::findOrFail($id);

            // Handle document upload similar to quotation
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $v = Validator::make(
                    ['extension' => strtolower($document->getClientOriginalExtension())],
                    ['extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt']
                );

                if ($v->fails()) {
                    return (new ErrorResource([
                        'message' => 'Invalid document format.',
                        'errors' => $v->errors(),
                        'debug_bar' => env("APP_DEBUG", false) ? true : false,
                    ]))->response()->setStatusCode(422);
                }

                // Delete old document
                if ($returnPurchase->document) {
                    @unlink(public_path('documents/purchase_return/' . $returnPurchase->document));
                }

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/purchase_return'), $documentName);
                $data['document'] = $documentName;
            }

            // Get old product returns and restore their inventory first
            $oldProductReturns = PurchaseProductReturn::where('return_id', $id)->get();

            foreach ($oldProductReturns as $oldPR) {
                $product = Product::find($oldPR->product_id);

                if ($oldPR->purchase_unit_id != 0) {
                    $unit = \App\Models\Unit::find($oldPR->purchase_unit_id);
                    $quantity = $unit->operator == '*'
                        ? $oldPR->qty * $unit->operation_value
                        : $oldPR->qty / $unit->operation_value;
                } else {
                    $quantity = $oldPR->qty;
                }

                // Restore inventory (we're removing the return temporarily)
                if ($oldPR->variant_id) {
                    $variant = \App\Models\ProductVariant::where([
                        ['product_id', $oldPR->product_id],
                        ['variant_id', $oldPR->variant_id]
                    ])->first();
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $oldPR->product_id],
                        ['variant_id', $oldPR->variant_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($variant) $variant->decrement('qty', $quantity);
                    if ($warehouse) $warehouse->decrement('qty', $quantity);
                } elseif ($oldPR->product_batch_id) {
                    $batch = \App\Models\ProductBatch::find($oldPR->product_batch_id);
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $oldPR->product_id],
                        ['product_batch_id', $oldPR->product_batch_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($batch) $batch->decrement('qty', $quantity);
                    if ($warehouse) $warehouse->decrement('qty', $quantity);
                } else {
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $oldPR->product_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($warehouse) $warehouse->decrement('qty', $quantity);
                }

                $product->decrement('qty', $quantity);
            }

            // Delete all old product returns
            PurchaseProductReturn::where('return_id', $id)->delete();

            // Process new products from table_generator
            $products = $request->products ?? [];
            foreach ($products as $product) {
                $productReturnData = [
                    'return_id' => $id,
                    'product_id' => $product['product_id'],
                    'product_batch_id' => $product['product_batch_id'] ?? null,
                    'variant_id' => $product['variant_id'] ?? null,
                    'imei_number' => $product['imei_number'] ?? null,
                    'qty' => $product['qty'],
                    'purchase_unit_id' => $product['purchase_unit_id'] ?? null,
                    'net_unit_cost' => $product['net_unit_price'],
                    'discount' => $product['discount'] ?? 0,
                    'tax_rate' => $product['tax_rate'] ?? 0,
                    'tax' => ($product['qty'] * $product['net_unit_price'] * ($product['tax_rate'] ?? 0)) / 100,
                    'total' => $product['subtotal'],
                ];

                PurchaseProductReturn::create($productReturnData);

                // Deduct from inventory (applying the new return)
                $productModel = Product::find($product['product_id']);
                $quantity = $product['qty']; // Simplified - real version needs unit conversion

                if ($product['variant_id'] ?? null) {
                    $variant = \App\Models\ProductVariant::where([
                        ['product_id', $product['product_id']],
                        ['variant_id', $product['variant_id']]
                    ])->first();
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $product['product_id']],
                        ['variant_id', $product['variant_id']],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    if ($variant) $variant->increment('qty', $quantity);
                    if ($warehouse) $warehouse->increment('qty', $quantity);
                } elseif ($product['product_batch_id'] ?? null) {
                    $batch = \App\Models\ProductBatch::find($product['product_batch_id']);
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $product['product_id']],
                        ['product_batch_id', $product['product_batch_id']],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    if ($batch) $batch->increment('qty', $quantity);
                    if ($warehouse) $warehouse->increment('qty', $quantity);
                } else {
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $product['product_id']],
                        ['warehouse_id', $data['warehouse_id']]
                    ])->first();
                    if ($warehouse) $warehouse->increment('qty', $quantity);
                }

                $productModel->increment('qty', $quantity);
            }

            // Update return purchase
            $returnPurchase->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Return purchase updated successfully',
                'navigate_url' => '/return-purchase',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return (new ErrorResource([
                'message' => 'An error occurred while updating the return purchase.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'trace' => env('APP_DEBUG', false) ? $e->getTraceAsString() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]))->response()->setStatusCode(500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-return-delete')) {
                return (new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                    'debug_bar' => env("APP_DEBUG", false) ? true : false,
                ]))->response()->setStatusCode(403);
            }

            $returnPurchase = ReturnPurchase::findOrFail($id);
            $productReturns = PurchaseProductReturn::where('return_id', $id)->get();

            // Restore inventory for all returned products
            foreach ($productReturns as $pr) {
                $product = Product::find($pr->product_id);

                if ($pr->purchase_unit_id != 0) {
                    $unit = \App\Models\Unit::find($pr->purchase_unit_id);
                    $quantity = $unit->operator == '*'
                        ? $pr->qty * $unit->operation_value
                        : $pr->qty / $unit->operation_value;
                } else {
                    $quantity = $pr->qty;
                }

                // Restore inventory (removing the return means adding back to inventory)
                if ($pr->variant_id) {
                    $variant = \App\Models\ProductVariant::where([
                        ['product_id', $pr->product_id],
                        ['variant_id', $pr->variant_id]
                    ])->first();
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $pr->product_id],
                        ['variant_id', $pr->variant_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($variant) $variant->decrement('qty', $quantity);
                    if ($warehouse) {
                        $warehouse->decrement('qty', $quantity);
                        // Restore IMEI if exists
                        if ($pr->imei_number) {
                            $warehouse->imei_number = $warehouse->imei_number
                                ? $warehouse->imei_number . ',' . $pr->imei_number
                                : $pr->imei_number;
                            $warehouse->save();
                        }
                    }
                } elseif ($pr->product_batch_id) {
                    $batch = \App\Models\ProductBatch::find($pr->product_batch_id);
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $pr->product_id],
                        ['product_batch_id', $pr->product_batch_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($batch) $batch->decrement('qty', $quantity);
                    if ($warehouse) $warehouse->decrement('qty', $quantity);
                } else {
                    $warehouse = \App\Models\Product_Warehouse::where([
                        ['product_id', $pr->product_id],
                        ['warehouse_id', $returnPurchase->warehouse_id]
                    ])->first();
                    if ($warehouse) {
                        $warehouse->decrement('qty', $quantity);
                        // Restore IMEI if exists
                        if ($pr->imei_number) {
                            $warehouse->imei_number = $warehouse->imei_number
                                ? $warehouse->imei_number . ',' . $pr->imei_number
                                : $pr->imei_number;
                            $warehouse->save();
                        }
                    }
                }

                $product->decrement('qty', $quantity);
                $pr->delete();
            }

            // Delete document if exists
            if ($returnPurchase->document) {
                @unlink(public_path('documents/purchase_return/' . $returnPurchase->document));
            }

            // Delete return purchase
            $returnPurchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Return purchase deleted successfully',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return (new ErrorResource([
                'message' => 'An error occurred while deleting the return purchase.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]))->response()->setStatusCode(500);
        }
    }
}
