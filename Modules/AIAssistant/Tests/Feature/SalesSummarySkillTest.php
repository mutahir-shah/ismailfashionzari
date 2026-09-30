<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AIAssistant\Skills\SalesSummarySkill;
use Modules\AIAssistant\DTO\AssistantMessageData;
use Modules\AIAssistant\DTO\AssistantContextData;
use Modules\AIAssistant\Services\SkillRegistry;
use Modules\AIAssistant\Services\AssistantOrchestrator;
use Modules\AIAssistant\Contracts\AssistantSkillRunRecorder;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Support\Carbon;

class SalesSummarySkillTest extends TestCase
{
    use RefreshDatabase;

    private SalesSummarySkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new SalesSummarySkill();
    }

    public function test_skill_metadata_and_stable_key()
    {
        $this->assertEquals('sales_summary', $this->skill->key());
        $this->assertEquals('Sales Summary', $this->skill->name());
        $this->assertNotEmpty($this->skill->description());
        $this->assertIsArray($this->skill->examples());
    }

    public function test_every_supported_prompt_resolves()
    {
        $prompts = [
            'show today\'s sales',
            'today sales',
            'sales today',
            'sales summary',
            'sales summary today',
        ];

        foreach ($prompts as $prompt) {
            $msg = new AssistantMessageData('user', $prompt);
            $this->assertTrue($this->skill->canHandle($msg), "Prompt '{$prompt}' should resolve.");
        }
    }

    public function test_case_whitespace_and_punctuation_tolerance()
    {
        $prompts = [
            '  SHOW today\'s SALES!!!  ',
            'Today Sales...',
            'Sales Today?',
            '  sales   summary   today  ',
        ];

        foreach ($prompts as $prompt) {
            $msg = new AssistantMessageData('user', $prompt);
            $this->assertTrue($this->skill->canHandle($msg), "Prompt '{$prompt}' should resolve.");
        }
    }

    public function test_unrelated_prompts_do_not_resolve()
    {
        $prompts = [
            'show today\'s purchases',
            'what is due today',
            'show top products',
            'stock levels',
            'sales tomorrow',
        ];

        foreach ($prompts as $prompt) {
            $msg = new AssistantMessageData('user', $prompt);
            $this->assertFalse($this->skill->canHandle($msg), "Prompt '{$prompt}' should NOT resolve.");
        }
    }

    private function setupBaseData()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'phone' => '123456789',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
            'is_deleted' => false,
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Test WH ' . uniqid(),
            'phone' => '123456',
            'email' => 'wh' . uniqid() . '@example.com',
            'address' => 'Test address',
            'is_active' => true,
        ]);
        $biller = Biller::create([
            'name' => 'Test Biller ' . uniqid(),
            'image' => 'biller.png',
            'company_name' => 'Test Co',
            'vat_number' => '123',
            'email' => 'biller' . uniqid() . '@example.com',
            'phone_number' => '123',
            'address' => 'Test',
            'city' => 'Test',
            'country' => 'Test',
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_group_id' => 1,
            'name' => 'Test Cust ' . uniqid(),
            'company_name' => 'Test Co',
            'email' => 'cust' . uniqid() . '@example.com',
            'phone_number' => '123',
            'address' => 'Test',
            'city' => 'Test',
            'is_active' => true,
        ]);
        return [$user, $warehouse, $biller, $customer];
    }

    private function createSale($data)
    {
        return Sale::create(array_merge([
            'reference_no' => 'sr-' . uniqid(),
            'user_id' => 1,
            'customer_id' => 1,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'grand_total' => 100,
            'paid_amount' => 100,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 4, // Paid
            'sale_status' => 1,    // Completed
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $data));
    }

    public function test_correct_totals_for_today()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();

        $this->createSale(['total_qty' => 2, 'grand_total' => 200, 'paid_amount' => 200, 'warehouse_id' => $warehouse->id]);
        $this->createSale(['total_qty' => 3, 'grand_total' => 300, 'paid_amount' => 300, 'warehouse_id' => $warehouse->id]);

        $context = new AssistantContextData();
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(2, $response->cards[0]['value']); // Transactions
        $this->assertEquals(5, $response->cards[1]['value']); // Items Sold
        $this->assertEquals(500, $response->cards[2]['value']); // Gross Total
        $this->assertEquals(500, $response->cards[3]['value']); // Paid Amount
        $this->assertEquals(0, $response->cards[4]['value']); // Due Amount
    }

    public function test_yesterday_future_sales_excluded()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();

        // Yesterday
        $this->createSale(['created_at' => Carbon::yesterday(), 'grand_total' => 100]);
        // Future
        $this->createSale(['created_at' => Carbon::tomorrow(), 'grand_total' => 100]);
        // Today
        $this->createSale(['created_at' => Carbon::today(), 'grand_total' => 50, 'paid_amount' => 50]);

        $context = new AssistantContextData();
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(1, $response->cards[0]['value']); // Transactions
        $this->assertEquals(50, $response->cards[2]['value']); // Gross Total
    }

    public function test_drafts_soft_deleted_and_opening_balance_excluded()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();

        // Valid
        $this->createSale(['grand_total' => 100, 'paid_amount' => 100]);
        // Draft
        $this->createSale(['grand_total' => 200, 'sale_status' => 3]);
        // Soft deleted
        $sale = $this->createSale(['grand_total' => 300]);
        $sale->delete();
        // Opening balance
        $this->createSale(['grand_total' => 400, 'sale_type' => 'Opening balance']);

        $context = new AssistantContextData();
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(1, $response->cards[0]['value']);
        $this->assertEquals(100, $response->cards[2]['value']);
    }

    public function test_paid_and_partial_due_sales_produce_correct_aggregates()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();

        // Paid: 100, Due: 0
        $this->createSale(['grand_total' => 100, 'paid_amount' => 100]);
        // Partial: 150, Paid: 50, Due: 100
        $this->createSale(['grand_total' => 150, 'paid_amount' => 50, 'payment_status' => 3]);
        // Unpaid: 200, Paid: 0, Due: 200
        $this->createSale(['grand_total' => 200, 'paid_amount' => 0, 'payment_status' => 1]);

        $context = new AssistantContextData();
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(3, $response->cards[0]['value']);
        $this->assertEquals(450, $response->cards[2]['value']); // Gross
        $this->assertEquals(150, $response->cards[3]['value']); // Paid
        $this->assertEquals(300, $response->cards[4]['value']); // Due
    }

    public function test_allowed_warehouse_ids_restrict_results()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();
        
        $warehouse2 = Warehouse::create([
            'name' => 'Test WH ' . uniqid(),
            'phone' => '123456',
            'email' => 'wh2' . uniqid() . '@example.com',
            'address' => 'Test address',
            'is_active' => true,
        ]);

        // WH 1
        $this->createSale(['warehouse_id' => $warehouse->id, 'grand_total' => 100, 'paid_amount' => 100]);
        // WH 2
        $this->createSale(['warehouse_id' => $warehouse2->id, 'grand_total' => 200, 'paid_amount' => 200]);

        $context = new AssistantContextData(businessContext: ['warehouse_ids' => [$warehouse->id]]);
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(1, $response->cards[0]['value']);
        $this->assertEquals(100, $response->cards[2]['value']);
    }

    public function test_explicit_empty_warehouse_access_returns_zero_totals()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();
        $this->createSale(['warehouse_id' => $warehouse->id, 'grand_total' => 100, 'paid_amount' => 100]);

        $context = new AssistantContextData(businessContext: ['warehouse_ids' => []]);
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(0, $response->cards[0]['value']);
        $this->assertEquals(0, $response->cards[2]['value']);
    }

    public function test_missing_warehouse_restriction_preserves_admin_compatibility()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();
        $warehouse2 = Warehouse::create([
            'name' => 'Test WH ' . uniqid(),
            'phone' => '123456',
            'email' => 'wh2' . uniqid() . '@example.com',
            'address' => 'Test address',
            'is_active' => true,
        ]);

        $this->createSale(['warehouse_id' => $warehouse->id, 'grand_total' => 100, 'paid_amount' => 100]);
        $this->createSale(['warehouse_id' => $warehouse2->id, 'grand_total' => 200, 'paid_amount' => 200]);

        // No warehouse_ids array at all
        $context = new AssistantContextData(businessContext: []);
        $response = $this->skill->handle(new AssistantMessageData('user', 'sales summary'), $context);

        $this->assertEquals(2, $response->cards[0]['value']);
        $this->assertEquals(300, $response->cards[2]['value']);
    }

    public function test_container_registry_contains_exactly_one_sales_summary_registration()
    {
        /** @var SkillRegistry $registry */
        $registry = app(SkillRegistry::class);
        $skills = $registry->all();
        
        $salesSummarySkills = array_filter($skills, fn($s) => $s->key() === 'sales_summary');
        
        $this->assertCount(1, $salesSummarySkills);
        $this->assertInstanceOf(SalesSummarySkill::class, array_values($salesSummarySkills)[0]);
    }

    public function test_container_resolved_orchestrator_executes_skill_and_records_run()
    {
        list($user, $warehouse, $biller, $customer) = $this->setupBaseData();
        $this->createSale(['warehouse_id' => $warehouse->id, 'grand_total' => 100, 'paid_amount' => 100]);
        
        /** @var AssistantOrchestrator $orchestrator */
        $orchestrator = app(AssistantOrchestrator::class);
        
        $message = new AssistantMessageData('user', 'show todays sales');
        $context = new AssistantContextData(userId: $user->id, businessContext: []);

        $response = $orchestrator->executeStructured($message, $context);
        
        $this->assertEquals('card', $response->responseType);
        $this->assertEquals(100, $response->cards[2]['value']); // Gross total
        $this->assertEquals('sales_summary', $response->metadata['skill']);

        // Check if run was recorded
        $this->assertDatabaseHas('ai_skill_runs', [
            'user_id' => $user->id,
            'skill_key' => 'sales_summary',
        ]);
    }
}
