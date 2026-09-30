<?php

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\AccountMapping;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleExchange;
use App\Models\AccountingSyncQueue;
use Exception;
use Illuminate\Support\Facades\Schema;

class AccountingService
{
    public const ROLE_CASH = 'cash';
    public const ROLE_ACCOUNTS_RECEIVABLE = 'accounts_receivable';
    public const ROLE_INVENTORY = 'inventory';
    public const ROLE_INPUT_VAT = 'input_vat';
    public const ROLE_ACCOUNTS_PAYABLE = 'accounts_payable';
    public const ROLE_CUSTOMER_DEPOSIT = 'customer_deposit';
    public const ROLE_REWARDS_LIABILITY = 'rewards_liability';
    public const ROLE_GIFT_CARD_LIABILITY = 'gift_card_liability';
    public const ROLE_OPENING_EQUITY = 'opening_equity';
    public const ROLE_SALES_REVENUE = 'sales_revenue';
    public const ROLE_SALES_RETURNS = 'sales_returns';
    public const ROLE_SALES_DISCOUNT = 'sales_discount';
    public const ROLE_OTHER_INCOME = 'other_income';
    public const ROLE_PAYROLL_EXPENSE = 'payroll_expense';
    public const ROLE_PURCHASE_RETURNS = 'purchase_returns';
    public const ROLE_FREIGHT_IN = 'freight_in';
    public const ROLE_REWARDS_EXPENSE = 'rewards_expense';
    public const ROLE_PURCHASE_DISCOUNT = 'purchase_discount';
    public const ROLE_OPERATING_EXPENSE = 'operating_expense';
    public const ROLE_EMPLOYEE_ADVANCE_RECEIVABLE = 'employee_advance_receivable';
    public const ROLE_COST_OF_GOODS_SOLD = 'cost_of_goods_sold';

    public const DEFAULT_ROLE_CODES = [
        self::ROLE_CASH => ['1000', '1300'],
        self::ROLE_ACCOUNTS_RECEIVABLE => ['1100'],
        self::ROLE_INVENTORY => ['1200'],
        self::ROLE_INPUT_VAT => ['1250'],
        self::ROLE_ACCOUNTS_PAYABLE => ['2100'],
        self::ROLE_CUSTOMER_DEPOSIT => ['2210'],
        self::ROLE_REWARDS_LIABILITY => ['2220'],
        self::ROLE_GIFT_CARD_LIABILITY => ['2250'],
        self::ROLE_OPENING_EQUITY => ['3900'],
        self::ROLE_SALES_REVENUE => ['4100', '4000'],
        self::ROLE_SALES_RETURNS => ['4150'],
        self::ROLE_SALES_DISCOUNT => ['4160'],
        self::ROLE_OTHER_INCOME => ['4300'],
        self::ROLE_PAYROLL_EXPENSE => ['5100'],
        self::ROLE_PURCHASE_RETURNS => ['5150'],
        self::ROLE_FREIGHT_IN => ['5200'],
        self::ROLE_REWARDS_EXPENSE => ['5210'],
        self::ROLE_PURCHASE_DISCOUNT => ['5300'],
        self::ROLE_OPERATING_EXPENSE => ['6100'],
        self::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE => ['1150'],
        self::ROLE_COST_OF_GOODS_SOLD => ['5000'],
    ];

    public const ROLE_ACCOUNT_TYPES = [
        self::ROLE_CASH => ['asset'],
        self::ROLE_ACCOUNTS_RECEIVABLE => ['asset'],
        self::ROLE_INVENTORY => ['asset'],
        self::ROLE_INPUT_VAT => ['asset'],
        self::ROLE_ACCOUNTS_PAYABLE => ['liability'],
        self::ROLE_CUSTOMER_DEPOSIT => ['liability'],
        self::ROLE_REWARDS_LIABILITY => ['liability'],
        self::ROLE_GIFT_CARD_LIABILITY => ['liability'],
        self::ROLE_OPENING_EQUITY => ['equity'],
        self::ROLE_SALES_REVENUE => ['revenue'],
        self::ROLE_SALES_RETURNS => ['revenue'],
        self::ROLE_SALES_DISCOUNT => ['revenue'],
        self::ROLE_OTHER_INCOME => ['revenue'],
        self::ROLE_PAYROLL_EXPENSE => ['expense'],
        self::ROLE_PURCHASE_RETURNS => ['expense', 'cogs'],
        self::ROLE_FREIGHT_IN => ['expense', 'cogs'],
        self::ROLE_REWARDS_EXPENSE => ['expense'],
        self::ROLE_PURCHASE_DISCOUNT => ['expense', 'revenue'],
        self::ROLE_OPERATING_EXPENSE => ['expense'],
        self::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE => ['asset'],
        self::ROLE_COST_OF_GOODS_SOLD => ['cogs'],
    ];

    public const CORE_CERTIFICATION_ROLES = [
        self::ROLE_CASH,
        self::ROLE_ACCOUNTS_RECEIVABLE,
        self::ROLE_INVENTORY,
        self::ROLE_ACCOUNTS_PAYABLE,
        self::ROLE_OPENING_EQUITY,
        self::ROLE_SALES_REVENUE,
        self::ROLE_SALES_RETURNS,
        self::ROLE_OPERATING_EXPENSE,
    ];

    public const FEATURE_CERTIFICATION_ROLES = [
        self::ROLE_SALES_DISCOUNT,
        self::ROLE_INPUT_VAT,
        self::ROLE_CUSTOMER_DEPOSIT,
        self::ROLE_GIFT_CARD_LIABILITY,
        self::ROLE_REWARDS_LIABILITY,
        self::ROLE_REWARDS_EXPENSE,
        self::ROLE_PURCHASE_RETURNS,
        self::ROLE_OTHER_INCOME,
        self::ROLE_PAYROLL_EXPENSE,
        self::ROLE_FREIGHT_IN,
        self::ROLE_PURCHASE_DISCOUNT,
        self::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE,
        self::ROLE_COST_OF_GOODS_SOLD,
    ];

    /**
     * Executes a journaling operation safely and returns an AccountingResult.
     */
    private function executeSafe(...$args): AccountingResult
    {
        $callback = end($args);

        if (!is_callable($callback)) {
            return AccountingResult::failed('Accounting callback is not callable.');
        }

        if (!app(AccountingModeService::class)->isDoubleEntryAuthoritative()) {
            return AccountingResult::skippedLegacyMode();
        }
        $config = \App\Models\AccountingConfig::find(1);

        if (count($args) >= 3) {
            $sourceType = $args[0];
            $sourceId = $args[1];
            
            try {
                if (class_exists($sourceType)) {
                    $model = $sourceType::find($sourceId);
                    if ($model && $model->created_at) {
                        $date = $model->created_at->toDateString();
                        if ($config->start_date && $date < $config->start_date) {
                            return AccountingResult::skippedPreActivation();
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore and proceed if date checking fails
            }
        }

        try {
            $journalEntry = $callback();

            if ($journalEntry instanceof JournalEntry && count($args) >= 3) {
                $sourceType = $args[0];
                $sourceId = $args[1];
                if (class_exists($sourceType)) {
                    $model = $sourceType::find($sourceId);
                    if ($model && Schema::hasColumn($model->getTable(), 'accounting_status')) {
                        $model->accounting_status = 'posted';
                        $model->saveQuietly();
                    }
                }
            }

            if ($journalEntry instanceof JournalEntry && count($args) >= 3 && Schema::hasTable('accounting_sync_queue')) {
                $queue = AccountingSyncQueue::firstOrNew([
                    'source_type' => $args[0],
                    'source_id' => $args[1],
                ]);
                $queue->status = 'posted';
                $queue->last_error = null;
                $queue->last_attempt_at = now();
                $queue->last_success_at = now();
                $queue->resolved_at = now();
                $queue->posted_at = $queue->posted_at ?: now();
                $queue->save();
            }

            return AccountingResult::success($journalEntry);
        } catch (\Throwable $e) {
            if (count($args) >= 3) {
                $sourceType = $args[0];
                $sourceId = $args[1];
                if (class_exists($sourceType)) {
                    $model = $sourceType::find($sourceId);
                    if ($model && Schema::hasColumn($model->getTable(), 'accounting_status')) {
                        $model->accounting_status = 'failed';
                        $model->saveQuietly();
                    }
                }
                if (Schema::hasTable('accounting_sync_queue')) {
                    $queue = AccountingSyncQueue::firstOrNew([
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                    ]);
                    $queue->status = 'failed';
                    $queue->attempts = (int) $queue->attempts + 1;
                    $queue->last_error = $e->getMessage();
                    $queue->last_attempt_at = now();
                    $queue->save();
                }
            }
            return AccountingResult::failed($e->getMessage());
        }
    }

    /**
     * Get an account ID by code.
     */
    public function getAccountId(string $code): int
    {
        return AccountingAccount::where('code', $code)->firstOrFail()->id;
    }

    public function getRoleAccount(string $role): AccountingAccount
    {
        $mapping = AccountMapping::where('mapped_type', $role)
            ->where('mapped_id', 0)
            ->first();

        if ($mapping) {
            return AccountingAccount::findOrFail($mapping->accounting_account_id);
        }

        $this->bootstrapSemanticRoleMapping($role);

        $mapping = AccountMapping::where('mapped_type', $role)
            ->where('mapped_id', 0)
            ->firstOrFail();

        return AccountingAccount::findOrFail($mapping->accounting_account_id);
    }

    public function getRoleAccountId(string $role): int
    {
        return $this->getRoleAccount($role)->id;
    }

    public function bootstrapSemanticRoleMappings(): void
    {
        foreach (array_keys(self::DEFAULT_ROLE_CODES) as $role) {
            $this->bootstrapSemanticRoleMapping($role);
        }
    }

    public function validateSemanticRoleMappings(?array $roles = null, bool $bootstrapMissing = false): array
    {
        $roles = $roles ?: array_keys(self::DEFAULT_ROLE_CODES);
        $results = [];

        foreach ($roles as $role) {
            if ($bootstrapMissing) {
                try {
                    $this->bootstrapSemanticRoleMapping($role);
                } catch (\Throwable $e) {
                    // Fall through to explicit validation failure below.
                }
            }

            $mapping = AccountMapping::where('mapped_type', $role)->where('mapped_id', 0)->first();
            if (!$mapping) {
                $results[$role] = [
                    'status' => 'fail',
                    'message' => "Missing required semantic account mapping: {$role}",
                    'account' => null,
                ];
                continue;
            }

            $account = AccountingAccount::find($mapping->accounting_account_id);
            if (!$account) {
                $results[$role] = [
                    'status' => 'fail',
                    'message' => "Semantic account mapping {$role} points to missing accounting_account_id {$mapping->accounting_account_id}.",
                    'account' => null,
                ];
                continue;
            }

            if (!$account->is_active) {
                $results[$role] = [
                    'status' => 'fail',
                    'message' => "Semantic account mapping {$role} points to inactive account {$account->code} - {$account->name}.",
                    'account' => $account,
                ];
                continue;
            }

            $allowedTypes = self::ROLE_ACCOUNT_TYPES[$role] ?? [];
            if ($allowedTypes && !in_array($account->account_type, $allowedTypes, true)) {
                $results[$role] = [
                    'status' => 'fail',
                    'message' => "Semantic account mapping {$role} points to {$account->code} - {$account->name} with type {$account->account_type}; expected " . implode(' or ', $allowedTypes) . '.',
                    'account' => $account,
                ];
                continue;
            }

            $results[$role] = [
                'status' => 'pass',
                'message' => "Role {$role} resolves to {$account->code} - {$account->name}.",
                'account' => $account,
            ];
        }

        return $results;
    }

    public function bootstrapSemanticRoleMapping(string $role): void
    {
        if (AccountMapping::where('mapped_type', $role)->where('mapped_id', 0)->exists()) {
            return;
        }

        foreach (self::DEFAULT_ROLE_CODES[$role] ?? [] as $code) {
            $account = AccountingAccount::where('code', $code)->first();
            if ($account) {
                AccountMapping::create([
                    'mapped_type' => $role,
                    'mapped_id' => 0,
                    'accounting_account_id' => $account->id,
                ]);
                return;
            }
        }

        throw new Exception("No semantic account mapping exists for role [{$role}], and no default bootstrap account was found.");
    }

    private function getCashAccountId(): int
    {
        return $this->getRoleAccountId(self::ROLE_CASH);
    }

    private function getMappedCashAccountId(?int $operationalAccountId): int
    {
        if (!$operationalAccountId) {
            return $this->getCashAccountId();
        }

        $mappedAccountId = $this->getMappedAccount('App\Models\Account', $operationalAccountId, $this->getCashAccountId());
        $isCashAccount = AccountingAccount::where('id', $mappedAccountId)
            ->where('is_active', true)
            ->where('account_type', 'asset')
            ->where('is_cash_account', true)
            ->exists();

        if (!$isCashAccount) throw new Exception(__('db.payment_account_invalid_mapping'));
        return $mappedAccountId;
    }

    public function resolveStrictCashAccountId(?int $operationalAccountId): int
    {
        if (!$operationalAccountId) {
            throw new Exception(__('db.employee_advance_invalid_payment_account'));
        }

        $legacyAccount = \App\Models\Account::whereKey($operationalAccountId)
            ->where('is_active', true)
            ->first();
        if (!app(AccountingModeService::class)->isDoubleEntryAuthoritative()) {
            if (!$legacyAccount) throw new Exception(__('db.employee_advance_invalid_payment_account'));
            return $legacyAccount->id;
        }
        $mapping = AccountMapping::where('mapped_type', 'App\\Models\\Account')
            ->where('mapped_id', $operationalAccountId)
            ->first();
        $account = $mapping ? AccountingAccount::find($mapping->accounting_account_id) : null;

        if (!$legacyAccount || !$account || !$account->is_active || !$account->is_cash_account || $account->account_type !== 'asset') {
            throw new Exception(__('db.employee_advance_invalid_payment_account'));
        }

        return $account->id;
    }

    /**
     * Get a mapped account for a specific entity (e.g. Customer, Supplier, Account).
     * If not mapped, returns the fallback control account ID.
     */
    public function getMappedAccount(string $type, int $id, int $fallbackAccountId): int
    {
        $mapping = AccountMapping::where('mapped_type', $type)
            ->where('mapped_id', $id)
            ->first();

        if ($mapping) {
            return $mapping->accounting_account_id;
        }

        if ($type === 'App\Models\Account') {
            return $this->createLegacyCashAccountMapping($id, $fallbackAccountId);
        }

        return $fallbackAccountId;
    }

    private function createLegacyCashAccountMapping(int $legacyAccountId, int $fallbackAccountId): int
    {
        $legacyAccount = \App\Models\Account::find($legacyAccountId);
        if (!$legacyAccount) {
            return $fallbackAccountId;
        }

        $baseCode = '10A' . $legacyAccount->id;
        $code = $baseCode;
        $suffix = 1;
        while (AccountingAccount::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $suffix++;
        }

        $attributes = [
            'code' => $code,
            'name' => $legacyAccount->name,
            'account_type' => 'asset',
            'parent_id' => $fallbackAccountId,
            'is_control_account' => false,
            'is_system' => false,
            'is_active' => true,
        ];

        if (Schema::hasColumn('accounting_accounts', 'is_cash_account')) {
            $attributes['is_cash_account'] = true;
        }

        $accountingAccount = AccountingAccount::create($attributes);

        AccountMapping::create([
            'accounting_account_id' => $accountingAccount->id,
            'mapped_type' => 'App\Models\Account',
            'mapped_id' => $legacyAccount->id,
        ]);

        return $accountingAccount->id;
    }

    public function recordSale($sale, string $eventType = 'sale_created'): AccountingResult
    {
        return $this->executeSafe(get_class($sale), $sale->id, function () use ($sale, $eventType) {
            $sourceType = get_class($sale);
            $eventType = $this->resolvePostEventType($sourceType, $sale->id, $eventType);

            if (isset($sale->accounting_status) && $sale->accounting_status === 'posted') {
                $existing = $this->existingUnreversedJournal($sourceType, $sale->id, $eventType);
                if ($existing) return $existing;
            }

            $arAccountId = $this->getMappedAccount('App\Models\Customer', $sale->customer_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            $revenueAccountId = $this->getRoleAccountId(self::ROLE_SALES_REVENUE);

            return JournalBuilder::create()
                ->setSource(get_class($sale), $sale->id)
                ->setEventType($eventType)
                ->setReference(($sale->reference_no ?? 'SALE-' . $sale->id) . '-' . strtoupper($eventType))
                ->setDate($sale->created_at->toDateString())
                ->setNote($sale->sale_note)
                ->addDebit($arAccountId, $sale->grand_total, 'Accounts Receivable for Sale ' . $sale->reference_no)
                ->addCredit($revenueAccountId, $sale->grand_total, 'Revenue from Sale ' . $sale->reference_no)
                ->save();
        });
    }

    public function recordPurchase($purchase, string $eventType = 'purchase_created'): AccountingResult
    {
        if ($this->isInitialStockPurchase($purchase)) {
            return $this->recordInitialStock($purchase);
        }

        return $this->executeSafe(get_class($purchase), $purchase->id, function () use ($purchase, $eventType) {
            $sourceType = get_class($purchase);
            $eventType = $this->resolvePostEventType($sourceType, $purchase->id, $eventType);

            if (isset($purchase->accounting_status) && $purchase->accounting_status === 'posted') {
                $existing = $this->existingUnreversedJournal($sourceType, $purchase->id, $eventType);
                if ($existing) return $existing;
            }

            $apControlAccountId = $this->getRoleAccountId(self::ROLE_ACCOUNTS_PAYABLE);
            $apAccountId = $purchase->supplier_id
                ? $this->getMappedAccount('App\Models\Supplier', (int) $purchase->supplier_id, $apControlAccountId)
                : $apControlAccountId;
            $inventoryAccountId = $this->getRoleAccountId(self::ROLE_INVENTORY);

            $builder = JournalBuilder::create()
                ->setSource(get_class($purchase), $purchase->id)
                ->setEventType($eventType)
                ->setReference(($purchase->reference_no ?? 'PURCHASE-' . $purchase->id) . '-' . strtoupper($eventType))
                ->setDate($purchase->created_at->toDateString())
                ->setNote($purchase->note);

            // Inventory base amount = sum of item cost * qty (stored in total_cost)
            if ($purchase->total_cost > 0) {
                $builder->addDebit($inventoryAccountId, $purchase->total_cost, 'Inventory Receipt for Purchase ' . $purchase->reference_no);
            }

            // Order Tax (Input VAT)
            if ($purchase->order_tax > 0) {
                $inputVatAccountId = $this->getRoleAccountId(self::ROLE_INPUT_VAT);
                $builder->addDebit($inputVatAccountId, $purchase->order_tax, 'Input VAT for Purchase ' . $purchase->reference_no);
            }

            // Shipping Cost (Freight In)
            if ($purchase->shipping_cost > 0) {
                $freightInAccountId = $this->getRoleAccountId(self::ROLE_FREIGHT_IN);
                $builder->addDebit($freightInAccountId, $purchase->shipping_cost, 'Shipping Cost for Purchase ' . $purchase->reference_no);
            }

            // Order Discount
            if ($purchase->order_discount > 0) {
                $purchaseDiscountAccountId = $this->getRoleAccountId(self::ROLE_PURCHASE_DISCOUNT);
                $builder->addCredit($purchaseDiscountAccountId, $purchase->order_discount, 'Purchase Discount for ' . $purchase->reference_no);
            }

            // Purchase recognition is always on credit. Actual cash movement belongs
            // exclusively to Payment journals, including immediate/partial payments.
            $builder->addCredit($apAccountId, $purchase->grand_total, 'Accounts Payable for Purchase ' . $purchase->reference_no);

            return $builder->save();
        });
    }

    public function recordInitialStock($purchase): AccountingResult
    {
        return $this->executeSafe(get_class($purchase), $purchase->id, function () use ($purchase) {
            $existing = JournalEntry::where('source_type', get_class($purchase))
                ->where('source_id', $purchase->id)
                ->where('event_type', 'initial_stock_created')
                ->first();
            if ($existing) return $existing;

            return JournalBuilder::create()
                ->setSource(get_class($purchase), $purchase->id)
                ->setSourceSubtype('initial_stock')
                ->setEventType('initial_stock_created')
                ->setReference(($purchase->reference_no ?? 'INITIAL-STOCK-' . $purchase->id) . '-INITIAL-STOCK')
                ->setDate($purchase->created_at->toDateString())
                ->setNote('Initial stock contribution')
                ->addDebit($this->getRoleAccountId(self::ROLE_INVENTORY), $purchase->grand_total, 'Initial Inventory')
                ->addCredit($this->getRoleAccountId(self::ROLE_OPENING_EQUITY), $purchase->grand_total, 'Initial Inventory Equity')
                ->save();
        });
    }

    public function isInitialStockPurchase($purchase): bool
    {
        return strtolower(trim((string) ($purchase->purchase_type ?? ''))) === 'initial_stock';
    }
    public function recordSaleReturn($return, string $eventType = 'sale_return_created'): AccountingResult
    {
        return $this->executeSafe(get_class($return), $return->id, function () use ($return, $eventType) {
            if (isset($return->accounting_status) && $return->accounting_status === 'posted') {
                return JournalEntry::where('source_type', get_class($return))->where('source_id', $return->id)->first();
            }

            $arAccountId = $this->getMappedAccount('App\Models\Customer', $return->customer_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            $salesReturnAccountId = $this->getRoleAccountId(self::ROLE_SALES_RETURNS);

            return JournalBuilder::create()
                ->setSource(get_class($return), $return->id)
                ->setEventType($eventType)
                ->setReference(($return->reference_no ?? 'SR') . '-' . $return->id . '-' . strtoupper($eventType))
                ->setDate($return->created_at->toDateString())
                ->setNote($return->return_note)
                ->addDebit($salesReturnAccountId, $return->grand_total, 'Sales Return ' . $return->reference_no)
                ->addCredit($arAccountId, $return->grand_total, 'Accounts Receivable reduction for Return ' . $return->reference_no)
                ->save();
        });
    }

    public function recordPurchaseReturn($returnPurchase, string $eventType = 'purchase_return_created'): AccountingResult
    {
        return $this->executeSafe(get_class($returnPurchase), $returnPurchase->id, function () use ($returnPurchase, $eventType) {
            if (isset($returnPurchase->accounting_status) && $returnPurchase->accounting_status === 'posted') {
                return JournalEntry::where('source_type', get_class($returnPurchase))->where('source_id', $returnPurchase->id)->first();
            }

            $apAccountId = $this->getMappedAccount('App\Models\Supplier', $returnPurchase->supplier_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_PAYABLE));
            $purchaseReturnAccountId = $this->getRoleAccountId(self::ROLE_PURCHASE_RETURNS);

            return JournalBuilder::create()
                ->setSource(get_class($returnPurchase), $returnPurchase->id)
                ->setEventType($eventType)
                ->setReference(($returnPurchase->reference_no ?? 'PR') . '-' . $returnPurchase->id . '-' . strtoupper($eventType))
                ->setDate($returnPurchase->created_at->toDateString())
                ->setNote($returnPurchase->return_note)
                ->addDebit($apAccountId, $returnPurchase->grand_total, 'Accounts Payable reduction for Return ' . $returnPurchase->reference_no)
                ->addCredit($purchaseReturnAccountId, $returnPurchase->grand_total, 'Purchase Return ' . $returnPurchase->reference_no)
                ->save();
        });
    }

    public function recordSaleExchange(SaleExchange $exchange, string $eventType = 'sale_exchange_created'): AccountingResult
    {
        return $this->executeSafe(get_class($exchange), $exchange->id, function () use ($exchange, $eventType) {
            $sourceType = get_class($exchange);
            $existing = $this->existingUnreversedJournal($sourceType, $exchange->id, $eventType);
            if ($existing) {
                return $existing;
            }

            $sale = Sale::findOrFail($exchange->sale_id);
            $arAccountId = $this->getMappedAccount('App\Models\Customer', $exchange->customer_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            $revenueAccountId = $this->getRoleAccountId(self::ROLE_SALES_REVENUE);
            $salesReturnAccountId = $this->getRoleAccountId(self::ROLE_SALES_RETURNS);
            $cashAccountId = $this->getCashAccountId();

            $returnedTotal = $exchange->products()->where('type', 'returned')->sum('total');
            $newTotal = $exchange->products()->where('type', 'new')->sum('total');

            $builder = JournalBuilder::create()
                ->setSource($sourceType, $exchange->id)
                ->setSourceSubtype('sale_exchange')
                ->setEventType($eventType)
                ->setReference(($exchange->reference_no ?? 'EXC') . '-' . $exchange->id . '-' . strtoupper($eventType))
                ->setDate($exchange->created_at->toDateString())
                ->setNote($exchange->exchange_note);

            if ($returnedTotal > 0) {
                $builder->addDebit($salesReturnAccountId, $returnedTotal, 'Exchange returned items for Sale ' . $sale->reference_no)
                    ->addCredit($arAccountId, $returnedTotal, 'AR reduction for Exchange ' . $exchange->reference_no);
            }

            if ($newTotal > 0) {
                $builder->addDebit($arAccountId, $newTotal, 'AR for Exchange new items ' . $exchange->reference_no)
                    ->addCredit($revenueAccountId, $newTotal, 'Revenue for Exchange new items ' . $exchange->reference_no);
            }

            if ($exchange->payment_type === 'receive' && $exchange->amount > 0) {
                $builder->addDebit($cashAccountId, $exchange->amount, 'Additional payment for Exchange ' . $exchange->reference_no)
                    ->addCredit($arAccountId, $exchange->amount, 'AR clearance for Exchange additional payment');
            } elseif ($exchange->payment_type === 'pay' && $exchange->amount > 0) {
                $builder->addDebit($arAccountId, $exchange->amount, 'Customer refund for Exchange ' . $exchange->reference_no)
                    ->addCredit($cashAccountId, $exchange->amount, 'Cash refund for Exchange ' . $exchange->reference_no);
            }

            return $builder->save();
        });
    }


    public function recordExpense($expense): AccountingResult
    {
        if (strtolower((string) $expense->type) === 'advance') {
            return $this->recordEmployeeAdvance($expense);
        }

        return $this->executeSafe(get_class($expense), $expense->id, function () use ($expense) {
            $cashAccountId = $this->getMappedAccount('App\Models\Account', $expense->account_id, $this->getCashAccountId());
            $expenseAccountId = $this->getMappedAccount('App\Models\ExpenseCategory', $expense->expense_category_id, $this->getRoleAccountId(self::ROLE_OPERATING_EXPENSE));

            return JournalBuilder::create()
                ->setSource(get_class($expense), $expense->id)
                ->setEventType('expense_created')
                ->setReference($expense->reference_no ?? 'EXP-' . $expense->id)
                ->setDate($expense->created_at->toDateString())
                ->setNote($expense->note)
                ->addDebit($expenseAccountId, $expense->amount, 'Operating Expense')
                ->addCredit($cashAccountId, $expense->amount, 'Cash Out for Expense')
                ->save();
        });
    }

    public function recordEmployeeAdvance($expense): AccountingResult
    {
        return $this->executeSafe(get_class($expense), $expense->id, function () use ($expense) {
            if (!$expense->employee_id) {
                throw new Exception(__('db.employee_advance_employee_required'));
            }

            $active = JournalEntry::where('source_type', get_class($expense))
                ->where('source_id', $expense->id)
                ->whereIn('event_type', ['employee_advance_created', 'employee_advance_updated'])
                ->whereNotIn('id', JournalEntry::whereNotNull('related_journal_entry_id')->select('related_journal_entry_id'))
                ->first();
            if ($active) return $active;

            $hasHistory = JournalEntry::where('source_type', get_class($expense))
                ->where('source_id', $expense->id)->exists();
            $eventType = $hasHistory ? 'employee_advance_updated' : 'employee_advance_created';
            $eventType = $this->resolvePostEventType(get_class($expense), $expense->id, $eventType);

            return JournalBuilder::create()
                ->setSource(get_class($expense), $expense->id)
                ->setSourceSubtype('employee_advance')
                ->setEventType($eventType)
                ->setReference(($expense->reference_no ?? 'ADV-' . $expense->id) . '-' . strtoupper($eventType))
                ->setDate($expense->created_at->toDateString())
                ->setNote($expense->note)
                ->addDebit($this->getRoleAccountId(self::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE), $expense->amount, 'Employee Advance Receivable')
                ->addCredit($this->resolveStrictCashAccountId($expense->account_id), $expense->amount, 'Employee Advance Payment')
                ->save();
        });
    }

    public function recordIncome($income): AccountingResult
    {
        return $this->executeSafe(get_class($income), $income->id, function () use ($income) {
            $cashAccountId = $this->getMappedAccount('App\Models\Account', $income->account_id, $this->getCashAccountId());
            $incomeAccountId = $this->getMappedAccount('App\Models\IncomeCategory', $income->income_category_id, $this->getRoleAccountId(self::ROLE_OTHER_INCOME));

            return JournalBuilder::create()
                ->setSource(get_class($income), $income->id)
                ->setEventType('income_created')
                ->setReference($income->reference_no ?? 'INC-' . $income->id)
                ->setDate($income->created_at->toDateString())
                ->setNote($income->note)
                ->addDebit($cashAccountId, $income->amount, 'Cash In for Income')
                ->addCredit($incomeAccountId, $income->amount, 'Other Income')
                ->save();
        });
    }

    public function recordPayroll($payroll, string $eventType = 'payroll_paid'): AccountingResult
    {
        return $this->executeSafe(get_class($payroll), $payroll->id, function () use ($payroll, $eventType) {
            $amountArray = is_array($payroll->amount_array)
                ? $payroll->amount_array
                : (json_decode((string) $payroll->amount_array, true) ?: []);
            $advanceRecovery = (float) ($amountArray['advance_recovery'] ?? 0);
            if ($advanceRecovery > 0) {
                $eventType = str_contains($eventType, 'updated')
                    ? 'employee_advance_recovery_updated'
                    : 'employee_advance_recovered';
            }

            $activeJournal = JournalEntry::where('source_type', get_class($payroll))
                ->where('source_id', $payroll->id)
                ->where(function ($query) {
                    $query->where('event_type', 'payroll_paid')
                        ->orWhere('event_type', 'like', 'payroll_updated%')
                        ->orWhere('event_type', 'employee_advance_recovered')
                        ->orWhere('event_type', 'like', 'employee_advance_recovery_updated%');
                })
                ->whereNotIn('id', JournalEntry::whereNotNull('related_journal_entry_id')
                    ->select('related_journal_entry_id'))
                ->first();
            if ($activeJournal) return $activeJournal;

            $eventType = $this->resolvePostEventType(get_class($payroll), $payroll->id, $eventType);
            $cashAccountId = $this->getMappedAccount('App\Models\Account', $payroll->account_id, $this->getCashAccountId());
            $payrollAccountId = $this->getRoleAccountId(self::ROLE_PAYROLL_EXPENSE);
            if ($advanceRecovery > 0) {
                if (!$payroll->employee_id) {
                    throw new Exception(__('db.employee_advance_employee_required'));
                }
                $outstanding = app(EmployeeAdvanceService::class)->outstandingForEmployee($payroll->employee_id, $payroll->id);
                if ($advanceRecovery - $outstanding > 0.0001) {
                    throw new Exception(__('db.employee_advance_over_recovery'));
                }
            }
            $grossPayroll = (float) $payroll->amount + $advanceRecovery;

            $builder = JournalBuilder::create()
                ->setSource(get_class($payroll), $payroll->id)
                ->setSourceSubtype($advanceRecovery > 0 ? 'employee_advance_recovered' : 'payroll')
                ->setEventType($eventType)
                ->setReference(($payroll->reference_no ?? 'PAYROLL-' . $payroll->id) . '-' . strtoupper($eventType))
                ->setDate($payroll->created_at->toDateString())
                ->setNote($payroll->note)
                ->addDebit($payrollAccountId, $grossPayroll, 'Payroll Expense')
                ->addCredit($cashAccountId, $payroll->amount, 'Cash Out for Payroll');

            if ($advanceRecovery > 0) {
                $builder->addCredit(
                    $this->getRoleAccountId(self::ROLE_EMPLOYEE_ADVANCE_RECEIVABLE),
                    $advanceRecovery,
                    'Employee Advance Recovery'
                );
            }

            return $builder->save();
        });
    }

    public function recordCustomerOpeningBalance($customer, string $eventType = 'customer_opening_balance_created'): AccountingResult
    {
        return $this->executeSafe(get_class($customer), $customer->id, function () use ($customer, $eventType) {
            $arAccountId = $this->getMappedAccount('App\Models\Customer', $customer->id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            $openingBalanceEquityId = $this->getRoleAccountId(self::ROLE_OPENING_EQUITY);

            return JournalBuilder::create()
                ->setSource(get_class($customer), $customer->id)
                ->setEventType($eventType)
                ->setReference('CUST-OB-' . $customer->id)
                ->setDate($customer->created_at->toDateString())
                ->setNote('Opening Balance for Customer ' . $customer->name)
                ->addDebit($arAccountId, $customer->opening_balance, 'A/R Initial Balance')
                ->addCredit($openingBalanceEquityId, $customer->opening_balance, 'Opening Equity')
                ->save();
        });
    }

    public function recordSupplierOpeningBalance($supplier, string $eventType = 'supplier_opening_balance_created'): AccountingResult
    {
        return $this->executeSafe(get_class($supplier), $supplier->id, function () use ($supplier, $eventType) {
            $apAccountId = $this->getMappedAccount('App\Models\Supplier', $supplier->id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_PAYABLE));
            $openingBalanceEquityId = $this->getRoleAccountId(self::ROLE_OPENING_EQUITY);

            return JournalBuilder::create()
                ->setSource(get_class($supplier), $supplier->id)
                ->setEventType($eventType)
                ->setReference('SUPP-OB-' . $supplier->id)
                ->setDate($supplier->created_at->toDateString())
                ->setNote('Opening Balance for Supplier ' . $supplier->name)
                ->addDebit($openingBalanceEquityId, $supplier->opening_balance, 'Opening Equity')
                ->addCredit($apAccountId, $supplier->opening_balance, 'A/P Initial Balance')
                ->save();
        });
    }

    public function recordAccountOpeningBalance($account, string $eventType = 'account_opening_balance_created'): AccountingResult
    {
        return $this->executeSafe(get_class($account), $account->id, function () use ($account, $eventType) {
            $cashAccountId = $this->getMappedAccount('App\Models\Account', $account->id, $this->getCashAccountId());
            $openingBalanceEquityId = $this->getRoleAccountId(self::ROLE_OPENING_EQUITY);

            return JournalBuilder::create()
                ->setSource(get_class($account), $account->id)
                ->setEventType($eventType)
                ->setReference('ACC-OB-' . $account->id)
                ->setDate($account->created_at->toDateString())
                ->setNote('Opening Balance for Account ' . $account->name)
                ->addDebit($cashAccountId, $account->initial_balance, 'Cash/Bank Initial Balance')
                ->addCredit($openingBalanceEquityId, $account->initial_balance, 'Opening Equity')
                ->save();
        });
    }


    public function reverseTransaction(string $sourceType, int $sourceId, string $eventSuffix = '_reversed'): AccountingResult
    {
        $result = $this->executeSafe($sourceType, $sourceId, function () use ($sourceType, $sourceId, $eventSuffix) {
            $sourceTypes = array_unique([$sourceType, class_basename($sourceType), '\\' . ltrim($sourceType, '\\')]);
            $entries = JournalEntry::whereIn('source_type', $sourceTypes)
                ->where('source_id', $sourceId)
                ->where('event_type', 'NOT LIKE', '%_reversed')
                ->where('event_type', 'NOT LIKE', '%_reversal')
                ->where('event_type', 'NOT LIKE', '%_deleted')
                ->when(
                    Schema::hasColumn('journal_entries', 'related_journal_entry_id'),
                    fn ($query) => $query->whereNull('related_journal_entry_id')
                )
                ->with('lines')
                ->get();

            $lastReversed = null;
            foreach ($entries as $entry) {
                $alreadyReversedQuery = JournalEntry::where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where(function ($query) use ($entry) {
                        $query->whereIn('event_type', [
                            $entry->event_type . '_reversed',
                            $entry->event_type . '_deleted',
                        ]);

                        if (Schema::hasColumn('journal_entries', 'related_journal_entry_id')) {
                            $query->orWhere('related_journal_entry_id', $entry->id);
                        }
                    });

                $alreadyReversed = $alreadyReversedQuery->exists();

                if ($alreadyReversed) {
                    continue;
                }

                $lastReversed = JournalBuilder::reverse($entry, $eventSuffix);
            }
            return $lastReversed;
        });

        if ($result->isSuccess()) {
            $model = class_exists($sourceType) ? $sourceType::find($sourceId) : null;
            if ($model && Schema::hasColumn($model->getTable(), 'accounting_status')) {
                $model->accounting_status = 'reversed';
                $model->saveQuietly();
            }
            if (Schema::hasTable('accounting_sync_queue')) {
                AccountingSyncQueue::updateOrCreate(
                    ['source_type' => $sourceType, 'source_id' => $sourceId],
                    ['status' => 'reversed', 'last_error' => null, 'last_attempt_at' => now(), 'resolved_at' => now()]
                );
            }
        }

        return $result;
    }

    public function recordPayment($payment, string $eventType = 'payment_received'): AccountingResult
    {
        if ($payment->sale_id) {
            if ($eventType === 'payment_received') {
                $eventType = $payment->return_id ? 'sale_refund_created' : 'sale_payment_created';
            }
            $existing = JournalEntry::where('source_type', get_class($payment))
                ->where('source_id', $payment->id)
                ->where('event_type', $eventType)
                ->first();
            if ($existing) {
                return AccountingResult::success($existing);
            }
            if ($payment->return_id) {
                return $this->recordCustomerRefund($payment, $eventType);
            }
            return $this->recordCustomerPayment($payment, $eventType);
        } elseif ($payment->purchase_id) {
            $purchase = Purchase::find($payment->purchase_id);
            if ($purchase && $this->isInitialStockPurchase($purchase)) {
                if (Schema::hasColumn($payment->getTable(), 'accounting_status')) {
                    $payment->accounting_status = \App\Services\ClientPreservingAccountingRemediationService::PAYMENT_EXCLUDED_STATUS;
                    $payment->saveQuietly();
                }
                return AccountingResult::success(null);
            }
            $isPurchaseRefund = !empty($payment->purchase_return_id) || !empty($payment->return_id);
            if ($eventType === 'payment_received') {
                $eventType = $isPurchaseRefund ? 'purchase_refund_created' : 'purchase_payment_created';
            }
            $existing = JournalEntry::where('source_type', get_class($payment))
                ->where('source_id', $payment->id)
                ->where('event_type', $eventType)
                ->first();
            if ($existing) {
                return AccountingResult::success($existing);
            }
            if ($isPurchaseRefund) {
                return $this->recordSupplierRefund($payment, $eventType);
            }
            return $this->recordSupplierPayment($payment, $eventType);
        }
        return AccountingResult::failed('Unknown payment type.');
    }

    private function existingUnreversedJournal(string $sourceType, int $sourceId, string $eventType): ?JournalEntry
    {
        return JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('event_type', $eventType)
            ->whereNotExists(function ($query) use ($sourceType, $sourceId, $eventType) {
                $query->selectRaw(1)
                    ->from('journal_entries as reversals')
                    ->whereColumn('reversals.source_type', 'journal_entries.source_type')
                    ->whereColumn('reversals.source_id', 'journal_entries.source_id')
                    ->where('reversals.source_type', $sourceType)
                    ->where('reversals.source_id', $sourceId)
                    ->where('reversals.event_type', $eventType . '_reversed');
            })
            ->first();
    }

    private function resolvePostEventType(string $sourceType, int $sourceId, string $eventType): string
    {
        if (!str_contains($eventType, '_updated')) {
            return $eventType;
        }

        $existing = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where(function ($query) use ($eventType) {
                $query->where('event_type', $eventType)
                    ->orWhere('event_type', 'like', $eventType . '_%');
            })
            ->pluck('event_type')
            ->all();

        if (!in_array($eventType, $existing, true)) {
            return $eventType;
        }

        $sequence = 2;
        while (in_array($eventType . '_' . $sequence, $existing, true)) {
            $sequence++;
        }

        return $eventType . '_' . $sequence;
    }

    private function recordCustomerPayment($payment, string $eventType): AccountingResult
    {
        return $this->executeSafe(get_class($payment), $payment->id, function () use ($payment, $eventType) {
            $sale = Sale::findOrFail($payment->sale_id);
            $arAccountId = $this->getMappedAccount('App\Models\Customer', $sale->customer_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($payment), $payment->id)
                ->setSourceSubtype('customer_payment')
                ->setEventType($eventType)
                ->setReference(($payment->payment_reference ?? 'PAY') . '-' . $payment->id . '-' . strtoupper($eventType))
                ->setDate($payment->payment_at ?? $payment->created_at->toDateString())
                ->setNote($payment->payment_note);

            $amount = $payment->amount;

            if ($payment->paying_method === 'Deposit') {
                $depositLiabilityAccountId = $this->getRoleAccountId(self::ROLE_CUSTOMER_DEPOSIT);
                $builder->addDebit($depositLiabilityAccountId, $amount, 'Deposit Applied to Sale ' . $sale->reference_no)
                        ->setSourceSubtype('deposit_redemption');
            } elseif ($payment->paying_method === 'Gift Card') {
                $giftCardLiabilityAccountId = $this->getRoleAccountId(self::ROLE_GIFT_CARD_LIABILITY);
                $builder->addDebit($giftCardLiabilityAccountId, $amount, 'Gift Card Applied to Sale ' . $sale->reference_no)
                        ->setSourceSubtype('gift_card_redemption');
            } elseif ($payment->paying_method === 'Points') {
                $rewardsLiabilityAccountId = $this->getRoleAccountId(self::ROLE_REWARDS_LIABILITY);
                $builder->addDebit($rewardsLiabilityAccountId, $amount, 'Reward Points Applied to Sale ' . $sale->reference_no)
                        ->setSourceSubtype('reward_point_redemption');
            } else {
                $cashAccountId = $payment->account_id
                    ? $this->getMappedAccount('App\Models\Account', $payment->account_id, $this->getCashAccountId())
                    : $this->getCashAccountId();
                $builder->addDebit($cashAccountId, $amount, 'Payment Received for Sale ' . $sale->reference_no);
            }

            $builder->addCredit($arAccountId, $amount, 'AR Clearance for Sale ' . $sale->reference_no);

            return $builder->save();
        });
    }

    private function recordSupplierPayment($payment, string $eventType): AccountingResult
    {
        return $this->executeSafe(get_class($payment), $payment->id, function () use ($payment, $eventType) {
            $purchase = Purchase::findOrFail($payment->purchase_id);
            $apControlAccountId = $this->getRoleAccountId(self::ROLE_ACCOUNTS_PAYABLE);
            $apAccountId = $purchase->supplier_id
                ? $this->getMappedAccount('App\Models\Supplier', (int) $purchase->supplier_id, $apControlAccountId)
                : $apControlAccountId;
            if (empty($payment->account_id) || !\App\Models\Account::whereKey($payment->account_id)->exists()) {
                throw new Exception('Purchase payment requires a valid Cash/Bank payment source.');
            }
            $cashAccountId = $this->getMappedCashAccountId($payment->account_id);
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($payment), $payment->id)
                ->setSourceSubtype('supplier_payment')
                ->setEventType($eventType)
                ->setReference(($payment->payment_reference ?? 'PAY') . '-' . $payment->id . '-' . strtoupper($eventType))
                ->setDate($payment->payment_at ?? $payment->created_at->toDateString())
                ->setNote($payment->payment_note)
                ->addDebit($apAccountId, $payment->amount, 'AP Clearance for Purchase ' . $purchase->reference_no)
                ->addCredit($cashAccountId, $payment->amount, 'Payment Sent for Purchase ' . $purchase->reference_no);

            return $builder->save();
        });
    }

    private function recordCustomerRefund($payment, string $eventType): AccountingResult
    {
        return $this->executeSafe(get_class($payment), $payment->id, function () use ($payment, $eventType) {
            $sale = Sale::findOrFail($payment->sale_id);
            $arAccountId = $this->getMappedAccount('App\Models\Customer', $sale->customer_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_RECEIVABLE));
            $cashAccountId = $this->getMappedCashAccountId($payment->account_id);

            return JournalBuilder::create()
                ->setSource(get_class($payment), $payment->id)
                ->setSourceSubtype('customer_refund')
                ->setEventType($eventType)
                ->setReference(($payment->payment_reference ?? 'REF') . '-' . $payment->id . '-' . strtoupper($eventType))
                ->setDate($payment->payment_at ?? $payment->created_at->toDateString())
                ->setNote($payment->payment_note)
                ->addDebit($arAccountId, $payment->amount, 'Customer refund for Sale ' . $sale->reference_no)
                ->addCredit($cashAccountId, $payment->amount, 'Cash refund for Sale ' . $sale->reference_no)
                ->save();
        });
    }

    private function recordSupplierRefund($payment, string $eventType): AccountingResult
    {
        return $this->executeSafe(get_class($payment), $payment->id, function () use ($payment, $eventType) {
            $purchase = Purchase::findOrFail($payment->purchase_id);
            $apAccountId = $this->getMappedAccount('App\Models\Supplier', $purchase->supplier_id, $this->getRoleAccountId(self::ROLE_ACCOUNTS_PAYABLE));
            $cashAccountId = $payment->account_id
                ? $this->getMappedAccount('App\Models\Account', $payment->account_id, $this->getCashAccountId())
                : $this->getCashAccountId();

            return JournalBuilder::create()
                ->setSource(get_class($payment), $payment->id)
                ->setSourceSubtype('supplier_refund')
                ->setEventType($eventType)
                ->setReference(($payment->payment_reference ?? 'REF') . '-' . $payment->id . '-' . strtoupper($eventType))
                ->setDate($payment->payment_at ?? $payment->created_at->toDateString())
                ->setNote($payment->payment_note)
                ->addDebit($cashAccountId, $payment->amount, 'Supplier refund for Purchase ' . $purchase->reference_no)
                ->addCredit($apAccountId, $payment->amount, 'AP reinstatement for Purchase refund ' . $purchase->reference_no)
                ->save();
        });
    }

    public function recordDeposit($deposit, string $eventType = 'customer_deposit'): AccountingResult
    {
        return $this->executeSafe(get_class($deposit), $deposit->id, function () use ($deposit, $eventType) {
            $cashAccountId = $deposit->account_id
                ? $this->getMappedAccount('App\Models\Account', $deposit->account_id, $this->getCashAccountId())
                : $this->getCashAccountId();
            $depositLiabilityAccountId = $this->getRoleAccountId(self::ROLE_CUSTOMER_DEPOSIT);
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($deposit), $deposit->id)
                ->setSourceSubtype('deposit')
                ->setEventType($eventType)
                ->setReference('DEP-' . $deposit->id . '-' . strtoupper($eventType))
                ->setDate($deposit->created_at->toDateString())
                ->setNote($deposit->note)
                ->addDebit($cashAccountId, $deposit->amount, 'Customer Deposit Received')
                ->addCredit($depositLiabilityAccountId, $deposit->amount, 'Customer Deposit Liability');

            return $builder->save();
        });
    }

    public function recordMoneyTransfer($transfer, string $eventType = 'money_transfer'): AccountingResult
    {
        return $this->executeSafe(get_class($transfer), $transfer->id, function () use ($transfer, $eventType) {
            $fromAccountId = $this->getMappedAccount('App\Models\Account', $transfer->from_account_id, $this->getCashAccountId());
            $toAccountId = $this->getMappedAccount('App\Models\Account', $transfer->to_account_id, $this->getCashAccountId());
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($transfer), $transfer->id)
                ->setSourceSubtype('money_transfer')
                ->setEventType($eventType)
                ->setReference(($transfer->reference_no ?? 'TRF-' . $transfer->id) . '-' . strtoupper($eventType))
                ->setDate($transfer->created_at->toDateString())
                ->setNote($transfer->note ?: 'Money Transfer')
                ->addDebit($toAccountId, $transfer->amount, 'Transfer In')
                ->addCredit($fromAccountId, $transfer->amount, 'Transfer Out');

            return $builder->save();
        });
    }

    public function recordGiftCardSale($giftCard, string $eventType = 'gift_card_sale'): AccountingResult
    {
        return $this->executeSafe(get_class($giftCard), $giftCard->id, function () use ($giftCard, $eventType) {
            $cashAccountId = $this->getCashAccountId();
            $giftCardLiabilityAccountId = $this->getRoleAccountId(self::ROLE_GIFT_CARD_LIABILITY);
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($giftCard), $giftCard->id)
                ->setSourceSubtype('gift_card')
                ->setEventType($eventType)
                ->setReference('GC-' . $giftCard->card_no . '-' . strtoupper($eventType))
                ->setDate($giftCard->created_at->toDateString())
                ->setNote('Gift Card Sold')
                ->addDebit($cashAccountId, $giftCard->amount, 'Gift Card Sale Receipt')
                ->addCredit($giftCardLiabilityAccountId, $giftCard->amount, 'Gift Card Liability');

            return $builder->save();
        });
    }

    public function recordPointsEarned($sale, $pointsEarned, $monetaryValue, string $eventType = 'points_earned'): AccountingResult
    {
        if ($monetaryValue <= 0) {
            return AccountingResult::success(null);
        }

        return $this->executeSafe(get_class($sale), $sale->id, function () use ($sale, $pointsEarned, $monetaryValue, $eventType) {
            $rewardsExpenseAccountId = $this->getRoleAccountId(self::ROLE_REWARDS_EXPENSE);
            $rewardsLiabilityAccountId = $this->getRoleAccountId(self::ROLE_REWARDS_LIABILITY);
            
            $builder = JournalBuilder::create()
                ->setSource(get_class($sale), $sale->id)
                ->setSourceSubtype('points_earned')
                ->setEventType($eventType)
                ->setReference('PTS-' . $sale->id . '-' . strtoupper($eventType))
                ->setDate($sale->created_at->toDateString())
                ->setNote("Earned $pointsEarned points for Sale " . $sale->reference_no)
                ->addDebit($rewardsExpenseAccountId, $monetaryValue, 'Loyalty Rewards Expense')
                ->addCredit($rewardsLiabilityAccountId, $monetaryValue, 'Customer Rewards Liability');

            return $builder->save();
        });
    }
}
