<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\DefaultDataCollection;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessDataCollection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payroll;
use App\Models\MoneyTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Traits\APIPaginationTrait;

class AccountController extends Controller
{
    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the account module
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            // Retrieve accounts
            $query = Account::where('is_active', 1);
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('account_no', 'LIKE', "%{$search}%");
                });
            }

            $query = $query->orderBy('id', 'desc');
            $accounts = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            return $this->withDashBackground([
                "title" => "Accounts",
                'row_height' => 5,
                'add_text' => 'Add Account',
                'add_url' => '/accounts/create',
                'columns' => [
                    ['label' => 'Account No', 'field' => 'account_no', 'type' => 'text'],
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Initial Balance', 'field' => 'initial_balance', 'type' => 'text'],
                    ['label' => 'Default', 'field' => 'is_default', 'type' => 'html'],
                    ['label' => 'Note', 'field' => 'note', 'type' => 'text'],
                    ['label' => 'Actions', 'type' => 'row', 'children' => [
                        ['type' => 'button', 'label' => 'Make Default', 'action' => ['api_url' => '/make-default/{id}', 'type' => 'custom_api'], "logics" => [
                            [
                                "field" => "is_default",
                                "values" => [false, null],
                            ],
                        ],],
                        ['type' => 'action', 'icon' => 'edit', 'action' => ['api_url' => '/accounts/{id}/edit', 'type' => 'form']],
                        ['type' => 'action', 'icon' => 'delete', 'action' => ['api_url' => '/accounts/{id}', 'type' => 'delete']],
                    ]],
                ],
                'rows' => $accounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'account_no' => $account->account_no ?: 'N/A',
                        'name' => $account->name,
                        'initial_balance' => $account->initial_balance ? number_format((float)$account->initial_balance, 2) : '0.00',
                        'is_default' => $account->is_default
                            ? "<span style='color: green; font-weight: bold;'>Yes</span>"
                            : "<span style='color: red;'>No</span>",
                        'note' => $account->note ?: 'N/A',
                    ];
                }),
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving account data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Add Account",
                "submit_url" => "/accounts",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "account_no",
                        "label" => "Account No",
                        "placeholder" => "Enter account number",
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter account name",
                    ],
                    [
                        "type" => "text",
                        "name" => "initial_balance",
                        "label" => "Initial Balance",
                        "placeholder" => "Enter initial balance",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note (optional)",
                        "multiline" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the form schema.'), 500);
        }
    }

    public function store(StoreAccountRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $lims_account_data = Account::where('is_active', true)->first();
            $data = $request->all();
            if ($data['initial_balance'])
                $data['total_balance'] = $data['initial_balance'];
            else
                $data['total_balance'] = 0;
            if (!$lims_account_data)
                $data['is_default'] = 1;
            $data['is_active'] = true;

            $account = Account::create($data);
            return response()->json(new SuccessDataCollection(
                new AccountResource($account),
                'Account created successfully'
            ), 201);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while creating the account.'), 500);
        }
    }

    public function makeDefault($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to modify accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $lims_account_data = Account::where('is_default', true)->first();
            if ($lims_account_data) {
                $lims_account_data->is_default = false;
                $lims_account_data->save();
            }

            $lims_account_data = Account::find($id);
            if (!$lims_account_data || !$lims_account_data->is_active) {
                return response()->json(new ErrorResource('Account not found.'), 404);
            }

            $lims_account_data->is_default = true;
            $lims_account_data->save();

            return response()->json(new SuccessDataCollection(
                new AccountResource($lims_account_data),
                'Account set as default successfully'
            ), 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while setting default account.'), 500);
        }
    }

    public function update(StoreAccountRequest $request, Account $account)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to update accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            if (!$account->is_active) {
                return response()->json(new ErrorResource('Account not found.'), 404);
            }

            $data = $request->all();
            if ($data['initial_balance'])
                $data['total_balance'] = $data['initial_balance'];
            else
                $data['total_balance'] = 0;

            $account->update($data);

            return response()->json(new SuccessDataCollection(
                new AccountResource($account),
                'Account updated successfully'
            ), 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while updating the account.'), 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $account = Account::find($id);
            if (!$account || !$account->is_active) {
                return response()->json(new ErrorResource('Account not found.'), 404);
            }

            $formSchema = [
                "title" => "Edit Account",
                "submit_url" => "/accounts/" . $id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "account_no",
                        "label" => "Account No",
                        "placeholder" => "Enter account number",
                        "value" => $account->account_no,
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter account name",
                        "value" => $account->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "initial_balance",
                        "label" => "Initial Balance",
                        "placeholder" => "Enter initial balance",
                        "keyboard_type" => "number",
                        "value" => $account->initial_balance,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note (optional)",
                        "multiline" => true,
                        "value" => $account->note,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the form schema.'), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $account = Account::find($id);
            if (!$account || !$account->is_active) {
                return response()->json(new ErrorResource('Account not found.'), 404);
            }

            return response()->json(new SuccessDataCollection(
                new AccountResource($account),
                'Account retrieved successfully'
            ), 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the account.'), 500);
        }
    }

    public function balanceSheet(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role || !$role->hasPermissionTo('balance-sheet')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $lims_account_list = Account::where('is_active', true)->get();
            $accounts = [];

            foreach ($lims_account_list as $account) {
                $payment_received = Payment::whereNotNull('sale_id')->where('account_id', $account->id)->sum('amount');
                $payment_sent = Payment::whereNotNull('purchase_id')->where('account_id', $account->id)->sum('amount');
                $returns = DB::table('returns')->where('account_id', $account->id)->sum('grand_total');
                $return_purchase = DB::table('return_purchases')->where('account_id', $account->id)->sum('grand_total');
                $expenses = DB::table('expenses')->where('account_id', $account->id)->sum('amount');
                $payrolls = DB::table('payrolls')->where('account_id', $account->id)->sum('amount');
                $sent_money_via_transfer = MoneyTransfer::where('from_account_id', $account->id)->sum('amount');
                $received_money_via_transfer = MoneyTransfer::where('to_account_id', $account->id)->sum('amount');

                $credit_amount = $payment_received + $return_purchase + $received_money_via_transfer + $account->initial_balance;
                $debit_amount = $payment_sent + $returns + $expenses + $payrolls + $sent_money_via_transfer;
                $balance = $credit_amount - $debit_amount;

                $accounts[] = [
                    'account' => new AccountResource($account),
                    'credit' => (float) $credit_amount,
                    'debit' => (float) $debit_amount,
                    'balance' => (float) $balance,
                ];
            }

            return response()->json(new SuccessDataCollection($accounts, 'Balance sheet retrieved successfully'), 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the balance sheet.'), 500);
        }
    }

    // public function accountStatement(Request $request)
    // {
    //     $data = $request->all();

    //     $lims_account_data = Account::find($data['account_id']);
    //     if (!$lims_account_data) {
    //         return response()->json(['status' => false, 'message' => 'Account not found'], 404);
    //     }

    //     $credit_list = new Collection;
    //     $debit_list = new Collection;
    //     $expense_list = new Collection;
    //     $return_list = new Collection;
    //     $purchase_return_list = new Collection;
    //     $payroll_list = new Collection;
    //     $recieved_money_transfer_list = new Collection;
    //     $sent_money_transfer_list = new Collection;

    //     if ($data['type'] == '0' || $data['type'] == '2') {
    //         $credit_list = Payment::whereNotNull('sale_id')
    //                         ->where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('payment_reference as reference_no', 'sale_id', 'amount', 'created_at')
    //                         ->get();

    //         $recieved_money_transfer_list = MoneyTransfer::where('to_account_id', $data['account_id'])
    //                                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                                         ->select('reference_no', 'to_account_id', 'amount', 'created_at')
    //                                         ->get();

    //         $purchase_return_list = ReturnPurchase::where('account_id', $data['account_id'])
    //                                 ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                                 ->select('reference_no', 'grand_total as amount', 'created_at')
    //                                 ->get();
    //     }

    //     if ($data['type'] == '0' || $data['type'] == '1') {
    //         $debit_list = Payment::whereNotNull('purchase_id')
    //                         ->where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('payment_reference as reference_no', 'purchase_id', 'amount', 'created_at')
    //                         ->get();

    //         $expense_list = Expense::where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('reference_no', 'amount', 'created_at')
    //                         ->get();

    //         $income_list = Income::where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('reference_no', 'amount', 'created_at')
    //                         ->get();

    //         $return_list = Returns::where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('reference_no', 'grand_total as amount', 'created_at')
    //                         ->get();

    //         $payroll_list = Payroll::where('account_id', $data['account_id'])
    //                         ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                         ->select('reference_no', 'amount', 'created_at')
    //                         ->get();

    //         $sent_money_transfer_list = MoneyTransfer::where('from_account_id', $data['account_id'])
    //                                     ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
    //                                     ->select('reference_no', 'to_account_id', 'amount', 'created_at')
    //                                     ->get();
    //     }

    //     // Combine transactions
    //     $all_transaction_list = $credit_list->concat($recieved_money_transfer_list)
    //                             ->concat($debit_list)
    //                             ->concat($expense_list)
    //                             ->concat($income_list)
    //                             ->concat($return_list)
    //                             ->concat($purchase_return_list)
    //                             ->concat($payroll_list)
    //                             ->concat($sent_money_transfer_list)
    //                             ->sortByDesc('created_at');

    //     // Fetch related transactions in bulk
    //     $sale_references = Sale::whereIn('id', $all_transaction_list->pluck('sale_id')->filter())->pluck('reference_no', 'id');
    //     $purchase_references = Purchase::whereIn('id', $all_transaction_list->pluck('purchase_id')->filter())->pluck('reference_no', 'id');

    //     // Process and format transactions
    //     $balance = 0;
    //     $transactions = $all_transaction_list->map(function ($data) use (&$balance, $lims_account_data, $sale_references, $purchase_references) {
    //         $transaction_ref = $data->sale_id ? ($sale_references[$data->sale_id] ?? '') :
    //                             ($data->purchase_id ? ($purchase_references[$data->purchase_id] ?? '') : '');

    //         if (str_contains($data->reference_no, 'spr') || str_contains($data->reference_no, 'prr') ||
    //             (str_contains($data->reference_no, 'mtr') && $data->to_account_id == $lims_account_data->id)) {
    //             $balance += $data->amount;
    //             $credit = $data->amount;
    //             $debit = 0;
    //         } else {
    //             $balance -= $data->amount;
    //             $debit = $data->amount;
    //             $credit = 0;
    //         }

    //         return [
    //             'date' => $data->created_at->format('Y-m-d'),
    //             'reference_no' => $data->reference_no,
    //             'related_transaction' => $transaction_ref,
    //             'credit' => number_format($credit, 2, '.', ''),
    //             'debit' => number_format($debit, 2, '.', ''),
    //             'balance' => number_format($balance, 2, '.', ''),
    //         ];
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Account statement retrieved successfully',
    //         'account' => [
    //             'id' => $lims_account_data->id,
    //             'name' => $lims_account_data->name
    //         ],
    //         'transactions' => $transactions
    //     ], 200);
    // }

    public function accountStatement(Request $request)
    {
        $data = $request->all();

        $lims_account_data = Account::find($data['account_id']);
        $credit_list = new Collection;
        $debit_list = new Collection;
        $expense_list = new Collection;
        $return_list = new Collection;
        $purchase_return_list = new Collection;
        $payroll_list = new Collection;
        $recieved_money_transfer_list = new Collection;
        $sent_money_transfer_list = new Collection;

        if ($data['type'] == '0' || $data['type'] == '2') {
            $credit_list = Payment::whereNotNull('sale_id')
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('payment_reference as reference_no', 'sale_id', 'amount', 'created_at')
                ->get();

            $recieved_money_transfer_list = MoneyTransfer::where('to_account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'to_account_id', 'amount', 'created_at')
                ->get();

            $purchase_return_list = ReturnPurchase::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'grand_total as amount', 'created_at')
                ->get();
        }

        if ($data['type'] == '0' || $data['type'] == '1') {
            $debit_list = Payment::whereNotNull('purchase_id')
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('payment_reference as reference_no', 'purchase_id', 'amount', 'created_at')
                ->get();

            $expense_list = Expense::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'amount', 'created_at')
                ->get();

            $income_list = Income::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'amount', 'created_at')
                ->get();

            $return_list = Returns::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'grand_total as amount', 'created_at')
                ->get();

            $payroll_list = Payroll::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'amount', 'created_at')
                ->get();

            $sent_money_transfer_list = MoneyTransfer::where('from_account_id', $data['account_id'])
                ->whereBetween('created_at', [$data['start_date'], $data['end_date']])
                ->select('reference_no', 'to_account_id', 'amount', 'created_at')
                ->get();
        }

        // Merge all transactions into a single list
        $all_transaction_list = $credit_list->concat($recieved_money_transfer_list)
            ->concat($debit_list)
            ->concat($expense_list)
            ->concat($income_list)
            ->concat($return_list)
            ->concat($purchase_return_list)
            ->concat($payroll_list)
            ->concat($sent_money_transfer_list)
            ->sortByDesc('created_at');

        $balance = 0;
        $transactions = [];

        foreach ($all_transaction_list as $data) {
            $transaction = null;

            if (!empty($data->sale_id)) {
                $transaction = Sale::select('reference_no')->find($data->sale_id);
            } elseif (!empty($data->purchase_id)) {
                $transaction = Purchase::select('reference_no')->find($data->purchase_id);
            }

            if (
                str_contains($data->reference_no, 'spr') ||
                str_contains($data->reference_no, 'prr') ||
                (str_contains($data->reference_no, 'mtr') && $data->to_account_id == $lims_account_data->id)
            ) {
                $balance += $data->amount;
                $credit = $data->amount;
                $debit = 0;
            } else {
                $balance -= $data->amount;
                $debit = $data->amount;
                $credit = 0;
            }

            $transactions[] = [
                'date' => $data->created_at->format('d/m/Y'),
                'reference_no' => $data->reference_no,
                'related_transaction' => $transaction ? $transaction->reference_no : null,
                'credit' => (float)$credit,
                'debit' => (float)$debit,
                'balance' => (float)$balance,
            ];
        }

        return response()->json(new DefaultDataCollection(
            $transactions
        ));
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $lims_account_data = Account::find($id);
            if (!$lims_account_data || !$lims_account_data->is_active) {
                return response()->json(new ErrorResource('Account not found.'), 404);
            }

            if ($lims_account_data->is_default) {
                return response()->json(new ErrorResource('Please make another account default first!'), 400);
            }

            // Check if demo mode is enabled for message
            if (!env('USER_VERIFIED')) {
                return response()->json(new SuccessDataCollection([], 'This feature is disabled for demo!'));
            }

            // Deactivate the account
            $lims_account_data->is_active = false;
            $lims_account_data->save();


            return response()->json(new SuccessDataCollection([], 'Account deleted successfully!'));
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while deleting the account.'), 500);
        }
    }

    public function accountsAll()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access accounts
            if (!$role->hasPermissionTo('account-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $accounts = Account::where('is_active', true)->get();

            $accountsData = $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name . ($account->account_no ? ' (' . $account->account_no . ')' : ''),
                    'account_no' => $account->account_no,
                    'is_default' => $account->is_default,
                ];
            });

            return response()->json(new SuccessDataCollection($accountsData, 'Accounts retrieved successfully'), 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving accounts.'), 500);
        }
    }
}
