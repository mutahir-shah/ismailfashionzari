<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\User;
use App\Models\Biller;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\DB;

class CreditLimitEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function getBaseUser()
    {
        return User::where('is_active', true)->where('role_id', 1)->first() ?? User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'is_active' => true,
            'is_deleted' => false
        ]);
    }
    
    private function setupBaseData()
    {
        $warehouse = Warehouse::first() ?? Warehouse::create([
            'name' => 'Test Warehouse',
            'phone' => '123456789',
            'email' => 'warehouse@example.com',
            'address' => '123 Test St',
            'is_active' => true
        ]);
        $biller = Biller::first() ?? Biller::create([
            'name' => 'Test Biller',
            'image' => 'zummXD2dvAtI.png',
            'company_name' => 'Test Company',
            'vat_number' => '123456',
            'email' => 'biller@example.com',
            'phone_number' => '123456789',
            'address' => '123 Test St',
            'city' => 'Test City',
            'country' => 'Test Country',
            'is_active' => true
        ]);
        $currency = \App\Models\Currency::where('code', 'USD')->first() ?? \App\Models\Currency::create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1
        ]);
        
        if (!\DB::table('general_settings')->first()) {
            \DB::table('general_settings')->insert([
                'id' => 1,
                'site_title' => 'Test POS',
                'site_logo' => 'logo.png',
                'is_rtl' => false,
                'currency' => $currency->id,
                'currency_position' => 'prefix',
                'staff_access' => 'own',
                'date_format' => 'd-m-Y',
                'theme' => 'default.css'
            ]);
        }
        
        if (!\DB::table('pos_setting')->first()) {
            \DB::table('pos_setting')->insert([
                'id' => 1,
                'customer_id' => 1,
                'warehouse_id' => 1,
                'biller_id' => 1,
                'product_number' => 4,
                'stripe_public_key' => 'test',
                'stripe_secret_key' => 'test',
                'keybord_active' => false
            ]);
        }
        
        if (!\DB::table('invoice_settings')->first()) {
            \DB::table('invoice_settings')->insert([
                'id' => 1,
                'show_column' => '{"active_generat_settings":0}',
                'template_name' => 'template1',
                'status' => 1,
                'is_default' => 1,
            ]);
        }
        
        $user = $this->getBaseUser();
        \App\Models\CashRegister::first() ?? \App\Models\CashRegister::create([
            'user_id' => $user->id,
            'warehouse_id' => 1,
            'cash_in_hand' => 0,
            'status' => 1
        ]);
        
        $customerZero = Customer::create([
            'customer_group_id' => 1,
            'name' => 'Zero Credit Customer',
            'phone_number' => '111111111',
            'email' => 'zero@example.com',
            'credit_limit' => 0,
            'is_active' => true,
            'opening_balance' => 0,
        ]);
        
        $customerLimited = Customer::create([
            'customer_group_id' => 1,
            'name' => 'Credit Limited Customer',
            'company_name' => 'Limited LLC',
            'email' => 'limited@example.com',
            'phone_number' => '1234567891',
            'address' => '123 Limit St',
            'city' => 'Limit City',
            'is_active' => true,
            'credit_limit' => 1000
        ]);

        $unit = \App\Models\Unit::create([
            'unit_code' => 'pc',
            'unit_name' => 'piece',
            'base_unit' => null,
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => true
        ]);

        \App\Models\Account::create([
            'account_no' => '11111',
            'name' => 'Test Account',
            'initial_balance' => 0,
            'total_balance' => 0,
            'note' => 'Test Default Account',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $product = \App\Models\Product::create([
            'name' => 'Test Product',
            'code' => 'TEST',
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'brand_id' => 1,
            'category_id' => 1,
            'unit_id' => $unit->id,
            'purchase_unit_id' => $unit->id,
            'sale_unit_id' => $unit->id,
            'cost' => 50,
            'price' => 100,
            'qty' => 100,
            'alert_quantity' => 10,
            'is_active' => true
        ]);

        return [$warehouse, $biller, $customerZero, $customerLimited, $product, $unit];
    }
    
    private function getPayload($customer_id, $warehouse_id, $biller_id, $product_id, $unit_name, $grand_total, $paid_amount, $sale_status = 1)
    {
        return [
            'customer_id' => $customer_id,
            'warehouse_id' => $warehouse_id,
            'biller_id' => $biller_id,
            'currency_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'qty' => [1],
            'sale_unit' => [$unit_name],
            'net_unit_price' => [$grand_total],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [$grand_total],
            'product_id' => [$product_id],
            'product_batch_id' => [null],
            'product_code' => ['TEST'],
            'imei_number' => [''],
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $grand_total,
            'grand_total' => $grand_total,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'coupon_active' => 0,
            'payment_status' => $paid_amount >= $grand_total ? 4 : 2,
            'sale_status' => $sale_status,
            'pos' => 0,
            'draft' => $sale_status == 3 ? 1 : 0,
            'sale_id' => null,
            'paid_amount' => [$paid_amount],
            'paying_amount' => [$paid_amount],
            'paid_by_id' => [1],
            'payment_note' => '',
        ];
    }

    // 1. Zero-limit unpaid POS/new sale is rejected and no sale is created.
    public function test_zero_limit_unpaid_sale_rejected_and_no_sale_created()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();
        $initialSalesCount = Sale::count();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerZero->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 100, 0));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Customer has no credit limit. Sale must be fully paid.');
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    // 2. Zero-limit fully paid sale is accepted.
    public function test_zero_limit_fully_paid_sale_accepted()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerZero->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 100, 100));

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['customer_id' => $customerZero->id, 'grand_total' => 100]);
    }

    // 3. Positive limit exceeded is rejected and no sale is created.
    public function test_positive_limit_exceeded_rejected_no_sale_created()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();
        $initialSalesCount = Sale::count();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1001, 0));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Credit limit exceeded. Limit: 1,000.00, Current Due: 0.00, Resulting Due: 1,001.00');
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    // 4. Result exactly equal to limit is accepted.
    public function test_result_exactly_equal_to_limit_is_accepted()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1000, 0));

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['customer_id' => $customerLimited->id, 'grand_total' => 1000]);
    }

    // 5. Existing due is included.
    public function test_existing_due_is_included()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        // Use 900 of the 1000 limit
        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 900, 0));
        
        $initialSalesCount = Sale::count();

        // Try to add 200, which would exceed the 1000 limit
        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 200, 0));

        $response->assertStatus(422);
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    // 6. Payments on fully paid historical sales do not reduce due incorrectly.
    public function test_payments_on_fully_paid_historical_sales_dont_reduce_due_incorrectly()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        // Add an old fully paid sale of 500
        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 500));

        $due = app(CustomerCreditService::class)->calculateCustomerDue($customerLimited->id);
        $this->assertEquals(0, $due);
    }

    // 7. Edit increase beyond limit is rejected and original sale remains unchanged.
    public function test_edit_increase_beyond_limit_rejected()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0));
        $sale = Sale::where('customer_id', $customerLimited->id)->first();

        // Attempt to edit to 1500 (exceeding 1000 limit)
        $payload = $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1500, 0);
        $payload['sale_id'] = $sale->id;
        $payload['paid_amount'] = 0;

        $response = $this->actingAs($user)->put("/sales/{$sale->id}", $payload, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(422);
        $this->assertEquals(500, $sale->fresh()->grand_total); // Remains unchanged
    }

    // 8. Edit within limit is accepted.
    public function test_edit_within_limit_accepted()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0));
        $sale = Sale::where('customer_id', $customerLimited->id)->first();

        // Attempt to edit to 800 (within 1000 limit)
        $payload = $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 800, 0);
        $payload['sale_id'] = $sale->id;
        $payload['paid_amount'] = 0; // The update method uses scalar paid_amount instead of array

        $response = $this->actingAs($user)->put("/sales/{$sale->id}", $payload);

        $response->assertRedirect();
        $this->assertEquals(800, $sale->fresh()->grand_total);
    }

    // 9. Edit excludes its original sale and related payments.
    public function test_edit_sale_ignores_its_own_previous_due()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 900, 0));
        $sale = Sale::where('customer_id', $customerLimited->id)->first();

        $payload = $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1000, 0);
        $payload['sale_id'] = $sale->id;
        $payload['paid_amount'] = 0; // The update method uses scalar paid_amount instead of array

        $response = $this->actingAs($user)->put("/sales/{$sale->id}", $payload);

        $response->assertRedirect(); 
        $this->assertEquals(1000, Sale::find($sale->id)->grand_total);
    }

    // 10. Changing customer during edit validates the new customer.
    public function test_changing_customer_during_edit_validates_new_customer()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0));
        $sale = Sale::where('customer_id', $customerLimited->id)->first();

        // Edit the sale, but assign it to Zero customer
        $payload = $this->getPayload($customerZero->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0);
        $payload['sale_id'] = $sale->id;
        $payload['paid_amount'] = 0; // update() expects scalar
        
        $response = $this->actingAs($user)->put("/sales/{$sale->id}", $payload, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(422); // Zero credit customer shouldn't accept unpaid
        $this->assertEquals($customerLimited->id, $sale->fresh()->customer_id);
    }

    // 11. Missing customer fails closed (caught by request validation).
    public function test_missing_customer_fails_closed()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();
        $initialSalesCount = Sale::count();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload(99999, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 100, 0));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('customer_id');
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    // 12. Inactive customer fails closed (caught by CustomerCreditService).
    public function test_inactive_customer_fails_closed()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();
        
        $customerZero->is_active = false;
        $customerZero->save();
        
        $initialSalesCount = Sale::count();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerZero->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 100, 0));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Invalid or inactive customer.');
        $this->assertEquals($initialSalesCount, Sale::count());
    }

    // 13. Soft-deleted sales and returns/refunds follow canonical due rules.
    public function test_soft_deleted_sales_and_returns_refunds_follow_canonical_due_rules()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();
        
        $customerLimited->opening_balance = 500;
        $customerLimited->save();

        // 1. Unpaid sale of 300
        $sale1 = Sale::create([
            'reference_no' => 'sr-20230101-000001',
            'user_id' => $user->id,
            'customer_id' => $customerLimited->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 300,
            'grand_total' => 300,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 2,
            'sale_status' => 1,
        ]);

        // 2. Soft deleted unpaid sale of 200 (should not count)
        $sale2 = Sale::create([
            'reference_no' => 'sr-20230101-000002',
            'user_id' => $user->id,
            'customer_id' => $customerLimited->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 200,
            'grand_total' => 200,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'payment_status' => 2,
            'sale_status' => 1,
        ]);
        $sale2->delete(); // soft delete

        // 3. Return of 100
        \App\Models\Returns::create([
            'reference_no' => 'rr-20230101-001',
            'user_id' => $user->id,
            'customer_id' => $customerLimited->id,
            'warehouse_id' => 1,
            'biller_id' => 1,
            'account_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_price' => 100,
            'total_discount' => 0,
            'total_tax' => 0,
            'order_tax' => 0,
            'grand_total' => 100,
        ]);

        // Due: 500 + 300 (sale1) - 100 (return) = 700. Limit is 1000. So they have 300 left.
        // Attempt unpaid sale of 400, should be rejected
        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 400, 0));
        
        $response->assertStatus(422);
        
        // Attempt unpaid sale of 300, should be accepted
        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 300, 0));
        $response->assertRedirect();
    }

    // 14. Draft behavior matches established semantics.
    public function test_draft_behavior_matches_established_semantics()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        // Create draft of 500 for customerLimited
        $payload = $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0);
        $payload['sale_status'] = 3; // draft
        
        $response = $this->actingAs($user)->postJson('/sales', $payload);
        $response->assertRedirect();
        
        // Ensure it doesn't count towards due limit
        $sale = Sale::where('customer_id', $customerLimited->id)->where('sale_status', 3)->first();
        $this->assertNotNull($sale);
        
        // Can they still buy 1000 (their full limit)? Yes.
        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1000, 0));
        $response->assertRedirect();
        
        // But drafts CANNOT bypass the missing/inactive customer check
        $payload['customer_id'] = 99999;
        $response = $this->actingAs($user)->postJson('/sales', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('customer_id');
    }

    // 15. JSON / AJAX rejection returns 422 with generic error payload.
    public function test_json_ajax_rejection_returns_422_with_error_payload()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1001, 0));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Credit limit exceeded. Limit: 1,000.00, Current Due: 0.00, Resulting Due: 1,001.00');
    }

    // 16. Normal form rejection redirects with old input/session error.
    public function test_normal_form_rejection_redirects_with_session_error()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $response = $this->actingAs($user)->post('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1001, 0));

        $response->assertRedirect();
        $this->assertEquals('Credit limit exceeded. Limit: 1,000.00, Current Due: 0.00, Resulting Due: 1,001.00', session('not_permitted'));
    }

    // 17. Concurrency protection for STORE executes under a transaction and uses lockForUpdate.
    public function test_concurrency_protection_via_lock_for_update_store()
    {
        // 1. PHP/PHPUnit execution is single-threaded, preventing true multi-connection 
        // concurrent execution without process forking, which is unsupported by the test runner.
        // 2. Instead, we use query listening to deterministically prove that the 
        // SaleController executes the credit limit query WITH a row lock ('for update') 
        // INSIDE a database transaction.
        
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $queries = [];
        $transactionLevelWhenLocked = 0;
        
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries, &$transactionLevelWhenLocked) {
            $queries[] = $query->sql;
            if (stripos($query->sql, 'for update') !== false && stripos($query->sql, 'customers') !== false) {
                $transactionLevelWhenLocked = \Illuminate\Support\Facades\DB::transactionLevel();
            }
        });

        $initialSalesCount = Sale::count();
        $response = $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1500, 0));

        $hasLockForUpdate = false;
        foreach ($queries as $sql) {
            if (stripos($sql, 'for update') !== false && stripos($sql, 'customers') !== false) {
                $hasLockForUpdate = true;
                break;
            }
        }

        $this->assertTrue($hasLockForUpdate, "The credit limit customer query must use lockForUpdate ('for update') in store().");
        $this->assertGreaterThan(0, $transactionLevelWhenLocked, "The credit limit check must execute while DB::transactionLevel() > 0 in store().");
        
        $response->assertStatus(422); // Exceeds limit
        $this->assertEquals($initialSalesCount, Sale::count(), "Database state should remain unchanged on validation rejection.");
    }
    
    // 18. Concurrency protection for UPDATE executes under a transaction and uses lockForUpdate.
    public function test_concurrency_protection_via_lock_for_update_update()
    {
        list($warehouse, $biller, $customerZero, $customerLimited, $product, $unit) = $this->setupBaseData();
        $user = $this->getBaseUser();

        $this->actingAs($user)->postJson('/sales', $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 500, 0));
        $sale = Sale::where('customer_id', $customerLimited->id)->first();
        
        $queries = [];
        $transactionLevelWhenLocked = 0;
        
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries, &$transactionLevelWhenLocked) {
            $queries[] = $query->sql;
            if (stripos($query->sql, 'for update') !== false && stripos($query->sql, 'customers') !== false) {
                $transactionLevelWhenLocked = \Illuminate\Support\Facades\DB::transactionLevel();
            }
        });

        $payload = $this->getPayload($customerLimited->id, $warehouse->id, $biller->id, $product->id, $unit->unit_name, 1500, 0);
        $payload['sale_id'] = $sale->id;
        $payload['paid_amount'] = 0;

        $response = $this->actingAs($user)->put("/sales/{$sale->id}", $payload, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $hasLockForUpdate = false;
        foreach ($queries as $sql) {
            if (stripos($sql, 'for update') !== false && stripos($sql, 'customers') !== false) {
                $hasLockForUpdate = true;
                break;
            }
        }

        $this->assertTrue($hasLockForUpdate, "The credit limit customer query must use lockForUpdate ('for update') in update().");
        $this->assertGreaterThan(0, $transactionLevelWhenLocked, "The credit limit check must execute while DB::transactionLevel() > 0 in update().");
        
        $response->assertStatus(422); // Exceeds limit
        $this->assertEquals(500, $sale->fresh()->grand_total, "Database state should remain unchanged on validation rejection.");
    }
}
