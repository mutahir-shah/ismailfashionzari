<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Account;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Services\DataRetrievalService;
use App\Traits\StaffAccess;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\APIPaginationTrait;

class ExpenseController extends Controller
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

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-index')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $query = Expense::with('warehouse', 'expenseCategory')
                ->orderBy('created_at', 'desc');
            $expenses = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            return $this->withDashBackground([
                'title' => "Expenses List",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Expense',
                'add_url' => '/expenses/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference No', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Category', 'field' => 'expense_category', 'type' => 'text'],
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
                                    'api_url' => '/expenses/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/expenses/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => ExpenseResource::collection($expenses),
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving expenses data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-add')) {
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

            $expenseCategories = $this->_dataRetrievalService->getAllExpenseCategories()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });
            $expenseCategories->prepend(['value' => 0, 'label' => 'Employee Expense']);

            $employees = \App\Models\Employee::where('is_active', true)->get()->map(function ($item) {
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
                "title" => "Add Expense",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/expenses",
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
                        "name" => "expense_category_id",
                        "label" => "Expense Category",
                        "placeholder" => "Select expense category",
                        "options" => $expenseCategories,
                        "new_screen" => "/expense-categories/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "employee_id",
                        "label" => "Employee",
                        "placeholder" => "Select employee",
                        "options" => $employees,
                        "logics" => [
                            [
                                "field" => "expense_category_id",
                                "values" => [0]
                            ]
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Type",
                        "options" => [
                            ['value' => 'expense', 'label' => 'Expense'],
                            ['value' => 'advance', 'label' => 'Advance'],
                        ],
                        "logics" => [
                            [
                                "field" => "expense_category_id",
                                "values" => [0]
                            ]
                        ]
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
                        "type" => "file",
                        "name" => "document",
                        "label" => "Attach Document",
                        "info" => "Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported",
                        "show_info_icon" => true,
                        "allowed_extensions" => ["jpg", "jpeg", "png", "gif", "pdf", "csv", "docx", "xlsx", "txt"],
                        "multiple" => false,
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
                'message' => 'An error occurred while loading the expense creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(StoreExpenseRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();

            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move(public_path('documents/expense'), $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move(public_path('documents/expense'), $documentName);
                }
                $data['document'] = $documentName;
            }

            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            } else {
                $data['created_at'] = date("Y-m-d H:i:s");
            }

            $data['reference_no'] = 'er-' . date("Ymd") . '-' . date("his");
            $data['user_id'] = Auth::id();

            $cash_register_data = CashRegister::where([
                ['user_id', $data['user_id']],
                ['warehouse_id', $data['warehouse_id']],
                ['status', true]
            ])->first();

            if ($cash_register_data) {
                $data['cash_register_id'] = $cash_register_data->id;
            }

            Expense::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'navigate_url' => '/expenses',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the expense.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(Expense $expense)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new ExpenseResource($expense),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving the expense.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $expense = Expense::findOrFail($id);

            $warehouses = $this->_dataRetrievalService->getAllWarehouses()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });

            $expenseCategories = $this->_dataRetrievalService->getAllExpenseCategories()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name
                ];
            });
            $expenseCategories->prepend(['value' => 0, 'label' => 'Employee Expense']);

            $employees = \App\Models\Employee::where('is_active', true)->get()->map(function ($item) {
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
                "title" => "Edit Expense",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/expenses/" . $expense->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "dd MMMM, yyyy",
                        "value" => date("d F, Y", strtotime($expense->created_at))
                    ],
                    [
                        "type" => "select",
                        "name" => "expense_category_id",
                        "label" => "Expense Category",
                        "placeholder" => "Select expense category",
                        "options" => $expenseCategories,
                        "value" => $expense->expense_category_id,
                        "new_screen" => "/expense-categories/create",
                    ],
                    [
                        "type" => "select",
                        "name" => "employee_id",
                        "label" => "Employee",
                        "placeholder" => "Select employee",
                        "options" => $employees,
                        "value" => $expense->employee_id,
                        "logics" => [
                            [
                                "field" => "expense_category_id",
                                "values" => [0]
                            ]
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Type",
                        "options" => [
                            ['value' => 'expense', 'label' => 'Expense'],
                            ['value' => 'advance', 'label' => 'Advance'],
                        ],
                        "value" => $expense->type,
                        "logics" => [
                            [
                                "field" => "expense_category_id",
                                "values" => [0]
                            ]
                        ]
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "placeholder" => "Select warehouse",
                        "options" => $warehouses,
                        "value" => $expense->warehouse_id,
                    ],
                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Amount",
                        "placeholder" => "Enter amount",
                        "keyboard_type" => "number",
                        "value" => $expense->amount,
                    ],
                    [
                        "type" => "select",
                        "name" => "account_id",
                        "label" => "Account",
                        "placeholder" => "Select account",
                        "options" => $accounts,
                        "value" => $expense->account_id,
                    ],
                    [
                        "type" => "file",
                        "name" => "document",
                        "label" => "Attach Document",
                        "info" => "Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported",
                        "show_info_icon" => true,
                        "allowed_extensions" => ["jpg", "jpeg", "png", "gif", "pdf", "csv", "docx", "xlsx", "txt"],
                        "multiple" => false,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note",
                        "multiline" => true,
                        "value" => $expense->note,
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the expense edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(StoreExpenseRequest $request, Expense $expense)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();

            if ($request->hasFile('document')) {
                if ($expense->document) {
                    $this->fileDelete(public_path('documents/expense/'), $expense->document);
                }

                $document = $request->file('document');
                $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
                $documentName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $documentName = $documentName . '.' . $ext;
                    $document->move(public_path('documents/expense'), $documentName);
                } else {
                    $documentName = $this->getTenantId() . '_' . $documentName . '.' . $ext;
                    $document->move(public_path('documents/expense'), $documentName);
                }
                $data['document'] = $documentName;
            }

            if (isset($data['created_at'])) {
                $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));
            }

            $expense->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'navigate_url' => '/expenses',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the expense.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(Expense $expense)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access expenses
            if (!$role->hasPermissionTo('expenses-delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the expense.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
