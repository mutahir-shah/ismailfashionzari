<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncomeResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreIncomeRequest;
use Illuminate\Http\Request;
use App\Models\Income;
use App\Models\Account;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Services\DataRetrievalService;
use App\Traits\StaffAccess;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Traits\APIPaginationTrait;

class IncomeController extends Controller
{
    use StaffAccess;
    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    private $_dataRetrievalService;

    public function __construct(DataRetrievalService $dataRetrievalService)
    {
        $this->_dataRetrievalService = $dataRetrievalService;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            // Retrieve incomes with proper filtering
            $query = Income::with('warehouse', 'incomeCategory')
                ->orderBy('created_at', 'desc');

            // Apply staff access check
            $this->staffAccessCheck($query);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $incomes = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            return $this->withDashBackground([
                'title' => "Incomes",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Income',
                'add_url' => '/incomes/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference No', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Income Category', 'field' => 'income_category', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Note', 'field' => 'note', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/incomes/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/incomes/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => IncomeResource::collection($incomes),
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving income data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouses = $this->_dataRetrievalService->getAllWarehouses()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $incomeCategories = $this->_dataRetrievalService->getAllIncomeCategories()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $accounts = $this->_dataRetrievalService->getAllAccounts()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $formSchema = [
                "title" => "Add Income",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/incomes",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "dd MMMM, yyyy",
                    ],
                    [
                        "type" => "select",
                        "name" => "income_category_id",
                        "label" => "Income Category",
                        "placeholder" => "Select income category",
                        "options" => $incomeCategories,
                        "new_screen" => "/income-categories/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "placeholder" => "Select warehouse",
                        "options" => $warehouses,
                    ],
                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Amount",
                        "placeholder" => "Enter amount",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "select",
                        "name" => "account_id",
                        "label" => "Account",
                        "placeholder" => "Select account",
                        "options" => $accounts,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note",
                        "multiline" => true,
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
                'message' => 'An error occurred while loading the income creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(StoreIncomeRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();

            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            } else {
                $data['created_at'] = date("Y-m-d H:i:s");
            }

            $data['reference_no'] = 'ir-' . date("Ymd") . '-' . date("his");
            $data['user_id'] = Auth::id();

            $cash_register_data = CashRegister::where([
                ['user_id', $data['user_id']],
                ['warehouse_id', $data['warehouse_id']],
                ['status', true]
            ])->first();

            if ($cash_register_data) {
                $data['cash_register_id'] = $cash_register_data->id;
            }

            Income::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Income created successfully.',
                'navigate_url' => '/incomes',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the income.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(Income $income)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new IncomeResource($income),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving the income.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $income = Income::findOrFail($id);

            $warehouses = $this->_dataRetrievalService->getAllWarehouses()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $incomeCategories = $this->_dataRetrievalService->getAllIncomeCategories()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $accounts = $this->_dataRetrievalService->getAllAccounts()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $formSchema = [
                "title" => "Edit Income",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/incomes/" . $income->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "dd MMMM, yyyy",
                        "value" => date("d F, Y", strtotime($income->created_at))
                    ],
                    [
                        "type" => "select",
                        "name" => "income_category_id",
                        "label" => "Income Category",
                        "placeholder" => "Select income category",
                        "options" => $incomeCategories,
                        "value" => $income->income_category_id,
                        "new_screen" => "/income-categories/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "placeholder" => "Select warehouse",
                        "options" => $warehouses,
                        "value" => $income->warehouse_id,
                    ],
                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Amount",
                        "placeholder" => "Enter amount",
                        "keyboard_type" => "number",
                        "value" => $income->amount,
                    ],
                    [
                        "type" => "select",
                        "name" => "account_id",
                        "label" => "Account",
                        "placeholder" => "Select account",
                        "options" => $accounts,
                        "value" => $income->account_id,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note",
                        "multiline" => true,
                        "value" => $income->note,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the income edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(StoreIncomeRequest $request, Income $income)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            $income->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Income updated successfully.',
                'navigate_url' => '/incomes',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the income.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(Income $income)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $income->delete();

            return response()->json([
                'success' => true,
                'message' => 'Income has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the income.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('incomes-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $income_id = $request['incomeIdArray'];
            foreach ($income_id as $id) {
                $lims_income_data = Income::find($id);
                $lims_income_data->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Selected incomes deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the selected incomes.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
