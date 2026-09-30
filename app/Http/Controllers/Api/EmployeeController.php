<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ErrorResource;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Warehouse;
use App\Models\Biller;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;

class EmployeeController extends Controller
{
    use TenantInfo;

    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access employees
            if (!$role->hasPermissionTo('employees-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Employee::with(['user'])->where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%")
                        ->orWhere('staff_id', 'LIKE', "%{$search}%");
                });
            }

            $employees = $query->orderBy('id', 'desc')->get();
            // Format employees for datatable
            $employeesTable = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'image_url' => $employee->image
                        ? url('images/employee', $employee->image)
                        : url('images/zummXD2dvAtI.png'),
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone_number' => $employee->phone_number ?? 'N/A',
                    'department' => $employee->department->name ?? 'N/A',
                    'staff_id' => $employee->staff_id ?? 'N/A',
                    'user_account' => $employee->user_id
                        ? '<span style="color: green;">Yes</span>'
                        : '<span style="color: red;">No</span>',
                ];
            });

            return $this->withDashBackground([
                'title' => "Employees",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Employee',
                'add_url' => '/employees/create',
                'columns' => [
                    ['label' => 'Image', 'field' => 'image_url', 'type' => 'avatar'],
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
                    ['label' => 'Phone', 'field' => 'phone_number', 'type' => 'text'],
                    ['label' => 'Department', 'field' => 'department', 'type' => 'text'],
                    ['label' => 'Staff ID', 'field' => 'staff_id', 'type' => 'text'],
                    ['label' => 'User Account', 'field' => 'user_account', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/employees/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/employees/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $employeesTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving employee data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add employees
            if (!$role->hasPermissionTo('employees-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get departments
            $departments = Department::where('is_active', true)->get();
            $departmentOptions = $departments->map(function ($department) {
                return [
                    'label' => $department->name,
                    'value' => $department->id
                ];
            })->toArray();

            // Get roles
            $roles = Role::where('is_active', true)->get();
            $roleOptions = $roles->map(function ($role) {
                return [
                    'label' => $role->display_name ?? $role->name,
                    'value' => $role->id
                ];
            })->toArray();

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            // Get billers
            $billers = Biller::where('is_active', true)->get();
            $billerOptions = $billers->map(function ($biller) {
                return [
                    'label' => $biller->name . ' (' . $biller->company_name . ')',
                    'value' => $biller->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Employee",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/employees",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Employee Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "employee_name",
                                "label" => "Employee Name",
                                "placeholder" => "Enter employee name",
                            ],
                            [
                                "type" => "file",
                                "name" => "image",
                                "label" => "Employee Image",
                                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "select",
                                "name" => "department_id",
                                "label" => "Department",
                                "options" => $departmentOptions,
                                "new_screen" => "/departments/create",
                            ],
                            [
                                "type" => "text",
                                "name" => "email",
                                "label" => "Email",
                                "placeholder" => "example@example.com",
                                "keyboard_type" => "email",
                            ],
                            [
                                "type" => "text",
                                "name" => "phone_number",
                                "label" => "Phone Number",
                                "placeholder" => "Enter phone number",
                                "keyboard_type" => "phone",
                            ],
                            [
                                "type" => "text",
                                "name" => "address",
                                "label" => "Address",
                                "placeholder" => "Enter address",
                            ],
                            [
                                "type" => "text",
                                "name" => "city",
                                "label" => "City",
                                "placeholder" => "Enter city",
                            ],
                            [
                                "type" => "text",
                                "name" => "country",
                                "label" => "Country",
                                "placeholder" => "Enter country",
                            ],
                            [
                                "type" => "text",
                                "name" => "staff_id",
                                "label" => "Staff ID",
                                "placeholder" => "Enter staff ID",
                            ],
                        ]
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "create_user",
                        "label" => "Create User Account",
                        "value" => true,
                    ],
                    [
                        "type" => "group",
                        "label" => "User Account Information",
                        "logics" => [
                            [
                                "field" => "create_user",
                                "values" => [true, 1],
                            ],
                        ],
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Username",
                                "placeholder" => "Enter username",
                                "info" => "Required only if creating user account",
                                "show_info_icon" => true,
                            ],
                            [
                                "type" => "datagenerator",
                                "name" => "password",
                                "label" => "Password",
                                "placeholder" => "Enter password",
                                "generator_url" => "/generate-password",
                            ],
                            [
                                "type" => "select",
                                "name" => "role_id",
                                "label" => "Role",
                                "options" => $roleOptions,
                            ],
                            [
                                "type" => "text",
                                "name" => "company",
                                "label" => "Company",
                                "placeholder" => "Select Company...",
                                "info" => "Optional - for Project module",
                                "show_info_icon" => true,
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Warehouse",
                                "options" => $warehouseOptions,
                                "info" => "Required for staff roles (not customer role)",
                                "show_info_icon" => true,
                                "logics" => [
                                    [
                                        "field" => "role_id",
                                        "values" => [3, 4, 6, 7, 8, 9, 10],
                                    ],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "biller_id",
                                "label" => "Biller",
                                "options" => $billerOptions,
                                "info" => "Required for staff roles (not customer role)",
                                "show_info_icon" => true,
                                "logics" => [
                                    [
                                        "field" => "role_id",
                                        "values" => [3, 4, 6, 7, 8, 9, 10],
                                    ],
                                ],
                            ],
                        ]
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
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the employee form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add employees
            if (!$role->hasPermissionTo('employees-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate employee data
            $request->validate([
                'employee_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('employees')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'phone_number' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10000',
            ]);

            $data = $request->except('image', 'token');
            $message = 'Employee created successfully';

            // Handle user creation if requested
            if ($request->has('create_user') && $request->create_user) {
                $request->validate([
                    'name' => [
                        'required',
                        'max:255',
                        Rule::unique('users')->where(function ($query) {
                            return $query->where('is_deleted', false);
                        }),
                    ],
                    'password' => 'required|string|min:6',
                    'role_id' => 'required|exists:roles,id',
                ]);

                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone_number'],
                    'role_id' => $data['role_id'],
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'biller_id' => $data['biller_id'] ?? null,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $createdUser = User::create($userData);
                $data['user_id'] = $createdUser->id;
                $message = 'Employee created successfully and added to user list';
            }

            // Handle image upload
            $image = $request->file('image');
            if ($image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;

                if (config('database.connections.saleprosaas_landlord')) {
                    $imageName = $this->getTenantId() . '_' . $imageName;
                }

                $image->move(public_path('images/employee'), $imageName);
                $data['image'] = $imageName;
            }

            $data['name'] = $data['employee_name'];
            $data['is_active'] = true;

            Employee::create($data);

            return response()->json([
                'success' => true,
                'message' => $message,
                'navigate_url' => '/employees'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the employee.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit employees
            if (!$role->hasPermissionTo('employees-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $employee = Employee::with(['user', 'department'])->findOrFail($id);

            // Get departments
            $departments = Department::where('is_active', true)->get();
            $departmentOptions = $departments->map(function ($department) {
                return [
                    'label' => $department->name,
                    'value' => $department->id
                ];
            })->toArray();

            // Get roles
            $roles = Role::where('is_active', true)->get();
            $roleOptions = $roles->map(function ($role) {
                return [
                    'label' => $role->display_name ?? $role->name,
                    'value' => $role->id
                ];
            })->toArray();

            // Get warehouses
            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            })->toArray();

            // Get billers
            $billers = Biller::where('is_active', true)->get();
            $billerOptions = $billers->map(function ($biller) {
                return [
                    'label' => $biller->name . ' (' . $biller->company_name . ')',
                    'value' => $biller->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Edit Employee",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/employees/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Employee Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "employee_name",
                                "label" => "Employee Name",
                                "placeholder" => "Enter employee name",
                                "value" => $employee->name,
                            ],
                            [
                                "type" => "file",
                                "name" => "image",
                                "label" => "Employee Image",
                                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "select",
                                "name" => "department_id",
                                "label" => "Department",
                                "options" => $departmentOptions,
                                "value" => $employee->department_id,
                                "new_screen" => "/departments/create",
                            ],
                            [
                                "type" => "text",
                                "name" => "email",
                                "label" => "Email",
                                "placeholder" => "example@example.com",
                                "keyboard_type" => "email",
                                "value" => $employee->email,
                            ],
                            [
                                "type" => "text",
                                "name" => "phone_number",
                                "label" => "Phone Number",
                                "placeholder" => "Enter phone number",
                                "keyboard_type" => "phone",
                                "value" => $employee->phone_number,
                            ],
                            [
                                "type" => "text",
                                "name" => "address",
                                "label" => "Address",
                                "placeholder" => "Enter address",
                                "value" => $employee->address,
                            ],
                            [
                                "type" => "text",
                                "name" => "city",
                                "label" => "City",
                                "placeholder" => "Enter city",
                                "value" => $employee->city,
                            ],
                            [
                                "type" => "text",
                                "name" => "country",
                                "label" => "Country",
                                "placeholder" => "Enter country",
                                "value" => $employee->country,
                            ],
                            [
                                "type" => "text",
                                "name" => "staff_id",
                                "label" => "Staff ID",
                                "placeholder" => "Enter staff ID",
                                "value" => $employee->staff_id,
                            ],
                        ]
                    ],
                ]
            ];

            // Add user account fields if user exists
            if ($employee->user_id && $employee->user) {
                $formSchema['fields'][] = [
                    "type" => "group",
                    "label" => "User Account Information",
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "name",
                            "label" => "Username",
                            "placeholder" => "Enter username",
                            "value" => $employee->user->name,
                        ],
                        [
                            "type" => "text",
                            "name" => "password",
                            "label" => "Password (Leave blank to keep current)",
                            "placeholder" => "Enter new password",
                        ],
                        [
                            "type" => "select",
                            "name" => "role_id",
                            "label" => "Role",
                            "options" => $roleOptions,
                            "value" => $employee->user->role_id,
                        ],
                        [
                            "type" => "select",
                            "name" => "warehouse_id",
                            "label" => "Warehouse",
                            "options" => $warehouseOptions,
                            "value" => $employee->user->warehouse_id,
                        ],
                        [
                            "type" => "select",
                            "name" => "biller_id",
                            "label" => "Biller",
                            "options" => $billerOptions,
                            "value" => $employee->user->biller_id,
                        ],
                    ]
                ];
                $formSchema['fields'][] = [
                    "type" => "hidden",
                    "name" => "employee_id",
                    "value" => $employee->id,
                ];
            }

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while editing the employee form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit employees
            if (!$role->hasPermissionTo('employees-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $employee = Employee::findOrFail($id);

            // Validate employee data
            $request->validate([
                'employee_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('employees')->ignore($employee->id)->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'phone_number' => 'required|string|max:255',
                'department_id' => 'required|exists:departments,id',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10000',
            ]);

            // Validate user data if user exists
            if ($employee->user_id) {
                $request->validate([
                    'name' => [
                        'required',
                        'max:255',
                        Rule::unique('users')->ignore($employee->user_id)->where(function ($query) {
                            return $query->where('is_deleted', false);
                        }),
                    ],
                    'role_id' => 'required|exists:roles,id',
                ]);
            }

            $data = $request->except('image', 'token');

            // Handle image upload
            $image = $request->file('image');
            if ($image) {
                // Delete old image if exists
                if ($employee->image && file_exists(public_path('images/employee/' . $employee->image))) {
                    unlink(public_path('images/employee/' . $employee->image));
                }

                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;

                if (config('database.connections.saleprosaas_landlord')) {
                    $imageName = $this->getTenantId() . '_' . $imageName;
                }

                $image->move(public_path('images/employee'), $imageName);
                $data['image'] = $imageName;
            }

            // Update user if exists
            if ($employee->user_id && $employee->user) {
                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone_number'],
                    'role_id' => $data['role_id'],
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'biller_id' => $data['biller_id'] ?? null,
                ];

                if (!empty($data['password'])) {
                    $userData['password'] = Hash::make($data['password']);
                }

                $employee->user->update($userData);
            }

            $data['name'] = $data['employee_name'];
            $employee->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'navigate_url' => '/employees'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the employee.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view employees
            if (!$role->hasPermissionTo('employees-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $employee = Employee::with(['user', 'department'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new EmployeeResource($employee),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving employee details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete employees
            if (!$role->hasPermissionTo('employees-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $employee = Employee::findOrFail($id);

            // Deactivate associated user if exists
            if ($employee->user_id && $employee->user) {
                $employee->user->update([
                    'is_deleted' => true,
                    'is_active' => false,
                ]);
            }

            // Delete image if exists
            if ($employee->image && file_exists(public_path('images/employee/' . $employee->image))) {
                unlink(public_path('images/employee/' . $employee->image));
            }

            // Deactivate employee
            $employee->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully.',
                'navigate_url' => '/employees'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the employee.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
