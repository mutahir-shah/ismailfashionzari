<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ExpenseCategoryResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('id', 'desc')->get();

            return $this->withDashBackground([
                'title' => "Expense Categories",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Expense Category',
                'add_url' => '/expensecategories/create',
                'columns' => [
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/expensecategories/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/expensecategories/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => ExpenseCategoryResource::collection($expenseCategories),
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving expense categories data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateCode()
    {
        try {
            $id = rand(10000000, 99999999); // Generate 8-digit random number
            return response()->json([
                'success' => true,
                'code' => $id,
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while generating code.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $formSchema = [
                "title" => "Add Expense Category",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/expense-categories",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter code",
                        "generator_url" => "/generate-code"
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter category name",
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
                'success' => false,
                'message' => 'An error occurred while loading expense category form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create expense categories
            if (!$role->hasPermissionTo('expense_category')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $data['is_active'] = $request->has('is_active') ? true : false;

            ExpenseCategory::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense Category created successfully.',
                'navigate_url' => '/expensecategories',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the expense category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(ExpenseCategory $expensecategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view expense categories
            if (!$role->hasPermissionTo('expense_category')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new ExpenseCategoryResource($expensecategory),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving expense category details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit expense categories
            if (!$role->hasPermissionTo('expense_category')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $expensecategory = ExpenseCategory::findOrFail($id);

            $formSchema = [
                "title" => "Edit " . $expensecategory->name,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/expensecategories/$id",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "datagenerator",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter code",
                        "value" => $expensecategory->code,
                        "generator_url" => "/generate-code",
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter category name",
                        "value" => $expensecategory->name,
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
                'success' => false,
                'message' => 'An error occurred while loading expense category edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $expensecategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit expense categories
            if (!$role->hasPermissionTo('expense_category')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $expensecategory->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense Category updated successfully.',
                'navigate_url' => '/expensecategories',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the expense category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ExpenseCategory $expensecategory)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete expense categories
            if (!$role->hasPermissionTo('expense_category')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $expensecategory->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Expense Category deleted successfully.',
                'navigate_url' => '/expensecategories',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the expense category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteBySelection(Request $request)
    {
        $expense_category_id = $request['expense_categoryIdArray'];
        foreach ($expense_category_id as $id) {
            $lims_expense_category_data = ExpenseCategory::find($id);
            $lims_expense_category_data->is_active = false;
            $lims_expense_category_data->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Data has been deleted successfully.',
            'navigate_url' => '/expensecategories',
        ], 200);
    }
}
