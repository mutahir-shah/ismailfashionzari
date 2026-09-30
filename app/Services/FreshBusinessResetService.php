<?php

namespace App\Services;

use App\Models\FreshBusinessResetManifest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FreshBusinessResetService
{
    /**
     * Business tables cleared on every Reset Business Data run.
     *
     * Order matters: children before parents to satisfy FK constraints
     * during the DELETE pass (FK checks are disabled, but the explicit order
     * also documents the logical grouping clearly).
     *
     * ADDING A NEW MODULE: append its transaction tables here.
     * Do NOT rely on any whitelist/blacklist mechanism.
     */
    private const CLEAR_TABLES = [
        // ── Accounting ledger ──────────────────────────────────────────
        'journal_lines',
        'journal_entries',
        'accounting_sync_queue',
        'accounting_activation_sessions',
        'accounting_periods',
        'periodic_inventory_closes',
        'financial_report_snapshots',

        // ── Payments & cash movement ───────────────────────────────────
        'payment_with_cheque',
        'payment_with_credit_card',
        'payment_with_gift_card',
        'payment_with_paypal',
        'installments',
        'installment_plans',
        'payments',
        'deposits',
        'money_transfers',
        'cash_registers',

        // ── Sales ─────────────────────────────────────────────────────
        'product_sale_modifiers',
        'product_sales',
        'sale_exchanges',
        'product_exchanges',
        'sales',

        // ── Purchases ─────────────────────────────────────────────────
        'purchase_product_return',
        'product_purchases',
        'purchases',

        // ── Returns ───────────────────────────────────────────────────
        'product_returns',
        'returns',
        'return_purchases',

        // ── Inventory movement ────────────────────────────────────────
        'product_adjustments',
        'adjustments',
        'product_transfer',
        'transfers',
        'product_damage_stocks',
        'damage_stocks',
        'product_warehouse',
        'product_batches',
        'stock_counts',

        // ── Gifting & loyalty ─────────────────────────────────────────
        'gift_card_recharges',
        'gift_cards',
        'reward_points',

        // ── Quotations & fulfillment ──────────────────────────────────
        'product_quotation',
        'quotations',
        'packing_slip_products',
        'packing_slips',
        'challans',
        'deliveries',
        'bookings',

        // ── HR & payroll ──────────────────────────────────────────────
        'attendances',
        'overtimes',
        'leaves',
        'payrolls',
        'expenses',
        'incomes',

        // ── Customers, suppliers, employees (transactional columns cleared below via zero, rows kept) ──
        // Note: customer/supplier/employee *rows* are business masters and are also cleared:
        'customer_addresses',
        'discount_plan_customers',
        'discount_plan_discounts',
        'discount_plans',

        // ── Service / Repair module ───────────────────────────────────
        'service_job_items',
        'service_job_updates',
        'service_devices',
        'service_vehicles',
        'service_jobs',

        // ── Tailoring module ──────────────────────────────────────────
        'tailoring_order_workflow_notes',
        'tailoring_order_materials',
        'tailoring_order_payments',
        'tailoring_order_items',
        'tailoring_item_alterations',
        'tailoring_orders',
        'tailoring_trial_items',
        'tailoring_trials',
        'tailoring_notification_logs',
        'tailoring_measurement_profile_versions',
        'tailoring_measurement_profiles',
        'tailoring_measurement_images',

        // ── Production ────────────────────────────────────────────────
        'product_productions',
        'productions',

        // ── E-commerce / social commerce ──────────────────────────────
        'ecommerce_payment_attempts',
        'social_commerce_clicks',

        // ── Projects & tasks ──────────────────────────────────────────
        'task_files',
        'task_discussions',
        'employee_task',
        'tasks',
        'project_files',
        'project_discussions',
        'project_bugs',
        'employee_project',
        'projects',

        // ── Restaurant / bookings ─────────────────────────────────────
        'reservation',

        // ── Master data cleared on reset ──────────────────────────────
        // (Products, customers, suppliers, employees, warehouses, etc.
        //  are business-owned and must be reset to zero state.)
        'product_modifier_group_modifiers',
        'product_modifier_groups',
        'modifiers',
        'modifier_groups',
        'product_variants',
        'products',
        'brands',
        'categories',
        'units',
        'variants',
        'tax_rate_details',
        'customers',
        'suppliers',
        'employees',
        'warehouses',
        'billers',
        'coupons',
        'floors',
        'tables',
        'kitchens',
        'qr_codes',
        'sliders',
        'departments',
        'designations',
        'expense_categories',
        'income_categories',
        'leave_types',
        'shifts',
        'device_types',
        'services',
        'printers',
        'menu_type',

        // ── Accounting master (cleared so chart-of-accounts starts fresh) ──
        'account_mappings',
        'accounting_accounts',
        'accounts',
        'accounting_live_remediation_approvals',
        'accounting_client_remediation_manifests',
        'pos_checkout_sessions',

        // ── Notifications & logs ──────────────────────────────────────
        'notifications',
        'activity_logs',
        'ai_conversations',
        'ai_messages',
        'ai_usage_logs',
        'ai_skill_runs',

        // ── DSO / alerts ──────────────────────────────────────────────
        'dso_alerts',
        'delete_account_requests',
        'mobile_tokens',

        // ── Couriers ─────────────────────────────────────────────────
        'couriers',
    ];

    public function preview(): array
    {
        return [];
    }

    public function execute(int $userId, string $confirmation, ?callable $failureProbe = null): FreshBusinessResetManifest
    {
        if ($confirmation !== 'RESET BUSINESS') {
            throw new RuntimeException('fresh_reset_confirmation_invalid');
        }

        $accountingBefore = DB::table('accounting_configs')->first();
        $activationBefore = $accountingBefore ? (array) $accountingBefore : [];

        try {
            $tableCounts = [];
            DB::transaction(function () use ($failureProbe, &$tableCounts) {

                foreach (self::CLEAR_TABLES as $table) {
                    if (Schema::hasTable($table)) {
                        $count = DB::table($table)->count();
                        DB::table($table)->delete();
                        $tableCounts[$table] = $count;
                    }
                }

                $this->resetAccountingConfig();

                if ($failureProbe) {
                    $failureProbe();
                }
            });

            $accountingAfter = DB::table('accounting_configs')->first();
            $activationAfter = $accountingAfter ? (array) $accountingAfter : [];

            $manifest = FreshBusinessResetManifest::create([
                'reset_type'              => 'fresh_business',
                'status'                  => 'success',
                'user_id'                 => $userId,
                'application_version'     => config('app.version'),
                'database_identifier_hash' => hash('sha256', (string) DB::connection()->getDatabaseName()),
                'affected_tables'         => self::CLEAR_TABLES,
                'before_counts'           => $tableCounts,
                'after_counts'            => array_fill_keys(array_keys($tableCounts), 0),
                'balances_before'         => [],
                'balances_after'          => [],
                'activation_before'       => $activationBefore,
                'activation_after'        => $activationAfter,
                'reset_at'                => now(),
            ]);

            return $manifest;
        } catch (\Throwable $e) {
            FreshBusinessResetManifest::create([
                'reset_type'              => 'fresh_business',
                'status'                  => 'failed',
                'error_message'           => $e->getMessage(),
                'user_id'                 => $userId,
                'application_version'     => config('app.version'),
                'database_identifier_hash' => hash('sha256', (string) DB::connection()->getDatabaseName()),
                'reset_at'                => now(),
            ]);
            throw $e;
        }
    }

    private function resetAccountingConfig(): void
    {
        if (!Schema::hasTable('accounting_configs')) {
            return;
        }

        // 1. Reset the config to a disabled state
        DB::table('accounting_configs')->updateOrInsert(['id' => 1], [
            'enabled'                  => false,
            'status'                   => 'not_activated',
            'activation_mode'          => null,
            'start_date'               => null,
            'opening_journal_entry_id' => null,
            'updated_at'               => now(),
        ]);

        // 2. Officially activate "new_business" mode.
        // This safely provisions default accounts, mappings, and a fresh activation session.
        app(\App\Services\AccountingActivationService::class)->activate('new_business');
    }
}
