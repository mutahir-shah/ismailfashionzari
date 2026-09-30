<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DiscountResource;
use App\Http\Resources\ErrorResource;
use App\Models\Discount;
use App\Models\DiscountPlan;
use App\Models\Product;
use App\Models\DiscountPlanDiscount;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class DiscountController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discounts = Discount::with('discountPlans')->orderBy('id', 'desc')->get();

            $discountsTable = $discounts->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'applicable_for' => ucfirst($discount->applicable_for),
                    'valid_from' => $discount->valid_from,
                    'valid_till' => $discount->valid_till,
                    'value' => $discount->type === 'percentage'
                        ? $discount->value . '%'
                        : (config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($discount->value, 2)
                            : number_format($discount->value, 2) . ' ' . config('currency')),
                    'discount_plans' => $discount->discountPlans->pluck('name')->implode(', ') ?: 'N/A',
                    'status' => $discount->is_active
                        ? '<span style="color: green;">Active</span>'
                        : '<span style="color: red;">Inactive</span>',
                ];
            });

            return $this->withDashBackground([
                'title' => "Discounts",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Discount',
                'add_url' => '/discounts/create',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Applicable For', 'field' => 'applicable_for', 'type' => 'text'],
                    ['label' => 'Valid From', 'field' => 'valid_from', 'type' => 'text'],
                    ['label' => 'Valid Till', 'field' => 'valid_till', 'type' => 'text'],
                    ['label' => 'Value', 'field' => 'value', 'type' => 'text'],
                    ['label' => 'Discount Plans', 'field' => 'discount_plans', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/discounts/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/discounts/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $discountsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving discounts data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discountPlans = DiscountPlan::where('is_active', true)->get();
            $discountPlanOptions = $discountPlans->map(function ($plan) {
                return [
                    'label' => $plan->name,
                    'value' => $plan->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Discount",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/discounts",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Discount Name",
                        "placeholder" => "Enter discount name",
                    ],
                    [
                        "type" => "select",
                        "name" => "applicable_for",
                        "label" => "Applicable For",
                        "options" => [
                            ["label" => "All Products", "value" => "All"],
                            ["label" => "Specific Products", "value" => "Specific"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "product_list",
                        "label" => "Product Codes (comma separated)",
                        "placeholder" => "Enter product codes separated by comma",
                        "info" => "Required only when 'Specific Products' is selected",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "valid_from",
                        "label" => "Valid From",
                        "placeholder" => "Select start date",
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "valid_till",
                        "label" => "Valid Till",
                        "placeholder" => "Select end date",
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Discount Type",
                        "options" => [
                            ["label" => "Percentage", "value" => "percentage"],
                            ["label" => "Fixed Amount", "value" => "fixed"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "value",
                        "label" => "Discount Value",
                        "placeholder" => "Enter discount value",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "minimum_qty",
                        "label" => "Minimum Quantity",
                        "placeholder" => "Enter minimum quantity",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "maximum_qty",
                        "label" => "Maximum Quantity",
                        "placeholder" => "Enter maximum quantity",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "select",
                        "name" => "discount_plan_id[]",
                        "label" => "Discount Plans",
                        "options" => $discountPlanOptions,
                        "info" => "Select applicable discount plans",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "days",
                        "label" => "Applicable Days",
                        "placeholder" => "Enter days (0=Sunday, 1=Monday, etc.) separated by comma",
                        "info" => "Enter day numbers separated by comma (0=Sunday, 1=Monday, 2=Tuesday, etc.)",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_active",
                        "label" => "Active",
                        "value" => true,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading discount form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'applicable_for' => 'required|string|in:All,Specific',
                'valid_from' => 'required|date',
                'valid_till' => 'required|date|after_or_equal:valid_from',
                'type' => 'required|string|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'minimum_qty' => 'nullable|integer|min:0',
                'maximum_qty' => 'nullable|integer|min:0',
                'days' => 'required|string',
                'discount_plan_id' => 'required|array',
            ]);

            $data = $request->all();
            $data['valid_from'] = date('Y-m-d', strtotime($data['valid_from']));
            $data['valid_till'] = date('Y-m-d', strtotime($data['valid_till']));

            if (isset($data['product_list']) && $data['applicable_for'] === 'Specific') {
                $data['product_list'] = is_array($data['product_list']) ? implode(",", $data['product_list']) : $data['product_list'];
            } else {
                $data['product_list'] = '';
            }

            $data['days'] = is_array($data['days']) ? implode(",", $data['days']) : $data['days'];
            $data['is_active'] = $request->has('is_active');

            $discount = Discount::create($data);

            // Create discount plan associations
            foreach ($data['discount_plan_id'] as $discountPlanId) {
                DiscountPlanDiscount::create([
                    'discount_id' => $discount->id,
                    'discount_plan_id' => $discountPlanId
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discount created successfully.',
                'navigate_url' => '/discounts'
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the discount.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Discount $discount)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new DiscountResource($discount->load('discountPlans')),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving discount details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discount = Discount::with('discountPlans')->findOrFail($id);
            $discountPlans = DiscountPlan::where('is_active', true)->get();
            $discountPlanOptions = $discountPlans->map(function ($plan) {
                return [
                    'label' => $plan->name,
                    'value' => $plan->id,
                ];
            })->toArray();

            $selectedDiscountPlans = $discount->discountPlans->pluck('id')->toArray();

            $formSchema = [
                "title" => "Edit Discount",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/discounts/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Discount Name",
                        "placeholder" => "Enter discount name",
                        "value" => $discount->name,
                    ],
                    [
                        "type" => "select",
                        "name" => "applicable_for",
                        "label" => "Applicable For",
                        "value" => $discount->applicable_for,
                        "options" => [
                            ["label" => "All Products", "value" => "All"],
                            ["label" => "Specific Products", "value" => "Specific"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "product_list",
                        "label" => "Product Codes (comma separated)",
                        "placeholder" => "Enter product codes separated by comma",
                        "value" => $discount->product_list,
                        "info" => "Required only when 'Specific Products' is selected",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "valid_from",
                        "label" => "Valid From",
                        "placeholder" => "Select start date",
                        "value" => $discount->valid_from,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "valid_till",
                        "label" => "Valid Till",
                        "placeholder" => "Select end date",
                        "value" => $discount->valid_till,
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Discount Type",
                        "value" => $discount->type,
                        "options" => [
                            ["label" => "Percentage", "value" => "percentage"],
                            ["label" => "Fixed Amount", "value" => "fixed"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "value",
                        "label" => "Discount Value",
                        "placeholder" => "Enter discount value",
                        "keyboard_type" => "number",
                        "value" => $discount->value,
                    ],
                    [
                        "type" => "text",
                        "name" => "minimum_qty",
                        "label" => "Minimum Quantity",
                        "placeholder" => "Enter minimum quantity",
                        "keyboard_type" => "number",
                        "value" => $discount->minimum_qty,
                    ],
                    [
                        "type" => "text",
                        "name" => "maximum_qty",
                        "label" => "Maximum Quantity",
                        "placeholder" => "Enter maximum quantity",
                        "keyboard_type" => "number",
                        "value" => $discount->maximum_qty,
                    ],
                    [
                        "type" => "select",
                        "name" => "discount_plan_id[]",
                        "label" => "Discount Plans",
                        "value" => $selectedDiscountPlans,
                        "options" => $discountPlanOptions,
                        "info" => "Select applicable discount plans",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "days",
                        "label" => "Applicable Days",
                        "placeholder" => "Enter days (0=Sunday, 1=Monday, etc.) separated by comma",
                        "value" => $discount->days,
                        "info" => "Enter day numbers separated by comma (0=Sunday, 1=Monday, 2=Tuesday, etc.)",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_active",
                        "label" => "Active",
                        "value" => $discount->is_active ? true : false,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading discount edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'applicable_for' => 'required|string|in:All,Specific',
                'valid_from' => 'required|date',
                'valid_till' => 'required|date|after_or_equal:valid_from',
                'type' => 'required|string|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'minimum_qty' => 'nullable|integer|min:0',
                'maximum_qty' => 'nullable|integer|min:0',
                'days' => 'required|string',
                'discount_plan_id' => 'required|array',
            ]);

            $discount = Discount::findOrFail($id);
            $data = $request->all();

            $data['valid_from'] = date('Y-m-d', strtotime(str_replace("/", "-", $data['valid_from'])));
            $data['valid_till'] = date('Y-m-d', strtotime(str_replace("/", "-", $data['valid_till'])));
            $data['is_active'] = $request->has('is_active');

            if ($data['applicable_for'] == 'All') {
                $data['product_list'] = '';
            } elseif (isset($data['product_list'])) {
                $data['product_list'] = is_array($data['product_list']) ? implode(",", $data['product_list']) : $data['product_list'];
            }

            $data['days'] = is_array($data['days']) ? implode(",", $data['days']) : $data['days'];

            // Update discount plan associations
            $preDiscountPlanIds = DiscountPlanDiscount::where('discount_id', $id)->pluck('discount_plan_id')->toArray();

            // Delete previous discount plan associations that are not in the new list
            foreach ($preDiscountPlanIds as $discountPlanId) {
                if (!in_array($discountPlanId, $data['discount_plan_id'])) {
                    DiscountPlanDiscount::where([
                        ['discount_plan_id', $discountPlanId],
                        ['discount_id', $id]
                    ])->delete();
                }
            }

            // Insert new discount plan associations
            foreach ($data['discount_plan_id'] as $discountPlanId) {
                if (!in_array($discountPlanId, $preDiscountPlanIds)) {
                    DiscountPlanDiscount::create([
                        'discount_plan_id' => $discountPlanId,
                        'discount_id' => $id
                    ]);
                }
            }

            $discount->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Discount updated successfully.',
                'navigate_url' => '/discounts'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the discount.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete discounts
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discount = Discount::findOrFail($id);

            // Delete associated discount plan relationships
            DiscountPlanDiscount::where('discount_id', $id)->delete();

            // Delete the discount
            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discount deleted successfully.',
                'navigate_url' => '/discounts'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the discount.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function productSearch($code)
    {
        try {
            $product = Product::where([
                ['code', $code],
                ['is_active', true]
            ])->select('id', 'name', 'code')->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                ],
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while searching for the product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
