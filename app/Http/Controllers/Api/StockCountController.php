<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\StockCount;
use App\Models\Warehouse;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Traits\TenantInfo;

class StockCountController extends Controller
{
    use TenantInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');
            $general_setting = DB::table('general_settings')->latest()->first();

            // Check staff access permissions
            if ($user->role_id > 2 && $general_setting->staff_access == 'own') {
                $query = StockCount::where('user_id', $user->id);
            } else {
                $query = StockCount::query();
            }

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $stockCounts = $query->orderBy('id', 'desc')->with(['warehouse'])->get();

            // Format stock counts for datatable
            $stockCountsTable = $stockCounts->map(function ($stockCount) use ($general_setting) {
                $warehouse = $stockCount->warehouse ?? DB::table('warehouses')->find($stockCount->warehouse_id);

                // Get category names
                $categoryNames = [];
                if ($stockCount->category_id) {
                    $categoryIds = explode(',', $stockCount->category_id);
                    foreach ($categoryIds as $categoryId) {
                        $category = DB::table('categories')->find($categoryId);
                        if ($category) {
                            $categoryNames[] = $category->name;
                        }
                    }
                }

                // Get brand names
                $brandNames = [];
                if ($stockCount->brand_id) {
                    $brandIds = explode(',', $stockCount->brand_id);
                    foreach ($brandIds as $brandId) {
                        $brand = DB::table('brands')->find($brandId);
                        if ($brand) {
                            $brandNames[] = $brand->title;
                        }
                    }
                }

                $typeDisplay = $stockCount->type == 'full' ? 'Full' : 'Partial';
                $typeColor = $stockCount->type == 'full' ? '#007bff' : '#17a2b8';

                return [
                    'id' => $stockCount->id,
                    'date' => date($general_setting->date_format ?? 'Y-m-d', strtotime($stockCount->created_at->toDateString())) . ' ' . $stockCount->created_at->toTimeString(),
                    'reference_no' => $stockCount->reference_no,
                    'warehouse' => $warehouse->name ?? 'N/A',
                    'categories' => implode(', ', $categoryNames) ?: '',
                    'brands' => implode(', ', $brandNames) ?: '',
                    'type' => "<span style='background-color: {$typeColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>{$typeDisplay}</span>",
                    'has_final_file' => (bool) $stockCount->final_file,
                ];
            });

            return $this->withDashBackground([
                'title' => "Stock Counts",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 4,
                'add_url' => '/stock-counts/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Categories', 'field' => 'categories', 'type' => 'text'],
                    ['label' => 'Brands', 'field' => 'brands', 'type' => 'text'],
                    ['label' => 'Type', 'field' => 'type', 'type' => 'html'],
                    [
                        'label' => 'Actions',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'download',
                                'action' => [
                                    'api_url' => '/stock-counts/{id}/download-initial',
                                    'type' => 'download'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'download',
                                'action' => [
                                    'api_url' => '/stock-counts/{id}/download-final',
                                    'type' => 'download'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'has_final_file',
                                        'values' => [true],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'view',
                                'action' => [
                                    'api_url' => '/stock-counts/{id}/report',
                                    'type' => 'view'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'has_final_file',
                                        'values' => [true],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'upload',
                                'action' => [
                                    'api_url' => '/stock-counts/{id}/finalize',
                                    'type' => 'form'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'has_final_file',
                                        'values' => [false],
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],
                'rows' => $stockCountsTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving stock count data.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $warehouses = Warehouse::where('is_active', true)->get();
            $categories = Category::where('is_active', true)->get();
            $brands = Brand::where('is_active', true)->get();

            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            $categoryOptions = $categories->map(function ($category) {
                return [
                    'label' => $category->name,
                    'value' => $category->id,
                ];
            })->toArray();

            $brandOptions = $brands->map(function ($brand) {
                return [
                    'label' => $brand->title,
                    'value' => $brand->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Create Stock Count",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/stock-counts",
                "method" => "POST",
                "navigate_url" => "/stock-counts",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "Stock count will generate a CSV file with current stock levels for you to fill in actual counted quantities.",
                    ],
                    [
                        "type" => "group",
                        "label" => "Stock Count Details",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse *",
                                "options" => $warehouseOptions,
                            ],
                            [
                                "type" => "select",
                                "name" => "type",
                                "label" => "Type *",
                                "options" => [
                                    ["label" => "Full Stock Count", "value" => "full"],
                                    ["label" => "Partial Stock Count", "value" => "partial"],
                                ],
                                "value" => "partial",
                            ],
                            [
                                "type" => "select",
                                "name" => "category_id[]",
                                "label" => "Categories (Optional)",
                                "options" => $categoryOptions,
                                "multiple" => true,
                                "logics" => [
                                    [
                                        "field" => "type",
                                        "values" => ["partial"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "brand_id[]",
                                "label" => "Brands (Optional)",
                                "options" => $brandOptions,
                                "multiple" => true,
                                "logics" => [
                                    [
                                        "field" => "type",
                                        "values" => ["partial"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the stock count form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate stock count data
            $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'type' => 'required|in:full,partial',
                'category_id' => 'nullable|array',
                'category_id.*' => 'exists:categories,id',
                'brand_id' => 'nullable|array',
                'brand_id.*' => 'exists:brands,id',
            ]);

            $data = $request->all();

            // Build product query based on filters
            $query = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->where([
                    ['products.is_active', true],
                    ['product_warehouse.warehouse_id', $data['warehouse_id']]
                ])
                ->select('products.name', 'products.code', 'product_warehouse.imei_number', 'product_warehouse.qty');

            // Apply category filter if provided
            if (isset($data['category_id']) && is_array($data['category_id'])) {
                $query->whereIn('products.category_id', $data['category_id']);
                $data['category_id'] = implode(",", $data['category_id']);
            }

            // Apply brand filter if provided
            if (isset($data['brand_id']) && is_array($data['brand_id'])) {
                $query->whereIn('products.brand_id', $data['brand_id']);
                $data['brand_id'] = implode(",", $data['brand_id']);
            }

            $products = $query->get();

            if ($products->count() == 0) {
                return new ErrorResource([
                    'message' => 'No products found for the selected criteria.',
                ]);
            }

            // Generate CSV file
            $csvData = ['Product Name,Product Code,IMEI or Serial Numbers,Expected,Counted'];
            foreach ($products as $product) {
                $csvData[] = $product->name . ',' . $product->code . ',' . str_replace(",", "/", $product->imei_number) . ',' . $product->qty . ',';
            }

            // Ensure stock_count directory exists
            if (!file_exists(public_path('stock_count/'))) {
                mkdir(public_path('stock_count/'), 0777, true);
            }

            $filename = date('Ymd') . '-' . date('his') . ".csv";

            if (config('database.connections.saleprosaas_landlord')) {
                $filename = $this->getTenantId() . '_' . $filename;
            }

            $filePath = public_path('stock_count/' . $filename);
            $file = fopen($filePath, "w+");
            foreach ($csvData as $cellData) {
                fputcsv($file, explode(',', $cellData));
            }
            fclose($file);

            // Create stock count record
            $data['user_id'] = $user->id;
            $data['reference_no'] = 'scr-' . date("Ymd") . '-' . date("his");
            $data['initial_file'] = $filename;
            $data['is_adjusted'] = false;

            StockCount::create($data);

            return new SuccessResource([
                'message' => 'Stock Count created successfully! Please download the initial file to complete it.',
                'navigate_url' => '/stock-counts',
                'download_url' => url('stock_count/' . $filename),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the stock count.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $stockCount = StockCount::findOrFail($id);

            // Check staff access permissions
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $stockCount->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this stock count.',
                ]);
            }

            return new SuccessResource([
                'data' => $stockCount,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving stock count details.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function finalize($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $stockCount = StockCount::findOrFail($id);

            // Check staff access permissions
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $stockCount->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this stock count.',
                ]);
            }

            if ($stockCount->final_file) {
                return new ErrorResource([
                    'message' => 'This stock count has already been finalized.',
                ]);
            }

            $formSchema = [
                "title" => "Finalize Stock Count",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/stock-counts/" . $id . "/finalize",
                "method" => "POST",
                "navigate_url" => "/stock-counts",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "You just need to update the Counted column in the initial file and upload it here.",
                    ],
                    [
                        "type" => "group",
                        "label" => "Finalize Details",
                        "items" => [
                            [
                                "type" => "file",
                                "name" => "final_file",
                                "label" => "Upload Final File *",
                                "allowed_extensions" => ["csv"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "text",
                                "name" => "note",
                                "label" => "Note",
                                "placeholder" => "Enter any notes about this stock count",
                                "multiline" => true,
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "stock_count_id",
                        "value" => $id,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the finalize form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function storeFinalize(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $stockCount = StockCount::findOrFail($id);

            // Check staff access permissions
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $stockCount->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this stock count.',
                ]);
            }

            // Validate finalize data
            $request->validate([
                'final_file' => 'required|file|mimes:csv',
                'note' => 'nullable|string|max:1000',
            ]);

            $ext = pathinfo($request->final_file->getClientOriginalName(), PATHINFO_EXTENSION);
            if ($ext != 'csv') {
                return new ErrorResource([
                    'message' => 'Please upload a CSV file.',
                ]);
            }

            $document = $request->final_file;
            $documentName = date('Ymd') . '-' . date('his') . ".csv";

            if (config('database.connections.saleprosaas_landlord')) {
                $documentName = $this->getTenantId() . '_' . $documentName;
            }

            $document->move(public_path('stock_count/'), $documentName);

            $data = $request->only(['note']);
            $data['final_file'] = $documentName;
            $stockCount->update($data);

            return new SuccessResource([
                'message' => 'Stock Count finalized successfully!',
                'navigate_url' => '/stock-counts'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while finalizing the stock count.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadInitial($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $stockCount = StockCount::findOrFail($id);

            // Check staff access permissions
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $stockCount->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this stock count.',
                ]);
            }

            $filePath = public_path('stock_count/' . $stockCount->initial_file);
            if (!file_exists($filePath)) {
                return new ErrorResource([
                    'message' => 'Initial file not found.',
                ]);
            }

            return response()->download($filePath);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while downloading the file.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadFinal($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access stock count module
            if (!$role->hasPermissionTo('stock_count')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $stockCount = StockCount::findOrFail($id);

            // Check staff access permissions
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $stockCount->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this stock count.',
                ]);
            }

            if (!$stockCount->final_file) {
                return new ErrorResource([
                    'message' => 'Final file not available. Please finalize the stock count first.',
                ]);
            }

            $filePath = public_path('stock_count/' . $stockCount->final_file);
            if (!file_exists($filePath)) {
                return new ErrorResource([
                    'message' => 'Final file not found.',
                ]);
            }

            return response()->download($filePath);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while downloading the file.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
