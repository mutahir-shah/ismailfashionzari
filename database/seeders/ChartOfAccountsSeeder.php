<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingAccount;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Current Assets', 'account_type' => 'asset', 'is_system' => true, 'is_control_account' => false],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '1000'],
            ['code' => '1200', 'name' => 'Inventory', 'account_type' => 'asset', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '1000'],
            ['code' => '1250', 'name' => 'Input VAT', 'account_type' => 'asset', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '1000'],
            ['code' => '1300', 'name' => 'Cash and Bank', 'account_type' => 'asset', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '1000', 'is_cash_account' => true],

            // Liabilities
            ['code' => '2000', 'name' => 'Current Liabilities', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => false],
            ['code' => '2100', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '2000'],
            ['code' => '2200', 'name' => 'Tax Payable', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '2000'],
            ['code' => '2210', 'name' => 'Customer Deposits', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '2000'],
            ['code' => '2220', 'name' => 'Customer Rewards Liability', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '2000'],
            ['code' => '2250', 'name' => 'Gift Card Liability', 'account_type' => 'liability', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '2000'],

            // Equity
            ['code' => '3000', 'name' => 'Equity', 'account_type' => 'equity', 'is_system' => true, 'is_control_account' => false],
            ['code' => '3100', 'name' => 'Retained Earnings', 'account_type' => 'equity', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '3000'],
            ['code' => '3900', 'name' => 'Opening Balance Equity', 'account_type' => 'equity', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '3000'],

            // Revenue
            ['code' => '4000', 'name' => 'Revenue', 'account_type' => 'revenue', 'is_system' => true, 'is_control_account' => false],
            ['code' => '4100', 'name' => 'Sales Revenue', 'account_type' => 'revenue', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '4000'],
            ['code' => '4150', 'name' => 'Sales Returns & Allowances', 'account_type' => 'revenue', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '4000'],
            ['code' => '4160', 'name' => 'Sales Discounts', 'account_type' => 'revenue', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '4000'],
            ['code' => '4300', 'name' => 'Other Income', 'account_type' => 'revenue', 'is_system' => true, 'is_control_account' => false, 'parent_code' => '4000'],

            // COGS
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'cogs', 'is_system' => true, 'is_control_account' => true],
            ['code' => '5150', 'name' => 'Purchase Returns & Allowances', 'account_type' => 'cogs', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'Freight In', 'account_type' => 'cogs', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '5000'],
            ['code' => '5300', 'name' => 'Purchase Discount', 'account_type' => 'cogs', 'is_system' => true, 'is_control_account' => true, 'parent_code' => '5000'],

            // Expenses
            ['code' => '6000', 'name' => 'Operating Expenses', 'account_type' => 'expense', 'is_system' => true, 'is_control_account' => false],
            ['code' => '6100', 'name' => 'General Expenses', 'account_type' => 'expense', 'is_system' => true, 'is_control_account' => false, 'parent_code' => '6000'],
            ['code' => '5100', 'name' => 'Payroll Expense', 'account_type' => 'expense', 'is_system' => true, 'is_control_account' => false, 'parent_code' => '6000'],
            ['code' => '5210', 'name' => 'Loyalty Rewards Expense', 'account_type' => 'expense', 'is_system' => true, 'is_control_account' => false, 'parent_code' => '6000'],
            ['code' => '6200', 'name' => 'Payroll Expense', 'account_type' => 'expense', 'is_system' => true, 'is_control_account' => false, 'parent_code' => '6000'],
        ];

        $codeMap = [];

        foreach ($accounts as $data) {
            $parent_code = $data['parent_code'] ?? null;
            unset($data['parent_code']);

            $data['parent_id'] = $parent_code ? $codeMap[$parent_code] : null;

            $account = AccountingAccount::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            $codeMap[$data['code']] = $account->id;
        }
    }
}
