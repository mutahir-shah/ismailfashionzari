<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\WarehouseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Product_Warehouse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Traits\CacheForget;

class WarehouseController extends Controller
{
    use CacheForget, ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            // Retrieve warehouses based on user role
            $query = Warehouse::where('is_active', true);

            // If user role is > 2, only show their assigned warehouse
            if ($user->role_id > 2) {
                $query->where('id', $user->warehouse_id);
            }

            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $warehouses = $query->orderBy('id', 'desc')->get();

            // Format warehouses for datatable
            $warehousesTable = $warehouses->map(function ($warehouse) {
                $totalProducts = $warehouse->products()->where('product_warehouse.qty', '>', 0)->count();
                $totalQuantity = $warehouse->products()->sum('product_warehouse.qty');

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'phone' => $warehouse->phone,
                    'email' => $warehouse->email ?? 'N/A',
                    'address' => $warehouse->address,
                    'number_of_products' => "<span style='font-size: 20px;'>" . $totalProducts . "</span>",
                    'stock_quantity' => "<span style='font-size: 20px;'>" . $totalQuantity . "</span>",
                ];
            });

            return $this->withDashBackground([
                'title' => "Warehouses",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Warehouse',
                'add_url' => '/warehouses/create',
                'import_url' => '/warehouses/import',
                'columns' => [
                    ['label' => 'Warehouse Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Phone', 'field' => 'phone', 'type' => 'text'],
                    ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
                    ['label' => 'Address', 'field' => 'address', 'type' => 'text'],
                    ['label' => 'Number of Products', 'field' => 'number_of_products', 'type' => 'html'],
                    ['label' => 'Stock Quantity', 'field' => 'stock_quantity', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/warehouses/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/warehouses/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $warehousesTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving warehouse data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Add a New Warehouse",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/warehouses",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Warehouse Name",
                        "placeholder" => "Enter warehouse name",
                    ],
                    [
                        "type" => "text",
                        "name" => "phone",
                        "label" => "Phone",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                    ],
                    [
                        "type" => "text",
                        "name" => "email",
                        "label" => "Email",
                        "placeholder" => "Enter email address",
                        "keyboard_type" => "email",
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "multiline" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the warehouse creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(StoreWarehouseRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $validatedData = $request->validated();
            $validatedData['is_active'] = true;

            $warehouse = Warehouse::create($validatedData);

            $products = Product::pluck('id');
            foreach ($products as $productId) {
                Product_Warehouse::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 0
                ]);
            }

            $this->cacheForget('warehouse_list');

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully.',
                'navigate_url' => '/warehouses',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the warehouse.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(Warehouse $warehouse)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new WarehouseResource($warehouse),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving the warehouse.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(Warehouse $warehouse)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Edit Warehouse",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/warehouses/" . $warehouse->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Warehouse Name",
                        "placeholder" => "Enter warehouse name",
                        "value" => $warehouse->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "phone",
                        "label" => "Phone",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                        "value" => $warehouse->phone,
                    ],
                    [
                        "type" => "text",
                        "name" => "email",
                        "label" => "Email",
                        "placeholder" => "Enter email address",
                        "keyboard_type" => "email",
                        "value" => $warehouse->email,
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "multiline" => true,
                        "value" => $warehouse->address,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the warehouse edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(StoreWarehouseRequest $request, Warehouse $warehouse)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $validatedData = $request->validated();
            $warehouse->update($validatedData);
            $this->cacheForget('warehouse_list');

            return response()->json([
                'success' => true,
                'message' => 'Warehouse updated successfully.',
                'navigate_url' => '/warehouses',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the warehouse.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create warehouses
            if (!$role->hasPermissionTo('warehouse-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import warehouses.',
                ], 403);
            }

            // Handle POST request - process the import
            if ($request->isMethod('post')) {
                $request->validate([
                    'file' => 'required|file|mimes:csv',
                ]);

                $upload = $request->file('file');
                $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
                if ($ext != 'csv') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please upload a CSV file',
                    ], 400);
                }

                $filePath = $upload->getRealPath();
                $file = fopen($filePath, 'r');
                $header = fgetcsv($file);
                $escapedHeader = [];

                // Validate and escape header
                foreach ($header as $key => $value) {
                    $lheader = strtolower($value);
                    $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                    array_push($escapedHeader, $escapedItem);
                }

                $importedCount = 0;
                // Loop through rows
                while ($columns = fgetcsv($file)) {
                    if ($columns[0] == "")
                        continue;

                    $data = array_combine($escapedHeader, $columns);

                    $warehouse = Warehouse::firstOrNew(['name' => $data['name'], 'is_active' => true]);
                    $warehouse->name = $data['name'];
                    $warehouse->phone = $data['phone'] ?? null;
                    $warehouse->email = $data['email'] ?? null;
                    $warehouse->address = $data['address'] ?? null;
                    $warehouse->is_active = true;
                    $warehouse->save();

                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('warehouse_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} warehouses.",
                    'navigate_url' => '/warehouses',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Warehouses",
                "submit_url" => "/warehouses/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, phone, email, address) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_warehouse.csv'),
                        "sample_file_name" => "sample_warehouse.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'), 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the warehouse module
            if (!$role->hasPermissionTo('warehouse')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouse->is_active = false;
            $warehouse->save();
            $this->cacheForget('warehouse_list');

            return response()->json([
                'success' => true,
                'message' => 'Warehouse has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the warehouse.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
