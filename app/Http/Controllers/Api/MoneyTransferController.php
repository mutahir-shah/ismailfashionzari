<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\MoneyTransferResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\StoreMoneyTransferRequest;
use App\Models\MoneyTransfer;
use App\Models\Account;
use Spatie\Permission\Models\Role;
use App\Services\DataRetrievalService;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class MoneyTransferController extends Controller
{
    use \App\Traits\APIPaginationTrait;
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

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = MoneyTransfer::with(['fromAccount', 'toAccount']);

            if (!empty($search)) {
                $query->where('reference_no', 'LIKE', "%{$search}%");
            }

            $moneyTransfers = $this->resolveCollection($query->orderBy('created_at', 'desc'), $request);
            $pagination = $this->resolvePagination($query->orderBy('created_at', 'desc'), $request);

            // Format money transfers for datatable
            $moneyTransfersTable = $moneyTransfers->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'date' => date(config('date_format'), strtotime($transfer->created_at)),
                    'reference_no' => $transfer->reference_no,
                    'from_account' => $transfer->fromAccount->name ?? 'N/A',
                    'to_account' => $transfer->toAccount->name ?? 'N/A',
                    'amount' => number_format($transfer->amount, config('decimal')),
                ];
            });

            return $this->withDashBackground([
                'title' => "Money Transfers",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Money Transfer',
                'add_url' => '/money-transfers/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference No', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'From Account', 'field' => 'from_account', 'type' => 'text'],
                    ['label' => 'To Account', 'field' => 'to_account', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/money-transfers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/money-transfers/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $moneyTransfersTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving money transfer data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $accounts = $this->_dataRetrievalService->getAllAccounts()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name . ' (' . $item->account_no . ')'
                ];
            });

            $formSchema = [
                "title" => "Add Money Transfer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/money-transfers",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "from_account_id",
                        "label" => "From Account",
                        "placeholder" => "Select from account",
                        "options" => $accounts,
                    ],
                    [
                        "type" => "select",
                        "name" => "to_account_id",
                        "label" => "To Account",
                        "placeholder" => "Select to account",
                        "options" => $accounts,
                    ],
                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Amount",
                        "placeholder" => "Enter amount",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "dd MMMM, yyyy",
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the money transfer creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'from_account_id' => 'required|exists:accounts,id',
                'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
                'amount' => 'required|numeric|min:0.01',
                'created_at' => 'required|date',
            ]);

            $data = $request->all();
            $data['reference_no'] = 'mtr-' . date("Ymd") . '-' . date("his");
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            MoneyTransfer::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Money transfer created successfully.',
                'navigate_url' => '/money-transfers',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the money transfer.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(MoneyTransfer $moneyTransfer)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new MoneyTransferResource($moneyTransfer),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving the money transfer.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit(MoneyTransfer $moneyTransfer)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $accounts = $this->_dataRetrievalService->getAllAccounts()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name . ' (' . $item->account_no . ')'
                ];
            });

            $formSchema = [
                "title" => "Edit Money Transfer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/money-transfers/" . $moneyTransfer->id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "from_account_id",
                        "label" => "From Account",
                        "placeholder" => "Select from account",
                        "options" => $accounts,
                        "value" => $moneyTransfer->from_account_id,
                    ],
                    [
                        "type" => "select",
                        "name" => "to_account_id",
                        "label" => "To Account",
                        "placeholder" => "Select to account",
                        "options" => $accounts,
                        "value" => $moneyTransfer->to_account_id,
                    ],
                    [
                        "type" => "text",
                        "name" => "amount",
                        "label" => "Amount",
                        "placeholder" => "Enter amount",
                        "keyboard_type" => "number",
                        "value" => $moneyTransfer->amount,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "created_at",
                        "label" => "Date",
                        "placeholder" => "Select date",
                        "format_specifier" => "dd MMMM, yyyy",
                        "value" => date("d F, Y", strtotime($moneyTransfer->created_at)),
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the money transfer edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, MoneyTransfer $moneyTransfer)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'from_account_id' => 'required|exists:accounts,id',
                'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
                'amount' => 'required|numeric|min:0.01',
                'created_at' => 'required|date',
            ]);

            $data = $request->all();
            $data['created_at'] = date("Y-m-d H:i:s", strtotime($data['created_at']));

            $moneyTransfer->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Money transfer updated successfully.',
                'navigate_url' => '/money-transfers',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the money transfer.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(MoneyTransfer $moneyTransfer)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('money-transfer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $moneyTransfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Money transfer has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the money transfer.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
