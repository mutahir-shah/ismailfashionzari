<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Account;
use App\Models\CashRegister;
use App\Models\IncomeCategory;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Warehouse;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RunScenario11 extends Command
{
    protected $signature = 'test:scenario11';
    protected $description = 'Run Scenario 11: Income and Expense from POS Page';

    public function handle()
    {
        Auth::loginUsingId(1); // Admin

        $account = Account::where('is_default', true)->first();
        $warehouse = Warehouse::first();
        $incomeCategory = IncomeCategory::first() ?? IncomeCategory::create(['code' => 'INC-01', 'name' => 'General Income', 'is_active' => true]);
        $expenseCategory = ExpenseCategory::first() ?? ExpenseCategory::create(['code' => 'EXP-01', 'name' => 'General Expense', 'is_active' => true]);
        
        $cashRegister = CashRegister::where('user_id', 1)->where('status', true)->first();
        if (!$cashRegister) {
            $this->error("No active cash register found. Scenario 11 requires an active cash register.");
            return;
        }

        // --- Part A: INCOME ---
        
        // Calculate dynamic account balance before Income
        $initialAccountBalance = $this->calculateAccountBalance($account->id);

        $incomeAmount = 500;
        $incomePostData = [
            'income_category_id' => $incomeCategory->id,
            'warehouse_id' => $warehouse->id,
            'amount' => $incomeAmount,
            'account_id' => $account->id,
            'note' => 'Scenario 11 POS Income',
            // IncomeController dynamically finds the active cash register
        ];

        $requestIncome = Request::create('/incomes', 'POST', $incomePostData);
        $incomeController = app(IncomeController::class);
        $incomeController->store($requestIncome);

        $afterIncomeAccountBalance = $this->calculateAccountBalance($account->id);
        $expectedAfterIncomeBalance = $initialAccountBalance + $incomeAmount;

        $this->info("## Scenario 11 Results\n");
        $this->info("### Income Verification");
        $this->line("Expected Account Balance After Income: $expectedAfterIncomeBalance");
        $this->line("Actual Account Balance After Income: $afterIncomeAccountBalance");
        $this->line("Result: " . ($expectedAfterIncomeBalance == $afterIncomeAccountBalance ? "PASS\n" : "FAIL\n"));


        // --- Part B: EXPENSE ---
        $expenseAmount = 150;
        $expensePostData = [
            'expense_category_id' => $expenseCategory->id,
            'warehouse_id' => $warehouse->id,
            'amount' => $expenseAmount,
            'account_id' => $account->id,
            'note' => 'Scenario 11 POS Expense',
            'cash_register' => $cashRegister->id, // ExpenseController pos flag
        ];

        $requestExpense = Request::create('/expenses', 'POST', $expensePostData);
        $expenseController = app(ExpenseController::class);
        $expenseController->store($requestExpense);

        $afterExpenseAccountBalance = $this->calculateAccountBalance($account->id);
        $expectedAfterExpenseBalance = $afterIncomeAccountBalance - $expenseAmount;

        $this->info("### Expense Verification");
        $this->line("Expected Account Balance After Expense: $expectedAfterExpenseBalance");
        $this->line("Actual Account Balance After Expense: $afterExpenseAccountBalance");
        $this->line("Result: " . ($expectedAfterExpenseBalance == $afterExpenseAccountBalance ? "PASS\n" : "FAIL\n"));

        // --- Cash Register Verification ---
        
        // Let's get total incomes linked to this cash register
        $registerIncomes = Income::where('cash_register_id', $cashRegister->id)->sum('amount');
        // Let's get total expenses linked to this cash register
        $registerExpenses = Expense::where('cash_register_id', $cashRegister->id)->sum('amount');
        
        $registerPaymentIn = Payment::where('cash_register_id', $cashRegister->id)->whereNotNull('sale_id')->whereNull('return_id')->sum('amount');
        $registerRefundIn = Payment::where('cash_register_id', $cashRegister->id)->whereNotNull('purchase_return_id')->sum('amount');
        $salesReceiptsTotal = $registerPaymentIn + $registerRefundIn;

        $registerPaymentOut = Payment::where('cash_register_id', $cashRegister->id)->whereNotNull('purchase_id')->sum('amount');
        $registerRefundOut = Payment::where('cash_register_id', $cashRegister->id)->whereNotNull('return_id')->sum('amount');
        $refundsTotal = $registerPaymentOut + $registerRefundOut;

        $openingCash = $cashRegister->cash_in_hand;
        $calculatedClosingCash = $openingCash + $salesReceiptsTotal + $registerIncomes - $registerExpenses - $refundsTotal;

        $this->info("### Final Validation Reconciliation Report");
        $this->line("  Opening Cash: " . $openingCash);
        $this->line("+ Sales Receipts: " . $salesReceiptsTotal);
        $this->line("+ Income: " . $registerIncomes);
        $this->line("- Expenses: " . $registerExpenses);
        $this->line("- Refunds (Returns/Purchases): " . $refundsTotal);
        $this->line("======================================");
        $this->line("Closing Cash: " . $calculatedClosingCash);

        // Verification of components
        if ($registerIncomes >= $incomeAmount && $registerExpenses >= $expenseAmount) {
            $this->info("Result: PASS");
        } else {
            $this->info("Result: FAIL (Income or Expense not recorded in register)");
        }
    }

    private function calculateAccountBalance($account_id)
    {
        $account = Account::find($account_id);
        $paymentSent = Payment::whereNotNull('purchase_id')->where('account_id', $account_id)->sum('amount');
        $paymentReceived = Payment::whereNotNull('sale_id')->whereNull('return_id')->where('account_id', $account_id)->sum('amount');
        $returnPaymentSent = Payment::whereNotNull('return_id')->where('account_id', $account_id)->sum('amount');
        $returnPurchasePaymentReceived = Payment::whereNotNull('purchase_return_id')->where('account_id', $account_id)->sum('amount');
        
        $expenses = Expense::where('account_id', $account_id)->sum('amount');
        $incomes = Income::where('account_id', $account_id)->sum('amount');
        $payrolls = \App\Models\Payroll::where('account_id', $account_id)->sum('amount');
        
        $moneyTransferSent = \App\Models\MoneyTransfer::where('from_account_id', $account_id)->sum('amount');
        $moneyTransferReceived = \App\Models\MoneyTransfer::where('to_account_id', $account_id)->sum('amount');

        return $account->initial_balance 
            + $paymentReceived 
            - $paymentSent 
            - $returnPaymentSent 
            + $returnPurchasePaymentReceived 
            - $expenses 
            + $incomes
            - $payrolls 
            - $moneyTransferSent 
            + $moneyTransferReceived;
    }
}
