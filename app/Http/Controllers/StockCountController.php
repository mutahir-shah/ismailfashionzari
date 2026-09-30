<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Product_Warehouse;
use App\Models\Warehouse;
use App\Models\StockCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class StockCountController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('stock_count') ) {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_brand_list = Brand::where('is_active', true)->get();
            $lims_category_list = Category::where('is_active', true)->get();
            $general_setting = DB::table('general_settings')->latest()->first();
            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
                $lims_stock_count_all = StockCount::orderBy('id', 'desc')->where('user_id', Auth::id())->get();
            else
                $lims_stock_count_all = StockCount::orderBy('id', 'desc')->get();

            return view('backend.stock_count.index', compact('lims_warehouse_list', 'lims_brand_list', 'lims_category_list', 'lims_stock_count_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if(isset($request->category_id) || isset($request->brand_id)){
            $data['type'] = "partial";
        }else{
            $data['type'] = "full";
        }
        if( isset($data['brand_id']) && isset($data['category_id']) ){
            $lims_product_list = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereIn('products.category_id', $data['category_id'] )
                ->whereIn('products.brand_id', $data['brand_id'] )
                ->whereIn('products.is_active', [1, 3])
                ->where('product_warehouse.warehouse_id', $data['warehouse_id'])
                ->select('products.name', 'products.code', 'product_warehouse.imei_number', 'product_warehouse.qty')
                ->get();

            $data['category_id'] = implode(",", $data['category_id']);
            $data['brand_id'] = implode(",", $data['brand_id']);
        }
        elseif( isset($data['category_id']) ){
            $lims_product_list = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereIn('products.category_id', $data['category_id'])
                ->whereIn('products.is_active', [1, 3])
                ->where('product_warehouse.warehouse_id', $data['warehouse_id'])
                ->select('products.name', 'products.code', 'product_warehouse.imei_number', 'product_warehouse.qty')
                ->get();

            $data['category_id'] = implode(",", $data['category_id']);
        }
        elseif( isset($data['brand_id']) ){
            $lims_product_list = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereIn('products.brand_id', $data['brand_id'])
                ->whereIn('products.is_active', [1, 3])
                ->where('product_warehouse.warehouse_id', $data['warehouse_id'])
                ->select('products.name', 'products.code', 'product_warehouse.imei_number', 'product_warehouse.qty')
                ->get();

            $data['brand_id'] = implode(",", $data['brand_id']);
        }
        else{
            $lims_product_list = DB::table('products')
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->whereIn('products.is_active', [1, 3])
                ->where('product_warehouse.warehouse_id', $data['warehouse_id'])
                ->select('products.name', 'products.code', 'product_warehouse.imei_number', 'product_warehouse.qty')
                ->get();
        }
        if( count($lims_product_list) ){
            $csvData=array('Product Name, Product Code, IMEI or Serial Numbers, Counted');
            foreach ($lims_product_list as $product) {
                $csvData[]=$product->name.','.$product->code.','.str_replace(",","/",$product->imei_number);
            }
            if (!file_exists(public_path().'/stock_count/')) {
                mkdir(public_path().'/stock_count/', 0777, true);
            }
            $filename= date('Ymd').'-'.date('his'). ".csv";
            $file_path= public_path().'/stock_count/'.$filename;
            $file = fopen($file_path, "w+");
            foreach ($csvData as $cellData){
              fputcsv($file, explode(',', $cellData));
            }
            fclose($file);

            $data['user_id'] = Auth::id();
            $data['reference_no'] = 'scr-' . date("Ymd") . '-'. date("his");
            $data['initial_file'] = $filename;
            $data['is_adjusted'] = false;
            StockCount::create($data);
            return redirect()->back()->with('message', __('db.Stock Count created successfully! Please download the initial file to complete it'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.No product found!'));
    }

    public function finalize(Request $request)
    {
        $ext = pathinfo($request->final_file->getClientOriginalName(), PATHINFO_EXTENSION);
        //checking if this is a CSV file
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));

        $data = $request->all();
        $document = $request->final_file;
        $documentName = date('Ymd').'-'.date('his'). ".csv";
        $document->move(public_path('stock_count/'), $documentName);
        $data['final_file'] = $documentName;
        $lims_stock_count_data = StockCount::find($data['stock_count_id']);
        $lims_stock_count_data->update($data);
        return redirect()->back()->with('message', __('db.Stock Count finalized successfully!'));
    }

    public function stockDif($id)
    {
        $lims_stock_count_data = StockCount::findOrFail($id);
        $warehouse_id = $lims_stock_count_data->warehouse_id;

        $file_path = public_path('stock_count/') . $lims_stock_count_data->final_file;

        if (!file_exists($file_path)) {
            return [];
        }

        $file_handle = fopen($file_path, 'r');

        $i = 0;
        $hasDifference = false;

        $product = [];
        $expected = [];
        $counted = [];
        $difference = [];
        $cost = [];

        while (($current_line = fgetcsv($file_handle)) !== false) {

            // Skip CSV header
            if ($i === 0) {
                $i++;
                continue;
            }

            // CSV safety check
            if (!isset($current_line[1])) {
                continue;
            }

            // Find product by code (exact or LIKE)
            $product_data = Product::select('id', 'code', 'cost')
                ->where('code', $current_line[1])
                ->orWhere('code', 'LIKE', "%{$current_line[1]}%")
                ->first();

            if (!$product_data) {
                continue;
            }

            // Get warehouse quantity
            $product_warehouse = Product_Warehouse::where([
                'warehouse_id' => $warehouse_id,
                'product_id'   => $product_data->id,
            ])->first();

            $expected_qty = (float) ($product_warehouse->qty ?? 0);

            // Clean & cast counted quantity from CSV
            $counted_qty = 0;
            if (isset($current_line[3])) {
                $csvQty = str_replace(',', '', trim($current_line[3]));
                if (is_numeric($csvQty)) {
                    $counted_qty = (float) $csvQty;
                }
            }

            // Calculate difference
            $diff = $counted_qty - $expected_qty;

            if ($diff != 0) {
                $hasDifference = true;
            }

            // Prepare response arrays
            $product[]    = $current_line[0] . ' [' . $product_data->code . ']';
            $expected[]   = $expected_qty;
            $counted[]    = $counted_qty;
            $difference[] = $diff;
            $cost[]       = $diff * (float) $product_data->cost;

            $i++;
        }

        fclose($file_handle);

        // Mark adjusted only if no difference exists
        if (!$hasDifference) {
            $lims_stock_count_data->is_adjusted = true;
            $lims_stock_count_data->save();
        }

        return [
            $product,
            $expected,
            $counted,
            $difference,
            $cost,
            $lims_stock_count_data->is_adjusted
        ];
    }

    public function qtyAdjustment($id)
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_stock_count_data = StockCount::find($id);
        $warehouse_id = $lims_stock_count_data->warehouse_id;
        $file_handle = fopen(public_path('stock_count/').$lims_stock_count_data->final_file, 'r');
        $i = 0;
        $product_id = [];
        while( !feof($file_handle) ) {
            $current_line = fgetcsv($file_handle);
            if( $current_line && $i > 0 ){
                $product_data = Product::select('id','code','qty')->where('code', $current_line[1])->first();
                $product_id[] = $product_data->id;
                $names[] = $current_line[0];
                $code[] = $current_line[1];

                $product_warehouse_data = Product_Warehouse::select('qty')->where([
                    'warehouse_id' => $warehouse_id,
                    'product_id' => $product_data->id,
                ])->first();

                if(isset($current_line[3])) {
                    $temp_qty = $current_line[3] - $product_warehouse_data->qty;
                } else {
                    $temp_qty = $product_warehouse_data->qty * (-1);
                }

                if($temp_qty < 0){
                    $qty[] = $temp_qty * (-1);
                    $action[] = '-';
                }
                else{
                    $qty[] = $temp_qty;
                    $action[] = '+';
                }
            }
            $i++;
        }
        return view('backend.stock_count.qty_adjustment', compact('lims_warehouse_list', 'warehouse_id', 'id', 'product_id', 'names', 'code', 'qty', 'action'));
    }
}
