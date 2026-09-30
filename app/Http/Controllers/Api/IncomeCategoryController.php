<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeCategoryRequest;
use App\Http\Resources\IncomeCategoryResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\Request;
use App\Models\IncomeCategory;
use Spatie\Permission\Models\Role;
use Keygen\Keygen;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IncomeCategoryController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');

            $query = IncomeCategory::where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            $incomeCategories = $query->orderBy('id', 'desc')->get();

            return $this->withDashBackground([
                'title' => "Income Categories",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Income Category',
                'add_url' => '/income-categories/create',
                'columns' => [
                    [
                        'label' => 'Code',
                        'field' => 'code',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Name',
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
                                    'api_url' => '/income-categories/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/income-categories/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => IncomeCategoryResource::collection($incomeCategories),
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving income category data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $formSchema = [
                "title" => "Add Income Category",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/income-categories",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter code",
                        "generator_url" => "/generate/income-category-code",
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter income category name",
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the income category creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(StoreIncomeCategoryRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $data['is_active'] = true;

            IncomeCategory::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Income category created successfully.',
                'navigate_url' => '/income-categories',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the income category.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(IncomeCategory $incomeCategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new IncomeCategoryResource($incomeCategory),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving the income category.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $incomeCategory = IncomeCategory::findOrFail($id);

            $formSchema = [
                "title" => "Edit Income Category",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/income-categories/" . $incomeCategory->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter code",
                        "value" => $incomeCategory->code,
                        "generator_url" => "/generate/income-category-code",
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter income category name",
                        "value" => $incomeCategory->name,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the income category edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(StoreIncomeCategoryRequest $request, IncomeCategory $incomeCategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $incomeCategory->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Income category updated successfully.',
                'navigate_url' => '/income-categories',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the income category.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(IncomeCategory $incomeCategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $incomeCategory->is_active = false;
            $incomeCategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Income category has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the income category.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $income_category_id = $request['income_categoryIdArray'];
            foreach ($income_category_id as $id) {
                $lims_income_category_data = IncomeCategory::find($id);
                $lims_income_category_data->is_active = false;
                $lims_income_category_data->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Selected income categories deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the selected income categories.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generateCode()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the income category module
            if (!$role->hasPermissionTo('income-categories-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $code = Keygen::numeric(8)->generate();

            return response()->json([
                'success' => true,
                'data' => $code,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while generating the code.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
