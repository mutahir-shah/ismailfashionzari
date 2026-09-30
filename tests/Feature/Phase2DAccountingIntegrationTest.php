<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payroll;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Employee;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class Phase2DAccountingIntegrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Since we are using RefreshDatabase, we need to create required data
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin'], ['id' => 1, 'is_active' => true]);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false
        ]);
        $this->actingAs($user);
        
        \App\Models\Warehouse::create([
            'name' => 'Test Warehouse',
            'phone' => '123456',
            'email' => 'warehouse@test.com',
            'address' => 'Test',
            'is_active' => true
        ]);
        
        Account::create([
            'account_no' => '1300',
            'name' => 'Cash',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'Cash Account',
            'is_active' => true,
        ]);
        
        Account::create([
            'account_no' => '3900',
            'name' => 'Opening Balance Equity',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'Equity',
            'is_active' => true,
        ]);
        
        Account::create([
            'account_no' => '2100',
            'name' => 'Accounts Payable',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'AP',
            'is_active' => true,
        ]);
        
        Account::create([
            'account_no' => '1200',
            'name' => 'Accounts Receivable',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'AR',
            'is_active' => true,
        ]);

        Account::create([
            'account_no' => '5100',
            'name' => 'Payroll Expense',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'Payroll',
            'is_active' => true,
        ]);
        
        ExpenseCategory::create([
            'code' => 'EXP1',
            'name' => 'Test Expense',
            'is_active' => true
        ]);
        
        IncomeCategory::create([
            'code' => 'INC1',
            'name' => 'Test Income',
            'is_active' => true
        ]);
        
        \App\Models\CustomerGroup::create([
            'name' => 'General',
            'percentage' => 0,
            'is_active' => true
        ]);
    }

    public function test_expense_creation_records_journal()
    {
        $cashAccount = Account::where('account_no', '1300')->first();
        $expenseCategory = ExpenseCategory::first();
        
        $response = $this->post('/expenses', [
            'expense_category_id' => $expenseCategory->id,
            'warehouse_id' => 1,
            'amount' => 500,
            'account_id' => $cashAccount->id,
            'note' => 'Test Expense'
        ]);

        $response->assertSessionHas('message');

        $expense = Expense::where('amount', 500)->first();
        $this->assertNotNull($expense);
        $this->assertEquals('posted', $expense->accounting_status);

        $journal = JournalEntry::where('source_type', Expense::class)
            ->where('source_id', $expense->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('expense_created', $journal->event_type);
        $this->assertCount(2, $journal->lines);
    }

    public function test_income_creation_records_journal()
    {
        $cashAccount = Account::where('account_no', '1300')->first();
        $incomeCategory = IncomeCategory::first();
        
        $response = $this->post('/incomes', [
            'income_category_id' => $incomeCategory->id,
            'warehouse_id' => 1,
            'amount' => 1000,
            'account_id' => $cashAccount->id,
            'note' => 'Test Income'
        ]);

        $response->assertSessionHas('message');

        $income = Income::where('amount', 1000)->first();
        $this->assertNotNull($income);
        $this->assertEquals('posted', $income->accounting_status);

        $journal = JournalEntry::where('source_type', Income::class)
            ->where('source_id', $income->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('income_created', $journal->event_type);
    }

    public function test_payroll_creation_records_journal()
    {
        $cashAccount = Account::where('account_no', '1300')->first();
        $employee = Employee::first();
        if (!$employee) {
            $employee = Employee::create([
                'name' => 'Test Employee',
                'email' => 'test@test.com',
                'phone_number' => '123456',
                'department_id' => 1,
                'is_active' => true
            ]);
        }
        
        $response = $this->post('/payroll', [
            'employee_id' => $employee->id,
            'account_id' => $cashAccount->id,
            'amount' => 1500,
            'paying_method' => 'Cash',
            'note' => 'Test Payroll'
        ]);

        $payroll = Payroll::where('amount', 1500)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals('posted', $payroll->accounting_status);

        $journal = JournalEntry::where('source_type', Payroll::class)
            ->where('source_id', $payroll->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('payroll_created', $journal->event_type);
    }

    public function test_customer_opening_balance_records_journal()
    {
        $response = $this->post('/customer', [
            'customer_group_id' => 1,
            'name' => 'Test Customer',
            'company_name' => 'Test Co',
            'email' => 'test_customer@test.com',
            'phone_number' => '1234567890',
            'address' => 'Test Address',
            'city' => 'Test City',
            'opening_balance' => 2000,
        ]);

        $customer = Customer::where('name', 'Test Customer')->first();
        $this->assertNotNull($customer);
        
        $journal = JournalEntry::where('source_type', Customer::class)
            ->where('source_id', $customer->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('customer_opening_balance_created', $journal->event_type);
        
        // Ensure A/R debit and Equity credit
        $debit = $journal->lines->where('type', 'debit')->first();
        $this->assertEquals(2000, $debit->amount);
    }

    public function test_supplier_opening_balance_records_journal()
    {
        $response = $this->post('/supplier', [
            'name' => 'Test Supplier',
            'company_name' => 'Supplier Co',
            'email' => 'supplier@test.com',
            'phone_number' => '0987654321',
            'address' => 'Test Address',
            'city' => 'Test City',
            'opening_balance' => 3000,
        ]);

        $supplier = Supplier::where('name', 'Test Supplier')->first();
        $this->assertNotNull($supplier);

        $journal = JournalEntry::where('source_type', Supplier::class)
            ->where('source_id', $supplier->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('supplier_opening_balance_created', $journal->event_type);
    }
}
