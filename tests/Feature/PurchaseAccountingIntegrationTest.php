<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\AccountingAccount;

class PurchaseAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (AccountingAccount::count() == 0) {
            $acc1 = AccountingAccount::create(['code' => '1300', 'name' => 'Cash', 'account_type' => 'asset', 'is_active' => true]);
            $acc2 = AccountingAccount::create(['code' => '1200', 'name' => 'Inventory', 'account_type' => 'asset', 'is_active' => true]);
            $acc3 = AccountingAccount::create(['code' => '2100', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'is_active' => true]);
            $acc4 = AccountingAccount::create(['code' => '1250', 'name' => 'Input VAT', 'account_type' => 'asset', 'is_active' => true]);
            $acc5 = AccountingAccount::create(['code' => '5200', 'name' => 'Freight In', 'account_type' => 'cogs', 'is_active' => true]);
            $acc6 = AccountingAccount::create(['code' => '5300', 'name' => 'Purchase Discount', 'account_type' => 'revenue', 'is_active' => true]);

            \App\Models\AccountMapping::create(['mapped_type' => 'cash', 'mapped_id' => 0, 'accounting_account_id' => $acc1->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'inventory', 'mapped_id' => 0, 'accounting_account_id' => $acc2->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'accounts_payable', 'mapped_id' => 0, 'accounting_account_id' => $acc3->id]);
            
            \App\Models\AccountingPeriod::create(['name' => 'Current', 'start_date' => '2000-01-01', 'end_date' => '2099-12-31', 'is_closed' => false]);
            \App\Models\Account::create(['id' => 1, 'account_no' => '123', 'name' => 'Cash', 'initial_balance' => 0, 'total_balance' => 0, 'is_default' => 1, 'is_active' => 1]);
        }
        
        \Illuminate\Support\Facades\DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => 1
        ]);

        \App\Models\Unit::create(['id' => 1, 'unit_code' => 'pc', 'unit_name' => 'Piece', 'base_unit' => null, 'operator' => '*', 'operation_value' => 1, 'is_active' => true]);

        \Illuminate\Support\Facades\DB::table('general_settings')->insert([
            'site_title' => 'SalePro',
            'currency' => 1,
            'currency_position' => 'prefix',
            'staff_access' => 'all',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'is_zatca' => 0,
            'without_stock' => 'no'
        ]);

        $this->user = User::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => 'password', 'role_id' => 1, 'is_active' => true, 'phone' => '123456789', 'company_name' => 'Test Co', 'is_deleted' => false]);
        $this->warehouse = Warehouse::create(['name' => 'W1', 'phone' => '123', 'email' => 'w@w.com', 'address' => 'Addr', 'is_active' => true, 'is_deleted' => false]);
        $this->supplier = Supplier::create(['name' => 'S1', 'company_name' => 'S1', 'vat_number' => '1', 'email' => 's@s.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true]);
        $this->product = Product::create(['name' => 'P1', 'code' => 'P1', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 100, 'qty' => 100, 'alert_quantity' => 1, 'is_active' => true]);
        \App\Models\Product_Warehouse::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 100]);
        
        if (!DB::table('pos_setting')->first()) {
            DB::table('pos_setting')->insert([
                'id' => 1,
                'customer_id' => 1,
                'warehouse_id' => $this->warehouse->id,
                'biller_id' => 1,
                'product_number' => 4,
                'stripe_public_key' => 'test',
                'stripe_secret_key' => 'test',
                'keybord_active' => 0,
            ]);
        }
    }

    public function test_credit_purchase_creation()
    {
        $payload = [
            'reference_no' => 'TEST-PURCHASE-1',
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'item' => 1,
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 100, // 10 * 10
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 100,
            'status' => 1,
            'payment_status' => 1, // pending
            
            // items
            'product_id' => [$this->product->id],
            'qty' => [10],
            'recieved' => [10],
            'purchase_unit' => ['Piece'],
            'net_unit_cost' => [10],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [100],
            'unit_cost' => [10],
            'net_unit_margin' => [0],
            'net_unit_margin_type' => ['percentage'],
            'net_unit_price' => [100],
            'product_batch_id' => [null],
            'imei_number' => [null],
            'product_code' => [$this->product->code],
        ];

        $this->actingAs($this->user);
        $this->withoutExceptionHandling();
        
        $response = $this->post('/purchases', $payload);
        if (Purchase::count() == 0) {
            $response->dumpSession();
            $response->dump();
        }
        $purchase = Purchase::latest()->first();
        $this->assertNotNull($purchase);
        
        // Assert journal was created for purchase
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Purchase::class,
            'source_id' => $purchase->id,
            'event_type' => 'purchase_created'
        ]);

        $journal = \App\Models\JournalEntry::where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->first();

        $inventoryAcct = AccountingAccount::where('code', '1200')->first();
        $apAcct = AccountingAccount::where('code', '2100')->first();

        // Should have DR Inventory 100 and CR AP 100
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $inventoryAcct->id,
            'debit' => 100,
            'credit' => 0
        ]);
        
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $apAcct->id,
            'debit' => 0,
            'credit' => 100
        ]);
    }
    
    public function test_purchase_update_reverses_and_reposts()
    {
        $purchase = Purchase::create([
            'reference_no' => 'TEST-PURCHASE-UPDATE',
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'item' => 1,
            'total_qty' => 10,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 100,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 100,
            'paid_amount' => 0,
            'status' => 1,
            'payment_status' => 1,
        ]);
        
        // Mock the accounting for the initial creation since we bypassed the controller
        $accService = app(\App\Services\AccountingService::class);
        $accService->recordPurchase($purchase, 'purchase_created');

        $payload = [
            'reference_no' => 'TEST-PURCHASE-UPDATE',
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $this->supplier->id,
            'item' => 1,
            'total_qty' => 20,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 200, // Updated cost
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'order_discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 200,
            'paid_amount' => 0,
            'status' => 1,
            'payment_status' => 1,
            'created_at' => now()->format('Y/m/d'),
            
            // items
            'product_id' => [$this->product->id],
            'qty' => [20],
            'recieved' => [20],
            'purchase_unit' => ['Piece'],
            'net_unit_cost' => [10],
            'discount' => [0],
            'tax_rate' => [0],
            'tax' => [0],
            'subtotal' => [200],
            'unit_cost' => [10],
            'net_unit_margin' => [0],
            'net_unit_margin_type' => ['Percentage'],
            'batch_no' => [null],
            'expired_date' => [null],
            'product_batch_id' => [null],
            'imei_number' => [null],
            'product_code' => [$this->product->code],
            'net_unit_margin' => [0],
            'net_unit_margin_type' => ['percentage'],
            'net_unit_price' => [100],
            'unit_cost' => [10],
        ];

        $this->actingAs($this->user);
        
        $response = $this->put("/purchases/{$purchase->id}", $payload);
        
        if (session('not_permitted')) {
            dump("NOT PERMITTED: " . session('not_permitted'));
        }
        
        $response->assertStatus(302);
        $response->assertSessionMissing('not_permitted');
        
        if (!\App\Models\JournalEntry::where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->where('event_type', 'purchase_created_reversed')
            ->exists()) {
            $response->dumpSession();
            $response->dump();
        }

        // Assert reversal journal was created
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Purchase::class,
            'source_id' => $purchase->id,
            'event_type' => 'purchase_created_reversed'
        ]);
        
        // Assert new updated journal was created
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Purchase::class,
            'source_id' => $purchase->id,
            'event_type' => 'purchase_updated'
        ]);
        
        $entry = \App\Models\JournalEntry::where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->where('event_type', 'purchase_updated')
            ->first();

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $entry->id,
            'debit' => 200,
            'credit' => 0
        ]);
        
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $entry->id,
            'debit' => 0,
            'credit' => 200
        ]);
    }
}
