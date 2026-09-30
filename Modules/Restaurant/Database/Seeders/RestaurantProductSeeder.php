<?php

namespace Modules\Restaurant\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Product_Warehouse;
use App\Traits\CacheForget;
use App\Traits\TenantInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Exception;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class RestaurantProductSeeder extends Seeder
{
    use CacheForget;
    use TenantInfo;
    public function run()
    {
        $warehouses = Warehouse::where('is_active', true)->pluck('id');

        $images = [
            'https://images.unsplash.com/photo-1606755962778-611d2e9aaf7d', // Fish Fry
            'https://images.unsplash.com/photo-1599785209795-0d0f107dbf77', // French Fries
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Vanilla Ice Cream
            'https://images.unsplash.com/photo-1569058241049-bc9f05467b57', // Pepsi Bottle
            'https://images.unsplash.com/photo-1598514983041-bd0a0b0a0f1f', // Chicken Burger
            'https://images.unsplash.com/photo-1617196034836-8a17e7c8a1a3', // French Fries
            'https://images.unsplash.com/photo-1617196025643-82f1c5f3b0b0', // Pepperoni Pizza
            'https://images.unsplash.com/photo-1604152135912-04a5761d36c1', // Vanilla Ice Cream
            'https://images.unsplash.com/photo-1548365328-8d5c3de9338f', // Margarita Pizza
            'https://images.unsplash.com/photo-1571790348262-9cdcb6cb18d1', // Orange Juice
            'https://images.unsplash.com/photo-1617196035598-8f7a2d9b9e1c', // BBQ Chicken Pizza
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Beef Burger
            'https://images.unsplash.com/photo-1569058241049-bc9f05467b57', // Pepsi
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Veg Sandwich
            'https://images.unsplash.com/photo-1606755962778-611d2e9aaf7d', // Fish Fry
            'https://images.unsplash.com/photo-1569058241049-bc9f05467b57', // Pepsi
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Beef Burger
            'https://images.unsplash.com/photo-1617196034836-8a17e7c8a1a3', // Pepsi
            'https://images.unsplash.com/photo-1548365328-8d5c3de9338f', // Margarita Pizza
            'https://images.unsplash.com/photo-1604152135912-04a5761d36c1', // Vanilla Ice Cream
            'https://images.unsplash.com/photo-1617196035598-8f7a2d9b9e1c', // Chocolate Cake
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Veg Sandwich
            'https://images.unsplash.com/photo-1571790348262-9cdcb6cb18d1', // Orange Juice
            'https://images.unsplash.com/photo-1569058241049-bc9f05467b57', // Chicken Burger
            'https://images.unsplash.com/photo-1617196034836-8a17e7c8a1a3', // Beef Burger
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Chicken Sandwich
            'https://images.unsplash.com/photo-1569058241049-bc9f05467b57', // Chicken Burger
            'https://images.unsplash.com/photo-1604152135912-04a5761d36c1', // Margarita Pizza
            'https://images.unsplash.com/photo-1586190848861-99aa4a171e90', // Beef Burger
            'https://images.unsplash.com/photo-1617196035598-8f7a2d9b9e1c', // Vanilla Ice Cream
        ];


        $products = [
            ['name' => 'Fish Fry', 'category_id' => 4, 'code' => 'FOOD-0001', 'cost' => 185, 'price' => 465, 'profit_margin' => 60.22, 'short_description' => 'Nostrum ut nostrum minima facere velit.'],
            ['name' => 'French Fries', 'category_id' => 5, 'code' => 'FOOD-0002', 'cost' => 81, 'price' => 193, 'profit_margin' => 58.03, 'short_description' => 'Dolore veritatis id qui beatae.'],
            ['name' => 'Vanilla Ice Cream', 'category_id' => 3, 'code' => 'FOOD-0003', 'cost' => 104, 'price' => 315, 'profit_margin' => 66.98, 'short_description' => 'Dolorum odio esse sapiente libero a.'],
            ['name' => 'Pepsi', 'category_id' => 4, 'code' => 'FOOD-0004', 'cost' => 291, 'price' => 443, 'profit_margin' => 34.31, 'short_description' => 'Est et ut id ex reiciendis.'],
            ['name' => 'Chicken Burger', 'category_id' => 2, 'code' => 'FOOD-0005', 'cost' => 152, 'price' => 219, 'profit_margin' => 30.59, 'short_description' => 'Aut laudantium quis enim laudantium et suscipit.'],
            ['name' => 'French Fries', 'category_id' => 5, 'code' => 'FOOD-0006', 'cost' => 261, 'price' => 510, 'profit_margin' => 48.82, 'short_description' => 'Est voluptas sed et dicta.'],
            ['name' => 'Pepperoni Pizza', 'category_id' => 5, 'code' => 'FOOD-0007', 'cost' => 165, 'price' => 320, 'profit_margin' => 48.44, 'short_description' => 'Rerum autem qui nemo sunt quia nesciunt.'],
            ['name' => 'Vanilla Ice Cream', 'category_id' => 1, 'code' => 'FOOD-0008', 'cost' => 175, 'price' => 327, 'profit_margin' => 46.48, 'short_description' => 'Et earum dolorem pariatur illum consequuntur.'],
            ['name' => 'Margarita Pizza', 'category_id' => 3, 'code' => 'FOOD-0009', 'cost' => 113, 'price' => 251, 'profit_margin' => 54.98, 'short_description' => 'Accusamus soluta fugit harum temporibus non quasi dolores.'],
            ['name' => 'Orange Juice', 'category_id' => 3, 'code' => 'FOOD-0010', 'cost' => 89, 'price' => 316, 'profit_margin' => 71.84, 'short_description' => 'Exercitationem sequi nihil voluptatem.'],
            ['name' => 'BBQ Chicken Pizza', 'category_id' => 4, 'code' => 'FOOD-0011', 'cost' => 124, 'price' => 409, 'profit_margin' => 69.68, 'short_description' => 'Impedit labore dolor ut numquam sed aperiam.'],
            ['name' => 'Beef Burger', 'category_id' => 3, 'code' => 'FOOD-0012', 'cost' => 280, 'price' => 385, 'profit_margin' => 27.27, 'short_description' => 'Necessitatibus voluptate recusandae quo minima laborum beatae fuga.'],
            ['name' => 'Pepsi', 'category_id' => 1, 'code' => 'FOOD-0013', 'cost' => 225, 'price' => 299, 'profit_margin' => 24.75, 'short_description' => 'Enim inventore modi ea odit labore rerum impedit illum.'],
            ['name' => 'Veg Sandwich', 'category_id' => 5, 'code' => 'FOOD-0014', 'cost' => 88, 'price' => 162, 'profit_margin' => 45.68, 'short_description' => 'Quos eius officia quae sit similique.'],
            ['name' => 'Fish Fry', 'category_id' => 1, 'code' => 'FOOD-0015', 'cost' => 117, 'price' => 237, 'profit_margin' => 50.63, 'short_description' => 'Aut esse dolores eveniet temporibus sed.'],
            ['name' => 'Pepsi', 'category_id' => 1, 'code' => 'FOOD-0016', 'cost' => 277, 'price' => 544, 'profit_margin' => 49.08, 'short_description' => 'Nihil ipsam consequatur sit qui consequatur et magni.'],
            ['name' => 'Beef Burger', 'category_id' => 1, 'code' => 'FOOD-0017', 'cost' => 190, 'price' => 253, 'profit_margin' => 24.9, 'short_description' => 'Repellendus eum magnam nesciunt ut saepe nemo.'],
            ['name' => 'Pepsi', 'category_id' => 5, 'code' => 'FOOD-0018', 'cost' => 193, 'price' => 344, 'profit_margin' => 43.9, 'short_description' => 'Et labore nisi quia vel ex et delectus.'],
            ['name' => 'Margarita Pizza', 'category_id' => 1, 'code' => 'FOOD-0019', 'cost' => 104, 'price' => 229, 'profit_margin' => 54.59, 'short_description' => 'Nemo ducimus quam quos sint eum suscipit consequatur rerum.'],
            ['name' => 'Vanilla Ice Cream', 'category_id' => 3, 'code' => 'FOOD-0020', 'cost' => 244, 'price' => 342, 'profit_margin' => 28.65, 'short_description' => 'Est fuga ut hic perferendis tempore officia dignissimos.'],
            ['name' => 'Chocolate Cake', 'category_id' => 5, 'code' => 'FOOD-0021', 'cost' => 113, 'price' => 293, 'profit_margin' => 61.43, 'short_description' => 'Asperiores et omnis accusamus consectetur non quo suscipit.'],
            ['name' => 'Veg Sandwich', 'category_id' => 3, 'code' => 'FOOD-0022', 'cost' => 135, 'price' => 208, 'profit_margin' => 35.1, 'short_description' => 'Quasi quaerat eos harum sunt ut.'],
            ['name' => 'Orange Juice', 'category_id' => 3, 'code' => 'FOOD-0023', 'cost' => 93, 'price' => 153, 'profit_margin' => 39.22, 'short_description' => 'Et quam sequi consequuntur dolores occaecati voluptatem.'],
            ['name' => 'Chicken Burger', 'category_id' => 4, 'code' => 'FOOD-0024', 'cost' => 238, 'price' => 351, 'profit_margin' => 32.19, 'short_description' => 'Voluptas et quos voluptatem sapiente.'],
            ['name' => 'Beef Burger', 'category_id' => 2, 'code' => 'FOOD-0025', 'cost' => 247, 'price' => 500, 'profit_margin' => 50.6, 'short_description' => 'Est libero accusantium labore exercitationem.'],
            ['name' => 'Chicken Sandwich', 'category_id' => 2, 'code' => 'FOOD-0026', 'cost' => 234, 'price' => 390, 'profit_margin' => 40, 'short_description' => 'Dicta modi minima dolorum enim quo quia omnis eveniet.'],
            ['name' => 'Chicken Burger', 'category_id' => 4, 'code' => 'FOOD-0027', 'cost' => 238, 'price' => 356, 'profit_margin' => 33.15, 'short_description' => 'Ipsum provident at quasi nihil ea adipisci.'],
            ['name' => 'Margarita Pizza', 'category_id' => 5, 'code' => 'FOOD-0028', 'cost' => 85, 'price' => 178, 'profit_margin' => 52.25, 'short_description' => 'Et minima pariatur itaque officiis inventore qui.'],
            ['name' => 'Beef Burger', 'category_id' => 3, 'code' => 'FOOD-0029', 'cost' => 288, 'price' => 365, 'profit_margin' => 21.1, 'short_description' => 'Atque voluptatem inventore itaque et excepturi voluptas.'],
            ['name' => 'Vanilla Ice Cream', 'category_id' => 2, 'code' => 'FOOD-0030', 'cost' => 170, 'price' => 369, 'profit_margin' => 53.93, 'short_description' => 'Voluptas ab perspiciatis dolorem sunt ut quia.'],
        ];


        // ✅ Ensure directories exist
        $dirs = [
            public_path('images/product/'),
            public_path('images/product/xlarge/'),
            public_path('images/product/large/'),
            public_path('images/product/medium/'),
            public_path('images/product/small/'),
        ];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        foreach ($products as $key => $p) {
            $image_names = [];
            try {
                $imageUrl = $images[$key] ?? null;

                if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    // Download image
                    $response = Http::get($imageUrl);
                    if (!$response->successful()) {
                        throw new \Exception("Failed to fetch image: {$imageUrl}");
                    }

                    $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $imageName = date("YmdHis") . ($key + 1) . '.' . $ext;

                    // Intervention Image manager
                    $manager = new ImageManager(new GdDriver);
                    $image = $manager->read($response->body());

                    // Save original image
                    $image->save(public_path('images/product/') . $imageName);

                    // Create multiple sizes
                    $image->cover(1000, 1250)->save(public_path('images/product/xlarge/') . $imageName, 100);
                    $image->cover(500, 500)->save(public_path('images/product/large/') . $imageName, 100);
                    $image->cover(250, 250)->save(public_path('images/product/medium/') . $imageName, 100);
                    $image->cover(100, 100)->save(public_path('images/product/small/') . $imageName, 100);

                    $image_names[] = $imageName;
                }
            } catch (\Exception $e) {
                Log::error("Error processing image: " . $e->getMessage());
            }

            // ✅ Create product
            $product = Product::create([
                'name' => $p['name'],
                'category_id' => $p['category_id'],
                'code' => $p['code'],
                'type' => 'food',
                'menu_type' => 'Restaurant Menu',
                'cost' => $p['cost'],
                'price' => $p['price'],
                'profit_margin' => $p['profit_margin'],
                'alert_quantity' => 10,
                'in_stock' => 1,
                'is_active' => 1,
                'is_online' => 1,
                'image' => $image_names[0] ?? null,
                'purchase_unit_id' => 0,
                'sale_unit_id' => 0,
                'short_description' => $p['short_description'],
                'slug' => Str::slug($p['name'], '-'),
                'barcode_symbology' => 'C128',
                'unit_id' => 1,
                'qty' => 0,
            ]);

            // ✅ Initial stock for all warehouses
            $initial_stock = 10;
            foreach ($warehouses as $warehouse_id) {
                Product_Warehouse::firstOrCreate([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse_id,
                    'qty' => $initial_stock,
                ]);
            }

            $product->qty += $initial_stock * count($warehouses);
            $product->save();
        }
    }
}
