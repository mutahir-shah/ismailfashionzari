<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Product_Sale;
use App\Models\Currency;
use App\Models\Biller;
use App\Models\PosSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PublicMenuController extends Controller
{
    public function placeOrder(Request $request)
    {
        $post_data = $request->all();
        $post_data['reference_no'] = 'pm-' . date('YmdHis');

        $general_setting = cache()->get('general_setting');
        $currency = Currency::find($general_setting->currency);

        # CUSTOMER & USER INFORMATION
        if (Auth::check()) {
            $user_id = Auth::id();
            $customer = Customer::where('user_id', $user_id)->first()
                        ?? Customer::where('phone_number', $post_data['billing_phone'])->first()
                        ?? Customer::where('email', Auth::user()->email)->first();
        } else {
            $customer = Customer::where('phone_number', $post_data['billing_phone'])->first();
            $user = User::where('role_id', 1)->where('is_active', 1)->first();
            $user_id = $user->id ?? 1;
        }

        if (!$customer) {
            $customer = Customer::create([
                'name' => $post_data['billing_name'],
                'phone_number' => $post_data['billing_phone'],
                'address' => $post_data['billing_address'] ?? 'N/A',
                'city' => 'N/A',
                'customer_group_id' => 1,
                'is_active' => 1
            ]);
        }

        $biller = Biller::where('is_active', true)->first();

        $create_sale = Sale::create([
            'reference_no'         => $post_data['reference_no'],
            'user_id'              => $user_id,
            'customer_id'          => $customer->id,
            'warehouse_id'         => $post_data['warehouse_id'],
            'biller_id'            => $biller->id ?? 1,
            'item'                 => $post_data['item_count'],
            'total_qty'            => $post_data['total_qty'],
            'total_discount'       => 0,
            'total_tax'            => 0,
            'total_price'          => $post_data['sub_total'],
            'grand_total'          => $post_data['grand_total'],
            'sale_status'          => 2, // Pending
            'payment_status'       => 1, // Pending
            'sale_note'            => $post_data['sale_note'] ?? null,
            'sale_type'            => 'online',
            'payment_mode'         => 'WhatsApp Order',
            'created_at'           => date('Y-m-d H:i:s'),
            'currency_id'          => $currency->id,
            'exchange_rate'        => $currency->exchange_rate ?? 1
        ]);

        $cart = $post_data['cart']; // Expecting array
        foreach ($cart as $item) {
            // Handle variant_id if passed
            $variant_id = 0;
            if (isset($item['variant_id']) && $item['variant_id'] != 'null') {
                $variant_id = $item['variant_id'];
            }

            Product_Sale::create([
                'sale_id' => $create_sale->id,
                'product_id' => $item['id'],
                'variant_id' => $variant_id,
                'qty' => $item['qty'],
                'net_unit_price' => $item['price'],
                'sale_unit_id' => $item['sale_unit_id'] ?? Product::find($item['id'])->sale_unit_id,
                'discount' => 0,
                'tax_rate' => 0,
                'tax' => 0,
                'total' => $item['qty'] * $item['price'],
            ]);
        }

        $accountingResult = app(\App\Services\AccountingService::class)
            ->recordSale($create_sale, 'ecommerce_order_created');
        if (!$accountingResult->success) {
            \Log::error('Accounting failed for public menu order', [
                'sale_id' => $create_sale->id,
                'error' => $accountingResult->error,
            ]);
        }

        return response()->json([
            'success' => true,
            'reference' => $create_sale->reference_no
        ]);
    }

    /**
     * Resolve a warehouse by its slug, or abort 404.
     */
    private function resolveWarehouse(string $slug): Warehouse
    {
        $warehouses = Warehouse::where('is_active', 1)->get();
        $warehouse  = $warehouses->first(function ($w) use ($slug) {
            return Str::slug($w->name) === $slug;
        });

        if (! $warehouse) {
            abort(404, 'Business not found.');
        }

        return $warehouse;
    }

    /**
     * Build the grouped variants_data structure expected by the front-end.
     * Applied to a single Product model instance.
     */
    private function buildVariantsData(Product $product): array
    {
        if (! $product->is_variant || $product->variant->isEmpty() || ! $product->variant_option) {
            return [];
        }

        $groups       = json_decode($product->variant_option, true) ?? [];
        $valueStrings = json_decode($product->variant_value,  true) ?? [];
        $pivotRows    = $product->variant->values(); // ordered by position
        $offset       = 0;
        $grouped      = [];

        foreach ($groups as $i => $groupLabel) {
            $optionNames = array_filter(
                array_map('trim', explode(',', $valueStrings[$i] ?? ''))
            );
            $options = [];
            foreach (array_values($optionNames) as $j => $optName) {
                $pv = $pivotRows->get($offset + $j);
                if (! $pv) {
                    continue;
                }
                $options[] = [
                    'id'               => $pv->pivot->id,
                    'name'             => $optName,
                    'additional_price' => (float) ($pv->pivot->additional_price ?? 0),
                ];
            }
            $offset += count($optionNames);
            if (count($options)) {
                $grouped[] = ['group' => $groupLabel, 'options' => $options];
            }
        }

        return $grouped;
    }

    // ─── Public page ─────────────────────────────────────────────────────────

    public function index(Request $request, $slug)
    {
        $warehouse = $this->resolveWarehouse($slug);

        // Handle optional table (restaurant module)
        $table           = null;
        $isRestaurant    = false;
        $general_setting = cache()->get('general_setting');

        if ($general_setting && $general_setting->modules) {
            $modules      = explode(',', $general_setting->modules);
            $isRestaurant = in_array('restaurant', $modules);
        }

        if ($isRestaurant && $request->table_id) {
            $table = DB::table('tables')
                ->join('floors', 'tables.floor_id', '=', 'floors.id')
                ->where('tables.id', $request->table_id)
                ->where('floors.warehouse_id', $warehouse->id)
                ->where('tables.is_active', 1)
                ->select('tables.id', 'tables.name', 'tables.floor_id')
                ->first();
        }

        $qr_catelog_setting = \App\Models\QrCatelogSetting::latest()->first();

        // Build categories that actually have active products in this warehouse.
        // This prevents empty categories from appearing in the nav.
        $productWarehouseQuery = DB::table('product_warehouse')
            ->where('warehouse_id', $warehouse->id);

        if ($qr_catelog_setting && $qr_catelog_setting->show_stock_out_product == 0) {
            $productWarehouseQuery->where('qty', '>', 0);
        }

        $productIds = $productWarehouseQuery->pluck('product_id');

        $activeCategoryIds = Product::whereIn('id', $productIds)
            ->where('is_active', 1)
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->filter()
            ->unique();

        $categories = Category::whereIn('id', $activeCategoryIds)
            ->where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('public_menu.index', compact(
            'warehouse',
            'table',
            'categories',
            'general_setting',
            'slug'
        ));
    }

    // ─── AJAX products endpoint ───────────────────────────────────────────────

    public function getProducts(Request $request, $slug)
    {
        $warehouse  = $this->resolveWarehouse($slug);
        $categoryId = (int) $request->query('category_id');
        $page       = max(1, (int) $request->query('page', 1));
        $limit      = 5;
        $general_setting = cache()->get('general_setting');

        $productIds = DB::table('product_warehouse')
            ->where('warehouse_id', $warehouse->id)
            ->pluck('product_id');

        $query = Product::join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->where('product_warehouse.warehouse_id', $warehouse->id)
            ->where('products.is_active', 1)
            ->with(['variant' => function ($q) {
                $q->orderBy('product_variants.position');
            }])
            ->select(
                'products.id',
                'products.name',
                'products.image',
                'products.price',
                'products.category_id',
                'products.is_variant',
                'products.variant_option',
                'products.variant_value',
                DB::raw('SUM(product_warehouse.qty) as qty')
            )
            ->groupBy('products.id');

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        } else {
            // null category – uncategorised products
            $query->whereNull('products.category_id');
        }

        $qr_catelog_setting = \App\Models\QrCatelogSetting::latest()->first();
        if ($qr_catelog_setting && $qr_catelog_setting->show_stock_out_product == 0) {
            $query->having('qty', '>', 0);
        }

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        $data = $paginated->map(function (Product $product) {
            // Support comma-separated image filenames (multi-image products)
            $rawImages  = $product->image ? array_filter(array_map('trim', explode(',', $product->image))) : [];
            $imageUrls  = [];
            foreach ($rawImages as $filename) {
                if ($filename && file_exists(public_path('images/product/' . $filename))) {
                    $imageUrls[] = asset('images/product/' . $filename);
                }
            }
            $primaryImage = $imageUrls[0] ?? null;

            return [
                'id'                => $product->id,
                'name'              => $product->name,
                'price'             => (float) $product->price,
                'image'             => $primaryImage,
                'images'            => $imageUrls,
                'qty'               => (float) $product->qty,
                'is_variant'        => (bool) $product->is_variant,
                'has_variants'      => count($this->buildVariantsData($product)) > 0,
                'variants_data'     => $this->buildVariantsData($product),
            ];
        });

        return response()->json([
            'data'         => $data->values(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
        ]);
    }
}
