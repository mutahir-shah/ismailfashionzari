<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\GiftCardResource;
use App\Http\Resources\SuccessResource;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\GiftCardRecharge;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Keygen\Keygen;
use Spatie\Permission\Models\Role;
use App\Traits\APIPaginationTrait;

class GiftCardController extends Controller
{
    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            // Retrieve gift cards
            $query = GiftCard::where('is_active', true)->with(['customer', 'user', 'creator']);

            if (!empty($search)) {
                $query->where('card_no', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    });
            }

            $query = $query->orderBy('id', 'desc');
            $giftCards = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format gift cards for table
            $giftCardsTable = $giftCards->map(function ($giftCard, $index) {
                $client = '';
                if ($giftCard->customer_id && $giftCard->customer) {
                    $client = $giftCard->customer->name;
                } elseif ($giftCard->user_id && $giftCard->user) {
                    $client = $giftCard->user->name;
                }

                $createdBy = $giftCard->creator ? $giftCard->creator->name : 'N/A';
                $balance = $giftCard->amount - $giftCard->expense;

                $expiredDateBadge = '';
                $badgeClass = 'success';
                if ($giftCard->expired_date < date("Y-m-d")) {
                    $badgeClass = 'danger';
                }
                $expiredDateBadge = '<div class="badge badge-' . $badgeClass . '">' . date('d-m-Y', strtotime($giftCard->expired_date)) . '</div>';

                return [
                    'id' => $giftCard->id,
                    'serial' => $index + 1,
                    'card_no' => $giftCard->card_no,
                    'client' => $client,
                    'amount' => number_format((float)$giftCard->amount, 2, '.', ''),
                    'expense' => number_format((float)$giftCard->expense, 2, '.', ''),
                    'balance' => number_format((float)$balance, 2, '.', ''),
                    'created_by' => $createdBy,
                    'expired_date' => $expiredDateBadge,
                ];
            });

            return $this->withDashBackground([
                'title' => "Gift Cards",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Gift Card',
                'add_url' => '/gift-cards/create',
                'columns' => [
                    ['label' => '#', 'field' => 'serial', 'type' => 'text'],
                    ['label' => 'Card No', 'field' => 'card_no', 'type' => 'text'],
                    ['label' => 'Customer/User', 'field' => 'client', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Expense', 'field' => 'expense', 'type' => 'text'],
                    ['label' => 'Balance', 'field' => 'balance', 'type' => 'text'],
                    ['label' => 'Created By', 'field' => 'created_by', 'type' => 'text'],
                    ['label' => 'Expired Date', 'field' => 'expired_date', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'view',
                                'action' => [
                                    'api_url' => '/gift-cards/{id}',
                                    'type' => 'view'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/gift-cards/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'recharge',
                                'action' => [
                                    'api_url' => '/gift-cards/{id}/recharge',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/gift-cards/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $giftCardsTable,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving gift card data.',
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $customers = Customer::where('is_active', true)->get();
            $users = User::where('is_active', true)->get();

            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name . ' (' . $customer->phone_number . ')',
                    'value' => $customer->id,
                ];
            })->toArray();

            $userOptions = $users->map(function ($user) {
                return [
                    'label' => $user->name . ' (' . $user->email . ')',
                    'value' => $user->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Gift Card",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/gift-cards",
                "method" => "POST",
                "navigate_url" => "/gift-cards",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Gift Card Information",
                        "items" => [
                            [
                                "type" => "datagenerator",
                                "name" => "card_no",
                                "label" => "Card No",
                                "placeholder" => "Enter card number",
                                "generator_url" => "/gift-cards/generate-code",
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "user_list",
                                "label" => "User List",
                                "value" => false,
                            ],
                            [
                                "type" => "select",
                                "name" => "user_id",
                                "label" => "User",
                                "options" => $userOptions,
                                "logics" => [
                                    [
                                        "field" => "user_list",
                                        "values" => [true, 1],
                                    ],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Customer",
                                "options" => $customerOptions,
                                "logics" => [
                                    [
                                        "field" => "user_list",
                                        "values" => [false, null, 0],
                                    ],
                                ],
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
     * Generate a unique card code
     */
    public function generateCode()
    {
        try {
            $code = Keygen::numeric(16)->generate();
            return response()->json($code);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while generating code.',
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'card_no' => [
                    'required',
                    'max:255',
                    Rule::unique('gift_cards')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'amount' => 'required|numeric|min:0',
                'user_id' => 'nullable|exists:users,id',
                'customer_id' => 'nullable|exists:customers,id',
                'expired_date' => 'nullable|date|after_or_equal:today',
                'user_list' => 'boolean',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            $data = $request->all();

            // Set customer or user based on user_list flag
            if ($request->input('user_list')) {
                $data['customer_id'] = null;
                if (!$request->input('user_id')) {
                    return new ErrorResource([
                        'message' => 'User is required when User List is selected.',
                    ]);
                }
            } else {
                $data['user_id'] = null;
                if (!$request->input('customer_id')) {
                    return new ErrorResource([
                        'message' => 'Customer is required when User List is not selected.',
                    ]);
                }
            }

            $data['is_active'] = true;
            $data['created_by'] = $user->id;
            $data['expense'] = 0;

            // Set default expired date if not provided
            if (!$data['expired_date']) {
                $data['expired_date'] = date('Y-m-d');
            }

            // Create gift card
            $giftCard = GiftCard::create($data);

            // Load relationships for response
            $giftCard->load(['customer', 'user', 'creator']);

            return new SuccessResource([
                'message' => 'Gift card created successfully',
                'data' => new GiftCardResource($giftCard),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the gift card.',
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)
                ->with(['customer', 'user', 'creator'])
                ->findOrFail($id);

            return new SuccessResource([
                'data' => new GiftCardResource($giftCard),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'Gift card not found.',
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)
                ->with(['customer', 'user'])
                ->findOrFail($id);

            $customers = Customer::where('is_active', true)->get();
            $users = User::where('is_active', true)->get();

            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name . ' (' . $customer->phone_number . ')',
                    'value' => $customer->id,
                ];
            })->toArray();

            $userOptions = $users->map(function ($user) {
                return [
                    'label' => $user->name . ' (' . $user->email . ')',
                    'value' => $user->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Update Gift Card",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/gift-cards/" . $id,
                "method" => "PUT",
                "navigate_url" => "/gift-cards",
                "fields" => [

                    [
                        "type" => "group",
                        "label" => "Gift Card Information",
                        "items" => [
                            [
                                "type" => "datagenerator",
                                "name" => "card_no",
                                "label" => "Card No",
                                "placeholder" => "Enter card number",
                                "value" => $giftCard->card_no,
                                "generator_url" => "/gift-cards/generate-code",
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "value" => $giftCard->amount,
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "user_list",
                                "label" => "User List",
                                "value" => $giftCard->user_id ? true : false,
                            ],
                            [
                                "type" => "select",
                                "name" => "user_id",
                                "label" => "User",
                                "options" => $userOptions,
                                "value" => $giftCard->user_id,
                                "logics" => [
                                    [
                                        "field" => "user_list",
                                        "values" => [true, 1],
                                    ],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Customer",
                                "options" => $customerOptions,
                                "value" => $giftCard->customer_id,
                                "logics" => [
                                    [
                                        "field" => "user_list",
                                        "values" => [false, null, 0],
                                    ],
                                ],
                            ],
                            [
                                "type" => "datepicker",
                                "name" => "expired_date",
                                "label" => "Expired Date",
                                "placeholder" => "Choose expired date",
                                "value" => $giftCard->expired_date,
                                "format_specifier" => "yyyy-MM-dd",
                                "starting_date" => date('Y-m-d'),
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "gift_card_id",
                        "value" => $giftCard->id,
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)->findOrFail($id);

            // Validate request data
            $validator = Validator::make($request->all(), [
                'card_no' => [
                    'required',
                    'max:255',
                    Rule::unique('gift_cards')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'amount' => 'required|numeric|min:0',
                'user_id' => 'nullable|exists:users,id',
                'customer_id' => 'nullable|exists:customers,id',
                'expired_date' => 'nullable|date',
                'user_list' => 'boolean',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            $data = $request->all();

            // Set customer or user based on user_list flag
            if ($request->input('user_list')) {
                $data['customer_id'] = null;
                if (!$request->input('user_id')) {
                    return new ErrorResource([
                        'message' => 'User is required when User List is selected.',
                    ]);
                }
            } else {
                $data['user_id'] = null;
                if (!$request->input('customer_id')) {
                    return new ErrorResource([
                        'message' => 'Customer is required when User List is not selected.',
                    ]);
                }
            }

            // Update gift card
            $giftCard->update([
                'card_no' => $data['card_no'],
                'amount' => $data['amount'],
                'user_id' => $data['user_id'],
                'customer_id' => $data['customer_id'],
                'expired_date' => $data['expired_date'],
            ]);

            // Load relationships for response
            $giftCard->load(['customer', 'user', 'creator']);

            return new SuccessResource([
                'message' => 'Gift card updated successfully',
                'data' => new GiftCardResource($giftCard),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the gift card.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for recharging the gift card.
     */
    public function rechargeForm(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)->findOrFail($id);

            $formSchema = [
                "title" => "Recharge Gift Card - " . $giftCard->card_no,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/gift-cards/" . $id . "/recharge",
                "method" => "POST",
                "navigate_url" => "/gift-cards",
                "fields" => [

                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Recharge Amount",
                        "placeholder" => "Enter recharge amount",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "hidden",
                        "name" => "gift_card_id",
                        "value" => $giftCard->id,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the recharge form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recharge a gift card
     */
    public function recharge(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)->findOrFail($id);
            $rechargeAmount = $request->input('amount');

            // Update gift card amount
            $giftCard->amount += $rechargeAmount;
            $giftCard->save();

            // Create recharge record
            GiftCardRecharge::create([
                'gift_card_id' => $giftCard->id,
                'amount' => $rechargeAmount,
                'user_id' => $user->id,
            ]);

            // Load relationships for response
            $giftCard->load(['customer', 'user', 'creator']);

            return new SuccessResource([
                'message' => 'Gift card recharged successfully',
                'data' => new GiftCardResource($giftCard),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while recharging the gift card.',
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

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCard = GiftCard::where('is_active', true)->findOrFail($id);

            // Soft delete the gift card
            $giftCard->update(['is_active' => false]);

            return new SuccessResource([
                'message' => 'Gift card deleted successfully',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the gift card.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete multiple gift cards by selection
     */
    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the gift card module
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $giftCardIds = $request->input('gift_cardIdArray', []);

            if (empty($giftCardIds)) {
                return new ErrorResource([
                    'message' => 'No gift cards selected for deletion.',
                ]);
            }

            $giftCards = GiftCard::whereIn('id', $giftCardIds)
                ->where('is_active', true)
                ->get();

            $deletedCount = $giftCards->count();

            foreach ($giftCards as $giftCard) {
                $giftCard->update(['is_active' => false]);
            }

            return new SuccessResource([
                'message' => "Deleted {$deletedCount} gift card(s) successfully",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting gift cards.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
