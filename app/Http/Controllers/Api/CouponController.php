<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\Coupon;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Keygen\Keygen;
use App\Traits\APIPaginationTrait;

class CouponController extends Controller
{

    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            $query = Coupon::where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('type', 'LIKE', "%{$search}%");
                });
            }

            $query = $query->orderBy('id', 'desc');
            $coupons = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format coupons for datatable
            $couponsTable = $coupons->map(function ($coupon) {
                $createdBy = User::find($coupon->user_id);
                $available = $coupon->quantity - $coupon->used;
                $isExpired = $coupon->expired_date < date('Y-m-d');

                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type === 'percentage'
                        ? "<span class='badge badge-info'>Percentage</span>"
                        : "<span class='badge badge-success'>Fixed Amount</span>",
                    'amount' => $coupon->amount . ($coupon->type === 'percentage' ? '%' : ''),
                    'minimum_amount' => $coupon->minimum_amount ?: 'N/A',
                    'quantity' => $coupon->quantity,
                    'available' => $isExpired
                        ? "<span class='badge badge-danger'>Expired</span>"
                        : "<span class='badge badge-primary'>" . $available . "</span>",
                    'expired_date' => $isExpired
                        ? "<span class='badge badge-danger'>" . $coupon->expired_date . "</span>"
                        : "<span class='badge badge-success'>" . $coupon->expired_date . "</span>",
                    'is_expired' => $isExpired,
                    'created_by' => $createdBy ? $createdBy->name : 'N/A',
                ];
            });

            return $this->withDashBackground([
                'title' => "Coupons",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Coupon',
                'add_url' => '/coupons/create',
                'columns' => [
                    [
                        'label' => 'Coupon Code',
                        'field' => 'code',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Type',
                        'field' => 'type',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Amount',
                        'field' => 'amount',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Minimum Amount',
                        'field' => 'minimum_amount',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Quantity',
                        'field' => 'quantity',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Available',
                        'field' => 'available',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Expired Date',
                        'field' => 'expired_date',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Created By',
                        'field' => 'created_by',
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
                                    'api_url' => '/coupons/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/coupons/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $couponsTable,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving coupon data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Coupon",
                "submit_url" => "/coupons",
                "method" => "POST",
                "navigate_url" => "/coupons",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Coupon Information",
                        "items" => [
                            [
                                "type" => "datagenerator",
                                "name" => "code",
                                "label" => "Coupon Code",
                                "placeholder" => "Enter coupon code",
                                "generator_url" => "/coupons/generate-code",
                            ],
                            [
                                "type" => "select",
                                "name" => "type",
                                "label" => "Type",
                                "options" => [
                                    ["label" => "Percentage", "value" => "percentage"],
                                    ["label" => "Fixed Amount", "value" => "fixed"],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "minimum_amount",
                                "label" => "Minimum Amount",
                                "placeholder" => "Enter minimum amount",
                                "keyboard_type" => "number",
                                "logics" => [
                                    [
                                        "field" => "type",
                                        "values" => ["fixed"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "text",
                                "name" => "quantity",
                                "label" => "Quantity",
                                "placeholder" => "Enter quantity",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "datepicker",
                                "name" => "expired_date",
                                "label" => "Expired Date",
                                "placeholder" => "Choose expired date",
                                "format_specifier" => "yyyy-MM-dd",
                                "starting_date" => date('Y-m-d'),
                            ],
                        ],
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
                'message' => 'An error occurred while creating the coupon form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generateCode()
    {
        try {
            $code = Keygen::alphanum(10)->generate();
            return response()->json(['code' => $code], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while generating coupon code.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate coupon data
            $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('coupons')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'type' => 'required|in:percentage,fixed',
                'amount' => 'required|numeric|min:0',
                'minimum_amount' => 'required_if:type,fixed|nullable|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'expired_date' => 'required|date|after_or_equal:today',
            ]);

            $data = $request->all();
            $data['used'] = 0;
            $data['user_id'] = $user->id;
            $data['is_active'] = true;

            // Set minimum amount to 0 for percentage type
            if ($data['type'] === 'percentage') {
                $data['minimum_amount'] = 0;
            }

            Coupon::create($data);

            return new SuccessResource([
                'message' => 'Coupon created successfully.',
                'navigate_url' => '/coupons'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the coupon.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $coupon = Coupon::with(['user'])->findOrFail($id);

            return new SuccessResource([
                'data' => $coupon,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving coupon details.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $coupon = Coupon::where('is_active', true)->findOrFail($id);

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Update Coupon",
                "submit_url" => "/coupons/" . $id,
                "method" => "PUT",
                "navigate_url" => "/coupons",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Coupon Information",
                        "items" => [
                            [
                                "type" => "datagenerator",
                                "name" => "code",
                                "label" => "Coupon Code",
                                "placeholder" => "Enter coupon code",
                                "value" => $coupon->code,
                                "generator_url" => "/coupons/generate-code",
                            ],
                            [
                                "type" => "select",
                                "name" => "type",
                                "label" => "Type",
                                "value" => $coupon->type,
                                "options" => [
                                    ["label" => "Percentage", "value" => "percentage"],
                                    ["label" => "Fixed Amount", "value" => "fixed"],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "minimum_amount",
                                "label" => "Minimum Amount",
                                "placeholder" => "Enter minimum amount",
                                "value" => $coupon->minimum_amount,
                                "keyboard_type" => "number",
                                "logics" => [
                                    [
                                        "field" => "type",
                                        "values" => ["fixed"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "value" => $coupon->amount,
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "text",
                                "name" => "quantity",
                                "label" => "Quantity",
                                "placeholder" => "Enter quantity",
                                "value" => $coupon->quantity,
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "datepicker",
                                "name" => "expired_date",
                                "label" => "Expired Date",
                                "placeholder" => "Choose expired date",
                                "value" => $coupon->expired_date,
                                "format_specifier" => "yyyy-MM-dd",
                                "starting_date" => date('Y-m-d'),
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "coupon_id",
                        "value" => $coupon->id,
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
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $coupon = Coupon::findOrFail($id);

            // Validate coupon data
            $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('coupons')->ignore($coupon->id)->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'type' => 'required|in:percentage,fixed',
                'amount' => 'required|numeric|min:0',
                'minimum_amount' => 'required_if:type,fixed|nullable|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'expired_date' => 'required|date',
            ]);

            $data = $request->all();

            // Set minimum amount to 0 for percentage type
            if ($data['type'] === 'percentage') {
                $data['minimum_amount'] = 0;
            }

            $coupon->update($data);

            return new SuccessResource([
                'message' => 'Coupon updated successfully.',
                'navigate_url' => '/coupons'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the coupon.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $coupon = Coupon::findOrFail($id);
            $coupon->update(['is_active' => false]);

            return new SuccessResource([
                'message' => 'Coupon deleted successfully.',
                'navigate_url' => '/coupons'
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the coupon.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access coupons
            if (!$role->hasPermissionTo('coupon')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $couponIds = $request->input('couponIdArray', []);

            if (empty($couponIds)) {
                return new ErrorResource([
                    'message' => 'No coupons selected for deletion.',
                ]);
            }

            Coupon::whereIn('id', $couponIds)->update(['is_active' => false]);

            return new SuccessResource([
                'message' => 'Selected coupons deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting selected coupons.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
