<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use Modules\Repair\Entities\ServiceJob;
use Modules\Repair\Entities\ServiceJobItem;

class ModuleAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (AccountingAccount::count() == 0) {
            $acc1 = AccountingAccount::create(['code' => '1300', 'name' => 'Cash', 'account_type' => 'asset', 'is_active' => true]);
            $acc2 = AccountingAccount::create(['code' => '4100', 'name' => 'Sales Revenue', 'account_type' => 'revenue', 'is_active' => true]);
            $acc3 = AccountingAccount::create(['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'is_active' => true]);
            \App\Models\AccountMapping::create(['mapped_type' => 'cash', 'mapped_id' => 0, 'accounting_account_id' => $acc1->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'sales_revenue', 'mapped_id' => 0, 'accounting_account_id' => $acc2->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'accounts_receivable', 'mapped_id' => 0, 'accounting_account_id' => $acc3->id]);
            \App\Models\AccountingPeriod::create(['name' => 'Current', 'start_date' => '2000-01-01', 'end_date' => '2099-12-31', 'is_closed' => false]);
            \App\Models\Account::create(['id' => 1, 'account_no' => '123', 'name' => 'Cash', 'initial_balance' => 0, 'total_balance' => 0, 'is_default' => 1, 'is_active' => 1]);
        }
        
        $this->user = User::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'password', 'role_id' => 1, 'is_active' => true, 'phone' => '123456789', 'company_name' => 'Test Co', 'is_deleted' => false]);
        $this->warehouse = Warehouse::create(['name' => 'W1', 'phone' => '123', 'email' => 'w@w.com', 'address' => 'Addr', 'is_active' => true, 'is_deleted' => false]);
        $this->biller = Biller::create(['name' => 'B1', 'company_name' => 'C1', 'vat_number' => '1', 'email' => 'b@b.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true, 'is_deleted' => false]);
        $this->customer = Customer::create(['customer_group_id' => 1, 'name' => 'C1', 'company_name' => 'C1', 'email' => 'c@c.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true, 'is_deleted' => false]);
        $this->product = Product::create(['name' => 'P1', 'code' => 'P1', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 100, 'qty' => 100, 'alert_quantity' => 1, 'is_active' => true]);
        \App\Models\Product_Warehouse::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 100]);
        config(['app.key' => 'base64:XG8o7y7n/83xZXZ5O7Y3A1Qc/58e2o+M3qjDqQ58e6s=']);

        \App\Models\AccountingConfig::updateOrCreate(['id' => 1], ['enabled' => true, 'start_date' => '2000-01-01']);

        \App\Models\Currency::updateOrCreate(['id' => 1], ['name' => 'US Dollar', 'code' => 'USD', 'exchange_rate' => 1]);

        if (!DB::table('pos_setting')->first()) {
            DB::table('pos_setting')->insert([
                'id' => 1,
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'biller_id' => $this->biller->id,
                'product_number' => 4,
                'stripe_public_key' => 'test',
                'stripe_secret_key' => 'test',
                'keybord_active' => 0,
            ]);
        }

        if (!DB::table('general_settings')->first()) {
            DB::table('general_settings')->insert([
                'site_title' => 'Test',
                'site_logo' => 'test.png',
                'currency' => 1,
                'currency_position' => 'prefix',
                'modules' => 'ecommerce,repair,hrm',
                'expiry_date' => '2099-12-31',
                'developed_by' => 'Test',
                'decimal' => 2,
                'staff_access' => 'all',
                'date_format' => 'd-m-Y',
                'timezone' => 'UTC',
                'theme' => 'default'
            ]);
        }
    }

    public function test_ecommerce_order_creates_ar_journal()
    {
        $payload = [
            'currency' => 1,
            'billing_name' => 'Test Bill',
            'billing_email' => 'bill@test.com',
            'billing_phone' => '1234',
            'billing_address' => '123',
            'billing_city' => 'C',
            'billing_state' => 'S',
            'billing_zip' => 'Z',
            'billing_country' => 'C',
            'shipping_name' => 'Test Ship',
            'shipping_email' => 'ship@test.com',
            'shipping_phone' => '1234',
            'shipping_address' => '123',
            'shipping_city' => 'C',
            'shipping_state' => 'S',
            'shipping_zip' => 'Z',
            'shipping_country' => 'C',
            'warehouse_id' => $this->warehouse->id,
            'biller_id' => $this->biller->id,
            'payment_mode' => 'Cash on Delivery',
            'sub_total' => 100,
            'grand_total' => 100,
            'item' => 1,
            'total_qty' => 1,
            'shipping_cost' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
        ];
        
        session(['cart' => [
            [
                'id' => $this->product->id,
                'qty' => 1,
                'unit_price' => 100,
                'sale_unit_id' => 1,
                'total_price' => 100,
                'variant' => 0
            ]
        ]]);

        $this->withoutExceptionHandling();
        $this->actingAs($this->user);
        $this->post('/place-order', $payload);

        $sale = Sale::where('reference_no', 'like', 'ecomm-%')->first();
        $this->assertNotNull($sale);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'event_type' => 'ecommerce_order_created'
        ]);
        
        // Ensure no payment journal is created for COD yet
        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => Payment::class,
            'event_type' => 'ecommerce_payment_confirmed'
        ]);
    }

    public function test_repair_sync_to_sale_journals()
    {
        // 1. Create a job
        $job = ServiceJob::create([
            'reference_no' => 'REP-1',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'service_type' => 'device',
            'title' => 'Fix phone',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $this->user->id,
            'total_amount' => 100,
            'due_amount' => 100,
            'paid_amount' => 0
        ]);

        ServiceJobItem::create([
            'service_job_id' => $job->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        // 2. Sync to sale
        $sale = $job->syncToSale();

        // 3. Assert journal created
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'event_type' => 'repair_invoice_created'
        ]);

        $firstEntryCount = \App\Models\JournalEntry::where('source_id', $sale->id)->count();

        // 4. Sync again without changes
        $job->syncToSale();

        // 5. Assert no new journals
        $this->assertEquals($firstEntryCount, \App\Models\JournalEntry::where('source_id', $sale->id)->count());

        // 6. Sync with changes
        $job->service_charge = 50;
        $job->recalculateTotals(); // calls syncToSale internally

        // 7. Assert reversal and updated journal
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'event_type' => 'repair_invoice_created_updated_reversal'
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'event_type' => 'repair_invoice_updated'
        ]);
    }

    public function test_unverified_paypal_callback_does_not_post_cash_journal()
    {
        [$sale, $payment] = $this->createPendingGatewayPayment('Paypal');
        $this->putGateway('PayPal', 'Client_ID,Secret;client,secret');

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response(['status' => 'APPROVED']),
        ]);

        $this->withSession(['sale_id' => $sale->id])
            ->post('/paypal-payment', ['payment_id' => $payment->id, 'transaction_id' => 'PAYPAL-PENDING'])
            ->assertStatus(422);

        $this->assertEquals(1, $sale->fresh()->payment_status);
        $this->assertFalse($this->paymentJournalExists($payment));
    }

    public function test_verified_paypal_callback_posts_once()
    {
        [$sale, $payment] = $this->createPendingGatewayPayment('Paypal');
        $this->putGateway('PayPal', 'Client_ID,Secret;client,secret');

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'amount' => ['value' => '100.00', 'currency_code' => 'USD'],
                    ]]],
                ]],
            ]),
        ]);

        $payload = ['payment_id' => $payment->id, 'transaction_id' => 'PAYPAL-COMPLETE'];
        $this->withSession(['sale_id' => $sale->id])->post('/paypal-payment', $payload)->assertOk();
        $this->withSession(['sale_id' => $sale->id])->post('/paypal-payment', $payload)->assertOk();

        $this->assertEquals(4, $sale->fresh()->payment_status);
        $this->assertEquals(1, JournalEntry::where([
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'event_type' => 'ecommerce_payment_confirmed',
        ])->count());
    }

    public function test_mollie_return_requires_provider_paid_status()
    {
        [$sale, $payment] = $this->createPendingGatewayPayment('Mollie');
        $this->putGateway('Mollie', 'api_key;test-key');

        $mollieStatus = 'pending';
        Http::fake(function () use (&$mollieStatus, $sale, $payment) {
            return Http::response([
                'id' => 'mollie-test',
                'status' => $mollieStatus,
                'amount' => ['value' => '100.00', 'currency' => 'USD'],
                'metadata' => ['sale_id' => $sale->id, 'payment_id' => $payment->id],
            ]);
        });

        $session = [
            'mollie_provider_payment_id' => 'mollie-test',
            'mollie_local_payment_id' => $payment->id,
        ];
        $this->withSession($session)->get('/mollie-success')->assertStatus(422);
        $this->assertFalse($this->paymentJournalExists($payment));

        $mollieStatus = 'paid';

        $this->withSession($session)->get('/mollie-success')->assertRedirect();
        $this->assertTrue($this->paymentJournalExists($payment));
    }

    private function createPendingGatewayPayment(string $method): array
    {
        $sale = Sale::create([
            'reference_no' => 'gateway-' . strtolower($method) . '-' . uniqid(),
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'biller_id' => $this->biller->id,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 100,
            'paid_amount' => 0,
            'sale_status' => 2,
            'payment_status' => 1,
            'sale_type' => 'online',
            'currency_id' => 1,
            'exchange_rate' => 1,
        ]);
        $payment = Payment::create([
            'payment_reference' => 'gateway-payment-' . uniqid(),
            'user_id' => $this->user->id,
            'sale_id' => $sale->id,
            'account_id' => 1,
            'amount' => 100,
            'change' => 0,
            'paying_method' => $method,
            'exchange_rate' => 1,
        ]);

        return [$sale, $payment];
    }

    private function putGateway(string $name, string $details): void
    {
        DB::table('external_services')->updateOrInsert(
            ['name' => $name],
            [
                'type' => 'payment',
                'details' => $details,
                'module_status' => json_encode(['ecommerce' => true]),
                'active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function paymentJournalExists(Payment $payment): bool
    {
        return JournalEntry::where([
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'event_type' => 'ecommerce_payment_confirmed',
        ])->exists();
    }
}
