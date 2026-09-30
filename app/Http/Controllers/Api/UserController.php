<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Biller;
use App\Models\Warehouse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\UserDetails;
use App\Models\MailSetting;
use Keygen;

class UserController extends Controller
{
    use \App\Traits\APIPaginationTrait;
    use \App\Traits\MailInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('users-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = User::where('is_deleted', false);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $users = $this->resolveCollection($query->orderBy('id', 'desc'), $request);
            $pagination = $this->resolvePagination($query->orderBy('id', 'desc'), $request);

            // Format users for datatable
            $usersTable = $users->map(function ($user) {
                $role = Role::find($user->role_id);
                $roleName = $role ? $role->display_name : 'N/A';

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_name' => $user->company_name ?? 'N/A',
                    'phone' => $user->phone ?? 'N/A',
                    'role' => $roleName,
                    'is_active' => $user->is_active
                        ? "<span style='color: green;'>Active</span>"
                        : "<span style='color: red;'>Inactive</span>",
                    'is_active_value' => $user->is_active,
                ];
            });

            return $this->withDashBackground([
                'title' => "Users",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add User',
                'add_url' => '/users/create',
                'columns' => [
                    ['label' => 'UserName', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
                    ['label' => 'Company Name', 'field' => 'company_name', 'type' => 'text'],
                    ['label' => 'Phone Number', 'field' => 'phone', 'type' => 'text'],
                    ['label' => 'Role', 'field' => 'role', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'is_active', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/users/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/users/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $usersTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving user data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('users-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $roles = Role::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->display_name ?? $item->name
                ];
            });

            $billers = Biller::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $warehouses = Warehouse::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $customerGroups = CustomerGroup::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $formSchema = [
                "title" => "Add New User",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/users",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "UserName",
                        "placeholder" => "Enter user name",
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
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                    ],
                    [
                        "type" => "datagenerator",
                        "name" => "password",
                        "label" => "Password",
                        "placeholder" => "Enter password",
                        "generator_url" => "/generate-password",
                    ],
                    [
                        "type" => "text",
                        "name" => "company_name",
                        "label" => "Company Name",
                        "placeholder" => "Enter company name",
                    ],
                    [
                        "type" => "select",
                        "name" => "role_id",
                        "label" => "Role",
                        "placeholder" => "Select role",
                        "options" => $roles,
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_group_id",
                        "label" => "Customer Group",
                        "placeholder" => "Select customer group",
                        "options" => $customerGroups,
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "customer_name",
                        "label" => "Name",
                        "placeholder" => "Enter customer name",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "tax_number",
                        "label" => "Tax Number",
                        "placeholder" => "Enter tax number",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "city",
                        "label" => "City",
                        "placeholder" => "Enter city",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "state",
                        "label" => "State",
                        "placeholder" => "Enter state",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "postal_code",
                        "label" => "Postal Code",
                        "placeholder" => "Enter postal code",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "country",
                        "label" => "Country",
                        "placeholder" => "Enter country",
                        "logics" => [
                            [
                                "field" => "role_id",
                                "values" => [5],
                            ],
                        ],
                    ],
                    [
                        "type" => "select",
                        "name" => "biller_id",
                        "label" => "Biller",
                        "placeholder" => "Select biller",
                        "options" => $billers,
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "placeholder" => "Select warehouse",
                        "options" => $warehouses,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_active",
                        "label" => "Active",
                        "value" => true,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the user creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('users-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $validationRules = [
                'name' => [
                    'required',
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'phone_number' => 'nullable|string|max:255',
                'password' => 'required|string|min:6',
                'role_id' => 'required|exists:roles,id',
                'biller_id' => 'nullable|exists:billers,id',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'is_active' => 'nullable|boolean',
            ];

            if ($request->role_id == 5) {
                $validationRules['phone_number'] = [
                    'max:255',
                    Rule::unique('customers')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ];
                $validationRules['customer_name'] = 'required|string|max:255';
                $validationRules['customer_group_id'] = 'required|exists:customer_groups,id';
                $validationRules['city'] = 'required|string|max:255';
                $validationRules['address'] = 'required|string|max:255';
            }

            $request->validate($validationRules);

            $data = $request->all();
            $data['is_deleted'] = false;
            $data['is_active'] = $request->has('is_active') ? $request->is_active : false;

            $mail_setting = MailSetting::latest()->first();
            $message = 'User created successfully.';
            if ($mail_setting) {
                $this->setMailInfo($mail_setting);
                try {
                    Mail::to($data['email'])->send(new UserDetails($data));
                } catch (\Exception $e) {
                    $message = 'User created successfully. Please setup your mail setting to send mail.';
                }
            }

            $data['password'] = Hash::make($data['password']);
            $data['phone'] = $data['phone_number'];

            $userData = User::create($data);

            if ($data['role_id'] == 5) {
                $data['user_id'] = $userData->id;
                $data['name'] = $data['customer_name'];
                $data['phone_number'] = $data['phone'];
                $data['is_active'] = true;
                Customer::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'navigate_url' => '/users',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the user.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(User $user)
    {
        try {
            $authUser = Auth::user();
            $role = Role::find($authUser->role_id);

            if (!$role->hasPermissionTo('users-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $roles = Role::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->display_name ?? $item->name
                ];
            });

            $billers = Biller::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $warehouses = Warehouse::where('is_active', true)->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $formSchema = [
                "title" => "Edit User",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/users/" . $user->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter user name",
                        "value" => $user->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "email",
                        "label" => "Email",
                        "placeholder" => "Enter email address",
                        "keyboard_type" => "email",
                        "value" => $user->email,
                    ],
                    [
                        "type" => "text",
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                        "value" => $user->phone,
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
                        "placeholder" => "Select role",
                        "options" => $roles,
                        "value" => $user->role_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "biller_id",
                        "label" => "Biller",
                        "placeholder" => "Select biller",
                        "options" => $billers,
                        "value" => $user->biller_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "placeholder" => "Select warehouse",
                        "options" => $warehouses,
                        "value" => $user->warehouse_id,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_active",
                        "label" => "Active",
                        "value" => $user->is_active ? true : false,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the user edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $authUser = Auth::user();
            $role = Role::find($authUser->role_id);

            if (!$role->hasPermissionTo('users-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'name' => [
                    'required',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'phone_number' => 'nullable|string|max:255',
                'password' => 'nullable|string|min:6',
                'role_id' => 'required|exists:roles,id',
                'biller_id' => 'nullable|exists:billers,id',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'is_active' => 'nullable|boolean',
            ]);

            $data = $request->except('password', 'token');
            $data['is_active'] = $request->has('is_active') ? $request->is_active : false;
            $data['phone'] = $data['phone_number'];

            if (!empty($request->password)) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'navigate_url' => '/users',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(User $user)
    {
        try {
            $authUser = Auth::user();
            $role = Role::find($authUser->role_id);

            if (!$role->hasPermissionTo('users-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $user->update([
                'is_deleted' => true,
                'is_active' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the user.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
