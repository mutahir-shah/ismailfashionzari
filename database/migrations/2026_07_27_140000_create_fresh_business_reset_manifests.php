<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fresh_business_reset_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('reset_type')->default('fresh_business');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('application_version')->nullable();
            $table->string('database_identifier_hash', 64);
            $table->json('affected_tables');
            $table->json('before_counts');
            $table->json('after_counts');
            $table->json('balances_before');
            $table->json('balances_after');
            $table->json('activation_before')->nullable();
            $table->json('activation_after')->nullable();
            $table->timestamp('reset_at');
            $table->timestamps();
        });

        if (Schema::hasTable('translations')) {
            $translations = [
                'operational_clear_title' => 'Clear Operational Data (Preserves Accounts)',
                'operational_clear_confirmation' => 'This legacy operation removes operational records but preserves payment accounts and their balances. Continue?',
                'operational_clear_success' => 'Operational data was cleared. Preserved account balances were not reset.',
                'fresh_reset_title' => 'Fresh Business Reset',
                'fresh_reset_warning_title' => 'Permanent business-history removal',
                'fresh_reset_warning' => 'This operation removes business history, resets stock, and resets every preserved financial balance to zero.',
                'fresh_reset_preserve_notice' => 'Users, security, settings, business masters, payment-account identities, Chart of Accounts, and semantic mappings are preserved.',
                'fresh_reset_preview' => 'Reset Preview',
                'fresh_reset_sales' => 'Sales records to remove', 'fresh_reset_purchases' => 'Purchase records to remove',
                'fresh_reset_payments' => 'Payment records to remove', 'fresh_reset_journals' => 'Accounting journals to remove',
                'fresh_reset_products' => 'Products retained with zero stock',
                'fresh_reset_stock_quantity' => 'Current stock quantity to reset',
                'fresh_reset_cash_bank' => 'Current payment-account balance to reset',
                'fresh_reset_payroll' => 'Payroll records to remove',
                'fresh_reset_employee_advances' => 'Employee advance records to remove',
                'fresh_reset_confirmation_label' => 'Type RESET to permanently reset this business',
                'fresh_reset_execute' => 'Reset Business to Zero', 'fresh_reset_manifest_history' => 'Reset Audit History',
                'fresh_reset_manifest_operator' => 'Operator', 'fresh_reset_manifest_tables' => 'affected tables',
                'fresh_reset_success' => 'Fresh Business Reset completed. Business financial state is zero.',
                'fresh_reset_failed' => 'Fresh Business Reset failed. No partial reset was committed.',
                'accounting_opening_balances_review_confirmation' => 'I reviewed these opening balances and explicitly approve their posting.',
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
        Schema::dropIfExists('fresh_business_reset_manifests');
    }
};
