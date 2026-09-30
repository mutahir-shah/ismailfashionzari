<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_accounts') || !Schema::hasTable('account_mappings')) {
            return;
        }

        // Fresh installations receive this account from AccountingActivationService.
        // This migration is for already-configured installations and must not seed a
        // partial chart of accounts into an otherwise empty database.
        if (!DB::table('accounting_accounts')->exists()) {
            return;
        }

        $accountId = DB::table('accounting_accounts')->where('code', '1150')->value('id');
        if (!$accountId) {
            $accountId = DB::table('accounting_accounts')->insertGetId([
                'code' => '1150',
                'name' => 'Employee Advance Receivable',
                'account_type' => 'asset',
                'is_control_account' => true,
                'is_system' => true,
                'is_active' => true,
                'is_cash_account' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('account_mappings')->updateOrInsert(
            ['mapped_type' => 'employee_advance_receivable', 'mapped_id' => 0],
            ['accounting_account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]
        );

        if (Schema::hasTable('translations')) {
            $translations = [
                'employee_advance_receivable' => 'Employee Advance Receivable',
                'employee_advance_recovery' => 'Advance Recovery',
                'employee_advance_payment_account' => 'Payment Account (Cash/Bank)',
                'employee_advance_employee_required' => 'An employee is required for an employee advance.',
                'employee_advance_invalid_payment_account' => 'Select an active Cash or Bank payment account.',
                'employee_advance_over_recovery' => 'Advance recovery cannot exceed the employee outstanding advance.',
                'employee_advance_posting_failed' => 'Employee advance accounting could not be posted.',
                'employee_advance_reversal_failed' => 'Employee advance accounting could not be reversed.',
                'employee_advance_save_failed' => 'Employee advance could not be saved: :error',
                'employee_advance_update_failed' => 'Employee advance could not be updated: :error',
                'employee_advance_delete_failed' => 'Employee advance could not be deleted: :error',
            ];
            foreach ($translations as $key => $value) {
                DB::table('translations')->updateOrInsert(
                    ['locale' => 'en', 'group' => 'db', 'key' => $key],
                    ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('account_mappings')) {
            DB::table('account_mappings')
                ->where('mapped_type', 'employee_advance_receivable')
                ->where('mapped_id', 0)
                ->delete();
        }
        if (Schema::hasTable('accounting_accounts') && Schema::hasTable('journal_lines')) {
            $accountId = DB::table('accounting_accounts')->where('code', '1150')->value('id');
            if ($accountId && !DB::table('journal_lines')->where('accounting_account_id', $accountId)->exists()) {
                DB::table('accounting_accounts')->where('id', $accountId)->where('is_system', true)->delete();
            }
        }
    }
};
