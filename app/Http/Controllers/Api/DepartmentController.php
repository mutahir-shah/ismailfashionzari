<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    use ProvidesThemeBackgrounds;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Get search parameter
            $search = $request->input('search', '');

            // Retrieve departments
            $query = Department::where('is_active', true);
            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $departments = $query->orderBy('id', 'desc')->get();

            // Format departments for datatable
            $departmentsTable = $departments->map(function ($department, $index) {
                return [
                    'id' => $department->id,
                    'serial' => $index + 1,
                    'name' => $department->name,
                ];
            });

            return $this->withDashBackground([
                'title' => "Departments",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Department',
                'add_url' => '/departments/create',
                'columns' => [
                    [
                        'label' => '#',
                        'field' => 'serial',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Department',
                        'field' => 'name',
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
                                    'api_url' => '/departments/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/departments/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $departmentsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving department data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "title" => "Add Department",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/departments",
                "method" => "POST",
                "navigate_url" => "/departments",
                "fields" => [

                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Department Name",
                        "placeholder" => "Type department name",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the create form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:departments,name,NULL,id,is_active,1',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            // Create new department
            $department = Department::create([
                'name' => $request->name,
                'is_active' => true,
            ]);

            return new SuccessResource([
                'message' => 'Department created successfully',
                'data' => new DepartmentResource($department),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the department.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $department = Department::where('is_active', true)->findOrFail($id);

            return new SuccessResource([
                'data' => new DepartmentResource($department),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'Department not found.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $department = Department::where('is_active', true)->findOrFail($id);

            $formSchema = [
                "title" => "Update Department",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/departments/" . $id,
                "method" => "PUT",
                "navigate_url" => "/departments",
                "fields" => [

                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Department Name",
                        "placeholder" => "Type department name",
                        "value" => $department->name,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "department_id",
                        "value" => $department->id,
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $department = Department::where('is_active', true)->findOrFail($id);

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:departments,name,' . $id . ',id,is_active,1',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            // Update department
            $department->update([
                'name' => $request->name,
            ]);

            return new SuccessResource([
                'message' => 'Department updated successfully',
                'data' => new DepartmentResource($department),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the department.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $department = Department::where('is_active', true)->findOrFail($id);

            // Check if department has employees
            if ($department->employees()->count() > 0) {
                return new ErrorResource([
                    'message' => 'Cannot delete department. It has employees assigned to it.',
                ]);
            }

            // Soft delete the department
            $department->update(['is_active' => false]);

            return new SuccessResource([
                'message' => 'Department deleted successfully',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the department.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete multiple departments by selection
     */
    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the department module
            if (!$role->hasPermissionTo('department')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $departmentIds = $request->input('departmentIdArray', []);

            if (empty($departmentIds)) {
                return new ErrorResource([
                    'message' => 'No departments selected for deletion.',
                ]);
            }

            $departments = Department::whereIn('id', $departmentIds)
                ->where('is_active', true)
                ->get();

            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($departments as $department) {
                // Check if department has employees
                if ($department->employees()->count() > 0) {
                    $skippedCount++;
                    continue;
                }

                // Soft delete the department
                $department->update(['is_active' => false]);
                $deletedCount++;
            }

            $message = "Deleted {$deletedCount} department(s) successfully";
            if ($skippedCount > 0) {
                $message .= ". Skipped {$skippedCount} department(s) with assigned employees.";
            }

            return new SuccessResource([
                'message' => $message,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting departments.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
