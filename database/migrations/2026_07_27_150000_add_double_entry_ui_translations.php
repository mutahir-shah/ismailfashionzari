<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('translations')) return;
        $translations = [
            'payment_accounts_title' => 'Payment Accounts', 'payment_account_add' => 'Add Payment Account',
            'payment_account_mapping' => 'Mapped Cash/Bank Account', 'payment_account_journal_balance' => 'Journal Balance',
            'payment_account_unmapped' => 'Mapping required', 'payment_account_balance_unavailable' => 'Not available until mapped',
            'payment_account_opening_balance_guidance' => 'Opening balances are managed through the reviewed accounting activation workflow.',
            'payment_account_invalid_mapping' => 'Select an active payment account mapped to a Cash or Bank asset account.',
            'chart_of_accounts_title' => 'Chart of Accounts',
            'semantic_account_mappings_title' => 'Semantic Account Mappings',
            'accounting_health_check_payment_accounts' => 'Payment account mappings',
            'accounting_health_check_payment_accounts_description' => 'Checks payment accounts and user defaults against active Cash and Bank ledger accounts.',
            'accounting_health_check_payment_accounts_legacy' => 'Legacy accounting mode is active; journal payment mappings are not required.',
            'accounting_health_check_payment_accounts_problem' => ':count payment account or user default mapping issue(s) require attention.',
            'accounting_health_check_payment_accounts_ok' => 'Payment accounts and user defaults have valid Cash or Bank mappings.',
            'accounting_health_action_payment_accounts' => 'Review Payment Accounts',
            'accounting_health_technical_label_payment_accounts' => 'Payment account mapping issues',
        ];
        foreach ($translations as $key => $value) DB::table('translations')->updateOrInsert(
            ['locale' => 'en', 'group' => 'db', 'key' => $key],
            ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void {}
};
