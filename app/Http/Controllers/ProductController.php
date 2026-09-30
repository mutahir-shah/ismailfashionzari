<?php

namespace App\Http\Controllers;

use DNS1D;
use Keygen\Keygen;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Barcode;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Traits\TenantInfo;
use App\Models\CustomField;
use App\Traits\CacheForget;
use Illuminate\Support\Str;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use Illuminate\Validation\Rule;
use App\Models\Product_Warehouse;
use App\Services\ProductImportImageService;
use App\Services\WarehouseAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ProductController extends Controller
{
    use CacheForget;
    use TenantInfo;

    private const PRODUCT_LIST_MAX_PAGE_SIZE = 250;

    public static function normalizeProductImportHeaders(array $headers): array
    {
        $aliases = [
            'profitmargin' => 'profit_margin', 'productdetails' => 'product_details',
            'variantvalue' => 'variant_value', 'variantname' => 'variant_name',
            'itemcode' => 'item_code', 'additionalcost' => 'additional_cost',
            'additionalprice' => 'additional_price', 'unitcode' => 'unit_code',
            'wholesaleprice' => 'wholesale_price',
        ];

        return array_map(function ($value) use ($aliases) {
            $normalized = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($value)));
            return $aliases[$normalized] ?? $normalized;
        }, $headers);
    }

    public static function parseProductImportVariantValue(string $value): array
    {
        if (trim($value) === '') return [[], []];
        preg_match_all('/\s*([^,\[\]]+)\[([^\]]+)\]\s*(?:,|$)/', $value, $matches);
        if ($matches[0] === [] || preg_replace('/\s*,?\s*/', '', implode('', $matches[0])) !== preg_replace('/\s*,?\s*/', '', $value)) {
            throw new \InvalidArgumentException('variant_value must use Option[Value/Value] syntax.');
        }
        $options = array_map('trim', $matches[1]);
        $values = array_map(fn ($item) => implode(',', array_map('trim', explode('/', $item))), $matches[2]);
        return [$options, $values];
    }

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id ?? 0);
        if ($role && $role->hasPermissionTo('products-index')) {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();

            $warehouse_id = 0;
            $product_type = 'all';
            $brand_id = 0;
            $category_id = 0;
            $unit_id = 0;
            $tax_id = 0;
            $imeiorvariant = 0;
            $stock_filter = 'all';

            if ($request->input('warehouse_id')) {
                $warehouse_id = $request->input('warehouse_id');
            } else if (Auth::user()->warehouse_id) {
                $warehouse_id = Auth::user()->warehouse_id;
            }
            if ($request->input('product_type')) $product_type = $request->input('product_type');
            if ($request->input('brand_id')) $brand_id = $request->input('brand_id');
            if ($request->input('category_id')) $category_id = $request->input('category_id');
            if ($request->input('unit_id')) $unit_id = $request->input('unit_id');
            if ($request->input('tax_id')) $tax_id = $request->input('tax_id');
            if ($request->input('imeiorvariant')) $imeiorvariant = $request->input('imeiorvariant');
            if ($request->input('stock_filter')) $stock_filter = $request->input('stock_filter');

            $permissions = Role::findByName($role->name)->permissions;

            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $role_id = $role->id;
            $numberOfProduct = DB::table('products')->where('is_active', true)->count();
            $custom_fields = CustomField::where([
                ['belongs_to', 'product'],
                ['is_table', true]
            ])->pluck('name');
            $field_name = [];
            foreach ($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            return view('backend.product.index', compact('warehouse_id', 'product_type', 'brand_id', 'category_id', 'unit_id', 'tax_id', 'imeiorvariant', 'stock_filter', 'all_permission', 'role_id', 'numberOfProduct', 'custom_fields', 'field_name', 'lims_warehouse_list', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function productData(Request $request)
    {
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'image',
            3 => 'code',
            4 => 'brand_id',
            5 => 'category_id',
            6 => 'qty',
            7 => 'unit_id',
            8 => 'price',
            9 => 'cost',
        ];

        $filtered_data = [
            'warehouse_id' => $request->input('warehouse_id'),
            'product_type' => $request->input('product_type'),
            'brand_id'     => $request->input('brand_id'),
            'category_id'  => $request->input('category_id'),
            'unit_id'      => $request->input('unit_id'),
            'tax_id'       => $request->input('tax_id'),
            'is_imei'      => $request->input('imeiorvariant') == 'imei' ? "1" : "0",
            'is_variant'   => $request->input('imeiorvariant') == 'variant' ? "1" : "0",
            'is_batch'     => $request->input('imeiorvariant') == 'batch' ? "1" : "0",
            // 'stock_filter'   => $request->input('stock_filter'),
        ];

        $is_recipe = $request->input('is_recipe');
        $warehouse_id = max(0, (int) $filtered_data['warehouse_id']);
        $warehouseAccess = app(WarehouseAccessService::class);
        $warehouseClassification = $warehouseAccess->classification();
        abort_if(
            in_array($warehouseClassification, [WarehouseAccessService::PORTAL_IDENTITY, WarehouseAccessService::INVALID_OPERATIONAL], true),
            403,
            'Warehouse access denied.'
        );
        if ($warehouseAccess->isRestricted()) {
            $warehouse_id = (int) $warehouseAccess->warehouseId();
        }

        // DataTables pagination/sorting
        $requestedLimit = (int) $request->input('length', 10);
        $limit = $requestedLimit > 0
            ? min($requestedLimit, self::PRODUCT_LIST_MAX_PAGE_SIZE)
            : self::PRODUCT_LIST_MAX_PAGE_SIZE;
        $start = max(0, (int) $request->input('start', 0));
        $orderColumn = (int) $request->input('order.0.column', 1);
        $order = 'products.' . ($columns[$orderColumn] ?? 'name');
        $dir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Custom fields
        $custom_fields = CustomField::where([
            ['belongs_to', 'product'],
            ['is_table', true]
        ])->pluck('name');
        $field_names = [];
        foreach ($custom_fields as $fieldName) {
            $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        }

        // Base query with relation
        $statusFilter = $request->input('status', 'active') ?: 'active';
        $stockFilter = $request->input('stock_filter', 'all') ?: 'all';
        $baseQuery = Product::with('category', 'brand', 'unit', 'tax');

        if ($statusFilter === 'inactive') {
            $baseQuery->where('products.is_active', 3);
        } elseif ($statusFilter === 'all') {
            $baseQuery->whereIn('products.is_active', [1, 3]);
        } else {
            $baseQuery->where('products.is_active', 1);
        }

        if ($stockFilter === 'with') {
            $baseQuery->where('products.qty', '>', 0);
        } elseif ($stockFilter === 'without') {
            $baseQuery->where('products.qty', '<=', 0);
        }

        if ($is_recipe) {
            $baseQuery->where('is_recipe', 1);
        }

        // Apply filters
        if (!empty($filtered_data['product_type']) && $filtered_data['product_type'] != 'all') {
            $baseQuery->where('type', $filtered_data['product_type']);
        }
        if (!empty($filtered_data['brand_id']) && $filtered_data['brand_id'] != '0') {
            $baseQuery->where('brand_id', $filtered_data['brand_id']);
        }
        if (!empty($filtered_data['category_id']) && $filtered_data['category_id'] != '0') {
            $baseQuery->where('category_id', $filtered_data['category_id']);
        }
        if (!empty($filtered_data['unit_id']) && $filtered_data['unit_id'] != '0') {
            $baseQuery->where('unit_id', $filtered_data['unit_id']);
        }
        if (!empty($filtered_data['tax_id']) && $filtered_data['tax_id'] != '0') {
            $baseQuery->where('tax_id', $filtered_data['tax_id']);
        }
        if (!empty($filtered_data['is_imei']) && $filtered_data['is_imei'] != '0') {
            $baseQuery->where('is_imei', $filtered_data['is_imei']);
        }
        if (!empty($filtered_data['is_variant']) && $filtered_data['is_variant'] != '0') {
            $baseQuery->where('is_variant', $filtered_data['is_variant']);
        }
        if (!empty($filtered_data['is_batch']) && $filtered_data['is_batch'] != '0') {
            $baseQuery->where('is_batch', $filtered_data['is_batch']);
        }

        $totalData = (clone $baseQuery)->count();
        $totalFiltered = $totalData;

        // Clone query for actual data retrieval
        $query = clone $baseQuery;

        $search = $request->input('search.value');

        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $query->where(function ($q) use ($field_names, $searchTerm) {
                $q->where('products.name', 'LIKE', $searchTerm)
                    ->orWhere('products.code', 'LIKE', $searchTerm)
                    ->orWhereIn('products.id', ProductVariant::select('product_id')->where('item_code', 'LIKE', $searchTerm))
                    ->orWhereIn('products.id', ProductPurchase::select('product_id')->where('imei_number', 'LIKE', $searchTerm))
                    ->orWhereIn('products.brand_id', Brand::select('id')->where('title', 'LIKE', $searchTerm))
                    ->orWhereIn('products.category_id', Category::select('id')->where('name', 'LIKE', $searchTerm));

                foreach ($field_names as $field_name) {
                    $safeField = str_replace('`', '', $field_name);
                    $q->orWhere("products.$safeField", 'LIKE', $searchTerm);
                }
            });

            $totalFiltered = (clone $query)->distinct()->count('products.id');
        }

        // Pagination + ordering
        $products = $query
            ->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->get();
        $productIds = $products->pluck('id');

        $avgCosts = $productIds->isEmpty()
            ? collect()
            : ProductPurchase::whereIn('product_id', $productIds)
                ->selectRaw('
                            product_id,
                            COALESCE(SUM(qty * net_unit_cost) / NULLIF(SUM(qty),0), 0) as avg_cost
                        ')
                ->groupBy('product_id')
                ->pluck('avg_cost', 'product_id');

        $standardProductIds = $products
            ->where('type', 'standard')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();
        $standardQtyQuery = Product_Warehouse::whereIn('product_id', $standardProductIds);
        if ($warehouse_id > 0) {
            $standardQtyQuery->where('warehouse_id', $warehouse_id);
        }
        $standardQty = $standardProductIds->isEmpty()
            ? collect()
            : $standardQtyQuery
                ->selectRaw('product_id, COALESCE(SUM(qty), 0) as total_qty')
                ->groupBy('product_id')
                ->pluck('total_qty', 'product_id');

        [$comboQty, $comboUnitNames] = $this->buildProductListComboData(
            $products->where('type', 'combo'),
            $warehouse_id
        );

        $decimal = (int) gen_setting()->decimal;
        $canViewStockCost = (int) Auth::user()->role_id <= 2;
        $allPermissions = (array) $request->input('all_permission', []);
        $socialShare = $this->productListSocialShareContext();

        // Data formatting
        $data = [];
        foreach ($products as $key => $product) {
            $nestedData = [];
            $nestedData['id'] = $product->id;
            $nestedData['key'] = $key;

            // Image
            $images = array_values(array_filter(array_map('trim', explode(',', $product->image ?? ''))));
            $product_image = !empty($images) ? htmlspecialchars($images[0]) : null;
            if ($product_image && $product_image != 'zummXD2dvAtI.png') {
                if (file_exists("public/images/product/small/" . $product_image)) {
                    $nestedData['image'] = '<img src="' . url('images/product/small', $product_image) . '" height="50" width="50">';
                } else {
                    $nestedData['image'] = '<img src="' . url('images/product', $product_image) . '" height="50" width="50">';
                }
            } else {
                $nestedData['image'] = '<img src="images/zummXD2dvAtI.png" height="50" width="50">';
            }

            // Visible column (UI)
            $nestedData['name'] = '<div class="d-flex align-items-center">' . $nestedData['image'] . '<span style="color:#111;margin:0 10px;">' . $product->name . '</span></div>';

            // Export-only column (hidden)
            $nestedData['image_path'] = url('images/product', $product_image);

            $nestedData['code'] = $product->code;
            $nestedData['brand'] = $product->brand->title ?? "N/A";
            $nestedData['category'] = $product->category->name ?? "N/A";

            // Quantity (respecting warehouse)
            if ($product->type == 'combo') {
                $nestedData['qty'] = $comboQty[$product->id] ?? 0;
            } elseif ($product->type == 'standard') {
                $nestedData['qty'] = $standardQty[(string) $product->id] ?? 0;
            } else {
                $nestedData['qty'] = $product->qty;
            }

            $nestedData['unit'] = $product->unit->unit_name ?? 'N/A';

            if ($product->type == 'combo') {
                $nestedData['wastage_percent'] = $product->wastage_percent ?? 'N/A';
                $nestedData['combo_unit'] = $comboUnitNames[$product->id] ?? '';
            } else {
                $nestedData['combo_unit_id'] = 'N/A';
            }

            if (isset($product->wholesale_price)) {
                $wholesale_price = '<br>(<small>wholesale</small>: ' . $product->wholesale_price . ')';
            } else {
                $wholesale_price = '';
            }

            $nestedData['price'] = number_format($product->price, $decimal) . $wholesale_price;

            // Imported products have no purchase history yet; their configured cost is
            // the authoritative listing/export value until a weighted purchase cost exists.
            $avg_cost = $avgCosts[$product->id] ?? $product->cost;
            $nestedData['cost'] = number_format($avg_cost, $decimal, '.', '');

            $stock_worth_price = format_currency($nestedData['qty'] * $product->price);
            if ($canViewStockCost)
                $stock_worth_cost = format_currency($nestedData['qty'] * $avg_cost);
            else
                $stock_worth_cost = '****';

            $nestedData['stock_worth'] = $stock_worth_price . ' / ' . $stock_worth_cost;


            // Custom fields values
            foreach ($field_names as $field_name) {
                $nestedData[$field_name] = $product->$field_name;
            }

            // Options dropdown
            $nestedData['options'] = '<div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __("db.action") . '
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                    <li>
                        <button type="button" class="btn btn-link view"><i class="ti ti-eye"></i> ' . __('db.View') . '</button>
                    </li>';

            if (in_array("products-edit", $allPermissions))
                $nestedData['options'] .= '<li>
                    <a href="' . route('products.edit', $product->id) . '" class="btn btn-link"><i class="ti ti-edit"></i> ' . __('db.edit') . '</a>
                </li>';

            if (in_array("product_history", $allPermissions))
                $nestedData['options'] .= '<form action="' . route('products.history') . '" method="GET">
                    <li>
                        <input type="hidden" name="product_id" value="' . $product->id . '" />
                        <button type="submit" class="btn btn-link"><i class="ti ti-checklist"></i> ' . __("db.Product History") . '</button>
                    </li></form>';

            if (in_array("print_barcode", $allPermissions)) {
                $product_info = $product->code . ' (' . $product->name . ')';
                $nestedData['options'] .= '<form action="' . route('product.printBarcode') . '" method="GET" target="_blank">
                    <li>
                        <input type="hidden" name="data" value="' . $product_info . '" />
                        <button type="submit" class="btn btn-link"><i class="ti ti-printer"></i> ' . __("db.print_barcode") . '</button>
                    </li></form>';
            }

            if ($socialShare) {
                $link = $socialShare['resolver']->resolve($product);
                $text = urlencode(str_replace(
                    ['{product_name}', '{product_link}'],
                    [$product->name, $link],
                    $socialShare['template']
                ));
                $nestedData['options'] .= '<li>
                    <a href="https://web.whatsapp.com/send/?text=' . $text . '" target="_blank" class="btn btn-link"><i class="ti ti-brand-whatsapp"></i> ' . __('db.WA: Share Product') . '</a>
                </li>';
            }

            if (in_array("products-edit", $allPermissions)) {
                if ((int)$product->is_active === 1) {
                    $nestedData['options'] .= '<li>
                        <button type="button" class="btn btn-link toggle-active" data-id="' . $product->id . '" data-status="deactivate"><i class="ti ti-power"></i> ' . __('db.Deactivate') . '</button>
                    </li>';
                } elseif ((int)$product->is_active === 3) {
                    $nestedData['options'] .= '<li>
                        <button type="button" class="btn btn-link toggle-active" data-id="' . $product->id . '" data-status="activate"><i class="ti ti-power"></i> ' . __('db.Activate') . '</button>
                    </li>';
                }
            }

            if (in_array("products-delete", $allPermissions))
                $nestedData['options'] .= '<form action="' . route("products.destroy", $product->id) . '" method="POST">' . csrf_field() . '' . method_field("DELETE") . '
                    <li>
                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> ' . __("db.delete") . '</button>
                    </li></form>
                </ul>
            </div>';


            // Extra product details
            $tax = $product->tax->name ?? "N/A";
            $tax_method = $product->tax_method == 1 ? __('db.Exclusive') : __('db.Inclusive');

            $nestedData['product'] = array(
                '[ "' . $product->type . '"',
                ' "' . $product->name . '"',
                ' "' . $product->code . '"',
                ' "' . $nestedData['brand'] . '"',
                ' "' . $nestedData['category'] . '"',
                ' "' . $nestedData['unit'] . '"',
                ' "' . $product->cost . '"',
                ' "' . $product->price . '"',
                ' "' . $tax . '"',
                ' "' . $tax_method . '"',
                ' "' . $product->alert_quantity . '"',
                ' "' . preg_replace('/\s+/S', " ", $product->product_details) . '"',
                ' "' . $product->id . '"',
                ' "' . $product->product_list . '"',
                ' "' . $product->variant_list . '"',
                ' "' . $product->qty_list . '"',
                ' "' . $product->price_list . '"',
                ' "' . $nestedData['qty'] . '"',
                ' "' . $product->image . '"',
                ' "' . $product->is_variant . '"',
                '"' . @$nestedData['combo_unit'] . '"',
                '"' . @$nestedData['wastage_percent'] . '"]'
            );

            $data[] = $nestedData;
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ]);
    }

    public function create()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-add')) {
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $numberOfProduct = Product::where('is_active', true)->count();
            $custom_fields = CustomField::where('belongs_to', 'product')->get();
            $kitchen_list = collect();
            $menu_type_list = collect();
            if (is_dir(base_path('Modules/Restaurant')) && \Illuminate\Support\Facades\Schema::hasTable('kitchens')) {
                $kitchen_list = DB::table('kitchens')->where('is_active', 1)->get();
                $menu_type_list = \Illuminate\Support\Facades\Schema::hasTable('menu_type') ? DB::table('menu_type')->where('is_active', 1)->get() : collect();
            }

            return view('backend.product.create', compact('kitchen_list', 'menu_type_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct', 'custom_fields'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    private function diffSizeOfImagePathExistOrCreate()
    {
        if (!file_exists(public_path("images/product/xlarge")) && !is_dir(public_path("images/product/xlarge"))) {
            mkdir(public_path("images/product/xlarge"), 0755, true);
        }
        if (!file_exists(public_path("images/product/large")) && !is_dir(public_path("images/product/large"))) {
            mkdir(public_path("images/product/large"), 0755, true);
        }
        if (!file_exists(public_path("images/product/medium")) && !is_dir(public_path("images/product/medium"))) {
            mkdir(public_path("images/product/medium"), 0755, true);
        }
        if (!file_exists(public_path("images/product/small")) && !is_dir(public_path("images/product/small"))) {
            mkdir(public_path("images/product/small"), 0755, true);
        }
    }

    private function diffSizeImageStore($image, string $imageName)
    {
        $image->resize(1000, 1250)->save(public_path('images/product/xlarge/' . $imageName));
        $image->resize(500, 500)->save(public_path('images/product/large/' . $imageName));
        $image->resize(250, 250)->save(public_path('images/product/medium/' . $imageName));
        $image->resize(100, 100)->save(public_path('images/product/small/' . $imageName));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => [
                'max:255',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except('image', 'file');

            // handle warranty and guarantee
            if (!isset($data['warranty'])) {
                unset($data['warranty']);
                unset($data['warranty_type']);
            }
            if (!isset($data['guarantee'])) {
                unset($data['guarantee']);
                unset($data['guarantee_type']);
            }

            if (isset($data['is_variant'])) {
                $data['variant_option'] = json_encode(array_unique($data['variant_option']));
                $data['variant_value'] = json_encode(array_unique($data['variant_value']));
            } else {
                $data['variant_option'] = $data['variant_value'] = null;
            }

            $data['name'] = preg_replace('/[\n\r]/', "<br>", htmlspecialchars(trim($data['name']), ENT_QUOTES));

            if(in_array('ecommerce', explode(',',config('addons')))) {
                $baseSlug = Str::slug($data['name'], '-');
                $baseSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $baseSlug);
                $baseSlug = str_replace( '\/', '/', $baseSlug );
                $count = Product::where('slug', 'LIKE', "$baseSlug%")->count();
                $data['slug'] = $count ? "{$baseSlug}-{$count}" : $baseSlug;
            }

            if (in_array('restaurant', explode(',', gen_setting()->modules))) {
                $data['kitchen_id'] = $request->input('kitchen_id');
                $data['menu_type'] = implode(',', $request->input('menu_type', []));
                unset($data['extras'], $data['is_addon']);
            }

            if ($data['type'] == 'combo' || (isset($data['is_recipe']) && $data['is_recipe'] == 1)) {

                $data['product_list'] = implode(",", $data['product_id']);
                $data['variant_list'] = implode(",", $data['variant_id']);
                $data['qty_list'] = implode(",", $data['product_qty']);
                $data['price_list'] = implode(",", $data['unit_price']);
                $data['wastage_percent'] = implode(",", $data['wastage_percent']);
                $data['combo_unit_id'] = implode(",", $data['combo_unit_id']);

                //$data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;
            } elseif ($data['type'] == 'digital' || $data['type'] == 'service')
                $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

            $data['product_details'] = str_replace('"', '@', $data['product_details']);

            if ($data['starting_date'])
                $data['starting_date'] = date('Y-m-d', strtotime($data['starting_date']));
            if ($data['last_date'])
                $data['last_date'] = date('Y-m-d', strtotime($data['last_date']));
            $data['is_active'] = true;
            $images = $request->image;
            $image_names = [];
            if ($images) {
                // Ensure the necessary directories exist using public_path()
                $this->diffSizeOfImagePathExistOrCreate();

                foreach ($images as $key => $image) {
                    $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                    $imageName = date("Ymdhis") . ($key + 1);

                    // Handle multi-tenant logic if necessary
                    if (!config('database.connections.saleprosaas_landlord')) {
                        $imageName = $imageName . '.' . $ext;
                    } else {
                        $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    }


                    $image->move(public_path('images/product'), $imageName);

                    $manager = new ImageManager(new GdDriver());
                    $image = $manager->read(public_path('images/product/' . $imageName));

                    $this->diffSizeImageStore($image, $imageName);

                    // Collect image names for saving in the database
                    $image_names[] = $imageName;
                }

                // Save the image names in the database
                $data['image'] = implode(",", $image_names);
            } else {
                $data['image'] = 'zummXD2dvAtI.png';
            }
            $file = $request->file;
            if ($file) {
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move(public_path('product/files'), $fileName);
                $data['file'] = $fileName;
            }
            if (!isset($data['is_sync_disable']) && \Schema::hasColumn('products', 'is_sync_disable'))
                $data['is_sync_disable'] = null;
            //return $data;
            if (!$data['profit_margin']) {
                $data['profit_margin'] = 0;
            }
            $lims_product_data = Product::create($data);

            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'product')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if (isset($data[$field_name])) {
                    if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if (count($custom_field_data))
                DB::table('products')->where('id', $lims_product_data->id)->update($custom_field_data);
            //dealing with initial stock and auto purchase
            $initial_stock = 0;
            if (isset($data['is_initial_stock']) && !isset($data['is_variant']) && !isset($data['is_batch'])) {
                foreach ($data['stock_warehouse_id'] as $key => $warehouse_id) {
                    $stock = $data['stock'][$key];
                    if ($stock > 0) {
                        $this->autoPurchase($lims_product_data, $warehouse_id, $stock);
                        $initial_stock += $stock;
                    }
                }
            }
            if ($initial_stock > 0) {
                $lims_product_data->qty += $initial_stock;
                $lims_product_data->save();
            }
            //dealing with product variant
            if (!isset($data['is_batch']))
                $data['is_batch'] = null;
            $variant_ids = [];
            if (isset($data['is_variant'])) {
                foreach ($data['variant_name'] as $key => $variant_name) {
                    $lims_variant_data = Variant::firstOrCreate(['name' => $data['variant_name'][$key]]);
                    $variant_ids[] = $lims_variant_data->id;
                    $product_variant = ProductVariant::firstOrNew([
                        'product_id' => $lims_product_data->id,
                        'variant_id' => $lims_variant_data->id,
                        'item_code' => $data['item_code'][$key],
                        'additional_cost' => $data['additional_cost'][$key],
                        'additional_price' => $data['additional_price'][$key],
                        'qty' => 0,
                    ]);
                    $product_variant->position = $key + 1;
                    $product_variant->save();
                }
            }
            if (isset($data['is_diffPrice'])) {
                foreach ($data['diff_price'] as $key => $diff_price) {
                    if ($diff_price) {
                        Product_Warehouse::firstOrCreate([
                            "product_id" => $lims_product_data->id,
                            "warehouse_id" => $data["warehouse_id"][$key],
                            "qty" => 0,
                            "price" => $diff_price
                        ]);
                    }
                }
            } elseif (!isset($data['is_initial_stock']) && !isset($data['is_batch']) && config('without_stock') == 'yes') {
                $warehouse_ids = Warehouse::where('is_active', true)->pluck('id');
                foreach ($warehouse_ids as $warehouse_id) {
                    if (count($variant_ids)) {
                        foreach ($variant_ids as $variant_id) {
                            Product_Warehouse::firstOrCreate([
                                "product_id" => $lims_product_data->id,
                                "variant_id" => $variant_id,
                                "warehouse_id" => $warehouse_id,
                                "qty" => 0,
                            ]);
                        }
                    } else {
                        Product_Warehouse::firstOrCreate([
                            "product_id" => $lims_product_data->id,
                            "warehouse_id" => $warehouse_id,
                            "qty" => 0,
                        ]);
                    }
                }
            }
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');

            if (\Module::has('SocialCommerce') && \Module::find('SocialCommerce')->isEnabled()) {
                $scSetting = \Modules\SocialCommerce\Entities\SocialProductSetting::firstOrNew(['product_id' => $lims_product_data->id]);
                $scSetting->is_published = $request->has('sc_is_published') ? 1 : 0;
                $scSetting->social_title = $request->input('sc_social_title');
                $scSetting->social_description = $request->input('sc_social_description');

                if ($request->hasFile('sc_social_image')) {
                    $sc_image = $request->file('sc_social_image');
                    $sc_ext = pathinfo($sc_image->getClientOriginalName(), PATHINFO_EXTENSION);
                    $sc_imageName = date("Ymdhis") . '_sc.' . $sc_ext;
                    if (!file_exists(public_path('images/product/social'))) {
                        mkdir(public_path('images/product/social'), 0777, true);
                    }
                    $sc_image->move(public_path('images/product/social'), $sc_imageName);
                    $scSetting->social_image = $sc_imageName;
                }
                $scSetting->save();
            }

            DB::commit();

            // DISPATCH LOW STOCK NOTIFICATION ON BASELINE CATALOG ENTRIES
            $this->checkAndNotifyProductStock($lims_product_data);

            if($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Product created successfully']);
            }
            return redirect('products')->with('create_message', 'Product created successfully');
        
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product creation failed: ' . $e->getMessage());
            if($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('db.product_creation_failed')]);
            }
            return redirect()->back()->with('not_permitted', __('db.product_creation_failed'));
        }
    }

    public function autoPurchase($product_data, $warehouse_id, $stock)
    {
        $data['reference_no'] = 'pr-' . date("Ymd") . '-' . date("his");
        $data['user_id'] = Auth::id();
        $data['warehouse_id'] = $warehouse_id;
        $data['item'] = 1;
        $data['total_qty'] = $stock;
        $data['total_discount'] = 0;
        $data['status'] = 1;
        $data['payment_status'] = 2;
        if ($product_data->tax_id) {
            $tax_data = DB::table('taxes')->select('rate')->find($product_data->tax_id);
            if ($product_data->tax_method == 1) {
                $net_unit_cost = number_format($product_data->cost, 2, '.', '');
                $tax = number_format($product_data->cost * $stock * ($tax_data->rate / 100), 2, '.', '');
                $cost = number_format(($product_data->cost * $stock) + $tax, 2, '.', '');
            } else {
                $net_unit_cost = number_format((100 / (100 + $tax_data->rate)) * $product_data->cost, 2, '.', '');
                $tax = number_format(($product_data->cost - $net_unit_cost) * $stock, 2, '.', '');
                $cost = number_format($product_data->cost * $stock, 2, '.', '');
            }
            $tax_rate = $tax_data->rate;
            $data['total_tax'] = $tax;
            $data['total_cost'] = $cost;
        } else {
            $data['total_tax'] = 0.00;
            $data['total_cost'] = number_format($product_data->cost * $stock, 2, '.', '');
            $net_unit_cost = number_format($product_data->cost, 2, '.', '');
            $tax_rate = 0.00;
            $tax = 0.00;
            $cost = number_format($product_data->cost * $stock, 2, '.', '');
        }

        $data['order_tax'] = 0;
        $data['grand_total'] = $data['total_cost'];
        $data['paid_amount'] = $data['grand_total'];
        $data['purchase_type'] = 'initial_stock';

        DB::beginTransaction();
        try {
            $product_warehouse_data = Product_Warehouse::select('id', 'qty')
                                    ->where([
                                        ['product_id', $product_data->id],
                                        ['warehouse_id', $warehouse_id]
                                    ])->first();
            if($product_warehouse_data) {
                $product_warehouse_data->qty += $stock;
                $product_warehouse_data->save();
            }
            else {
                $lims_product_warehouse_data = new Product_Warehouse();
                $lims_product_warehouse_data->product_id = $product_data->id;
                $lims_product_warehouse_data->warehouse_id = $warehouse_id;
                $lims_product_warehouse_data->qty = $stock;
                $lims_product_warehouse_data->save();
            }

            //insetting data to purchase table
            $purchase_data = Purchase::create($data);
            //inserting data to product_purchases table
            ProductPurchase::create([
                'purchase_id' => $purchase_data->id,
                'product_id' => $product_data->id,
                'qty' => $stock,
                'recieved' => $stock,
                'purchase_unit_id' => $product_data->unit_id,
                'net_unit_cost' => $net_unit_cost,
                'discount' => 0,
                'tax_rate' => $tax_rate,
                'tax' => $tax,
                'total' => $cost
            ]);
            //inserting data to payments table
            $syntheticPayment = Payment::create([
                'payment_reference' => 'ppr-' . date("Ymd") . '-' . date("his"),
                'user_id' => Auth::id(),
                'purchase_id' => $purchase_data->id,
                'account_id' => 0,
                'amount' => $data['grand_total'],
                'change' => 0,
                'paying_method' => 'Cash',
                'accounting_status' => 'pending',
            ]);

            // Before activation the activation snapshot will recognize this stock.
            // After activation it is posted as Inventory / Opening Balance Equity.
            $result = app(\App\Services\AccountingService::class)->recordInitialStock($purchase_data);
            $purchase_data->accounting_status = $result->isPosted() ? 'posted' : ($result->isSuccess() ? 'pending' : 'failed');
            $purchase_data->saveQuietly();

            DB::commit(); // ✅ Ensure atomic operations

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Purchase Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function history(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('product_history')) {
            if ($request->input('warehouse_id'))
                $warehouse_id = $request->input('warehouse_id');
            else
                $warehouse_id = 0;

            if ($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            } else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d'))))));
                $ending_date = date("Y-m-d");
            }
            $product_id = $request->input('product_id');
            $product_data = Product::select('name', 'code')->find($product_id);
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            return view('backend.product.history', compact('starting_date', 'ending_date', 'warehouse_id', 'product_id', 'product_data', 'lims_warehouse_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function saleHistoryData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('sales')
            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
            ->whereNull('sales.deleted_at')
            ->where('product_sales.product_id', $product_id);

        if ($request->input('starting_date')) {
            $q = $q->whereDate('sales.created_at', '>=', $request->input('starting_date'));
        }
        if ($request->input('ending_date')) {
            $q = $q->whereDate('sales.created_at', '<=', $request->input('ending_date'));
        }
        if ($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('sales.user_id', Auth::id());

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length', 10);
        else
            $limit = $totalData;
        $start = $request->input('start', 0);
        $orderCol = $request->input('order.0.column');
        $order = isset($columns[$orderCol]) ? 'sales.' . $columns[$orderCol] : 'sales.created_at';
        $dir = $request->input('order.0.dir', 'desc') ?: 'desc';
        $q = $q->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('units', 'units.id', '=', 'product_sales.sale_unit_id')
            ->select('sales.id', 'sales.reference_no', 'sales.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_sales.qty', 'product_sales.sale_unit_id', 'product_sales.total', 'units.unit_code', 'units.operator', 'units.operation_value')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if (empty($request->input('search.value'))) {
            $sales = $q->get();
        } else {
            $search = $request->input('search.value');
            $parsed_date = date('Y-m-d', strtotime(str_replace('/', '-', $search)));

            $q->where(function ($query) use ($search, $parsed_date) {
                $query->whereDate('sales.created_at', '=', $parsed_date)
                    ->orWhere('sales.reference_no', 'LIKE', "%{$search}%");
            });

            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q->where('sales.user_id', Auth::id());
            }

            $sales = $q->get();

            $totalFiltered = $q->count();
        }
        $data = array();
        if (!empty($sales)) {
            foreach ($sales as $key => $sale) {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $nestedData['customer'] = $sale->customer_name . ' [' . ($sale->customer_number) . ']';

                $nestedData['qty'] = number_format($sale->qty, config('decimal'));
                if ($sale->sale_unit_id && $sale->unit_code) {
                    $nestedData['qty'] .= ' ' . $sale->unit_code;
                }
                $nestedData['qty_value'] = (float) $sale->qty;
                $nestedData['operator'] = $sale->operator ?? '*';
                $nestedData['operation_value'] = (float) ($sale->operation_value ?? 1);
                $nestedData['unit_name'] = $sale->unit_code ?? '';

                $nestedData['qty_base'] = $sale->operator == '*'
                    ? $sale->qty * ($sale->operation_value ?: 1)
                    : $sale->qty / ($sale->operation_value ?: 1);

                $nestedData['unit_price'] = number_format(($sale->total / $sale->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($sale->total, config('decimal'));
                $nestedData['sub_total_value'] = (float) $sale->total;
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        return response()->json($json_data);
    }

    public function purchaseHistoryData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('purchases')
            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->where('product_purchases.product_id', $product_id)
            ->whereNull('purchases.deleted_at')
            ->whereDate('purchases.created_at', '>=', $request->input('starting_date'))
            ->whereDate('purchases.created_at', '<=', $request->input('ending_date'));
        if ($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('purchases.user_id', Auth::id());

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.' . $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('units', 'units.id', '=', 'product_purchases.purchase_unit_id')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if (empty($request->input('search.value'))) {
            $purchases = $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total', 'units.unit_code', 'units.operator', 'units.operation_value')->get();
        } else {
            $search = $request->input('search.value');
            $q = $q->whereDate('purchases.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $purchases =  $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total', 'units.unit_code', 'units.operator', 'units.operation_value')
                    ->orwhere([
                        ['purchases.reference_no', 'LIKE', "%{$search}%"],
                        ['purchases.user_id', Auth::id()]
                    ])->get();
                $totalFiltered = $q->orwhere([
                    ['purchases.reference_no', 'LIKE', "%{$search}%"],
                    ['purchases.user_id', Auth::id()]
                ])->count();
            } else {
                $purchases =  $q->select('purchases.id', 'purchases.reference_no', 'purchases.created_at', 'purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id', 'product_purchases.total', 'units.unit_code', 'units.operator', 'units.operation_value')
                    ->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")
                    ->get();
                $totalFiltered = $q->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if (!empty($purchases)) {
            foreach ($purchases as $key => $purchase) {
                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at));
                $nestedData['reference_no'] = $purchase->reference_no;
                $nestedData['warehouse'] = $purchase->warehouse_name;
                if ($purchase->supplier_id)
                    $nestedData['supplier'] = $purchase->supplier_name . ' [' . ($purchase->supplier_number) . ']';
                else
                    $nestedData['supplier'] = 'N/A';
                $nestedData['qty'] = number_format($purchase->qty, config('decimal'));

                if ($purchase->purchase_unit_id && $purchase->unit_code) {
                    $nestedData['qty'] .= ' ' . $purchase->unit_code;
                }

                $nestedData['qty_value'] = (float) $purchase->qty;
                $nestedData['operator'] = $purchase->operator ?? '*';
                $nestedData['operation_value'] = (float) ($purchase->operation_value ?? 1);
                $nestedData['unit_name'] = $purchase->unit_code ?? '';

                $nestedData['qty_base'] =
                    ($purchase->operator == '*')
                    ? $purchase->qty * $purchase->operation_value
                    : $purchase->qty / $purchase->operation_value;

                $nestedData['unit_cost'] = number_format(($purchase->total / $purchase->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($purchase->total, config('decimal'));
                $nestedData['sub_total_value'] = (float) $purchase->total;
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function saleReturnHistoryData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('returns')
            ->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
            ->where('product_returns.product_id', $product_id)
            ->whereDate('returns.created_at', '>=', $request->input('starting_date'))
            ->whereDate('returns.created_at', '<=', $request->input('ending_date'));
        if ($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('returns.user_id', Auth::id());

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'returns.' . $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->join('customers', 'returns.customer_id', '=', 'customers.id')
            ->join('warehouses', 'returns.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('units', 'units.id', '=', 'product_returns.sale_unit_id')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if (empty($request->input('search.value'))) {
            $returnss = $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total', 'units.unit_code', 'units.operator', 'units.operation_value')->get();
        } else {
            $search = $request->input('search.value');
            $q = $q->whereDate('returns.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $returnss =  $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total', 'units.unit_code', 'units.operator', 'units.operation_value')
                    ->orwhere([
                        ['returns.reference_no', 'LIKE', "%{$search}%"],
                        ['returns.user_id', Auth::id()]
                    ])
                    ->get();
                $totalFiltered = $q->orwhere([
                    ['returns.reference_no', 'LIKE', "%{$search}%"],
                    ['returns.user_id', Auth::id()]
                ])
                    ->count();
            } else {
                $returnss =  $q->select('returns.id', 'returns.reference_no', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'product_returns.qty', 'product_returns.sale_unit_id', 'product_returns.total', 'units.unit_code', 'units.operator', 'units.operation_value')
                    ->orwhere('returns.reference_no', 'LIKE', "%{$search}%")
                    ->get();
                $totalFiltered = $q->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if (!empty($returnss)) {
            foreach ($returnss as $key => $returns) {
                $nestedData['id'] = $returns->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($returns->created_at));
                $nestedData['reference_no'] = $returns->reference_no;
                $nestedData['warehouse'] = $returns->warehouse_name;
                $nestedData['customer'] = $returns->customer_name . ' [' . ($returns->customer_number) . ']';

                $nestedData['qty'] = number_format($returns->qty, config('decimal'));

                if ($returns->sale_unit_id && $returns->unit_code) {
                    $nestedData['qty'] .= ' ' . $returns->unit_code;
                }

                $nestedData['qty_value'] = (float) $returns->qty;
                $nestedData['operator'] = $returns->operator ?? '*';
                $nestedData['operation_value'] = (float) ($returns->operation_value ?? 1);
                $nestedData['unit_name'] = $returns->unit_code ?? '';

                $nestedData['qty_base'] =
                    ($returns->operator == '*')
                    ? $returns->qty * $returns->operation_value
                    : $returns->qty / $returns->operation_value;

                $nestedData['unit_price'] = number_format(($returns->total / $returns->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($returns->total, config('decimal'));
                $nestedData['sub_total_value'] = (float) $returns->total;
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function purchaseReturnHistoryData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $product_id = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');

        $q = DB::table('return_purchases')
            ->join('purchase_product_return', 'return_purchases.id', '=', 'purchase_product_return.return_id')
            ->where('purchase_product_return.product_id', $product_id)
            ->whereDate('return_purchases.created_at', '>=', $request->input('starting_date'))
            ->whereDate('return_purchases.created_at', '<=', $request->input('ending_date'));
        if ($warehouse_id)
            $q = $q->where('warehouse_id', $warehouse_id);
        if (Auth::user()->role_id > 2 && config('staff_access') == 'own')
            $q = $q->where('return_purchases.user_id', Auth::id());

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if ($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'return_purchases.' . $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->leftJoin('suppliers', 'return_purchases.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'return_purchases.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('units', 'units.id', '=', 'purchase_product_return.purchase_unit_id')
            ->select('return_purchases.id', 'return_purchases.reference_no', 'return_purchases.created_at', 'return_purchases.supplier_id', 'suppliers.name as supplier_name', 'suppliers.phone_number as supplier_number', 'warehouses.name as warehouse_name', 'purchase_product_return.qty', 'purchase_product_return.purchase_unit_id', 'purchase_product_return.total', 'units.unit_code', 'units.operator', 'units.operation_value')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if (empty($request->input('search.value'))) {
            $return_purchases = $q->get();
        } else {
            $search = $request->input('search.value');
            $q = $q->whereDate('return_purchases.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))));

            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $return_purchases =  $q->orwhere([
                    ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                    ['return_purchases.user_id', Auth::id()]
                ])
                    ->get();
                $totalFiltered = $q->orwhere([
                    ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                    ['return_purchases.user_id', Auth::id()]
                ])
                    ->count();
            } else {
                $return_purchases =  $q->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if (!empty($return_purchases)) {
            foreach ($return_purchases as $key => $return_purchase) {
                $nestedData['id'] = $return_purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($return_purchase->created_at));
                $nestedData['reference_no'] = $return_purchase->reference_no;
                $nestedData['warehouse'] = $return_purchase->warehouse_name;
                if ($return_purchase->supplier_id)
                    $nestedData['supplier'] = $return_purchase->supplier_name . ' [' . ($return_purchase->supplier_number) . ']';
                else
                    $nestedData['supplier'] = 'N/A';

                $nestedData['qty'] = number_format($return_purchase->qty, config('decimal'));

                if ($return_purchase->purchase_unit_id && $return_purchase->unit_code) {
                    $nestedData['qty'] .= ' ' . $return_purchase->unit_code;
                }

                $nestedData['qty_value'] = (float) $return_purchase->qty;
                $nestedData['operator'] = $return_purchase->operator ?? '*';
                $nestedData['operation_value'] = (float) ($return_purchase->operation_value ?? 1);
                $nestedData['unit_name'] = $return_purchase->unit_code ?? '';

                $nestedData['qty_base'] =
                    ($return_purchase->operator == '*')
                    ? $return_purchase->qty * $return_purchase->operation_value
                    : $return_purchase->qty / $return_purchase->operation_value;



                $nestedData['unit_cost'] = number_format(($return_purchase->total / $return_purchase->qty), config('decimal'));
                $nestedData['sub_total'] = number_format($return_purchase->total, config('decimal'));
                $nestedData['sub_total_value'] = (float) $return_purchase->total;
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function adjustmentHistoryData(Request $request)
    {
        $product_id   = (int) $request->product_id;
        $warehouse_id = $request->warehouse_id;

        $rows = DB::table('adjustments as a')
            ->join('product_adjustments as pa', 'pa.adjustment_id', '=', 'a.id')
            ->where('pa.product_id', $product_id)
            ->whereBetween(DB::raw('DATE(a.created_at)'), [
                $request->starting_date,
                $request->ending_date
            ])
            ->when($warehouse_id, function ($q) use ($warehouse_id) {
                $q->where('a.warehouse_id', $warehouse_id);
            })
            ->select(
                'a.created_at',
                'a.reference_no',
                'a.warehouse_id',
                'pa.qty',
                'pa.action',
                'a.note'
            )
            ->orderBy('a.created_at', 'desc')
            ->get();

        $warehouses = Warehouse::pluck('name', 'id');

        $data = [];
        $key  = 1;

        foreach ($rows as $row) {
            $data[] = [
                'key'       => $key++,
                'date'      => date(config('date_format'), strtotime($row->created_at)),
                'reference' => $row->reference_no,
                'warehouse' => $warehouses[$row->warehouse_id] ?? '',
                'qty'       => number_format($row->qty, config('decimal')),
                'type'      => $row->action === 'addition' ? 'Adjustment +' : 'Adjustment -',
                'note'      => $row->note ?? 'N/A',
            ];
        }

        return response()->json([
            "draw"            => intval($request->draw),
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data
        ]);
    }

    public function transferHistoryData(Request $request)
    {
        $product_id   = (int) $request->product_id;
        $warehouse_id = $request->warehouse_id;

        $rows = DB::table('transfers as t')
            ->join('product_transfer as pt', 'pt.transfer_id', '=', 't.id')
            ->where('pt.product_id', $product_id)
            ->whereBetween(DB::raw('DATE(t.created_at)'), [
                $request->starting_date,
                $request->ending_date
            ])
            ->when($warehouse_id, function ($q) use ($warehouse_id) {
                $q->where(function ($qq) use ($warehouse_id) {
                    $qq->where('t.from_warehouse_id', $warehouse_id)
                        ->orWhere('t.to_warehouse_id', $warehouse_id);
                });
            })
            ->select(
                't.created_at',
                't.reference_no',
                't.from_warehouse_id',
                't.to_warehouse_id',
                'pt.qty',
                't.note'
            )
            ->orderBy('t.created_at', 'desc')
            ->get();

        $warehouses = Warehouse::pluck('name', 'id');

        $data = [];
        $key  = 1;

        foreach ($rows as $row) {

            // OUT
            if (!$warehouse_id || $row->from_warehouse_id == $warehouse_id) {
                $data[] = [
                    'key'       => $key++,
                    'date'      => date(config('date_format'), strtotime($row->created_at)),
                    'reference' => $row->reference_no,
                    'from'      => $warehouses[$row->from_warehouse_id] ?? '',
                    'to'        => $warehouses[$row->to_warehouse_id] ?? '',
                    'qty'       => number_format(-$row->qty, config('decimal')),
                ];
            }

            // IN
            if (!$warehouse_id || $row->to_warehouse_id == $warehouse_id) {
                $data[] = [
                    'key'       => $key++,
                    'date'      => date(config('date_format'), strtotime($row->created_at)),
                    'reference' => $row->reference_no,
                    'from'      => $warehouses[$row->from_warehouse_id] ?? '',
                    'to'        => $warehouses[$row->to_warehouse_id] ?? '',
                    'qty'       => number_format($row->qty, config('decimal')),
                ];
            }
        }

        return response()->json([
            "draw"            => intval($request->draw),
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data
        ]);
    }

    public function variantData($id)
    {
        if (Auth::user()->role_id > 2) {
            return ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
                ->join('product_warehouse', function ($join) {
                    $join->on('product_variants.product_id', '=', 'product_warehouse.product_id');
                    $join->on('product_variants.variant_id', '=', 'product_warehouse.variant_id');
                })
                ->select('variants.name', 'product_variants.item_code', 'product_variants.additional_cost', 'product_variants.additional_price', 'product_warehouse.qty')
                ->where([
                    ['product_warehouse.product_id', $id],
                    ['product_warehouse.warehouse_id', Auth::user()->warehouse_id]
                ])
                ->orderBy('product_variants.position')
                ->get();
        } else {
            return ProductVariant::join('variants', 'product_variants.variant_id', '=', 'variants.id')
                ->select('variants.name', 'product_variants.item_code', 'product_variants.additional_cost', 'product_variants.additional_price', 'product_variants.qty')
                ->orderBy('product_variants.position')
                ->where('product_id', $id)
                ->get();
        }
    }

    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-edit')) {
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_product_data = Product::where('id', $id)->first();

            $cost  = (float) $lims_product_data->cost;
            $price = (float) $lims_product_data->price;
            $margin = (float) $lims_product_data->profit_margin;
            if ($cost > 0 && $price > 0 && $lims_product_data->profit_margin_type == 'percentage') {
                $calculatedMargin = (($price - $cost) / $cost) * 100;

                if (round($calculatedMargin, 2) != round($margin, 2)) {
                    $lims_product_data->profit_margin = round($calculatedMargin, 2);
                }
            }

            if ($lims_product_data->variant_option) {
                $lims_product_data->variant_option = json_decode($lims_product_data->variant_option);
                $lims_product_data->variant_value = json_decode($lims_product_data->variant_value);
            }
            $lims_product_variant_data = $lims_product_data->variant()->orderBy('position')->get();

            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $noOfVariantValue = 0;
            $custom_fields = CustomField::where('belongs_to', 'product')->get();

            $related_products = collect();
            if (in_array('ecommerce', explode(',', gen_setting()->modules))) {
                $product_arr = explode(',', (string) $lims_product_data->related_products);
                $related_products = DB::table('products')->whereIn('id', array_filter($product_arr))->get();
            }

            $kitchen_list = collect();
            $menu_type_list = collect();
            if (is_dir(base_path('Modules/Restaurant')) && \Illuminate\Support\Facades\Schema::hasTable('kitchens')) {
                $kitchen_list = DB::table('kitchens')->where('is_active', 1)->get();
                $menu_type_list = \Illuminate\Support\Facades\Schema::hasTable('menu_type') ? DB::table('menu_type')->where('is_active', 1)->get() : collect();
            }

            return view('backend.product.edit', compact(
                'related_products', 'kitchen_list', 'menu_type_list', 'lims_product_list_without_variant',
                'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list',
                'lims_tax_list', 'lims_product_data', 'lims_product_variant_data', 'lims_warehouse_list',
                'noOfVariantValue', 'custom_fields'
            ));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function updateProduct(Request $request)
    {

        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        $this->validate($request, [
            'code' => [
                'max:255',
                Rule::unique('products')->ignore($request->input('id'))->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        DB::beginTransaction();
        try {
            $lims_product_data = Product::findOrFail($request->input('id'));
            $data = $request->except('image', 'file', 'prev_img');
            $data['name'] = htmlspecialchars(trim($data['name']), ENT_QUOTES);
            $data['code'] = htmlspecialchars(trim($data['code']), ENT_QUOTES);
            $data['profit_margin_type'] = $request->input('profit_margin_type', 'percentage');
            $data['profit_margin'] = $request->input('profit_margin', 0);

            $general_setting = cache()->get('general_setting');
            if(in_array('ecommerce', explode(',',$general_setting->modules))) {
                $baseSlug = Str::slug($data['name'], '-');
                $baseSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $baseSlug);
                $baseSlug = str_replace( '\/', '/', $baseSlug );
                $count = Product::where('slug', 'LIKE', "$baseSlug%")->where('id', '!=', $lims_product_data->id)->count();
                $data['slug'] = $count ? "{$baseSlug}-{$count}" : $baseSlug;
                $data['related_products'] = rtrim($request->products, ",");

                if (isset($request->in_stock))
                    $data['in_stock'] = $request->input('in_stock');
                else
                    $data['in_stock'] = 0;

                if (isset($request->is_online))
                    $data['is_online'] = $request->input('is_online');
                else
                    $data['is_online'] = 0;
            }

            if(in_array('restaurant', explode(',',$general_setting->modules))) {
                $data['kitchen_id'] = $request->input('kitchen_id');
                $data['menu_type'] = implode(',', $request->input('menu_type', []));
                unset($data['extras'], $data['is_addon']);
            }


            if ($data['type'] == 'combo') {
                $data['product_list'] = implode(",", $data['product_id']);
                $data['variant_list'] = implode(",", $data['variant_id']);
                $data['qty_list'] = implode(",", $data['product_qty']);
                $data['price_list'] = implode(",", $data['unit_price']);
                $data['wastage_percent'] = implode(",", $data['wastage_percent']);
                $data['combo_unit_id'] = implode(",", $data['combo_unit_id']);
                //$data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;
            } elseif ($data['type'] == 'digital' || $data['type'] == 'service')
                $data['cost'] = $data['unit_id'] = $data['purchase_unit_id'] = $data['sale_unit_id'] = 0;

            if (!isset($data['featured']))
                $data['featured'] = 0;

            if (!isset($data['is_embeded']))
                $data['is_embeded'] = 0;

            if (!isset($data['promotion']))
                $data['promotion'] = null;

            if (!isset($data['is_batch']))
                $data['is_batch'] = null;

            if (!isset($data['is_imei']))
                $data['is_imei'] = null;

            if (!isset($data['is_sync_disable']) && \Schema::hasColumn('products', 'is_sync_disable'))
                $data['is_sync_disable'] = null;

            if (isset($data['short_description']))
                $data['short_description'] = $data['short_description'];
            $data['product_details'] = str_replace('"', '@', $data['product_details']);
            if ($data['starting_date'])
                $data['starting_date'] = date('Y-m-d', strtotime($data['starting_date']));
            if ($data['last_date'])
                $data['last_date'] = date('Y-m-d', strtotime($data['last_date']));

            $previous_images = [];
            //dealing with previous images
            if ($request->prev_img) {
                foreach ($request->prev_img as $key => $prev_img) {
                    if (!in_array($prev_img, $previous_images))
                        $previous_images[] = $prev_img;
                }
                $lims_product_data->image = implode(",", $previous_images);
                $lims_product_data->save();
            } else {
                $lims_product_data->image = null;
                $lims_product_data->save();
            }

            //dealing with new images
            if ($request->image) {
                // Ensure the necessary directories exist using public_path()
                $this->diffSizeOfImagePathExistOrCreate();

                $images = $request->image;
                $image_names = [];
                $length = count(explode(",", $lims_product_data->image));

                foreach ($images as $key => $image) {
                    $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);

                    if (!config('database.connections.saleprosaas_landlord')) {
                        $imageName = date("Ymdhis") . ($length + $key + 1) . '.' . $ext;
                    } else {
                        $imageName = $this->getTenantId() . '_' . date("Ymdhis") . ($length + $key + 1) . '.' . $ext;
                    }

                    $image->move(public_path('images/product'), $imageName);

                    $manager = new ImageManager(new GdDriver());
                    $image = $manager->read(public_path('images/product/' . $imageName));

                    $this->diffSizeImageStore($image, $imageName);

                    $image_names[] = $imageName;
                }

                // Append or set the image field with the new image names
                if ($lims_product_data->image)
                    $data['image'] = $lims_product_data->image . ',' . implode(",", $image_names);
                else
                    $data['image'] = implode(",", $image_names);
            } else
                $data['image'] = $lims_product_data->image;

            $file = $request->file;
            if ($file) {
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = strtotime(date('Y-m-d H:i:s'));
                $fileName = $fileName . '.' . $ext;
                $file->move(public_path('product/files'), $fileName);
                $data['file'] = $fileName;
            }

            $old_product_variant_ids = ProductVariant::where('product_id', $request->input('id'))->pluck('id')->toArray();
            $new_product_variant_ids = [];
            //dealing with product variant
            if (isset($data['is_variant'])) {
                if (isset($data['variant_option']) && isset($data['variant_value'])) {
                    $data['variant_option'] = json_encode(array_unique($data['variant_option']));
                    $data['variant_value'] = json_encode(array_unique($data['variant_value']));
                }
                foreach ($data['variant_name'] as $key => $variant_name) {
                    $lims_variant_data = Variant::firstOrCreate(['name' => $data['variant_name'][$key]]);
                    $lims_product_variant_data = ProductVariant::where([
                        ['product_id', $lims_product_data->id],
                        ['variant_id', $lims_variant_data->id]
                    ])->first();
                    if ($lims_product_variant_data) {
                        $lims_product_variant_data->update([
                            'position' => $key + 1,
                            'item_code' => $data['item_code'][$key],
                            'additional_cost' => $data['additional_cost'][$key],
                            'additional_price' => $data['additional_price'][$key]
                        ]);
                    } else {
                        // return 2;
                        $lims_product_variant_data = ProductVariant::firstOrNew([
                            'product_id' => $lims_product_data->id,
                            'variant_id' => $lims_variant_data->id,
                            'item_code' => $data['item_code'][$key],
                            'additional_cost' => $data['additional_cost'][$key],
                            'additional_price' => $data['additional_price'][$key],
                            'qty' => 0,
                        ]);
                        $lims_product_variant_data->position = $key + 1;
                        $lims_product_variant_data->save();
                    }

                    $product_warehouses = Product_Warehouse::where([
                        'product_id' => $lims_product_data->id,
                        'variant_id' => $lims_variant_data->id
                    ])->get();

                    if ($product_warehouses->isEmpty()) {
                        $warehouse_ids = Warehouse::pluck('id')->toArray();
                        foreach ($warehouse_ids as $w_id) {
                            Product_Warehouse::firstOrCreate([
                                "product_id" => $lims_product_data->id,
                                "variant_id" => $lims_variant_data->id,
                                "warehouse_id" => $w_id,
                                "qty" => 0,
                            ]);
                        }
                    }

                    $new_product_variant_ids[] = $lims_product_variant_data->id;
                }
            } else {
                $data['is_variant'] = null;
                $data['variant_option'] = null;
                $data['variant_value'] = null;
            }
            //deleting old product variant if not exist
            foreach ($old_product_variant_ids as $key => $product_variant_id) {
                if (!in_array($product_variant_id, $new_product_variant_ids)) {
                    $productVariant = ProductVariant::find($product_variant_id);
                    if ($productVariant->qty > 0) {
                        DB::rollBack();
                        // return dd($productVariant);
                        return redirect()->back()->with('not_permitted', __('db.This variant has a quantity; you cannot delete it'));
                    }
                    Product_Warehouse::where('product_id', $productVariant->product_id)
                        ->where('variant_id', $productVariant->variant_id)
                        ->delete();

                    $productVariant->delete();
                }
            }

            if (isset($data['is_diffPrice'])) {
                foreach ($data['diff_price'] as $key => $diff_price) {
                    if ($diff_price) {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $data['warehouse_id'][$key])->first();
                        if ($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->price = $diff_price;
                            $lims_product_warehouse_data->save();
                        } else {
                            Product_Warehouse::firstOrCreate([
                                "product_id" => $lims_product_data->id,
                                "warehouse_id" => $data["warehouse_id"][$key],
                                "qty" => 0,
                                "price" => $diff_price
                            ]);
                        }
                    }
                }
            } else {
                $data['is_diffPrice'] = false;
                if (isset($data['warehouse_id'])) {
                    foreach ($data['warehouse_id'] as $key => $warehouse_id) {
                        $lims_product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($lims_product_data->id, $warehouse_id)->first();
                        if ($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->price = null;
                            $lims_product_warehouse_data->save();
                        }
                    }
                }
            }
            // handle warranty and guarantee
            if (!isset($data['warranty'])) {
                $data['warranty'] = null;
                $data['warranty_type'] = null;
            }
            if (!isset($data['guarantee'])) {
                $data['guarantee'] = null;
                $data['guarantee_type'] = null;
            }
            $lims_product_data->update($data);
            //inserting data for custom fields
            $custom_field_data = [];
            $custom_fields = CustomField::where('belongs_to', 'product')->select('name', 'type')->get();
            foreach ($custom_fields as $type => $custom_field) {
                $field_name = str_replace(' ', '_', strtolower($custom_field->name));
                if (isset($data[$field_name])) {
                    if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                        $custom_field_data[$field_name] = implode(",", $data[$field_name]);
                    else
                        $custom_field_data[$field_name] = $data[$field_name];
                }
            }
            if (count($custom_field_data))
                DB::table('products')->where('id', $lims_product_data->id)->update($custom_field_data);
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');

            if (\Module::has('SocialCommerce') && \Module::find('SocialCommerce')->isEnabled()) {
                $scSetting = \Modules\SocialCommerce\Entities\SocialProductSetting::firstOrNew(['product_id' => $lims_product_data->id]);
                $scSetting->is_published = $request->has('sc_is_published') ? 1 : 0;
                $scSetting->social_title = $request->input('sc_social_title');
                $scSetting->social_description = $request->input('sc_social_description');

                if ($request->hasFile('sc_social_image')) {
                    $sc_image = $request->file('sc_social_image');
                    $sc_ext = pathinfo($sc_image->getClientOriginalName(), PATHINFO_EXTENSION);
                    $sc_imageName = date("Ymdhis") . '_sc.' . $sc_ext;
                    if (!file_exists(public_path('images/product/social'))) {
                        mkdir(public_path('images/product/social'), 0777, true);
                    }
                    $sc_image->move(public_path('images/product/social'), $sc_imageName);
                    $scSetting->social_image = $sc_imageName;
                }
                $scSetting->save();
            }

            DB::commit();

            // RE-EVALUATE STOCK DEFICITS FOLLOWING DIRECT CATALOG UPDATES
            $this->checkAndNotifyProductStock($lims_product_data);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Product updated successfully']);
            }
            return redirect('products')->with('edit_message', 'Product updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('not_permitted', __('db.Failed to update product Please try again'));
        }
    }

    public function generateCode()
    {
        $id = Keygen::numeric(8)->generate();
        return $id;
    }

    public function search(Request $request)
    {
        $product_code = explode(" (", $request['data']);
        $lims_product_data = Product::where('code', $product_code[0])->first();

        $product[] = $lims_product_data->name;
        $product[] = $lims_product_data->code;
        $product[] = $lims_product_data->qty;
        $product[] = $lims_product_data->price;
        $product[] = $lims_product_data->id;
        return $product;
    }

    public function saleUnit($id)
    {
        $unit = Unit::where([
            "base_unit" => $id,
            "is_active" => true
        ])->orWhere('id', $id)->pluck('unit_name', 'id');

        return json_encode($unit);
    }

    public function getData($id, $variant_id)
    {

        if ($variant_id) {
            $data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.name', 'product_variants.item_code')
                ->where([
                    ['products.id', $id],
                    ['product_variants.variant_id', $variant_id]
                ])->first();
            $data->code = $data->item_code;
        } else
            $data = Product::select('name', 'code')->find($id);
        return $data;
    }

    public function productWarehouseData($id)
    {
        $warehouse = [];
        $qty = [];
        $batch = [];
        $expired_date = [];
        $imei_number = [];
        $warehouse_name = [];
        $variant_name = [];
        $variant_qty = [];
        $product_warehouse = [];
        $product_variant_warehouse = [];

        $lims_product_data = Product::findOrFail($id);

        if ($lims_product_data->type == 'combo') {
            $lims_product_warehouse_data = [];
            $active_warehouses = Warehouse::where('is_active', true)->get();

            foreach ($active_warehouses as $wh) {
                $combo_qty = $this->calculateComboQty($lims_product_data, $wh->id);

                // Only include combo warehouses where quantity > 0
                if ($combo_qty > 0) {
                    $warehouse[] = $wh->name;
                    $batch[] = 'N/A';
                    $expired_date[] = 'N/A';
                    $qty[] = $combo_qty;
                    $imei_number[] = 'N/A';
                }
            }
        } elseif ($lims_product_data->is_variant) {
            // Retrieve variants where qty > 0
            $lims_product_variant_warehouse_data = Product_Warehouse::where('product_id', $lims_product_data->id)
                ->where('qty', '>', 0)
                ->orderBy('warehouse_id')
                ->get();

            // Retrieve aggregated warehouse stock where sum(qty) > 0
            $lims_product_warehouse_data = Product_Warehouse::select('warehouse_id', DB::raw('sum(qty) as qty'))
                ->where('product_id', $id)
                ->groupBy('warehouse_id')
                ->havingRaw('sum(qty) > 0')
                ->get();

            foreach ($lims_product_variant_warehouse_data as $product_variant_warehouse_data) {
                $lims_warehouse_data = Warehouse::find($product_variant_warehouse_data->warehouse_id);
                $lims_variant_data = Variant::find($product_variant_warehouse_data->variant_id);

                if ($lims_warehouse_data) {
                    $warehouse_name[] = $lims_warehouse_data->name;
                    $variant_name[] = $lims_variant_data->name ?? '';
                    $variant_qty[] = $product_variant_warehouse_data->qty;
                }
            }
        } else {
            // Retrieve standard product warehouse records where qty > 0
            $lims_product_warehouse_data = Product_Warehouse::where('product_id', $id)
                ->where('qty', '>', 0)
                ->orderBy('warehouse_id', 'asc')
                ->get();
        }

        foreach ($lims_product_warehouse_data as $product_warehouse_data) {
            $lims_warehouse_data = Warehouse::find($product_warehouse_data->warehouse_id);

            if (!$lims_warehouse_data) {
                continue;
            }

            // Handle batch & expiry dates
            if ($product_warehouse_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no', 'expired_date')->find($product_warehouse_data->product_batch_id);
                $batch_no = $product_batch_data->batch_no ?? 'N/A';
                $expiredDate = isset($product_batch_data->expired_date) 
                    ? date(config('date_format'), strtotime($product_batch_data->expired_date)) 
                    : 'N/A';
            } else {
                $batch_no = 'N/A';
                $expiredDate = 'N/A';
            }

            $warehouse[] = $lims_warehouse_data->name;
            $batch[] = $batch_no;
            $expired_date[] = $expiredDate;
            $qty[] = $product_warehouse_data->qty;

            // Process IMEI numbers
            $current_imei = '';
            if ($product_warehouse_data->imei_number && !str_contains($product_warehouse_data->imei_number, 'null')) {
                $current_imei = $product_warehouse_data->imei_number;
            }

            // Deduplicate IMEIs if available
            if (!empty($current_imei)) {
                $unique_imeis = array_unique(array_filter(explode(',', $current_imei)));
                $imei_number[] = implode(',', $unique_imeis);
            } else {
                $imei_number[] = 'N/A';
            }
        }

        $product_warehouse = [$warehouse, $qty, $batch, $expired_date, $imei_number];
        $product_variant_warehouse = [$warehouse_name, $variant_name, $variant_qty];

        return [
            'product_warehouse' => $product_warehouse, 
            'product_variant_warehouse' => $product_variant_warehouse
        ];
    }

    public function printBarcode(Request $request)
    {
        if ($request->input('data')) {
            $preLoadedproducts = $this->limsProductSearch($request);
        } else
            $preLoadedproducts = [];

        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();

        $barcode_settings = Barcode::select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))->get();
        $default = $barcode_settings->where('is_default', 1)->first();
        $barcode_settings = $barcode_settings->pluck('name', 'id');
        $warehouses = Warehouse::orderBy('name', 'asc')
            ->pluck('name', 'id');

        return view('backend.product.print_barcode', compact('barcode_settings', 'lims_product_list_without_variant', 'lims_product_list_with_variant', 'preLoadedproducts', 'warehouses'));
    }

    public function productWithoutVariant()
    {
        return Product::ActiveStandard()->select('id', 'name', 'code')
            ->whereNull('is_variant')->get();
    }

    public function productWithVariant()
    {
        return Product::join('product_variants', 'products.id', 'product_variants.product_id')
            ->ActiveStandard()
            ->whereNotNull('is_variant')
            ->select('products.id', 'products.name', 'product_variants.item_code', 'product_variants.qty')
            ->orderBy('position')->get();
    }

    // this is also required for print barcode/label
    public function limsProductSearch(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        $product_code = explode("(", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_list = Product::where([
            ['code', $product_code[0]],
            ['products.is_active', 1]
        ])->get();
        if (count($lims_product_list) == 0) {
            $lims_product_list = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.item_code', 'product_variants.variant_id', 'product_variants.additional_price')
                ->where('product_variants.item_code', $product_code[0])
                ->where('products.is_active', 1)
                ->get();
        } elseif ($lims_product_list[0]->is_variant) {
            $lims_product_list = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.*', 'product_variants.item_code', 'product_variants.variant_id', 'product_variants.additional_price')
                ->where('product_variants.product_id', $lims_product_list[0]->id)
                ->where('products.is_active', 1)
                ->get();
        }
        foreach ($lims_product_list as $lims_product_data) {
            $product = [];
            $product[] = $lims_product_data->name;
            if ($lims_product_data->is_variant) {
                $product[] = $lims_product_data->item_code;
                $variant_id = $lims_product_data->variant_id;
                $additional_price = $lims_product_data->additional_price;
            } else {
                $product[] = $lims_product_data->code;
                $variant_id = '';
                $additional_price = 0;
            }

            // adding brand name
            $brand = Brand::find($lims_product_data->brand_id);

            // addin product warehouse price
            $diff_price = false;
            if ($request->barcode == true) {
                $warehouse_product = Product_Warehouse::select(
                    'product_warehouse.*',
                    'warehouses.name as warehouse_name' // warehouse এর name সহ
                )
                    ->join('warehouses', 'product_warehouse.warehouse_id', '=', 'warehouses.id')
                    ->where('product_warehouse.product_id', $lims_product_data->id)
                    ->where('product_warehouse.price', '!=', null)
                    ->latest()
                    ->get();
                foreach ($warehouse_product as $warehouse) {
                    if ($lims_product_data->price != $warehouse->price) {
                        $diff_price = true;
                    }
                }
            }

            $product[] = $lims_product_data->price + $additional_price;
            $product[] = DNS1D::getBarcodePNG($product[1], $lims_product_data->barcode_symbology);
            $product[] = $lims_product_data->promotion_price;
            $product[] = config('currency');
            $product[] = config('currency_position');
            $product[] = $lims_product_data->qty;
            $product[] = $lims_product_data->id;
            $product[] = $variant_id;
            $product[] = $lims_product_data->cost;
            $product[] = $brand->title ?? 'N/A';
            $product[] = $lims_product_data->unit_id ?? 'N/A';
            $unit = Unit::query()->where('id', $lims_product_data->unit_id)->orWhere('base_unit', $lims_product_data->unit_id)->get()->unique('id') ?? 'N/A';
            $unitOptions = '';
            foreach ($unit as $row) {
                $selected = $lims_product_data->unit_id == $row->id ? 'selected' : '';
                $unitOptions .= '<option value="' . $row->id . '" data-operation_value="' . $row->operation_value . '" data-operator="' . $row->operator . '" ' . $selected . '>' . $row->unit_name . '</option>';
            }

            $product[] = '
                <select name="combo_unit_id[]" class="btn btn-outline-secondary form-control combo_unit_id"  onchange="calculate_price()">
                    ' . $unitOptions . '
                </select>
            ';
            $product[] = $diff_price ?? 'N/A';
            $product[] = $warehouse_product ?? 'N/A';
            $products[] = $product;
        }
        return $products;
    }

    /*public function getBarcode()
    {
        return DNS1D::getBarcodePNG('72782608', 'C128');
    }*/

    public function checkBatchAvailability($product_id, $batch_no, $warehouse_id)
    {
        $product_batch_data = ProductBatch::where([
            ['product_id', $product_id],
            ['batch_no', $batch_no]
        ])->first();
        if ($product_batch_data) {
            $product_warehouse_data = Product_Warehouse::select('qty')
                ->where([
                    ['product_batch_id', $product_batch_data->id],
                    ['warehouse_id', $warehouse_id]
                ])->first();
            if ($product_warehouse_data) {
                $data['qty'] = $product_warehouse_data->qty;
                $data['product_batch_id'] = $product_batch_data->id;
                $data['expired_date'] = date(config('date_format'), strtotime($product_batch_data->expired_date));
                $data['message'] = 'ok';
            } else {
                $data['qty'] = 0;
                $data['message'] = 'This Batch does not exist in the selected warehouse!';
            }
        } else {
            $data['message'] = 'Wrong Batch Number!';
        }
        return $data;
    }


    public function importProduct(Request $request)
    {
        // 1. Structural File Level Constraints Check
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480', // Supports up to 20MB files safely
        ]);

        $upload = $request->file('file');
        $filePath = $upload->getRealPath();

        // Initialize global errors array early to preserve pre-scan validation issues
        $errors = [];

        // --- PASS 1: STREAM METRICS, CATEGORIES, BRANDS & VARIANTS FOR CACHE WARM-UP (Memory Flatline) ---
        if (($file = fopen($filePath, 'r')) === false) {
            return back()->with('message', __('db.unable_to_open_uploaded_file'));
        }

        $header = fgetcsv($file);
        if (!$header) {
            fclose($file);
            return back()->with('message', __('db.CSV file is empty or invalid'));
        }

        $escapedHeader = self::normalizeProductImportHeaders($header);

        if (count($escapedHeader) !== count(array_unique($escapedHeader))) {
            fclose($file);
            return back()->with('message', __('db.CSV contains duplicate or conflicting column headers'));
        }

        $requiredColumns = ['code', 'name', 'category', 'unit_code'];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $escapedHeader)) {
                fclose($file);
                return back()->with(
                    'message',
                    __("db.CSV is missing a required column: :column", ['column' => $col])
                );
            }
        }

        $allCodes = [];
        $allCategories = [];
        $allBrands = [];
        $allVariantNames = [];

        while ($row = fgetcsv($file)) {
            if (count($escapedHeader) !== count($row)) {
                continue; // Explicitly handled and logged with physical row index values on Pass 2
            }

            $mappedRow = array_combine($escapedHeader, $row);

            $code = trim($mappedRow['code'] ?? '');
            if ($code !== '') {
                $allCodes[] = $code;
            }

            if (!empty($mappedRow['category'])) {
                $allCategories[] = trim($mappedRow['category']);
            }

            if (!empty($mappedRow['brand']) && trim($mappedRow['brand']) !== 'N/A') {
                $allBrands[] = trim($mappedRow['brand']);
            }

            if (!empty($mappedRow['variant_name'])) {
                foreach (explode(',', $mappedRow['variant_name']) as $vName) {
                    $allVariantNames[] = trim($vName);
                }
            }
        }

        fclose($file);

        // Filter unique records across all warm-up arrays
        $allCodes = array_unique($allCodes);
        $allCategories = array_unique($allCategories);
        $allBrands = array_unique($allBrands);
        $allVariantNames = array_unique(array_filter($allVariantNames));

        // Warm up the database ledger for taxonomies outside the stream chunking loop
        DB::transaction(function () use ($allCategories, $allBrands, $allVariantNames) {
            foreach ($allCategories as $catName) {
                Category::firstOrCreate(
                    ['name' => $catName],
                    ['is_active' => true]
                );
            }

            foreach ($allBrands as $brandName) {
                Brand::firstOrCreate(
                    ['title' => $brandName],
                    ['is_active' => true]
                );
            }

            foreach (array_chunk($allVariantNames, 100) as $chunk) {
                foreach ($chunk as $name) {
                    Variant::firstOrCreate(['name' => $name]);
                }
            }
        });

        // Hydrate Global Array Cache Lookups
        // OPTIMIZATION: Transform string indices to lowercase keys to prevent case-insensitivity collation desync mismatches
        $existingProducts = Product::whereIn('code', $allCodes)
            ->get()
            ->keyBy('code');

        $categories = Category::whereIn('name', $allCategories)
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->name) => $item->id];
            })
            ->toArray();

        $brands = Brand::whereIn('title', $allBrands)
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->title) => $item->id];
            })
            ->toArray();

        $variantsCache = Variant::whereIn('name', $allVariantNames)
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtolower($item->name) => $item->id];
            })
            ->toArray();

        $units = Unit::pluck('id', 'unit_code');
        $warehouses = Warehouse::where('is_active', true)
            ->pluck('id')
            ->toArray();

        $general_setting = GeneralSetting::first();
        $defaultMargin = $general_setting->default_margin_value ?? 25;
        $addonsConfig = config('addons') ?? '';

        // --- PASS 2: GENERATOR DRIVEN CHUNK STREAMING ---
        $importedCount = 0;
        $processedCodesInBatch = [];
        $pendingImages = [];

        $chunkGenerator = function () use ($filePath, $escapedHeader) {
            $file = fopen($filePath, 'r');
            fgetcsv($file); // Skip index header row

            $currentChunk = [];
            $rowCounter = 1;

            while ($row = fgetcsv($file)) {
                $rowCounter++;

                if (count($escapedHeader) !== count($row)) {
                    yield [
                        'error' => __(
                            "db.Row :row: CSV column count mismatch",
                            ['row' => $rowCounter]
                        )
                    ];
                    continue;
                }

                $mappedRow = array_combine($escapedHeader, $row);
                $mappedRow['_spreadsheet_row_num'] = $rowCounter;

                $currentChunk[] = $mappedRow;

                if (count($currentChunk) === 100) {
                    yield ['chunk' => $currentChunk];
                    $currentChunk = [];
                }
            }

            if (!empty($currentChunk)) {
                yield ['chunk' => $currentChunk];
            }

            fclose($file);
        };

        foreach ($chunkGenerator() as $streamResult) {
            if (isset($streamResult['error'])) {
                $errors[] = $streamResult['error'];
                continue;
            }

            $chunk = $streamResult['chunk'];
            $validatedChunkRows = [];

            // --- PHASE A: ISOLATED VALIDATIONS, MANUAL SECURE HOPS & SLUG ANALYSIS ---

            $chunkBaseSlugs = [];

            foreach ($chunk as $row) {
                if (!empty($row['name'])) {
                    $chunkBaseSlugs[] = Str::slug(trim($row['name']));
                }
            }

            $chunkBaseSlugs = array_unique($chunkBaseSlugs);

            $slugMatches = [];

            if (!empty($chunkBaseSlugs) && in_array('ecommerce', explode(',', $addonsConfig))) {
                $query = Product::query();

                foreach ($chunkBaseSlugs as $slug) {
                    $query->orWhere('slug', 'LIKE', "$slug%");
                }

                $slugMatches = $query->pluck('slug')->toArray();
            }

            foreach ($chunk as $rowRef) {
                $spreadsheetRow = $rowRef['_spreadsheet_row_num'];

                $code = trim($rowRef['code'] ?? '');
                $rowRef['code'] = $code;

                if ($code === '') {
                    $errors[] = __(
                        "db.Row :row: Product code cannot be empty",
                        ['row' => $spreadsheetRow]
                    );
                    continue;
                }

                // OPTIMIZATION: Validate missing name strings up front to avoid corrupting slug metrics and system records
                $name = trim($rowRef['name'] ?? '');

                if ($name === '') {
                    $errors[] = __(
                        "db.Row :row: Product name cannot be empty",
                        ['row' => $spreadsheetRow]
                    );
                    continue;
                }

                if (in_array($code, $processedCodesInBatch)) {
                    $errors[] = __(
                        "db.Row :row: Duplicate code ':code' found inside this CSV payload",
                        [
                            'row' => $spreadsheetRow,
                            'code' => $code
                        ]
                    );
                    continue;
                }

                if (!isset($units[$rowRef['unit_code']])) {
                    $errors[] = __(
                        "db.Row :row: Unit code not found: :code",
                        [
                            'row' => $spreadsheetRow,
                            'code' => $rowRef['unit_code']
                        ]
                    );
                    continue;
                }

                if (!isset($rowRef['category']) || trim($rowRef['category']) === '') {
                    $errors[] = __(
                        "db.Row :row: Category name is required and cannot be empty",
                        ['row' => $spreadsheetRow]
                    );
                    continue;
                }

                // Case-Insensitive Array Key Map Matches Check Protection Safeguards
                $normalizedCategoryKey = strtolower(trim($rowRef['category']));

                if (!isset($categories[$normalizedCategoryKey])) {
                    $errors[] = __(
                        "db.Row :row: Category lookup mapping desynchronization failure: :category",
                        [
                            'row' => $spreadsheetRow,
                            'category' => $rowRef['category']
                        ]
                    );
                    continue;
                }

                $processedCodesInBatch[] = $code;

                $parseNumber = function ($val) {
                    return (float) str_replace(',', '', $val ?? 0);
                };

                $cost = $parseNumber($rowRef['cost'] ?? 0);
                $price = $parseNumber($rowRef['price'] ?? 0);
                $wholesale_price = $parseNumber($rowRef['wholesale_price'] ?? 0);

                if (($rowRef['profit_margin'] ?? '') !== '') {
                    $margin = (float) $rowRef['profit_margin'];
                    $price = $cost > 0
                        ? ($cost * (1 + $margin / 100))
                        : $price;
                } elseif ($price > 0) {
                    $margin = $cost > 0
                        ? (($price - $cost) / $cost) * 100
                        : $defaultMargin;
                } else {
                    $margin = $defaultMargin;
                    $price = $cost > 0
                        ? ($cost * (1 + $margin / 100))
                        : 0.00;
                }

                /*
                |--------------------------------------------------------------------------
                | Product Type Normalization
                |--------------------------------------------------------------------------
                |
                | Product type is optional in the CSV. Missing, NULL, blank, or
                | whitespace-only values must default to "standard".
                |
                | IMPORTANT:
                | Using `$rowRef['type'] ?? 'standard'` alone does not handle an
                | existing CSV column containing an empty string.
                |
                */
                $productType = strtolower(trim((string) ($rowRef['type'] ?? '')));

                if ($productType === '') {
                    $productType = 'standard';
                }

                // Slug Management Logic
                $slug = null;

                if (in_array('ecommerce', explode(',', $addonsConfig))) {
                    $baseSlug = Str::slug($name);
                    $product = $existingProducts->get($code) ?? null;

                    if ($product && str_starts_with($product->slug ?? '', $baseSlug)) {
                        $slug = $product->slug;
                    } else {
                        $matchingCount = 0;

                        foreach ($slugMatches as $existingSlug) {
                            if ($product && $existingSlug === $product->slug) {
                                continue;
                            }

                            if (str_starts_with($existingSlug, $baseSlug)) {
                                $matchingCount++;
                            }
                        }

                        $slug = $matchingCount
                            ? "{$baseSlug}-{$matchingCount}"
                            : $baseSlug;

                        $slugMatches[] = $slug;
                    }
                }

                $imageSources = [];

                if (!empty($rowRef['image'])) {
                    $imageSources = array_values(
                        array_unique(
                            array_filter(
                                array_map(
                                    'trim',
                                    explode(',', $rowRef['image'])
                                )
                            )
                        )
                    );
                }

                $variantNames = array_values(
                    array_filter(
                        array_map(
                            'trim',
                            explode(',', $rowRef['variant_name'] ?? '')
                        ),
                        'strlen'
                    )
                );

                $variantLists = [];

                foreach (['item_code', 'additional_cost', 'additional_price'] as $variantColumn) {
                    $variantLists[$variantColumn] = array_values(
                        array_map(
                            'trim',
                            explode(',', $rowRef[$variantColumn] ?? '')
                        )
                    );

                    if (
                        $variantNames &&
                        (
                            count($variantLists[$variantColumn]) !== count($variantNames) ||
                            in_array('', $variantLists[$variantColumn], true)
                        )
                    ) {
                        $errors[] = __(
                            "db.Row :row: :column must contain exactly one value for each variant",
                            [
                                'row' => $spreadsheetRow,
                                'column' => $variantColumn
                            ]
                        );
                        continue 2;
                    }
                }

                if (
                    count($variantNames) !==
                    count(array_unique(array_map('strtolower', $variantNames)))
                ) {
                    $errors[] = __(
                        "db.Row :row: Variant names must be unique",
                        ['row' => $spreadsheetRow]
                    );
                    continue;
                }

                if (
                    $variantNames &&
                    count($variantLists['item_code']) !==
                    count(
                        array_unique(
                            array_map(
                                'strtolower',
                                $variantLists['item_code']
                            )
                        )
                    )
                ) {
                    $errors[] = __(
                        "db.Row :row: Variant item codes must be unique",
                        ['row' => $spreadsheetRow]
                    );
                    continue;
                }

                try {
                    [$variantOptions, $variantValues] =
                        self::parseProductImportVariantValue(
                            (string) ($rowRef['variant_value'] ?? '')
                        );
                } catch (\InvalidArgumentException $exception) {
                    $errors[] = __(
                        "db.Row :row: :message",
                        [
                            'row' => $spreadsheetRow,
                            'message' => $exception->getMessage()
                        ]
                    );
                    continue;
                }

                $validatedChunkRows[] = [
                    '_spreadsheet_row_num' => $spreadsheetRow,
                    '_image_sources'       => $imageSources,
                    '_slug'                => $slug,
                    'code'                 => $code,
                    'name'                 => $name,
                    'type'                 => $productType,
                    'category'             => $normalizedCategoryKey,
                    'brand'                => strtolower(trim($rowRef['brand'] ?? '')),
                    'unit_code'            => $rowRef['unit_code'],
                    'product_details'      => $rowRef['product_details'] ?? '',
                    'variant_name'         => $rowRef['variant_name'] ?? '',
                    'variant_value'        => $rowRef['variant_value'] ?? '',
                    'item_code'            => $rowRef['item_code'] ?? '',
                    'additional_cost'      => $rowRef['additional_cost'] ?? '',
                    'additional_price'     => $rowRef['additional_price'] ?? '',
                    '_variant_names'       => $variantNames,
                    '_variant_lists'       => $variantLists,
                    '_variant_options'     => $variantOptions,
                    '_variant_values'      => $variantValues,
                    'cost'                 => $cost,
                    'price'                => $price,
                    'profit_margin'        => $margin,
                    'wholesale_price'      => $wholesale_price
                ];
            }

            if (empty($validatedChunkRows)) {
                continue;
            }

            // --- PHASE B: TRANSACTION CONSTRAINED DATABASE PERSISTENCE WRITES ---
            DB::beginTransaction();

            $rowsImportedInThisBatchCount = 0;
            $importLocks = [];

            try {
                foreach ($validatedChunkRows as $data) {
                    // All entries are safely mapped using downcased associative pointers
                    $categoryId = $categories[$data['category']];

                    $brandId = (
                        !empty($data['brand']) &&
                        $data['brand'] !== 'n/a'
                    )
                        ? ($brands[$data['brand']] ?? null)
                        : null;

                    if (DB::getDriverName() === 'mysql') {
                        $lockName = 'product-csv:' . sha1(strtolower($data['code']));

                        $acquired = (int) (
                            DB::selectOne(
                                'SELECT GET_LOCK(?, 10) AS acquired',
                                [$lockName]
                            )->acquired ?? 0
                        );

                        if ($acquired !== 1) {
                            throw new \RuntimeException(
                                "Timed out waiting to import product code {$data['code']}"
                            );
                        }

                        $importLocks[] = $lockName;
                    }

                    // Re-query after taking the lock: another timed-out browser request may
                    // still be completing the same CSV concurrently.
                    $product = Product::where('code', $data['code'])
                        ->orderBy('id')
                        ->first();

                    $isNew = false;

                    if (!$product) {
                        $product = new Product();
                        $product->code = $data['code'];
                        $product->is_active = 1;
                        $isNew = true;
                    }

                    $product->fill([
                        'name'              => $data['name'],
                        'type'              => $data['type'],
                        'barcode_symbology' => 'C128',
                        'brand_id'          => $brandId,
                        'category_id'       => $categoryId,
                        'unit_id'           => $units[$data['unit_code']],
                        'purchase_unit_id'  => $units[$data['unit_code']],
                        'sale_unit_id'      => $units[$data['unit_code']],
                        'cost'              => $data['cost'],
                        'price'             => $data['price'],
                        'profit_margin'     => $data['profit_margin'],
                        'wholesale_price'   => $data['wholesale_price'],
                        'tax_method'        => 1,
                        'product_details'   => $data['product_details'],
                    ]);

                    if ($isNew) {
                        $product->qty = 0;

                        $product->image = file_exists(
                            public_path('images/product/zummXD2dvAtI.png')
                        )
                            ? 'zummXD2dvAtI.png'
                            : null;
                    }

                    if ($data['_slug'] !== null) {
                        $product->slug = $data['_slug'];
                        $product->in_stock = true;
                    }

                    $product->save();

                    foreach ($data['_image_sources'] as $source) {
                        $key = hash(
                            'sha256',
                            strtolower($data['code']) . '|' . $source
                        );

                        $pendingImages[$key] = [
                            'key'           => $key,
                            'product_code'  => $data['code'],
                            'source'        => $source,
                            'filename_base' => 'import_' . substr($key, 0, 40),
                            'status'        => 'pending',
                        ];
                    }

                    $variantIdsPassed = [];

                    if (!empty($data['_variant_names'])) {
                        $vNames = $data['_variant_names'];

                        $existingVariants = ProductVariant::where(
                            'product_id',
                            $product->id
                        )
                            ->get()
                            ->keyBy('variant_id');

                        foreach ($vNames as $k => $vName) {
                            $cleanedVName = strtolower(trim($vName));
                            $vId = $variantsCache[$cleanedVName] ?? null;

                            if (!$vId) {
                                continue;
                            }

                            $variantIdsPassed[] = $vId;

                            $variantRowData = [
                                'product_id'       => $product->id,
                                'variant_id'       => $vId,
                                'position'         => $k + 1,
                                'item_code'        => $data['_variant_lists']['item_code'][$k],
                                'additional_cost'  => (float) str_replace(
                                    ',',
                                    '',
                                    $data['_variant_lists']['additional_cost'][$k]
                                ),
                                'additional_price' => (float) str_replace(
                                    ',',
                                    '',
                                    $data['_variant_lists']['additional_price'][$k]
                                ),
                                'qty'              => 0,
                            ];

                            $productVariant = $existingVariants->get($vId);

                            if ($productVariant) {
                                $productVariant
                                    ->fill($variantRowData)
                                    ->save();
                            } else {
                                ProductVariant::create($variantRowData);

                                if (!$isNew) {
                                    foreach ($warehouses as $wid) {
                                        Product_Warehouse::firstOrCreate(
                                            [
                                                'product_id'   => $product->id,
                                                'variant_id'   => $vId,
                                                'warehouse_id' => $wid,
                                            ],
                                            [
                                                'qty' => 0
                                            ]
                                        );
                                    }
                                }
                            }
                        }

                        $product->is_variant = true;

                        $product->variant_option = $data['_variant_options']
                            ? json_encode($data['_variant_options'])
                            : null;

                        $product->variant_value = $data['_variant_values']
                            ? json_encode($data['_variant_values'])
                            : null;

                        $product->save();
                    }

                    if ($isNew) {
                        $warehouseData = [];

                        if (!empty($variantIdsPassed)) {
                            foreach ($variantIdsPassed as $vId) {
                                foreach ($warehouses as $wid) {
                                    $warehouseData[] = [
                                        'product_id'   => $product->id,
                                        'variant_id'   => $vId,
                                        'warehouse_id' => $wid,
                                        'qty'          => 0,
                                    ];
                                }
                            }
                        } else {
                            foreach ($warehouses as $wid) {
                                $warehouseData[] = [
                                    'product_id'   => $product->id,
                                    'variant_id'   => null,
                                    'warehouse_id' => $wid,
                                    'qty'          => 0,
                                ];
                            }
                        }

                        foreach ($warehouseData as $warehouseRow) {
                            Product_Warehouse::firstOrCreate(
                                array_intersect_key(
                                    $warehouseRow,
                                    array_flip([
                                        'product_id',
                                        'variant_id',
                                        'warehouse_id'
                                    ])
                                ),
                                [
                                    'qty' => 0
                                ]
                            );
                        }
                    }

                    $rowsImportedInThisBatchCount++;
                }

                DB::commit();

                foreach ($importLocks as $lockName) {
                    DB::selectOne(
                        'SELECT RELEASE_LOCK(?)',
                        [$lockName]
                    );
                }

                $importedCount += $rowsImportedInThisBatchCount;
            } catch (\Exception $batchException) {
                DB::rollBack();

                foreach ($importLocks as $lockName) {
                    DB::selectOne(
                        'SELECT RELEASE_LOCK(?)',
                        [$lockName]
                    );
                }

                Log::error(
                    "Import transaction failure handling line series: " .
                    $batchException->getMessage()
                );

                $errors[] = __(
                    "db.Critical execution database break encountered on the row chunk beginning at row :row:.",
                    [
                        'row' => $chunk[0]['_spreadsheet_row_num'] ?? 'unknown'
                    ]
                );
            }
        }

        $imageToken = app(ProductImportImageService::class)
            ->createManifest(array_values($pendingImages));

        if (count($errors) > 0) {
            return back()
                ->with('import_errors', $errors)
                ->with('image_import_token', $imageToken)
                ->with('message', __('db.import_completed_with_errors'));
        }

        return redirect('products')->with([
            'import_message' => __(
                "db.Successfully imported :count products!",
                ['count' => $importedCount]
            ),
            'image_import_token' => $imageToken,
        ]);
    }

    public function processImportImages(Request $request, ProductImportImageService $images)
    {
        $data = $request->validate(['token' => ['required', 'regex:/^[a-f0-9]{40}$/'], 'limit' => 'nullable|integer|min:1|max:20']);
        return response()->json($images->process($data['token'], $data['limit'] ?? ProductImportImageService::BATCH_SIZE));
    }

    public function allProductInStock()
    {
        if (!in_array('ecommerce', explode(',', config('addons'))))
            return redirect()->back()->with('not_permitted', __('db.Please install the ecommerce addon!'));
        Product::where('is_active', true)->update(['in_stock' => true]);
        return redirect()->back()->with('create_message', __('db.All Products set to in stock successfully!'));
    }

    public function showAllProductOnline()
    {
        if (!in_array('ecommerce', explode(',', config('addons'))))
            return redirect()->back()->with('not_permitted', __('db.Please install the ecommerce addon!'));
        Product::where('is_active', true)->update(['is_online' => true]);
        return redirect()->back()->with('create_message', __('db.All Products will be showed to online!'));
    }

    private function deleteImageFromStorage($image)
    {
        $this->fileDelete(public_path('images/product/'), $image);
        $this->fileDelete(public_path('images/product/xlarge/'), $image);
        $this->fileDelete(public_path('images/product/large/'), $image);
        $this->fileDelete(public_path('images/product/medium/'), $image);
        $this->fileDelete(public_path('images/product/small/'), $image);
    }

    public function deleteBySelection(Request $request)
    {
        $product_id = $request['productIdArray'];
        foreach ($product_id as $id) {
            $lims_product_data = Product::findOrFail($id);
            $lims_product_data->is_active = false;

            if($lims_product_data->image && $lims_product_data->image != 'zummXD2dvAtI.png') {
                $images = explode(",", $lims_product_data->image);
                foreach ($images as $image) {
                    $this->deleteImageFromStorage($image);
                }
                $lims_product_data->image = null;
            }
            $lims_product_data->save();
        }
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');
        return 'Product deleted successfully!';
    }

    public function destroy($id)
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        } else {
            $lims_product_data = Product::findOrFail($id);
            $lims_product_data->is_active = false;
            if($lims_product_data->image && $lims_product_data->image != 'zummXD2dvAtI.png') {
                $images = explode(",", $lims_product_data->image);
                foreach ($images as $key => $image) {
                    $this->deleteImageFromStorage($image);
                }
                $lims_product_data->image = null;
            }
            $lims_product_data->save();
            $this->cacheForget('product_list');
            $this->cacheForget('product_list_with_variant');
            // return redirect('products')->with('message', __('db.Product deleted successfully'));
            return redirect()->back()->with('message', __('db.Product deleted successfully'));
        }
    }

    public function toggleActive($id)
    {
        $roleId = Auth::user()->role_id ?? 0;
        $role = Role::find($roleId);
        $hasPermission = false;
        if ($roleId <= 2) {
            $hasPermission = true;
        } elseif ($role) {
            try {
                $hasPermission = $role->hasPermissionTo('products-edit');
            } catch (\Exception $e) {
                $hasPermission = false;
            }
        }

        if (!$hasPermission) {
            return response()->json(['success' => false, 'message' => __('db.Sorry! You are not allowed to access this module')], 403);
        }

        $product = Product::find($id);
        if (!$product || (int)$product->is_active === 0) {
            return response()->json(['success' => false, 'message' => 'Product not found or has been deleted.'], 404);
        }

        if ((int)$product->is_active === 1) {
            $product->is_active = 3;
            $message = 'Product deactivated successfully.';
        } elseif ((int)$product->is_active === 3) {
            $product->is_active = 1;
            $message = 'Product activated successfully.';
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid product status transition.'], 422);
        }

        $product->save();
        $this->cacheForget('product_list');
        $this->cacheForget('product_list_with_variant');

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_active' => (int)$product->is_active
        ]);
    }

    public function getProductPrice($id)
    {
        $lims_product_data = Product::where('id', $id)->select('price')->first();

        return $lims_product_data;
    }

    private function productListSocialShareContext(): ?array
    {
        if (!\Nwidart\Modules\Facades\Module::has('SocialCommerce')) {
            return null;
        }

        $module = \Nwidart\Modules\Facades\Module::find('SocialCommerce');
        if (!$module || !$module->isEnabled()) {
            return null;
        }

        $settings = \Modules\SocialCommerce\Entities\SocialCommerceSetting::first();
        if (!$settings || !$settings->is_active) {
            return null;
        }

        return [
            'template' => $settings->share_template ?? '{product_name} - {product_link}',
            'resolver' => app(\Modules\SocialCommerce\Services\ProductLinkResolver::class),
        ];
    }

    private function buildProductListComboData(Collection $comboProducts, int $warehouseId): array
    {
        if ($comboProducts->isEmpty()) {
            return [[], []];
        }

        $componentIds = collect();
        $comboUnitIds = collect();
        foreach ($comboProducts as $comboProduct) {
            $componentIds->push(...array_values(array_filter(explode(',', $comboProduct->product_list ?? ''))));
            $comboUnitIds->push(...array_values(array_filter(explode(',', $comboProduct->combo_unit_id ?? ''))));
        }
        $componentIds = $componentIds->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $comboUnitIds = $comboUnitIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $childProducts = Product::whereIn('id', $componentIds)
            ->pluck('unit_id', 'id');
        $units = Unit::whereIn('id', $comboUnitIds)
            ->get(['id', 'unit_name', 'operator', 'operation_value'])
            ->keyBy('id');

        $componentStockIds = $componentIds->map(fn ($id) => (string) $id);
        $stockQuery = Product_Warehouse::whereIn('product_id', $componentStockIds);
        if ($warehouseId > 0) {
            $stockQuery->where('warehouse_id', $warehouseId);
        }
        $componentStock = $componentStockIds->isEmpty()
            ? collect()
            : $stockQuery
                ->selectRaw('product_id, COALESCE(SUM(qty), 0) as total_qty')
                ->groupBy('product_id')
                ->pluck('total_qty', 'product_id');

        $quantities = [];
        $unitNames = [];
        foreach ($comboProducts as $comboProduct) {
            $productIds = array_values(array_filter(explode(',', $comboProduct->product_list ?? '')));
            $requiredQtys = array_values(array_filter(explode(',', $comboProduct->qty_list ?? '')));
            $unitIds = $comboProduct->combo_unit_id
                ? array_values(array_filter(explode(',', $comboProduct->combo_unit_id)))
                : [];
            $minAvailable = PHP_INT_MAX;

            foreach ($productIds as $index => $componentId) {
                $required = isset($requiredQtys[$index]) ? (float) $requiredQtys[$index] : 1.0;
                $childUnitId = $childProducts[(int) $componentId] ?? null;
                $comboUnitId = isset($unitIds[$index]) ? (int) $unitIds[$index] : null;

                if ($childUnitId && $comboUnitId && $comboUnitId !== (int) $childUnitId) {
                    $unit = $units[$comboUnitId] ?? null;
                    if ($unit) {
                        if ($unit->operator === '*') {
                            $required *= $unit->operation_value;
                        } elseif ($unit->operator === '/') {
                            $required /= $unit->operation_value;
                        }
                    }
                }

                $stock = (float) ($componentStock[(string) $componentId] ?? 0);
                if ($stock <= 0) {
                    $minAvailable = 0;
                    break;
                }

                $minAvailable = min($minAvailable, (int) floor($stock / max(0.0001, $required)));
            }

            $quantities[$comboProduct->id] = $minAvailable === PHP_INT_MAX ? 0 : $minAvailable;
            $unitNames[$comboProduct->id] = collect(explode(',', (string) $comboProduct->combo_unit_id))
                ->map(fn ($unitId) => $units[(int) $unitId]->unit_name ?? '')
                ->implode(',');
        }

        return [$quantities, $unitNames];
    }

    public function calculateComboQty($product, $warehouse_id = 0)
    {
        $componentIds = array_values(array_filter(explode(',', $product->product_list ?? '')));
        $requiredQtys = array_values(array_filter(explode(',', $product->qty_list ?? '')));
        $comboUnitIds = $product->combo_unit_id ? array_values(array_filter(explode(',', $product->combo_unit_id))) : [];
        $minAvailable = PHP_INT_MAX;

        $childProducts = Product::whereIn('id', $componentIds)
            ->select('id', 'unit_id')
            ->get()
            ->keyBy('id');

        $allUnits = Unit::all()->keyBy('id');

        foreach ($componentIds as $i => $compId) {
            $required = isset($requiredQtys[$i]) ? (float) $requiredQtys[$i] : 1.0;

            if ($warehouse_id > 0) {
                $stock = Product_Warehouse::where([
                    ['product_id', $compId],
                    ['warehouse_id', $warehouse_id]
                ])->sum('qty');
            } else {
                $stock = Product_Warehouse::where('product_id', $compId)->sum('qty');
            }

            $child = $childProducts[$compId] ?? null;
            if ($child) {
                $comboUnitId = $comboUnitIds[$i] ?? null;
                if ($comboUnitId && $comboUnitId != $child->unit_id) {
                    $unit = $allUnits[$comboUnitId] ?? null;
                    if ($unit) {
                        if ($unit->operator == '*') {
                            $required = $required * $unit->operation_value;
                        } elseif ($unit->operator == '/') {
                            $required = $required / $unit->operation_value;
                        }
                    }
                }
            }

            if ($stock <= 0) {
                $minAvailable = 0;
                break;
            }

            $minAvailable = min($minAvailable, (int) floor($stock / max(0.0001, $required)));
        }

        return ($minAvailable === PHP_INT_MAX) ? 0 : $minAvailable;
    }

    protected function checkAndNotifyProductStock($product)
    {
        try {
            if (in_array($product->type, ['standard', 'combo']) && isset($product->alert_quantity)) {
                if ($product->qty <= $product->alert_quantity) {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $admins = \App\Models\User::where('role_id', '<=', 2)->get();

                    $notificationService->dispatch('low_stock', [
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'current_qty'  => $product->qty,
                        'alert_qty'    => $product->alert_quantity,
                        'admin_users'  => $admins,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Product alert verification failed: " . $e->getMessage());
        }
    }
}
