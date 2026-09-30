<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product_Warehouse;
use App\Models\Product;
use App\Models\Adjustment;
use App\Models\ProductAdjustment;
use Illuminate\Support\Facades\DB;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\StockCount;
use App\Models\ProductVariant;
use App\Models\ProductPurchase;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdjustmentController extends Controller
{
    protected $adjustmentDomainService;

    public function __construct(?\App\Services\Domain\AdjustmentDomainService $adjustmentDomainService = null)
    {
        $this->adjustmentDomainService = $adjustmentDomainService ?? app(\App\Services\Domain\AdjustmentDomainService::class);
    }


    public function index()

    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('adjustment') ) {
            /*if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $lims_adjustment_all = Adjustment::orderBy('id', 'desc')->where('user_id', Auth::id())->get();
            else*/
                $lims_adjustment_all = Adjustment::orderBy('id', 'desc')->get();
            return view('backend.adjustment.index', compact('lims_adjustment_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function getProduct($warehouseId)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Fetch purchase summary (ONE QUERY)
        |--------------------------------------------------------------------------
        */
        $purchaseSummary = DB::table('product_purchases')
            ->join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
            ->where('purchases.warehouse_id', $warehouseId)
            ->whereNull('purchases.deleted_at')
            ->groupBy('product_purchases.product_id', 'product_purchases.variant_id')
            ->selectRaw('
                product_purchases.product_id,
                product_purchases.variant_id,
                SUM(product_purchases.qty) AS total_qty,
                SUM(product_purchases.total) AS total_cost
            ')
            ->get()
            ->keyBy(function ($row) {
                return $row->product_id . '_' . ($row->variant_id ?? 0);
            });

        /*
        |--------------------------------------------------------------------------
        | 2. Fetch non-variant products (ONE QUERY)
        |--------------------------------------------------------------------------
        */
        $products = DB::table('products')
                    ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                    ->whereNull('products.is_variant')
                    ->where('products.is_active', 1)
                    ->where('product_warehouse.warehouse_id', $warehouseId)
                    ->groupBy('products.id', 'products.code', 'products.name', 'products.cost')
                    ->select(
                        'products.id',
                        'products.code',
                        'products.name',
                        'products.cost',
                        DB::raw('SUM(product_warehouse.qty) as qty')
                    )
                    ->get();


        /*
        |--------------------------------------------------------------------------
        | 3. Fetch variant products (ONE QUERY)
        |--------------------------------------------------------------------------
        */
        $variantProducts = DB::table('products')
                          ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                          ->join('product_variants', 'product_warehouse.variant_id', '=', 'product_variants.id')
                          ->whereNotNull('products.is_variant')
                          ->where('products.is_active', 1)
                          ->where('product_warehouse.warehouse_id', $warehouseId)
                          ->groupBy(
                              'products.id',
                              'products.name',
                              'products.cost',
                              'product_variants.item_code',
                              'product_variants.id'
                          )
                          ->select(
                              'products.id',
                              'products.name',
                              'products.cost',
                              DB::raw('SUM(product_warehouse.qty) as qty'),
                              'product_variants.item_code',
                              'product_variants.id as variant_id'
                          )
                          ->get();


        /*
        |--------------------------------------------------------------------------
        | 4. Build result in memory (FAST)
        |--------------------------------------------------------------------------
        */
        $product_code = [];
        $product_name = [];
        $product_qty  = [];
        $product_cost = [];

        /* Normal products */
        foreach ($products as $p) {
            $key = $p->id . '_0';
            $summary = $purchaseSummary[$key] ?? null;

            $cost = ($summary && $summary->total_qty > 0)
                ? round($summary->total_cost / $summary->total_qty, 4)
                : $p->cost;

            $product_code[] = $p->code;
            $product_name[] = $p->name;
            $product_qty[]  = $p->qty;
            $product_cost[] = $cost;
        }

        /* Variant products */
        foreach ($variantProducts as $p) {
            $key = $p->id . '_' . $p->variant_id;
            $summary = $purchaseSummary[$key] ?? null;

            $cost = ($summary && $summary->total_qty > 0)
                ? round($summary->total_cost / $summary->total_qty, 4)
                : $p->cost;

            $product_code[] = $p->item_code;
            $product_name[] = $p->name;
            $product_qty[]  = $p->qty;
            $product_cost[] = $cost;
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Return in original SalePro format
        |--------------------------------------------------------------------------
        */
        return [
            $product_code,
            $product_name,
            $product_qty,
            $product_cost
        ];
    }

    public function limsProductSearch(Request $request)
    {
        // dd($request->all());
        $product_code = explode("|", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where('code', $product_code[0])
                            ->whereIn('is_active', [1, 3])
                            ->whereNull('is_variant')
                            ->first();
        if(!$lims_product_data) {
            $lims_product_data = Product::where('name', $product_code[1])
                            ->whereIn('is_active', [1, 3])
                            ->whereNotNull(['is_variant'])
                            ->first();
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->where('product_variants.item_code', $product_code[0])
                ->whereIn('products.is_active', [1, 3])
                ->whereNotNull('is_variant')
                ->select('products.*', 'product_variants.item_code', 'product_variants.additional_cost')
                ->first();
            if ($lims_product_data) {
                $lims_product_data->cost += $lims_product_data->additional_cost;
            }
        }
        $product[] = $lims_product_data->name; //0
        if($lims_product_data->is_variant)
            $product[] = $lims_product_data->item_code; //1
        else
            $product[] = $lims_product_data->code;//1

        $product[] = $lims_product_data->cost;//2
        $product['profit_margin'] = $lims_product_data->profit_margin;
        $product['profit_margin_type'] = $lims_product_data->profit_margin_type;
        $product['product_price'] = $lims_product_data->price;

        $cost = (float)$lims_product_data->cost;
        $price = (float)$lims_product_data->price;

        if ($cost > 0 && $lims_product_data->profit_margin_type === 'percentage') {
            $calculatedMargin = (($price - $cost) / $cost) * 100;
        } else if ($cost > 0 && $lims_product_data->profit_margin_type === 'flat') {
            $calculatedMargin = $price - $cost;
        } else {
            $calculatedMargin = 0; // or null, or skip updating
        }

        if (round($calculatedMargin, 2) != round((float)$lims_product_data->profit_margin, 2)) {
            $product['profit_margin'] = $calculatedMargin;
        }

        if ($lims_product_data->tax_id) {
            $lims_tax_data = Tax::find($lims_product_data->tax_id);
            $product[] = $lims_tax_data->rate;//3
            $product[] = $lims_tax_data->name;//4
        } else {
            $product[] = 0;//3
            $product[] = 'No Tax';//4
        }
        $product[] = $lims_product_data->tax_method;//5

        $units = Unit::where("base_unit", $lims_product_data->unit_id)
                    ->orWhere('id', $lims_product_data->unit_id)
                    ->get();
        $unit_name = array();
        $unit_operator = array();
        $unit_operation_value = array();
        foreach ($units as $unit) {
            if ($lims_product_data->purchase_unit_id == $unit->id) {
                array_unshift($unit_name, $unit->unit_name);
                array_unshift($unit_operator, $unit->operator);
                array_unshift($unit_operation_value, $unit->operation_value);
            } else {
                $unit_name[]  = $unit->unit_name;
                $unit_operator[] = $unit->operator;
                $unit_operation_value[] = $unit->operation_value;
            }
        }

        $product[] = implode(",", $unit_name) . ','; //6
        $product[] = implode(",", $unit_operator) . ','; //7
        $product[] = implode(",", $unit_operation_value) . ','; //8
        $product[] = $lims_product_data->id; //9
        $product[] = $lims_product_data->is_batch; //10
        $product[] = $lims_product_data->is_imei; //11
        // return dd($product);
        return $product;
    }

    // public function limsProductSearch(Request $request)
    // {
    //     $product_code = explode("|", $request['data']);
    //     $product_code[0] = rtrim($product_code[0], " ");
    //     $lims_product_data = Product::where([
    //                             ['code', $product_code[0]],
    //                             ['is_active', true]
    //                         ])
    //                         ->whereNull('is_variant')
    //                         ->first();
    //     if(!$lims_product_data) {
    //         $lims_product_data = Product::where([
    //                             ['name', $product_code[1]],
    //                             ['is_active', true]
    //                         ])
    //                         ->whereNotNull(['is_variant'])
    //                         ->first();
    //         $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
    //             ->where([
    //                 ['product_variants.item_code', $product_code[0]],
    //                 ['products.is_active', true]
    //             ])
    //             ->whereNotNull('is_variant')
    //             ->select('products.*', 'product_variants.item_code', 'product_variants.additional_cost')
    //             ->first();
    //         $lims_product_data->cost += $lims_product_data->additional_cost;
    //     }
    //     $product[] = $lims_product_data->name;
    //     if($lims_product_data->is_variant)
    //         $product[] = $lims_product_data->item_code;
    //     else
    //         $product[] = $lims_product_data->code;
    //     $product[] = $lims_product_data->cost;

    //     if ($lims_product_data->tax_id) {
    //         $lims_tax_data = Tax::find($lims_product_data->tax_id);
    //         $product[] = $lims_tax_data->rate;
    //         $product[] = $lims_tax_data->name;
    //     } else {
    //         $product[] = 0;
    //         $product[] = 'No Tax';
    //     }
    //     $product[] = $lims_product_data->tax_method;

    //     $units = Unit::where("base_unit", $lims_product_data->unit_id)
    //                 ->orWhere('id', $lims_product_data->unit_id)
    //                 ->get();
    //     $unit_name = array();
    //     $unit_operator = array();
    //     $unit_operation_value = array();
    //     foreach ($units as $unit) {
    //         if ($lims_product_data->purchase_unit_id == $unit->id) {
    //             array_unshift($unit_name, $unit->unit_name);
    //             array_unshift($unit_operator, $unit->operator);
    //             array_unshift($unit_operation_value, $unit->operation_value);
    //         } else {
    //             $unit_name[]  = $unit->unit_name;
    //             $unit_operator[] = $unit->operator;
    //             $unit_operation_value[] = $unit->operation_value;
    //         }
    //     }

    //     $product[] = implode(",", $unit_name) . ',';
    //     $product[] = implode(",", $unit_operator) . ',';
    //     $product[] = implode(",", $unit_operation_value) . ',';
    //     $product[] = $lims_product_data->id;
    //     $product[] = $lims_product_data->is_batch;
    //     $product[] = $lims_product_data->is_imei;
    //     // return dd($product);
    //     return $product;
    // }

    public function create()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        return view('backend.adjustment.create', compact('lims_warehouse_list', 'lims_product_list_without_variant', 'lims_product_list_with_variant'));

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
            ->select('products.id', 'products.name', 'product_variants.item_code')
            ->orderBy('position')
            ->get();
    }

    public function store(Request $request)
    {
        try {
            $data = $request->except('document');
            $document = $request->document;
            if ($document) {
                $documentName = $document->getClientOriginalName();
                $document->move(public_path('documents/adjustment'), $documentName);
                $data['document'] = $documentName;
            }

            $this->adjustmentDomainService->createAdjustment($data, Auth::user());

            return redirect('qty_adjustment')->with('message', __('db.Data inserted successfully'));
        } catch (\Throwable $e) {
            return redirect('qty_adjustment')
                ->with('not_permitted', $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }


    public function edit($id)
    {
        $lims_adjustment_data = Adjustment::findOrFail($id);
        $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        
        $lims_product_list_without_variant = $this->productWithoutVariant();
        $lims_product_list_with_variant = $this->productWithVariant();
        return view('backend.adjustment.edit',  compact('lims_adjustment_data', 'lims_warehouse_list', 'lims_product_adjustment_data','lims_product_list_without_variant', 'lims_product_list_with_variant'));
    }

    public function update(Request $request, $id)
    {
        try{
            DB::beginTransaction();
            $data = $request->except('document');
                $lims_adjustment_data = Adjustment::findOrFail($id);

                $document = $request->document;
                if ($document) {
                    $this->fileDelete(public_path('documents/adjustment/'), $lims_adjustment_data->document);

                    $documentName = $document->getClientOriginalName();
                    $document->move(public_path('documents/adjustment'), $documentName);
                    $data['document'] = $documentName;
                }

                $lims_adjustment_data = Adjustment::find($id);
                $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();


                $product_id = $data['product_id'];
                $product_variant_id = $data['product_variant_id'];
                $product_code = $data['product_code'];
                $qty = $data['qty'];
                $unit_cost = $data['unit_cost'];
                $action = $data['action'];
                $old_product_variant_id = [];
                foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {

                    $old_product_id[] = $product_adjustment_data->product_id;
                    $lims_product_data = Product::find($product_adjustment_data->product_id);
                    if($product_adjustment_data->variant_id) {
                        $lims_product_variant_data = ProductVariant::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id]
                        ])->first();
                        $old_product_variant_id[$key] = $lims_product_variant_data->id;
                        if($product_adjustment_data->action == '-') {
                            $lims_product_variant_data->qty += $product_adjustment_data->qty;
                        }
                        elseif($product_adjustment_data->action == '+') {
                            $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                        }
                        $lims_product_variant_data->save();
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    }
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $product_adjustment_data->product_id],
                                ['warehouse_id', $lims_adjustment_data->warehouse_id]
                            ])->first();
                    }
                    
                    if($product_adjustment_data->action == '-'){
                        $lims_product_data->qty += $product_adjustment_data->qty;
                        if ($lims_product_warehouse_data) $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                    }
                    elseif($product_adjustment_data->action == '+'){
                        $lims_product_data->qty -= $product_adjustment_data->qty;
                        if ($lims_product_warehouse_data) $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                    }

                    $lims_product_data->save();
                    if ($lims_product_warehouse_data) $lims_product_warehouse_data->save();

                    if($product_adjustment_data->variant_id && !(in_array($old_product_variant_id[$key], $product_variant_id)) ){
                        $product_adjustment_data->delete();
                    }
                    elseif( !(in_array($old_product_id[$key], $product_id)) )
                        $product_adjustment_data->delete();
                }

                foreach ($product_id as $key => $pro_id) {

                    $lims_product_data = Product::find($pro_id);
                    if($lims_product_data->is_variant) {
                        $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['variant_id', $lims_product_variant_data->variant_id ],
                            ['warehouse_id', $data['warehouse_id'] ],
                        ])->first();

                        if($action[$key] == '-'){
                            $lims_product_variant_data->qty -= $qty[$key];
                        }
                        elseif($action[$key] == '+'){
                            $lims_product_variant_data->qty += $qty[$key];
                        }
                        $lims_product_variant_data->save();
                        $variant_id = $lims_product_variant_data->variant_id;
                    }
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id'] ],
                            ])->first();
                        $variant_id = null;
                    }


                    if($action[$key] == '-'){
                        if ($lims_product_warehouse_data) $lims_product_warehouse_data->qty -= $qty[$key];
                        $lims_product_data->qty -= $qty[$key];
                    }
                    elseif($action[$key] == '+'){
                        if ($lims_product_warehouse_data) $lims_product_warehouse_data->qty += $qty[$key];
                        $lims_product_data->qty += $qty[$key];
                    }
                    if ($lims_product_warehouse_data) $lims_product_warehouse_data->save();
                    $lims_product_data->save();

                    $product_adjustment['product_id'] = $pro_id;
                    $product_adjustment['variant_id'] = $variant_id;
                    $product_adjustment['adjustment_id'] = $id;
                    $product_adjustment['unit_cost'] = $unit_cost[$key];
                    $product_adjustment['action'] = $action[$key];


                    if($product_adjustment['variant_id'] && in_array($product_variant_id[$key], $old_product_variant_id)) {
                       $adjustment = ProductAdjustment::where([
                            ['product_id', $pro_id],
                            ['variant_id', $product_adjustment['variant_id']],
                            ['adjustment_id', $id]
                        ])->first();
                        if($action[$key] == '-'){
                            $product_adjustment['qty'] = $adjustment->qty - $qty[$key];
                        }
                        elseif($action[$key] == '+'){
                            $product_adjustment['qty'] = $adjustment->qty + $qty[$key];
                        }
                        $adjustment->update($product_adjustment);
                    }
                    elseif( $product_adjustment['variant_id'] === null && in_array($pro_id, $old_product_id) ){
                       $adjustment =  ProductAdjustment::where([
                        ['adjustment_id', $id],
                        ['product_id', $pro_id]
                        ])->first();
                        if($action[$key] == '-'){
                            $product_adjustment['qty'] = $adjustment->qty - $qty[$key];
                        }
                        elseif($action[$key] == '+'){
                            $product_adjustment['qty'] = $adjustment->qty + $qty[$key];
                        }
                        $adjustment->update($product_adjustment);
                    }
                    else{
                        $product_adjustment['qty'] = $qty[$key];
                        ProductAdjustment::create($product_adjustment);
                    }
                }
                $lims_adjustment_data->update($data);
             DB::commit();
             return redirect('qty_adjustment')->with('message', __('db.Data updated successfully'));
        }catch(\Throwable $e){
            DB::rollBack();
            dd($e);
            return redirect('qty_adjustment')->with('not_permitted', __('db.someting_error
_please_try'));
        }
    }

    public function deleteBySelection(Request $request)
    {
        try {
            \DB::beginTransaction();
            $adjustment_id = $request['adjustmentIdArray'];
            $files_to_delete = [];
            foreach ($adjustment_id as $id) {
                $lims_adjustment_data = Adjustment::find($id);
                if ($lims_adjustment_data->document) {
                    $files_to_delete[] = $lims_adjustment_data->document;
                }

                $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
            foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {
                $lims_product_data = Product::find($product_adjustment_data->product_id);
                if($product_adjustment_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    if($product_adjustment_data->action == '-'){
                        $lims_product_variant_data->qty += $product_adjustment_data->qty;
                    }
                    elseif($product_adjustment_data->action == '+'){
                        $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                    }
                    $lims_product_variant_data->save();
                }
                else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                }
                if($product_adjustment_data->action == '-'){
                    $lims_product_data->qty += $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                }
                elseif($product_adjustment_data->action == '+'){
                    $lims_product_data->qty -= $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                }
                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_adjustment_data->delete();
            }
            $lims_adjustment_data->delete();
            }
            \DB::commit();
            
            foreach ($files_to_delete as $file) {
                $this->fileDelete(public_path('documents/adjustment/'), $file);
            }
            return 'Data deleted successfully';
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Adjustment bulk deletion failed: ' . $e->getMessage());
            return 'Adjustment bulk deletion failed: ' . $e->getMessage();
        }
    }

    public function destroy($id)
    {
        try {
            \DB::beginTransaction();
            $lims_adjustment_data = Adjustment::find($id);
            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {
            $lims_product_data = Product::find($product_adjustment_data->product_id);
            if($product_adjustment_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['variant_id', $product_adjustment_data->variant_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
                if($product_adjustment_data->action == '-'){
                    $lims_product_variant_data->qty += $product_adjustment_data->qty;
                }
                elseif($product_adjustment_data->action == '+'){
                    $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                }
                $lims_product_variant_data->save();
            }
            else {
                $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
            }
            if($product_adjustment_data->action == '-'){
                $lims_product_data->qty += $product_adjustment_data->qty;
                $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
            }
            elseif($product_adjustment_data->action == '+'){
                $lims_product_data->qty -= $product_adjustment_data->qty;
                $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
            }
            $lims_product_data->save();
            $lims_product_warehouse_data->save();
            $product_adjustment_data->delete();
        }
        $lims_adjustment_data->delete();
        \DB::commit();

        $this->fileDelete(public_path('documents/adjustment/'), $lims_adjustment_data->document);

        return redirect('qty_adjustment')->with('not_permitted', __('db.Data deleted successfully'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Adjustment deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Adjustment deletion failed: ' . $e->getMessage());
        }
    }
}
