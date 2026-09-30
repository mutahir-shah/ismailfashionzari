<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomField;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Services\Domain\RecipeDomainService;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('products-index')){
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();

            if($request->input('warehouse_id')) {
                $warehouse_id = $request->input('warehouse_id');
            }
            else {
                if (Auth::user()->warehouse_id) $warehouse_id = Auth::user()->warehouse_id;
                else $warehouse_id = 0;
            }

            $permissions = $role->permissions;

            $all_permission = [];
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            $role_id = $role->id;
            $numberOfProduct = DB::table('products')->where('is_active', true)->count();
            $custom_fields = CustomField::where([
                                ['belongs_to', 'product'],
                                ['is_table', true]
                            ])->pluck('name');
            $field_name = [];
            foreach($custom_fields as $fieldName) {
                $field_name[] = str_replace(" ", "_", strtolower($fieldName));
            }
            return view('manufacturing::recipe.index', compact('warehouse_id','all_permission', 'role_id', 'numberOfProduct', 'custom_fields', 'field_name','lims_warehouse_list'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    // public function index()
    // {
    //     return view('manufacturing::index');
    // }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-add')){
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $numberOfProduct = Product::where('is_active', true)->count();
            $custom_fields = CustomField::where('belongs_to', 'product')->get();

            $general_setting = DB::table('general_settings')->select('modules')->first();
            $lims_product_list = Product::where([
                    ['type', 'standard'],
                    ['is_active', true]
                ])->orWhere('type','combo')->get(['name','id']);
            if(in_array('restaurant',explode(',',$general_setting->modules))){
                $kitchen_list = DB::table('kitchens')->where('is_active',1)->get();
                $menu_type_list = DB::table('menu_type')->where('is_active',1)->get();

                return view('manufacturing::recipe.create',compact('lims_product_list','kitchen_list','menu_type_list','lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct', 'custom_fields'));
            }

            return view('manufacturing::recipe.create',compact('lims_product_list','lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct', 'custom_fields'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
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

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request, RecipeDomainService $recipes)
    {
        $request->validate([
            'p_id' => 'required',
            'product_qty' => 'required|array',
            'unit_price' => 'required|array',
            'product_unit_cost' => 'required|array',
        ]);

        $recipes->save(Product::query()->findOrFail($request->p_id), $request->all());

        return redirect()->route('manufacturing.recipes.index')->with('success', 'Successfully Recipe Created');
    }

    private function storeLegacy(Request $request)
    {
        $request->validate([
            'p_id' => 'required',
            'product_qty' => 'required|array',
            'unit_price' => 'required|array',
            'product_unit_cost' => 'required|array',
        ]);
        try{
            DB::beginTransaction();
            $product = Product::query()->findOrFail($request->p_id);
            
            $data = [
                'qty_list'=> implode(",", $request->product_qty ?? []),
                'price_list'=> implode(",", $request->unit_price ?? []),
                'wastage_percent'=> implode(",", $request->wastage_percent ?? []),
                'combo_unit_id'=> implode(",", $request->combo_unit_id ?? []),
                'product_list'=> implode(",", $request->product_list ?? $request->product_id ?? []),
                'variant_list'=> implode(",", $request->variant_list ?? $request->variant_id ?? []),
                'is_recipe' => 1
            ];

            $total_cost = 0;
            $total_price = 0;
            if ($request->product_qty) {
                foreach ($request->product_qty as $index => $qty) {
                    $unit_cost = $request->product_unit_cost[$index] ?? 0;
                    $unit_price = $request->unit_price[$index] ?? 0;
                    $combo_unit_id = $request->combo_unit_id[$index] ?? null;
                    
                    $converted_qty = $qty;
                    if ($combo_unit_id) {
                        $unit = Unit::find($combo_unit_id);
                        if ($unit) {
                            if ($unit->operator == '*') {
                                $converted_qty = $qty * $unit->operation_value;
                            } elseif ($unit->operator == '/') {
                                $converted_qty = $qty / $unit->operation_value;
                            }
                        }
                    }
                    
                    $total_cost += $converted_qty * $unit_cost;
                    $total_price += $converted_qty * $unit_price;
                }
            }

            $data['cost'] = $total_cost;
            $data['price'] = $total_price;
            $product->update($data);
            DB::commit();
            return redirect()->route('manufacturing.recipes.index')->with('success','Successfully Recipe Created');

        } catch (\Throwable $e){
            DB::rollBack();
            \Log::error($e->getMessage());
            return redirect()->back()->with('not_permitted', __('db.generic_error_please_try_again'));
        }

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('manufacturing::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('products-add')){
            $lims_product_list_without_variant = $this->productWithoutVariant();
            $lims_product_list_with_variant = $this->productWithVariant();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $lims_unit_list = Unit::where('is_active', true)->get();
            $lims_tax_list = Tax::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $numberOfProduct = Product::where('is_active', true)->count();
            $custom_fields = CustomField::where('belongs_to', 'product')->get();

            $general_setting = DB::table('general_settings')->select('modules')->first();
            $lims_product_data = Product::where([
            ['id', $id],
        ])->first();
            if(in_array('restaurant',explode(',',$general_setting->modules))){
                $kitchen_list = DB::table('kitchens')->where('is_active',1)->get();
                $menu_type_list = DB::table('menu_type')->where('is_active',1)->get();

                return view('manufacturing::recipe.edit',compact('lims_product_data','kitchen_list','menu_type_list','lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct', 'custom_fields'));
            }

            return view('manufacturing::recipe.edit',compact('lims_product_data','lims_product_list_without_variant', 'lims_product_list_with_variant', 'lims_brand_list', 'lims_category_list', 'lims_unit_list', 'lims_tax_list', 'lims_warehouse_list', 'numberOfProduct', 'custom_fields'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $product = Product::query()->findOrFail($id);
        $product->update([
            'is_recipe' => 0,
            'qty_list' => null,
            'price_list' => null,
            'wastage_percent' => '',
            'combo_unit_id' => null,
            'product_list' => null,
            'variant_list' => null,
        ]);
        return redirect()->back()->with('success','Successfully recipe Removed');
    }


    public function getIngredientData(Request $request)
    {
        $product = Product::find($request->product_id);
        $variant_id = $request->variant_id;
        $variant = null;
        if($variant_id) {
            $variant = \App\Models\ProductVariant::where([
                ['product_id', $request->product_id],
                ['variant_id', $variant_id]
            ])->first();
        }
        $data['name'] = $product->name;
        $data['code'] = $variant ? $variant->item_code : $product->code;
        
        $qty = $request->qty;
        $price = $request->price;
        $combo_unit_id = $request->combo_unit_id;
        
        $converted_qty = $qty;
        if($combo_unit_id) {
            $unit = Unit::find($combo_unit_id);
            if($unit) {
                if($unit->operator == '*')
                    $converted_qty = $qty * $unit->operation_value;
                elseif($unit->operator == '/')
                    $converted_qty = $qty / $unit->operation_value;
            }
        }
        
        $data['calculated_price'] = $converted_qty * $price;
        return $data;
    }

    public function productData(Request $request)
    {
        $columns = array(
            1 => 'name',
            2 => 'category_id',
            3 => 'unit_id',
            4 => 'qty',
            5 => 'price',
            6 => 'cost',
        );
        $warehouse_id = $request->input('warehouse_id');
        $is_recipe = $request->input('is_recipe');
        // dd($warehouse_id);
        if($is_recipe == true){
            $totalData = Product::where('is_recipe',1)->where('is_active', true)->count();
        }else{
            $totalData = DB::table('products')->where('is_active', true)->count();
        }
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'products.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        //fetching custom fields data
        $custom_fields = CustomField::where([
                        ['belongs_to', 'product'],
                        ['is_table', true]
                    ])->pluck('name');
        $field_names = [];
        foreach($custom_fields as $fieldName) {
            $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        }
        if(empty($request->input('search.value'))){
            $query = Product::with('category', 'brand', 'unit')->offset($start)
                        ->where('is_active', true)
                        ->limit($limit)
                        ->orderBy($order,$dir);
            $products = $is_recipe ? $query->where('is_recipe',1)->get() : $query->get();
        } else {
            $search = $request->input('search.value');
            $q = Product::select('products.*')
                ->with('category', 'brand', 'unit')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->leftjoin('product_purchases','product_purchases.product_id','=', 'products.id')
                ->leftjoin('brands', 'products.brand_id', '=', 'brands.id')
                ->leftjoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->where([
                    ['products.name', 'LIKE', "%{$search}%"],
                    ['products.is_active', true]
                ])
                ->orWhere([
                    ['products.code', 'LIKE', "%{$search}%"],
                    ['products.is_active', true]
                ])
                ->orWhere([
                    ['product_variants.item_code', 'LIKE', "%{$search}%"],
                    ['products.is_active', true]
                ])
                ->orWhere([
                    ['categories.name', 'LIKE', "%{$search}%"],
                    ['categories.is_active', true],
                    ['products.is_active', true]
                ])
                ->orWhere([
                    ['brands.title', 'LIKE', "%{$search}%"],
                    ['brands.is_active', true],
                    ['products.is_active', true]
                ])
                ->orWhere([
                    ['product_purchases.imei_number', 'LIKE', "%{$search}%"],
                    ['products.is_active', true]
                ]);
            //searching with custom field
            foreach ($field_names as $key => $field_name) {
                $q = $q->orwhere('products.' . $field_name, 'LIKE', "%{$search}%");
            }

            if ($is_recipe) {
                $q = $q->where('products.is_recipe', 1);
            }
            $q = $q->offset($start)
                ->limit($limit)
                ->orderBy($order,$dir);

            $products = $q->groupBy('products.id')->get();
            $totalFiltered = $q->groupBy('products.id')->count();
            /*$totalFiltered = Product::
                            join('categories', 'products.category_id', '=', 'categories.id')
                            ->leftjoin('brands', 'products.brand_id', '=', 'brands.id')
                            ->where([
                                ['products.name','LIKE',"%{$search}%"],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['products.code', 'LIKE', "%{$search}%"],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['categories.name', 'LIKE', "%{$search}%"],
                                ['categories.is_active', true],
                                ['products.is_active', true]
                            ])
                            ->orWhere([
                                ['brands.title', 'LIKE', "%{$search}%"],
                                ['brands.is_active', true],
                                ['products.is_active', true]
                            ])
                            ->count();*/
        }
        $data = array();
        if(!empty($products))
        {
            $all_units = Unit::pluck('unit_name', 'id')->toArray();
            foreach ($products as $key=>$product)
            {
                $nestedData['id'] = $product->id;
                $nestedData['key'] = $key;
                $product_image = explode(",", $product->image);
                $product_image = htmlspecialchars($product_image[0]);
                if($product_image && $product_image != 'zummXD2dvAtI.png') {
                    if(file_exists("public/images/product/small/". $product_image))
                        $nestedData['image'] = '<img src="'.url('images/product/small', $product_image).'" height="50" width="50">';
                    else
                        $nestedData['image'] = '<img src="'.url('images/product', $product_image).'" height="50" width="50">';
                }
                else
                    $nestedData['image'] = '<img src="images/zummXD2dvAtI.png" height="50" width="50">';
                $nestedData['name'] = '<div class="d-flex align-items-center">'.$nestedData['image'].'  <span style="color:#111;margin:0 10px;">'.$product->name.'</span></div>';
                $nestedData['code'] = $product->code;
                if($product->brand)
                    $nestedData['brand'] = $product->brand->title;
                else
                    $nestedData['brand'] = "N/A";
                $nestedData['category'] = $product->category->name;
                if($warehouse_id > 0 && $product->type == 'standard') {
                    $nestedData['qty'] = Product_Warehouse::where([
                                                ['product_id', $product->id],
                                                ['warehouse_id', $warehouse_id]
                                            ])->sum('qty');
                }
                elseif($product->type == 'standard') {
                    $nestedData['qty'] = Product_Warehouse::where([
                        ['product_id', $product->id],
                    ])->sum('qty');
                }
                else
                    $nestedData['qty'] = $product->qty;

                if($product->unit_id)
                    $nestedData['unit'] = $product->unit->unit_name ?? 'N/A';
                else
                    $nestedData['unit'] = 'N/A';

                if($product->type == 'combo' || $product->is_recipe == 1){
                    $nestedData['wastage_percent'] = $product->wastage_percent ?? 'N/A';
                    $combo_unit_id = $product->combo_unit_id;
                    if ($combo_unit_id && $combo_unit_id !== 'N/A') {
                        $combo_unit_arr = explode(',', $combo_unit_id);
                        $combo_unit_names = array_map(function ($id) use ($all_units) {
                            return $all_units[$id] ?? '';
                        }, $combo_unit_arr);
                        $nestedData['combo_unit'] = implode(',', $combo_unit_names);
                    } else {
                        $nestedData['combo_unit'] = 'N/A';
                    }
                }
                else{
                    $nestedData['combo_unit_id'] = 'N/A';
                }
                $nestedData['cost'] = $product->cost;
                $nestedData['price'] = $product->price;

                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.($nestedData['qty'] * $product->price).' / '.config('currency').' '.($nestedData['qty'] * $product->cost);
                else
                    $nestedData['stock_worth'] = ($nestedData['qty'] * $product->price).' '.config('currency').' / '.($nestedData['qty'] * $product->cost).' '.config('currency');

                //fetching custom fields data
                foreach($field_names as $field_name) {
                    $nestedData[$field_name] = $product->$field_name;
                }

                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.__("db.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            <li>
                                <button="type" class="btn btn-link view"><i class="ti ti-eye"></i> '.__('db.View').'</button>
                            </li>';

                if(in_array("products-edit", $request['all_permission']))
                    $nestedData['options'] .= '<li>
                            <a href="'.route('manufacturing.recipes.edit', $product->id).'" class="btn btn-link"><i class="ti ti-edit"></i> '.__('db.edit').'</a>
                        </li>';

                if(in_array("products-delete", $request['all_permission']))
                    $nestedData['options'] .= '<form method="POST" action="'.route('manufacturing.recipes.destroy', $product->id).'">
                            '.csrf_field().'
                            '.method_field("DELETE").'
                            <li>
                              <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="ti ti-trash"></i> '.__("db.delete").'</button>
                            </li></form>
                        </ul>
                    </div>';
                // data for product details by one click
                if($product->tax_id)
                    $tax = Tax::find($product->tax_id)->name ?? "N/A";
                else
                    $tax = "N/A";

                if($product->tax_method == 1)
                    $tax_method = __('db.Exclusive');
                else
                    $tax_method = __('db.Inclusive');

                $nestedData['product'] = array( '[ "'.$product->type.'"', ' "'.$product->name.'"', ' "'.$product->code.'"', ' "'.$nestedData['brand'].'"', ' "'.$nestedData['category'].'"', ' "'.$nestedData['unit'].'"', ' "'.$product->cost.'"', ' "'.$product->price.'"', ' "'.$tax.'"', ' "'.$tax_method.'"', ' "'.$product->alert_quantity.'"', ' "'.preg_replace('/\s+/S', " ", $product->product_details).'"', ' "'.$product->id.'"', ' "'.$product->product_list.'"', ' "'.$product->variant_list.'"', ' "'.$product->qty_list.'"', ' "'.$product->price_list.'"', ' "'.$nestedData['qty'].'"', ' "'.$product->image.'"', ' "'.$product->is_variant.'"','"'.@$nestedData['combo_unit'].'"','"'.@$nestedData['wastage_percent'].'"', '"'.@$product->combo_unit_id.'"]' );
                //$nestedData['imagedata'] = DNS1D::getBarcodePNG($product->code, $product->barcode_symbology);
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
}
