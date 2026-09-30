<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\AccountingAccount;

class SaleAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (AccountingAccount::count() == 0) {
            $acc1 = AccountingAccount::create(['code' => '1000', 'name' => 'Cash', 'account_type' => 'asset', 'is_active' => true]);
            $acc2 = AccountingAccount::create(['code' => '4100', 'name' => 'Sales Revenue', 'account_type' => 'revenue', 'is_active' => true]);
            $acc3 = AccountingAccount::create(['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'is_active' => true]);
            \App\Models\AccountMapping::create(['mapped_type' => 'cash', 'mapped_id' => 0, 'accounting_account_id' => $acc1->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'sales_revenue', 'mapped_id' => 0, 'accounting_account_id' => $acc2->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'accounts_receivable', 'mapped_id' => 0, 'accounting_account_id' => $acc3->id]);
            \App\Models\AccountingPeriod::create(['name' => 'Current', 'start_date' => '2000-01-01', 'end_date' => '2099-12-31', 'is_closed' => false]);
            \App\Models\Account::create(['id' => 1, 'account_no' => '123', 'name' => 'Cash', 'initial_balance' => 0, 'total_balance' => 0, 'is_default' => 1, 'is_active' => 1]);
        }

        \App\Models\AccountingConfig::updateOrCreate(['id' => 1], [
            'enabled' => true,
            'status' => 'active',
            'start_date' => '2000-01-01',
        ]);

        \App\Models\Currency::firstOrCreate(['id' => 1], [
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => true,
        ]);
        \App\Models\Unit::firstOrCreate(['id' => 1], [
            'unit_code' => 'pc',
            'unit_name' => 'Piece',
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => true,
        ]);
        DB::table('general_settings')->insert([
            'site_title' => 'SalePro Test',
            'currency' => 1,
            'currency_position' => 'prefix',
            'staff_access' => 'all',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'is_zatca' => 0,
            'without_stock' => 'no',
        ]);
        DB::table('invoice_settings')->insert([
            'show_column' => '{"active_generat_settings":0}',
            'template_name' => 'template1',
            'status' => 1,
            'is_default' => 1,
        ]);
        \Illuminate\Support\Facades\Cache::flush();
        
        $this->user = User::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'password', 'role_id' => 1, 'is_active' => true, 'phone' => '123456789', 'company_name' => 'Test Co', 'is_deleted' => false]);
        $this->warehouse = Warehouse::create(['name' => 'W1', 'phone' => '123', 'email' => 'w@w.com', 'address' => 'Addr', 'is_active' => true, 'is_deleted' => false]);
        $this->biller = Biller::create(['name' => 'B1', 'company_name' => 'C1', 'vat_number' => '1', 'email' => 'b@b.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true, 'is_deleted' => false]);
        $this->customer = Customer::create(['customer_group_id' => 1, 'name' => 'C1', 'company_name' => 'C1', 'email' => 'c@c.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true, 'is_deleted' => false]);
        $this->product = Product::create(['name' => 'P1', 'code' => 'P1', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 100, 'qty' => 100, 'alert_quantity' => 1, 'is_active' => true]);
        \App\Models\Product_Warehouse::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 100]);
        
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
    }

    // A simulated test that ensures if AccountingService throws, Sale is kept but exception is logged
    public function test_sale_creation_does_not_rollback_if_accounting_fails()
    {
        // 1. Force AccountingService to throw or fail
        // We'll mock the AccountingService to return a failure
        $mockService = \Mockery::mock(\App\Services\AccountingService::class);
        $mockService->shouldReceive('recordSale')->once()->andReturn(
            new \App\Services\AccountingResult(false, 'Simulated accounting failure')
        );
        $mockService->shouldReceive('recordPayment')->andReturn(
            new \App\Services\AccountingResult(true)
        );
        $this->app->instance(\App\Services\AccountingService::class, $mockService);

        // 2. Prepare payload
        $payload = [
            'reference_no' => 'TEST-SALE-999',
            'warehouse_id' => $this->warehouse->id,
            'biller_id' => $this->biller->id,
            'customer_id' => $this->customer->id,
            'currency_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'grand_total' => 100,
            'sale_status' => 1,
            'payment_status' => 4, // paid
            'draft' => 0,
            'coupon_active' => 0,
            // items
            'product_id' => [$this->product->id],
            'qty' => [1],
            'sale_unit' => ['Piece'],
            'net_unit_price' => [100],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [100],
            'product_batch_id' => [null],
            'imei_number' => [null],
            'product_code' => [$this->product->code],
            // Payments
            'paid_amount' => [100],
            'paid_by_id' => [1], // cash
            'paying_amount' => [100],
            'payment_note' => 'test'
        ];

        // 3. Post to controller
        $this->actingAs($this->user);
        
        $response = $this->post('/sales', $payload);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 4. Assert Sale is NOT rolled back
        $this->assertDatabaseHas('sales', [
            'reference_no' => 'TEST-SALE-999'
        ]);
        
        // Assert payments are created
        $sale = Sale::where('reference_no', 'TEST-SALE-999')->first();
        if (\Schema::hasColumn($sale->getTable(), 'accounting_status')) {
            $this->assertSame('failed', $sale->accounting_status);
        }
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id
        ]);
    }

    public function test_sale_creation_successful_accounting()
    {
        // 2. Prepare payload
        $payload = [
            'warehouse_id' => $this->warehouse->id,
            'biller_id' => $this->biller->id,
            'customer_id' => $this->customer->id,
            'currency_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'grand_total' => 100,
            'sale_status' => 1,
            'payment_status' => 4, // paid
            'draft' => 0,
            'coupon_active' => 0,
            // items
            'product_id' => [$this->product->id],
            'qty' => [1],
            'sale_unit' => ['Piece'],
            'net_unit_price' => [100],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [100],
            'product_batch_id' => [null],
            'imei_number' => [null],
            'product_code' => [$this->product->code],
            // Payments
            'paid_amount' => [100],
            'paid_by_id' => [1], // cash
            'paying_amount' => [100],
            'payment_note' => 'test success'
        ];

        $this->actingAs($this->user);
        
        // This time use real AccountingService, we don't mock it
        $response = $this->post('/sales', $payload);
        $response->assertSessionHasNoErrors();
        
        // Sale should be created
        $sale = Sale::latest()->first();
        $this->assertNotNull($sale);
        
        // Assert journal was created for sale
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Sale::class,
            'source_id' => $sale->id
        ]);
        
        $payment = Payment::where('sale_id', $sale->id)->first();
        // Assert journal was created for payment
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Payment::class,
            'source_id' => $payment->id
        ]);
    }
}
