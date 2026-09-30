<?php

namespace Modules\AIAssistant\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Returns;
use App\Models\Product_Sale;
use App\Models\ProductReturn;
use App\Models\Product_Warehouse;

class Phase4SlowMovingProductsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
    }

    private function createProduct($code): Product
    {
        return Product::create([
            'name' => 'Prod ' . $code, 
            'code' => $code, 
            'type' => 'standard', 
            'barcode_symbology' => 'C128', 
            'brand_id' => 1, 
            'category_id' => 1, 
            'unit_id' => 1, 
            'purchase_unit_id' => 1, 
            'sale_unit_id' => 1, 
            'cost' => 10, 
            'price' => 20, 
            'is_active' => true
        ]);
    }

    private function mockSkill(array $businessContext = [])
    {
        $skill = new \Modules\AIAssistant\Skills\SlowMovingProductsSkill();
        $message = new \Modules\AIAssistant\DTO\AssistantMessageData(
            role: 'user',
            content: 'slow moving products',
        );
        $context = new \Modules\AIAssistant\DTO\AssistantContextData(
            tenantId: null,
            userId: 1,
            businessContext: $businessContext,
            systemContext: []
        );
        return $skill->handle($message, $context);
    }

    // 1. Returned quantity treatment
    public function test_returned_quantities_subtract_from_sales()
    {
        $p = $this->createProduct('RET1');
        Product_Warehouse::create(['product_id' => $p->id, 'warehouse_id' => 1, 'qty' => 50]);
        
        $sale = Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 20, 'grand_total' => 20, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product_Sale::create(['sale_id' => $sale->id, 'product_id' => $p->id, 'qty' => 10, 'sale_unit_id' => 1, 'net_unit_price' => 20, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 200]);
        
        // Return matching the sale date/warehouse
        $ret = Returns::create(['reference_no' => 'R1', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'account_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 20, 'grand_total' => 20, 'created_at' => now(), 'updated_at' => now()]);
        ProductReturn::create(['return_id' => $ret->id, 'product_id' => $p->id, 'qty' => 10, 'sale_unit_id' => 1, 'net_unit_price' => 20, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 200]);

        $response = $this->mockSkill();
        
        // Net sales is 10 - 10 = 0. So it should be slow moving.
        $tableRow = collect($response->table['rows'])->firstWhere('0', $p->name . ' (' . $p->code . ')');
        $this->assertNotNull($tableRow);
        $this->assertEquals(0, $tableRow[2]); // Net Sales
    }

    // 2 & 3. No-sale product, old-sale product, deterministic ordering
    public function test_deterministic_ordering_of_no_sale_and_old_sale()
    {
        $p1 = $this->createProduct('A_NoSale'); // Name: Prod A_NoSale
        Product_Warehouse::create(['product_id' => $p1->id, 'warehouse_id' => 1, 'qty' => 50]);
        
        $p2 = $this->createProduct('B_OldSale'); // Name: Prod B_OldSale
        Product_Warehouse::create(['product_id' => $p2->id, 'warehouse_id' => 1, 'qty' => 50]);
        $sale2 = Sale::create(['reference_no' => 'S2', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 20, 'grand_total' => 20, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => now()->subDays(40), 'updated_at' => now()->subDays(40)]); // Older than lookback
        Product_Sale::create(['sale_id' => $sale2->id, 'product_id' => $p2->id, 'qty' => 10, 'sale_unit_id' => 1, 'net_unit_price' => 20, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 200]);

        $p3 = $this->createProduct('C_NoSale'); // Name: Prod C_NoSale
        Product_Warehouse::create(['product_id' => $p3->id, 'warehouse_id' => 1, 'qty' => 50]);

        $response = $this->mockSkill();
        
        // Order should be:
        // 1. A_NoSale (no sale date, lowest name)
        // 2. C_NoSale (no sale date, higher name)
        // 3. B_OldSale (has sale date)
        
        $rows = array_filter($response->table['rows'], fn($r) => in_array($r[0], ['Prod A_NoSale (A_NoSale)', 'Prod B_OldSale (B_OldSale)', 'Prod C_NoSale (C_NoSale)']));
        $rows = array_values($rows);
        
        $this->assertEquals('Prod A_NoSale (A_NoSale)', $rows[0][0]);
        $this->assertEquals('Prod C_NoSale (C_NoSale)', $rows[1][0]);
        $this->assertEquals('Prod B_OldSale (B_OldSale)', $rows[2][0]);
    }

    // 4. Last sale date output
    public function test_last_sale_date_output()
    {
        $p = $this->createProduct('LSD1');
        Product_Warehouse::create(['product_id' => $p->id, 'warehouse_id' => 1, 'qty' => 50]);
        $date = now()->subDays(40);
        $sale = Sale::create(['reference_no' => 'S1', 'user_id' => 1, 'customer_id' => 1, 'warehouse_id' => 1, 'biller_id' => 1, 'item' => 1, 'total_qty' => 1, 'total_price' => 20, 'grand_total' => 20, 'paid_amount' => 0, 'payment_status' => 1, 'sale_status' => 1, 'created_at' => $date, 'updated_at' => $date]);
        Product_Sale::create(['sale_id' => $sale->id, 'product_id' => $p->id, 'qty' => 10, 'sale_unit_id' => 1, 'net_unit_price' => 20, 'discount' => 0, 'tax_rate' => 0, 'tax' => 0, 'total' => 200]);

        $response = $this->mockSkill();
        
        $tableRow = collect($response->table['rows'])->firstWhere('0', $p->name . ' (' . $p->code . ')');
        $this->assertEquals($date->format('Y-m-d'), $tableRow[3]);
    }

    // 5. Warehouse restriction
    public function test_warehouse_restriction()
    {
        $p = $this->createProduct('WH1');
        Product_Warehouse::create(['product_id' => $p->id, 'warehouse_id' => 1, 'qty' => 50]);
        Product_Warehouse::create(['product_id' => $p->id, 'warehouse_id' => 2, 'qty' => 50]); // Should be filtered
        
        $response = $this->mockSkill(['warehouse_ids' => [1]]);
        
        $tableRow = collect($response->table['rows'])->firstWhere('0', $p->name . ' (' . $p->code . ')');
        $this->assertEquals(50, $tableRow[1]); // Only 50 from WH 1
        $this->assertFalse($response->metadata['failed_closed']);
    }

    // 6. Explicit empty scope warning
    public function test_explicit_empty_scope_warning()
    {
        $response = $this->mockSkill(['warehouse_ids' => []]);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('empty_warehouse_scope', $response->metadata['reason']);
        $this->assertContains('No warehouse access is available for this request.', $response->warnings);
        $this->assertEquals(0, $response->cards[0]['value']);
        $this->assertEmpty($response->table);
    }

    // 7. own fail-closed warning
    public function test_own_fail_closed_warning()
    {
        $response = $this->mockSkill(['own_user_id' => 1]);
        $this->assertTrue($response->metadata['failed_closed']);
        $this->assertEquals('own_access_restriction', $response->metadata['reason']);
        $this->assertContains('Slow moving products are global or warehouse-wide. User-restricted scopes are not permitted to view this data.', $response->warnings);
        $this->assertEquals(0, $response->cards[0]['value']);
        $this->assertEmpty($response->table);
    }

    // 8. Result count includes all matches while table is limited
    public function test_result_count_includes_all_while_table_is_limited()
    {
        for ($i = 0; $i < 15; $i++) {
            $p = $this->createProduct('MANY' . $i);
            Product_Warehouse::create(['product_id' => $p->id, 'warehouse_id' => 1, 'qty' => 50]);
        }
        
        $response = $this->mockSkill();
        
        $this->assertGreaterThanOrEqual(15, collect($response->cards)->firstWhere('title', 'Slow Moving Products')['value']);
        $this->assertLessThanOrEqual(10, count($response->table['rows'])); // Result limit is 10
    }
}
