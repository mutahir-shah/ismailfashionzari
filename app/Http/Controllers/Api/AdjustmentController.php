<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Resources\AdjustmentResource;
use App\Http\Resources\ErrorResource;
use App\Models\Warehouse;
use App\Models\Product_Warehouse;
use App\Models\Product;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\StockCount;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class AdjustmentController extends Controller
{
    use \App\Traits\TenantInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Adjustment::with(['warehouse']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $adjustments = $query->orderBy('id', 'desc')->get();

            // Format adjustments for datatable
            $adjustmentsTable = $adjustments->map(function ($adjustment) {
                // Get product adjustment details
                $productDetails = [];
                $product_adjustments = DB::table('product_adjustments')
                    ->where('adjustment_id', $adjustment->id)
                    ->get();

                foreach ($product_adjustments as $product_adjustment) {
                    if ($product_adjustment->variant_id) {
                        $product = DB::table('products')
                            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                            ->select('products.name', 'product_variants.item_code as code')
                            ->where([
                                ['product_id', $product_adjustment->product_id],
                                ['variant_id', $product_adjustment->variant_id]
                            ])->first();
                    } else {
                        $product = DB::table('products')
                            ->select('name', 'code')
                            ->find($product_adjustment->product_id);
                    }

                    if ($product) {
                        $productDetails[] = $product->name . '<br>' . $product_adjustment->qty . ' x ' . $product_adjustment->unit_cost;
                    }
                }

                return [
                    'id' => $adjustment->id,
                    'date' => date("d-m-Y", strtotime($adjustment->created_at->toDateString())) . ' ' . $adjustment->created_at->toTimeString(),
                    'reference_no' => $adjustment->reference_no,
                    'warehouse' => $adjustment->warehouse->name ?? 'N/A',
                    'products' => implode('<br>', $productDetails),
                    'note' => $adjustment->note ?? '',
                ];
            });

            return $this->withDashBackground([
                'title' => "Stock Adjustments",
                'row_height' => 5,
                'add_text' => 'Add Adjustment',
                'add_url' => '/adjustments/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Products', 'field' => 'products', 'type' => 'html'],
                    ['label' => 'Note', 'field' => 'note', 'type' => 'text'],
                    [
                        'label' => 'Action',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/adjustments/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/adjustments/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $adjustmentsTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving adjustment data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Adjustment",
                "submit_url" => "/adjustments",
                "method" => "POST",
                "navigate_url" => "/adjustments",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Adjustment Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "date",
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
                                "generator_url" => "/generate/adjustment-reference",
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouseOptions,
                                "info" => "Select warehouse for adjustment",
                                "show_info_icon" => true,
                                "required" => true,
                            ],
                            [
                                "type" => "file",
                                "name" => "document",
                                "label" => "Attach Document",
                                "allowed_extensions" => ["pdf", "jpg", "jpeg", "png", "gif"],
                                "multiple" => false,
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
                                "info" => "Select products to adjust inventory",
                                "show_info_icon" => true,
                                "duplicate_handling" => [
                                    "strategy" => "prevent_duplicate",
                                    "identifier_field" => "id",
                                    "error_message" => "This product is already added. Please edit the existing row to adjust quantity.",
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
                                        "name" => "current_qty",
                                        "label" => "Current Qty",
                                        "type" => "number",
                                        "editable" => false,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                    [
                                        "name" => "adjustment_type",
                                        "label" => "Type (+/-)",
                                        "type" => "text",
                                        "editable" => true,
                                        "width" => 100,
                                    ],
                                    [
                                        "name" => "qty",
                                        "label" => "Adjust By",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                ],
                                "formula_engine" => [
                                    "enabled" => false,
                                ],
                                "totals" => [
                                    [
                                        "label" => "Total Items",
                                        "formula" => "COUNT(*)",
                                        "position" => "left",
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
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter adjustment note",
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the adjustment form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreAdjustmentRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->except('document', 'token');

            // Handle stock count if provided
            if (isset($data['stock_count_id'])) {
                $lims_stock_count_data = StockCount::find($data['stock_count_id']);
                $lims_stock_count_data->is_adjusted = true;
                $lims_stock_count_data->save();
            }

            $data['reference_no'] = 'adr-' . date("Ymd") . '-' . date("his");

            // Handle document upload
            $document = $request->document;
            if ($document) {
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/adjustment'), $documentName);
                $data['document'] = $documentName;
            }

            $lims_adjustment_data = Adjustment::create($data);

            $product_id = $data['product_id'];
            $product_code = $data['product_code'];
            $qty = $data['qty'];
            $unit_cost = $data['unit_cost'];
            $action = $data['action'];

            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);

                if ($lims_product_data->is_variant) {
                    $product_code_key = $product_code[$key] ?? null;
                    if ($product_code_key) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                            ->FindExactProductWithCode($pro_id, $product_code_key)->first();

                        if ($lims_product_variant_data) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['variant_id', $lims_product_variant_data->variant_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            if ($action[$key] == '-') {
                                $lims_product_variant_data->qty -= $qty[$key];
                            } elseif ($action[$key] == '+') {
                                $lims_product_variant_data->qty += $qty[$key];
                            }
                            $lims_product_variant_data->save();
                            $variant_id = $lims_product_variant_data->variant_id;
                        } else {
                            $variant_id = null;
                        }
                    } else {
                        $variant_id = null;
                    }
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();
                    $variant_id = null;
                }

                if (isset($lims_product_warehouse_data)) {
                    if ($action[$key] == '-') {
                        $lims_product_data->qty -= $qty[$key];
                        $lims_product_warehouse_data->qty -= $qty[$key];
                    } elseif ($action[$key] == '+') {
                        $lims_product_data->qty += $qty[$key];
                        $lims_product_warehouse_data->qty += $qty[$key];
                    }
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }

                $product_adjustment = [
                    'product_id' => $pro_id,
                    'variant_id' => $variant_id,
                    'adjustment_id' => $lims_adjustment_data->id,
                    'qty' => $qty[$key],
                    'unit_cost' => $unit_cost[$key],
                    'action' => $action[$key],
                ];
                ProductAdjustment::create($product_adjustment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adjustment created successfully.',
                'navigate_url' => '/adjustments'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the adjustment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $adjustment = Adjustment::with('warehouse')->findOrFail($id);
            $product_adjustments = ProductAdjustment::where('adjustment_id', $id)->get();

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            // Prepare product rows for table generator
            $productRows = [];
            foreach ($product_adjustments as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;

                $productWarehouse = Product_Warehouse::where([
                    ['product_id', $item->product_id],
                    ['warehouse_id', $adjustment->warehouse_id],
                ])->first();

                $currentQty = $productWarehouse ? $productWarehouse->qty : 0;

                $productRows[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'current_qty' => (int)$currentQty,
                    'adjustment_type' => $item->action,
                    'qty' => (int)$item->qty,
                ];
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Adjustment",
                "submit_url" => "/adjustments/{$id}",
                "method" => "PUT",
                "navigate_url" => "/adjustments",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Adjustment Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "date",
                                "label" => "Date",
                                "placeholder" => "Select date",
                                "format_specifier" => "dd MMMM, yyyy",
                                "value" => $adjustment->created_at ? $adjustment->created_at->format('d F, Y') : now()->format('d F, Y'),
                            ],
                            [
                                "type" => "text",
                                "name" => "reference_no_display",
                                "label" => "Reference No",
                                "value" => $adjustment->reference_no ?? '',
                                "disabled" => true,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "reference_no",
                                "value" => $adjustment->reference_no ?? '',
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "placeholder" => "Select warehouse",
                                "options" => $warehouseOptions,
                                "value" => $adjustment->warehouse_id,
                                "info" => "Select warehouse for adjustment",
                                "show_info_icon" => true,
                                "required" => true,
                            ],
                            [
                                "type" => "file",
                                "name" => "document",
                                "label" => "Attach Document",
                                "allowed_extensions" => ["pdf", "jpg", "jpeg", "png", "gif"],
                                "multiple" => false,
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
                                "info" => "Select products to adjust inventory",
                                "show_info_icon" => true,
                                "value" => $productRows,
                                "duplicate_handling" => [
                                    "strategy" => "prevent_duplicate",
                                    "identifier_field" => "id",
                                    "error_message" => "This product is already added. Please edit the existing row to adjust quantity.",
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
                                        "name" => "current_qty",
                                        "label" => "Current Qty",
                                        "type" => "number",
                                        "editable" => false,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                    [
                                        "name" => "adjustment_type",
                                        "label" => "Type (+/-)",
                                        "type" => "text",
                                        "editable" => true,
                                        "width" => 100,
                                    ],
                                    [
                                        "name" => "qty",
                                        "label" => "Adjust By",
                                        "type" => "number",
                                        "editable" => true,
                                        "width" => 100,
                                        "decimal_places" => 0,
                                    ],
                                ],
                                "formula_engine" => [
                                    "enabled" => false,
                                ],
                                "totals" => [
                                    [
                                        "label" => "Total Items",
                                        "formula" => "COUNT(*)",
                                        "position" => "left",
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
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter adjustment note",
                                "value" => $adjustment->note,
                            ],
                        ]
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while editing the adjustment form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate request
            $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:products,id',
                'qty' => 'required|array',
                'qty.*' => 'required|numeric|min:0',
                'unit_cost' => 'required|array',
                'unit_cost.*' => 'required|numeric|min:0',
                'action' => 'required|array',
                'action.*' => 'required|in:+,-',
            ]);

            DB::beginTransaction();

            $adjustment = Adjustment::findOrFail($id);
            $data = $request->except('document', 'token');

            // Handle document upload
            $document = $request->file('document');
            if ($document) {
                $v = Validator::make(
                    [
                        'extension' => strtolower($document->getClientOriginalExtension()),
                    ],
                    [
                        'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                    ]
                );
                if ($v->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file extension.',
                        'errors' => $v->errors(),
                    ], 422);
                }

                // Delete old document if exists
                if ($adjustment->document && file_exists(public_path('documents/adjustment/' . $adjustment->document))) {
                    unlink(public_path('documents/adjustment/' . $adjustment->document));
                }

                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                }
                $document->move(public_path('documents/adjustment'), $documentName);
                $data['document'] = $documentName;
            }

            // Reverse previous adjustments
            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
            foreach ($lims_product_adjustment_data as $product_adjustment_data) {
                $lims_product_data = Product::find($product_adjustment_data->product_id);

                if ($product_adjustment_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['variant_id', $product_adjustment_data->variant_id]
                    ])->first();

                    if ($lims_product_variant_data) {
                        if ($product_adjustment_data->action == '-') {
                            $lims_product_variant_data->qty += $product_adjustment_data->qty;
                        } elseif ($product_adjustment_data->action == '+') {
                            $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                        }
                        $lims_product_variant_data->save();
                    }

                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['variant_id', $product_adjustment_data->variant_id],
                        ['warehouse_id', $adjustment->warehouse_id]
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $adjustment->warehouse_id]
                    ])->first();
                }

                if ($lims_product_warehouse_data) {
                    if ($product_adjustment_data->action == '-') {
                        $lims_product_data->qty += $product_adjustment_data->qty;
                        $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                    } elseif ($product_adjustment_data->action == '+') {
                        $lims_product_data->qty -= $product_adjustment_data->qty;
                        $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                    }
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }
            }

            // Delete old product adjustments
            ProductAdjustment::where('adjustment_id', $id)->delete();

            // Apply new adjustments
            $product_id = $data['product_id'];
            $product_code = $data['product_code'] ?? [];
            $qty = $data['qty'];
            $unit_cost = $data['unit_cost'];
            $action = $data['action'];

            foreach ($product_id as $key => $pro_id) {
                $lims_product_data = Product::find($pro_id);

                if ($lims_product_data->is_variant) {
                    $product_code_key = $product_code[$key] ?? null;
                    if ($product_code_key) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')
                            ->FindExactProductWithCode($pro_id, $product_code_key)->first();

                        if ($lims_product_variant_data) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['variant_id', $lims_product_variant_data->variant_id],
                                ['warehouse_id', $data['warehouse_id']],
                            ])->first();

                            if ($action[$key] == '-') {
                                $lims_product_variant_data->qty -= $qty[$key];
                            } elseif ($action[$key] == '+') {
                                $lims_product_variant_data->qty += $qty[$key];
                            }
                            $lims_product_variant_data->save();
                            $variant_id = $lims_product_variant_data->variant_id;
                        } else {
                            $variant_id = null;
                        }
                    } else {
                        $variant_id = null;
                    }
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id']],
                    ])->first();
                    $variant_id = null;
                }

                if (isset($lims_product_warehouse_data)) {
                    if ($action[$key] == '-') {
                        $lims_product_data->qty -= $qty[$key];
                        $lims_product_warehouse_data->qty -= $qty[$key];
                    } elseif ($action[$key] == '+') {
                        $lims_product_data->qty += $qty[$key];
                        $lims_product_warehouse_data->qty += $qty[$key];
                    }
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }

                $product_adjustment = [
                    'product_id' => $pro_id,
                    'variant_id' => $variant_id,
                    'adjustment_id' => $id,
                    'qty' => $qty[$key],
                    'unit_cost' => $unit_cost[$key],
                    'action' => $action[$key],
                ];
                ProductAdjustment::create($product_adjustment);
            }

            $adjustment->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adjustment updated successfully.',
                'navigate_url' => '/adjustments'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the adjustment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $adjustment = Adjustment::with(['warehouse', 'user'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new AdjustmentResource($adjustment),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving adjustment details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            DB::beginTransaction();

            $lims_adjustment_data = Adjustment::findOrFail($id);
            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();

            foreach ($lims_product_adjustment_data as $product_adjustment_data) {
                $lims_product_data = Product::find($product_adjustment_data->product_id);

                if ($product_adjustment_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')
                        ->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();

                    if ($lims_product_variant_data) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();

                        if ($product_adjustment_data->action == '-') {
                            $lims_product_variant_data->qty += $product_adjustment_data->qty;
                        } elseif ($product_adjustment_data->action == '+') {
                            $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                        }
                        $lims_product_variant_data->save();
                    }
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
                }

                if ($lims_product_warehouse_data) {
                    if ($product_adjustment_data->action == '-') {
                        $lims_product_data->qty += $product_adjustment_data->qty;
                        $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                    } elseif ($product_adjustment_data->action == '+') {
                        $lims_product_data->qty -= $product_adjustment_data->qty;
                        $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                    }
                    $lims_product_data->save();
                    $lims_product_warehouse_data->save();
                }

                $product_adjustment_data->delete();
            }

            // Delete document if exists
            if ($lims_adjustment_data->document && file_exists(public_path('documents/adjustment/' . $lims_adjustment_data->document))) {
                unlink(public_path('documents/adjustment/' . $lims_adjustment_data->document));
            }

            $lims_adjustment_data->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Adjustment deleted successfully.',
                'navigate_url' => '/adjustments'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the adjustment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProduct($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $lims_product_warehouse_data = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereNull('products.is_variant')
                ->where([
                    ['products.is_active', true],
                    ['product_warehouse.warehouse_id', $id]
                ])
                ->select('product_warehouse.qty', 'products.code', 'products.name', 'product_warehouse.product_id', 'products.cost')
                ->get();

            $lims_product_withVariant_warehouse_data = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereNotNull('products.is_variant')
                ->where([
                    ['products.is_active', true],
                    ['product_warehouse.warehouse_id', $id]
                ])
                ->select('products.name', 'product_warehouse.qty', 'product_warehouse.product_id', 'product_warehouse.variant_id', 'products.cost')
                ->get();

            $product_code = [];
            $product_name = [];
            $product_qty = [];
            $product_cost = [];

            foreach ($lims_product_warehouse_data as $product_warehouse) {
                $product_qty[] = $product_warehouse->qty;
                $product_code[] = $product_warehouse->code;
                $product_name[] = $product_warehouse->name;

                $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.product_id', '=', 'purchases.id')
                    ->where([
                        ['product_id', $product_warehouse->product_id],
                        ['warehouse_id', $id]
                    ])
                    ->selectRaw('SUM(qty) AS total_qty, SUM(total) AS total_cost')
                    ->first();

                if ($product_purchase_data && $product_purchase_data->total_qty > 0) {
                    $product_cost[] = $product_purchase_data->total_cost / $product_purchase_data->total_qty;
                } else {
                    $product_cost[] = $product_warehouse->cost;
                }
            }

            foreach ($lims_product_withVariant_warehouse_data as $product_warehouse) {
                $product_variant = ProductVariant::select('item_code')
                    ->FindExactProduct($product_warehouse->product_id, $product_warehouse->variant_id)->first();

                if ($product_variant) {
                    $product_qty[] = $product_warehouse->qty;
                    $product_code[] = $product_variant->item_code;
                    $product_name[] = $product_warehouse->name;

                    $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.product_id', '=', 'purchases.id')
                        ->where([
                            ['product_id', $product_warehouse->product_id],
                            ['variant_id', $product_warehouse->variant_id],
                            ['warehouse_id', $id]
                        ])
                        ->selectRaw('SUM(qty) AS total_qty, SUM(total) AS total_cost')
                        ->first();

                    if ($product_purchase_data && $product_purchase_data->total_qty > 0) {
                        $product_cost[] = $product_purchase_data->total_cost / $product_purchase_data->total_qty;
                    } else {
                        $product_cost[] = $product_warehouse->cost;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_codes' => $product_code,
                    'product_names' => $product_name,
                    'product_qtys' => $product_qty,
                    'product_costs' => $product_cost,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function productSearch(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access adjustments
            if (!$role->hasPermissionTo('adjustment')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $product_code = explode("(", $request['data']);
            $product_info = explode("|", $request['data']);
            $product_code[0] = rtrim($product_code[0], " ");

            $lims_product_data = Product::where([
                ['code', $product_code[0]],
                ['is_active', true]
            ])->first();

            if (!$lims_product_data) {
                $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                    ->select('products.id', 'products.name', 'products.is_variant', 'product_variants.id as product_variant_id', 'product_variants.item_code')
                    ->where([
                        ['product_variants.item_code', $product_code[0]],
                        ['products.is_active', true]
                    ])->first();
            }

            if (!$lims_product_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                ], 404);
            }

            $product = [];
            $product[] = $lims_product_data->name;
            $product_variant_id = null;

            if ($lims_product_data->is_variant) {
                $product[] = $lims_product_data->item_code;
                $product_variant_id = $lims_product_data->product_variant_id;
            } else {
                $product[] = $lims_product_data->code ?? $product_code[0];
            }

            $product[] = $lims_product_data->id;
            $product[] = $product_variant_id;
            $product[] = $product_info[1] ?? 0; // unit cost

            $quantity = explode("|", $request['data']);
            if (count($quantity) >= 3) {
                $product[] = $quantity[2]; // current quantity
            }

            return response()->json([
                'success' => true,
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for the product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
