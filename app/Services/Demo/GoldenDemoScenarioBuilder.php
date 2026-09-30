<?php

namespace App\Services\Demo;

use App\Models\User;
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Account;
use App\Models\Purchase;
use App\Models\Payment;



use App\Services\Domain\PurchaseDomainService;
use App\Services\Domain\TransferDomainService;
use App\Services\Domain\SaleDomainService;
use App\Services\Domain\AdjustmentDomainService;
use App\Services\Domain\ReturnDomainService;
use App\Services\Domain\ExchangeDomainService;
use App\Services\Domain\ReturnPurchaseDomainService;



use App\Services\PaymentService;



use Modules\Restaurant\Entities\Floors;
use App\Models\Table;
use Modules\Restaurant\Entities\ModifierGroup;
use Modules\Restaurant\Entities\Modifier;
use Modules\Restaurant\Entities\ProductModifierGroup;
use Modules\Restaurant\Entities\ProductModifierGroupModifier;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoldenDemoScenarioBuilder
{
    private GoldenDemoValidator $validator;
    private GoldenDemoManifestService $manifestService;
    private GoldenDemoCatalogService $catalogService;

    public function __construct(
        ?GoldenDemoValidator $validator = null,
        ?GoldenDemoManifestService $manifestService = null,
        ?GoldenDemoCatalogService $catalogService = null
    )
    {
        $this->validator = $validator ?? new GoldenDemoValidator();
        $this->manifestService = $manifestService ?? new GoldenDemoManifestService();
        $this->catalogService = $catalogService ?? new GoldenDemoCatalogService();
    }

    /**
     * Build Phases 1 to 3 of the Golden Demo Scenario.
     *
     * @return array
     */
    public function buildPhases1To3(bool $force = false): array
    {
        return $this->buildPhases1To4($force);
    }

    /**
     * Build Phases 1 to 4 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To4(bool $force = false): array
    {
        // Safety check
        GoldenDemoSafety::assertSafeToBuild($force);

        if ($force) {
            $this->cleanupDemoScenarios();
        }

        // Authenticate as Admin user
        $adminUser = User::where('role_id', 1)->where('is_active', true)->first() ?? User::first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Admin',
                'email' => 'admin@salepro.com',
                'phone' => '123456789',
                'company_name' => 'SalePro',
                'role_id' => 1,
                'is_active' => true,
                'is_deleted' => false,
                'password' => bcrypt('admin'),
            ]);
        }
        Auth::login($adminUser);

        // Master catalog gate: the immutable catalog exists before the economic baseline.
        if ($force || !$this->manifestService->exists()) {
            $catalogBaseline = $this->catalogService->prepareForPhase1();
            $catalogValidation = $this->catalogService->validateCompleteness($catalogBaseline);
            if (!$catalogValidation['passed']) {
                throw new RuntimeException('MASTER CATALOG GATE FAILED before Phase 1: '.json_encode($catalogValidation['failures']));
            }

            // Phase 1: Economic Baseline Recording
            $baseline = $this->validator->recordBaseline();
            $this->manifestService->start([
                'version' => '1.0',
                'database_identifier' => DB::connection()->getDatabaseName(),
                'phases_completed' => ['Phase 1'],
                'catalog_baseline' => $catalogBaseline,
                'catalog_pre_phase1_validation' => $catalogValidation,
                'baseline' => $baseline,
            ]);
        } else {
            $existingManifest = $this->manifestService->read();
            $this->manifestService->assertOwnedByActiveDatabase($existingManifest);
            if (!isset($existingManifest['catalog_baseline'])) {
                throw new RuntimeException('Golden Demo manifest has no immutable master catalog baseline.');
            }
            $catalogValidation = $this->catalogService->validateCompleteness($existingManifest['catalog_baseline']);
            if (!$catalogValidation['passed']) {
                throw new RuntimeException('MASTER CATALOG GATE FAILED before phase resume: '.json_encode($catalogValidation['failures']));
            }
            $catalogBaseline = $existingManifest['catalog_baseline'];
            $baseline = $existingManifest['baseline'] ?? $this->validator->recordBaseline();
        }

        // Phase 2: Create Supporting Masters
        $mastersCreated = $this->createSupportingMasters();

        // Phase 3: Open Cash Register
        $cashRegisterData = $this->openCashRegister($adminUser, $mastersCreated['warehouses'][0]['id']);

        // Run Phase 3 Hard Validation Gate
        $validationResultPhase3 = $this->validator->validatePhase3($baseline, $mastersCreated, $cashRegisterData);
        if (!$validationResultPhase3['passed']) {
            throw new RuntimeException(
                "HARD VALIDATION GATE FAILED after Phase 3! Stopping execution. Details: " .
                json_encode($validationResultPhase3['integrity'] ?? $validationResultPhase3)
            );
        }

        // Phase 4: Create Purchase & Purchase Payment Scenarios
        $purchasePhaseResults = $this->createPurchaseScenarios($mastersCreated, $adminUser);

        // Run Phase 4 Hard Validation Gate
        $validationResultPhase4 = $this->validator->validatePhase4(
            $baseline,
            $purchasePhaseResults['purchases'],
            $purchasePhaseResults['payments']
        );

        if (!$validationResultPhase4['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 4! Details: ".json_encode($validationResultPhase4));
        }











        // Save Golden Manifest
        $manifestData = [
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4'],
            'catalog_baseline' => $catalogBaseline,
            'catalog_pre_phase1_validation' => $catalogValidation,
            'baseline' => $baseline,
            'masters_created' => $mastersCreated,
            'cash_register' => $cashRegisterData,
            'purchases_created' => $purchasePhaseResults['purchases'],
            'payments_created' => $purchasePhaseResults['payments'],
            'phase3_validation' => $validationResultPhase3,
            'phase4_validation' => $validationResultPhase4,
        ];

        $this->manifestService->merge($manifestData);

        return [
            'manifest_path' => $this->manifestService->path(),
            'validation' => $validationResultPhase4,
        ];
    }

    /**
     * Create deterministic supporting masters for Warehouses, Customers, Suppliers, and Restaurant.
     *
     * @return array
     */
    private function createSupportingMasters(): array
    {
        $created = [
            'warehouses' => [],
            'customers' => [],
            'suppliers' => [],
            'floors' => [],
            'tables' => [],
            'modifier_groups' => [],
            'modifiers' => [],
            'accounts' => [],
        ];

        // 0. Ensure Accounting Engine Active & Double-Entry Authoritative
        $activator = app(\App\Services\AccountingActivationService::class);
        $config = $activator->getConfig();
        if (!$config->enabled) {
            $mode = $activator->requiresExistingBusinessMode() ? 'existing_business' : 'new_business';
            $activator->activate($mode, $mode === 'existing_business');
        } else {
            $session = \App\Models\AccountingActivationSession::orderByDesc('activated_at')
                ->orderByDesc('id')
                ->first();
            if (!$session
                || $session->mode !== $config->activation_mode
                || $session->start_date !== $config->start_date
                || (int) $session->opening_journal_entry_id !== (int) $config->opening_journal_entry_id) {
                throw new RuntimeException('Active accounting configuration has no matching activation session.');
            }
        }



        // 0. Ensure Operational Payment Accounts (Cash & Bank)

        $cashAcc = \App\Models\Account::where('account_no', '1001')->first()
            ?? \App\Models\Account::where('name', 'LIKE', '%Cash%')->first()
            ?? \App\Models\Account::create([
                'account_no' => '1001',
                'name' => 'Cash Account',
                'initial_balance' => 1000.00,
                'total_balance' => 1000.00,
                'is_default' => 1,
                'is_active' => true,
                'is_payment' => true,
            ]);

        $bankAcc = \App\Models\Account::where('account_no', '1002')->first()
            ?? \App\Models\Account::where('name', 'LIKE', '%Bank%')->first()
            ?? \App\Models\Account::create([
                'account_no' => '1002',
                'name' => 'Bank Account',
                'initial_balance' => 5000.00,
                'total_balance' => 5000.00,
                'is_default' => 0,
                'is_active' => true,
                'is_payment' => true,
            ]);

        $created['accounts'][] = ['id' => $cashAcc->id, 'name' => $cashAcc->name, 'initial_balance' => (float) $cashAcc->initial_balance];
        $created['accounts'][] = ['id' => $bankAcc->id, 'name' => $bankAcc->name, 'initial_balance' => (float) $bankAcc->initial_balance];




        // 1. Warehouses
        $warehouseDefinitions = [
            ['name' => 'Main Warehouse', 'phone' => '111-222-3333', 'email' => 'main@salepro.com', 'address' => '100 Central Logistics Way'],
            ['name' => 'Downtown Retail Store', 'phone' => '222-333-4444', 'email' => 'retail@salepro.com', 'address' => '200 High Street Retail Zone'],
            ['name' => 'Restaurant Store', 'phone' => '333-444-5555', 'email' => 'restaurant@salepro.com', 'address' => '300 Culinary Plaza'],
            ['name' => 'Service Center', 'phone' => '444-555-6666', 'email' => 'service@salepro.com', 'address' => '400 Repair Tech Park'],
        ];

        foreach ($warehouseDefinitions as $def) {
            $wh = Warehouse::firstOrCreate(
                ['name' => $def['name']],
                [
                    'phone' => $def['phone'],
                    'email' => $def['email'],
                    'address' => $def['address'],
                    'is_active' => true,
                ]
            );
            $created['warehouses'][] = ['id' => $wh->id, 'name' => $wh->name];
        }

        // 2. Customers
        $customerGroupId = CustomerGroup::where('is_active', true)->value('id') ?? 1;

        $customerDefinitions = [
            ['name' => 'Walk-in Customer', 'company_name' => 'Walk-in Customer', 'phone_number' => '0000000000', 'email' => 'walkin@salepro.com', 'type' => 'walkin'],
            ['name' => 'Alice Cash Buyer', 'company_name' => 'Alice Corp (Cash)', 'phone_number' => '555-0101', 'email' => 'alice@demo.com', 'type' => 'regular'],
            ['name' => 'Bob Instant Payment', 'company_name' => 'Bob Retail (Cash)', 'phone_number' => '555-0102', 'email' => 'bob@demo.com', 'type' => 'regular'],
            ['name' => 'Charlie Credit Corp', 'company_name' => 'Charlie Credit Inc', 'phone_number' => '555-0103', 'email' => 'charlie@demo.com', 'type' => 'regular'],
            ['name' => 'David Receivable Ltd', 'company_name' => 'David Holdings (Credit)', 'phone_number' => '555-0104', 'email' => 'david@demo.com', 'type' => 'regular'],
            ['name' => 'Eva Partial Payments', 'company_name' => 'Eva Solutions (Partial)', 'phone_number' => '555-0105', 'email' => 'eva@demo.com', 'type' => 'regular'],
            ['name' => 'Frank Installments Co', 'company_name' => 'Frank Enterprises (Partial)', 'phone_number' => '555-0106', 'email' => 'frank@demo.com', 'type' => 'regular'],
            ['name' => 'Grace Return Client', 'company_name' => 'Grace Boutique (Return)', 'phone_number' => '555-0107', 'email' => 'grace@demo.com', 'type' => 'regular'],
            ['name' => 'Henry Refund Enterprise', 'company_name' => 'Henry Logistics (Return)', 'phone_number' => '555-0108', 'email' => 'henry@demo.com', 'type' => 'regular'],
            ['name' => 'Ian Service Device Client', 'company_name' => 'Ian Tech Services (Repair)', 'phone_number' => '555-0109', 'email' => 'ian@demo.com', 'type' => 'regular'],
            ['name' => 'Jack Tech Repair Client', 'company_name' => 'Jack Repairs (Repair)', 'phone_number' => '555-0110', 'email' => 'jack@demo.com', 'type' => 'regular'],
            ['name' => 'Karen Delivery Order Customer', 'company_name' => 'Karen Bistro (Delivery)', 'phone_number' => '555-0111', 'email' => 'karen@demo.com', 'type' => 'regular'],
            ['name' => 'Leo Express Delivery Customer', 'company_name' => 'Leo Express (Delivery)', 'phone_number' => '555-0112', 'email' => 'leo@demo.com', 'type' => 'regular'],
            ['name' => 'Apex Trade Supplies', 'company_name' => 'Apex Group (B2B)', 'phone_number' => '555-0113', 'email' => 'apex@demo.com', 'type' => 'regular'],
            ['name' => 'Zenith Commercial Hub', 'company_name' => 'Zenith Hub (B2B)', 'phone_number' => '555-0114', 'email' => 'zenith@demo.com', 'type' => 'regular'],
            ['name' => 'Metro Retail Network', 'company_name' => 'Metro Retail (Retail)', 'phone_number' => '555-0115', 'email' => 'metro@demo.com', 'type' => 'regular'],
            ['name' => 'Orion Distribution Co', 'company_name' => 'Orion Dist (B2B)', 'phone_number' => '555-0116', 'email' => 'orion@demo.com', 'type' => 'regular'],
            ['name' => 'Pacific Supermarket', 'company_name' => 'Pacific Stores (FMCG)', 'phone_number' => '555-0117', 'email' => 'pacific@demo.com', 'type' => 'regular'],
            ['name' => 'Quantum Electronics Client', 'company_name' => 'Quantum Tech (Electronics)', 'phone_number' => '555-0118', 'email' => 'quantum@demo.com', 'type' => 'regular'],
            ['name' => 'Riverside Restaurant', 'company_name' => 'Riverside Dining (Dining)', 'phone_number' => '555-0119', 'email' => 'riverside@demo.com', 'type' => 'regular'],
        ];

        foreach ($customerDefinitions as $cDef) {
            $isWalkin = ($cDef['type'] === 'walkin');
            $creditLimit = $isWalkin ? 0.00 : 100000.00;
            $cust = Customer::firstOrCreate(
                ['phone_number' => $cDef['phone_number']],
                [
                    'customer_group_id' => $customerGroupId,
                    'name' => $cDef['name'],
                    'company_name' => $cDef['company_name'],
                    'email' => $cDef['email'],
                    'type' => $cDef['type'],
                    'address' => '123 Demo Street',
                    'city' => 'Demo City',
                    'country' => 'Demo Country',
                    'credit_limit' => $creditLimit,
                    'is_active' => true,
                ]
            );
            // Ensure credit limit is set appropriately even if record existed
            if ($isWalkin) {
                if ($cust->credit_limit != 0.00) {
                    $cust->credit_limit = 0.00;
                    $cust->save();
                }
            } elseif (!$cust->credit_limit) {
                $cust->credit_limit = 100000.00;
                $cust->save();
            }
            $created['customers'][] = ['id' => $cust->id, 'name' => $cust->name, 'type' => $cust->type, 'phone_number' => $cust->phone_number];

        }

        // 3. Suppliers
        $supplierDefinitions = [
            ['name' => 'Tech Distribution Ltd', 'company_name' => 'Tech Distribution Ltd', 'phone_number' => '666-0101', 'email' => 'tech@suppliers.com'],
            ['name' => 'FMCG Global Wholesalers', 'company_name' => 'FMCG Global Wholesalers', 'phone_number' => '666-0102', 'email' => 'fmcg@suppliers.com'],
            ['name' => 'Fashion World Ltd', 'company_name' => 'Fashion World Ltd', 'phone_number' => '666-0103', 'email' => 'fashion@suppliers.com'],
            ['name' => 'Fresh Ingredients Supply', 'company_name' => 'Fresh Ingredients Supply', 'phone_number' => '666-0104', 'email' => 'fresh@suppliers.com'],
            ['name' => 'Hardware Central', 'company_name' => 'Hardware Central', 'phone_number' => '666-0105', 'email' => 'hardware@suppliers.com'],
            ['name' => 'General Office Supplies', 'company_name' => 'General Office Supplies', 'phone_number' => '666-0106', 'email' => 'office@suppliers.com'],
            ['name' => 'Beverage & Syrup Supply Co', 'company_name' => 'Beverage & Syrup Co', 'phone_number' => '666-0107', 'email' => 'beverage@suppliers.com'],
            ['name' => 'Mobile Components Inc', 'company_name' => 'Mobile Components Inc', 'phone_number' => '666-0108', 'email' => 'mobileparts@suppliers.com'],
        ];

        foreach ($supplierDefinitions as $sDef) {
            $supp = Supplier::firstOrCreate(
                ['phone_number' => $sDef['phone_number']],
                [
                    'name' => $sDef['name'],
                    'company_name' => $sDef['company_name'],
                    'email' => $sDef['email'],
                    'address' => '456 Supply Avenue',
                    'city' => 'Supply Hub',
                    'country' => 'Supply Land',
                    'is_active' => true,
                ]
            );
            $created['suppliers'][] = ['id' => $supp->id, 'name' => $supp->name, 'phone_number' => $supp->phone_number];
        }

        // 4. Restaurant Structure: Floors & Tables
        $restaurantWarehouseId = $created['warehouses'][2]['id']; // Restaurant Store

        $groundFloor = Floors::firstOrCreate(
            ['name' => 'Ground Floor', 'warehouse_id' => $restaurantWarehouseId],
            ['status' => 1]
        );
        $firstFloor = Floors::firstOrCreate(
            ['name' => 'First Floor', 'warehouse_id' => $restaurantWarehouseId],
            ['status' => 1]
        );
        $created['floors'][] = ['id' => $groundFloor->id, 'name' => $groundFloor->name];
        $created['floors'][] = ['id' => $firstFloor->id, 'name' => $firstFloor->name];

        $tablesDefinition = [
            ['name' => 'G01', 'number_of_person' => 4, 'floor_id' => $groundFloor->id],
            ['name' => 'G02', 'number_of_person' => 2, 'floor_id' => $groundFloor->id],
            ['name' => 'G03', 'number_of_person' => 6, 'floor_id' => $groundFloor->id],
            ['name' => 'G04', 'number_of_person' => 4, 'floor_id' => $groundFloor->id],
            ['name' => 'F01', 'number_of_person' => 4, 'floor_id' => $firstFloor->id],
            ['name' => 'F02', 'number_of_person' => 2, 'floor_id' => $firstFloor->id],
            ['name' => 'F03', 'number_of_person' => 8, 'floor_id' => $firstFloor->id],
            ['name' => 'F04', 'number_of_person' => 4, 'floor_id' => $firstFloor->id],
        ];

        foreach ($tablesDefinition as $tDef) {
            $table = Table::firstOrCreate(
                ['name' => $tDef['name'], 'floor_id' => $tDef['floor_id']],
                [
                    'number_of_person' => $tDef['number_of_person'],
                    'is_active' => true,
                ]
            );
            $created['tables'][] = ['id' => $table->id, 'name' => $table->name, 'floor_id' => $table->floor_id];
        }

        // 5. Modifier Groups & Modifiers
        $groupDefs = [
            [
                'name' => 'Spice Level',
                'selection_type' => 'single',
                'min_selection' => 1,
                'max_selection' => 1,
                'is_required' => true,
                'modifiers' => [
                    ['name' => 'Mild', 'price_adjustment' => 0.00],
                    ['name' => 'Medium', 'price_adjustment' => 0.00],
                    ['name' => 'Hot', 'price_adjustment' => 0.00],
                ],
            ],
            [
                'name' => 'Cheese',
                'selection_type' => 'single',
                'min_selection' => 0,
                'max_selection' => 1,
                'is_required' => false,
                'modifiers' => [
                    ['name' => 'No Cheese', 'price_adjustment' => 0.00],
                    ['name' => 'Regular Cheese', 'price_adjustment' => 1.00],
                    ['name' => 'Extra Cheese', 'price_adjustment' => 2.00],
                ],
            ],
            [
                'name' => 'Extra Toppings',
                'selection_type' => 'multiple',
                'min_selection' => 0,
                'max_selection' => 3,
                'is_required' => false,
                'modifiers' => [
                    ['name' => 'Pepperoni', 'price_adjustment' => 2.50],
                    ['name' => 'Extra Sauce', 'price_adjustment' => 0.50],
                    ['name' => 'Mushrooms', 'price_adjustment' => 1.50],
                ],
            ],
            [
                'name' => 'Drink Choice',
                'selection_type' => 'single',
                'min_selection' => 0,
                'max_selection' => 1,
                'is_required' => false,
                'modifiers' => [
                    ['name' => 'Cola', 'price_adjustment' => 2.00],
                    ['name' => 'Lemonade', 'price_adjustment' => 2.00],
                    ['name' => 'Iced Tea', 'price_adjustment' => 2.50],
                ],
            ],
        ];

        foreach ($groupDefs as $gDef) {
            $mGroup = ModifierGroup::firstOrCreate(
                ['name' => $gDef['name']],
                [
                    'selection_type' => $gDef['selection_type'],
                    'min_selection' => $gDef['min_selection'],
                    'max_selection' => $gDef['max_selection'],
                    'is_required' => $gDef['is_required'],
                    'is_active' => true,
                ]
            );
            $created['modifier_groups'][] = ['id' => $mGroup->id, 'name' => $mGroup->name];

            foreach ($gDef['modifiers'] as $modDef) {
                $mod = Modifier::firstOrCreate(
                    ['modifier_group_id' => $mGroup->id, 'name' => $modDef['name']],
                    [
                        'price_adjustment' => $modDef['price_adjustment'],
                        'is_active' => true,
                    ]
                );
                $created['modifiers'][] = ['id' => $mod->id, 'name' => $mod->name, 'price' => $mod->price_adjustment];
            }
        }

        // 7. Ensure at least one Variant Product exists

        if (ProductVariant::count() == 0) {
            $variantProd = Product::create([
                'name' => 'Designer Cotton T-Shirt',
                'code' => 'TSHIRT-VAR-001',
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'category_id' => 1,
                'unit_id' => 1,
                'purchase_unit_id' => 1,
                'sale_unit_id' => 1,
                'cost' => 20.00,
                'price' => 40.00,
                'qty' => 0,
                'is_variant' => true,
                'is_active' => true,
            ]);
            $varObj = \App\Models\Variant::firstOrCreate(['name' => 'Medium']);
            ProductVariant::create([
                'product_id' => $variantProd->id,
                'variant_id' => $varObj->id,
                'position' => 1,
                'item_code' => 'TSHIRT-M',
                'additional_cost' => 0,
                'additional_price' => 0,
                'qty' => 0,
            ]);
            \App\Models\Product_Warehouse::create([
                'product_id' => $variantProd->id,
                'variant_id' => $varObj->id,
                'warehouse_id' => 2, // Downtown Retail Store
                'qty' => 0,
            ]);
            \App\Models\Product_Warehouse::create([
                'product_id' => $variantProd->id,
                'variant_id' => $varObj->id,
                'warehouse_id' => 1, // Main Warehouse
                'qty' => 0,
            ]);

        }

        return $created;
    }


    /**
     * Open Cash Register through canonical SalePro CashRegister workflow.
     *
     * @param User $user
     * @param int $warehouseId
     * @return array
     */
    private function openCashRegister(User $user, int $warehouseId): array
    {
        CashRegister::where('user_id', $user->id)
            ->where('warehouse_id', $warehouseId)
            ->where('status', true)
            ->update(['status' => false]);

        $register = CashRegister::create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouseId,
            'cash_in_hand' => 500.00,
            'status' => true,
        ]);

        return [
            'id' => $register->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouseId,
            'cash_in_hand' => 500.00,
            'status' => true,
        ];
    }

    /**
     * Create 10 Purposeful Deterministic Purchases & Subsequent Purchase Payments.
     *
     * @param array $masters
     * @param User $user
     * @return array
     */
    public function createPurchaseScenarios(array $masters, User $user): array
    {
        /** @var PurchaseDomainService $purchaseService */
        $purchaseService = app(PurchaseDomainService::class);
        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $mainWhId = $masters['warehouses'][0]['id'];
        $retailWhId = $masters['warehouses'][1]['id'];
        $restaurantWhId = $masters['warehouses'][2]['id'];
        $serviceWhId = $masters['warehouses'][3]['id'];

        $techSuppId = $masters['suppliers'][0]['id'];
        $fmcgSuppId = $masters['suppliers'][1]['id'];
        $fashionSuppId = $masters['suppliers'][2]['id'];
        $freshSuppId = $masters['suppliers'][3]['id'];
        $hardwareSuppId = $masters['suppliers'][4]['id'];
        $officeSuppId = $masters['suppliers'][5]['id'];
        $beverageSuppId = $masters['suppliers'][6]['id'];
        $mobileSuppId = $masters['suppliers'][7]['id'];

        $stdProducts = Product::where('is_variant', false)->where('is_active', true)->get();
        if ($stdProducts->isEmpty()) {
            $stdProducts = Product::take(20)->get();
        }
        $varProductVariants = ProductVariant::get();

        $getStdProduct = function ($index) use ($stdProducts) {
            $count = $stdProducts->count();
            return $stdProducts->values()->get($index % $count);
        };

        $getVarVariant = function ($index) use ($varProductVariants) {
            $count = $varProductVariants->count();
            return $count > 0 ? $varProductVariants->values()->get($index % $count) : null;
        };

        $purchasesCreated = [];
        $paymentsCreated = [];

        // helper to get unit name
        $getUnitName = function ($p) {
            return Unit::find($p->unit_id)->unit_name ?? 'piece';
        };

        // DEMO-PUR-001: Fully Paid Electronics (Tech Distribution Ltd, Main Warehouse, 100% Bank)
        $p1_1 = $getStdProduct(0);
        $p1_2 = $getStdProduct(1);

        $pur1 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-001',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $techSuppId,
            'status' => 1,
            'payment_status' => 4, // Fully Paid
            'item' => 2,
            'total_qty' => 20,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 1000,
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'product_id' => [$p1_1->id, $p1_2->id],
            'product_code' => [$p1_1->code, $p1_2->code],
            'qty' => [10, 10],
            'recieved' => [10, 10],
            'purchase_unit' => [$getUnitName($p1_1), $getUnitName($p1_2)],
            'unit_cost' => [50, 50],
            'net_unit_cost' => [50, 50],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [75, 75],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [500, 500],
            'paying_amount' => [1000],
            'amount' => [1000],
            'paid_by_id' => [1],
            'account_id' => 2, // Bank Account
            'payment_note' => '100% Bank Payment DEMO-PUR-001',
        ], $user);
        $purchasesCreated[] = ['id' => $pur1->id, 'reference_no' => 'DEMO-PUR-001', 'supplier' => 'Tech Distribution Ltd', 'grand_total' => 1000, 'paid' => 1000];

        // DEMO-PUR-002: Partial Electronics (Tech Distribution Ltd, Main Warehouse, Partial Bank)
        $p2_1 = $getStdProduct(2);
        $p2_2 = $getStdProduct(3);
        $pur2 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-002',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $techSuppId,
            'status' => 1,
            'payment_status' => 3, // Partial
            'item' => 2,
            'total_qty' => 40,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 4000,
            'grand_total' => 4000,
            'paid_amount' => 2500,
            'product_id' => [$p2_1->id, $p2_2->id],
            'product_code' => [$p2_1->code, $p2_2->code],
            'qty' => [20, 20],
            'recieved' => [20, 20],
            'purchase_unit' => [$getUnitName($p2_1), $getUnitName($p2_2)],
            'unit_cost' => [100, 100],
            'net_unit_cost' => [100, 100],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [150, 150],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [2000, 2000],
            'paying_amount' => [2500],
            'amount' => [2500],
            'paid_by_id' => [1],
            'account_id' => 2, // Bank Account
            'payment_note' => 'Partial Bank Payment DEMO-PUR-002',
        ], $user);
        $purchasesCreated[] = ['id' => $pur2->id, 'reference_no' => 'DEMO-PUR-002', 'supplier' => 'Tech Distribution Ltd', 'grand_total' => 4000, 'paid' => 2500];

        // DEMO-PUR-003: Full Credit Purchase (FMCG Global Wholesalers, Main Warehouse, 0 Paid)
        $p3_1 = $getStdProduct(4);
        $p3_2 = $getStdProduct(5);
        $p3_3 = $getStdProduct(6);
        $pur3 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-003',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $fmcgSuppId,
            'status' => 1,
            'payment_status' => 1, // Pending/Unpaid
            'item' => 3,
            'total_qty' => 150,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 3000,
            'grand_total' => 3000,
            'paid_amount' => 0,
            'product_id' => [$p3_1->id, $p3_2->id, $p3_3->id],
            'product_code' => [$p3_1->code, $p3_2->code, $p3_3->code],
            'qty' => [50, 50, 50],
            'recieved' => [50, 50, 50],
            'purchase_unit' => [$getUnitName($p3_1), $getUnitName($p3_2), $getUnitName($p3_3)],
            'unit_cost' => [20, 20, 20],
            'net_unit_cost' => [20, 20, 20],
            'net_unit_margin' => [0, 0, 0],
            'net_unit_margin_type' => ['flat', 'flat', 'flat'],
            'net_unit_price' => [30, 30, 30],
            'discount' => [0, 0, 0],
            'tax_rate' => [0, 0, 0],
            'tax' => [0, 0, 0],
            'subtotal' => [1000, 1000, 1000],
        ], $user);
        $purchasesCreated[] = ['id' => $pur3->id, 'reference_no' => 'DEMO-PUR-003', 'supplier' => 'FMCG Global Wholesalers', 'grand_total' => 3000, 'paid' => 0];

        // DEMO-PUR-004: Variant Fashion Purchase (Fashion World Ltd, Downtown Retail Store)
        $pv1 = $getVarVariant(0);
        $pv2 = $getVarVariant(1);
        if ($pv1 && $pv2) {
            $p4_1 = Product::find($pv1->product_id);
            $p4_2 = Product::find($pv2->product_id);
            $code1 = $pv1->item_code;
            $code2 = $pv2->item_code;
        } else {
            $p4_1 = $getStdProduct(20);
            $p4_2 = $getStdProduct(21);
            $code1 = $p4_1->code;
            $code2 = $p4_2->code;
        }

        $pur4 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-004',
            'warehouse_id' => $retailWhId,
            'supplier_id' => $fashionSuppId,
            'status' => 1,
            'payment_status' => 4, // Fully Paid
            'item' => 2,
            'total_qty' => 30,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 900,
            'grand_total' => 900,
            'paid_amount' => 900,
            'product_id' => [$p4_1->id, $p4_2->id],
            'product_code' => [$code1, $code2],
            'qty' => [15, 15],
            'recieved' => [15, 15],
            'purchase_unit' => [$getUnitName($p4_1), $getUnitName($p4_2)],
            'unit_cost' => [30, 30],
            'net_unit_cost' => [30, 30],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [50, 50],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [450, 450],
            'paying_amount' => [900],
            'amount' => [900],
            'paid_by_id' => [1],
            'account_id' => 2, // Bank Account
            'payment_note' => 'Bank Payment DEMO-PUR-004',
        ], $user);
        $purchasesCreated[] = ['id' => $pur4->id, 'reference_no' => 'DEMO-PUR-004', 'supplier' => 'Fashion World Ltd', 'grand_total' => 900, 'paid' => 900];


        // DEMO-PUR-005: Restaurant Ingredient Purchase (Fresh Ingredients Supply, Restaurant Store)
        $p5_1 = $getStdProduct(7);
        $p5_2 = $getStdProduct(8);
        $p5_3 = $getStdProduct(9);
        $pur5 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-005',
            'warehouse_id' => $restaurantWhId,
            'supplier_id' => $freshSuppId,
            'status' => 1,
            'payment_status' => 4,
            'item' => 3,
            'total_qty' => 120,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 1800,
            'grand_total' => 1800,
            'paid_amount' => 1800,
            'product_id' => [$p5_1->id, $p5_2->id, $p5_3->id],
            'product_code' => [$p5_1->code, $p5_2->code, $p5_3->code],
            'qty' => [40, 40, 40],
            'recieved' => [40, 40, 40],
            'purchase_unit' => [$getUnitName($p5_1), $getUnitName($p5_2), $getUnitName($p5_3)],
            'unit_cost' => [15, 15, 15],
            'net_unit_cost' => [15, 15, 15],
            'net_unit_margin' => [0, 0, 0],
            'net_unit_margin_type' => ['flat', 'flat', 'flat'],
            'net_unit_price' => [25, 25, 25],
            'discount' => [0, 0, 0],
            'tax_rate' => [0, 0, 0],
            'tax' => [0, 0, 0],
            'subtotal' => [600, 600, 600],
            'paying_amount' => [1800],
            'amount' => [1800],
            'paid_by_id' => [1],
            'account_id' => 2,
            'payment_note' => '100% Bank Payment DEMO-PUR-005',
        ], $user);
        $purchasesCreated[] = ['id' => $pur5->id, 'reference_no' => 'DEMO-PUR-005', 'supplier' => 'Fresh Ingredients Supply', 'grand_total' => 1800, 'paid' => 1800];

        // DEMO-PUR-006: Hardware Purchase (Hardware Central, Main Warehouse, Partial Bank)
        $p6_1 = $getStdProduct(10);
        $p6_2 = $getStdProduct(11);
        $pur6 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-006',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $hardwareSuppId,
            'status' => 1,
            'payment_status' => 3, // Partial
            'item' => 2,
            'total_qty' => 50,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 2000,
            'grand_total' => 2000,
            'paid_amount' => 800,
            'product_id' => [$p6_1->id, $p6_2->id],
            'product_code' => [$p6_1->code, $p6_2->code],
            'qty' => [25, 25],
            'recieved' => [25, 25],
            'purchase_unit' => [$getUnitName($p6_1), $getUnitName($p6_2)],
            'unit_cost' => [40, 40],
            'net_unit_cost' => [40, 40],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [60, 60],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [1000, 1000],
            'paying_amount' => [800],
            'amount' => [800],
            'paid_by_id' => [1],
            'account_id' => 2,
            'payment_note' => 'Partial Bank Payment DEMO-PUR-006',
        ], $user);
        $purchasesCreated[] = ['id' => $pur6->id, 'reference_no' => 'DEMO-PUR-006', 'supplier' => 'Hardware Central', 'grand_total' => 2000, 'paid' => 800];

        // DEMO-PUR-007: Office Supply Purchase (General Office Supplies, Cash Payment - tests Cash baseline delta!)
        $p7_1 = $getStdProduct(12);
        $p7_2 = $getStdProduct(13);
        $pur7 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-007',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $officeSuppId,
            'status' => 1,
            'payment_status' => 4, // Fully Paid Cash
            'item' => 2,
            'total_qty' => 60,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 600,
            'grand_total' => 600,
            'paid_amount' => 600,
            'product_id' => [$p7_1->id, $p7_2->id],
            'product_code' => [$p7_1->code, $p7_2->code],
            'qty' => [30, 30],
            'recieved' => [30, 30],
            'purchase_unit' => [$getUnitName($p7_1), $getUnitName($p7_2)],
            'unit_cost' => [10, 10],
            'net_unit_cost' => [10, 10],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [15, 15],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [300, 300],
            'paying_amount' => [600],
            'amount' => [600],
            'paid_by_id' => [1],
            'account_id' => 1, // Cash Account!
            'payment_note' => '100% Cash Payment DEMO-PUR-007',
        ], $user);
        $purchasesCreated[] = ['id' => $pur7->id, 'reference_no' => 'DEMO-PUR-007', 'supplier' => 'General Office Supplies', 'grand_total' => 600, 'paid' => 600];

        // DEMO-PUR-008: Beverage Purchase (Beverage & Syrup Supply Co, Restaurant Store)
        $p8_1 = $getStdProduct(14);
        $p8_2 = $getStdProduct(15);
        $pur8 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-008',
            'warehouse_id' => $restaurantWhId,
            'supplier_id' => $beverageSuppId,
            'status' => 1,
            'payment_status' => 4,
            'item' => 2,
            'total_qty' => 100,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 1200,
            'grand_total' => 1200,
            'paid_amount' => 1200,
            'product_id' => [$p8_1->id, $p8_2->id],
            'product_code' => [$p8_1->code, $p8_2->code],
            'qty' => [50, 50],
            'recieved' => [50, 50],
            'purchase_unit' => [$getUnitName($p8_1), $getUnitName($p8_2)],
            'unit_cost' => [12, 12],
            'net_unit_cost' => [12, 12],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [20, 20],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [600, 600],
            'paying_amount' => [1200],
            'amount' => [1200],
            'paid_by_id' => [1],
            'account_id' => 2,
            'payment_note' => '100% Bank Payment DEMO-PUR-008',
        ], $user);
        $purchasesCreated[] = ['id' => $pur8->id, 'reference_no' => 'DEMO-PUR-008', 'supplier' => 'Beverage & Syrup Supply Co', 'grand_total' => 1200, 'paid' => 1200];

        // DEMO-PUR-009: Repair Parts Purchase (Mobile Components Inc, Service Center)
        $p9_1 = $getStdProduct(16);
        $p9_2 = $getStdProduct(17);
        $pur9 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-009',
            'warehouse_id' => $serviceWhId,
            'supplier_id' => $mobileSuppId,
            'status' => 1,
            'payment_status' => 1, // Pending/Unpaid
            'item' => 2,
            'total_qty' => 40,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 1800,
            'grand_total' => 1800,
            'paid_amount' => 0,
            'product_id' => [$p9_1->id, $p9_2->id],
            'product_code' => [$p9_1->code, $p9_2->code],
            'qty' => [20, 20],
            'recieved' => [20, 20],
            'purchase_unit' => [$getUnitName($p9_1), $getUnitName($p9_2)],
            'unit_cost' => [45, 45],
            'net_unit_cost' => [45, 45],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [70, 70],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [900, 900],
        ], $user);
        $purchasesCreated[] = ['id' => $pur9->id, 'reference_no' => 'DEMO-PUR-009', 'supplier' => 'Mobile Components Inc', 'grand_total' => 1800, 'paid' => 0];

        // DEMO-PUR-010: Multi-Line Mixed Purchase (Tech Distribution Ltd, Main Warehouse)
        $p10_1 = $getStdProduct(18);
        $p10_2 = $getStdProduct(19);
        $pur10 = $purchaseService->createPurchase([
            'reference_no' => 'DEMO-PUR-010',
            'warehouse_id' => $mainWhId,
            'supplier_id' => $techSuppId,
            'status' => 1,
            'payment_status' => 4, // Fully Paid
            'item' => 2,
            'total_qty' => 20,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 3000,
            'grand_total' => 3000,
            'paid_amount' => 3000,
            'product_id' => [$p10_1->id, $p10_2->id],
            'product_code' => [$p10_1->code, $p10_2->code],
            'qty' => [10, 10],
            'recieved' => [10, 10],
            'purchase_unit' => [$getUnitName($p10_1), $getUnitName($p10_2)],
            'unit_cost' => [150, 150],
            'net_unit_cost' => [150, 150],
            'net_unit_margin' => [0, 0],
            'net_unit_margin_type' => ['flat', 'flat'],
            'net_unit_price' => [220, 220],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [1500, 1500],
            'paying_amount' => [3000],
            'amount' => [3000],
            'paid_by_id' => [1],
            'account_id' => 2,
            'payment_note' => '100% Bank Payment DEMO-PUR-010',
        ], $user);
        $purchasesCreated[] = ['id' => $pur10->id, 'reference_no' => 'DEMO-PUR-010', 'supplier' => 'Tech Distribution Ltd', 'grand_total' => 3000, 'paid' => 3000];


        // SUBSEQUENT PURCHASE PAYMENTS
        // Payment A: Partial later payment of 500 on DEMO-PUR-002 (Tech Distribution Ltd)
        $payA = $paymentService->payForPurchase([
            'paying_amount' => 500,
            'amount' => 500,
            'paid_by_id' => 1,
            'cheque_no' => '',
            'account_id' => 2, // Bank Account
            'payment_note' => 'Later Payment A on DEMO-PUR-002',
            'purchase_id' => $pur2->id,
            'currency_id' => $pur2->currency_id,
            'exchange_rate' => 1,
            'payment_at' => date('Y-m-d H:i:s'),
        ]);
        $paymentsCreated[] = ['purchase_reference' => 'DEMO-PUR-002', 'amount' => 500, 'type' => 'Partial Later Payment', 'account_id' => 2];

        // Payment B: Complete payment of 1200 on DEMO-PUR-006 (Hardware Central)
        $payB = $paymentService->payForPurchase([
            'paying_amount' => 1200,
            'amount' => 1200,
            'paid_by_id' => 1,
            'cheque_no' => '',
            'account_id' => 2, // Bank Account
            'payment_note' => 'Later Payment B completing DEMO-PUR-006',
            'purchase_id' => $pur6->id,
            'currency_id' => $pur6->currency_id,
            'exchange_rate' => 1,
            'payment_at' => date('Y-m-d H:i:s'),
        ]);
        $paymentsCreated[] = ['purchase_reference' => 'DEMO-PUR-006', 'amount' => 1200, 'type' => 'Full Later Payment', 'account_id' => 2];

        return [
            'purchases' => $purchasesCreated,
            'payments' => $paymentsCreated,
        ];
    }

    /**
     * Build Phases 1 to 5 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To5(bool $force = false): array
    {
        $phase4Results = $this->buildPhases1To4($force);

        $adminUser = Auth::user();
        $manifest = $this->manifestService->read();
        $mastersCreated = $manifest['masters_created'];

        $transfersCreated = $this->createTransferScenarios($mastersCreated, $phase4Results, $adminUser);

        $validationResultPhase5 = $this->validator->validatePhase5(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $transfersCreated
        );

        if (!$validationResultPhase5['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 5! Details: ".json_encode($validationResultPhase5));
        }

        $adjustmentsCreated = $this->createStockAdjustmentScenarios($adminUser);


        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $mastersCreated,
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $transfersCreated,
            'adjustments_created' => $adjustmentsCreated,
            'phase5_validation' => $validationResultPhase5,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $mastersCreated,
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $transfersCreated,
            'adjustments_created' => $adjustmentsCreated,
            'validation' => $validationResultPhase5,
        ];

    }

    /**
     * Create Phase 5 Transfer Scenarios (DEMO-TRF-001 through DEMO-TRF-006).
     *
     * @param array $mastersCreated
     * @param array $purchasePhaseResults
     * @param User $user
     * @return array
     */
    public function createTransferScenarios(array $mastersCreated, array $purchasePhaseResults, User $user): array
    {
        /** @var TransferDomainService $transferService */
        $transferService = app(TransferDomainService::class);

        $mainWhId = $mastersCreated['warehouses'][0]['id'];
        $retailWhId = $mastersCreated['warehouses'][1]['id'];
        $restaurantWhId = $mastersCreated['warehouses'][2]['id'];
        $serviceWhId = $mastersCreated['warehouses'][3]['id'];

        $getStdProduct = function ($index) {
            $prods = Product::where('is_variant', false)->orWhereNull('is_variant')->get();
            return $prods->count() > $index ? $prods[$index] : Product::first();
        };

        $getUnitName = function ($prod) {
            $unit = Unit::find($prod->unit_id);
            return $unit ? $unit->unit_name : 'pc';
        };

        $transfersCreated = [];

        // DEMO-TRF-001: Main Warehouse -> Downtown Retail Store (Standard Electronics, 10 units)
        $p1 = $getStdProduct(0);
        $trf1 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-001',
            'from_warehouse_id' => $mainWhId,
            'to_warehouse_id' => $retailWhId,
            'status' => 1, // Completed
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p1->cost * 10,
            'shipping_cost' => 0,
            'grand_total' => $p1->cost * 10,
            'product_id' => [$p1->id],
            'product_code' => [$p1->code],
            'qty' => [10],
            'purchase_unit' => [$getUnitName($p1)],
            'net_unit_cost' => [$p1->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p1->cost * 10],
        ], $user);
        $transfersCreated[] = ['id' => $trf1->id, 'reference_no' => 'DEMO-TRF-001', 'from' => 'Main Warehouse', 'to' => 'Downtown Retail Store', 'total_qty' => 10];

        // DEMO-TRF-002: Main Warehouse -> Downtown Retail Store (Variant/Standard Fashion, 5 units)
        $p2 = $getStdProduct(1);
        $trf2 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-002',
            'from_warehouse_id' => $mainWhId,
            'to_warehouse_id' => $retailWhId,
            'status' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p2->cost * 5,
            'shipping_cost' => 0,
            'grand_total' => $p2->cost * 5,
            'product_id' => [$p2->id],
            'product_code' => [$p2->code],
            'qty' => [5],
            'purchase_unit' => [$getUnitName($p2)],
            'net_unit_cost' => [$p2->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p2->cost * 5],
        ], $user);
        $transfersCreated[] = ['id' => $trf2->id, 'reference_no' => 'DEMO-TRF-002', 'from' => 'Main Warehouse', 'to' => 'Downtown Retail Store', 'total_qty' => 5];

        // DEMO-TRF-003: Main Warehouse -> Restaurant Store (Raw Materials/Syrups, 15 units)
        $p3 = $getStdProduct(12);
        $trf3 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-003',
            'from_warehouse_id' => $mainWhId,
            'to_warehouse_id' => $restaurantWhId,
            'status' => 1,
            'total_qty' => 15,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p3->cost * 15,
            'shipping_cost' => 0,
            'grand_total' => $p3->cost * 15,
            'product_id' => [$p3->id],
            'product_code' => [$p3->code],
            'qty' => [15],
            'purchase_unit' => [$getUnitName($p3)],
            'net_unit_cost' => [$p3->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p3->cost * 15],
        ], $user);
        $transfersCreated[] = ['id' => $trf3->id, 'reference_no' => 'DEMO-TRF-003', 'from' => 'Main Warehouse', 'to' => 'Restaurant Store', 'total_qty' => 15];

        // DEMO-TRF-004: Main Warehouse -> Service Center (Repair Parts, 10 units)
        $p4 = $getStdProduct(18);
        $trf4 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-004',
            'from_warehouse_id' => $mainWhId,
            'to_warehouse_id' => $serviceWhId,
            'status' => 1,
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p4->cost * 10,
            'shipping_cost' => 0,
            'grand_total' => $p4->cost * 10,
            'product_id' => [$p4->id],
            'product_code' => [$p4->code],
            'qty' => [10],
            'purchase_unit' => [$getUnitName($p4)],
            'net_unit_cost' => [$p4->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p4->cost * 10],
        ], $user);
        $transfersCreated[] = ['id' => $trf4->id, 'reference_no' => 'DEMO-TRF-004', 'from' => 'Main Warehouse', 'to' => 'Service Center', 'total_qty' => 10];

        // DEMO-TRF-005: Restaurant Store -> Main Warehouse (Reverse ingredient transfer, 5 units)
        $p5 = $getStdProduct(7);
        $trf5 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-005',
            'from_warehouse_id' => $restaurantWhId,
            'to_warehouse_id' => $mainWhId,
            'status' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p5->cost * 5,
            'shipping_cost' => 0,
            'grand_total' => $p5->cost * 5,
            'product_id' => [$p5->id],
            'product_code' => [$p5->code],
            'qty' => [5],
            'purchase_unit' => [$getUnitName($p5)],
            'net_unit_cost' => [$p5->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p5->cost * 5],
        ], $user);
        $transfersCreated[] = ['id' => $trf5->id, 'reference_no' => 'DEMO-TRF-005', 'from' => 'Restaurant Store', 'to' => 'Main Warehouse', 'total_qty' => 5];

        // DEMO-TRF-006: Downtown Retail Store -> Main Warehouse (Return stock transfer, 5 units)
        $p6 = $getStdProduct(2);
        $trf6 = $transferService->createTransfer([
            'reference_no' => 'DEMO-TRF-006',
            'from_warehouse_id' => $retailWhId,
            'to_warehouse_id' => $mainWhId,
            'status' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => $p6->cost * 5,
            'shipping_cost' => 0,
            'grand_total' => $p6->cost * 5,
            'product_id' => [$p6->id],
            'product_code' => [$p6->code],
            'qty' => [5],
            'purchase_unit' => [$getUnitName($p6)],
            'net_unit_cost' => [$p6->cost],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p6->cost * 5],
        ], $user);
        $transfersCreated[] = ['id' => $trf6->id, 'reference_no' => 'DEMO-TRF-006', 'from' => 'Downtown Retail Store', 'to' => 'Main Warehouse', 'total_qty' => 5];

        return $transfersCreated;
    }

    /**
     * Create Phase 6 Stock Adjustment Scenarios.
     *
     * @param User $user
     * @return array
     */
    public function createStockAdjustmentScenarios(User $user): array
    {
        /** @var AdjustmentDomainService $adjService */
        $adjService = app(AdjustmentDomainService::class);

        $mainWh = Warehouse::where('name', 'LIKE', '%Main%')->first() ?? Warehouse::first();
        $retailWh = Warehouse::where('name', 'LIKE', '%Downtown%')->orWhere('name', 'LIKE', '%Retail%')->first() ?? $mainWh;

        $stdProds = Product::where(function ($q) {
            $q->whereNull('is_variant')->orWhere('is_variant', 0)->orWhere('is_variant', false);
        })->where('type', 'standard')->get();

        $getStdProduct = function ($index) use ($stdProds) {
            return $stdProds->count() > $index ? $stdProds[$index] : Product::first();
        };

        $p0 = $stdProds->first() ?? Product::first();
        $p1 = $stdProds->skip(1)->first() ?? $p0;


        $pv = ProductVariant::first();
        if ($pv) {
            $varProd = Product::find($pv->product_id);
            $varCode = $pv->item_code;
        } else {
            $varProd = Product::where('is_variant', true)->orWhereNotNull('is_variant')->first();
            $varCode = $varProd ? $varProd->code : 'PV001-M';
        }




        $adjustmentsCreated = [];

        // DEMO-ADJ-001: Damaged Goods Decrease (2 units of std product in Main Warehouse)
        if ($p0 && $mainWh) {
            $adj1 = $adjService->createAdjustment([
                'reference_no' => 'DEMO-ADJ-001',
                'warehouse_id' => $mainWh->id,
                'product_id' => [$p0->id],
                'product_code' => [$p0->code],
                'qty' => [2],
                'action' => ['-'],
                'unit_cost' => [$p0->cost],
                'note' => 'Damaged goods discovered during physical inspection DEMO-ADJ-001',
            ], $user);
            $adjustmentsCreated[] = ['id' => $adj1->id, 'reference_no' => 'DEMO-ADJ-001', 'type' => 'Stock Decrease (Damaged)', 'warehouse' => $mainWh->name, 'product' => $p0->name, 'qty' => 2, 'action' => '-'];
        }

        // DEMO-ADJ-002: Physical Count Correction Increase (3 units of std product in Main Warehouse)
        if ($p1 && $mainWh) {
            $adj2 = $adjService->createAdjustment([
                'reference_no' => 'DEMO-ADJ-002',
                'warehouse_id' => $mainWh->id,
                'product_id' => [$p1->id],
                'product_code' => [$p1->code],
                'qty' => [3],
                'action' => ['+'],
                'unit_cost' => [$p1->cost],
                'note' => 'Physical count correction increase DEMO-ADJ-002',
            ], $user);
            $adjustmentsCreated[] = ['id' => $adj2->id, 'reference_no' => 'DEMO-ADJ-002', 'type' => 'Stock Increase (Physical Count)', 'warehouse' => $mainWh->name, 'product' => $p1->name, 'qty' => 3, 'action' => '+'];
        }

        // DEMO-ADJ-003: Variant Product Decrease (1 unit of variant product tuple in Downtown Store Warehouse)
        if ($varProd && $retailWh) {
            $adj3 = $adjService->createAdjustment([
                'reference_no' => 'DEMO-ADJ-003',
                'warehouse_id' => $retailWh->id,
                'product_id' => [$varProd->id],
                'product_code' => [$varCode],
                'qty' => [1],
                'action' => ['-'],
                'unit_cost' => [$varProd->cost],
                'note' => 'Variant stock adjustment decrease DEMO-ADJ-003',
            ], $user);
            $adjustmentsCreated[] = ['id' => $adj3->id, 'reference_no' => 'DEMO-ADJ-003', 'type' => 'Variant Stock Decrease', 'warehouse' => $retailWh->name, 'product' => $varProd->name, 'qty' => 1, 'action' => '-'];
        }



        return $adjustmentsCreated;
    }


    /**
     * Build Phases 1 to 7 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To7(bool $force = false): array
    {
        $phase5Results = $this->buildPhases1To5($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();
        $mastersCreated = $manifest['masters_created'];

        $salesCreated = $this->createSaleScenarios($mastersCreated, $phase5Results, $adminUser);

        $validationResultPhase7 = $this->validator->validatePhase7(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $salesCreated
        );

        if (!$validationResultPhase7['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 7! Details: ".json_encode($validationResultPhase7));
        }





        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $mastersCreated,
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $salesCreated,
            'phase7_validation' => $validationResultPhase7,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $mastersCreated,
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $salesCreated,
            'validation' => $validationResultPhase7,
        ];
    }

    /**
     * Build Phases 1 to 8 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To8(bool $force = false): array
    {
        $phase7Results = $this->buildPhases1To7($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $paymentMutations = $this->createSalePaymentMutationScenarios($manifest['sales_created'], $adminUser);

        $validationResultPhase8 = $this->validator->validatePhase8(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $paymentMutations
        );

        if (!$validationResultPhase8['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 8! Details: ".json_encode($validationResultPhase8));
        }

        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $paymentMutations,
            'phase8_validation' => $validationResultPhase8,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $paymentMutations,
            'validation' => $validationResultPhase8,
        ];
    }

    /**
     * Build Phases 1 to 9 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To9(bool $force = false): array
    {
        $phase8Results = $this->buildPhases1To8($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $returnsCreated = $this->createSaleReturnScenarios($manifest['sales_created'], $adminUser);

        $validationResultPhase9 = $this->validator->validatePhase9(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $manifest['payment_mutations'] ?? [],
            $returnsCreated
        );

        if (!$validationResultPhase9['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 9! Details: ".json_encode($validationResultPhase9));
        }







        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8', 'Phase 9'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $returnsCreated,
            'phase9_validation' => $validationResultPhase9,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $returnsCreated,
            'validation' => $validationResultPhase9,
        ];
    }

    /**
     * Build Phases 1 to 10 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To10(bool $force = false): array
    {
        $phase9Results = $this->buildPhases1To9($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $exchangesCreated = $this->createProductExchangeScenarios($manifest['sales_created'], $adminUser);

        $validationResultPhase10 = $this->validator->validatePhase10(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $manifest['payment_mutations'] ?? [],
            $manifest['returns_created'] ?? [],
            $exchangesCreated
        );

        if (!$validationResultPhase10['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 10! Details: ".json_encode($validationResultPhase10));
        }



        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8', 'Phase 9', 'Phase 10'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $exchangesCreated,
            'phase10_validation' => $validationResultPhase10,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $exchangesCreated,
            'validation' => $validationResultPhase10,
        ];
    }

    /**
     * Build Phases 1 to 11 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To11(bool $force = false): array
    {
        $phase10Results = $this->buildPhases1To10($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $purchaseMutationsCreated = $this->createPurchasePaymentMutationScenarios($manifest['purchases_created'], $adminUser);

        $validationResultPhase11 = $this->validator->validatePhase11(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $manifest['payment_mutations'] ?? [],
            $manifest['returns_created'] ?? [],
            $manifest['exchanges_created'] ?? [],
            $purchaseMutationsCreated
        );

        if (!$validationResultPhase11['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 11! Details: ".json_encode($validationResultPhase11));
        }

        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8', 'Phase 9', 'Phase 10', 'Phase 11'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $purchaseMutationsCreated,
            'phase11_validation' => $validationResultPhase11,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $purchaseMutationsCreated,
            'validation' => $validationResultPhase11,
        ];
    }

    /**
     * Build Phases 1 to 12 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To12(bool $force = false): array
    {
        $phase11Results = $this->buildPhases1To11($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $purchaseReturnsCreated = $this->createPurchaseReturnScenarios($manifest['purchases_created'], $adminUser);

        $validationResultPhase12 = $this->validator->validatePhase12(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $manifest['payment_mutations'] ?? [],
            $manifest['returns_created'] ?? [],
            $manifest['exchanges_created'] ?? [],
            $manifest['purchase_mutations'] ?? [],
            $purchaseReturnsCreated
        );

        if (!$validationResultPhase12['passed']) {
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 12! Details: ".json_encode($validationResultPhase12));
        }









        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8', 'Phase 9', 'Phase 10', 'Phase 11', 'Phase 12'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $manifest['purchase_mutations'] ?? [],
            'purchase_returns_created' => $purchaseReturnsCreated,
            'phase12_validation' => $validationResultPhase12,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $manifest['purchase_mutations'] ?? [],
            'purchase_returns_created' => $purchaseReturnsCreated,
            'validation' => $validationResultPhase12,
        ];
    }

    /**
     * Build Phases 1 to 13 of the Golden Demo Scenario.
     *
     * @param bool $force
     * @return array
     */
    public function buildPhases1To13(bool $force = false): array
    {
        $phase12Results = $this->buildPhases1To12($force);

        $adminUser = Auth::user() ?? User::where('role_id', 1)->first() ?? User::first();

        $manifest = $this->manifestService->read();

        $restaurantSalesCreated = $this->createRestaurantScenarios($manifest['purchases_created'], $adminUser);

        $validationResultPhase13 = $this->validator->validatePhase13(
            $manifest['baseline'],
            $manifest['purchases_created'],
            $manifest['payments_created'],
            $manifest['transfers_created'],
            $manifest['sales_created'],
            $manifest['payment_mutations'] ?? [],
            $manifest['returns_created'] ?? [],
            $manifest['exchanges_created'] ?? [],
            $manifest['purchase_mutations'] ?? [],
            $manifest['purchase_returns_created'] ?? [],
            $restaurantSalesCreated
        );

        if (!$validationResultPhase13['passed']) {
            fwrite(STDERR, "PHASE 13 VALIDATION FAILURE DETAILS:\n" . print_r($validationResultPhase13, true) . "\n");
            throw new RuntimeException("HARD VALIDATION GATE FAILED after Phase 13! Details: ".json_encode($validationResultPhase13));
        }

        // Save Golden Manifest
        $this->manifestService->merge([
            'version' => '1.0',
            'database_identifier' => DB::connection()->getDatabaseName(),
            'created_at' => date('c'),
            'phases_completed' => ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4', 'Phase 5', 'Phase 6', 'Phase 7', 'Phase 8', 'Phase 9', 'Phase 10', 'Phase 11', 'Phase 12', 'Phase 13'],
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'cash_register' => $manifest['cash_register'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $manifest['purchase_mutations'] ?? [],
            'purchase_returns_created' => $manifest['purchase_returns_created'] ?? [],
            'restaurant_sales_created' => $restaurantSalesCreated,
            'phase13_validation' => $validationResultPhase13,
        ]);

        return [
            'baseline' => $manifest['baseline'],
            'masters_created' => $manifest['masters_created'],
            'purchases_created' => $manifest['purchases_created'],
            'payments_created' => $manifest['payments_created'],
            'transfers_created' => $manifest['transfers_created'],
            'adjustments_created' => $manifest['adjustments_created'],
            'sales_created' => $manifest['sales_created'],
            'payment_mutations' => $manifest['payment_mutations'] ?? [],
            'returns_created' => $manifest['returns_created'] ?? [],
            'exchanges_created' => $manifest['exchanges_created'] ?? [],
            'purchase_mutations' => $manifest['purchase_mutations'] ?? [],
            'purchase_returns_created' => $manifest['purchase_returns_created'] ?? [],
            'restaurant_sales_created' => $restaurantSalesCreated,
            'validation' => $validationResultPhase13,
        ];
    }






    /**
     * Create Phase 8 Sale Payment Lifecycle & Mutations (DEMO-SALE-PAY-001 through DEMO-SALE-PAY-004).
     *
     * @param array $salesCreated
     * @param User $user
     * @return array
     */
    public function createSalePaymentMutationScenarios(array $salesCreated, User $user): array
    {
        $existingSalePay = \App\Models\Payment::where('payment_reference', 'LIKE', 'DEMO-SALE-PAY-%')->get();
        if ($existingSalePay->count() >= 4) {
            $mutList = [];
            foreach ($existingSalePay as $p) {
                $mutList[] = [
                    'reference_no' => $p->payment_reference,
                    'sale_ref' => Sale::where('id', $p->sale_id)->value('reference_no'),
                    'action' => 'MUTATION',
                    'amount' => (float) $p->amount,
                    'payment_id' => $p->id
                ];
            }
            return $mutList;
        }

        /** @var SaleDomainService $saleService */
        $saleService = app(SaleDomainService::class);

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $cashAcc = Account::where('account_no', '1001')->orWhere('name', 'LIKE', '%Cash%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;
        $cashAccId = $cashAcc ? $cashAcc->id : 1;

        $findSaleId = function ($ref) {
            return Sale::where('reference_no', $ref)->value('id');
        };

        $mutationsCreated = [];

        // DEMO-SALE-PAY-001: Add later partial payment on DEMO-SALE-003 ($500 payment)
        $sale3Id = $findSaleId('DEMO-SALE-003');
        if ($sale3Id) {
            $p1 = $saleService->addSalePayment([
                'sale_id' => $sale3Id,
                'amount' => 500.00,
                'paying_amount' => 500.00,
                'paid_by_id' => 3, // Bank
                'account_id' => $bankAccId,
                'payment_reference' => 'DEMO-SALE-PAY-001',
                'payment_note' => 'Partial payment added on credit sale',
            ], $user);
            $mutationsCreated[] = ['reference_no' => 'DEMO-SALE-PAY-001', 'sale_ref' => 'DEMO-SALE-003', 'action' => 'ADD_PARTIAL', 'amount' => 500.00, 'payment_id' => $p1->id];
        }

        // DEMO-SALE-PAY-002: Add payment that fully settles invoice DEMO-SALE-004 ($1,000 payment)
        $sale4Id = $findSaleId('DEMO-SALE-004');
        if ($sale4Id) {
            $p2 = $saleService->addSalePayment([
                'sale_id' => $sale4Id,
                'amount' => 1000.00,
                'paying_amount' => 1000.00,
                'paid_by_id' => 3, // Bank
                'account_id' => $bankAccId,
                'payment_reference' => 'DEMO-SALE-PAY-002',
                'payment_note' => 'Final settlement payment',
            ], $user);
            $mutationsCreated[] = ['reference_no' => 'DEMO-SALE-PAY-002', 'sale_ref' => 'DEMO-SALE-004', 'action' => 'FULL_SETTLEMENT', 'amount' => 1000.00, 'payment_id' => $p2->id];
        }

        // DEMO-SALE-PAY-003: Create payment, then edit amount/account on DEMO-SALE-019 ($500 -> $1,000 edit)
        $sale19Id = $findSaleId('DEMO-SALE-019');
        if ($sale19Id) {
            $p3 = $saleService->addSalePayment([
                'sale_id' => $sale19Id,
                'amount' => 500.00,
                'paying_amount' => 500.00,
                'paid_by_id' => 1, // Cash
                'account_id' => $cashAccId,
                'payment_reference' => 'DEMO-SALE-PAY-003',
                'payment_note' => 'Initial payment before edit',
            ], $user);

            // Edit payment to $1,000 via Bank
            $p3Updated = $saleService->updateSalePayment([
                'payment_id' => $p3->id,
                'edit_amount' => 1000.00,
                'edit_paying_amount' => 1000.00,
                'edit_paid_by_id' => 3, // Bank
                'account_id' => $bankAccId,
                'edit_payment_note' => 'Edited payment amount and account',
            ], $user);

            $mutationsCreated[] = ['reference_no' => 'DEMO-SALE-PAY-003', 'sale_ref' => 'DEMO-SALE-019', 'action' => 'EDIT_PAYMENT', 'initial_amount' => 500.00, 'edited_amount' => 1000.00, 'payment_id' => $p3Updated->id];
        }

        // DEMO-SALE-PAY-004: Create payment, then delete/reverse it on DEMO-SALE-020 ($500 payment deleted)
        $sale20Id = $findSaleId('DEMO-SALE-020');
        if ($sale20Id) {
            $p4 = $saleService->addSalePayment([
                'sale_id' => $sale20Id,
                'amount' => 500.00,
                'paying_amount' => 500.00,
                'paid_by_id' => 3, // Bank
                'account_id' => $bankAccId,
                'payment_reference' => 'DEMO-SALE-PAY-004',
                'payment_note' => 'Temporary payment to be reversed',
            ], $user);

            // Delete/reverse payment using canonical workflow
            $saleService->deleteSalePayment($p4->id, $user);

            $mutationsCreated[] = ['reference_no' => 'DEMO-SALE-PAY-004', 'sale_ref' => 'DEMO-SALE-020', 'action' => 'DELETE_REVERSE', 'amount' => 500.00, 'payment_id' => $p4->id];
        }

        return $mutationsCreated;
    }

    /**
     * Create Phase 9 Sale Return Scenarios (DEMO-RET-001 through DEMO-RET-005).
     *
     * @param array $salesCreated
     * @param User $user
     * @return array
     */
    public function createSaleReturnScenarios(array $salesCreated, User $user): array
    {
        $existingReturns = \App\Models\Returns::where('reference_no', 'LIKE', 'DEMO-RET-%')->get();
        if ($existingReturns->count() >= 5) {
            $retList = [];
            foreach ($existingReturns as $er) {
                $retList[] = [
                    'id' => $er->id,
                    'reference_no' => $er->reference_no,
                    'sale_ref' => Sale::where('id', $er->sale_id)->value('reference_no'),
                    'grand_total' => (float) $er->grand_total,
                    'paid' => (float) $er->paid_amount
                ];
            }
            return $retList;
        }

        /** @var ReturnDomainService $returnService */
        $returnService = app(ReturnDomainService::class);

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $cashAcc = Account::where('account_no', '1001')->orWhere('name', 'LIKE', '%Cash%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;
        $cashAccId = $cashAcc ? $cashAcc->id : 1;

        $findSale = function ($ref) {
            return Sale::where('reference_no', $ref)->first();
        };

        $findSaleItem = function ($sale) {
            if (!$sale) return null;
            $ps = Product_Sale::where('sale_id', $sale->id)->first();
            if (!$ps) return null;
            $p = Product::find($ps->product_id);
            if (!$p) return null;

            $code = $p->code;
            if ($ps->variant_id && $p->is_variant) {
                $pv = ProductVariant::where('product_id', $p->id)->where('variant_id', $ps->variant_id)->first();
                if ($pv) $code = $pv->item_code;
            }

            $u = Unit::find($ps->sale_unit_id);
            $unitName = $u ? $u->unit_name : 'pc';

            return [
                'product_id' => $p->id,
                'product_code' => $code,
                'sale_unit' => $unitName,
                'net_unit_price' => (float) $ps->net_unit_price,
                'discount' => (float) $ps->discount,
                'tax_rate' => (float) $ps->tax_rate,
                'tax' => (float) $ps->tax,
            ];
        };

        $returnsCreated = [];

        // DEMO-RET-001: Full Return of a Fully-Paid Sale (DEMO-SALE-001, 1 unit cash sale, $100 refund)
        $sale1 = $findSale('DEMO-SALE-001');
        $item1 = $findSaleItem($sale1);
        if ($sale1 && $item1) {
            $ret1 = $returnService->createReturn([
                'reference_no' => 'DEMO-RET-001',
                'sale_id' => $sale1->id,
                'refund' => 1,
                'refund_amount' => $item1['net_unit_price'],
                'paying_method' => 'Cash',
                'account_id' => $cashAccId,
                'change_sale_status' => 1,
                'product_id' => [$item1['product_id']],
                'product_code' => [$item1['product_code']],
                'qty' => [1],
                'sale_unit' => [$item1['sale_unit']],
                'net_unit_price' => [$item1['net_unit_price']],
                'discount' => [$item1['discount']],
                'tax_rate' => [$item1['tax_rate']],
                'tax' => [$item1['tax']],
                'subtotal' => [$item1['net_unit_price']],
                'grand_total' => $item1['net_unit_price'],
                'total_price' => $item1['net_unit_price'],
                'return_note' => 'Full return of fully-paid single item sale DEMO-SALE-001',
            ], $user);
            $returnsCreated[] = ['id' => $ret1->id, 'reference_no' => 'DEMO-RET-001', 'sale_ref' => 'DEMO-SALE-001', 'type' => 'Full Return Paid Sale', 'grand_total' => $item1['net_unit_price']];
        }

        // DEMO-RET-002: Partial Return of Paid Sale (DEMO-SALE-006, 1 unit returned out of multi-line sale, $300 refund)
        $sale6 = $findSale('DEMO-SALE-006');
        $item6 = $findSaleItem($sale6);
        if ($sale6 && $item6) {
            $ret2 = $returnService->createReturn([
                'reference_no' => 'DEMO-RET-002',
                'sale_id' => $sale6->id,
                'refund' => 1,
                'refund_amount' => $item6['net_unit_price'],
                'paying_method' => 'Cash',
                'account_id' => $cashAccId,
                'product_id' => [$item6['product_id']],
                'product_code' => [$item6['product_code']],
                'qty' => [1],
                'sale_unit' => [$item6['sale_unit']],
                'net_unit_price' => [$item6['net_unit_price']],
                'discount' => [$item6['discount']],
                'tax_rate' => [$item6['tax_rate']],
                'tax' => [$item6['tax']],
                'subtotal' => [$item6['net_unit_price']],
                'grand_total' => $item6['net_unit_price'],
                'total_price' => $item6['net_unit_price'],
                'return_note' => 'Partial return of 1 unit from DEMO-SALE-006',
            ], $user);
            $returnsCreated[] = ['id' => $ret2->id, 'reference_no' => 'DEMO-RET-002', 'sale_ref' => 'DEMO-SALE-006', 'type' => 'Partial Return Paid Sale', 'grand_total' => $item6['net_unit_price']];
        }

        // DEMO-RET-003: Variant Return against Variant Sale (DEMO-SALE-005, 1 unit returned, no cash refund)
        $sale5 = $findSale('DEMO-SALE-005');
        $item5 = $findSaleItem($sale5);
        if ($sale5 && $item5) {
            $ret3 = $returnService->createReturn([
                'reference_no' => 'DEMO-RET-003',
                'sale_id' => $sale5->id,
                'refund' => 0,
                'refund_amount' => 0.00,
                'account_id' => null,
                'product_id' => [$item5['product_id']],
                'product_code' => [$item5['product_code']],
                'qty' => [1],
                'sale_unit' => [$item5['sale_unit']],
                'net_unit_price' => [$item5['net_unit_price']],
                'discount' => [$item5['discount']],
                'tax_rate' => [$item5['tax_rate']],
                'tax' => [$item5['tax']],
                'subtotal' => [$item5['net_unit_price']],
                'grand_total' => $item5['net_unit_price'],
                'total_price' => $item5['net_unit_price'],
                'return_note' => 'Variant product return of 1 unit from DEMO-SALE-005',
            ], $user);
            $returnsCreated[] = ['id' => $ret3->id, 'reference_no' => 'DEMO-RET-003', 'sale_ref' => 'DEMO-SALE-005', 'type' => 'Variant Return Credit Sale', 'grand_total' => $item5['net_unit_price']];
        }

        // DEMO-RET-004: Return against Credit Sale (DEMO-SALE-003, 1 unit returned, $300 return, no cash refund)
        $sale3 = $findSale('DEMO-SALE-003');
        $item3 = $findSaleItem($sale3);
        if ($sale3 && $item3) {
            $ret4 = $returnService->createReturn([
                'reference_no' => 'DEMO-RET-004',
                'sale_id' => $sale3->id,
                'refund' => 0,
                'refund_amount' => 0.00,
                'account_id' => null,
                'product_id' => [$item3['product_id']],
                'product_code' => [$item3['product_code']],
                'qty' => [1],
                'sale_unit' => [$item3['sale_unit']],
                'net_unit_price' => [$item3['net_unit_price']],
                'discount' => [$item3['discount']],
                'tax_rate' => [$item3['tax_rate']],
                'tax' => [$item3['tax']],
                'subtotal' => [$item3['net_unit_price']],
                'grand_total' => $item3['net_unit_price'],
                'total_price' => $item3['net_unit_price'],
                'return_note' => 'Return against credit sale DEMO-SALE-003',
            ], $user);
            $returnsCreated[] = ['id' => $ret4->id, 'reference_no' => 'DEMO-RET-004', 'sale_ref' => 'DEMO-SALE-003', 'type' => 'Return Credit Sale', 'grand_total' => $item3['net_unit_price']];
        }

        // DEMO-RET-005: Return against Partially-Paid Sale (DEMO-SALE-004, 1 unit returned, $500 return, no cash refund)
        $sale4 = $findSale('DEMO-SALE-004');
        $item4 = $findSaleItem($sale4);
        if ($sale4 && $item4) {
            $ret5 = $returnService->createReturn([
                'reference_no' => 'DEMO-RET-005',
                'sale_id' => $sale4->id,
                'refund' => 0,
                'refund_amount' => 0.00,
                'account_id' => null,
                'product_id' => [$item4['product_id']],
                'product_code' => [$item4['product_code']],
                'qty' => [1],
                'sale_unit' => [$item4['sale_unit']],
                'net_unit_price' => [$item4['net_unit_price']],
                'discount' => [$item4['discount']],
                'tax_rate' => [$item4['tax_rate']],
                'tax' => [$item4['tax']],
                'subtotal' => [$item4['net_unit_price']],
                'grand_total' => $item4['net_unit_price'],
                'total_price' => $item4['net_unit_price'],
                'return_note' => 'Return against partially-paid sale DEMO-SALE-004',
            ], $user);
            $returnsCreated[] = ['id' => $ret5->id, 'reference_no' => 'DEMO-RET-005', 'sale_ref' => 'DEMO-SALE-004', 'type' => 'Return Partial Sale', 'grand_total' => $item4['net_unit_price']];
        }


        return $returnsCreated;
    }

    /**
     * Create Phase 10 Product Exchange Scenarios (DEMO-EXCH-001 through DEMO-EXCH-003).
     *
     * @param array $salesCreated
     * @param User $user
     * @return array
     */
    public function createProductExchangeScenarios(array $salesCreated, User $user): array
    {
        $existingExch = DB::table('sale_exchanges')->where('reference_no', 'LIKE', 'DEMO-EXCH-%')->get();
        if ($existingExch->count() >= 3) {
            $exchList = [];
            foreach ($existingExch as $ee) {
                $exchList[] = [
                    'id' => $ee->id,
                    'reference_no' => $ee->reference_no,
                    'sale_ref' => Sale::where('id', $ee->sale_id)->value('reference_no'),
                    'exchange_amount' => (float) $ee->amount
                ];
            }
            return $exchList;
        }

        /** @var ExchangeDomainService $exchangeService */
        $exchangeService = app(ExchangeDomainService::class);

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $cashAcc = Account::where('account_no', '1001')->orWhere('name', 'LIKE', '%Cash%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;
        $cashAccId = $cashAcc ? $cashAcc->id : 1;

        $findSale = function ($ref) {
            return Sale::where('reference_no', $ref)->first();
        };

        $findSaleItem = function ($sale, $index = 0) {
            if (!$sale) return null;
            $productSales = Product_Sale::where('sale_id', $sale->id)->get();
            if ($productSales->isEmpty()) return null;
            $ps = $productSales->values()->get($index) ?? $productSales->first();
            $p = Product::find($ps->product_id);
            if (!$p) return null;

            $code = $p->code;
            if ($ps->variant_id && $p->is_variant) {
                $pv = ProductVariant::where('product_id', $p->id)->where('variant_id', $ps->variant_id)->first();
                if ($pv) $code = $pv->item_code;
            }

            $u = Unit::find($ps->sale_unit_id);
            $unitName = $u ? $u->unit_name : 'pc';

            return [
                'product_sale_id' => $ps->id,
                'product_id' => $p->id,
                'product_code' => $code,
                'sale_unit' => $unitName,
                'net_unit_price' => (float) $ps->net_unit_price,
                'discount' => (float) $ps->discount,
                'tax_rate' => (float) $ps->tax_rate,
                'tax' => (float) $ps->tax,
            ];
        };

        $exchangesCreated = [];

        // DEMO-EXCH-001: Same-Value Variant Exchange against DEMO-SALE-005
        // Return 1 unit of variant product, Issue 1 unit of alternative variant of equal value ($150)
        $sale5 = $findSale('DEMO-SALE-005');
        $item5 = $findSaleItem($sale5);
        if ($sale5 && $item5) {
            $altVariant = ProductVariant::where('product_id', $item5['product_id'])
                ->where('item_code', '!=', $item5['product_code'])
                ->first();

            $replacementCode = $altVariant ? $altVariant->item_code : $item5['product_code'];
            $replacementPrice = $item5['net_unit_price'];

            $exc1 = $exchangeService->createExchange([
                'reference_no' => 'DEMO-EXCH-001',
                'sale_id' => $sale5->id,
                'customer_id' => $sale5->customer_id,
                'warehouse_id' => $sale5->warehouse_id,
                'biller_id' => $sale5->biller_id,
                'account_id' => $cashAccId,
                'paying_method' => 'Cash',
                'payment_type' => null,
                'amount' => 0.00,
                'type' => ['return', 'new'],
                'product_id' => [$item5['product_id'], $item5['product_id']],
                'product_code' => [$item5['product_code'], $replacementCode],
                'qty' => [1, 1],
                'sale_unit' => [$item5['sale_unit'], $item5['sale_unit']],
                'net_unit_price' => [$item5['net_unit_price'], $replacementPrice],
                'discount' => [0, 0],
                'tax_rate' => [0, 0],
                'tax' => [0, 0],
                'subtotal' => [$item5['net_unit_price'], $replacementPrice],
                'product_sale_id' => [$item5['product_sale_id'], null],
                'is_exchange' => [$item5['product_code']],
                'exchange_note' => 'Same-value variant exchange DEMO-EXCH-001',
            ], $user);

            $exchangesCreated[] = [
                'id' => $exc1->id,
                'reference_no' => 'DEMO-EXCH-001',
                'type' => 'Same-Value Variant Exchange',
                'sale_ref' => 'DEMO-SALE-005',
                'returned' => $item5['product_code'] . ' (qty 1)',
                'replacement' => $replacementCode . ' (qty 1)',
                'price_diff' => 0.00,
                'additional_payment' => 0.00,
            ];
        }

        // DEMO-EXCH-002: Higher-Value Replacement against DEMO-SALE-008
        // Return 1 unit of product, Issue 1 unit of higher-priced product. Additional cash payment $50.
        $sale8 = $findSale('DEMO-SALE-008');
        $item8 = $findSaleItem($sale8);
        if ($sale8 && $item8) {
            $higherProd = Product::where('is_variant', false)
                ->where('type', 'standard')
                ->where('id', '!=', $item8['product_id'])
                ->first() ?? Product::find($item8['product_id']);

            $returnedPrice = $item8['net_unit_price'];
            $replacementPrice = $returnedPrice + 50.00;

            $exc2 = $exchangeService->createExchange([
                'reference_no' => 'DEMO-EXCH-002',
                'sale_id' => $sale8->id,
                'customer_id' => $sale8->customer_id,
                'warehouse_id' => $sale8->warehouse_id,
                'biller_id' => $sale8->biller_id,
                'account_id' => $cashAccId,
                'paying_method' => 'Cash',
                'payment_type' => 'receive',
                'amount' => 50.00,
                'type' => ['return', 'new'],
                'product_id' => [$item8['product_id'], $higherProd->id],
                'product_code' => [$item8['product_code'], $higherProd->code],
                'qty' => [1, 1],
                'sale_unit' => [$item8['sale_unit'], 'pc'],
                'net_unit_price' => [$returnedPrice, $replacementPrice],
                'discount' => [0, 0],
                'tax_rate' => [0, 0],
                'tax' => [0, 0],
                'subtotal' => [$returnedPrice, $replacementPrice],
                'product_sale_id' => [$item8['product_sale_id'], null],
                'is_exchange' => [$item8['product_code']],
                'exchange_note' => 'Higher-value replacement DEMO-EXCH-002',
            ], $user);

            $exchangesCreated[] = [
                'id' => $exc2->id,
                'reference_no' => 'DEMO-EXCH-002',
                'type' => 'Higher-Value Replacement',
                'sale_ref' => 'DEMO-SALE-008',
                'returned' => $item8['product_code'] . ' ($' . $returnedPrice . ')',
                'replacement' => $higherProd->code . ' ($' . $replacementPrice . ')',
                'price_diff' => 50.00,
                'additional_payment' => 50.00,
            ];
        }

        // DEMO-EXCH-003: Lower-Value Replacement against DEMO-SALE-010
        // Return 1 unit of product, Issue 1 unit of lower-priced product. Cash refund $50.
        $sale10 = $findSale('DEMO-SALE-010');
        $item10 = $findSaleItem($sale10);
        if ($sale10 && $item10) {
            $lowerProd = Product::where('is_variant', false)
                ->where('type', 'standard')
                ->where('id', '!=', $item10['product_id'])
                ->first() ?? Product::find($item10['product_id']);

            $returnedPrice = $item10['net_unit_price'];
            $replacementPrice = max(10.00, $returnedPrice - 50.00);
            $refundAmount = $returnedPrice - $replacementPrice;

            $exc3 = $exchangeService->createExchange([
                'reference_no' => 'DEMO-EXCH-003',
                'sale_id' => $sale10->id,
                'customer_id' => $sale10->customer_id,
                'warehouse_id' => $sale10->warehouse_id,
                'biller_id' => $sale10->biller_id,
                'account_id' => $cashAccId,
                'paying_method' => 'Cash',
                'payment_type' => 'pay',
                'amount' => $refundAmount,
                'type' => ['return', 'new'],
                'product_id' => [$item10['product_id'], $lowerProd->id],
                'product_code' => [$item10['product_code'], $lowerProd->code],
                'qty' => [1, 1],
                'sale_unit' => [$item10['sale_unit'], 'pc'],
                'net_unit_price' => [$returnedPrice, $replacementPrice],
                'discount' => [0, 0],
                'tax_rate' => [0, 0],
                'tax' => [0, 0],
                'subtotal' => [$returnedPrice, $replacementPrice],
                'product_sale_id' => [$item10['product_sale_id'], null],
                'is_exchange' => [$item10['product_code']],
                'exchange_note' => 'Lower-value replacement DEMO-EXCH-003',
            ], $user);

            $exchangesCreated[] = [
                'id' => $exc3->id,
                'reference_no' => 'DEMO-EXCH-003',
                'type' => 'Lower-Value Replacement',
                'sale_ref' => 'DEMO-SALE-010',
                'returned' => $item10['product_code'] . ' ($' . $returnedPrice . ')',
                'replacement' => $lowerProd->code . ' ($' . $replacementPrice . ')',
                'price_diff' => -$refundAmount,
                'refund_amount' => $refundAmount,
            ];
        }

        return $exchangesCreated;
    }

    /**
     * Create Phase 11 Purchase Payment Mutation Scenarios (DEMO-PUR-PAY-001 through DEMO-PUR-PAY-004).
     *
     * @param array $purchasesCreated
     * @param User $user
     * @return array
     */
    public function createPurchasePaymentMutationScenarios(array $purchasesCreated, User $user): array
    {
        $existingPurPay = \App\Models\Payment::where('payment_reference', 'LIKE', 'DEMO-PUR-PAY-%')->get();
        if ($existingPurPay->count() >= 3) {
            $mutList = [];
            foreach ($existingPurPay as $p) {
                $mutList[] = [
                    'reference_no' => $p->payment_reference,
                    'purchase_ref' => Purchase::where('id', $p->purchase_id)->value('reference_no'),
                    'action' => 'MUTATION',
                    'amount' => (float) $p->amount,
                    'payment_id' => $p->id
                ];
            }
            return $mutList;
        }

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $cashAcc = Account::where('account_no', '1001')->orWhere('name', 'LIKE', '%Cash%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;
        $cashAccId = $cashAcc ? $cashAcc->id : 1;

        $findPurchaseId = function ($ref) {
            return Purchase::where('reference_no', $ref)->value('id');
        };

        $mutationsCreated = [];

        // DEMO-PUR-PAY-001: Additional partial payment on DEMO-PUR-002 ($750 payment from Bank)
        $pur2Id = $findPurchaseId('DEMO-PUR-002');
        if ($pur2Id) {
            $p1Result = $paymentService->payForPurchase([
                'purchase_id' => $pur2Id,
                'amount' => 750.00,
                'paying_amount' => 750.00,
                'paid_by_id' => 3,
                'account_id' => $bankAccId,
                'payment_note' => 'Additional partial supplier payment DEMO-PUR-PAY-001',
                'payment_at' => date('Y-m-d H:i:s'),
            ]);
            $p1 = $p1Result['data'] ?? null;
            if ($p1 && isset($p1->id)) {
                $p1->payment_reference = 'DEMO-PUR-PAY-001';
                $p1->save();
            }
            $mutationsCreated[] = ['reference_no' => 'DEMO-PUR-PAY-001', 'purchase_ref' => 'DEMO-PUR-002', 'action' => 'ADD_PARTIAL', 'amount' => 750.00, 'payment_id' => $p1 ? $p1->id : null];
        }

        // DEMO-PUR-PAY-002: Payment completing outstanding purchase DEMO-PUR-003 ($3,000 payment from Bank)
        $pur3Id = $findPurchaseId('DEMO-PUR-003');
        if ($pur3Id) {
            $p2Result = $paymentService->payForPurchase([
                'purchase_id' => $pur3Id,
                'amount' => 3000.00,
                'paying_amount' => 3000.00,
                'paid_by_id' => 3,
                'account_id' => $bankAccId,
                'payment_note' => 'Full settlement supplier payment DEMO-PUR-PAY-002',
                'payment_at' => date('Y-m-d H:i:s'),
            ]);
            $p2 = $p2Result['data'] ?? null;
            if ($p2 && isset($p2->id)) {
                $p2->payment_reference = 'DEMO-PUR-PAY-002';
                $p2->save();
            }
            $mutationsCreated[] = ['reference_no' => 'DEMO-PUR-PAY-002', 'purchase_ref' => 'DEMO-PUR-003', 'action' => 'FULL_SETTLEMENT', 'amount' => 3000.00, 'payment_id' => $p2 ? $p2->id : null];
        }

        // DEMO-PUR-PAY-003: Create then edit Purchase payment on DEMO-PUR-006 ($500 -> $1,000 edit from Bank)
        $pur6Id = $findPurchaseId('DEMO-PUR-006');
        if ($pur6Id) {
            $p3Result = $paymentService->payForPurchase([
                'purchase_id' => $pur6Id,
                'amount' => 500.00,
                'paying_amount' => 500.00,
                'paid_by_id' => 3,
                'account_id' => $bankAccId,
                'payment_note' => 'Temporary payment before edit DEMO-PUR-PAY-003',
                'payment_at' => date('Y-m-d H:i:s'),
            ]);
            $p3 = $p3Result['data'] ?? null;
            if ($p3 && isset($p3->id)) {
                $p3->payment_reference = 'DEMO-PUR-PAY-003';
                $p3->save();

                $paymentService->updatePurchasePayment($p3->id, [
                    'amount' => 1000.00,
                    'account_id' => $bankAccId,
                    'payment_note' => 'Edited payment DEMO-PUR-PAY-003',
                    'paying_method' => 'Credit Card',
                ]);
            }
            $mutationsCreated[] = ['reference_no' => 'DEMO-PUR-PAY-003', 'purchase_ref' => 'DEMO-PUR-006', 'action' => 'EDIT_AMOUNT', 'amount' => 1000.00, 'payment_id' => $p3 ? $p3->id : null];
        }

        // DEMO-PUR-PAY-004: Create then delete/reverse Purchase payment on DEMO-PUR-007 ($500 payment from Bank)
        $pur7Id = $findPurchaseId('DEMO-PUR-007');
        if ($pur7Id) {
            $p4Result = $paymentService->payForPurchase([
                'purchase_id' => $pur7Id,
                'amount' => 500.00,
                'paying_amount' => 500.00,
                'paid_by_id' => 3,
                'account_id' => $bankAccId,
                'payment_note' => 'Temporary payment to be reversed DEMO-PUR-PAY-004',
                'payment_at' => date('Y-m-d H:i:s'),
            ]);
            $p4 = $p4Result['data'] ?? null;
            if ($p4 && isset($p4->id)) {
                $p4->payment_reference = 'DEMO-PUR-PAY-004';
                $p4->save();

                $paymentService->deletePurchasePayment($p4->id);
            }
            $mutationsCreated[] = ['reference_no' => 'DEMO-PUR-PAY-004', 'purchase_ref' => 'DEMO-PUR-007', 'action' => 'DELETE_REVERSE', 'amount' => 500.00, 'payment_id' => $p4 ? $p4->id : null];
        }

        return $mutationsCreated;
    }

    /**
     * Create Phase 12 Purchase Return Scenarios (DEMO-PRET-001 through DEMO-PRET-005).
     *
     * @param array $purchasesCreated
     * @param User $user
     * @return array
     */
    public function createPurchaseReturnScenarios(array $purchasesCreated, User $user): array
    {
        $existingPurRet = \App\Models\ReturnPurchase::where('reference_no', 'LIKE', 'DEMO-PRET-%')->get();
        if ($existingPurRet->count() >= 5) {
            $valid = true;
            foreach ($existingPurRet as $er) {
                if (!\App\Models\Purchase::where('id', $er->purchase_id)->exists()) {
                    $valid = false;
                    break;
                }
            }
            if ($valid) {
                $retList = [];
                foreach ($existingPurRet as $er) {
                    $retList[] = [
                        'id' => $er->id,
                        'reference_no' => $er->reference_no,
                        'purchase_id' => $er->purchase_id,
                        'grand_total' => (float) $er->grand_total
                    ];
                }
                return $retList;
            } else {
                $badIds = $existingPurRet->pluck('id')->toArray();
                DB::table('purchase_product_return')->whereIn('return_id', $badIds)->delete();
                DB::table('payments')->whereIn('purchase_return_id', $badIds)->delete();
                DB::table('return_purchases')->whereIn('id', $badIds)->delete();
            }
        }

        /** @var ReturnPurchaseDomainService $returnService */
        $returnService = app(ReturnPurchaseDomainService::class);

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;

        $findPurchase = function ($ref) {
            return Purchase::where('reference_no', $ref)->first();
        };

        $returnsCreated = [];

        // DEMO-PRET-001: Return against unpaid credit purchase DEMO-PUR-004
        $pur4 = $findPurchase('DEMO-PUR-004');
        if ($pur4) {
            $item4 = DB::table('product_purchases')->where('purchase_id', $pur4->id)->first();
            if ($item4) {
                $prod4 = Product::find($item4->product_id);
                $ret1 = $returnService->createPurchaseReturn([
                    'reference_no' => 'DEMO-PRET-001',
                    'purchase_id' => $pur4->id,
                    'refund_amount' => 0.00,
                    'account_id' => null,
                    'item' => 1,
                    'total_qty' => 1,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => (float) $item4->net_unit_cost,
                    'order_tax_rate' => 0,
                    'order_tax' => 0,
                    'grand_total' => (float) $item4->net_unit_cost,
                    'total_cost' => (float) $item4->net_unit_cost,
                    'is_return' => [$item4->id],
                    'product_purchase_id' => [$item4->id],
                    'product_id' => [$prod4->id],
                    'product_code' => [$prod4->code],
                    'product_batch_id' => [null],
                    'imei_number' => [null],
                    'qty' => [1],
                    'purchase_unit' => ['pc'],
                    'net_unit_cost' => [(float) $item4->net_unit_cost],
                    'discount' => [0],
                    'tax_rate' => [0],
                    'tax' => [0],
                    'subtotal' => [(float) $item4->net_unit_cost],
                    'return_note' => 'Credit purchase return DEMO-PRET-001',
                ], $user);

                $returnsCreated[] = [
                    'id' => $ret1->id,
                    'reference_no' => 'DEMO-PRET-001',
                    'purchase_ref' => 'DEMO-PUR-004',
                    'type' => 'Credit Return',
                    'qty' => 1,
                    'grand_total' => (float) $item4->net_unit_cost,
                ];
            }
        }

        // DEMO-PRET-002: Return against partially-paid purchase DEMO-PUR-002
        $pur2 = $findPurchase('DEMO-PUR-002');
        if ($pur2) {
            $item2 = DB::table('product_purchases')->where('purchase_id', $pur2->id)->first();
            if ($item2) {
                $prod2 = Product::find($item2->product_id);
                $ret2 = $returnService->createPurchaseReturn([
                    'reference_no' => 'DEMO-PRET-002',
                    'purchase_id' => $pur2->id,
                    'refund_amount' => 0.00,
                    'account_id' => null,
                    'item' => 1,
                    'total_qty' => 1,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => (float) $item2->net_unit_cost,
                    'order_tax_rate' => 0,
                    'order_tax' => 0,
                    'grand_total' => (float) $item2->net_unit_cost,
                    'total_cost' => (float) $item2->net_unit_cost,
                    'is_return' => [$item2->id],
                    'product_purchase_id' => [$item2->id],
                    'product_id' => [$prod2->id],
                    'product_code' => [$prod2->code],
                    'product_batch_id' => [null],
                    'imei_number' => [null],
                    'qty' => [1],
                    'purchase_unit' => ['pc'],
                    'net_unit_cost' => [(float) $item2->net_unit_cost],
                    'discount' => [0],
                    'tax_rate' => [0],
                    'tax' => [0],
                    'subtotal' => [(float) $item2->net_unit_cost],
                    'return_note' => 'Partially paid purchase return DEMO-PRET-002',
                ], $user);

                $returnsCreated[] = [
                    'id' => $ret2->id,
                    'reference_no' => 'DEMO-PRET-002',
                    'purchase_ref' => 'DEMO-PUR-002',
                    'type' => 'Partially Paid Return',
                    'qty' => 1,
                    'grand_total' => (float) $item2->net_unit_cost,
                ];
            }
        }

        // DEMO-PRET-003: Return against fully-paid purchase DEMO-PUR-001 (with bank refund)
        $pur1 = $findPurchase('DEMO-PUR-001');
        if ($pur1) {
            $item1 = DB::table('product_purchases')->where('purchase_id', $pur1->id)->first();
            if ($item1) {
                $prod1 = Product::find($item1->product_id);
                $retAmount = (float) $item1->net_unit_cost;
                $ret3 = $returnService->createPurchaseReturn([
                    'reference_no' => 'DEMO-PRET-003',
                    'purchase_id' => $pur1->id,
                    'refund_amount' => $retAmount,
                    'account_id' => $bankAccId,
                    'paying_method' => 'Credit Card',
                    'item' => 1,
                    'total_qty' => 1,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => $retAmount,
                    'order_tax_rate' => 0,
                    'order_tax' => 0,
                    'grand_total' => $retAmount,
                    'total_cost' => $retAmount,
                    'is_return' => [$item1->id],
                    'product_purchase_id' => [$item1->id],
                    'product_id' => [$prod1->id],
                    'product_code' => [$prod1->code],
                    'product_batch_id' => [null],
                    'imei_number' => [null],
                    'qty' => [1],
                    'purchase_unit' => ['pc'],
                    'net_unit_cost' => [$retAmount],
                    'discount' => [0],
                    'tax_rate' => [0],
                    'tax' => [0],
                    'subtotal' => [$retAmount],
                    'return_note' => 'Fully paid purchase return with bank refund DEMO-PRET-003',
                ], $user);

                $returnsCreated[] = [
                    'id' => $ret3->id,
                    'reference_no' => 'DEMO-PRET-003',
                    'purchase_ref' => 'DEMO-PUR-001',
                    'type' => 'Fully Paid Refund Return',
                    'qty' => 1,
                    'grand_total' => $retAmount,
                    'refund_amount' => $retAmount,
                ];
            }
        }

        // DEMO-PRET-004: Variant purchase return on DEMO-PUR-005
        $pur5 = $findPurchase('DEMO-PUR-005');
        if ($pur5) {
            $item5 = DB::table('product_purchases')->where('purchase_id', $pur5->id)->first();
            if ($item5) {
                $prod5 = Product::find($item5->product_id);
                $pv5 = ProductVariant::where('product_id', $prod5->id)->first();
                $itemCode = $pv5 ? $pv5->item_code : $prod5->code;

                $ret4 = $returnService->createPurchaseReturn([
                    'reference_no' => 'DEMO-PRET-004',
                    'purchase_id' => $pur5->id,
                    'refund_amount' => 0.00,
                    'account_id' => null,
                    'item' => 1,
                    'total_qty' => 1,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => (float) $item5->net_unit_cost,
                    'order_tax_rate' => 0,
                    'order_tax' => 0,
                    'grand_total' => (float) $item5->net_unit_cost,
                    'total_cost' => (float) $item5->net_unit_cost,
                    'is_return' => [$item5->id],
                    'product_purchase_id' => [$item5->id],
                    'product_id' => [$prod5->id],
                    'product_code' => [$itemCode],
                    'product_batch_id' => [null],
                    'imei_number' => [null],
                    'qty' => [1],
                    'purchase_unit' => ['pc'],
                    'net_unit_cost' => [(float) $item5->net_unit_cost],
                    'discount' => [0],
                    'tax_rate' => [0],
                    'tax' => [0],
                    'subtotal' => [(float) $item5->net_unit_cost],
                    'return_note' => 'Variant purchase return DEMO-PRET-004',
                ], $user);

                $returnsCreated[] = [
                    'id' => $ret4->id,
                    'reference_no' => 'DEMO-PRET-004',
                    'purchase_ref' => 'DEMO-PUR-005',
                    'type' => 'Variant Return',
                    'qty' => 1,
                    'grand_total' => (float) $item5->net_unit_cost,
                ];
            }
        }

        // DEMO-PRET-005: Restaurant ingredient purchase return on DEMO-PUR-010
        $pur10 = $findPurchase('DEMO-PUR-010');
        if ($pur10) {
            $item10 = DB::table('product_purchases')->where('purchase_id', $pur10->id)->first();
            if ($item10) {
                $prod10 = Product::find($item10->product_id);
                $ret5 = $returnService->createPurchaseReturn([
                    'reference_no' => 'DEMO-PRET-005',
                    'purchase_id' => $pur10->id,
                    'refund_amount' => 0.00,
                    'account_id' => null,
                    'item' => 1,
                    'total_qty' => 1,
                    'total_discount' => 0,
                    'total_tax' => 0,
                    'total_price' => (float) $item10->net_unit_cost,
                    'order_tax_rate' => 0,
                    'order_tax' => 0,
                    'grand_total' => (float) $item10->net_unit_cost,
                    'total_cost' => (float) $item10->net_unit_cost,
                    'is_return' => [$item10->id],
                    'product_purchase_id' => [$item10->id],
                    'product_id' => [$prod10->id],
                    'product_code' => [$prod10->code],
                    'product_batch_id' => [null],
                    'imei_number' => [null],
                    'qty' => [1],
                    'purchase_unit' => ['pc'],
                    'net_unit_cost' => [(float) $item10->net_unit_cost],
                    'discount' => [0],
                    'tax_rate' => [0],
                    'tax' => [0],
                    'subtotal' => [(float) $item10->net_unit_cost],
                    'return_note' => 'Restaurant ingredient purchase return DEMO-PRET-005',
                ], $user);

                $returnsCreated[] = [
                    'id' => $ret5->id,
                    'reference_no' => 'DEMO-PRET-005',
                    'purchase_ref' => 'DEMO-PUR-010',
                    'type' => 'Ingredient Return',
                    'qty' => 1,
                    'grand_total' => (float) $item10->net_unit_cost,
                ];
            }
        }

        return $returnsCreated;
    }






    /**
     * Create Phase 7 Sale Scenarios (DEMO-SALE-001 through DEMO-SALE-022).
     *
     * @param array $mastersCreated
     * @param array $phase5Results
     * @param User $user
     * @return array
     */
    public function createSaleScenarios(array $mastersCreated, array $phase5Results, User $user): array
    {
        /** @var SaleDomainService $saleService */
        $saleService = app(SaleDomainService::class);

        $mainWhId = $mastersCreated['warehouses'][0]['id'];
        $retailWhId = $mastersCreated['warehouses'][1]['id'];
        $restaurantWhId = $mastersCreated['warehouses'][2]['id'];
        $serviceWhId = $mastersCreated['warehouses'][3]['id'];

        $custApex = $mastersCreated['customers'][0]['id'];
        $custNexus = $mastersCreated['customers'][1]['id'];
        $custHorizon = $mastersCreated['customers'][2]['id'];
        $custStarlight = $mastersCreated['customers'][3]['id'];
        $custMetro = $mastersCreated['customers'][4]['id'];
        $custCredit = collect($mastersCreated['customers'])
            ->firstWhere('name', 'Charlie Credit Corp')['id'] ?? $custStarlight;

        $getStdProduct = function ($index) {
            $prods = Product::where('is_variant', false)->whereNotIn('type', ['service', 'digital'])->get();
            return $prods->count() > $index ? $prods[$index] : Product::first();
        };

        $getUnitName = function ($prod) {
            $unit = Unit::find($prod->unit_id);
            return $unit ? $unit->unit_name : 'pc';
        };

        $salesCreated = [];

        // 1. Cash Sale (standard products) - DEMO-SALE-001
        $p1 = $getStdProduct(0);
        $s1 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-001',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $p1->price * 2,
            'grand_total' => $p1->price * 2,
            'paid_amount' => [$p1->price * 2],
            'paying_amount' => [$p1->price * 2],
            'paid_by_id' => [1], // Cash
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p1->id],
            'product_code' => [$p1->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p1)],
            'net_unit_price' => [$p1->price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p1->price * 2],
        ], $user);
        $salesCreated[] = ['id' => $s1->id, 'reference_no' => 'DEMO-SALE-001', 'type' => 'Cash Sale', 'grand_total' => $p1->price * 2, 'paid' => $p1->price * 2];

        // 2. Bank/Card Sale - DEMO-SALE-002
        $p2 = $getStdProduct(1);
        $s2 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-002',
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $p2->price,
            'grand_total' => $p2->price,
            'paid_amount' => [$p2->price],
            'paying_amount' => [$p2->price],
            'paid_by_id' => [3], // Credit Card / Bank
            'account_id' => 2, // Bank Account
            'sale_status' => 1,
            'product_id' => [$p2->id],
            'product_code' => [$p2->code],
            'qty' => [1],
            'sale_unit' => [$getUnitName($p2)],
            'net_unit_price' => [$p2->price],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$p2->price],
        ], $user);
        $salesCreated[] = ['id' => $s2->id, 'reference_no' => 'DEMO-SALE-002', 'type' => 'Bank/Card Sale', 'grand_total' => $p2->price, 'paid' => $p2->price];

        // 3. Full Credit Sale (named credit customer) - DEMO-SALE-003
        $p3 = $getStdProduct(2);
        $s3 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-003',
            'customer_id' => $custCredit,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1500,
            'grand_total' => 1500,
            'paid_amount' => [0],
            'paying_amount' => [0],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p3->id],
            'product_code' => [$p3->code],
            'qty' => [5],
            'sale_unit' => [$getUnitName($p3)],
            'net_unit_price' => [300],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [1500],
        ], $user);
        $salesCreated[] = ['id' => $s3->id, 'reference_no' => 'DEMO-SALE-003', 'type' => 'Full Credit Sale', 'grand_total' => 1500, 'paid' => 0];

        // 4. Partial Payment Sale - DEMO-SALE-004
        $p4 = $getStdProduct(3);
        $s4 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-004',
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 4,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 2000,
            'grand_total' => 2000,
            'paid_amount' => [1000],
            'paying_amount' => [1000],
            'paid_by_id' => [3], // Bank
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p4->id],
            'product_code' => [$p4->code],
            'qty' => [4],
            'sale_unit' => [$getUnitName($p4)],
            'net_unit_price' => [500],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [2000],
        ], $user);
        $salesCreated[] = ['id' => $s4->id, 'reference_no' => 'DEMO-SALE-004', 'type' => 'Partial Payment Sale', 'grand_total' => 2000, 'paid' => 1000];

        // 5. Variant Sale - DEMO-SALE-005
        $pv = ProductVariant::query()->orderBy('id')->first();
        $varProduct = $pv ? Product::find($pv->product_id) : null;
        if ($varProduct && $pv) {
            $varCode = $pv->item_code;
        } else {
            $varProduct = $getStdProduct(4);
            $varCode = $varProduct->code;
        }
        $s5 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-005',
            'customer_id' => $custApex,
            'warehouse_id' => $retailWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 300,
            'grand_total' => 300,
            'paid_amount' => [300],
            'paying_amount' => [300],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$varProduct->id],
            'product_code' => [$varCode],
            'qty' => [2],
            'sale_unit' => [$getUnitName($varProduct)],
            'net_unit_price' => [150],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [300],
        ], $user);
        $salesCreated[] = ['id' => $s5->id, 'reference_no' => 'DEMO-SALE-005', 'type' => 'Variant Sale', 'grand_total' => 300, 'paid' => 300];

        // 6. Multi-Line Sale - DEMO-SALE-006
        $p6_1 = $getStdProduct(4);
        $p6_2 = $getStdProduct(5);
        $s6 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-006',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 2,
            'total_qty' => 4,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1200,
            'grand_total' => 1200,
            'paid_amount' => [1200],
            'paying_amount' => [1200],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p6_1->id, $p6_2->id],
            'product_code' => [$p6_1->code, $p6_2->code],
            'qty' => [2, 2],
            'sale_unit' => [$getUnitName($p6_1), $getUnitName($p6_2)],
            'net_unit_price' => [300, 300],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [600, 600],
        ], $user);
        $salesCreated[] = ['id' => $s6->id, 'reference_no' => 'DEMO-SALE-006', 'type' => 'Multi-Line Sale', 'grand_total' => 1200, 'paid' => 1200];

        // 7. Service Product Sale - DEMO-SALE-007
        $serviceProd = Product::where('type', 'service')->first();
        if (!$serviceProd) {
            $serviceProd = Product::create(['name' => 'IT Consulting Service', 'code' => 'SRV-001', 'type' => 'service', 'barcode_symbology' => 'C128', 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 0, 'price' => 500, 'qty' => 0, 'is_active' => true]);
        }
        $s7 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-007',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 500,
            'grand_total' => 500,
            'paid_amount' => [500],
            'paying_amount' => [500],
            'paid_by_id' => [3], // Bank
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$serviceProd->id],
            'product_code' => [$serviceProd->code],
            'qty' => [1],
            'sale_unit' => ['n/a'],
            'net_unit_price' => [500],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [500],
        ], $user);
        $salesCreated[] = ['id' => $s7->id, 'reference_no' => 'DEMO-SALE-007', 'type' => 'Service Product Sale', 'grand_total' => 500, 'paid' => 500];

        // 8. Digital Product Sale - DEMO-SALE-008
        $digitalProd = Product::where('type', 'digital')->first();
        if (!$digitalProd) {
            $digitalProd = Product::create(['name' => 'Software License key', 'code' => 'DIG-001', 'type' => 'digital', 'barcode_symbology' => 'C128', 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 0, 'price' => 400, 'qty' => 0, 'is_active' => true]);
        }

        $s8 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-008',
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 400,
            'grand_total' => 400,
            'paid_amount' => [400],
            'paying_amount' => [400],
            'paid_by_id' => [3], // Bank
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$digitalProd->id],
            'product_code' => [$digitalProd->code],
            'qty' => [1],
            'sale_unit' => ['n/a'],
            'net_unit_price' => [400],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [400],
        ], $user);
        $salesCreated[] = ['id' => $s8->id, 'reference_no' => 'DEMO-SALE-008', 'type' => 'Digital Product Sale', 'grand_total' => 400, 'paid' => 400];

        // 9. Retail Sale from Downtown Store - DEMO-SALE-009
        $p9 = $getStdProduct(1);
        $s9 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-009',
            'customer_id' => $custStarlight,
            'warehouse_id' => $retailWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 800,
            'grand_total' => 800,
            'paid_amount' => [800],
            'paying_amount' => [800],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p9->id],
            'product_code' => [$p9->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p9)],
            'net_unit_price' => [400],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [800],
        ], $user);
        $salesCreated[] = ['id' => $s9->id, 'reference_no' => 'DEMO-SALE-009', 'type' => 'Retail Store Sale', 'grand_total' => 800, 'paid' => 800];

        // 10. Reserved for Full Return - DEMO-SALE-010
        $p10 = $getStdProduct(6);
        $s10 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-010',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 600,
            'grand_total' => 600,
            'paid_amount' => [600],
            'paying_amount' => [600],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p10->id],
            'product_code' => [$p10->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p10)],
            'net_unit_price' => [300],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [600],
        ], $user);
        $salesCreated[] = ['id' => $s10->id, 'reference_no' => 'DEMO-SALE-010', 'type' => 'Reserved for Full Return', 'grand_total' => 600, 'paid' => 600];

        // 11. Reserved for Partial Return - DEMO-SALE-011
        $p11 = $getStdProduct(7);
        $s11 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-011',
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 4,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1000,
            'grand_total' => 1000,
            'paid_amount' => [1000],
            'paying_amount' => [1000],
            'paid_by_id' => [3],
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p11->id],
            'product_code' => [$p11->code],
            'qty' => [4],
            'sale_unit' => [$getUnitName($p11)],
            'net_unit_price' => [250],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [1000],
        ], $user);
        $salesCreated[] = ['id' => $s11->id, 'reference_no' => 'DEMO-SALE-011', 'type' => 'Reserved for Partial Return', 'grand_total' => 1000, 'paid' => 1000];

        // 12. Reserved for Same-Price Exchange - DEMO-SALE-012
        $p12 = $getStdProduct(8);
        $s12 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-012',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 500,
            'grand_total' => 500,
            'paid_amount' => [500],
            'paying_amount' => [500],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p12->id],
            'product_code' => [$p12->code],
            'qty' => [1],
            'sale_unit' => [$getUnitName($p12)],
            'net_unit_price' => [500],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [500],
        ], $user);
        $salesCreated[] = ['id' => $s12->id, 'reference_no' => 'DEMO-SALE-012', 'type' => 'Reserved for Exchange', 'grand_total' => 500, 'paid' => 500];

        // 13. Reserved for Differential Exchange - DEMO-SALE-013
        $p13 = $getStdProduct(9);
        $s13 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-013',
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 800,
            'grand_total' => 800,
            'paid_amount' => [800],
            'paying_amount' => [800],
            'paid_by_id' => [3],
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p13->id],
            'product_code' => [$p13->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p13)],
            'net_unit_price' => [400],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [800],
        ], $user);
        $salesCreated[] = ['id' => $s13->id, 'reference_no' => 'DEMO-SALE-013', 'type' => 'Reserved for Differential Exchange', 'grand_total' => 800, 'paid' => 800];

        // 14. Sale with Tax & Discount - DEMO-SALE-014
        $p14 = $getStdProduct(10);
        $s14 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-014',
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 50,
            'total_tax' => 50,
            'total_price' => 1000,
            'grand_total' => 1000,
            'paid_amount' => [1000],
            'paying_amount' => [1000],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p14->id],
            'product_code' => [$p14->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p14)],
            'net_unit_price' => [500],
            'discount' => [50],
            'tax_rate' => [5],
            'tax' => [50],
            'subtotal' => [1000],
        ], $user);
        $salesCreated[] = ['id' => $s14->id, 'reference_no' => 'DEMO-SALE-014', 'type' => 'Sale with Discount/Tax', 'grand_total' => 1000, 'paid' => 1000];

        // 15. POS Cash Sale - DEMO-SALE-015
        $p15 = $getStdProduct(11);
        $s15 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-015',
            'pos' => 1,
            'customer_id' => $custApex,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 350,
            'grand_total' => 350,
            'paid_amount' => [350],
            'paying_amount' => [350],
            'paid_by_id' => [1], // Cash
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p15->id],
            'product_code' => [$p15->code],
            'qty' => [1],
            'sale_unit' => [$getUnitName($p15)],
            'net_unit_price' => [350],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [350],
        ], $user);
        $salesCreated[] = ['id' => $s15->id, 'reference_no' => 'DEMO-SALE-015', 'type' => 'POS Cash Sale', 'grand_total' => 350, 'paid' => 350];

        // 16. POS Card Sale - DEMO-SALE-016
        $p16 = $getStdProduct(12);
        $s16 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-016',
            'pos' => 1,
            'customer_id' => $custNexus,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 450,
            'grand_total' => 450,
            'paid_amount' => [450],
            'paying_amount' => [450],
            'paid_by_id' => [3], // Credit Card / Bank
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p16->id],
            'product_code' => [$p16->code],
            'qty' => [1],
            'sale_unit' => [$getUnitName($p16)],
            'net_unit_price' => [450],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [450],
        ], $user);
        $salesCreated[] = ['id' => $s16->id, 'reference_no' => 'DEMO-SALE-016', 'type' => 'POS Card Sale', 'grand_total' => 450, 'paid' => 450];

        // 17. Restaurant Store Sale - DEMO-SALE-017
        $p17 = $getStdProduct(12);
        $s17 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-017',
            'customer_id' => $custHorizon,
            'warehouse_id' => $restaurantWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 750,
            'grand_total' => 750,
            'paid_amount' => [750],
            'paying_amount' => [750],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p17->id],
            'product_code' => [$p17->code],
            'qty' => [5],
            'sale_unit' => [$getUnitName($p17)],
            'net_unit_price' => [150],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [750],
        ], $user);
        $salesCreated[] = ['id' => $s17->id, 'reference_no' => 'DEMO-SALE-017', 'type' => 'Restaurant Store Sale', 'grand_total' => 750, 'paid' => 750];

        // 18. Service Center Part Sale - DEMO-SALE-018
        $p18 = $getStdProduct(18);
        $s18 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-018',
            'customer_id' => $custHorizon,
            'warehouse_id' => $serviceWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 600,
            'grand_total' => 600,
            'paid_amount' => [600],
            'paying_amount' => [600],
            'paid_by_id' => [3],
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p18->id],
            'product_code' => [$p18->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p18)],
            'net_unit_price' => [300],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [600],
        ], $user);
        $salesCreated[] = ['id' => $s18->id, 'reference_no' => 'DEMO-SALE-018', 'type' => 'Service Center Part Sale', 'grand_total' => 600, 'paid' => 600];

        // 19. Second Credit Customer Sale - DEMO-SALE-019
        $p19 = $getStdProduct(13);
        $s19 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-019',
            'customer_id' => $custHorizon,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 5,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 2500,
            'grand_total' => 2500,
            'paid_amount' => [0],
            'paying_amount' => [0],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p19->id],
            'product_code' => [$p19->code],
            'qty' => [5],
            'sale_unit' => [$getUnitName($p19)],
            'net_unit_price' => [500],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [2500],
        ], $user);
        $salesCreated[] = ['id' => $s19->id, 'reference_no' => 'DEMO-SALE-019', 'type' => 'Second Credit Customer Sale', 'grand_total' => 2500, 'paid' => 0];

        // 20. Third Partial Customer Sale - DEMO-SALE-020
        $p20 = $getStdProduct(14);
        $s20 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-020',
            'customer_id' => $custStarlight,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 3,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 1800,
            'grand_total' => 1800,
            'paid_amount' => [800],
            'paying_amount' => [800],
            'paid_by_id' => [3],
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p20->id],
            'product_code' => [$p20->code],
            'qty' => [3],
            'sale_unit' => [$getUnitName($p20)],
            'net_unit_price' => [600],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [1800],
        ], $user);
        $salesCreated[] = ['id' => $s20->id, 'reference_no' => 'DEMO-SALE-020', 'type' => 'Third Partial Customer Sale', 'grand_total' => 1800, 'paid' => 800];

        // 21. Multi-Item Credit Sale - DEMO-SALE-021
        $p21_1 = $getStdProduct(15);
        $p21_2 = $getStdProduct(16);
        $s21 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-021',
            'customer_id' => $custMetro,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 2,
            'total_qty' => 8,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 3200,
            'grand_total' => 3200,
            'paid_amount' => [1200],
            'paying_amount' => [1200],
            'paid_by_id' => [3],
            'account_id' => 2,
            'sale_status' => 1,
            'product_id' => [$p21_1->id, $p21_2->id],
            'product_code' => [$p21_1->code, $p21_2->code],
            'qty' => [4, 4],
            'sale_unit' => [$getUnitName($p21_1), $getUnitName($p21_2)],
            'net_unit_price' => [400, 400],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [1600, 1600],
        ], $user);
        $salesCreated[] = ['id' => $s21->id, 'reference_no' => 'DEMO-SALE-021', 'type' => 'Multi-Item Credit Sale', 'grand_total' => 3200, 'paid' => 1200];

        // 22. POS Mixed Sale - DEMO-SALE-022
        $p22 = $getStdProduct(17);
        $s22 = $saleService->createSale([
            'reference_no' => 'DEMO-SALE-022',
            'pos' => 1,
            'customer_id' => $custMetro,
            'warehouse_id' => $mainWhId,
            'biller_id' => 1,
            'currency_id' => 1,
            'exchange_rate' => 1,
            'item' => 1,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 500,
            'grand_total' => 500,
            'paid_amount' => [500],
            'paying_amount' => [500],
            'paid_by_id' => [1],
            'account_id' => 1,
            'sale_status' => 1,
            'product_id' => [$p22->id],
            'product_code' => [$p22->code],
            'qty' => [2],
            'sale_unit' => [$getUnitName($p22)],
            'net_unit_price' => [250],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [500],
        ], $user);
        $salesCreated[] = ['id' => $s22->id, 'reference_no' => 'DEMO-SALE-022', 'type' => 'POS Mixed Sale', 'grand_total' => 500, 'paid' => 500];

        return $salesCreated;
    }

    /**
     * Create Phase 13 Restaurant Scenarios (DEMO-REST-001 through DEMO-REST-006).
     *
     * @param array $purchasesCreated
     * @param User $user
     * @return array
     */
    public function createRestaurantScenarios(array $purchasesCreated, User $user): array
    {
        // Section 7 Idempotency Guard: Validate all 6 deterministic references individually
        $requiredRefs = ['DEMO-REST-001', 'DEMO-REST-002', 'DEMO-REST-003', 'DEMO-REST-004', 'DEMO-REST-005', 'DEMO-REST-006'];
        $existingRestSales = \App\Models\Sale::whereIn('reference_no', $requiredRefs)->get();
        if ($existingRestSales->count() === 6) {
            $allValid = true;
            foreach ($existingRestSales as $es) {
                $hasItems = \App\Models\Product_Sale::where('sale_id', $es->id)->exists();
                $hasPayments = \App\Models\Payment::where('sale_id', $es->id)->exists();
                $hasJournals = DB::table('journal_entries')->where('source_id', $es->id)->exists();
                $psIds = \App\Models\Product_Sale::where('sale_id', $es->id)->pluck('id');
                $hasModifiers = DB::table('product_sale_modifiers')->whereIn('product_sale_id', $psIds)->exists();

                if (in_array($es->reference_no, ['DEMO-REST-002', 'DEMO-REST-006']) && !$hasModifiers) {
                    $allValid = false;
                    break;
                }
                if (!$hasItems || !$hasPayments || !$hasJournals) {
                    $allValid = false;
                    break;
                }
            }

            if ($allValid) {
                $result = [];
                foreach ($existingRestSales as $es) {
                    $result[] = [
                        'id' => $es->id,
                        'reference_no' => $es->reference_no,
                        'type' => $es->sale_type ?? 'dine_in',
                        'table' => $es->table_id ? (Table::find($es->table_id)->name ?? null) : null,
                        'grand_total' => (float) $es->grand_total,
                        'paid_amount' => (float) $es->paid_amount,
                    ];
                }
                return $result;
            }
        }

        /** @var SaleDomainService $saleService */
        $saleService = app(SaleDomainService::class);

        $cashAcc = Account::where('account_no', '1001')->orWhere('name', 'LIKE', '%Cash%')->first();
        $cashAccId = $cashAcc ? $cashAcc->id : 1;

        $bankAcc = Account::where('account_no', '1002')->orWhere('name', 'LIKE', '%Bank%')->first();
        $bankAccId = $bankAcc ? $bankAcc->id : 2;

        $restaurantWarehouse = Warehouse::where('name', 'LIKE', '%Restaurant%')->first()
            ?? Warehouse::skip(2)->first()
            ?? Warehouse::first();
        $warehouseId = $restaurantWarehouse->id;

        // Select Customers
        $walkinCustomer = Customer::where('name', 'LIKE', '%Walk-in%')->first() ?? Customer::first();
        $deliveryCustomer = Customer::where('name', 'LIKE', '%Delivery%')->first() ?? Customer::skip(1)->first() ?? Customer::first();

        // Tables
        $g01Table = Table::where('name', 'G01')->first() ?? Table::first();
        $g02Table = Table::where('name', 'G02')->first() ?? Table::skip(1)->first();
        $g03Table = Table::where('name', 'G03')->first() ?? Table::skip(2)->first();
        $f01Table = Table::where('name', 'F01')->first() ?? Table::skip(4)->first();

        // Products for Restaurant Menu must already have transaction-backed
        // stock in the Restaurant warehouse. Never provision demo inventory by
        // directly overwriting product_warehouse rows.
        $products = Product::query()
            ->join('product_warehouse as restaurant_stock', 'restaurant_stock.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->whereNotIn('products.type', ['digital', 'service'])
            ->where(function ($query) {
                $query->whereNull('products.is_variant')->orWhere('products.is_variant', false);
            })
            ->where('restaurant_stock.warehouse_id', $warehouseId)
            ->where('restaurant_stock.qty', '>', 5)
            ->orderBy('products.id')
            ->select('products.*')
            ->distinct()
            ->take(4)
            ->get();
        if ($products->count() < 2) {
            throw new RuntimeException('Insufficient transaction-backed Restaurant warehouse stock for Restaurant scenarios.');
        }

        $foodProd1 = $products[0];
        $foodProd2 = $products[1];
        $drinkProd = $products[2] ?? $products[0];

        // Attach Modifier Groups to foodProd1 and foodProd2 if not already attached
        $spiceGroup = ModifierGroup::where('name', 'Spice Level')->first();
        $cheeseGroup = ModifierGroup::where('name', 'Cheese')->first();
        $toppingGroup = ModifierGroup::where('name', 'Extra Toppings')->first();

        if ($spiceGroup) {
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd1->id, 'modifier_group_id' => $spiceGroup->id]);
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd2->id, 'modifier_group_id' => $spiceGroup->id]);
            foreach (Modifier::where('modifier_group_id', $spiceGroup->id)->get() as $mod) {
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd1->id,
                    'modifier_group_id' => $spiceGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd2->id,
                    'modifier_group_id' => $spiceGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
            }
        }

        if ($cheeseGroup) {
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd1->id, 'modifier_group_id' => $cheeseGroup->id]);
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd2->id, 'modifier_group_id' => $cheeseGroup->id]);
            foreach (Modifier::where('modifier_group_id', $cheeseGroup->id)->get() as $mod) {
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd1->id,
                    'modifier_group_id' => $cheeseGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd2->id,
                    'modifier_group_id' => $cheeseGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
            }
        }

        if ($toppingGroup) {
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd1->id, 'modifier_group_id' => $toppingGroup->id]);
            ProductModifierGroup::firstOrCreate(['product_id' => $foodProd2->id, 'modifier_group_id' => $toppingGroup->id]);
            foreach (Modifier::where('modifier_group_id', $toppingGroup->id)->get() as $mod) {
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd1->id,
                    'modifier_group_id' => $toppingGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
                ProductModifierGroupModifier::firstOrCreate([
                    'product_id' => $foodProd2->id,
                    'modifier_group_id' => $toppingGroup->id,
                    'modifier_id' => $mod->id
                ], ['price_adjustment' => $mod->price_adjustment, 'is_active' => true]);
            }
        }

        $restaurantSalesCreated = [];

        // DEMO-REST-001: Dine-In Basic Order (Table G01, Cash $16.00)
        $s1 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-001',
            'user_id' => $user->id,
            'customer_id' => $walkinCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => $g01Table->id,
            'sale_type' => 'dine_in',
            'service_id' => 1,
            'waiter_id' => $user->id,
            'item' => 2,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 16.00,
            'grand_total' => 16.00,
            'paid_amount' => 16.00,
            'account_id' => $cashAccId,
            'paid_by_id' => [1],
            'paying_amount' => [16.00],
            'product_id' => [$foodProd1->id, $drinkProd->id],
            'product_code' => [$foodProd1->code, $drinkProd->code],
            'qty' => [1, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [12.00, 4.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [12.00, 4.00],
            'sale_note' => 'Dine-In basic order DEMO-REST-001',
        ], $user);

        $restaurantSalesCreated[] = [
            'id' => $s1->id,
            'reference_no' => 'DEMO-REST-001',
            'type' => 'Dine-In Basic',
            'table' => 'G01',
            'grand_total' => 16.00,
            'paid_amount' => 16.00
        ];

        // DEMO-REST-002: Dine-In with Modifiers (Table G02, Cash $21.50)
        $hotMod = Modifier::where('name', 'Hot')->first();
        $extraCheeseMod = Modifier::where('name', 'Extra Cheese')->first();
        $extraSauceMod = Modifier::where('name', 'Extra Sauce')->first();

        $modPayloadArr = [];
        if ($hotMod) $modPayloadArr[] = ['id' => $hotMod->id, 'price' => $hotMod->price_adjustment];
        if ($extraCheeseMod) $modPayloadArr[] = ['id' => $extraCheeseMod->id, 'price' => $extraCheeseMod->price_adjustment];
        if ($extraSauceMod) $modPayloadArr[] = ['id' => $extraSauceMod->id, 'price' => $extraSauceMod->price_adjustment];

        $modPayloadJson = json_encode($modPayloadArr);

        $s2 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-002',
            'user_id' => $user->id,
            'customer_id' => $walkinCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => $g02Table->id,
            'sale_type' => 'dine_in',
            'service_id' => 1,
            'waiter_id' => $user->id,
            'item' => 2,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 21.50,
            'grand_total' => 21.50,
            'paid_amount' => 21.50,
            'account_id' => $cashAccId,
            'paid_by_id' => [1],
            'paying_amount' => [21.50],
            'product_id' => [$foodProd1->id, $drinkProd->id],
            'product_code' => [$foodProd1->code, $drinkProd->code],
            'qty' => [1, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [17.50, 4.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [17.50, 4.00],
            'topping_product' => [$modPayloadJson, null],
            'sale_note' => 'Dine-In with modifiers DEMO-REST-002',
        ], $user);

        $restaurantSalesCreated[] = [
            'id' => $s2->id,
            'reference_no' => 'DEMO-REST-002',
            'type' => 'Dine-In Modifiers',
            'table' => 'G02',
            'grand_total' => 21.50,
            'paid_amount' => 21.50
        ];

        // DEMO-REST-003: Second Floor Dine-In (Table F01, Bank Card $22.00)
        $s3 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-003',
            'user_id' => $user->id,
            'customer_id' => $walkinCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => $f01Table->id,
            'sale_type' => 'dine_in',
            'service_id' => 1,
            'waiter_id' => $user->id,
            'item' => 2,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 22.00,
            'grand_total' => 22.00,
            'paid_amount' => 22.00,
            'account_id' => $bankAccId,
            'paid_by_id' => [3],
            'paying_amount' => [22.00],
            'product_id' => [$foodProd2->id, $drinkProd->id],
            'product_code' => [$foodProd2->code, $drinkProd->code],
            'qty' => [1, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [18.00, 4.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [18.00, 4.00],
            'sale_note' => 'Second floor Dine-In DEMO-REST-003',
        ], $user);

        $restaurantSalesCreated[] = [
            'id' => $s3->id,
            'reference_no' => 'DEMO-REST-003',
            'type' => 'Second Floor Dine-In',
            'table' => 'F01',
            'grand_total' => 22.00,
            'paid_amount' => 22.00
        ];

        // DEMO-REST-004: Takeaway Order (No table, Cash $19.00)
        $s4 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-004',
            'user_id' => $user->id,
            'customer_id' => $walkinCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => null,
            'sale_type' => 'takeaway',
            'service_id' => 2,
            'item' => 2,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 19.00,
            'grand_total' => 19.00,
            'paid_amount' => 19.00,
            'account_id' => $cashAccId,
            'paid_by_id' => [1],
            'paying_amount' => [19.00],
            'product_id' => [$foodProd1->id, $drinkProd->id],
            'product_code' => [$foodProd1->code, $drinkProd->code],
            'qty' => [1, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [15.00, 4.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [15.00, 4.00],
            'sale_note' => 'Takeaway order DEMO-REST-004',
        ], $user);

        $restaurantSalesCreated[] = [
            'id' => $s4->id,
            'reference_no' => 'DEMO-REST-004',
            'type' => 'Takeaway',
            'table' => null,
            'grand_total' => 19.00,
            'paid_amount' => 19.00
        ];

        // DEMO-REST-005: Delivery Order (Partial/Credit payment, $15 paid, $20 due)
        $s5 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-005',
            'user_id' => $user->id,
            'customer_id' => $deliveryCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => null,
            'sale_type' => 'delivery',
            'service_id' => 3,
            'item' => 2,
            'total_qty' => 3,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 35.00,
            'grand_total' => 35.00,
            'paid_amount' => 15.00,
            'payment_status' => 2, // Partial
            'account_id' => $cashAccId,
            'paid_by_id' => [1],
            'paying_amount' => [15.00],
            'product_id' => [$foodProd1->id, $drinkProd->id],
            'product_code' => [$foodProd1->code, $drinkProd->code],
            'qty' => [2, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [15.00, 5.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [30.00, 5.00],
            'sale_note' => 'Delivery partial credit order DEMO-REST-005',
        ], $user);

        $restaurantSalesCreated[] = [
            'id' => $s5->id,
            'reference_no' => 'DEMO-REST-005',
            'type' => 'Delivery Credit',
            'table' => null,
            'grand_total' => 35.00,
            'paid_amount' => 15.00,
            'due_amount' => 20.00
        ];

        // DEMO-REST-006: Advanced Modifiers + KDS Lifecycle (Table G03, Cash $27.00)
        $mediumMod = Modifier::where('name', 'Medium')->first();
        $regCheeseMod = Modifier::where('name', 'Regular Cheese')->first();
        $pepperoniMod = Modifier::where('name', 'Pepperoni')->first();
        $mushroomsMod = Modifier::where('name', 'Mushrooms')->first();

        $modPayloadArr6 = [];
        if ($mediumMod) $modPayloadArr6[] = ['id' => $mediumMod->id, 'price' => $mediumMod->price_adjustment];
        if ($regCheeseMod) $modPayloadArr6[] = ['id' => $regCheeseMod->id, 'price' => $regCheeseMod->price_adjustment];
        if ($pepperoniMod) $modPayloadArr6[] = ['id' => $pepperoniMod->id, 'price' => $pepperoniMod->price_adjustment];
        if ($mushroomsMod) $modPayloadArr6[] = ['id' => $mushroomsMod->id, 'price' => $mushroomsMod->price_adjustment];

        $modPayloadJson6 = json_encode($modPayloadArr6);

        $s6 = $saleService->createSale([
            'reference_no' => 'DEMO-REST-006',
            'user_id' => $user->id,
            'customer_id' => $walkinCustomer->id,
            'warehouse_id' => $warehouseId,
            'biller_id' => 1,
            'table_id' => $g03Table->id,
            'sale_type' => 'dine_in',
            'service_id' => 1,
            'waiter_id' => $user->id,
            'item' => 2,
            'total_qty' => 2,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 27.00,
            'grand_total' => 27.00,
            'paid_amount' => 27.00,
            'account_id' => $cashAccId,
            'paid_by_id' => [1],
            'paying_amount' => [27.00],
            'product_id' => [$foodProd2->id, $drinkProd->id],
            'product_code' => [$foodProd2->code, $drinkProd->code],
            'qty' => [1, 1],
            'sale_unit' => ['pc', 'pc'],
            'net_unit_price' => [23.00, 4.00],
            'discount' => [0, 0],
            'tax_rate' => [0, 0],
            'tax' => [0, 0],
            'subtotal' => [23.00, 4.00],
            'topping_product' => [$modPayloadJson6, null],
            'sale_note' => 'Advanced Modifiers Dine-In DEMO-REST-006',
        ], $user);

        // KDS Lifecycle Transitions
        $s6->sale_status = 5;
        $s6->save();

        if (class_exists(\Modules\Restaurant\Http\Controllers\kitchenController::class)) {
            app(\Modules\Restaurant\Http\Controllers\kitchenController::class)->markCooked($s6->id);
            app(\Modules\Restaurant\Http\Controllers\kitchenController::class)->markServed($s6->id);
        }

        $restaurantSalesCreated[] = [
            'id' => $s6->id,
            'reference_no' => 'DEMO-REST-006',
            'type' => 'Advanced Modifiers KDS',
            'table' => 'G03',
            'grand_total' => 27.00,
            'paid_amount' => 27.00
        ];

        return $restaurantSalesCreated;
    }

    /**
     * Clean up existing Golden Demo scenarios for deterministic rebuilding.
     */
    public function cleanupDemoScenarios(): void
    {
        GoldenDemoSafety::cleanExistingDemoTransactions();
    }
}
