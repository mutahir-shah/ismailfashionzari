<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\AccountingAccount;

class ReturnAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (AccountingAccount::count() == 0) {
            $acc1 = AccountingAccount::create(['code' => '1300', 'name' => 'Cash', 'account_type' => 'asset', 'is_active' => true]);
            $acc2 = AccountingAccount::create(['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => 'asset', 'is_active' => true]);
            $acc3 = AccountingAccount::create(['code' => '2100', 'name' => 'Accounts Payable', 'account_type' => 'liability', 'is_active' => true]);
            $acc4 = AccountingAccount::create(['code' => '4150', 'name' => 'Sales Returns & Allowances', 'account_type' => 'revenue', 'is_active' => true]);
            $acc5 = AccountingAccount::create(['code' => '5150', 'name' => 'Purchase Returns & Allowances', 'account_type' => 'cogs', 'is_active' => true]);

            \App\Models\AccountMapping::create(['mapped_type' => 'cash', 'mapped_id' => 0, 'accounting_account_id' => $acc1->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'accounts_receivable', 'mapped_id' => 0, 'accounting_account_id' => $acc2->id]);
            \App\Models\AccountMapping::create(['mapped_type' => 'accounts_payable', 'mapped_id' => 0, 'accounting_account_id' => $acc3->id]);
            
            \App\Models\AccountingPeriod::create(['name' => 'Current', 'start_date' => '2000-01-01', 'end_date' => '2099-12-31', 'is_closed' => false]);
            \App\Models\Account::create(['id' => 1, 'account_no' => '123', 'name' => 'Cash', 'initial_balance' => 0, 'total_balance' => 0, 'is_default' => 1, 'is_active' => 1]);
        }

        \App\Models\AccountingConfig::updateOrCreate(['id' => 1], [
            'enabled' => true,
            'status' => 'active',
            'start_date' => '2000-01-01',
        ]);
        
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
        $this->customer = Customer::create(['customer_group_id' => 1, 'name' => 'C1', 'company_name' => 'C1', 'email' => 'c@c.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'S1', 'company_name' => 'S1', 'vat_number' => '1', 'email' => 's@s.com', 'phone_number' => '1', 'address' => 'A', 'city' => 'C', 'is_active' => true]);
        $this->product = Product::create(['name' => 'P1', 'code' => 'P1', 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => 1, 'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 10, 'price' => 100, 'qty' => 100, 'alert_quantity' => 1, 'is_active' => true]);
        \App\Models\Product_Warehouse::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 100]);
    }

    public function test_sale_return_accounting_entries()
    {
        $saleReturn = Returns::create([
            'reference_no' => 'TEST-RETURN-1',
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'account_id' => 1,
            'biller_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => 100,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 100,
            'document' => null,
            'return_note' => 'Test Return',
            'staff_note' => null,
        ]);

        $accService = app(\App\Services\AccountingService::class);
        $res = $accService->recordSaleReturn($saleReturn, 'sale_return_created');
        $this->assertTrue($res->success);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => Returns::class,
            'source_id' => $saleReturn->id,
            'event_type' => 'sale_return_created'
        ]);

        $journal = \App\Models\JournalEntry::where('source_type', Returns::class)
            ->where('source_id', $saleReturn->id)
            ->first();

        $arAcct = AccountingAccount::where('code', '1100')->first();
        $salesReturnAcct = AccountingAccount::where('code', '4150')->first();

        // DR Sales Returns & Allowances 100, CR AR 100
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $salesReturnAcct->id,
            'debit' => 100,
            'credit' => 0
        ]);
        
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $arAcct->id,
            'debit' => 0,
            'credit' => 100
        ]);
    }

    public function test_purchase_return_accounting_entries()
    {
        $purchaseReturn = ReturnPurchase::create([
            'reference_no' => 'TEST-PRETURN-1',
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'account_id' => 1,
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_cost' => 10,
            'order_tax_rate' => 0,
            'order_tax' => 0,
            'grand_total' => 10,
            'document' => null,
            'return_note' => 'Test Purchase Return',
            'staff_note' => null,
        ]);

        $accService = app(\App\Services\AccountingService::class);
        $res = $accService->recordPurchaseReturn($purchaseReturn, 'purchase_return_created');
        $this->assertTrue($res->success);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => ReturnPurchase::class,
            'source_id' => $purchaseReturn->id,
            'event_type' => 'purchase_return_created'
        ]);

        $journal = \App\Models\JournalEntry::where('source_type', ReturnPurchase::class)
            ->where('source_id', $purchaseReturn->id)
            ->first();

        $apAcct = AccountingAccount::where('code', '2100')->first();
        $purchaseReturnAcct = AccountingAccount::where('code', '5150')->first();

        // DR AP 10, CR Purchase Returns & Allowances 10
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $apAcct->id,
            'debit' => 10,
            'credit' => 0
        ]);
        
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'accounting_account_id' => $purchaseReturnAcct->id,
            'debit' => 0,
            'credit' => 10
        ]);
    }
}
