<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Modules\Restaurant\Entities\Modifier;
use Modules\Restaurant\Entities\ModifierGroup;
use Modules\Restaurant\Entities\ProductModifierGroup;
use Modules\Restaurant\Entities\ProductModifierGroupModifier;
use Modules\Restaurant\Entities\ProductSaleModifier;
use Modules\Restaurant\Services\ModifierInventoryService;
use Modules\Restaurant\Services\ModifierSelectionService;
use Tests\TestCase;

class RestaurantModifierSelectionTest extends TestCase
{
    use DatabaseTransactions;

    private Product $product;
    private ModifierGroup $group;
    private Modifier $modifier;
    private ModifierSelectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::create([
            'name' => 'Modifier Test Product',
            'code' => 'MOD-' . uniqid(),
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'brand_id' => 1,
            'category_id' => 1,
            'unit_id' => 1,
            'purchase_unit_id' => 1,
            'sale_unit_id' => 1,
            'price' => 10,
            'cost' => 5,
            'qty' => 10,
            'is_active' => 1,
        ]);
        $this->group = ModifierGroup::create([
            'name' => 'Size',
            'selection_type' => 'single',
            'min_selection' => 1,
            'max_selection' => 1,
            'is_required' => 1,
            'is_active' => 1,
        ]);
        $this->modifier = Modifier::create([
            'modifier_group_id' => $this->group->id,
            'name' => 'Large',
            'price_adjustment' => 2.5,
            'is_active' => 1,
        ]);
        ProductModifierGroup::create([
            'product_id' => $this->product->id,
            'modifier_group_id' => $this->group->id,
        ]);
        ProductModifierGroupModifier::create([
            'product_id' => $this->product->id,
            'modifier_group_id' => $this->group->id,
            'modifier_id' => $this->modifier->id,
            'price_adjustment' => 3.25,
            'is_active' => 1,
        ]);

        $this->service = app(ModifierSelectionService::class);
    }

    public function test_it_uses_authoritative_modifier_snapshot_and_price(): void
    {
        $resolved = $this->service->resolve($this->product->id, json_encode([
            ['id' => $this->modifier->id, 'name' => 'Forged', 'price' => 3.25],
        ]));

        $this->assertSame('Large', $resolved[0]['modifier_name']);
        $this->assertSame('Size', $resolved[0]['modifier_group_name']);
        $this->assertSame(3.25, $resolved[0]['price_adjustment']);
    }

    public function test_it_rejects_client_price_tampering(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->resolve($this->product->id, json_encode([
            ['id' => $this->modifier->id, 'price' => -100],
        ]));
    }

    public function test_it_rejects_missing_required_selection(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->resolve($this->product->id, null);
    }

    public function test_it_rejects_modifier_not_assigned_to_product(): void
    {
        $otherProduct = Product::create([
            'name' => 'Other Product',
            'code' => 'OTHER-' . uniqid(),
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'brand_id' => 1,
            'category_id' => 1,
            'unit_id' => 1,
            'purchase_unit_id' => 1,
            'sale_unit_id' => 1,
            'price' => 10,
            'cost' => 5,
            'qty' => 10,
            'is_active' => 1,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->resolve($otherProduct->id, json_encode([
            ['id' => $this->modifier->id, 'price' => 3.25],
        ]));
    }

    public function test_it_rejects_duplicate_modifier_ids(): void
    {
        $this->expectException(ValidationException::class);

        $payload = ['id' => $this->modifier->id, 'price' => 3.25];
        $this->service->resolve($this->product->id, json_encode([$payload, $payload]));
    }

    public function test_inventory_reversal_uses_the_persisted_sale_snapshot(): void
    {
        $ingredient = Product::create([
            'name' => 'Snapshot Ingredient',
            'code' => 'ING-' . uniqid(),
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'brand_id' => 1,
            'category_id' => 1,
            'unit_id' => 1,
            'purchase_unit_id' => 1,
            'sale_unit_id' => 1,
            'price' => 1,
            'cost' => 1,
            'qty' => 5,
            'is_active' => 1,
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Modifier Test Warehouse',
            'phone' => '123',
            'email' => uniqid() . '@example.test',
            'address' => 'Test',
            'is_active' => 1,
        ]);
        Product_Warehouse::create([
            'product_id' => $ingredient->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 5,
        ]);
        $snapshot = new ProductSaleModifier([
            'product_list' => (string) $ingredient->id,
            'qty_list' => '0.5',
        ]);

        // A later configuration edit must not affect reversal of the old sale.
        ProductModifierGroupModifier::where('modifier_id', $this->modifier->id)
            ->update(['product_list' => null, 'qty_list' => null]);

        app(ModifierInventoryService::class)->adjustSnapshot($snapshot, 4, $warehouse->id, 1);

        $this->assertSame(7.0, (float) $ingredient->fresh()->qty);
        $this->assertSame(
            7.0,
            (float) Product_Warehouse::where('product_id', $ingredient->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('qty')
        );
    }

    public function test_historical_labels_and_prices_are_snapshot_values(): void
    {
        $snapshot = new ProductSaleModifier([
            'modifier_group_name' => 'Original Size',
            'modifier_name' => 'Original Large',
            'price_adjustment' => 4.25,
        ]);

        $this->group->update(['name' => 'Renamed Size']);
        $this->modifier->update(['name' => 'Renamed Large', 'price_adjustment' => 9]);

        $this->assertSame('Original Size: Original Large', $snapshot->displayLabel());
        $this->assertSame(4.25, (float) $snapshot->price_adjustment);
    }
}
