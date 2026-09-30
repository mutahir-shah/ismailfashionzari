<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DiscountPlanResource;
use App\Http\Resources\ErrorResource;
use App\Models\DiscountPlan;
use App\Models\DiscountPlanCustomer;
use App\Models\Customer;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class DiscountPlanController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discountPlans = DiscountPlan::with('customers')->orderBy('id', 'desc')->get();

            $discountPlansTable = $discountPlans->map(function ($discountPlan) {
                return [
                    'id' => $discountPlan->id,
                    'name' => $discountPlan->name,
                    'type' => ucfirst($discountPlan->type ?? 'N/A'),
                    'customers' => $discountPlan->customers->pluck('name')->implode(', ') ?: 'No customers',
                    'status' => $discountPlan->is_active
                        ? '<span style="color: green;">Active</span>'
                        : '<span style="color: red;">Inactive</span>',
                ];
            });

            return $this->withDashBackground([
                'title' => "Discount Plans",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Discount Plan',
                'add_url' => '/discount-plans/create',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Type', 'field' => 'type', 'type' => 'text'],
                    ['label' => 'Customers', 'field' => 'customers', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/discount-plans/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/discount-plans/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $discountPlansTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving discount plans data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Discount Plan",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/discount-plans",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Plan Name",
                        "placeholder" => "Enter discount plan name",
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Plan Type",
                        "options" => [
                            ["label" => "Percentage", "value" => "percentage"],
                            ["label" => "Fixed Amount", "value" => "fixed"],
                            ["label" => "Buy X Get Y", "value" => "buy_x_get_y"],
                        ],
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_id[]",
                        "label" => "Customers",
                        "options" => $customerOptions,
                        "info" => "Select customers who can use this discount plan",
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
                'message' => 'An error occurred while loading discount plan form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|in:percentage,fixed,buy_x_get_y',
                'customer_id' => 'required|array',
                'customer_id.*' => 'exists:customers,id',
            ]);

            $data = $request->all();
            $data['is_active'] = $request->has('is_active');

            $discountPlan = DiscountPlan::create($data);

            // Create customer associations
            foreach ($data['customer_id'] as $customerId) {
                DiscountPlanCustomer::create([
                    'discount_plan_id' => $discountPlan->id,
                    'customer_id' => $customerId
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discount Plan created successfully.',
                'navigate_url' => '/discount-plans'
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the discount plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(DiscountPlan $discountPlan)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new DiscountPlanResource($discountPlan->load('customers')),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving discount plan details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discountPlan = DiscountPlan::with('customers')->findOrFail($id);
            $customers = Customer::where('is_active', true)->get();
            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id,
                ];
            })->toArray();

            $selectedCustomers = $discountPlan->customers->pluck('id')->toArray();

            $formSchema = [
                "title" => "Edit Discount Plan",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/discount-plans/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Plan Name",
                        "placeholder" => "Enter discount plan name",
                        "value" => $discountPlan->name,
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Plan Type",
                        "value" => $discountPlan->type,
                        "options" => [
                            ["label" => "Percentage", "value" => "percentage"],
                            ["label" => "Fixed Amount", "value" => "fixed"],
                            ["label" => "Buy X Get Y", "value" => "buy_x_get_y"],
                        ],
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_id[]",
                        "label" => "Customers",
                        "value" => $selectedCustomers,
                        "options" => $customerOptions,
                        "info" => "Select customers who can use this discount plan",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_active",
                        "label" => "Active",
                        "value" => $discountPlan->is_active ? true : false,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading discount plan edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|in:percentage,fixed,buy_x_get_y',
                'customer_id' => 'required|array',
                'customer_id.*' => 'exists:customers,id',
            ]);

            $discountPlan = DiscountPlan::findOrFail($id);
            $data = $request->all();
            $data['is_active'] = $request->has('is_active');

            // Update customer associations
            $preCustomerIds = DiscountPlanCustomer::where('discount_plan_id', $id)->pluck('customer_id')->toArray();

            // Delete previous customer associations that are not in the new list
            foreach ($preCustomerIds as $customerId) {
                if (!in_array($customerId, $data['customer_id'])) {
                    DiscountPlanCustomer::where([
                        ['discount_plan_id', $id],
                        ['customer_id', $customerId]
                    ])->delete();
                }
            }

            // Insert new customer associations
            foreach ($data['customer_id'] as $customerId) {
                if (!in_array($customerId, $preCustomerIds)) {
                    DiscountPlanCustomer::create([
                        'discount_plan_id' => $id,
                        'customer_id' => $customerId
                    ]);
                }
            }

            $discountPlan->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Discount Plan updated successfully.',
                'navigate_url' => '/discount-plans'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the discount plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete discount plans
            if (!$role->hasPermissionTo('discount_plan')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $discountPlan = DiscountPlan::findOrFail($id);

            // Delete associated customer relationships
            DiscountPlanCustomer::where('discount_plan_id', $id)->delete();

            // Delete the discount plan
            $discountPlan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discount Plan deleted successfully.',
                'navigate_url' => '/discount-plans'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the discount plan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
