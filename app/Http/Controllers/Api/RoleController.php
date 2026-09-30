<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\Roles;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles (only Admin and Owner)
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            $query = Roles::where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $roles = $query->orderBy('id', 'asc')->get();

            // Format roles for datatable
            // Format roles for datatable (list schema)
            $rolesTable = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description ?: 'N/A',
                    'is_system' => ($role->id <= 2 || $role->id == 5) ? true : false,
                ];
            });

            return $this->withDashBackground([
                'title' => "Roles",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 3,
                'add_text' => 'Add Role',
                'add_url' => '/roles/create',
                'columns' => [
                    [
                        'label' => 'Name',
                        'field' => 'name',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Description',
                        'field' => 'description',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/roles/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'permissions',
                                'action' => [
                                    'api_url' => '/roles/{id}/permissions',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/roles/{id}',
                                    'type' => 'delete'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'is_system',
                                        'values' => [false],
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
                'rows' => $rolesTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving role data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "title" => "Add Role",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/roles",
                "method" => "POST",
                "navigate_url" => "/roles",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Role Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Role Name",
                                "placeholder" => "Enter role name",
                            ],
                            [
                                "type" => "text",
                                "name" => "description",
                                "label" => "Description",
                                "placeholder" => "Enter role description",
                                "multiline" => true,
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "guard_name",
                        "value" => "web",
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => true,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the role form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate role data
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'description' => 'nullable|string|max:1000',
                'guard_name' => 'required|string',
            ]);

            $data = $request->all();
            $data['is_active'] = true;

            Roles::create($data);

            return new SuccessResource([
                'message' => 'Role created successfully.',
                'navigate_url' => '/roles'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the role.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $role = Roles::findOrFail($id);

            return new SuccessResource([
                'data' => $role,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving role details.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $role = Roles::where('is_active', true)->findOrFail($id);

            $formSchema = [
                "title" => "Update Role",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/roles/" . $id,
                "method" => "PUT",
                "navigate_url" => "/roles",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Role Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Role Name",
                                "placeholder" => "Enter role name",
                                "value" => $role->name,
                            ],
                            [
                                "type" => "text",
                                "name" => "description",
                                "label" => "Description",
                                "placeholder" => "Enter role description",
                                "value" => $role->description,
                                "multiline" => true,
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "role_id",
                        "value" => $role->id,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $role = Roles::findOrFail($id);

            // Validate role data
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles')->ignore($role->id)->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'description' => 'nullable|string|max:1000',
            ]);

            $data = $request->only(['name', 'description']);
            $role->update($data);

            return new SuccessResource([
                'message' => 'Role updated successfully.',
                'navigate_url' => '/roles'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the role.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();

            // Check if the user has permission to access roles
            if ($user->role_id > 2) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $role = Roles::findOrFail($id);

            // Prevent deletion of system roles (Admin: 1, Owner: 2, Customer: 5)
            if ($role->id <= 2 || $role->id == 5) {
                return new ErrorResource([
                    'message' => 'System roles cannot be deleted.',
                ]);
            }

            $role->update(['is_active' => false]);

            return new SuccessResource([
                'message' => 'Role deleted successfully.',
                'navigate_url' => '/roles'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the role.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
