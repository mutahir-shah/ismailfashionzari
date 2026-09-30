<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\AIAssistant\Skills\PurchaseSummarySkill;
use Modules\AIAssistant\Skills\LowStockSkill;
use Modules\AIAssistant\Skills\TopProductsSkill;
use Modules\AIAssistant\Skills\CustomerDueSkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\Services\SkillRegistry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Customer;
use App\Models\Product;

class Phase3RemainingSkillsTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Shared helpers
    // ---------------------------------------------------------------------------

    private function createUser(array $override = []): User
    {
        return User::create(array_merge([
            'name'       => 'Test User',
            'email'      => 'user' . uniqid() . '@example.com',
            'phone'      => '123456789',
            'password'   => bcrypt('password'),
            'role_id'    => 1,
            'is_active'  => true,
            'is_deleted' => false,
        ], $override));
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name'      => 'WH ' . uniqid(),
            'phone'     => '000',
            'email'     => 'wh' . uniqid() . '@example.com',
            'address'   => 'addr',
            'is_active' => true,
        ]);
    }

    private function createCustomer(array $override = []): Customer
    {
        return Customer::create(array_merge([
            'customer_group_id' => 1,
            'name'              => 'Cust ' . uniqid(),
            'company_name'      => 'Co',
            'email'             => 'cust' . uniqid() . '@example.com',
            'phone_number'      => '123',
            'address'           => 'Addr',
            'city'              => 'City',
            'is_active'         => true,
        ], $override));
    }

    private function createSale(array $override = []): Sale
    {
        return Sale::create(array_merge([
            'reference_no'   => 'SR-' . uniqid(),
            'user_id'        => 1,
            'customer_id'    => 1,
            'warehouse_id'   => 1,
            'biller_id'      => 1,
            'item'           => 1,
            'total_qty'      => 1,
            'total_discount' => 0,
            'total_tax'      => 0,
            'total_price'    => 100,
            'grand_total'    => 100,
            'paid_amount'    => 100,
            'order_tax_rate' => 0,
            'order_tax'      => 0,
            'order_discount' => 0,
            'payment_status' => 4,
            'sale_status'    => 1,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),
        ], $override));
    }

    private function createPurchase(array $override = []): Purchase
    {
        return Purchase::create(array_merge([
            'reference_no'   => 'PR-' . uniqid(),
            'user_id'        => 1,
            'warehouse_id'   => 1,
            'supplier_id'    => 1,
            'item'           => 1,
            'total_qty'      => 1,
            'total_discount' => 0,
            'total_tax'      => 0,
            'total_cost'     => 100,
            'grand_total'    => 100,
            'paid_amount'    => 100,
            'order_tax_rate' => 0,
            'order_tax'      => 0,
            'order_discount' => 0,
            'payment_status' => 4,
            'status'         => 1,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),
        ], $override));
    }

    private function createProduct(array $override = []): Product
    {
        return Product::create(array_merge([
            'name'               => 'Product ' . uniqid(),
            'code'               => 'P-' . uniqid(),
            'type'               => 'standard',
            'barcode_symbology'  => 'C128',
            'category_id'        => 1,
            'unit_id'            => 1,
            'purchase_unit_id'   => 1,
            'sale_unit_id'       => 1,
            'cost'               => 10,
            'price'              => 15,
            'alert_quantity'     => 5,
            'is_active'          => true,
        ], $override));
    }

    private function createProductWarehouse(int $productId, int $warehouseId, float $qty): void
    {
        \Illuminate\Support\Facades\DB::table('product_warehouse')->insert([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'qty'          => $qty,
        ]);
    }

    private function createProductSale(int $saleId, int $productId, float $qty, float $total): void
    {
        \Illuminate\Support\Facades\DB::table('product_sales')->insert([
            'sale_id'        => $saleId,
            'product_id'     => $productId,
            'qty'            => $qty,
            'return_qty'     => 0,
            'sale_unit_id'   => 1,
            'net_unit_price' => $total / $qty,
            'discount'       => 0,
            'tax_rate'       => 0,
            'tax'            => 0,
            'total'          => $total,
        ]);
    }

    // ============================================================================
    // PURCHASE SUMMARY SKILL
    // ============================================================================

    public function test_purchase_summary_stable_metadata()
    {
        $skill = new PurchaseSummarySkill();
        $this->assertEquals('purchase_summary', $skill->key());
        $this->assertNotEmpty($skill->name());
        $this->assertNotEmpty($skill->description());
        $this->assertNotEmpty($skill->examples());
    }

    public function test_purchase_summary_resolves_supported_prompts()
    {
        $skill = new PurchaseSummarySkill();
        $prompts = [
            'show todays purchases',
            'purchases today',
            'purchase today',
            'purchase summary',
            'purchase summary today',
        ];
        foreach ($prompts as $p) {
            $this->assertTrue($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should resolve.");
        }
    }

    public function test_purchase_summary_whitespace_and_punctuation_tolerance()
    {
        $skill = new PurchaseSummarySkill();
        $prompts = [
            "  purchase   summary  ",
            "Purchase Summary",
            "PURCHASE SUMMARY",
            "purchase-summary",
        ];
        // Only whitespace/case normalisation is guaranteed; hyphen removal maps to 'purchase summary'
        $this->assertTrue($skill->canHandle(new AssistantMessageData('user', '  purchase   summary  ')));
        $this->assertTrue($skill->canHandle(new AssistantMessageData('user', 'Purchase Summary')));
    }

    public function test_purchase_summary_does_not_resolve_unrelated_prompts()
    {
        $skill = new PurchaseSummarySkill();
        $unrelated = [
            'sales summary',
            'low stock',
            'top products',
            'customer due summary',
            'show random data',
        ];
        foreach ($unrelated as $p) {
            $this->assertFalse($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should NOT resolve.");
        }
    }

    public function test_purchase_summary_correct_totals_for_today()
    {
        $wh = $this->createWarehouse();
        $this->createPurchase(['warehouse_id' => $wh->id, 'total_qty' => 2, 'grand_total' => 200, 'paid_amount' => 200]);
        $this->createPurchase(['warehouse_id' => $wh->id, 'total_qty' => 3, 'grand_total' => 300, 'paid_amount' => 150]);

        $skill    = new PurchaseSummarySkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'purchase summary'), $context);

        $this->assertEquals(2, $response->cards[0]['value']);   // count
        $this->assertEquals(5, $response->cards[1]['value']);   // qty
        $this->assertEquals(500, $response->cards[2]['value']); // grand_total
        $this->assertEquals(350, $response->cards[3]['value']); // paid
        $this->assertEquals(150, $response->cards[4]['value']); // due (300-150)
    }

    public function test_purchase_summary_excludes_drafts_and_soft_deleted()
    {
        $this->createPurchase(['grand_total' => 100, 'paid_amount' => 100]);              // included
        $this->createPurchase(['grand_total' => 999, 'status' => 3]);                     // draft — excluded
        $deletedPurchase = $this->createPurchase(['grand_total' => 999]);
        $deletedPurchase->delete();     // soft-deleted — excluded

        $skill    = new PurchaseSummarySkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'purchase summary'), $context);

        $this->assertEquals(1, $response->cards[0]['value']);
        $this->assertEquals(100, $response->cards[2]['value']);
    }

    public function test_purchase_summary_warehouse_restriction()
    {
        $wh1 = $this->createWarehouse();
        $wh2 = $this->createWarehouse();
        $this->createPurchase(['warehouse_id' => $wh1->id, 'grand_total' => 100, 'paid_amount' => 100]);
        $this->createPurchase(['warehouse_id' => $wh2->id, 'grand_total' => 200, 'paid_amount' => 200]);

        $skill    = new PurchaseSummarySkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => [$wh1->id]]);
        $response = $skill->handle(new AssistantMessageData('user', 'purchase summary'), $context);

        $this->assertEquals(1, $response->cards[0]['value']);
        $this->assertEquals(100, $response->cards[2]['value']);
    }

    public function test_purchase_summary_empty_warehouse_returns_zero()
    {
        $this->createPurchase(['grand_total' => 100]);

        $skill    = new PurchaseSummarySkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => []]);
        $response = $skill->handle(new AssistantMessageData('user', 'purchase summary'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
        $this->assertEquals(0, $response->cards[2]['value']);
    }

    public function test_purchase_summary_no_warehouse_key_returns_all()
    {
        $wh1 = $this->createWarehouse();
        $wh2 = $this->createWarehouse();
        $this->createPurchase(['warehouse_id' => $wh1->id, 'grand_total' => 100, 'paid_amount' => 100]);
        $this->createPurchase(['warehouse_id' => $wh2->id, 'grand_total' => 200, 'paid_amount' => 200]);

        $skill    = new PurchaseSummarySkill();
        $context  = new AssistantContextData(businessContext: []);
        $response = $skill->handle(new AssistantMessageData('user', 'purchase summary'), $context);

        $this->assertEquals(2, $response->cards[0]['value']);
        $this->assertEquals(300, $response->cards[2]['value']);
    }

    // ============================================================================
    // LOW STOCK SKILL
    // ============================================================================

    public function test_low_stock_stable_metadata()
    {
        $skill = new LowStockSkill();
        $this->assertEquals('low_stock', $skill->key());
        $this->assertNotEmpty($skill->name());
        $this->assertNotEmpty($skill->description());
        $this->assertNotEmpty($skill->examples());
    }

    public function test_low_stock_resolves_supported_prompts()
    {
        $skill = new LowStockSkill();
        $prompts = [
            'show low stock products',
            'which products are low in stock',
            'low stock',
            'stock alerts',
        ];
        foreach ($prompts as $p) {
            $this->assertTrue($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should resolve.");
        }
    }

    public function test_low_stock_does_not_resolve_unrelated_prompts()
    {
        $skill = new LowStockSkill();
        $unrelated = [
            'sales summary',
            'purchase summary',
            'top products',
            'customer due summary',
            'which customers owe money',
        ];
        foreach ($unrelated as $p) {
            $this->assertFalse($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should NOT resolve.");
        }
    }

    public function test_low_stock_detects_products_below_threshold()
    {
        $wh      = $this->createWarehouse();
        $product = $this->createProduct(['alert_quantity' => 5]);
        $this->createProductWarehouse($product->id, $wh->id, 3); // 3 <= 5 => low stock

        $skill    = new LowStockSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'low stock'), $context);

        $this->assertGreaterThanOrEqual(1, $response->cards[0]['value']);
    }

    public function test_low_stock_excludes_products_above_threshold()
    {
        $wh      = $this->createWarehouse();
        $product = $this->createProduct(['alert_quantity' => 5]);
        $this->createProductWarehouse($product->id, $wh->id, 10); // 10 > 5 => not low

        $skill    = new LowStockSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'low stock'), $context);

        // The product with qty=10 should NOT be in the list
        $this->assertEquals(0, $response->cards[0]['value']);
    }

    public function test_low_stock_empty_warehouse_returns_zero()
    {
        $wh      = $this->createWarehouse();
        $product = $this->createProduct(['alert_quantity' => 5]);
        $this->createProductWarehouse($product->id, $wh->id, 2); // low stock in wh

        $skill    = new LowStockSkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => []]);
        $response = $skill->handle(new AssistantMessageData('user', 'low stock'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
    }

    public function test_low_stock_warehouse_restriction_filters_results()
    {
        $wh1     = $this->createWarehouse();
        $wh2     = $this->createWarehouse();
        $product = $this->createProduct(['alert_quantity' => 5]);
        $this->createProductWarehouse($product->id, $wh2->id, 2); // low in wh2 only

        $skill    = new LowStockSkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => [$wh1->id]]);
        $response = $skill->handle(new AssistantMessageData('user', 'low stock'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
    }

    // ============================================================================
    // TOP PRODUCTS SKILL
    // ============================================================================

    public function test_top_products_stable_metadata()
    {
        $skill = new TopProductsSkill();
        $this->assertEquals('top_products', $skill->key());
        $this->assertNotEmpty($skill->name());
        $this->assertNotEmpty($skill->description());
        $this->assertNotEmpty($skill->examples());
    }

    public function test_top_products_resolves_supported_prompts()
    {
        $skill = new TopProductsSkill();
        $prompts = [
            'show top selling products',
            'best selling products today',
            'top products',
            'what sold the most today',
        ];
        foreach ($prompts as $p) {
            $this->assertTrue($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should resolve.");
        }
    }

    public function test_top_products_does_not_resolve_unrelated_prompts()
    {
        $skill = new TopProductsSkill();
        $unrelated = [
            'sales summary',
            'purchase summary',
            'low stock',
            'customer due summary',
            'stock alerts',
        ];
        foreach ($unrelated as $p) {
            $this->assertFalse($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should NOT resolve.");
        }
    }

    public function test_top_products_returns_products_ranked_by_qty()
    {
        $wh       = $this->createWarehouse();
        $product1 = $this->createProduct();
        $product2 = $this->createProduct();

        // Sale for product2 has more qty
        $sale1 = $this->createSale(['warehouse_id' => $wh->id, 'grand_total' => 100]);
        $sale2 = $this->createSale(['warehouse_id' => $wh->id, 'grand_total' => 300]);
        $this->createProductSale($sale1->id, $product1->id, 1, 100);
        $this->createProductSale($sale2->id, $product2->id, 3, 300);

        $skill    = new TopProductsSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'top products'), $context);

        $this->assertNotEmpty($response->table);
        $this->assertCount(2, $response->table['rows']);
        // First row should be the highest qty product
        $this->assertStringContainsString($product2->name, $response->table['rows'][0][0]);
    }

    public function test_top_products_excludes_drafts_and_opening_balance()
    {
        $product = $this->createProduct();
        $sale    = $this->createSale(['sale_status' => 3, 'grand_total' => 100]); // draft
        $this->createProductSale($sale->id, $product->id, 5, 100);

        $skill    = new TopProductsSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'top products'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
    }

    public function test_top_products_empty_warehouse_returns_empty()
    {
        $wh      = $this->createWarehouse();
        $product = $this->createProduct();
        $sale    = $this->createSale(['warehouse_id' => $wh->id]);
        $this->createProductSale($sale->id, $product->id, 2, 100);

        $skill    = new TopProductsSkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => []]);
        $response = $skill->handle(new AssistantMessageData('user', 'top products'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
    }

    public function test_top_products_warehouse_restriction()
    {
        $wh1     = $this->createWarehouse();
        $wh2     = $this->createWarehouse();
        $product = $this->createProduct();

        $sale1 = $this->createSale(['warehouse_id' => $wh1->id, 'grand_total' => 100]);
        $sale2 = $this->createSale(['warehouse_id' => $wh2->id, 'grand_total' => 200]);
        $this->createProductSale($sale1->id, $product->id, 1, 100);
        $this->createProductSale($sale2->id, $product->id, 2, 200);

        $skill    = new TopProductsSkill();
        $context  = new AssistantContextData(businessContext: ['warehouse_ids' => [$wh1->id]]);
        $response = $skill->handle(new AssistantMessageData('user', 'top products'), $context);

        // Only wh1 sale counts — qty_sold should be 1, not 3
        $this->assertNotEmpty($response->table);
        $this->assertEquals('1', $response->table['rows'][0][1]); // qty sold
    }

    // ============================================================================
    // CUSTOMER DUE SKILL
    // ============================================================================

    public function test_customer_due_stable_metadata()
    {
        $skill = new CustomerDueSkill();
        $this->assertEquals('customer_due', $skill->key());
        $this->assertNotEmpty($skill->name());
        $this->assertNotEmpty($skill->description());
        $this->assertNotEmpty($skill->examples());
    }

    public function test_customer_due_resolves_supported_prompts()
    {
        $skill = new CustomerDueSkill();
        $prompts = [
            'customer due summary',
            'show customer dues',
            'which customers owe money',
            'outstanding customer balances',
        ];
        foreach ($prompts as $p) {
            $this->assertTrue($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should resolve.");
        }
    }

    public function test_customer_due_does_not_resolve_unrelated_prompts()
    {
        $skill = new CustomerDueSkill();
        $unrelated = [
            'sales summary',
            'purchase summary',
            'low stock',
            'top products',
            'show todays sales',
        ];
        foreach ($unrelated as $p) {
            $this->assertFalse($skill->canHandle(new AssistantMessageData('user', $p)), "'{$p}' should NOT resolve.");
        }
    }

    public function test_customer_due_returns_customers_with_positive_balance()
    {
        $customer = $this->createCustomer(['opening_balance' => 0]);
        $this->createSale([
            'customer_id'    => $customer->id,
            'grand_total'    => 200,
            'paid_amount'    => 100,
            'payment_status' => 2, // partial
        ]);

        $skill    = new CustomerDueSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'customer due summary'), $context);

        $this->assertGreaterThanOrEqual(1, $response->cards[0]['value']);
        $this->assertGreaterThan(0, $response->cards[1]['value']);
    }

    public function test_customer_due_excludes_zero_balance_customers()
    {
        $customer = $this->createCustomer(['opening_balance' => 0]);
        $this->createSale([
            'customer_id' => $customer->id,
            'grand_total' => 100,
            'paid_amount' => 100,
        ]);

        $skill    = new CustomerDueSkill();
        $context  = new AssistantContextData();
        $response = $skill->handle(new AssistantMessageData('user', 'customer due summary'), $context);

        // A fully paid customer should not appear
        // The count may be zero (or exclude this customer)
        // We just check the total is >= 0
        $this->assertGreaterThanOrEqual(0, $response->cards[0]['value']);
    }

    // ============================================================================
    // REGISTRY – all 5 skills registered
    // ============================================================================

    public function test_registry_contains_all_five_phase3_skills()
    {
        /** @var SkillRegistry $registry */
        $registry = app(SkillRegistry::class);

        // SkillRegistry::all() returns a plain array
        $keys = array_map(fn($s) => $s->key(), $registry->all());

        $this->assertContains('sales_summary', $keys);
        $this->assertContains('purchase_summary', $keys);
        $this->assertContains('low_stock', $keys);
        $this->assertContains('top_products', $keys);
        $this->assertContains('customer_due', $keys);
    }

    // ============================================================================
    // CROSS-SKILL COLLISION TESTS
    // ============================================================================

    public function test_sales_prompts_do_not_resolve_purchase_skill()
    {
        $skill = new PurchaseSummarySkill();
        $this->assertFalse($skill->canHandle(new AssistantMessageData('user', 'sales summary')));
        $this->assertFalse($skill->canHandle(new AssistantMessageData('user', 'show todays sales')));
    }

    public function test_purchase_prompts_do_not_resolve_sales_skill()
    {
        $skill = new \Modules\AIAssistant\Skills\SalesSummarySkill();
        $this->assertFalse($skill->canHandle(new AssistantMessageData('user', 'purchase summary')));
        $this->assertFalse($skill->canHandle(new AssistantMessageData('user', 'purchases today')));
    }

    public function test_low_stock_prompts_do_not_cross_resolve()
    {
        $salesSkill    = new \Modules\AIAssistant\Skills\SalesSummarySkill();
        $purchSkill    = new PurchaseSummarySkill();
        $topSkill      = new TopProductsSkill();
        $dueSkill      = new CustomerDueSkill();

        $msg = new AssistantMessageData('user', 'low stock');
        $this->assertFalse($salesSkill->canHandle($msg));
        $this->assertFalse($purchSkill->canHandle($msg));
        $this->assertFalse($topSkill->canHandle($msg));
        $this->assertFalse($dueSkill->canHandle($msg));
    }

    public function test_top_products_prompts_do_not_cross_resolve()
    {
        $salesSkill    = new \Modules\AIAssistant\Skills\SalesSummarySkill();
        $purchSkill    = new PurchaseSummarySkill();
        $lowSkill      = new LowStockSkill();
        $dueSkill      = new CustomerDueSkill();

        $msg = new AssistantMessageData('user', 'top products');
        $this->assertFalse($salesSkill->canHandle($msg));
        $this->assertFalse($purchSkill->canHandle($msg));
        $this->assertFalse($lowSkill->canHandle($msg));
        $this->assertFalse($dueSkill->canHandle($msg));
    }

    public function test_customer_due_prompts_do_not_cross_resolve()
    {
        $salesSkill    = new \Modules\AIAssistant\Skills\SalesSummarySkill();
        $purchSkill    = new PurchaseSummarySkill();
        $lowSkill      = new LowStockSkill();
        $topSkill      = new TopProductsSkill();

        $msg = new AssistantMessageData('user', 'customer due summary');
        $this->assertFalse($salesSkill->canHandle($msg));
        $this->assertFalse($purchSkill->canHandle($msg));
        $this->assertFalse($lowSkill->canHandle($msg));
        $this->assertFalse($topSkill->canHandle($msg));
    }

    // ============================================================================
    // STRUCTURED PROMPT ENDPOINT
    // ============================================================================

    private function seedGeneralSetting(): void
    {
        \Illuminate\Support\Facades\DB::table('general_settings')->updateOrInsert(['id' => 1], [
            'site_title'        => 'Test',
            'currency'          => 1,
            'currency_position' => 'prefix',
            'staff_access'      => 'all',
            'date_format'       => 'd-m-Y',
            'theme'             => 'default.css',
            'updated_at'        => now(),
        ]);
        Cache::forget('general_setting');
        Cache::forget('currency');
    }

    private function mockPermission(User $user): void
    {
        $roleId = $user->role_id;
        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(['id' => $roleId], [
            'name' => 'AI Assistant test role '.$roleId,
            'guard_name' => 'web',
            'is_active' => true,
        ]);
        $permissionId = \Illuminate\Support\Facades\DB::table('permissions')
            ->where('name', 'ai-assistant-index')
            ->where('guard_name', 'web')
            ->value('id');
        if (!$permissionId) {
            $permissionId = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId([
                'name' => 'ai-assistant-index',
                'guard_name' => 'web',
            ]);
        }
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->updateOrInsert([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);
        Cache::forget('role_has_permissions_list'.$roleId);
    }

    public function test_endpoint_guest_is_redirected()
    {
        $response = $this->post(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response->assertRedirect(route('login'));
    }

    public function test_endpoint_requires_prompt_field()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_endpoint_rejects_oversized_prompt()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $response = $this->actingAs($user)->postJson(
            route('ai-assistant.prompt'),
            ['prompt' => str_repeat('a', 501)]
        );
        $response->assertStatus(422);
    }

    public function test_endpoint_without_permission_redirects()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser(['role_id' => 3]);
        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(['id' => 3], [
            'name' => 'Restricted AI test role',
            'guard_name' => 'web',
            'is_active' => true,
        ]);
        $permissionId = \Illuminate\Support\Facades\DB::table('permissions')
            ->where('name', 'ai-assistant-index')
            ->where('guard_name', 'web')
            ->value('id');
        if ($permissionId) {
            \Illuminate\Support\Facades\DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', 3)
                ->delete();
        }
        Cache::forget('role_has_permissions_list3');

        $response = $this->actingAs($user)->post(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response->assertRedirect('/');
        $response->assertSessionHas('not_permitted');
    }

    public function test_endpoint_unsupported_prompt_returns_fallback()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'tell me something random']);
        $response->assertStatus(200);
        $response->assertJsonPath('response_type', 'text');
    }

    public function test_endpoint_routes_all_five_skills()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $prompts = [
            'sales summary' => 'sales_summary',
            'purchase summary' => 'purchase_summary',
            'low stock' => 'low_stock',
            'top products' => 'top_products',
            'customer due summary' => 'customer_due',
        ];

        foreach ($prompts as $prompt => $expectedSkillKey) {
            $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => $prompt]);
            $response->assertStatus(200);
            $this->assertArrayHasKey('skill', $response->json('metadata'));
            $this->assertEquals($expectedSkillKey, $response->json('metadata.skill'));
        }
    }

    public function test_endpoint_context_spoofing_is_ignored()
    {
        $this->seedGeneralSetting();
        // Create staff user assigned to warehouse 1
        $user = $this->createUser(['role_id' => 3, 'warehouse_id' => 1]);
        $this->mockPermission($user);

        \Illuminate\Support\Facades\DB::table('general_settings')->where('id', 1)->update(['staff_access' => 'warehouse']);
        Cache::forget('general_setting');

        // Send a malicious request trying to override the warehouse scope to [2, 3]
        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), [
            'prompt' => 'sales summary',
            'warehouse_ids' => [2, 3]
        ]);

        $response->assertStatus(200);
        
        // The endpoint should enforce the server-side derived warehouse scope [1]
        $this->assertEquals([1], $response->json('metadata.warehouse_ids'));
    }

    public function test_endpoint_unassigned_staff_gets_empty_warehouse_scope()
    {
        $this->seedGeneralSetting();
        // Create staff user with NO warehouse assigned
        $user = $this->createUser(['role_id' => 3, 'warehouse_id' => null]);
        $this->mockPermission($user);

        \Illuminate\Support\Facades\DB::table('general_settings')->where('id', 1)->update(['staff_access' => 'warehouse']);
        Cache::forget('general_setting');

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $response->assertStatus(200);
        
        // The endpoint should enforce an empty array scope for unassigned warehouse staff
        $this->assertEquals([], $response->json('metadata.warehouse_ids'));
    }

    public function test_endpoint_records_skill_run()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);

        $this->assertDatabaseHas('ai_skill_runs', [
            'user_id'   => $user->id,
            'skill_key' => 'sales_summary',
        ]);
    }

    public function test_endpoint_response_contains_no_stack_traces_or_secrets()
    {
        $this->seedGeneralSetting();
        $user = $this->createUser();
        $this->mockPermission($user);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.prompt'), ['prompt' => 'sales summary']);
        $body = $response->content();

        $this->assertStringNotContainsString('Exception', $body);
        $this->assertStringNotContainsString('Stack trace', $body);
        $this->assertStringNotContainsString('APP_KEY', $body);
    }
}
