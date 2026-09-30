<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditCustomerGroupRequest;
use App\Http\Requests\StoreCustomerGroupRequest;
use App\Http\Resources\CustomerGroupResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Traits\CacheForget;

class CustomerGroupController extends Controller
{
    use CacheForget;
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customerGroups = CustomerGroup::where('is_active', true)->orderBy('id', 'desc')->get();

            return $this->withDashBackground([
                'title' => "Customer Groups",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 3,
                'add_text' => 'Add Customer Group',
                'add_url' => '/customergroups/create',
                'import_url' => '/customergroups/import',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Percentage', 'field' => 'percentage', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/customergroups/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/customergroups/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => CustomerGroupResource::collection($customerGroups),
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving customer groups data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Add Customer Group",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/customergroups",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter customer group name",
                    ],
                    [
                        "type" => "text",
                        "name" => "percentage",
                        "label" => "Percentage (%)",
                        "keyboard_type" => "number",
                        "placeholder" => "Enter percentage",
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ]
                ]
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading customer group form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreCustomerGroupRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $data['is_active'] = true;
            $customerGroup = CustomerGroup::create($data);
            $this->cacheForget('customer_group_list');

            return response()->json([
                'success' => true,
                'message' => 'Customer Group created successfully.',
                'navigate_url' => '/customergroups',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the customer group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(CustomerGroup $customergroup)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new CustomerGroupResource($customergroup),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving customer group details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customerGroup = CustomerGroup::findOrFail($id);
            $formSchema = [
                "title" => "Edit Customer Group",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/customergroups/" . $customerGroup->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter customer group name",
                        "value" => $customerGroup->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "percentage",
                        "label" => "Percentage (%)",
                        "keyboard_type" => "number",
                        "placeholder" => "Enter percentage",
                        "value" => $customerGroup->percentage,
                    ],
                ]
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading customer group edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(EditCustomerGroupRequest $request, CustomerGroup $customergroup)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $input = $request->validated();
            $customergroup->update($input);
            $this->cacheForget('customer_group_list');

            return response()->json([
                'success' => true,
                'message' => 'Customer Group updated successfully.',
                'navigate_url' => '/customergroups',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the customer group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create customer groups
            if (!$role->hasPermissionTo('customer_group-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import customer groups.',
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

                    $customer_group = CustomerGroup::firstOrNew(['name' => $data['name'], 'is_active' => true]);
                    $customer_group->name = $data['name'];
                    $customer_group->percentage = $data['percentage'];
                    $customer_group->is_active = true;
                    $customer_group->save();

                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('customer_group_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} customer groups.",
                    'navigate_url' => '/customergroups',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Customer Groups",
                "submit_url" => "/customergroups/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, percentage) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_customer_group.csv'),
                        "sample_file_name" => "sample_customer_group.csv",
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

    public function destroy(CustomerGroup $customergroup)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete customer groups
            if (!$role->hasPermissionTo('customer_group')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customergroup->update(['is_active' => false]);
            $this->cacheForget('customer_group_list');

            return response()->json([
                'success' => true,
                'message' => 'Customer Group has been deleted successfully.',
                'navigate_url' => '/customergroups',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the customer group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
