<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payroll;
use App\Models\MoneyTransfer;
use App\Models\Purchase;
use App\Models\Sale;
use DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use Carbon\Carbon;
use App\Services\AccountingService;
use App\Services\AccountingModeService;
use App\Services\PaymentAccountService;

class AccountsController extends Controller
{
    public function __construct(public AccountingService $accountingService, public AccountingModeService $accountingMode, public PaymentAccountService $paymentAccounts)
    {
    }

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('account-index')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $lims_account_all = Account::where('is_active', true)->get();
        $doubleEntryAccounting = $this->accountingMode->isDoubleEntryAuthoritative();

        if ($doubleEntryAccounting) {
            $this->paymentAccounts->decorate($lims_account_all);
            return view('backend.account.index', $this->accountIndexViewData($lims_account_all, $doubleEntryAccounting));
        }

        foreach ($lims_account_all as $account) {

            // -------------------
            // CREDIT
            // -------------------
            $payment_received = Payment::whereNotNull('sale_id')
                ->whereNull('return_id')
                ->where('account_id', $account->id)
                ->sum('amount');

            $purchase_return = $this->purchaseReturnPaymentQuery()
                ->where('account_id', $account->id)
                ->sum('amount');

            $recieved_money_via_transfer = MoneyTransfer::where('to_account_id', $account->id)
                ->sum('amount');

            $income = Income::where('account_id', $account->id)
                ->sum('amount');

            $credit = $payment_received + $purchase_return + $recieved_money_via_transfer + ($account->initial_balance ?? 0) + $income;

            // -------------------
            // DEBIT
            // -------------------
            // Sales Return → due adjust + refund logic
            $refund = Payment::whereNotNull('sale_id')
                ->whereNotNull('return_id')
                ->where('account_id', $account->id)
                ->sum('amount');

            $payment_sent = Payment::whereNotNull('purchase_id')
                ->whereNull('return_id')
                ->whereNull('purchase_return_id')
                ->where('account_id', $account->id)
                ->sum('amount');

            $expenses = DB::table('expenses')
                ->where('account_id', $account->id)
                ->sum('amount');

            $payrolls = DB::table('payrolls')
                ->where('account_id', $account->id)
                ->sum('amount');

            $sent_money_via_transfer = MoneyTransfer::where('from_account_id', $account->id)
                ->sum('amount');

            $debit = $refund + $payment_sent + $expenses + $payrolls + $sent_money_via_transfer;

            // -------------------
            // FINAL BALANCE
            // -------------------
            $account->balance = $credit - $debit;
        }

        return view('backend.account.index', $this->accountIndexViewData($lims_account_all, $doubleEntryAccounting));
    }

    private function accountIndexViewData(Collection $lims_account_all, bool $doubleEntryAccounting): array
    {
        return [
            'lims_account_all' => $lims_account_all,
            'doubleEntryAccounting' => $doubleEntryAccounting,
        ];
    }



    public function store(Request $request)
    {
        $this->validate($request, [
            'account_no' => [
                'max:255',
                Rule::unique('accounts')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $lims_account_data = Account::where('is_active', true)->first();
        $data = $request->all();
        $data['initial_balance'] = $this->accountingMode->isDoubleEntryAuthoritative() ? 0 : ($data['initial_balance'] ?? 0);
        // Keep the legacy NOT NULL column populated while account balances are calculated dynamically.
        $data['total_balance'] = $data['initial_balance'];

        if (!$lims_account_data)
            $data['is_default'] = 1;
        $data['is_active'] = true;
        $account = Account::create($data);

        if ($this->accountingMode->isDoubleEntryAuthoritative()) {
            $this->paymentAccounts->ensureMapping($account);
        }

        if ($account->initial_balance > 0) {
            $this->accountingService->recordAccountOpeningBalance($account);
        }

        return redirect('accounts')->with('message', __('db.Account created successfully'));
    }

    public function makeDefault($id)
    {
        if ($this->accountingMode->isDoubleEntryAuthoritative()) $this->paymentAccounts->assertValidId((int) $id);
        $lims_account_data = Account::where('is_default', true)->first();
        $lims_account_data->is_default = false;
        $lims_account_data->save();

        $lims_account_data = Account::find($id);
        $lims_account_data->is_default = true;
        $lims_account_data->save();

        return 'Account set as default successfully';
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'account_no' => [
                'max:255',
                Rule::unique('accounts')->ignore($request->account_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        $account = Account::find($data['account_id']);
        if ($this->accountingMode->isDoubleEntryAuthoritative()) unset($data['initial_balance'], $data['total_balance']);
        // total_balance logic deprecated

        $old_initial_balance = $account->initial_balance;
        
        $account->update($data);

        if ($old_initial_balance != $account->initial_balance) {
            $this->accountingService->reverseTransaction(get_class($account), $account->id);
            if ($account->initial_balance > 0) {
                $this->accountingService->recordAccountOpeningBalance($account, 'account_opening_balance_updated');
            }
        }

        return redirect('accounts')->with('message', __('db.Account updated successfully'));
    }

    public function balanceSheet()
    {
        if ($this->accountingMode->isDoubleEntryAuthoritative()) return redirect()->route('accounting.balanceSheet');
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('balance-sheet')) {
            $lims_account_list = Account::where('is_active', true)->get();
            $debit = [];
            $credit = [];
            foreach ($lims_account_list as $account) {
                $payment_recieved = Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account->id)->sum('amount');
                $payment_sent = Payment::whereNotNull('purchase_id')->whereNull('return_id')->whereNull('purchase_return_id')->where('account_id', $account->id)->sum('amount');
                $returns = Payment::whereNotNull('sale_id')->whereNotNull('return_id')->where('account_id', $account->id)->sum('amount');
                $return_purchase = $this->purchaseReturnPaymentQuery()->where('account_id', $account->id)->sum('amount');
                $expenses = DB::table('expenses')->where('account_id', $account->id)->sum('amount');
                $payrolls = DB::table('payrolls')->where('account_id', $account->id)->sum('amount');
                $sent_money_via_transfer = MoneyTransfer::where('from_account_id', $account->id)->sum('amount');
                $recieved_money_via_transfer = MoneyTransfer::where('to_account_id', $account->id)->sum('amount');

                $credit[] = $payment_recieved + $return_purchase + $recieved_money_via_transfer + $account->initial_balance;
                $debit[] = $payment_sent + $returns + $expenses + $payrolls + $sent_money_via_transfer;
            }
            return view('backend.account.balance_sheet', compact('lims_account_list', 'debit', 'credit'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function accountStatement(Request $request)
    {
        if ($this->accountingMode->isDoubleEntryAuthoritative()) return redirect()->route('accounting.generalLedger');
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'in:0,1,2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $lims_account_data = Account::where('is_active', true)->find($data['account_id']);
        if (!$lims_account_data) {
            return redirect('accounts')->with('not_permitted', __('db.account_not_found'));
        }
        $initial_balance =  $lims_account_data;

        $start_date = Carbon::parse($data['start_date'])->startOfDay();
        $end_date   = Carbon::parse($data['end_date'])->endOfDay();
        $type = $data['type'] ?? '0';

        $opening_balance = $this->accountOpeningBalanceBefore($lims_account_data, $start_date);
        $balance = $opening_balance;

        $account_statement_array = collect();

        // -----------------------------
        // CREDIT TRANSACTIONS
        // -----------------------------
        if ($type == '0' || $type == '2') {

            // Sale Payment
            $sale_payments = Payment::whereNotNull('sale_id')
                ->whereNull('return_id')
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'credit';
                    return $item;
                });

            // Money Received
            $money_received = MoneyTransfer::where('to_account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'credit';
                    return $item;
                });

            // Purchase Return
            $purchase_return = $this->purchaseReturnPaymentQuery()
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'credit';
                    return $item;
                });

            // Income
            $income = Income::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'credit';
                    return $item;
                });

            $account_statement_array = $account_statement_array
                ->concat($sale_payments)
                ->concat($money_received)
                ->concat($purchase_return)
                ->concat($income);
        }

        // -----------------------------
        // DEBIT TRANSACTIONS
        // -----------------------------
        if ($type == '0' || $type == '1') {

            // Purchase Payment
            $purchase_payment = Payment::whereNotNull('purchase_id')
                ->whereNull('return_id')
                ->whereNull('purchase_return_id')
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'debit';
                    return $item;
                });

            // Expenses
            $expense = Expense::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'debit';
                    return $item;
                });

            // Payroll
            $payroll = Payroll::where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'debit';
                    return $item;
                });

            // Money Sent
            $money_sent = MoneyTransfer::where('from_account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'debit';
                    return $item;
                });

            // Sales Return → DEBIT calculation
            $sales_returns = Payment::whereNotNull('sale_id')
                ->whereNotNull('return_id')
                ->where('account_id', $data['account_id'])
                ->whereBetween('created_at', [$start_date, $end_date])
                ->get()
                ->map(function ($item) {
                    $item->type = 'debit';
                    return $item;
                });

            $account_statement_array = $account_statement_array
                ->concat($purchase_payment)
                ->concat($expense)
                ->concat($payroll)
                ->concat($money_sent)
                ->concat($sales_returns);
        }

        // -----------------------------
        // Sort by created_at ASC
        // -----------------------------
        $account_statement_array = $account_statement_array->sortBy('created_at')->values();

        // -----------------------------
        // Build balance for each row
        // -----------------------------
        $balance_tracker = $balance;
        $final_array = [];
        foreach ($account_statement_array as $data) {
            $credit = $data->type == 'credit' ? $data->amount : 0;
            $debit  = $data->type == 'debit' ? $data->amount : 0;

            $balance_tracker += $credit;
            $balance_tracker -= $debit;

            $transaction_ref = '';
            if (isset($data->sale_id)) {
                $transaction_ref = Sale::where('id', $data->sale_id)->value('reference_no');
            } elseif (isset($data->purchase_id)) {
                $transaction_ref = Purchase::where('id', $data->purchase_id)->value('reference_no');
            }

            $final_array[] = [
                $data->created_at,
                $data->reference_no ?? $data->payment_reference ?? '',
                $transaction_ref,
                $credit,
                $debit,
                $balance_tracker
            ];
        }
        return view('backend.account.account_statement', compact(
            'lims_account_data',
            'final_array',
            'balance_tracker',
            'initial_balance',
            'opening_balance'
        ));
    }

    private function accountOpeningBalanceBefore(Account $account, Carbon $startDate): float
    {
        $paymentReceived = Payment::whereNotNull('sale_id')
            ->whereNull('return_id')
            ->where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $purchaseReturn = $this->purchaseReturnPaymentQuery()
            ->where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $receivedMoneyViaTransfer = MoneyTransfer::where('to_account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $income = Income::where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $refund = Payment::whereNotNull('sale_id')
            ->whereNotNull('return_id')
            ->where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $paymentSent = Payment::whereNotNull('purchase_id')
            ->whereNull('return_id')
            ->whereNull('purchase_return_id')
            ->where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $expenses = Expense::where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $payrolls = Payroll::where('account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $sentMoneyViaTransfer = MoneyTransfer::where('from_account_id', $account->id)
            ->where('created_at', '<', $startDate)
            ->sum('amount');

        $credit = $paymentReceived + $purchaseReturn + $receivedMoneyViaTransfer + ($account->initial_balance ?? 0) + $income;
        $debit = $refund + $paymentSent + $expenses + $payrolls + $sentMoneyViaTransfer;

        return (float) ($credit - $debit);
    }


    private function purchaseReturnPaymentQuery()
    {
        return Payment::whereNotNull('purchase_id')
            ->where(function ($query) {
                $query->whereNotNull('purchase_return_id')
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->whereNotNull('return_id')
                            ->whereNull('sale_id');
                    });
            });
    }



    public function destroy($id)
    {
        if (!config('app.user_verified'))
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        $lims_account_data = Account::find($id);
        if (!$lims_account_data->is_default) {
            $lims_account_data->is_active = false;
            $lims_account_data->save();
            return redirect('accounts')->with('not_permitted', __('db.Account deleted successfully!'));
        } else
            return redirect('accounts')->with('not_permitted', __('db.Please make another account default first!'));
    }

    public function accountsAll()
    {
        $lims_account_list = DB::table('accounts')->where('is_active', true)->get();

        $html = '';
        foreach ($lims_account_list as $account) {
            if ($account->is_default == 1) {
                $html .= '<option selected value="' . $account->id . '">' . $account->name . ' (' . $account->account_no . ')' . '</option>';
            } else {
                $html .= '<option value="' . $account->id . '">' . $account->name . ' (' . $account->account_no . ')' . '</option>';
            }
        }

        return response()->json($html);
    }
}
