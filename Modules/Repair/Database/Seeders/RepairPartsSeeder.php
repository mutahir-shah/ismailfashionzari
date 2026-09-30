<?php

namespace Modules\Repair\Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RepairPartsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Prerequisites ─────────────────────────────────────────────────────
        $unit = Unit::where('unit_code', 'Pcs')->first()
             ?? Unit::first();

        if (!$unit) {
            $unit = Unit::create([
                'unit_name'       => 'Piece',
                'unit_code'       => 'Pcs',
                'base_unit'       => null,
                'operator'        => '*',
                'operation_value' => 1,
                'is_active'       => true,
            ]);
        }

        // Category — Repair Parts
        $category = Category::firstOrCreate(
            ['name' => 'Repair Parts'],
            ['is_active' => true]
        );

        // Brand
        $brands = [
            'Generic'  => Brand::firstOrCreate(['title' => 'Generic'],  ['is_active' => true]),
            'Samsung'  => Brand::firstOrCreate(['title' => 'Samsung'],  ['is_active' => true]),
            'Apple'    => Brand::firstOrCreate(['title' => 'Apple'],    ['is_active' => true]),
            'Xiaomi'   => Brand::firstOrCreate(['title' => 'Xiaomi'],   ['is_active' => true]),
            'OnePlus'  => Brand::firstOrCreate(['title' => 'OnePlus'],  ['is_active' => true]),
        ];

        // Warehouses — stock will be spread across all active warehouses
        $warehouses = Warehouse::where('is_active', true)->get();

        // ── Products ──────────────────────────────────────────────────────────
        $parts = [
            // Mobile / Device Parts
            [
                'name'  => 'Mobile Screen LCD (Generic)',
                'code'  => 'RP-LCD-001',
                'cost'  => 450,
                'price' => 650,
                'brand' => 'Generic',
                'qty'   => 35,
            ],
            [
                'name'  => 'Samsung Display Panel AMOLED',
                'code'  => 'RP-LCD-002',
                'cost'  => 1200,
                'price' => 1800,
                'brand' => 'Samsung',
                'qty'   => 22,
            ],
            [
                'name'  => 'iPhone Display Assembly',
                'code'  => 'RP-LCD-003',
                'cost'  => 2500,
                'price' => 3500,
                'brand' => 'Apple',
                'qty'   => 20,
            ],
            [
                'name'  => 'Mobile Battery 3000mAh (Generic)',
                'code'  => 'RP-BAT-001',
                'cost'  => 180,
                'price' => 280,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
            [
                'name'  => 'Samsung Battery EB-BG970ABU',
                'code'  => 'RP-BAT-002',
                'cost'  => 350,
                'price' => 550,
                'brand' => 'Samsung',
                'qty'   => 30,
            ],
            [
                'name'  => 'iPhone Battery Replacement',
                'code'  => 'RP-BAT-003',
                'cost'  => 500,
                'price' => 750,
                'brand' => 'Apple',
                'qty'   => 25,
            ],
            [
                'name'  => 'Charging Port USB-C (Generic)',
                'code'  => 'RP-CHG-001',
                'cost'  => 80,
                'price' => 150,
                'brand' => 'Generic',
                'qty'   => 38,
            ],
            [
                'name'  => 'Lightning Charging Port (Apple)',
                'code'  => 'RP-CHG-002',
                'cost'  => 200,
                'price' => 350,
                'brand' => 'Apple',
                'qty'   => 28,
            ],
            [
                'name'  => 'Front Camera Module (Generic)',
                'code'  => 'RP-CAM-001',
                'cost'  => 250,
                'price' => 400,
                'brand' => 'Generic',
                'qty'   => 24,
            ],
            [
                'name'  => 'Rear Camera 48MP (Xiaomi)',
                'code'  => 'RP-CAM-002',
                'cost'  => 600,
                'price' => 950,
                'brand' => 'Xiaomi',
                'qty'   => 21,
            ],
            [
                'name'  => 'Back Panel / Housing (Generic)',
                'code'  => 'RP-HSG-001',
                'cost'  => 120,
                'price' => 220,
                'brand' => 'Generic',
                'qty'   => 36,
            ],
            [
                'name'  => 'Fingerprint Sensor (Generic)',
                'code'  => 'RP-FPS-001',
                'cost'  => 180,
                'price' => 300,
                'brand' => 'Generic',
                'qty'   => 32,
            ],
            [
                'name'  => 'Speaker Earpiece Module',
                'code'  => 'RP-SPK-001',
                'cost'  => 90,
                'price' => 160,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
            [
                'name'  => 'Loud Speaker (Bottom)',
                'code'  => 'RP-SPK-002',
                'cost'  => 110,
                'price' => 190,
                'brand' => 'Generic',
                'qty'   => 34,
            ],
            [
                'name'  => 'Microphone Module (Generic)',
                'code'  => 'RP-MIC-001',
                'cost'  => 70,
                'price' => 130,
                'brand' => 'Generic',
                'qty'   => 38,
            ],

            // Vehicle / Bike Parts
            [
                'name'  => 'Engine Oil Filter (Generic)',
                'code'  => 'RP-VEH-001',
                'cost'  => 120,
                'price' => 200,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
            [
                'name'  => 'Spark Plug (Generic)',
                'code'  => 'RP-VEH-002',
                'cost'  => 80,
                'price' => 140,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
            [
                'name'  => 'Brake Pad Set (Generic)',
                'code'  => 'RP-VEH-003',
                'cost'  => 250,
                'price' => 400,
                'brand' => 'Generic',
                'qty'   => 30,
            ],

            // General Repair Consumables
            [
                'name'  => 'Thermal Paste (Syringe 1g)',
                'code'  => 'RP-CNS-001',
                'cost'  => 30,
                'price' => 60,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
            [
                'name'  => 'Screen Adhesive Tape (Roll)',
                'code'  => 'RP-CNS-002',
                'cost'  => 40,
                'price' => 80,
                'brand' => 'Generic',
                'qty'   => 40,
            ],
        ];

        // ── Insert / Upsert ───────────────────────────────────────────────────
        foreach ($parts as $part) {
            $brand = $brands[$part['brand']];
            $qty   = $part['qty']; // already 20–40

            // Upsert: update if code exists, create otherwise
            $product = Product::updateOrCreate(
                ['code' => $part['code']],
                [
                    'name'             => $part['name'],
                    'type'             => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id'         => $brand->id,
                    'category_id'      => $category->id,
                    'unit_id'          => $unit->id,
                    'purchase_unit_id' => $unit->id,
                    'sale_unit_id'     => $unit->id,
                    'cost'             => $part['cost'],
                    'price'            => $part['price'],
                    'profit_margin'    => round((($part['price'] - $part['cost']) / $part['cost']) * 100, 2),
                    'profit_margin_type' => 'percentage',
                    'qty'              => $qty,
                    'alert_quantity'   => 5,
                    'tax_method'       => 1,
                    'image'            => 'zummXD2dvAtI.png',
                    'is_active'        => true,
                    'featured'         => 1,
                ]
            );

            // ── Warehouse stock ───────────────────────────────────────────────
            if ($warehouses->count() > 0) {
                // Distribute qty evenly across warehouses (min 20 per warehouse)
                $perWarehouse = (int) floor($qty / $warehouses->count());
                $perWarehouse = max($perWarehouse, 20); // ensure at least 20

                foreach ($warehouses as $warehouse) {
                    Product_Warehouse::updateOrCreate(
                        [
                            'product_id'   => $product->id,
                            'warehouse_id' => $warehouse->id,
                        ],
                        ['qty' => $perWarehouse]
                    );
                }

                // Sync product.qty to total warehouse stock
                $totalStock = Product_Warehouse::where('product_id', $product->id)->sum('qty');
                $product->qty = $totalStock;
                $product->save();
            }

            $this->command->info("✅ Seeded: {$part['name']} [{$part['code']}] — qty: {$product->qty}");
        }

        $this->command->newLine();
        $this->command->info('🔧 Repair Parts Seeder completed — 20 products seeded.');
    }
}
