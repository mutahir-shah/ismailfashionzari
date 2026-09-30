<?php

namespace App\Http\Controllers;

use App\Models\Biller;
use App\Models\Category;
use App\Models\Challan;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomField;
use App\Models\Expense;
use App\Models\GeneralSetting;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product_Sale;
use App\Models\Product_Warehouse;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\ReturnPurchase;
use App\Models\Returns;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Services\Profitability\ProfitabilityFilter;
use App\Services\Profitability\ProfitabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ReportController extends Controller
{
    private $own_data = false;
    private $current_user_id = null;

    public function __construct()
    {
        if (Auth::check()) {
            $this->current_user_id = Auth::id();
            $this->own_data = (Auth::user()->role_id > 2 && config('staff_access') == 'own');
        }
    }

    public function productQuantityAlert(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);

        if(!$role->hasPermissionTo('product-qty-alert')) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $warehouse_id = $request->warehouse_id;

        // A warehouse report must compare the alert threshold with that warehouse's
        // pivot quantity. products.qty is the global aggregate and leaks other
        // warehouses into both the displayed quantity and alert decision.
        if ($warehouse_id && $warehouse_id != 0) {
            $lims_product_data = Product::query()
                ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->select('products.name', 'products.code', 'products.image', 'products.alert_quantity', 'product_warehouse.qty')
                ->where('products.is_active', true)
                ->where('product_warehouse.warehouse_id', $warehouse_id)
                ->whereColumn('products.alert_quantity', '>', 'product_warehouse.qty');
        } else {
            $lims_product_data = Product::select('name', 'code', 'image', 'qty', 'alert_quantity')
                ->where('is_active', true)
                ->whereColumn('alert_quantity', '>', 'qty');
        }

        $lims_product_data = $lims_product_data->get();

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();

        if ($request->ajax()) {
            return view('backend.report.partials.qty_alert_table', compact('lims_product_data', 'lims_warehouse_list', 'warehouse_id'))->render();
        }

        return view('backend.report.qty_alert_report', compact('lims_product_data','lims_warehouse_list', 'warehouse_id'));
    }


    public function dailySaleObjective(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('dso-report')) {
            if($request->input('starting_date')) {
                $starting_date = $request->input('starting_date');
                $ending_date = $request->input('ending_date');
            }
            else {
                $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m-d') )))));
                $ending_date = date("Y-m-d");
            }
            return view('backend.report.daily_sale_objective', compact('starting_date', 'ending_date'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function dailySaleObjectiveData(Request $request)
    {
        $starting_date = date("Y-m-d", strtotime("+1 day", strtotime($request->input('starting_date'))));
        $ending_date = date("Y-m-d", strtotime("+1 day", strtotime($request->input('ending_date'))));

        $columns = array(
            1 => 'created_at',
        );
        $totalData = DB::table('dso_alerts')
                    ->whereDate('created_at', '>=', $starting_date)
                    ->whereDate('created_at', '<=', $ending_date)
                    ->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value'))) {
            $lims_dso_alert_data = DB::table('dso_alerts')
                                  ->whereDate('created_at', '>=', $starting_date)
                                  ->whereDate('created_at', '<=', $ending_date)
                                  ->offset($start)
                                  ->limit($limit)
                                  ->orderBy($order, $dir)
                                  ->get();
        }
        else
        {
            $search = $request->input('search.value');
            $lims_dso_alert_data = DB::table('dso_alerts') 
                                  ->whereDate('dso_alerts.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                                  ->offset($start)
                                  ->limit($limit)
                                  ->orderBy($order, $dir)
                                  ->get();
        }
        $data = array();
        if(!empty($lims_dso_alert_data))
        {
            foreach ($lims_dso_alert_data as $key => $dso_alert_data)
            {
                $nestedData['id'] = $dso_alert_data->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime("-1 day", strtotime($dso_alert_data->created_at)));
                foreach (json_decode($dso_alert_data->product_info) as $index => $product_info) {
                    if($index)
                        $nestedData['product_info'] .= ', ';
                    $nestedData['product_info'] = $product_info->name.' ['.$product_info->code.']';
                }
                $nestedData['number_of_products'] = $dso_alert_data->number_of_products;
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

    public function productExpiry()
    {
        // $general_settings_data = GeneralSetting::select('expiry_type','expiry_value')->first();

        // $date = date('Y-m-d', strtotime('+'.$general_settings_data["expiry_value"].' '.$general_settings_data["expiry_type"]));
        $lims_product_data = DB::table('products')
                            ->join('product_batches', 'products.id', '=', 'product_batches.product_id')
                            // ->whereDate('product_batches.expired_date', '<=', $date)
                            ->where([
                                ['products.is_active', true],
                                ['product_batches.qty', '>', 0]
                            ])
                            ->select('products.name', 'products.code', 'products.image', 'product_batches.batch_no', 'product_batches.batch_no', 'product_batches.expired_date', 'product_batches.qty')
                            ->get();
        return view('backend.report.product_expiry_report', compact('lims_product_data'));
    }

    public function warehouseStock(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('warehouse-stock-report')) {
            if(isset($request->warehouse_id))
                $warehouse_id = $request->warehouse_id;
            else
                $warehouse_id = 0;
            if(!$warehouse_id) {
                $total_item = DB::table('product_warehouse')
                            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                            ->where([
                                ['products.is_active', true],
                                ['product_warehouse.qty', '>' , 0]
                            ])->count();

                $total_qty = DB::table('product_warehouse')
                    ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                    ->where('products.is_active', true)
                    ->sum('product_warehouse.qty');

                $total_price = DB::table('products')->where('is_active', true)->sum(DB::raw('price * qty'));
                $total_cost = DB::table('products')->where('is_active', true)->sum(DB::raw('cost * qty'));
            }
            else {
                $total_item = DB::table('product_warehouse')
                            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                            ->where([
                                ['products.is_active', true],
                                ['product_warehouse.qty', '>' , 0],
                                ['product_warehouse.warehouse_id', $warehouse_id]
                            ])->count();
                $total_qty = DB::table('product_warehouse')
                                ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                                ->where([
                                    ['products.is_active', true],
                                    ['product_warehouse.warehouse_id', $warehouse_id]
                                ])->sum('product_warehouse.qty');
                $total_price = DB::table('product_warehouse')
                                ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                                ->where([
                                    ['products.is_active', true],
                                    ['product_warehouse.warehouse_id', $warehouse_id]
                                ])->sum(DB::raw('products.price * product_warehouse.qty'));
                $total_cost = DB::table('product_warehouse')
                                ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                                ->where([
                                    ['products.is_active', true],
                                    ['product_warehouse.warehouse_id', $warehouse_id]
                                ])->sum(DB::raw('products.cost * product_warehouse.qty'));
            }

            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            if($request->ajax()) {
                return view('backend.report.partials.warehouse_stock_table', compact('total_item', 'total_qty', 'total_price', 'total_cost', 'lims_warehouse_list', 'warehouse_id'))->render();
            }
            return view('backend.report.warehouse_stock', compact('total_item', 'total_qty', 'total_price', 'total_cost', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function dailySale(Request $request, $year, $month)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('daily-sale')){
            $start = 1;
            $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
            while($start <= $number_of_day)
            {
                if($start < 10)
                    $date = $year.'-'.$month.'-0'.$start;
                else
                    $date = $year.'-'.$month.'-'.$start;
                $query1 = array(
                    'SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) as total_discount',
                    'SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) as order_discount',
                    'SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) as total_tax',
                    'SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) as order_tax',
                    'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) as shipping_cost',
                    'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) as grand_total'
                );
                $sale_data = Sale::whereDate('created_at', $date)
                        ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                        ->whereNull('deleted_at')   
                        ->where(function ($q) {
                            $q->where('sale_type', '!=', 'opening balance')
                            ->orWhereNull('sale_type');
                        })
                        ->selectRaw(implode(',', $query1))
                        ->get();

                $total_discount[$start] = number_format($sale_data[0]->total_discount, config('decimal'));
                $order_discount[$start] = number_format($sale_data[0]->order_discount, config('decimal'));
                $total_tax[$start] = number_format($sale_data[0]->total_tax, config('decimal'));
                $order_tax[$start] = number_format($sale_data[0]->order_tax, config('decimal'));
                $shipping_cost[$start] = number_format($sale_data[0]->shipping_cost, config('decimal'));
                $grand_total[$start] = number_format($sale_data[0]->grand_total, config('decimal'));
                $start++;
            }
            $start_day = date('w', strtotime($year.'-'.$month.'-01')) + 1;
            $prev_year = date('Y', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
            $prev_month = date('m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
            $next_year = date('Y', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
            $next_month = date('m', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $warehouse_id = 0;

            if ($request->ajax()) {
                return view('backend.report.partials.daily_sale_table', compact(
                    'total_discount','order_discount','total_tax','order_tax',
                    'shipping_cost','grand_total','start_day','year','month',
                    'number_of_day','prev_year','prev_month','next_year','next_month'
                ))->render();
            }

            return view('backend.report.daily_sale', compact('total_discount','order_discount', 'total_tax', 'order_tax', 'shipping_cost', 'grand_total', 'start_day', 'year', 'month', 'number_of_day', 'prev_year', 'prev_month', 'next_year', 'next_month', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function dailySaleByWarehouse(Request $request,$year,$month)
    {
        $data = $request->all();
        if($data['warehouse_id'] == 0)
            return $this->dailySale($request, $year, $month);
        $start = 1;
        $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        while($start <= $number_of_day)
        {
            if($start < 10)
                $date = $year.'-'.$month.'-0'.$start;
            else
                $date = $year.'-'.$month.'-'.$start;
            $query1 = array(
                'SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) as total_discount',
                'SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) as order_discount',
                'SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) as total_tax',
                'SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) as order_tax',
                'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) as shipping_cost',
                'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) as grand_total'
            );
            $sale_data = Sale::where('warehouse_id', $data['warehouse_id'])
                        ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                        ->whereDate('created_at', $date)
                        ->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('sale_type', '!=', 'opening balance')
                            ->orWhereNull('sale_type');
                        })
                        ->selectRaw(implode(',', $query1))
                        ->get();
            $total_discount[$start] = number_format($sale_data[0]->total_discount, config('decimal'));
            $order_discount[$start] = number_format($sale_data[0]->order_discount, config('decimal'));
            $total_tax[$start] = number_format($sale_data[0]->total_tax, config('decimal'));
            $order_tax[$start] = number_format($sale_data[0]->order_tax, config('decimal'));
            $shipping_cost[$start] = number_format($sale_data[0]->shipping_cost, config('decimal'));
            $grand_total[$start] = number_format($sale_data[0]->grand_total, config('decimal'));
            $start++;
        }
        $start_day = date('w', strtotime($year.'-'.$month.'-01')) + 1;
        $prev_year = date('Y', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $prev_month = date('m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $next_year = date('Y', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
        $next_month = date('m', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $warehouse_id = $data['warehouse_id'];

        if ($request->ajax()) {
            return view('backend.report.partials.daily_sale_table', compact(
                'total_discount','order_discount','total_tax','order_tax',
                'shipping_cost','grand_total','start_day','year','month',
                'number_of_day','prev_year','prev_month','next_year','next_month'
            ))->render();
        }

        return view('backend.report.daily_sale', compact('total_discount','order_discount', 'total_tax', 'order_tax', 'shipping_cost', 'grand_total', 'start_day', 'year', 'month', 'number_of_day', 'prev_year', 'prev_month', 'next_year', 'next_month', 'lims_warehouse_list', 'warehouse_id'));

    }

    public function dailyPurchase(Request $request, $year, $month)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('daily-purchase')){
            $start = 1;
            $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
            while($start <= $number_of_day)
            {
                if($start < 10)
                    $date = $year.'-'.$month.'-0'.$start;
                else
                    $date = $year.'-'.$month.'-'.$start;
                $query1 = array(
                    'SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_discount',
                    'SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_discount',
                    'SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_tax',
                    'SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_tax',
                    'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS shipping_cost',
                    'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total'
                );
                $purchase_data = Purchase::whereDate('created_at', $date)
                                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                                ->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->whereNull('purchase_type')
                                    ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                })
                                ->selectRaw(implode(',', $query1))
                                ->get();
                $total_discount[$start] = $purchase_data[0]->total_discount;
                $order_discount[$start] = $purchase_data[0]->order_discount;
                $total_tax[$start] = $purchase_data[0]->total_tax;
                $order_tax[$start] = $purchase_data[0]->order_tax;
                $shipping_cost[$start] = $purchase_data[0]->shipping_cost;
                $grand_total[$start] = $purchase_data[0]->grand_total;
                $start++;
            }
            $start_day = date('w', strtotime($year.'-'.$month.'-01')) + 1;
            $prev_year = date('Y', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
            $prev_month = date('m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
            $next_year = date('Y', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
            $next_month = date('m', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $warehouse_id = 0;
            return view('backend.report.daily_purchase', compact('total_discount','order_discount', 'total_tax', 'order_tax', 'shipping_cost', 'grand_total', 'start_day', 'year', 'month', 'number_of_day', 'prev_year', 'prev_month', 'next_year', 'next_month', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function dailyPurchaseByWarehouse(Request $request, $year, $month)
    {
        $warehouse_id = $request->warehouse_id;

        $start = 1;
        $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));

        while($start <= $number_of_day)
        {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $start);

            $purchase_data = Purchase::when($warehouse_id != 0, fn($q) => $q->where('warehouse_id', $warehouse_id))
                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                ->whereDate('created_at', $date)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('purchase_type')
                    ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                })
                ->selectRaw("
                    SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_discount,
                    SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_discount,
                    SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_tax,
                    SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_tax,
                    SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS shipping_cost,
                    SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total
                ")
                ->first();

            $total_discount[$start] = $purchase_data->total_discount;
            $order_discount[$start] = $purchase_data->order_discount;
            $total_tax[$start] = $purchase_data->total_tax;
            $order_tax[$start] = $purchase_data->order_tax;
            $shipping_cost[$start] = $purchase_data->shipping_cost;
            $grand_total[$start] = $purchase_data->grand_total;

            $start++;
        }

        $start_day = date('w', strtotime($year.'-'.$month.'-01')) + 1;
        $prev_year = date('Y', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $prev_month = date('m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $next_year = date('Y', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
        $next_month = date('m', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));

        // тЬЕ AJAX response
        if ($request->ajax()) {
            return view('backend.report.partials.daily_purchase_table', compact(
                'total_discount','order_discount','total_tax','order_tax',
                'shipping_cost','grand_total','start_day','year','month',
                'number_of_day','prev_year','prev_month','next_year','next_month'
            ))->render();
        }

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();

        return view('backend.report.daily_purchase', compact(
            'total_discount','order_discount','total_tax','order_tax',
            'shipping_cost','grand_total','start_day','year','month',
            'number_of_day','prev_year','prev_month','next_year','next_month',
            'lims_warehouse_list','warehouse_id'
        ));
    }

    public function monthlySale(Request $request, $year)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('monthly-sale')){
            $start = strtotime($year .'-01-01');
            $end = strtotime($year .'-12-31');
            while($start <= $end)
            {
                $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, $year));
                $start_date = $year . '-'. date('m', $start).'-'.'01';
                $end_date = $year . '-'. date('m', $start).'-'.$number_of_day;

                $sale_q = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                        ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                        ->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->where('sale_type', '!=', 'opening balance')
                            ->orWhereNull('sale_type');
                        });

                $temp_total_discount = $sale_q->sum(DB::raw('total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $total_discount[] = number_format((float)$temp_total_discount, config('decimal'), '.', '');

                $temp_order_discount = $sale_q->sum(DB::raw('order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $order_discount[] = number_format((float)$temp_order_discount, config('decimal'), '.', '');

                $temp_total_tax = $sale_q->sum(DB::raw('total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $total_tax[] = number_format((float)$temp_total_tax, config('decimal'), '.', '');

                $temp_order_tax = $sale_q->sum(DB::raw('order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $order_tax[] = number_format((float)$temp_order_tax, config('decimal'), '.', '');

                $temp_shipping_cost = $sale_q->sum(DB::raw('shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $shipping_cost[] = number_format((float)$temp_shipping_cost, config('decimal'), '.', '');

                $temp_total = $sale_q->sum(DB::raw('grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)'));

                $total[] = number_format((float)$temp_total, config('decimal'), '.', '');

                $start = strtotime("+1 month", $start);
            }
            $lims_warehouse_list = Warehouse::where('is_active',true)->get();
            $warehouse_id = 0;

            return view('backend.report.monthly_sale', compact('year', 'total_discount', 'order_discount', 'total_tax', 'order_tax', 'shipping_cost', 'total', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function monthlySaleByWarehouse(Request $request, $year)
    {
        $warehouse_id = $request->warehouse_id;

        // reuse same logic (like daily fix)
        $start = strtotime($year .'-01-01');
        $end = strtotime($year .'-12-31');

        while($start <= $end)
        {
            $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, $year));
            $start_date = $year . '-'. date('m', $start).'-01';
            $end_date = $year . '-'. date('m', $start).'-'.$number_of_day;

            $sale_q = Sale::when($warehouse_id != 0, fn($q) => $q->where('warehouse_id', $warehouse_id))
                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('sale_type', '!=', 'opening balance')
                    ->orWhereNull('sale_type');
                });

            $total_discount[] = number_format((float)$sale_q->sum(DB::raw('total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');
            $order_discount[] = number_format((float)$sale_q->sum(DB::raw('order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');
            $total_tax[] = number_format((float)$sale_q->sum(DB::raw('total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');
            $order_tax[] = number_format((float)$sale_q->sum(DB::raw('order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');
            $shipping_cost[] = number_format((float)$sale_q->sum(DB::raw('shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');
            $total[] = number_format((float)$sale_q->sum(DB::raw('grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)')), config('decimal'), '.', '');

            $start = strtotime("+1 month", $start);
        }

        if ($request->ajax()) {
            return view('backend.report.partials.monthly_sale_table', compact(
                'year', 'total_discount', 'order_discount', 'total_tax',
                'order_tax', 'shipping_cost', 'total'
            ))->render();
        }

        $lims_warehouse_list = Warehouse::where('is_active',true)->get();

        return view('backend.report.monthly_sale', compact(
            'year', 'total_discount', 'order_discount', 'total_tax',
            'order_tax', 'shipping_cost', 'total',
            'lims_warehouse_list', 'warehouse_id'
        ));
    }

    public function monthlyPurchase(Request $request, $year)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('monthly-purchase')){
            $start = strtotime($year .'-01-01');
            $end = strtotime($year .'-12-31');
            while($start <= $end)
            {
                $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, $year));
                $start_date = $year . '-'. date('m', $start).'-'.'01';
                $end_date = $year . '-'. date('m', $start).'-'.$number_of_day;

                $query1 = array(
                    'SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_discount',
                    'SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_discount',
                    'SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_tax',
                    'SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_tax',
                    'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS shipping_cost',
                    'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total'
                );
                $purchase_data = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                                ->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->whereNull('purchase_type')
                                    ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                })
                                ->selectRaw(implode(',', $query1))
                                ->get();

                $total_discount[] = number_format((float)$purchase_data[0]->total_discount, config('decimal'), '.', '');
                $order_discount[] = number_format((float)$purchase_data[0]->order_discount, config('decimal'), '.', '');
                $total_tax[] = number_format((float)$purchase_data[0]->total_tax, config('decimal'), '.', '');
                $order_tax[] = number_format((float)$purchase_data[0]->order_tax, config('decimal'), '.', '');
                $shipping_cost[] = number_format((float)$purchase_data[0]->shipping_cost, config('decimal'), '.', '');
                $grand_total[] = number_format((float)$purchase_data[0]->grand_total, config('decimal'), '.', '');
                $start = strtotime("+1 month", $start);
            }
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $warehouse_id = 0;
            return view('backend.report.monthly_purchase', compact('year', 'total_discount', 'order_discount', 'total_tax', 'order_tax', 'shipping_cost', 'grand_total', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function monthlyPurchaseByWarehouse(Request $request, $year)
    {
        $warehouse_id = $request->warehouse_id;

        $start = strtotime($year .'-01-01');
        $end = strtotime($year .'-12-31');

        $total_discount = $order_discount = $total_tax = $order_tax = $shipping_cost = $grand_total = [];

        while($start <= $end)
        {
            $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, $year));
            $start_date = $year . '-'. date('m', $start).'-01';
            $end_date = $year . '-'. date('m', $start).'-'.$number_of_day;

            $query1 = [
                'SUM(total_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_discount',
                'SUM(order_discount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_discount',
                'SUM(total_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS total_tax',
                'SUM(order_tax  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS order_tax',
                'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS shipping_cost',
                'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total'
            ];

            $purchase_q = Purchase::when($warehouse_id != 0, fn($q) => $q->where('warehouse_id', $warehouse_id))
                            ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                            ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('purchase_type')
                                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                            })
                            ->selectRaw(implode(',', $query1))
                            ->first(); // use first() instead of get()[0] for cleaner code

            $total_discount[] = number_format((float)$purchase_q->total_discount, config('decimal'), '.', '');
            $order_discount[] = number_format((float)$purchase_q->order_discount, config('decimal'), '.', '');
            $total_tax[] = number_format((float)$purchase_q->total_tax, config('decimal'), '.', '');
            $order_tax[] = number_format((float)$purchase_q->order_tax, config('decimal'), '.', '');
            $shipping_cost[] = number_format((float)$purchase_q->shipping_cost, config('decimal'), '.', '');
            $grand_total[] = number_format((float)$purchase_q->grand_total, config('decimal'), '.', '');

            $start = strtotime("+1 month", $start);
        }

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();

        if ($request->ajax()) {
            return view('backend.report.partials.monthly_purchase_table', compact(
                'year', 'total_discount', 'order_discount', 'total_tax',
                'order_tax', 'shipping_cost', 'grand_total'
            ))->render();
        }

        return view('backend.report.monthly_purchase', compact(
            'year', 'total_discount', 'order_discount', 'total_tax',
            'order_tax', 'shipping_cost', 'grand_total',
            'lims_warehouse_list', 'warehouse_id'
        ));
    }

    public function bestSeller()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('best-seller')){
            $start = strtotime(date("Y-m", strtotime("-2 months")).'-01');
            $end = strtotime(date("Y").'-'.date("m").'-31');

            while($start <= $end)
            {
                $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, date('Y', $start)));
                $start_date = date("Y-m", $start).'-'.'01';
                $end_date = date("Y-m", $start).'-'.$number_of_day;

                // $best_selling_qty = Product_Sale::select(DB::raw('product_id, sum(qty) as sold_qty'))->whereDate('created_at', '>=' , $start_date)->whereDate('created_at', '<=' , $end_date)->groupBy('product_id')->orderBy('sold_qty', 'desc')->take(1)->get();
                $best_selling_qty = Product_Sale::join('sales','product_sales.sale_id','=','sales.id')
                                        ->select(DB::raw('product_sales.product_id, sum(product_sales.qty) as sold_qty'))
                                        ->whereNull('sales.deleted_at')
                                        ->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)
                                        ->when($this->own_data,function($query) {
                                            $query->where('sales.user_id',$this->current_user_id);
                                        })
                                        ->groupBy('product_sales.product_id')
                                        ->orderBy('sold_qty', 'desc')
                                        ->take(1)
                                        ->get();
                if(!count($best_selling_qty)){
                    $product[] = '';
                    $sold_qty[] = 0;
                }
                foreach ($best_selling_qty as $best_seller) {
                    $product_data = Product::find($best_seller->product_id);
                    $product[] = $product_data->name.': '.$product_data->code;
                    $sold_qty[] = $best_seller->sold_qty;
                }
                $start = strtotime("+1 month", $start);
            }
            $start_month = date("F Y", strtotime('-2 month'));
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $warehouse_id = 0;
            //return $product;
            return view('backend.report.best_seller', compact('product', 'sold_qty', 'start_month', 'lims_warehouse_list', 'warehouse_id'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function bestSellerByWarehouse(Request $request)
    {
        $data = $request->all();
        if($data['warehouse_id'] == 0)
            return redirect()->back();

        $start = strtotime(date("Y-m", strtotime("-2 months")).'-01');
        $end = strtotime(date("Y").'-'.date("m").'-31');

        while($start <= $end)
        {
            $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, date('Y', $start)));
            $start_date = date("Y-m", $start).'-'.'01';
            $end_date = date("Y-m", $start).'-'.$number_of_day;

            $best_selling_qty = DB::table('sales')
                                    ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->select(DB::raw('product_sales.product_id, sum(product_sales.qty) as sold_qty'))
                                    ->where('sales.warehouse_id', $data['warehouse_id'])
                                    ->whereNull('sales.deleted_at')
                                    ->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)
                                    ->when($this->own_data,function($query) {
                                        $query->where('sales.user_id',$this->current_user_id);
                                    })
                                    ->groupBy('product_sales.product_id')
                                    ->orderBy('sold_qty', 'desc')
                                    ->take(1)
                                    ->get();

            if(!count($best_selling_qty)) {
                $product[] = '';
                $sold_qty[] = 0;
            }
            foreach ($best_selling_qty as $best_seller) {
                $product_data = Product::find($best_seller->product_id);
                $product[] = $product_data->name.': '.$product_data->code;
                $sold_qty[] = $best_seller->sold_qty;
            }
            $start = strtotime("+1 month", $start);
        }
        $start_month = date("F Y", strtotime('-2 month'));
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $warehouse_id = $data['warehouse_id'];
        return view('backend.report.best_seller', compact('product', 'sold_qty', 'start_month', 'lims_warehouse_list', 'warehouse_id'));
    }

    public function summary()
    {
        $start_date = date('Y-m').'-'.'01';
        $end_date = date('Y-m-d');
        $warehouse_id = 0;

        $lims_warehouse_list = Cache::remember('warehouse_list', 60*60*24*365, function () {
            return Warehouse::where('is_active', true)->get();
        });

        return view('backend.report.profit_loss', compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list'));

    }

    private function dateFilter($query, $start_date, $end_date, $table = null)
    {
        $column = $table ? $table . '.created_at' : 'created_at';

        return $query->whereBetween($column, [
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        ]);
    }

    private function userFilter($query, $table = null)
    {
        if ($this->own_data) {
            $query->where(($table ? $table.'.' : '') . 'user_id', $this->current_user_id);
        }
        return $query;
    }

    private function warehouseFilter($query, $warehouse_id, $table = null)
    {
        if (!empty($warehouse_id)) {
            $query->where(($table ? $table.'.' : '') . 'warehouse_id', $warehouse_id);
        }
        return $query;
    }

    private function excludeOpening($query, $field)
    {
        return $query->where(function ($q) use ($field) {
            $q->whereNull($field);

            if (preg_match('/(^|\.)purchase_type$/', $field)) {
                $q->orWhereNotIn($field, Purchase::NON_SUPPLIER_OPENING_TYPES);
            } else {
                $q->orWhere($field, '!=', 'opening balance');
            }
        });
    }

    public function profitLoss(Request $request, ProfitabilityService $profitabilityService)
    {
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];
        $warehouse_id = $request['warehouse_id'];

        if(isset($warehouse_id) && $warehouse_id != 0){
            $lims_warehouse_all = Warehouse::where('id',$warehouse_id)->get();
            $lims_warehouse = Warehouse::where('id',$warehouse_id)->first();
        }else{
            $lims_warehouse_all = Warehouse::where('is_active',true)->get();
            $lims_warehouse = null;
        }

        $query1 = [
            'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total',
            'SUM(shipping_cost  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS shipping_cost',
            'SUM(paid_amount  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS paid_amount',
            'SUM((total_tax + order_tax)  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS tax',
            'SUM((total_discount + order_discount)  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS discount'
        ];

        $query2 = [
            'SUM(grand_total  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS grand_total',
            'SUM((total_tax + order_tax)  / COALESCE(NULLIF(exchange_rate, 0), 1)) AS tax'
        ];

        // Use the canonical profitability engine for COGS so historical reports are
        // based on the cost that was available at the sale/return date. The service
        // also applies IMEI/serial specific-identification costing where transaction
        // lines contain IMEI values, and batch-specific costing where a batch exists.
        $profitabilityFilter = $this->profitabilityFilterFromRequest($request);
        $profitabilityRows = $profitabilityService->productRows($profitabilityFilter);
        $product_cost = (float) $profitabilityRows->sum('net_cost');

        // ProfitabilityService already excludes purchase tax from inventory cost,
        // so the legacy view must not add purchase tax back a second time.
        $product_tax = 0.0;

        // ================== PURCHASE QUERIES ==================
        $purchaseQuery = Purchase::query();
        $purchaseQuery = $this->dateFilter($purchaseQuery, $start_date, $end_date);
        $purchaseQuery = $this->excludeOpening($purchaseQuery, 'purchase_type');
        $purchaseQuery = $this->userFilter($purchaseQuery);
        $purchaseQuery = $this->warehouseFilter($purchaseQuery, $warehouse_id);
        $purchaseQuery->whereNull('deleted_at');

        $purchase = (clone $purchaseQuery)->selectRaw(implode(',', $query1))->get();
        $total_purchase = (clone $purchaseQuery)->count();

        // ================== SALE QUERIES ==================
        $saleQuery = Sale::query();
        $saleQuery = $this->dateFilter($saleQuery, $start_date, $end_date);
        $saleQuery = $this->excludeOpening($saleQuery, 'sale_type');
        $saleQuery = $this->userFilter($saleQuery);
        $saleQuery = $this->warehouseFilter($saleQuery, $warehouse_id);
        $saleQuery->whereNull('deleted_at');

        $sale = (clone $saleQuery)->selectRaw(implode(',', $query1))->get();
        $total_sale = (clone $saleQuery)->count();

        // ================== RETURN QUERIES ==================
        $returnQuery = Returns::query();
        $returnQuery = $this->dateFilter($returnQuery, $start_date, $end_date);
        $returnQuery = $this->userFilter($returnQuery);
        $returnQuery = $this->warehouseFilter($returnQuery, $warehouse_id);

        $return = (clone $returnQuery)->selectRaw(implode(',', $query2))->get();
        $total_return = (clone $returnQuery)->count();

        // ================== PURCHASE RETURN QUERIES ==================
        $returnPurchaseQuery = ReturnPurchase::query();
        $returnPurchaseQuery = $this->dateFilter($returnPurchaseQuery, $start_date, $end_date);
        $returnPurchaseQuery = $this->userFilter($returnPurchaseQuery);
        $returnPurchaseQuery = $this->warehouseFilter($returnPurchaseQuery, $warehouse_id);

        $purchaseReturnActualTotal = (clone $returnPurchaseQuery)
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->selectRaw('SUM(COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total) / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) AS grand_total, SUM((return_purchases.total_tax + return_purchases.order_tax) / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) AS tax')
            ->first();
        $purchase_return = collect([$purchaseReturnActualTotal]);
        $total_purchase_return = (clone $returnPurchaseQuery)->count();

        // ================== EXPENSE QUERIES ==================
        $expenseQuery = Expense::query();
        $expenseQuery = $this->dateFilter($expenseQuery, $start_date, $end_date);
        $expenseQuery = $this->userFilter($expenseQuery);
        $expenseQuery = $this->warehouseFilter($expenseQuery, $warehouse_id);

        $expense = (clone $expenseQuery)->sum('amount');
        $total_expense = (clone $expenseQuery)->count();

        // ================== INCOME QUERIES ==================
        $incomeQuery = Income::query();
        $incomeQuery = $this->dateFilter($incomeQuery, $start_date, $end_date);
        $incomeQuery = $this->userFilter($incomeQuery);
        $incomeQuery = $this->warehouseFilter($incomeQuery, $warehouse_id);

        $income = (clone $incomeQuery)->sum('amount');
        $total_income = (clone $incomeQuery)->count();

        // ================== PAYROLL QUERIES ==================
        $payrollQuery = Payroll::query();
        $payrollQuery = $this->dateFilter($payrollQuery, $start_date, $end_date);
        $payrollQuery = $this->userFilter($payrollQuery);
        
        $payroll = (clone $payrollQuery)->sum('amount');
        $total_payroll = (clone $payrollQuery)->count();

        // ================== INVENTORY COUNT ==================
        $total_item = DB::table('product_warehouse')
                    ->join('products', 'product_warehouse.product_id', '=', 'products.id')
                    ->where([
                        ['products.is_active', true],
                        ['product_warehouse.qty', '>' , 0]
                    ]);
        if(isset($warehouse_id) && $warehouse_id != 0){
            $total_item = $total_item->where('product_warehouse.warehouse_id', $warehouse_id);
        }
        $total_item = $total_item->count();

        // ================== PAYMENT RECEIVED QUERIES WITH WAREHOUSE FILTER ==================
        $paymentSaleBase = DB::table('payments')
                            ->join('sales', 'payments.sale_id', '=', 'sales.id')
                            ->whereNotNull('payments.sale_id')
                            ->whereNull('payments.return_id');

        $paymentSaleBase = $this->dateFilter($paymentSaleBase, $start_date, $end_date, 'payments');
        $paymentSaleBase = $this->userFilter($paymentSaleBase, 'sales');
        $paymentSaleBase = $this->warehouseFilter($paymentSaleBase, $warehouse_id, 'sales');

        $payment_recieved_number = (clone $paymentSaleBase)->count();
        $payment_recieved = (clone $paymentSaleBase)->sum(DB::raw('payments.amount / payments.exchange_rate'));

        // Payment breakdown
        $methods = ['Credit Card', 'Cheque', 'Gift Card', 'Paypal', 'Deposit'];

        $paymentBreakdown = [];

        foreach ($methods as $method) {
            $paymentBreakdown[$method] = (clone $paymentSaleBase)
                ->where('payments.paying_method', $method)
                ->sum(DB::raw('payments.amount / payments.exchange_rate'));
        }

        $credit_card_payment_sale = $paymentBreakdown['Credit Card'];
        $cheque_payment_sale = $paymentBreakdown['Cheque'];
        $gift_card_payment_sale = $paymentBreakdown['Gift Card'];
        $paypal_payment_sale = $paymentBreakdown['Paypal'];
        $deposit_payment_sale = $paymentBreakdown['Deposit'];

        $cash_payment_sale = $payment_recieved - array_sum($paymentBreakdown);

        $paymentPurchaseBase = DB::table('payments')
            ->join('purchases', 'payments.purchase_id', '=', 'purchases.id')
            ->whereNotNull('payments.purchase_id');

        $paymentPurchaseBase = $this->dateFilter($paymentPurchaseBase, $start_date, $end_date, 'payments');
        $paymentPurchaseBase = $this->userFilter($paymentPurchaseBase, 'purchases');
        $paymentPurchaseBase = $this->warehouseFilter($paymentPurchaseBase, $warehouse_id, 'purchases');

        $payment_sent_number = (clone $paymentPurchaseBase)->count();
        $payment_sent = (clone $paymentPurchaseBase)->sum(DB::raw('payments.amount / payments.exchange_rate'));

        $cheque_payment_purchase = (clone $paymentPurchaseBase)
            ->where('payments.paying_method', 'Cheque')
            ->sum(DB::raw('payments.amount / payments.exchange_rate'));

        $credit_card_payment_purchase = (clone $paymentPurchaseBase)
            ->where('payments.paying_method', 'Credit Card')
            ->sum(DB::raw('payments.amount / payments.exchange_rate'));

        $cash_payment_purchase = $payment_sent - $cheque_payment_purchase - $credit_card_payment_purchase;

        // ================== WAREHOUSE WISE BREAKDOWN ==================
        $warehouse_name = [];
        $warehouse_sale = [];
        $warehouse_purchase = [];
        $warehouse_return = [];
        $warehouse_purchase_return = [];
        $warehouse_expense = [];

        foreach ($lims_warehouse_all as $warehouse) {
            $warehouse_name[] = $warehouse->name;

            $warehouse_sale[] = Sale::where('warehouse_id', $warehouse->id)
                                    ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                    ->whereNull('deleted_at')
                                    ->where(function($q) {
                                        $q->where('sale_type', '!=', 'opening balance')
                                        ->orWhereNull('sale_type');
                                    })
                                    ->when($this->own_data,function($query) {
                                        $query->where('user_id',$this->current_user_id);
                                    })
                                    ->selectRaw(implode(',', $query2))
                                    ->get();

            $warehouse_purchase[] = Purchase::where('warehouse_id', $warehouse->id)
                                        ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                        ->whereNull('deleted_at')
                                        ->where(function($q) {
                                            $q->whereNull('purchase_type')
                                            ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                        })
                                        ->selectRaw(implode(',', $query2))
                                        ->get();

            $warehouse_return[] = Returns::where('warehouse_id', $warehouse->id)
                                        ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                        ->selectRaw(implode(',', $query2))
                                        ->get();

            $warehousePurchaseReturn = ReturnPurchase::where('warehouse_id', $warehouse->id)
                ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
                ->whereDate('return_purchases.created_at', '>=', $start_date)
                ->whereDate('return_purchases.created_at', '<=', $end_date)
                ->selectRaw('SUM(COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total) / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) AS grand_total, SUM((return_purchases.total_tax + return_purchases.order_tax) / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) AS tax')
                ->get();
            $warehouse_purchase_return[] = $warehousePurchaseReturn;

            $warehouse_expense[] = Expense::where('warehouse_id', $warehouse->id)
                                        ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                        ->sum('amount');
        }

        $lims_warehouse_list = Cache::remember('warehouse_list', 60*60*24*365, function () {
            return Warehouse::where('is_active', true)->get();
        });

        $general_setting = GeneralSetting::select('decimal')->first();

        if ($request->ajax()) {
            return view('backend.report.profit_loss_result', compact(
                'purchase',
                'sale',
                'return',
                'purchase_return',
                'product_cost',
                'product_tax',
                'expense',
                'income',
                'payroll',
                'warehouse_name',
                'warehouse_sale',
                'warehouse_purchase',
                'warehouse_return',
                'warehouse_purchase_return',
                'warehouse_expense',
                'general_setting',
                'total_purchase',
                'total_sale',
                'total_return',
                'total_purchase_return',
                'payment_recieved',
                'payment_recieved_number',
                'payment_sent',
                'payment_sent_number',
                'cash_payment_sale',
                'cheque_payment_sale',
                'credit_card_payment_sale',
                'gift_card_payment_sale',
                'paypal_payment_sale',
                'deposit_payment_sale',
                'cash_payment_purchase',
                'cheque_payment_purchase',
                'credit_card_payment_purchase',
                'total_expense',
                'total_income',
                'total_payroll'
            ));
        }

    }

    public function calculateAverageCOGS($product_sale_data)
    {
        $product_cost = 0;
        $product_tax = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            $product_data = Product::select('type', 'product_list', 'variant_list', 'qty_list', 'cost')->find($product_sale->product_id);
            if(@$product_data->type == 'combo') {
                $product_list = explode(",", $product_data->product_list);
                if($product_data->variant_list)
                    $variant_list = explode(",", $product_data->variant_list);
                else
                    $variant_list = [];
                $qty_list = explode(",", $product_data->qty_list);

                foreach ($product_list as $index => $product_id) {
                    if(count($variant_list) && $variant_list[$index]) {
                        $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where([
                            ['product_purchases.product_id', $product_id],
                            ['product_purchases.variant_id', $variant_list[$index] ]
                        ])
                        ->whereNull('purchases.deleted_at')
                        ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total')
                        ->get();
                    }
                    else {
                        $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where('product_purchases.product_id', $product_id)
                        ->whereNull('purchases.deleted_at')
                        ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total')
                        ->get();
                    }
                    $total_received_qty = 0;
                    $total_purchased_amount = 0;
                    $total_tax = 0;
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $qty_list[$index];
                    foreach ($product_purchase_data as $key => $product_purchase) {
                        $purchase_unit_data = $product_purchase->purchase_unit_id ? Unit::select('operator', 'operation_value')->find($product_purchase->purchase_unit_id) : null;
                        if($purchase_unit_data && $purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        elseif($purchase_unit_data)
                            $total_received_qty += $product_purchase->recieved / ($purchase_unit_data->operation_value ?: 1);
                        else
                            $total_received_qty += $product_purchase->recieved;

                        $total_purchased_amount += $product_purchase->total /
                        (($product_purchase->exchange_rate && $product_purchase->exchange_rate != 0) ? $product_purchase->exchange_rate : 1);

                        $total_tax += $product_purchase->tax /
                        (($product_purchase->exchange_rate && $product_purchase->exchange_rate != 0) ? $product_purchase->exchange_rate : 1);
                    }
                    if($total_received_qty) {
                        $averageCost = $total_purchased_amount / $total_received_qty;
                        $averageTax = $total_tax / $total_received_qty;
                    }
                    else {
                        $component_data = Product::select('cost')->find($product_id);
                        $averageCost = $component_data->cost;
                        $averageTax = 0;
                    }
                    $product_cost += $sold_qty * $averageCost;
                    $product_tax += $sold_qty * $averageTax;
                }
            }
            else {
                if($product_sale->product_batch_id) {
                    $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where([
                        ['product_purchases.product_id', $product_sale->product_id],
                        ['product_purchases.product_batch_id', $product_sale->product_batch_id]
                    ])
                    ->whereNull('purchases.deleted_at')
                    ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total')
                    ->get();
                }
                elseif($product_sale->variant_id) {
                    $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where([
                        ['product_purchases.product_id', $product_sale->product_id],
                        ['product_purchases.variant_id', $product_sale->variant_id]
                    ])
                    ->whereNull('purchases.deleted_at')
                    ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total')
                    ->get();
                }
                else {
                    $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where('product_id', $product_sale->product_id)
                        ->whereNull('purchases.deleted_at')
                    ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total', 'purchases.exchange_rate')
                    ->get();
                }
                $total_received_qty = 0;
                $total_purchased_amount = 0;
                $total_tax = 0;
                if($product_sale->sale_unit_id) {
                    $sale_unit_data = Unit::select('operator', 'operation_value')->find($product_sale->sale_unit_id);
                    if($sale_unit_data && $sale_unit_data->operator == '*')
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $sale_unit_data->operation_value;
                    elseif($sale_unit_data)
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) / ($sale_unit_data->operation_value ?: 1);
                    else
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty);
                }
                else {
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty);
                }
                foreach ($product_purchase_data as $key => $product_purchase) {
                    $purchase_unit_data = Unit::select('operator', 'operation_value')->find($product_purchase->purchase_unit_id);
                    if($purchase_unit_data) {
                        if($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;

                        $total_purchased_amount += $product_purchase->total /
                        (($product_purchase->exchange_rate && $product_purchase->exchange_rate != 0) ? $product_purchase->exchange_rate : 1);

                        $total_tax += $product_purchase->tax /
                        (($product_purchase->exchange_rate && $product_purchase->exchange_rate != 0) ? $product_purchase->exchange_rate : 1);
                    }
                }
                if($total_received_qty) {
                    $averageCost = $total_purchased_amount / $total_received_qty;
                    $averageTax = $total_tax / $total_received_qty;
                }
                else {
                    if($product_sale->variant_id) {
                        $additional_cost = DB::table('product_variants')
                            ->where([
                                ['product_id', $product_sale->product_id],
                                ['variant_id', $product_sale->variant_id]
                            ])->value('additional_cost');
                        $averageCost = $product_data->cost + ($additional_cost ?? 0);
                    }
                    else {
                        $averageCost = $product_data->cost;
                    }
                    $averageTax = 0;
                }
                $product_cost += $sold_qty * $averageCost;
                $product_tax += $sold_qty * $averageTax;
            }
        }
        return [$product_cost, $product_tax];
    }

    public function profitabilityProduct(Request $request)
    {
        $start_date = $request->start_date ?? date('Y-m') . '-01';
        $end_date = $request->end_date ?? date('Y-m-d');
        $warehouse_id = (int) ($request->warehouse_id ?? 0);
        $group_variants = (bool) $request->boolean('group_variants', false);
        $user = Auth::user();
        $warehouseQuery = Warehouse::where('is_active', true);

        if ($user && (int) $user->role_id > 2 && $user->warehouse_id) {
            $warehouse_id = (int) $user->warehouse_id;
            $warehouseQuery->where('id', $warehouse_id);
        }

        $lims_warehouse_list = $warehouseQuery->get();

        return view('backend.report.profitability_product', compact(
            'start_date',
            'end_date',
            'warehouse_id',
            'group_variants',
            'lims_warehouse_list'
        ));
    }

    public function profitabilityCategory(Request $request)
    {
        return $this->profitabilityDimension($request, 'category');
    }

    public function profitabilityBrand(Request $request)
    {
        return $this->profitabilityDimension($request, 'brand');
    }

    public function profitabilityCustomer(Request $request)
    {
        return $this->profitabilityDimension($request, 'customer');
    }

    public function profitabilityLocation(Request $request)
    {
        return $this->profitabilityDimension($request, 'location');
    }

    public function profitabilityInvoice(Request $request)
    {
        return $this->profitabilityDimension($request, 'invoice');
    }

    private function profitabilityDimension(Request $request, string $dimension)
    {
        $start_date = $request->start_date ?? date('Y-m') . '-01';
        $end_date = $request->end_date ?? date('Y-m-d');
        $warehouse_id = (int) ($request->warehouse_id ?? 0);
        $user = Auth::user();
        $warehouseQuery = Warehouse::where('is_active', true);

        if ($user && (int) $user->role_id > 2 && $user->warehouse_id) {
            $warehouse_id = (int) $user->warehouse_id;
            $warehouseQuery->where('id', $warehouse_id);
        }

        $lims_warehouse_list = $warehouseQuery->get();
        $active_tab = $dimension;
        $data_route = $this->profitabilityDimensionDataRoute($dimension);
        $group_label = $this->profitabilityDimensionLabel($dimension);
        $group_count_label = $this->profitabilityDimensionCountLabel($dimension);
        $table_columns = $this->profitabilityDimensionColumns($dimension);
        $summary_counts = $this->profitabilityDimensionSummaryCounts($dimension);
        $default_order_column = array_search('gross_profit', array_column($table_columns, 'key'), true);
        $default_order_column = $default_order_column === false ? 0 : $default_order_column;

        return view('backend.report.profitability_dimension', compact(
            'start_date',
            'end_date',
            'warehouse_id',
            'lims_warehouse_list',
            'active_tab',
            'dimension',
            'data_route',
            'group_label',
            'group_count_label',
            'table_columns',
            'summary_counts',
            'default_order_column'
        ));
    }

    public function profitabilityProductData(Request $request, ProfitabilityService $profitabilityService)
    {
        try {
            $filter = $this->profitabilityFilterFromRequest($request);
            $report = $profitabilityService->dataTable($filter);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $report['recordsTotal'],
                'recordsFiltered' => $report['recordsFiltered'],
                'data' => $report['rows']->map(fn ($row) => $this->formatProfitabilityProductRow($row))->values(),
                'totals' => $this->formatProfitabilityTotals($report['totals']),
                'estimated_cost' => (bool) $report['totals']['estimated_cost'],
                'cost_basis_codes' => $report['totals']['cost_basis_codes'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Profit by Product report failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('db.profitability_report_error'),
            ], 500);
        }
    }

    public function profitabilityCategoryData(Request $request, ProfitabilityService $profitabilityService)
    {
        return $this->profitabilityDimensionData($request, $profitabilityService, 'category');
    }

    public function profitabilityBrandData(Request $request, ProfitabilityService $profitabilityService)
    {
        return $this->profitabilityDimensionData($request, $profitabilityService, 'brand');
    }

    public function profitabilityCustomerData(Request $request, ProfitabilityService $profitabilityService)
    {
        return $this->profitabilityDimensionData($request, $profitabilityService, 'customer');
    }

    public function profitabilityLocationData(Request $request, ProfitabilityService $profitabilityService)
    {
        return $this->profitabilityDimensionData($request, $profitabilityService, 'location');
    }

    public function profitabilityInvoiceData(Request $request, ProfitabilityService $profitabilityService)
    {
        return $this->profitabilityDimensionData($request, $profitabilityService, 'invoice');
    }

    private function profitabilityDimensionData(Request $request, ProfitabilityService $profitabilityService, string $dimension)
    {
        try {
            $filter = $this->profitabilityFilterFromRequest($request, $this->profitabilityDimensionSortColumns($dimension));
            $report = match ($dimension) {
                'category' => $profitabilityService->categoryDataTable($filter),
                'brand' => $profitabilityService->brandDataTable($filter),
                'customer' => $profitabilityService->customerDataTable($filter),
                'location' => $profitabilityService->locationDataTable($filter),
                'invoice' => $profitabilityService->invoiceDataTable($filter),
                default => throw new InvalidArgumentException(__('db.profitability_report_error')),
            };

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $report['recordsTotal'],
                'recordsFiltered' => $report['recordsFiltered'],
                'data' => $report['rows']->map(fn ($row) => $this->formatProfitabilityDimensionRow($row))->values(),
                'totals' => $this->formatProfitabilityDimensionTotals($report['totals'], $dimension),
                'estimated_cost' => (bool) $report['totals']['estimated_cost'],
                'cost_basis_codes' => $report['totals']['cost_basis_codes'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Profitability dimension report failed', [
                'dimension' => $dimension,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('db.profitability_report_error'),
            ], 500);
        }
    }

    private function profitabilityDimensionDataRoute(string $dimension): string
    {
        return match ($dimension) {
            'category' => route('report.profitability.categoryData'),
            'brand' => route('report.profitability.brandData'),
            'customer' => route('report.profitability.customerData'),
            'location' => route('report.profitability.locationData'),
            'invoice' => route('report.profitability.invoiceData'),
            default => route('report.profitability.productData'),
        };
    }

    private function profitabilityDimensionLabel(string $dimension): string
    {
        return match ($dimension) {
            'category' => __('db.category'),
            'brand' => __('db.Brand'),
            'customer' => __('db.customer'),
            'location' => __('db.profitability_location'),
            'invoice' => __('db.profitability_invoice'),
            default => __('db.profitability_analysis'),
        };
    }

    private function profitabilityDimensionCountLabel(string $dimension): string
    {
        return match ($dimension) {
            'category' => __('db.profitability_category_count'),
            'brand' => __('db.profitability_brand_count'),
            'customer' => __('db.profitability_customer_count'),
            'location' => __('db.profitability_location_count'),
            'invoice' => __('db.profitability_invoice_count'),
            default => __('db.Total'),
        };
    }

    private function profitabilityDimensionColumns(string $dimension): array
    {
        $financialColumns = [
            ['key' => 'net_quantity', 'label' => __('db.profitability_net_quantity')],
            ['key' => 'gross_sales', 'label' => __('db.profitability_gross_sales')],
            ['key' => 'discounts', 'label' => __('db.Discount')],
            ['key' => 'returns', 'label' => __('db.Sale Return')],
            ['key' => 'net_sales', 'label' => __('db.profitability_net_sales')],
            ['key' => 'net_cost', 'label' => __('db.profitability_net_cost')],
            ['key' => 'gross_profit', 'label' => __('db.profitability_gross_profit')],
            ['key' => 'gross_margin', 'label' => __('db.profitability_gross_margin')],
            ['key' => 'profit_contribution', 'label' => __('db.profitability_profit_contribution')],
        ];

        return match ($dimension) {
            'invoice' => [
                ['key' => 'invoice', 'label' => __('db.profitability_invoice')],
                ['key' => 'sale_date', 'label' => __('db.profitability_original_sale_date')],
                ['key' => 'period_activity', 'label' => __('db.profitability_period_activity')],
                ['key' => 'customer', 'label' => __('db.customer')],
                ['key' => 'location', 'label' => __('db.profitability_location')],
                ['key' => 'cashier', 'label' => __('db.profitability_cashier_created_by')],
                ['key' => 'product_count', 'label' => __('db.profitability_product_count')],
                ['key' => 'net_quantity', 'label' => __('db.profitability_net_quantity')],
                ['key' => 'gross_sales', 'label' => __('db.profitability_gross_sales')],
                ['key' => 'discounts', 'label' => __('db.Discount')],
                ['key' => 'returns', 'label' => __('db.Sale Return')],
                ['key' => 'net_sales', 'label' => __('db.profitability_net_sales')],
                ['key' => 'net_cost', 'label' => __('db.profitability_net_cost')],
                ['key' => 'gross_profit', 'label' => __('db.profitability_gross_profit')],
                ['key' => 'gross_margin', 'label' => __('db.profitability_gross_margin')],
                ['key' => 'cost_quality', 'label' => __('db.profitability_cost_quality')],
            ],
            'customer' => array_merge([
                ['key' => 'group', 'label' => __('db.customer')],
                ['key' => 'code', 'label' => __('db.profitability_customer_code_or_phone')],
                ['key' => 'location_count', 'label' => __('db.profitability_location_count')],
                ['key' => 'product_count', 'label' => __('db.profitability_product_count')],
                ['key' => 'invoice_count', 'label' => __('db.profitability_invoice_count')],
            ], $financialColumns),
            'location' => array_merge([
                ['key' => 'group', 'label' => __('db.profitability_location')],
                ['key' => 'customer_count', 'label' => __('db.profitability_customer_count')],
                ['key' => 'product_count', 'label' => __('db.profitability_product_count')],
                ['key' => 'invoice_count', 'label' => __('db.profitability_invoice_count')],
            ], $financialColumns),
            default => array_merge([
                ['key' => 'group', 'label' => $this->profitabilityDimensionLabel($dimension)],
                ['key' => 'product_count', 'label' => __('db.profitability_product_count')],
                ['key' => 'invoice_count', 'label' => __('db.profitability_invoice_count')],
            ], $financialColumns),
        };
    }

    private function profitabilityDimensionSortColumns(string $dimension): array
    {
        return collect($this->profitabilityDimensionColumns($dimension))
            ->pluck('key')
            ->all();
    }

    private function profitabilityDimensionSummaryCounts(string $dimension): array
    {
        if ($dimension === 'invoice') {
            return [
                ['key' => 'invoice_count', 'label' => __('db.profitability_invoice_count')],
                ['key' => 'return_only_count', 'label' => __('db.profitability_return_only_invoice_count')],
                ['key' => 'estimated_invoice_count', 'label' => __('db.profitability_estimated_invoice_count')],
            ];
        }

        $counts = [
            ['key' => 'group_count', 'label' => $this->profitabilityDimensionCountLabel($dimension)],
        ];

        if (in_array($dimension, ['customer', 'location'], true)) {
            $counts[] = ['key' => 'invoice_count', 'label' => __('db.profitability_invoice_count')];
        }

        return $counts;
    }

    private function profitabilityFilterFromRequest(Request $request, ?array $columns = null): ProfitabilityFilter
    {
        $startDate = ProfitabilityFilter::normalizeDate($request->input('start_date'), date('Y-m') . '-01');
        $endDate = ProfitabilityFilter::normalizeDate($request->input('end_date'), date('Y-m-d'));

        if (\Carbon\Carbon::parse($endDate)->lt(\Carbon\Carbon::parse($startDate))) {
            throw new InvalidArgumentException(__('db.profitability_invalid_date_range'));
        }

        $columns ??= [
            0 => 'product',
            1 => 'product_code',
            2 => 'net_quantity',
            3 => 'gross_sales',
            4 => 'discounts',
            5 => 'returns',
            6 => 'net_sales',
            7 => 'net_cost',
            8 => 'gross_profit',
            9 => 'gross_margin',
            10 => 'invoice_count',
        ];

        $length = (int) $request->input('length', 10);
        $user = Auth::user();
        $warehouseId = (int) $request->input('warehouse_id', 0) ?: null;

        if ($user && (int) $user->role_id > 2 && $user->warehouse_id) {
            $warehouseId = $warehouseId && (int) $warehouseId !== (int) $user->warehouse_id
                ? -1
                : (int) $user->warehouse_id;
        }

        return new ProfitabilityFilter(
            startDate: $startDate,
            endDate: $endDate,
            warehouseId: $warehouseId,
            groupVariants: $request->boolean('group_variants', false),
            ownDataOnly: $user && (int) $user->role_id > 2 && config('staff_access') === 'own',
            userId: $user?->id,
            search: $request->input('search.value'),
            sortBy: $columns[(int) $request->input('order.0.column', 8)] ?? 'gross_profit',
            sortDirection: $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc',
            offset: max(0, (int) $request->input('start', 0)),
            limit: $length === -1 ? -1 : max(1, $length),
        );
    }

    private function formatProfitabilityProductRow(array $row): array
    {
        $productName = e($row['product_name']);
        $variantName = $row['variant_name'] ? e($row['variant_name']) : null;
        $costBasis = e($this->profitabilityCostBasisLabel($row['cost_basis_codes'][0] ?? 'zero_cost'));
        $estimatedBadge = $row['estimated_cost']
            ? ' <span class="badge badge-warning">' . e(__('db.profitability_estimated_cost')) . '</span>'
            : '';

        $product = '<div>' . $productName;
        if ($variantName) {
            $product .= '<br><small class="text-muted">' . e(__('db.Variant')) . ': ' . $variantName . '</small>';
        }
        $product .= '<br><small class="text-muted">' . e(__('db.profitability_cost_basis')) . ': ' . $costBasis . '</small>' . $estimatedBadge . '</div>';

        return [
            'product' => $product,
            'product_code' => e($row['product_code'] ?: __('db.not_applicable')),
            'net_quantity' => $this->formatProfitabilityNumber($row['net_quantity']),
            'gross_sales' => $this->formatProfitabilityMoney($row['gross_sales']),
            'discounts' => $this->formatProfitabilityMoney($row['discounts']),
            'returns' => $this->formatProfitabilityMoney($row['returned_revenue']),
            'net_sales' => $this->formatProfitabilityMoney($row['net_sales']),
            'net_cost' => $this->formatProfitabilityMoney($row['net_cost']),
            'gross_profit' => $this->formatProfitabilityMoney($row['gross_profit']),
            'gross_margin' => $this->formatProfitabilityMargin($row['gross_margin']),
            'invoice_count' => (int) $row['invoice_count'],
        ];
    }

    private function formatProfitabilityTotals(array $totals): array
    {
        return [
            'net_quantity' => $this->formatProfitabilityNumber($totals['net_quantity']),
            'gross_sales' => $this->formatProfitabilityMoney($totals['gross_sales']),
            'discounts' => $this->formatProfitabilityMoney($totals['discounts']),
            'returns' => $this->formatProfitabilityMoney($totals['returned_revenue']),
            'net_sales' => $this->formatProfitabilityMoney($totals['net_sales']),
            'net_cost' => $this->formatProfitabilityMoney($totals['net_cost']),
            'gross_profit' => $this->formatProfitabilityMoney($totals['gross_profit']),
            'gross_margin' => $this->formatProfitabilityMargin($totals['gross_margin']),
            'invoice_count' => (int) $totals['invoice_count'],
            'estimated_cost' => (bool) $totals['estimated_cost'],
            'cost_basis' => collect($totals['cost_basis_codes'])
                ->map(fn ($basis) => $this->profitabilityCostBasisLabel($basis))
                ->implode(', '),
        ];
    }

    private function formatProfitabilityDimensionRow(array $row): array
    {
        return [
            'group' => e($row['name'] ?? ''),
            'invoice' => e($row['invoice'] ?? ''),
            'sale_date' => $this->formatProfitabilityDate($row['sale_date'] ?? null),
            'period_activity' => e($this->profitabilityInvoiceActivityLabel($row['period_activity'] ?? 'sale')),
            'customer' => e($row['customer_name'] ?? __('db.profitability_unassigned_customer')),
            'location' => e($row['location_name'] ?? __('db.profitability_unassigned_location')),
            'cashier' => e($row['cashier_name'] ?? __('db.profitability_not_available')),
            'code' => e(($row['code'] ?? '') !== '' ? $row['code'] : __('db.not_applicable')),
            'customer_count' => (int) ($row['customer_count'] ?? 0),
            'location_count' => (int) ($row['location_count'] ?? 0),
            'product_count' => (int) ($row['product_count'] ?? 0),
            'invoice_count' => (int) ($row['invoice_count'] ?? 0),
            'net_quantity' => $this->formatProfitabilityNumber($row['net_quantity']),
            'gross_sales' => $this->formatProfitabilityMoney($row['gross_sales']),
            'discounts' => $this->formatProfitabilityMoney($row['discounts']),
            'returns' => $this->formatProfitabilityMoney($row['returned_revenue']),
            'net_sales' => $this->formatProfitabilityMoney($row['net_sales']),
            'net_cost' => $this->formatProfitabilityMoney($row['net_cost']),
            'gross_profit' => $this->formatProfitabilityMoney($row['gross_profit']),
            'gross_margin' => $this->formatProfitabilityMargin($row['gross_margin']),
            'profit_contribution' => $this->formatProfitabilityMargin($row['profit_contribution'] ?? null),
            'cost_quality' => e($this->profitabilityCostQualityLabel((bool) ($row['estimated_cost'] ?? false))),
        ];
    }

    private function formatProfitabilityDimensionTotals(array $totals, string $dimension): array
    {
        return [
            'group_count' => (int) $totals['group_count'],
            'return_only_count' => (int) ($totals['return_only_count'] ?? 0),
            'estimated_invoice_count' => (int) ($totals['estimated_invoice_count'] ?? 0),
            'customer_count' => (int) ($totals['customer_count'] ?? 0),
            'location_count' => (int) ($totals['location_count'] ?? 0),
            'product_count' => (int) ($totals['product_count'] ?? 0),
            'invoice_count' => $dimension === 'category' || $dimension === 'brand'
                ? __('db.profitability_not_available')
                : (int) ($totals['invoice_count'] ?? 0),
            'net_quantity' => $this->formatProfitabilityNumber($totals['net_quantity']),
            'gross_sales' => $this->formatProfitabilityMoney($totals['gross_sales']),
            'discounts' => $this->formatProfitabilityMoney($totals['discounts']),
            'returns' => $this->formatProfitabilityMoney($totals['returned_revenue']),
            'net_sales' => $this->formatProfitabilityMoney($totals['net_sales']),
            'net_cost' => $this->formatProfitabilityMoney($totals['net_cost']),
            'gross_profit' => $this->formatProfitabilityMoney($totals['gross_profit']),
            'gross_margin' => $this->formatProfitabilityMargin($totals['gross_margin']),
            'profit_contribution' => __('db.profitability_not_available'),
            'estimated_cost' => (bool) $totals['estimated_cost'],
            'cost_basis' => collect($totals['cost_basis_codes'])
                ->map(fn ($basis) => $this->profitabilityCostBasisLabel($basis))
                ->implode(', '),
        ];
    }

    private function formatProfitabilityDate(?string $date): string
    {
        if (! $date) {
            return __('db.profitability_not_available');
        }

        try {
            return Carbon::parse($date)->format(config('date_format', 'Y-m-d'));
        } catch (\Throwable) {
            return __('db.profitability_not_available');
        }
    }

    private function formatProfitabilityMoney(float $amount): string
    {
        return format_currency($amount);
    }

    private function formatProfitabilityNumber(float $amount): string
    {
        return number_format($amount, (int) config('decimal', 2), '.', '');
    }

    private function formatProfitabilityMargin(?float $margin): string
    {
        if ($margin === null) {
            return __('db.profitability_not_available');
        }

        return number_format($margin, 2, '.', '') . '%';
    }

    private function profitabilityInvoiceActivityLabel(string $activity): string
    {
        return match ($activity) {
            'sale_and_return' => __('db.profitability_period_sale_and_return'),
            'return' => __('db.profitability_period_return'),
            default => __('db.profitability_period_sale'),
        };
    }

    private function profitabilityCostQualityLabel(bool $estimated): string
    {
        return $estimated
            ? __('db.profitability_estimated_cost')
            : __('db.profitability_recorded_cost');
    }

    private function profitabilityCostBasisLabel(string $basis): string
    {
        return match ($basis) {
            'imei_specific_purchase_cost' => trans()->has('db.profitability_basis_imei_specific_purchase_cost')
                ? __('db.profitability_basis_imei_specific_purchase_cost')
                : 'IMEI/Serial specific purchase cost',
            'imei_specific_purchase_cost_with_fallback' => trans()->has('db.profitability_basis_imei_specific_purchase_cost_with_fallback')
                ? __('db.profitability_basis_imei_specific_purchase_cost_with_fallback')
                : 'IMEI/Serial specific cost with legacy fallback',
            'batch_average_purchase_cost' => __('db.profitability_basis_batch_average_purchase_cost'),
            'variant_average_purchase_cost' => __('db.profitability_basis_variant_average_purchase_cost'),
            'product_average_purchase_cost' => __('db.profitability_basis_product_average_purchase_cost'),
            'combo_component_average_purchase_cost' => __('db.profitability_basis_combo_component_average_purchase_cost'),
            'combo_component_cost_estimate' => __('db.profitability_basis_combo_component_cost_estimate'),
            'current_production_cost_estimate' => __('db.profitability_basis_current_production_cost_estimate'),
            'current_service_or_digital_cost_estimate' => __('db.profitability_basis_current_service_or_digital_cost_estimate'),
            'zero_service_or_digital_cost' => __('db.profitability_basis_zero_service_or_digital_cost'),
            'current_product_cost_estimate' => __('db.profitability_basis_current_product_cost_estimate'),
            'missing_product' => __('db.profitability_basis_missing_product'),
            'combo_components_missing' => __('db.profitability_basis_combo_components_missing'),
            default => __('db.profitability_basis_unknown'),
        };
    }

    public function productReport(Request $request)
    {
        $data = $request->all();
        $start_date = $request->start_date ?? date('Y-m').'-'.'01';
        $end_date = $request->end_date ?? date('Y-m-d');
        $warehouse_id = $data['warehouse_id'] ?? 0;
        $category_id = $data['category_id'] ?? 0;
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.report.product_report',compact('start_date', 'end_date', 'warehouse_id', 'category_id', 'lims_warehouse_list'));
    }

    public function productReportData(Request $request)
    {
        try {
        // --- Input & safe defaults
        $start_date = $request->input('start_date') . ' 00:00:00';
        $end_date   = $request->input('end_date') . ' 23:59:59';
        $warehouse_id = (int) $request->input('warehouse_id', 0);
        $category_id = (int) $request->input('category_id', 0);
        $search       = $request->input('search.value', null);
        $draw         = (int) $request->input('draw', 1);
        $limit        = $request->input('length') != -1 ? (int)$request->input('length') : 1000;
        $start        = (int) $request->input('start', 0);
        $orderColumn  = $request->input('order.0.column');
        $columns = [1 => 'name']; // keep mapping consistent with your frontend
        $order = $columns[$orderColumn] ?? 'name';
        $dir   = $request->input('order.0.dir', 'asc');

        // --- Preload small reference tables once
        $units = DB::table('units')->get()->keyBy('id')->toArray(); // cached units

        // helper for unit conversion (keep it local)
        $convertQty = function ($qty, $unitId) use ($units) {
            if (!$unitId || !isset($units[$unitId])) {
                return (float)$qty;
            }
            $unit = $units[$unitId];
            if ($unit->operator === '*') {
                return (float)$qty * (float)$unit->operation_value;
            }
            return (float)$qty / (float)$unit->operation_value;
        };

        // --- 1) Build list of product IDs that have ANY stock (>0)
        $stockQuery = DB::table('product_warehouse')
            ->selectRaw('product_id, COALESCE(variant_id, 0) as variant_id, SUM(qty) as total_qty')
            ->groupBy('product_id', DB::raw('COALESCE(variant_id, 0)'));

        // Apply warehouse-specific filter ONLY when $warehouse_id != 0
        if ($warehouse_id != 0) {
            $stockQuery->where('warehouse_id', $warehouse_id);
        }

        // Only include products that have POSITIVE stock in that warehouse
        $stockRows = $stockQuery->havingRaw('SUM(qty) > 0')->get();

        // Build maps for stock:
        // stocksByProduct[product_id]['variants'][variant_id] = qty
        // stocksByProduct[product_id]['total'] = total qty across variants
        $stocksByProduct = [];
        foreach ($stockRows as $r) {
            $pid = (int)$r->product_id;
            $vid = (int)$r->variant_id;
            $qty = (float)$r->total_qty;
            if (!isset($stocksByProduct[$pid])) $stocksByProduct[$pid] = ['variants' => [], 'total' => 0.0];
            $stocksByProduct[$pid]['variants'][$vid] = $qty;
            $stocksByProduct[$pid]['total'] += $qty;
        }

        // If no product has stock, return empty result early
        if (empty($stocksByProduct)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $productIdsWithStock = array_keys($stocksByProduct);

        // --- 2) DataTables light query: count, filtered ids & page
        $productsBaseQuery = Product::with('category')
            ->select('id', 'name', 'code', 'category_id', 'qty', 'is_variant', 'price', 'cost')
            ->where('is_active', true)
            ->when($this->own_data,function($query) {
                $query->where('user_id',$this->current_user_id);
            })
            ->when($category_id > 0, fn($q) => $q->where('category_id', $category_id))
            ->whereIn('id', $productIdsWithStock);

        $totalData = (clone $productsBaseQuery)->count();

        if ($search) {
            $productsBaseQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // total count (filtered)
        $totalFiltered = (clone $productsBaseQuery)->count();

        // fetch only product ids for the current page (lightweight)
        $pagedProductIds = $productsBaseQuery
            ->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->pluck('id')
            ->toArray();

        if (empty($pagedProductIds)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalData,
                'recordsFiltered' => $totalFiltered,
                'data' => []
            ]);
        }

        // --- 3) Load full product models (with category) for the paged ids
        $products = Product::with('category')
            ->select('id', 'name', 'code', 'category_id', 'qty', 'is_variant', 'price', 'cost')
            ->whereIn('id', $pagedProductIds)
            ->when($this->own_data,function($query) {
                $query->where('user_id',$this->current_user_id);
            })
            ->get()
            ->keyBy('id');

        // --- 4) Load product variants for these products (if any) тАФ we'll use variant qty from ProductVariant->qty for global
        $productVariants = ProductVariant::whereIn('product_id', $pagedProductIds)
            ->select('product_id', 'variant_id', 'item_code', 'qty')
            ->when($this->own_data,function($query) {
                $query->where('user_id',$this->current_user_id);
            })
            ->get()
            ->groupBy('product_id');

        // helper map accessors to check stock for variant/global
        // For variant-level stock, prefer product_warehouse aggregate if available, else ProductVariant.qty
        // stocksByProduct might contain variant entries from product_warehouse (global sums)
        // productVariants map gives per-variant qty from ProductVariant table (non-warehouse)
        // We'll use stocksByProduct first (since it's grouped from product_warehouse), otherwise fallback.

        // --- 5) Load aggregated transactional data in batches (grouped)
        // Purchases (product_purchases JOIN purchases) => amount (total/exchange_rate) and qty grouped by unit id to convert later
        $purchaseRows = DB::table('purchases')
            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->when($warehouse_id > 0, function ($q) use ($warehouse_id) {
                return $q->where('purchases.warehouse_id', $warehouse_id);
            })
            ->when($this->own_data,function($query) {
                $query->where('purchases.user_id',$this->current_user_id);
            })
            ->whereNull('purchases.deleted_at')
            ->whereDate('purchases.created_at', '>=', $start_date)
            ->whereDate('purchases.created_at', '<=', $end_date)
            ->whereIn('product_purchases.product_id', $pagedProductIds)
            ->selectRaw('product_purchases.product_id, COALESCE(product_purchases.variant_id, 0) as variant_id, product_purchases.purchase_unit_id as unit_id, SUM(product_purchases.qty) as qty_sum, SUM((product_purchases.net_unit_cost * product_purchases.qty) / COALESCE(NULLIF(purchases.exchange_rate, 0), 1)) as amount_sum')
            ->groupBy('product_purchases.product_id', 'variant_id', 'product_purchases.purchase_unit_id')
            ->get();

        // Sales
        $saleRows = DB::table('sales')
            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
            ->leftJoin('installment_plans', function ($join) {
                $join->on('installment_plans.reference_id', '=', 'sales.id')
                    ->where('installment_plans.reference_type', '=', 'sale');
            })
            ->when($warehouse_id > 0, function ($q) use ($warehouse_id) {
                return $q->where('sales.warehouse_id', $warehouse_id);
            })
            ->when($this->own_data,function($query) {
                $query->where('sales.user_id',$this->current_user_id);
            })
            ->whereNull('sales.deleted_at')
            ->whereDate('sales.created_at', '>=', $start_date)
            ->whereDate('sales.created_at', '<=', $end_date)
            ->whereIn('product_sales.product_id', $pagedProductIds)
            ->selectRaw('product_sales.product_id, COALESCE(product_sales.variant_id, 0) as variant_id, product_sales.sale_unit_id as unit_id, SUM(product_sales.qty) as qty_sum, SUM(product_sales.total / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as amount_sum, SUM((product_sales.tax + (CASE WHEN (sales.total_price - COALESCE(installment_plans.additional_amount, 0)) > 0 THEN (product_sales.total / (sales.total_price - COALESCE(installment_plans.additional_amount, 0))) * COALESCE(sales.order_tax, 0) ELSE 0 END)) / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as tax_sum')
            ->groupBy('product_sales.product_id', 'variant_id', 'product_sales.sale_unit_id')
            ->get();

        // Product returns (returns join product_returns)
        $returnRows = DB::table('returns')
            ->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
            ->when($warehouse_id > 0, function ($q) use ($warehouse_id) {
                return $q->where('returns.warehouse_id', $warehouse_id);
            })
            ->when($this->own_data,function($query) {
                $query->where('returns.user_id',$this->current_user_id);
            })
            ->whereDate('returns.created_at', '>=', $start_date)
            ->whereDate('returns.created_at', '<=', $end_date)
            ->whereIn('product_returns.product_id', $pagedProductIds)
            ->selectRaw('product_returns.product_id, COALESCE(product_returns.variant_id, 0) as variant_id, product_returns.sale_unit_id as unit_id, SUM(product_returns.qty) as qty_sum, SUM(product_returns.total  / COALESCE(NULLIF(returns.exchange_rate, 0), 1)) as amount_sum')
            ->groupBy('product_returns.product_id', 'variant_id', 'product_returns.sale_unit_id')
            ->get();

        // Purchase returns (return_purchases join purchase_product_return)
        $purchaseReturnRows = DB::table('return_purchases')
            ->join('purchase_product_return', 'return_purchases.id', '=', 'purchase_product_return.return_id')
            ->when($warehouse_id > 0, function ($q) use ($warehouse_id) {
                return $q->where('return_purchases.warehouse_id', $warehouse_id);
            })
            ->when($this->own_data,function($query) {
                $query->where('return_purchases.user_id',$this->current_user_id);
            })
            ->whereDate('return_purchases.created_at', '>=', $start_date)
            ->whereDate('return_purchases.created_at', '<=', $end_date)
            ->whereIn('purchase_product_return.product_id', $pagedProductIds)
            ->selectRaw('purchase_product_return.product_id, COALESCE(purchase_product_return.variant_id, 0) as variant_id, purchase_product_return.purchase_unit_id as unit_id, SUM(purchase_product_return.qty) as qty_sum, SUM(purchase_product_return.total  / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) as amount_sum')
            ->groupBy('purchase_product_return.product_id', 'variant_id', 'purchase_product_return.purchase_unit_id')
            ->get();

        // Build aggregated maps: amounts and converted qtys per product+variant
        $aggregate = function ($rows) use ($convertQty) {
            $map = [];
            foreach ($rows as $r) {
                $pid = (int)$r->product_id;
                $vid = (int)$r->variant_id;
                $unitId = $r->unit_id;
                $qtySum = (float)$r->qty_sum;
                $amountSum = (float)$r->amount_sum;

                if (!isset($map[$pid])) $map[$pid] = [];
                if (!isset($map[$pid][$vid])) $map[$pid][$vid] = ['amount' => 0.0, 'qty' => 0.0, 'tax' => 0.0];

                $map[$pid][$vid]['amount'] += $amountSum;
                $map[$pid][$vid]['tax'] += (float)($r->tax_sum ?? 0);
                // Convert qty per unit rules
                $map[$pid][$vid]['qty'] += $convertQty($qtySum, $unitId);
            }
            return $map;
        };

        $purchasesMap = $aggregate($purchaseRows);
        $salesMap = $aggregate($saleRows);
        $returnsMap = $aggregate($returnRows);
        $purchaseReturnsMap = $aggregate($purchaseReturnRows);

        // Profit must use the same COGS engine as Profit/Loss and the dedicated
        // profitability reports. Period purchase averages are not a valid proxy
        // for the cost of units sold, especially for IMEI/Batch inventory.
        $profitabilityRows = app(ProfitabilityService::class)
            ->productRows($this->profitabilityFilterFromRequest($request));
        $profitabilityMap = [];
        foreach ($profitabilityRows as $profitabilityRow) {
            $profitabilityKey = (int) $profitabilityRow['product_id'] . ':' . (int) ($profitabilityRow['variant_id'] ?? 0);
            $profitabilityMap[$profitabilityKey] = $profitabilityRow;
        }

        // --- 6) Build final rows
        $data = [];

        foreach ($products as $product) {
            $pid = $product->id;
            $isVariant = (bool)$product->is_variant;

            // Function to fetch aggregated values with safe defaults
            $getAgg = function ($map, $pid, $vid) {
                return $map[$pid][$vid] ?? ['amount' => 0.0, 'qty' => 0.0, 'tax' => 0.0];
            };

            if ($isVariant) {
                // iterate over variants for this product
                $variants = $productVariants->get($pid) ?? collect([]);
                foreach ($variants as $variant) {
                    $vid = (int)$variant->variant_id;
                    $itemCode = $variant->item_code ?? $variant->id;

                    // Determine in_stock
                    $inStock = 0;
                    if (isset($stocksByProduct[$pid]['variants'][$vid])) {
                        $inStock = (float)$stocksByProduct[$pid]['variants'][$vid];
                    } else {
                        // fallback to ProductVariant.qty
                        $inStock = (float)$variant->qty;
                    }

                    // skip rows with zero stock (preserves your previous behavior)
                    if ($inStock <= 0) continue;

                    // aggregated numbers
                    $purchased = $getAgg($purchasesMap, $pid, $vid);
                    $sold      = $getAgg($salesMap, $pid, $vid);
                    $returned  = $getAgg($returnsMap, $pid, $vid);
                    $purchaseReturned = $getAgg($purchaseReturnsMap, $pid, $vid);

                    $nested = [];
                    $nested['imei_numbers'] = $this->findImeis($pid, $vid);
                    $nested['key'] = count($data);
                    $variantName = optional(Variant::select('name')->find($vid))->name ?? 'N/A';
                    $nested['name'] = $product->name . ' [' . $variantName . ']' . '<br/>Product Code: ' . $itemCode;
                    $nested['category'] = optional($product->category)->name ?? 'N/A';

                    $nested['purchased_amount'] = $purchased['amount'];
                    $nested['purchased_qty'] = $purchased['qty'];

                    $nested['sold_amount'] = $sold['amount'];
                    $nested['tax'] = $sold['tax'];
                    $nested['sold_qty'] = $sold['qty'];

                    $nested['returned_amount'] = $returned['amount'];
                    $nested['returned_qty'] = $returned['qty'];

                    $nested['purchase_returned_amount'] = $purchaseReturned['amount'];
                    $nested['purchase_returned_qty'] = $purchaseReturned['qty'];

                    $profitability = $profitabilityMap[$pid . ':' . $vid] ?? null;
                    $nested['profit'] = (float) ($profitability['gross_profit'] ?? 0);

                    $nested['in_stock'] = $inStock;

                    $averageCost = 0;

                    if ($purchased['qty'] > 0) {
                        $averageCost = $purchased['amount'] / $purchased['qty'];
                    }

                    $nested['stock_worth'] = format_currency($nested['in_stock'] * $product->price).' / '.format_currency($nested['in_stock'] * $averageCost);

                    $nested['profit'] = number_format((float)$nested['profit'], config('decimal'), '.', '');

                    $data[] = $nested;
                }
            } else {
                // non-variant product
                // Determine in_stock: from product_warehouse group or product->qty fallback
                $inStock = $stocksByProduct[$pid]['total'] ?? (float)$product->qty;

                if ($inStock <= 0) continue;

                $purchased = $getAgg($purchasesMap, $pid, 0);
                $sold = $getAgg($salesMap, $pid, 0);
                $returned = $getAgg($returnsMap, $pid, 0);
                $purchaseReturned = $getAgg($purchaseReturnsMap, $pid, 0);

                $nested = [];
                $nested['imei_numbers'] = $this->findImeis($pid);
                $nested['key'] = count($data);
                $nested['name'] = $product->name . '<br/>Product Code: ' . $product->code;
                $nested['category'] = optional($product->category)->name ?? 'N/A';

                $nested['purchased_amount'] = $purchased['amount'];
                $nested['purchased_qty'] = $purchased['qty'];

                $nested['sold_amount'] = $sold['amount'];
                $nested['tax'] = $sold['tax'];
                $nested['sold_qty'] = $sold['qty'];

                $nested['returned_amount'] = $returned['amount'];
                $nested['returned_qty'] = $returned['qty'];

                $nested['purchase_returned_amount'] = $purchaseReturned['amount'];
                $nested['purchase_returned_qty'] = $purchaseReturned['qty'];

                $profitability = $profitabilityMap[$pid . ':0'] ?? null;
                $nested['profit'] = (float) ($profitability['gross_profit'] ?? 0);

                $nested['in_stock'] = $inStock;

                $averageCost = 0;

                if ($purchased['qty'] > 0) {
                    $averageCost = $purchased['amount'] / $purchased['qty'];
                }
                
                $nested['stock_worth'] = format_currency($nested['in_stock'] * $product->price).' / '.format_currency($nested['in_stock'] * $averageCost);
                
                $nested['profit'] = number_format((float)$nested['profit'], config('decimal'), '.', '');

                $data[] = $nested;
            }
        }

        // --- 7) Return DataTables JSON
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$totalData,
            'recordsFiltered' => (int)$totalFiltered,
            'data' => $data
        ]);
        } catch (\Throwable $e) {
            \Log::error('Product report data failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Product report search failed.',
            ], 500);
        }
    }

    private function findImeis(string $product_id, string $variant_id = '0')
    {
        $imei_numbers = [];
        $purchases = [];
        if ($variant_id === '0') {
            $purchases = Product_Warehouse::where('product_id', $product_id)
                ->whereNotNull('imei_number')
                ->select('imei_number')->get();
        } else {
            $purchases = Product_Warehouse::where('product_id', $product_id)
                ->where('variant_id', '=', $variant_id)
                ->whereNotNull('imei_number')
                ->select('imei_number')->get();
        }

        foreach ($purchases as $purchase) {
            $imei_numbers[] = array_unique(explode(',', $purchase->imei_number));
        }
        $imeis = [];
        foreach ($imei_numbers as $imei_number) {
            foreach ($imei_number as $imei) {
                if ($imei != 'null')
                    $imeis[] = $imei;
            }
        }

        $convert_to_string = '';
        foreach ($imeis as $key => $value) {
            $convert_to_string .= $value;
            if (count($imeis)-1 > $key) {
                $convert_to_string .= '<br/>';
            }
        }

        if (empty($convert_to_string)) {
            return 'N/A';
        }
        return $convert_to_string;
    }

    public function purchaseReport(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $request->input('end_date') ?: date('Y-m-d');
        $warehouse_id = $request->input('warehouse_id', 0) ?: 0;
        $category_id = $request->input('category_id', 0) ?: 0;
        $product_id = [];
        $variant_id = [];
        $product_name = [];
        $product_qty = [];
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.report.purchase_report',compact('product_id', 'variant_id', 'product_name', 'product_qty', 'start_date', 'end_date', 'lims_warehouse_list', 'warehouse_id', 'category_id'));
    }

    public function purchaseReportData(Request $request)
    {
        $startDateVal = $request->input('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $endDateVal = $request->input('end_date') ?: date('Y-m-d');
        $start_date = $startDateVal . ' 00:00:00';
        $end_date = $endDateVal . ' 23:59:59';
        $warehouse_id = $request->input('warehouse_id', 0) ?: 0;
        $category_id = $request->input('category_id', 0) ?: 0;
        $product_id = [];
        $variant_id = [];
        $product_name = [];
        $product_qty = [];
        $totalData = 0;

        $columns = array(
            1 => 'name',
            2 => 'category_id',
            5 => 'qty'
        );

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        //return $request;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir');
        if($request->input('search.value')) {
            $search = $request->input('search.value');
            $totalData = Product::where('is_active', true)
            ->when($category_id > 0, fn($q) => $q->where('category_id', $category_id))
            ->where('name', 'LIKE', "%{$search}%")
            ->count();
            $lims_product_all = Product::with('category')
                                ->select('id', 'name', 'code', 'category_id', 'qty', 'is_variant', 'price', 'cost')
                                ->when($category_id > 0, fn($q) => $q->where('category_id', $category_id))
                                ->where([
                                    ['name', 'LIKE', "%{$search}%"],
                                    ['is_active', true]
                                ])->offset($start)
                                  ->limit($limit)
                                  ->orderBy($order, $dir)
                                  ->get();
        }
        else {
            $totalData = Product::where('is_active', true)
            ->when($category_id > 0, fn($q) => $q->where('category_id', $category_id))
            ->count();
            $lims_product_all = Product::with('category')
                                ->select('id', 'name', 'code', 'category_id', 'qty', 'is_variant', 'price', 'cost')
                                ->when($category_id > 0, fn($q) => $q->where('category_id', $category_id))
                                ->where('is_active', true)
                                ->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $dir)
                                ->get();
        }

        $totalFiltered = $totalData;
        $purchaseTotals = function (int $productId, ?int $variantId = null) use ($warehouse_id, $start_date, $end_date) {
            return DB::table('purchases')
                ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                ->when($this->own_data, fn($q) => $q->where('purchases.user_id', $this->current_user_id))
                ->when($warehouse_id > 0, fn($q) => $q->where('purchases.warehouse_id', $warehouse_id))
                ->when($variantId !== null, fn($q) => $q->where('product_purchases.variant_id', $variantId))
                ->where('product_purchases.product_id', $productId)
                ->whereNull('purchases.deleted_at')
                ->whereDate('purchases.created_at', '>=', $start_date)
                ->whereDate('purchases.created_at', '<=', $end_date)
                ->selectRaw('SUM(product_purchases.total / COALESCE(NULLIF(purchases.exchange_rate, 0), 1)) as purchased_amount, SUM((product_purchases.tax + (CASE WHEN purchases.total_cost > 0 THEN (product_purchases.total / purchases.total_cost) * COALESCE(purchases.order_tax, 0) ELSE 0 END)) / COALESCE(NULLIF(purchases.exchange_rate, 0), 1)) as tax')
                ->first();
        };
        $data = [];
        foreach ($lims_product_all as $product) {
            $variant_id_all = [];
            if($warehouse_id == 0) {
                if($product->is_variant) {
                    $variant_id_all = ProductVariant::where('product_id', $product->id)->pluck('variant_id', 'item_code');
                    foreach ($this->purchaseReportHistoricalVariantIds($product->id, 0, $start_date, $end_date) as $historicalVariantId) {
                        if (!$variant_id_all->contains($historicalVariantId)) {
                            $variant_id_all->put($product->code.'-HIST-'.$historicalVariantId, $historicalVariantId);
                        }
                    }
                    foreach ($variant_id_all as $item_code => $variant_id) {
                        $variant_data = Variant::select('name')->find($variant_id);
                        $nestedData['key'] = count($data);
                        $imeis = $this->findImeis($product->id, $variant_id);
                        $variantName = $variant_data?->name ?? __('db.Unknown').' #'.$variant_id;
                        $nestedData['name'] = $product->name . ' [' . $variantName . ']'.'<br>'. 'Product Code: ' . $item_code . ($imeis != 'N/A' ? '<br>' . 'IMEI: ' . str_replace("<br/>", ",", $imeis) : '');
                        $nestedData['category'] = $product->category->name;
                        //purchase data
                        $totals = $purchaseTotals($product->id, $variant_id);
                        $nestedData['purchased_amount'] = (float)($totals->purchased_amount ?? 0);
                        $nestedData['tax'] = (float)($totals->tax ?? 0);

                        $lims_product_purchase_data = ProductPurchase::select('purchase_unit_id', 'qty')->where([
                                                ['product_id', $product->id],
                                                ['variant_id', $variant_id]
                                        ])->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();

                        $purchased_qty = 0;
                        if(count($lims_product_purchase_data)) {
                            foreach ($lims_product_purchase_data as $product_purchase) {
                                $unit = $product_purchase->purchase_unit_id ? DB::table('units')->find($product_purchase->purchase_unit_id) : null;
                                if($unit && $unit->operator == '*'){
                                    $purchased_qty += $product_purchase->qty * $unit->operation_value;
                                }
                                elseif($unit && $unit->operator == '/'){
                                    $purchased_qty += $product_purchase->qty / ($unit->operation_value ?: 1);
                                }
                                else {
                                    $purchased_qty += $product_purchase->qty;
                                }
                            }
                        }
                        $nestedData['purchased_qty'] = $purchased_qty;


                        $product_variant_data = ProductVariant::where([
                            ['product_id', $product->id],
                            ['variant_id', $variant_id]
                        ])->select('qty')->first();
                        $nestedData['in_stock'] = $product_variant_data?->qty
                            ?? Product_Warehouse::where('product_id', $product->id)->where('variant_id', $variant_id)->sum('qty');

                        $data[] = $nestedData;
                    }
                } else {
                    $nestedData['key'] = count($data);
                    $imeis = $this->findImeis($product->id);
                    $nestedData['name'] = $product->name.'<br>'. 'Product Code: ' . $product->code . ($imeis != 'N/A' ? '<br>' . 'IMEI: ' . str_replace("<br/>", ",", $imeis) : '');
                    $nestedData['category'] = $product->category->name;
                    //purchase data
                    $totals = $purchaseTotals($product->id);
                    $nestedData['purchased_amount'] = (float)($totals->purchased_amount ?? 0);
                    $nestedData['tax'] = (float)($totals->tax ?? 0);

                    $lims_product_purchase_data = ProductPurchase::select('purchase_unit_id', 'qty')->where('product_id', $product->id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();

                    $purchased_qty = 0;
                    if(count($lims_product_purchase_data)) {
                        foreach ($lims_product_purchase_data as $product_purchase) {
                            $unit = $product_purchase->purchase_unit_id ? DB::table('units')->find($product_purchase->purchase_unit_id) : null;
                            if($unit && $unit->operator == '*'){
                                $purchased_qty += $product_purchase->qty * $unit->operation_value;
                            }
                            elseif($unit && $unit->operator == '/'){
                                $purchased_qty += $product_purchase->qty / ($unit->operation_value ?: 1);
                            }
                            else {
                                $purchased_qty += $product_purchase->qty;
                            }
                        }
                    }
                    $nestedData['purchased_qty'] = $purchased_qty;
                    $nestedData['in_stock'] = $product->qty;

                    $data[] = $nestedData;
                }
            }
            else {
                if($product->is_variant) {
                    $variant_id_all = ProductVariant::where('product_id', $product->id)->pluck('variant_id', 'item_code');
                    foreach ($this->purchaseReportHistoricalVariantIds($product->id, (int) $warehouse_id, $start_date, $end_date) as $historicalVariantId) {
                        if (!$variant_id_all->contains($historicalVariantId)) {
                            $variant_id_all->put($product->code.'-HIST-'.$historicalVariantId, $historicalVariantId);
                        }
                    }

                    foreach ($variant_id_all as $item_code => $variant_id) {
                        $variant_data = Variant::select('name')->find($variant_id);
                        $nestedData['key'] = count($data);
                        $variantName = $variant_data?->name ?? __('db.Unknown').' #'.$variant_id;
                        $nestedData['name'] = $product->name . ' [' . $variantName . ']'.'<br>'. 'Product Code: ' . $item_code;
                        $nestedData['category'] = $product->category->name;
                        //purchase data
                        $totals = $purchaseTotals($product->id, $variant_id);
                        $nestedData['purchased_amount'] = (float)($totals->purchased_amount ?? 0);
                        $nestedData['tax'] = (float)($totals->tax ?? 0);
                        $lims_product_purchase_data = DB::table('purchases')
                                    ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                        ['product_purchases.product_id', $product->id],
                                        ['product_purchases.variant_id', $variant_id],
                                        ['purchases.warehouse_id', $warehouse_id]
                                    ])->whereNull('purchases.deleted_at')
                                    ->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)
                                    ->select('product_purchases.purchase_unit_id', 'product_purchases.qty')
                                    ->get();

                        $purchased_qty = 0;
                        if(count($lims_product_purchase_data)) {
                            foreach ($lims_product_purchase_data as $product_purchase) {
                                $unit = $product_purchase->purchase_unit_id ? DB::table('units')->find($product_purchase->purchase_unit_id) : null;
                                if($unit && $unit->operator == '*'){
                                    $purchased_qty += $product_purchase->qty * $unit->operation_value;
                                }
                                elseif($unit && $unit->operator == '/'){
                                    $purchased_qty += $product_purchase->qty / ($unit->operation_value ?: 1);
                                }
                                else {
                                    $purchased_qty += $product_purchase->qty;
                                }
                            }
                        }
                        $nestedData['purchased_qty'] = $purchased_qty;

                        $product_warehouse = Product_Warehouse::where([
                            ['product_id', $product->id],
                            ['variant_id', $variant_id],
                            ['warehouse_id', $warehouse_id]
                        ])->select('qty')->first();
                        if($product_warehouse)
                            $nestedData['in_stock'] = $product_warehouse->qty;
                        else
                            $nestedData['in_stock'] = 0;

                        $data[] = $nestedData;
                    }
                }
                else {
                    $nestedData['key'] = count($data);
                    $nestedData['name'] = $product->name.'<br>'. 'Product Code: ' . $product->code;
                    $nestedData['category'] = $product->category->name;
                    //purchase data
                    $totals = $purchaseTotals($product->id);
                    $nestedData['purchased_amount'] = (float)($totals->purchased_amount ?? 0);
                    $nestedData['tax'] = (float)($totals->tax ?? 0);
                    $lims_product_purchase_data = DB::table('purchases')
                                ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                    ['product_purchases.product_id', $product->id],
                                    ['purchases.warehouse_id', $warehouse_id]
                                ])->whereNull('purchases.deleted_at')
                                ->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)
                                ->select('product_purchases.purchase_unit_id', 'product_purchases.qty')
                                ->get();

                    $purchased_qty = 0;
                    if(count($lims_product_purchase_data)) {
                        foreach ($lims_product_purchase_data as $product_purchase) {
                            $unit = $product_purchase->purchase_unit_id ? DB::table('units')->find($product_purchase->purchase_unit_id) : null;
                            if($unit && $unit->operator == '*'){
                                $purchased_qty += $product_purchase->qty * $unit->operation_value;
                            }
                            elseif($unit && $unit->operator == '/'){
                                $purchased_qty += $product_purchase->qty / ($unit->operation_value ?: 1);
                            }
                            else {
                                $purchased_qty += $product_purchase->qty;
                            }
                        }
                    }
                    $nestedData['purchased_qty'] = $purchased_qty;

                    $product_warehouse = Product_Warehouse::where([
                        ['product_id', $product->id],
                        ['warehouse_id', $warehouse_id]
                    ])->select('qty')->first();
                    if($product_warehouse)
                        $nestedData['in_stock'] = $product_warehouse->qty;
                    else
                        $nestedData['in_stock'] = 0;

                    $data[] = $nestedData;
                }
            }
        }

        /*$totalData = count($data);
        $totalFiltered = $totalData;*/
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        return response()->json($json_data);
    }

    private function purchaseReportHistoricalVariantIds(int $productId, int $warehouseId, string $startDate, string $endDate): array
    {
        return DB::table('purchases')
            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
            ->when($this->own_data, fn($query) => $query->where('purchases.user_id', $this->current_user_id))
            ->when($warehouseId > 0, fn($query) => $query->where('purchases.warehouse_id', $warehouseId))
            ->where('product_purchases.product_id', $productId)
            ->whereNotNull('product_purchases.variant_id')
            ->where('product_purchases.variant_id', '!=', 0)
            ->whereNull('purchases.deleted_at')
            ->whereDate('purchases.created_at', '>=', $startDate)
            ->whereDate('purchases.created_at', '<=', $endDate)
            ->distinct()
            ->pluck('product_purchases.variant_id')
            ->map(fn($variantId) => (int) $variantId)
            ->all();
    }

    public function saleReport(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $request->input('end_date') ?: date('Y-m-d');
        $warehouse_id = $request->input('warehouse_id', 0) ?: 0;
        $category_id = $request->input('category_id', 0) ?: 0;
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        // Fetch custom fields data
        // $custom_fields = CustomField::where([
        //     ['belongs_to', 'sale'],
        //     ['is_table', true]
        // ])->pluck('name');
        // $field_names = [];
        // foreach($custom_fields as $fieldName) {
        //     $field_names[] = str_replace(" ", "_", strtolower($fieldName));
        // }

        return view('backend.report.sale_report',compact('start_date', 'end_date', 'warehouse_id', 'category_id', 'lims_warehouse_list',
        //  'custom_fields', 'field_names'
         ));
    }

    public function saleReportData(Request $request)
    {
        $start_date   = $request->start_date . ' 00:00:00';
        $end_date     = $request->end_date . ' 23:59:59';
        $warehouseAccess = app(\App\Services\WarehouseAccessService::class);
        abort_if(
            !$warehouseAccess->isGlobal() && !$warehouseAccess->hasValidWarehouseAssignment(),
            403,
            'A valid active warehouse assignment is required.'
        );
        $warehouse_id = $warehouseAccess->isRestricted()
            ? (int) $warehouseAccess->warehouseId()
            : (int) $request->warehouse_id;
        $category_id  = (int) $request->category_id;
    
        $limit  = $request->input('length', 10);
        $start  = $request->input('start', 0);
    
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir         = $request->input('order.0.dir', 'asc');
    
        $columns = [
            0 => 'products.name',
        ];
    
        $orderColumn = $columns[$orderColumnIndex] ?? 'products.name';
    
        $search = $request->input('search.value');
    
        /*
        |--------------------------------------------------------------------------
        | Base Aggregated Query (CORE OPTIMIZATION)
        |--------------------------------------------------------------------------
        */
        $query = DB::table('product_sales')
            ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
            ->join('products', 'products.id', '=', 'product_sales.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('installment_plans', function($join) {
                $join->on('installment_plans.reference_id', '=', 'sales.id')
                     ->where('installment_plans.reference_type', '=', 'sale');
            })
            ->whereNull('sales.deleted_at')
            ->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)
            ->when($warehouse_id > 0, fn($q) => $q->where('sales.warehouse_id', $warehouse_id))
            ->when($category_id > 0, fn($q) => $q->where('products.category_id', $category_id))
            ->when($search, fn($q) => $q->where('products.name', 'LIKE', "%{$search}%"))
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.is_variant',
                'products.qty as product_qty',
                'categories.name as category_name',
                'product_sales.variant_id', 
                DB::raw('SUM((product_sales.total + (CASE WHEN (sales.total_price - COALESCE(installment_plans.additional_amount, 0)) > 0 THEN (product_sales.total / (sales.total_price - COALESCE(installment_plans.additional_amount, 0))) * (COALESCE(sales.order_tax, 0) + COALESCE(sales.shipping_cost, 0) + COALESCE(sales.service_charge, 0) + COALESCE(installment_plans.additional_amount, 0) - COALESCE(sales.order_discount, 0) - COALESCE(sales.coupon_discount, 0)) ELSE 0 END)) / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as sold_amount'),
                DB::raw('SUM(product_sales.qty) as sold_qty'),
                DB::raw('SUM((product_sales.tax + (CASE WHEN (sales.total_price - COALESCE(installment_plans.additional_amount, 0)) > 0 THEN (product_sales.total / (sales.total_price - COALESCE(installment_plans.additional_amount, 0))) * COALESCE(sales.order_tax, 0) ELSE 0 END)) / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) as tax')
            )
            ->groupBy('products.id', 'product_sales.variant_id')
            ->havingRaw('SUM((product_sales.total + (CASE WHEN (sales.total_price - COALESCE(installment_plans.additional_amount, 0)) > 0 THEN (product_sales.total / (sales.total_price - COALESCE(installment_plans.additional_amount, 0))) * (COALESCE(sales.order_tax, 0) + COALESCE(sales.shipping_cost, 0) + COALESCE(sales.service_charge, 0) + COALESCE(installment_plans.additional_amount, 0) - COALESCE(sales.order_discount, 0) - COALESCE(sales.coupon_discount, 0)) ELSE 0 END)) / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) > 0'); // ЁЯФе omit zero sales
    
        /*
        |--------------------------------------------------------------------------
        | Total Count (for DataTables)
        |--------------------------------------------------------------------------
        */
        $totalData = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->count();
    
        /*
        |--------------------------------------------------------------------------
        | Fetch Data (with pagination)
        |--------------------------------------------------------------------------
        */
        $results = $query
            ->orderBy($orderColumn, $orderDir)
            ->offset($start)
            ->limit($limit)
            ->get();
    
        /*
        |--------------------------------------------------------------------------
        | Preload Variants (avoid N+1)
        |--------------------------------------------------------------------------
        */
        $variantIds = $results->pluck('variant_id')->filter()->unique();
    
        $variants = DB::table('variants')
            ->whereIn('id', $variantIds)
            ->pluck('name', 'id');
    
        /*
        |--------------------------------------------------------------------------
        | Preload Product Warehouse Stock
        |--------------------------------------------------------------------------
        */
        $productIds = $results->pluck('id')->unique();
    
        $productWarehouseStock = DB::table('product_warehouse')
            ->whereIn('product_id', $productIds)
            ->when($warehouse_id > 0, fn($q) => $q->where('warehouse_id', $warehouse_id))
            ->selectRaw('product_id, COALESCE(variant_id, 0) as variant_id, SUM(qty) as total_qty')
            ->groupBy('product_id', DB::raw('COALESCE(variant_id, 0)'))
            ->get()
            ->keyBy(fn($row) => $row->product_id . '_' . $row->variant_id);
    
        /*
        |--------------------------------------------------------------------------
        | Format Data
        |--------------------------------------------------------------------------
        */
        $data = [];
    
        foreach ($results as $index => $row) {
            $nestedData = [];
            
            $nestedData['key'] = $start + $index + 1;
    
            $variantName = $row->variant_id
                ? ($variants[$row->variant_id] ?? '')
                : null;
    
            $name = $row->name;
    
            if ($variantName) {
                $name .= " [{$variantName}]";
            }
    
            $name .= '<br>Product Code: ' . $row->code;
    
            $nestedData['name'] = $name;
            $nestedData['category'] = $row->category_name;
            $nestedData['sold_amount'] = (float) $row->sold_amount;
            $nestedData['tax'] = (float) $row->tax;
            $nestedData['sold_qty'] = (float) $row->sold_qty;
    
            /*
            |--------------------------------------------------------------------------
            | Stock Calculation (always from product_warehouse)
            |--------------------------------------------------------------------------
            */
            $stockKey = $row->id . '_' . ($row->variant_id ?? 0);
            $nestedData['in_stock'] = isset($productWarehouseStock[$stockKey])
                ? (float) $productWarehouseStock[$stockKey]->total_qty
                : 0;
    
            $data[] = $nestedData;
        }
    
        /*
        |--------------------------------------------------------------------------
        | Final JSON Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalData,
            "data" => $data
        ]);
    }


    public function stockReport(Request $request)
    {
        $warehouse_id = $request->warehouse_id ?? 0;

        $warehouses = Warehouse::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->get();

        return view('backend.report.stock_report', compact(
            'warehouses',
            'categories',
            'warehouse_id'
        ));
    }

    public function stockReportData(Request $request)
    {
        $warehouse_id = $request->warehouse_id;
        $category_id = $request->category_id;
        $status       = $request->stock_status;

        $draw  = intval($request->draw);
        $start = intval($request->start);
        $limit = intval($request->length);

        $search = $request->search['value'] ?? null;

        try {

            /*
            |--------------------------------------------------------------------------
            | Average Cost Query (Weighted Average Cost)
            |--------------------------------------------------------------------------
            */

            $averageCosts = DB::table('product_purchases as pp')

                ->join('purchases as p', 'pp.purchase_id', '=', 'p.id')

                ->selectRaw('
                    pp.product_id,

                    COALESCE(pp.variant_id, 0) as variant_id,

                    SUM(
                        (
                            pp.net_unit_cost * pp.qty
                        ) / COALESCE(NULLIF(p.exchange_rate, 0), 1)
                    ) / NULLIF(SUM(pp.qty), 0) as average_cost
                ')

                ->whereNull('p.deleted_at')

                ->when($warehouse_id && $warehouse_id > 0, function ($q) use ($warehouse_id) {
                    $q->where('p.warehouse_id', $warehouse_id);
                })

                ->when($this->own_data, function ($q) {
                    $q->where('p.user_id', $this->current_user_id);
                })

                ->groupBy(
                    'pp.product_id',
                    DB::raw('COALESCE(pp.variant_id, 0)')
                )

                ->get();

            /*
            |--------------------------------------------------------------------------
            | Convert Average Cost To Lookup Map
            |--------------------------------------------------------------------------
            */

            $averageCostMap = [];

            foreach ($averageCosts as $row) {

                $averageCostMap[$row->product_id][$row->variant_id]
                    = (float) $row->average_cost;
            }

            /*
            |--------------------------------------------------------------------------
            | Base Query
            |--------------------------------------------------------------------------
            */

            $query = DB::table('product_warehouse as pw')
                ->join('products as p', 'pw.product_id', '=', 'p.id')
                ->join('warehouses as w', 'pw.warehouse_id', '=', 'w.id')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->leftJoin('variants as v', 'pw.variant_id', '=', 'v.id')

                ->where('p.is_active', true)

                ->where(function ($q) {

                    // Simple products тАФ is_variant is nullable, so NULL means non-variant too
                    $q->where(function ($sub) {
                        $sub->where(function ($s) {
                                $s->where('p.is_variant', 0)
                                ->orWhereNull('p.is_variant');
                            })
                            ->whereNull('pw.variant_id');
                    });

                    // Variant products
                    $q->orWhere(function ($sub) {
                        $sub->where('p.is_variant', 1)
                            ->whereNotNull('pw.variant_id');
                    });
                })

                ->select(
                    'p.id',
                    'p.code',
                    'p.name',

                    'v.name as variant',
                    'v.id as variant_id',

                    'c.name as category',
                    'c.id as category_id',

                    'w.name as warehouse',
                    'w.id as warehouse_id',

                    'pw.qty',

                    'p.alert_quantity',

                    DB::raw('p.price as price'),
                    DB::raw('p.cost as fallback_cost')
                );

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            // Warehouse filter
            if ($warehouse_id && $warehouse_id > 0) {
                $query->where('pw.warehouse_id', $warehouse_id);
            }

            // Category filter
            if ($category_id && $category_id > 0) {
                $query->where('p.category_id', $category_id);
            }

            // Search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('p.name', 'like', "%{$search}%")
                        ->orWhere('p.code', 'like', "%{$search}%")
                        ->orWhere('v.name', 'like', "%{$search}%")
                        ->orWhere('w.name', 'like', "%{$search}%");
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Stock Status Filter
            |--------------------------------------------------------------------------
            */

            if ($status == 'in_stock') {

                $query->whereColumn('pw.qty', '>', 'p.alert_quantity');

            } elseif ($status == 'low_stock') {

                $query->whereColumn('pw.qty', '<=', 'p.alert_quantity')
                    ->where('pw.qty', '>', 0);

            } elseif ($status == 'out_stock') {

                $query->where('pw.qty', '=', 0);

            } elseif ($status == 'negative') {

                $query->where('pw.qty', '<', 0);
            }

            /*
            |--------------------------------------------------------------------------
            | Total Counts
            |--------------------------------------------------------------------------
            */

            $totalFiltered = (clone $query)->count();

            /*
            |--------------------------------------------------------------------------
            | Ordering
            |--------------------------------------------------------------------------
            */

            $orderColumnIndex = $request->input('order.0.column', 0);

            $orderDirection = $request->input('order.0.dir', 'asc');

            $columns = $request->columns ?? [];

            $orderColumnName = $columns[$orderColumnIndex]['data'] ?? 'name';

            $columnMap = [
                'code'      => 'p.code',
                'name'      => 'p.name',
                'variant'   => 'v.name',
                'category'  => 'c.name',
                'warehouse' => 'w.name',
                'qty'       => 'pw.qty',
                'price'     => 'p.price',
            ];

            $query->orderBy($columnMap[$orderColumnName] ?? 'p.name', $orderDirection);


            /*
            |--------------------------------------------------------------------------
            | Summary Totals (aggregate SQL тАФ full filtered dataset, not page-only)
            |--------------------------------------------------------------------------
            */
            $summaryQuery = clone $query;
            $summaryRows = $summaryQuery
                ->select('p.id', 'pw.qty', 'p.price', 'p.cost as fallback_cost', DB::raw('COALESCE(v.id, 0) as variant_id'))
                ->get();

            $summary_total_qty = 0;
            $summary_total_cost_value = 0;
            $summary_total_price_value = 0;
            $summary_total_profit = 0;

            foreach ($summaryRows as $sRow) {
                $sQty = (float) $sRow->qty;
                $sVariantKey = $sRow->variant_id ?? 0;
                $sAvgCost = $averageCostMap[$sRow->id][$sVariantKey] ?? (float) $sRow->fallback_cost;

                $summary_total_qty += $sQty;
                $summary_total_cost_value += $sQty * $sAvgCost;
                $summary_total_price_value += $sQty * (float) $sRow->price;
                $summary_total_profit += ((float) $sRow->price - $sAvgCost) * $sQty;
            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            if ($limit != -1) {

                $query->offset($start)
                    ->limit($limit);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Data
            |--------------------------------------------------------------------------
            */

            $stocks = $query->get();

            /*
            |--------------------------------------------------------------------------
            | Prepare Response
            |--------------------------------------------------------------------------
            */

            $data = [];

            foreach ($stocks as $stock) {

                $current_qty = (float) $stock->qty;

                $variantKey = $stock->variant_id ?? 0;

                /*
                |--------------------------------------------------------------------------
                | Average Cost
                |--------------------------------------------------------------------------
                */

                $averageCost =
                    $averageCostMap[$stock->id][$variantKey]
                    ?? (float) $stock->fallback_cost;

                /*
                |--------------------------------------------------------------------------
                | Calculations
                |--------------------------------------------------------------------------
                */

                $stock_value_cost = $current_qty * $averageCost;

                $stock_value_price = $current_qty * $stock->price;

                $profit = ($stock->price - $averageCost) * $current_qty;

                /*
                |--------------------------------------------------------------------------
                | Data Row
                |--------------------------------------------------------------------------
                */

                $data[] = [

                    'code' => $stock->code,

                    'name' => $stock->name,

                    'variant' => $stock->variant ?? 'N/A',

                    'category' => $stock->category ?? 'N/A',

                    'warehouse' => $stock->warehouse,

                    'cost' => round($averageCost, config('decimal')),

                    'price' => round($stock->price, config('decimal')),

                    'qty' => round($current_qty, config('decimal')),

                    'stock_cost' => round($stock_value_cost, config('decimal')),

                    'stock_price' => round($stock_value_price, config('decimal')),

                    'profit' => round($profit, config('decimal')),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Response (footer/summary use full-dataset totals)
            |--------------------------------------------------------------------------
            */

            return response()->json([

                "draw" => $draw,

                "recordsTotal" => $totalFiltered,

                "recordsFiltered" => $totalFiltered,

                "data" => $data,

                "footer" => [

                    "total_qty" => round($summary_total_qty, config('decimal')),

                    "total_cost_value" => round($summary_total_cost_value, config('decimal')),

                    "total_price_value" => round($summary_total_price_value, config('decimal')),

                    "total_profit" => round($summary_total_profit, config('decimal')),
                ],

                'summary' => [
                    'total_qty' => round($summary_total_qty, config('decimal')),
                    'closing_stock_cost' => round($summary_total_cost_value, config('decimal')),
                    'closing_stock_price' => round($summary_total_price_value, config('decimal')),
                    'potential_profit' => round($summary_total_profit, config('decimal')),
                    'profit_margin' => $summary_total_price_value > 0
                        ? round(($summary_total_profit / $summary_total_price_value) * 100, 2)
                        : 0,
                ]
            ]);

        }  catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }
    }

    private function reportCustomField($data, $custom_fields)
    {
        $custom_data = [];

        foreach ($custom_fields as $field_name => $type) {
            $lower_field_name = str_replace(" ", "_", strtolower($field_name));
            if ($type === 'number') {
                $custom_data[$lower_field_name] = $data->sum($lower_field_name);
            } else {
                $custom_data[$lower_field_name] = $data->pluck($lower_field_name)->filter()->values();
            }
        }

        return $custom_data;
    }

    public function challanReport(Request $request)
    {
        if($request->input('starting_date')) {
            $starting_date = $request->input('starting_date');
            $ending_date = $request->input('ending_date');
            $based_on = $request->input('based_on');
        }
        else {
            $starting_date = date("Y-m-"."01");
            $ending_date = date("Y-m-d");
            $based_on = 'created_at';
        }
        $challan_data = Challan::whereDate($based_on, '>=', $starting_date)
                            ->whereDate($based_on, '<=', $ending_date)
                            ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                            ->where('status', 'Close')
                            ->get();
        //return $challan_data;
        $index = 0;
        if ($request->ajax()) {
            return view('backend.report.partials.challan_table', compact('index', 'challan_data', 'based_on', 'starting_date', 'ending_date'))->render();
        }
        return view('backend.report.challan_report', compact('index', 'challan_data', 'based_on', 'starting_date', 'ending_date'));
    }

    public function saleReportChart(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = strtotime($request->end_date);
        $warehouse_id = $request->warehouse_id;
        $time_period = $request->time_period;
        $product_list = $request->product_list ?? '';

        if($time_period == 'monthly') {
            for($i = strtotime($start_date); $i <= $end_date; $i = strtotime('+1 month', $i)) {
                $date_points[] = date('Y-m-d', $i);
            }
        }
        else {
            for($i = strtotime('Saturday', strtotime($start_date)); $i <= $end_date; $i = strtotime('+1 week', $i)) {
                $date_points[] = date('Y-m-d', $i);
            }
        }
        $date_points[] = $request->end_date;
        //return $date_points;
        foreach ($date_points as $key => $date_point) {
            $q = DB::table('sales')
                ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->whereNull('sales.deleted_at')
                ->whereDate('sales.created_at', '>=', $start_date)
                ->whereDate('sales.created_at', '<=', $date_point)
                ->when($this->own_data, fn($q) => $q->where('sales.user_id', $this->current_user_id));
            if($warehouse_id)
                $qty = $q->where('sales.warehouse_id', $warehouse_id);
            if(isset($request->product_list)) {
                $product_ids = Product::whereIn('code', explode(",", trim($request->product_list)))->pluck('id')->toArray();
                $q->whereIn('product_sales.product_id', $product_ids);
            }
            $qty = $q->sum('product_sales.qty');
            $sold_qty[$key] = $qty;
            $start_date = $date_point;
        }
        $lims_warehouse_list = Warehouse::where('is_active', true)->select('id', 'name')->get();
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        if ($request->ajax()) {
            return view('backend.report.partials.sale_report_chart_table', compact('start_date', 'end_date', 'warehouse_id', 'time_period', 'product_list', 'sold_qty', 'date_points', 'lims_warehouse_list'))->render();
        }

        return view('backend.report.sale_report_chart', compact('start_date', 'end_date', 'warehouse_id', 'time_period', 'product_list', 'sold_qty', 'date_points', 'lims_warehouse_list'));
    }

    public function paymentReportByDate(Request $request)
    {
        $data = $request->all();

        $start_date = $data['start_date'] ?? null;
        $end_date   = $data['end_date'] ?? null;
        $payment_method = $data['payment_method'] ?? null;

        $start_date = is_array($start_date) ? reset($start_date) : $start_date;
        $end_date = is_array($end_date) ? reset($end_date) : $end_date;
        $payment_method = is_array($payment_method) ? reset($payment_method) : $payment_method;
        $query = Payment::query()
                    ->when($this->own_data, function ($q) {
                        $q->whereHas('sale', function ($s) {
                            $s->where('user_id', $this->current_user_id);
                        });
                    });
        $warehouseAccess = app(\App\Services\WarehouseAccessService::class);
        if ($warehouseAccess->isRestricted()) {
            $warehouseId = $warehouseAccess->warehouseId();
            $query->where(function ($q) use ($warehouseId) {
                $q->whereHas('sale', fn ($sale) => $sale->where('warehouse_id', $warehouseId))
                    ->orWhereHas('purchase', fn ($purchase) => $purchase->where('warehouse_id', $warehouseId))
                    ->orWhereHas('saleReturn', fn ($return) => $return->where('warehouse_id', $warehouseId))
                    ->orWhereHas('purchaseReturn', fn ($return) => $return->where('warehouse_id', $warehouseId));
            });
        }
        if ($start_date && $end_date) {
            $query->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date);
        }
        if (!empty($payment_method)) {
            $query->where('paying_method', $payment_method);
        }
        $lims_payment_data = $query->orderBy('created_at', 'desc')->get();

        if ($request->ajax()) {
            return view('backend.report.partials.payment_table', compact('lims_payment_data', 'start_date', 'end_date', 'payment_method'))->render();
        }

        return view(
            'backend.report.payment_report',
            compact('lims_payment_data', 'start_date', 'end_date', 'payment_method')
        );
    }

    public function warehouseReport(Request $request)
    {
        $warehouse_id = $request->input('warehouse_id');

        if($request->input('start_date')) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        }
        else {
            $start_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
            $end_date = date("Y-m-d");
        }
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.report.warehouse_report',compact('start_date', 'end_date', 'warehouse_id', 'lims_warehouse_list'));
    }

    public function warehouseSaleData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');
        $q = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.warehouse_id', $warehouse_id)
            ->whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->whereDate('sales.created_at', '>=', $request->input('start_date'))->whereDate('sales.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('sales.id', 'sales.reference_no', 'sales.grand_total', 'sales.paid_amount', 'sales.sale_status', 'sales.created_at', 'customers.name as customer', 'sales.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        { 
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['sales.created_at', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['sales.created_at', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $totalDue = $this->customerReportTotalDue($request);
        $totalDue = $this->customerReportTotalDue($request);
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['customer'] = $sale->customer;
                $product_sale_data = DB::table('sales')->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->join('products', 'product_sales.product_id', '=', 'products.id')
                                    ->whereNull('sales.deleted_at')
                                    ->where('sales.id', $sale->id)
                                    ->select('products.name as product_name', 'product_sales.qty', 'product_sales.sale_unit_id')
                                    ->get();
                foreach ($product_sale_data as $index => $product_sale) {
                    if($product_sale->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_sale->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($sale->paid_amount / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['due'] = number_format($this->customerReportSaleDue($sale), cache()->get('general_setting')->decimal);
                switch ($sale->sale_status) {
                    case 1: $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>'; break;
                    case 2: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>'; break;
                    case 3: $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>'; break;
                    case 4: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>'; break;
                    case 5: $nestedData['status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>'; break;
                    case 6: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>'; break;
                    default: $nestedData['status'] = '<div class="badge badge-secondary">' . __('db.Draft') . '</div>'; break;
                }
                $data[] = $nestedData;
            }
        }
        $totalDue = $this->customerReportTotalDue($request);
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "total_due"       => number_format($totalDue, cache()->get('general_setting')->decimal)
        );
        echo json_encode($json_data);
    }

    private function customerReportTotalDue(Request $request): float
    {
        $query = DB::table('sales')
            ->where('customer_id', $request->input('customer_id'))
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('sale_type', '!=', 'opening balance')
                    ->orWhereNull('sale_type');
            })
            ->whereDate('created_at', '>=', $request->input('start_date'))
            ->whereDate('created_at', '<=', $request->input('end_date'))
            ->select('id', 'grand_total', 'paid_amount', 'sale_status', 'exchange_rate');
        app(\App\Services\WarehouseAccessService::class)->scope($query, 'sales.warehouse_id');

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));
            $query->where(function ($q) use ($search, $searchDate) {
                $q->whereDate('created_at', $searchDate)
                    ->orWhere('created_at', 'LIKE', "%{$search}%")
                    ->orWhere('reference_no', 'LIKE', "%{$search}%");
            });
        }

        return $query->get()->sum(fn ($sale) => $this->customerReportSaleDue($sale));
    }

    public function warehousePurchaseData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');
        $q = DB::table('purchases')
            //->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->where('purchases.warehouse_id', $warehouse_id)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('purchase_type')
                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
            })
            ->whereDate('purchases.created_at', '>=', $request->input('start_date'))->whereDate('purchases.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('purchases.id', 'purchases.reference_no', 'purchases.supplier_id', 'purchases.grand_total', 'purchases.paid_amount', 'purchases.status', 'purchases.created_at', 'purchases.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $purchases =  $q->orwhere([
                                ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['purchases.created_at', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['purchases.created_at', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $purchases =  $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($purchases))
        {
            foreach ($purchases as $key => $purchase)
            {
                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at));
                $nestedData['reference_no'] = $purchase->reference_no;
                if($purchase->supplier_id) {
                    $supplier = DB::table('suppliers')->select('name')->where('id',$purchase->supplier_id)->first();
                    $nestedData['supplier'] = $supplier->name;
                }
                else
                    $nestedData['supplier'] = 'N/A';
                $product_purchase_data = DB::table('purchases')->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                                    ->join('products', 'product_purchases.product_id', '=', 'products.id')
                                    ->where('purchases.id', $purchase->id)
                                    ->whereNull('purchases.deleted_at')
                                    ->select('products.name as product_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id')
                                    ->get();
                foreach ($product_purchase_data as $index => $product_purchase) {
                    if($product_purchase->purchase_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_purchase->purchase_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($purchase->grand_total / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($purchase->paid_amount / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['balance'] = number_format(($purchase->grand_total - $purchase->paid_amount) / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                if($purchase->status == 1){
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Completed').'</div>';
                    $status = __('db.Completed');
                }
                elseif($purchase->status == 2){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                    $status = __('db.Pending');
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-warning">'.__('db.Draft').'</div>';
                    $status = __('db.Draft');
                }
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

    public function warehouseQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');
        $q = DB::table('quotations')
            ->join('customers', 'quotations.customer_id', '=', 'customers.id')
            ->leftJoin('suppliers', 'quotations.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->where('quotations.warehouse_id', $warehouse_id)
            ->whereDate('quotations.created_at', '>=', $request->input('start_date'))->whereDate('quotations.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.supplier_id', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'suppliers.name as supplier_name', 'customers.name as customer_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['customer'] = $quotation->customer_name;
                if($quotation->supplier_id) {
                    $nestedData['supplier'] = $quotation->supplier_name;
                }
                else
                    $nestedData['supplier'] = 'N/A';
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function warehouseReturnData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');
        $q = DB::table('returns')
            ->join('customers', 'returns.customer_id', '=', 'customers.id')
            ->leftJoin('billers', 'returns.biller_id', '=', 'billers.id')
            ->leftJoin(DB::raw('(select return_id, sum(amount) as refunded_amount from payments where return_id is not null and sale_id is not null group by return_id) as sale_return_refunds'), 'sale_return_refunds.return_id', '=', 'returns.id')
            ->where('returns.warehouse_id', $warehouse_id)
            ->whereDate('returns.created_at', '>=', $request->input('start_date'))->whereDate('returns.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'returns.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('returns.id', 'returns.reference_no', DB::raw('COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) as grand_total'), 'returns.exchange_rate', 'returns.created_at', 'customers.name as customer_name', 'billers.name as biller_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $returns = $q->get();
        }
        else
        { 
            $search = $request->input('search.value');
            $q = $q->whereDate('returns.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $returns =  $q->orwhere([
                                ['returns.reference_no', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['returns.created_at', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['returns.reference_no', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['returns.created_at', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $returns =  $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($returns))
        {
            foreach ($returns as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['customer'] = $sale->customer_name;
                $nestedData['biller'] = $sale->biller_name;
                $product_return_data = DB::table('returns')->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
                                    ->join('products', 'product_returns.product_id', '=', 'products.id')
                                    ->where('returns.id', $sale->id)
                                    ->select('products.name as product_name', 'product_returns.qty', 'product_returns.sale_unit_id')
                                    ->get();
                foreach ($product_return_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
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

    public function warehouseExpenseData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $warehouse_id = $request->input('warehouse_id');
        $q = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.warehouse_id', $warehouse_id)
            ->whereDate('expenses.created_at', '>=', $request->input('start_date'))->whereDate('expenses.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'expenses.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('expenses.id', 'expenses.reference_no', 'expenses.amount', 'expenses.created_at', 'expenses.note', 'expense_categories.name as category')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $expenses = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('expenses.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $expenses =  $q->orwhere([
                                ['expenses.reference_no', 'LIKE', "%{$search}%"],
                                ['expenses.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['expenses.created_at', 'LIKE', "%{$search}%"],
                                ['expenses.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['expenses.reference_no', 'LIKE', "%{$search}%"],
                                    ['expenses.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['expenses.created_at', 'LIKE', "%{$search}%"],
                                    ['expenses.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $expenses =  $q->orwhere('expenses.created_at', 'LIKE', "%{$search}%")->orwhere('expenses.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('expenses.created_at', 'LIKE', "%{$search}%")->orwhere('expenses.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($expenses))
        {
            foreach ($expenses as $key => $expense)
            {
                $nestedData['id'] = $expense->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($expense->created_at));
                $nestedData['reference_no'] = $expense->reference_no;
                $nestedData['category'] = $expense->category;
                $nestedData['amount'] = number_format($expense->amount, cache()->get('general_setting')->decimal);
                $nestedData['note'] = $expense->note;
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

    public function userReport(Request $request)
    {
        $data = $request->all();
        $user_id = $data['user_id'];
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $lims_user_list = User::where('is_active', true)->get();
        return view('backend.report.user_report', compact('user_id', 'start_date', 'end_date', 'lims_user_list'));
    }

    public function billerReport(Request $request)
    {
        $data = $request->all();
        $biller_id = $data['biller_id'];
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $lims_biller_list = Biller::where('is_active', true)->get();
        return view('backend.report.biller_report', compact('biller_id', 'start_date', 'end_date', 'lims_biller_list'));
    }

    public function billerSaleData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $biller_id = $request->input('biller_id');

        $q = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->where('sales.biller_id', $biller_id)
            ->whereDate('sales.created_at', '>=', $request->input('start_date'))->whereDate('sales.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'sales.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('sales.id', 'sales.reference_no', 'sales.grand_total', 'sales.paid_amount', 'sales.sale_status', 'sales.created_at', 'customers.name as customer', 'warehouses.name as warehouse', 'sales.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['sales.created_at', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['sales.created_at', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $totalDue = $this->customerReportTotalDue($request);
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['customer'] = $sale->customer;
                $nestedData['warehouse'] = $sale->warehouse;
                $product_sale_data = DB::table('sales')->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->join('products', 'product_sales.product_id', '=', 'products.id')
                                    ->whereNull('sales.deleted_at')
                                    ->where('sales.id', $sale->id)
                                    ->select('products.name as product_name', 'product_sales.qty', 'product_sales.sale_unit_id')
                                    ->get();
                $nestedData['product'] = '';
                foreach ($product_sale_data as $index => $product_sale) {
                    if($product_sale->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_sale->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($sale->paid_amount / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['due'] = number_format($this->customerReportSaleDue($sale), cache()->get('general_setting')->decimal);
                switch ($sale->sale_status) {
                    case 1: $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>'; break;
                    case 2: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>'; break;
                    case 3: $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>'; break;
                    case 4: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>'; break;
                    case 5: $nestedData['status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>'; break;
                    case 6: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>'; break;
                    default: $nestedData['status'] = '<div class="badge badge-secondary">' . __('db.Draft') . '</div>'; break;
                }
                $data[] = $nestedData;
            }
        }
        $totalDue = $this->customerReportTotalDue($request);
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        echo json_encode($json_data);
    }

    public function billerQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $biller_id = $request->input('biller_id');
        $q = DB::table('quotations')
            ->join('customers', 'quotations.customer_id', '=', 'customers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->where('quotations.biller_id', $biller_id)
            ->whereDate('quotations.created_at', '>=', $request->input('start_date'))->whereDate('quotations.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'warehouses.name as warehouse_name', 'customers.name as customer_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['customer'] = $quotation->customer_name;
                $nestedData['warehouse'] = $quotation->warehouse_name;
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function billerPaymentData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $biller_id = $request->input('biller_id');
        $q = DB::table('payments')
           ->join('sales', 'payments.sale_id', '=', 'sales.id')
           ->whereNull('sales.deleted_at')
           ->whereNull('payments.return_id')
           ->where('sales.biller_Id',$biller_id)
           ->whereDate('payments.created_at', '>=', $request->input('start_date'))->whereDate('payments.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'payments.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payments.*')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payments = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payments.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payments =  $q->orwhere([
                                ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payments.created_at', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payments.created_at', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payments =  $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payments))
        {
            foreach ($payments as $key => $payment)
            {
                $nestedData['id'] = $payment->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payment->created_at));
                $nestedData['reference_no'] = $payment->payment_reference;
                $nestedData['amount'] = number_format($payment->amount, cache()->get('general_setting')->decimal);
                $nestedData['paying_method'] = $payment->paying_method;
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

    public function userSaleData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->where('sales.user_id', $user_id)
            ->whereDate('sales.created_at', '>=', $request->input('start_date'))->whereDate('sales.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'sales.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('sales.id', 'sales.reference_no', 'sales.grand_total', 'sales.paid_amount', 'sales.sale_status', 'sales.created_at', 'customers.name as customer', 'warehouses.name as warehouse', 'sales.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['sales.created_at', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['sales.created_at', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['customer'] = $sale->customer;
                $nestedData['warehouse'] = $sale->warehouse;
                $product_sale_data = DB::table('sales')->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->join('products', 'product_sales.product_id', '=', 'products.id')
                                    ->where('sales.id', $sale->id)
                                    ->whereNull('sales.deleted_at')
                                    ->select('products.name as product_name', 'product_sales.qty', 'product_sales.sale_unit_id')
                                    ->get();
                foreach ($product_sale_data as $index => $product_sale) {
                    if($product_sale->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_sale->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($sale->paid_amount / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['due'] = number_format($this->customerReportSaleDue($sale), cache()->get('general_setting')->decimal);
                switch ($sale->sale_status) {
                    case 1: $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>'; break;
                    case 2: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>'; break;
                    case 3: $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>'; break;
                    case 4: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>'; break;
                    case 5: $nestedData['status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>'; break;
                    case 6: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>'; break;
                    default: $nestedData['status'] = '<div class="badge badge-secondary">' . __('db.Draft') . '</div>'; break;
                }
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

    public function userPurchaseData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('purchases')
            ->join('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
            ->where('purchases.user_id', $user_id)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('purchase_type')
                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
            })
            ->whereDate('purchases.created_at', '>=', $request->input('start_date'))->whereDate('purchases.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'purchases.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('purchases.id', 'purchases.reference_no', 'purchases.supplier_id', 'purchases.grand_total', 'purchases.paid_amount', 'purchases.status', 'purchases.created_at', 'warehouses.name as warehouse', 'purchases.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $purchases =  $q->orwhere([
                                ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['purchases.created_at', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['purchases.created_at', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $purchases =  $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($purchases))
        {
            foreach ($purchases as $key => $purchase)
            {
                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at));
                $nestedData['reference_no'] = $purchase->reference_no;
                $nestedData['warehouse'] = $purchase->warehouse;
                if($purchase->supplier_id) {
                    $supplier = DB::table('suppliers')->select('name')->where('id',$purchase->supplier_id)->first();
                    $nestedData['supplier'] = $supplier->name;
                }
                else
                    $nestedData['supplier'] = 'N/A';
                $product_purchase_data = DB::table('purchases')->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                                    ->join('products', 'product_purchases.product_id', '=', 'products.id')
                                    ->where('purchases.id', $purchase->id)
                                    ->whereNull('purchases.deleted_at')
                                    ->select('products.name as product_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id')
                                    ->get();
                foreach ($product_purchase_data as $index => $product_purchase) {
                    if($product_purchase->purchase_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_purchase->purchase_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($purchase->grand_total / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($purchase->paid_amount / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['balance'] = number_format(($purchase->grand_total - $purchase->paid_amount) / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                if($purchase->status == 1){
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Completed').'</div>';
                    $status = __('db.Completed');
                }
                elseif($purchase->status == 2){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                    $status = __('db.Pending');
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-warning">'.__('db.Draft').'</div>';
                    $status = __('db.Draft');
                }
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

    public function userQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('quotations')
            ->join('customers', 'quotations.customer_id', '=', 'customers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->where('quotations.user_id', $user_id)
            ->whereDate('quotations.created_at', '>=', $request->input('start_date'))->whereDate('quotations.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'warehouses.name as warehouse_name', 'customers.name as customer_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['customer'] = $quotation->customer_name;
                $nestedData['warehouse'] = $quotation->warehouse_name;
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function userTransferData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('transfers')
           ->join('warehouses as fromWarehouse', 'transfers.from_warehouse_id', '=', 'fromWarehouse.id')
           ->join('warehouses as toWarehouse', 'transfers.to_warehouse_id', '=', 'toWarehouse.id')
           ->where('transfers.user_id', $user_id)
           ->whereDate('transfers.created_at', '>=', $request->input('start_date'))->whereDate('transfers.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'transfers.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('transfers.id', 'transfers.status', 'transfers.created_at', 'transfers.reference_no', 'transfers.grand_total', 'fromWarehouse.name as fromWarehouse', 'toWarehouse.name as toWarehouse')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $transfers = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('transfers.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $transfers =  $q->orwhere([
                                ['transfers.reference_no', 'LIKE', "%{$search}%"],
                                ['transfers.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['transfers.created_at', 'LIKE', "%{$search}%"],
                                ['transfers.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['transfers.reference_no', 'LIKE', "%{$search}%"],
                                    ['transfers.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['transfers.created_at', 'LIKE', "%{$search}%"],
                                    ['transfers.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $transfers =  $q->orwhere('transfers.created_at', 'LIKE', "%{$search}%")->orwhere('transfers.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('transfers.created_at', 'LIKE', "%{$search}%")->orwhere('transfers.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($transfers))
        {
            foreach ($transfers as $key => $transfer)
            {
                $nestedData['id'] = $transfer->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($transfer->created_at));
                $nestedData['reference_no'] = $transfer->reference_no;
                $nestedData['fromWarehouse'] = $transfer->fromWarehouse;
                $nestedData['toWarehouse'] = $transfer->toWarehouse;
                $product_transfer_data = DB::table('product_transfer')
                                    ->where('transfer_id', $transfer->id)
                                    ->get();
                foreach ($product_transfer_data as $index => $product_transfer) {
                    $product = DB::table('products')->find($product_transfer->product_id);
                    if($product_transfer->variant_id) {
                        $variant = DB::table('variants')->find($product_transfer->variant_id);
                        $product->name .= ' ['.$variant->name.']';
                    }
                    $unit = DB::table('units')->find($product_transfer->purchase_unit_id);
                    if($index){
                        if($unit){
                            $nestedData['product'] .= $product->name.' ('.$product_transfer->qty.' '.$unit->unit_code.')';
                        }else{
                            $nestedData['product'] .= $product->name.' ('.$product_transfer->qty.')';
                        }
                    }else{
                        if($unit){
                            $nestedData['product'] = $product->name.' ('.$product_transfer->qty.' '.$unit->unit_code.')';
                        }else{
                            $nestedData['product'] = $product->name.' ('.$product_transfer->qty.')';
                        }
                    }
                }
                $nestedData['grandTotal'] = number_format($transfer->grand_total, cache()->get('general_setting')->decimal);
                if($transfer->status == 1){
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Completed').'</div>';
                }
                elseif($transfer->status == 2) {
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function userPaymentData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('payments')
           ->where('payments.user_id', $user_id)
           ->whereDate('payments.created_at', '>=', $request->input('start_date'))->whereDate('payments.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'payments.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payments.*')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payments = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payments.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payments =  $q->orwhere([
                                ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payments.created_at', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payments.created_at', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payments =  $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payments))
        {
            foreach ($payments as $key => $payment)
            {
                $nestedData['id'] = $payment->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payment->created_at));
                $nestedData['reference_no'] = $payment->payment_reference;
                $nestedData['amount'] = number_format($payment->amount, cache()->get('general_setting')->decimal);
                $nestedData['paying_method'] = $payment->paying_method;
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

    public function userPayrollData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('payrolls')
           ->join('employees', 'payrolls.employee_id', '=', 'employees.id')
           ->where('payrolls.user_id', $user_id)
           ->whereDate('payrolls.created_at', '>=', $request->input('start_date'))->whereDate('payrolls.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'payrolls.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payrolls.id', 'payrolls.created_at', 'payrolls.reference_no', 'payrolls.amount', 'payrolls.paying_method', 'employees.name as employee')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payrolls = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payrolls.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payrolls =  $q->orwhere([
                                ['payrolls.reference_no', 'LIKE', "%{$search}%"],
                                ['payrolls.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payrolls.created_at', 'LIKE', "%{$search}%"],
                                ['payrolls.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payrolls.reference_no', 'LIKE', "%{$search}%"],
                                    ['payrolls.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payrolls.created_at', 'LIKE', "%{$search}%"],
                                    ['payrolls.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payrolls =  $q->orwhere('payrolls.created_at', 'LIKE', "%{$search}%")->orwhere('payrolls.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payrolls.created_at', 'LIKE', "%{$search}%")->orwhere('payrolls.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payrolls))
        {
            foreach ($payrolls as $key => $payroll)
            {
                $nestedData['id'] = $payroll->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payroll->created_at));
                $nestedData['reference_no'] = $payroll->reference_no;
                $nestedData['employee'] = $payroll->employee;
                $nestedData['amount'] = number_format($payroll->amount, cache()->get('general_setting')->decimal);
                if($payroll->paying_method == 0)
                    $nestedData['method'] = 'Cash';
                elseif($payroll->paying_method == 1)
                    $nestedData['method'] = 'Cheque';
                else
                    $nestedData['method'] = 'Credit Card';
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

    public function userExpenseData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $user_id = $request->input('user_id');
        $q = DB::table('expenses')
            ->join('warehouses', 'expenses.warehouse_id', '=', 'warehouses.id')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.user_id', $user_id)
            ->whereDate('expenses.created_at', '>=', $request->input('start_date'))->whereDate('expenses.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'expenses.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('expenses.id', 'expenses.reference_no', 'expenses.amount', 'expenses.created_at', 'expenses.note', 'expense_categories.name as category', 'warehouses.name as warehouse')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $expenses = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('expenses.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $expenses =  $q->orwhere([
                                ['expenses.reference_no', 'LIKE', "%{$search}%"],
                                ['expenses.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['expenses.created_at', 'LIKE', "%{$search}%"],
                                ['expenses.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['expenses.reference_no', 'LIKE', "%{$search}%"],
                                    ['expenses.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['expenses.created_at', 'LIKE', "%{$search}%"],
                                    ['expenses.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $expenses =  $q->orwhere('expenses.created_at', 'LIKE', "%{$search}%")->orwhere('expenses.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('expenses.created_at', 'LIKE', "%{$search}%")->orwhere('expenses.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($expenses))
        {
            foreach ($expenses as $key => $expense)
            {
                $nestedData['id'] = $expense->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($expense->created_at));
                $nestedData['reference_no'] = $expense->reference_no;
                $nestedData['warehouse'] = $expense->warehouse;
                $nestedData['category'] = $expense->category;
                $nestedData['amount'] = number_format($expense->amount, cache()->get('general_setting')->decimal);
                $nestedData['note'] = $expense->note;
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

    public function customerReport(Request $request)
    {
        $customer_id = $request->input('customer_id');
        if($request->input('start_date')) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        }
        else {
            $start_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
            $end_date = date("Y-m-d");
        }
        $lims_customer_list = Customer::where('is_active', true)->get();
        return view('backend.report.customer_report',compact('start_date', 'end_date', 'customer_id', 'lims_customer_list'));
    }

    public function customerSaleData(Request $request, ProfitabilityService $profitabilityService)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_id = $request->input('customer_id');
        $q = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->where('sales.customer_id', $customer_id)
            ->whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->whereDate('sales.created_at', '>=', $request->input('start_date'))->whereDate('sales.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'sales.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('sales.id', 'sales.reference_no', 'sales.total_price', 'sales.grand_total', 'sales.paid_amount', 'sales.sale_status', 'sales.created_at', 'warehouses.name as warehouse_name', 'sales.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['sales.created_at', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['sales.created_at', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $product_sale_data = DB::table('sales')->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->join('products', 'product_sales.product_id', '=', 'products.id')
                                    ->where('sales.id', $sale->id)
                                    ->whereNull('sales.deleted_at')
                                    ->select('products.name as product_name', 'product_sales.qty', 'product_sales.sale_unit_id')
                                    ->get();
                foreach ($product_sale_data as $index => $product_sale) {
                    if($product_sale->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_sale->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                // Use the same canonical, historical cost resolver as Profitability/P&L.
                // This preserves IMEI/serial-specific purchase cost and reverses the
                // original specific cost when a serialized unit is returned.
                $nestedData['total_cost'] = number_format(
                    $profitabilityService->saleNetCost((int) $sale->id),
                    cache()->get('general_setting')->decimal
                );
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['returned_amount'] = number_format($this->customerReportReturnedAmount((int) $sale->id), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($sale->paid_amount / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['due'] = number_format($this->customerReportSaleDue($sale), cache()->get('general_setting')->decimal);
                switch ($sale->sale_status) {
                    case 1: $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>'; break;
                    case 2: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>'; break;
                    case 3: $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>'; break;
                    case 4: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>'; break;
                    case 5: $nestedData['status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>'; break;
                    case 6: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>'; break;
                    default: $nestedData['status'] = '<div class="badge badge-secondary">' . __('db.Draft') . '</div>'; break;
                }
                $data[] = $nestedData;
            }
        }
        $totalDue = $this->customerReportTotalDue($request);
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "total_due"       => number_format($totalDue, cache()->get('general_setting')->decimal)
        );
        echo json_encode($json_data);
    }

    private function customerReportSaleDue(object $sale): float
    {
        $exchangeRate = $sale->exchange_rate ?: 1;
        $grandTotal = (float) $sale->grand_total / $exchangeRate;
        $paidAmount = (float) $sale->paid_amount / $exchangeRate;
        $returnedAmount = $this->customerReportReturnedAmount((int) $sale->id);

        return $grandTotal - $returnedAmount - $paidAmount;
    }

    private function customerReportReturnedAmount(int $saleId): float
    {
        return (float) DB::table('returns')
            ->leftJoin(DB::raw('(select return_id, sum(amount) as refunded_amount from payments where return_id is not null and sale_id is not null group by return_id) as sale_return_refunds'), 'sale_return_refunds.return_id', '=', 'returns.id')
            ->where('sale_id', $saleId)
            ->sum(DB::raw('COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) / COALESCE(NULLIF(returns.exchange_rate, 0), 1)'));
    }

    public function customerPaymentData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_id = $request->input('customer_id');
        $q = DB::table('payments')
           ->join('sales', 'payments.sale_id', '=', 'sales.id')
           ->join('customers', 'customers.id', '=', 'sales.customer_id')
           ->where('sales.customer_id', $customer_id)
           ->whereNull('sales.deleted_at')
           ->whereNull('payments.return_id')
           ->whereDate('payments.created_at', '>=', $request->input('start_date'))->whereDate('payments.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'payments.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payments.*', 'sales.reference_no as sale_reference')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payments = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payments.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payments =  $q->orwhere([
                                ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payments.created_at', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payments.created_at', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payments =  $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payments))
        {
            foreach ($payments as $key => $payment)
            {
                $nestedData['id'] = $payment->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payment->created_at));
                $nestedData['reference_no'] = $payment->payment_reference;
                $nestedData['sale_reference'] = $payment->sale_reference;
                $nestedData['amount'] = number_format($payment->amount, cache()->get('general_setting')->decimal);
                $nestedData['paying_method'] = $payment->paying_method;
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

    public function customerQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_id = $request->input('customer_id');
        $q = DB::table('quotations')
            ->join('customers', 'quotations.customer_id', '=', 'customers.id')
            ->leftJoin('suppliers', 'quotations.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->where('quotations.customer_id', $customer_id)
            ->whereDate('quotations.created_at', '>=', $request->input('start_date'))->whereDate('quotations.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.supplier_id', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'suppliers.name as supplier_name', 'warehouses.name as warehouse_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['warehouse'] = $quotation->warehouse_name;
                if($quotation->supplier_id) {
                    $nestedData['supplier'] = $quotation->supplier_name;
                }
                else
                    $nestedData['supplier'] = 'N/A';
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function customerReturnData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_id = $request->input('customer_id');
        $q = DB::table('returns')
            ->join('customers', 'returns.customer_id', '=', 'customers.id')
            ->join('warehouses', 'returns.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('billers', 'returns.biller_id', '=', 'billers.id')
            ->leftJoin(DB::raw('(select return_id, sum(amount) as refunded_amount from payments where return_id is not null and sale_id is not null group by return_id) as sale_return_refunds'), 'sale_return_refunds.return_id', '=', 'returns.id')
            ->where('returns.customer_id', $customer_id)
            ->whereDate('returns.created_at', '>=', $request->input('start_date'))->whereDate('returns.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'returns.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('returns.id', 'returns.reference_no', DB::raw('COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) as grand_total'), 'returns.exchange_rate', 'returns.created_at', 'warehouses.name as warehouse_name', 'billers.name as biller_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $returns = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('returns.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $returns =  $q->orwhere([
                                ['returns.reference_no', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['returns.created_at', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['returns.reference_no', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['returns.created_at', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $returns =  $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($returns))
        {
            foreach ($returns as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $nestedData['biller'] = $sale->biller_name;
                $product_return_data = DB::table('returns')->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
                                    ->join('products', 'product_returns.product_id', '=', 'products.id')
                                    ->where('returns.id', $sale->id)
                                    ->select('products.name as product_name', 'product_returns.qty', 'product_returns.sale_unit_id')
                                    ->get();
                foreach ($product_return_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
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

    public function customerGroupReport(Request $request)
    {
        $customer_group_id = $request->input('customer_group_id');
        if($request->input('starting_date')) {
            $starting_date = $request->input('starting_date');
            $ending_date = $request->input('ending_date');
        }
        else {
            $starting_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
            $ending_date = date("Y-m-d");
        }
        $lims_customer_group_list = CustomerGroup::where('is_active', true)->get();
        return view('backend.report.customer_group_report',compact('starting_date', 'ending_date', 'customer_group_id', 'lims_customer_group_list'));
    }

    public function customerGroupSaleData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_group_id = $request->input('customer_group_id');
        $customer_ids = Customer::where('customer_group_id', $customer_group_id)->pluck('id');
        $q = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->whereIn('sales.customer_id', $customer_ids)
            ->whereDate('sales.created_at', '>=', $request->input('starting_date'))->whereDate('sales.created_at', '<=', $request->input('ending_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'sales.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('sales.id', 'sales.reference_no', 'sales.grand_total', 'sales.paid_amount', 'sales.sale_status', 'sales.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name', 'sales.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $sales = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('sales.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $sales =  $q->orwhere([
                                ['sales.reference_no', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['sales.created_at', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->orWhere([
                                ['customers.name', 'LIKE', "%{$search}%"],
                                ['sales.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['sales.reference_no', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['sales.created_at', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->orWhere([
                                    ['customers.name', 'LIKE', "%{$search}%"],
                                    ['sales.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $sales =  $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('sales.created_at', 'LIKE', "%{$search}%")->orwhere('sales.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($sales))
        {
            foreach ($sales as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $nestedData['customer'] = $sale->customer_name.' ['.($sale->customer_number).']';
                $product_sale_data = DB::table('sales')->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                                    ->join('products', 'product_sales.product_id', '=', 'products.id')
                                    ->where('sales.id', $sale->id)
                                    ->whereNull('sales.deleted_at')
                                    ->select('products.name as product_name', 'product_sales.qty', 'product_sales.sale_unit_id')
                                    ->get();
                foreach ($product_sale_data as $index => $product_sale) {
                    if($product_sale->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_sale->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_sale->product_name.' ('.number_format($product_sale->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['returned_amount'] = number_format($this->customerReportReturnedAmount((int) $sale->id), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($sale->paid_amount / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['due'] = number_format($this->customerReportSaleDue($sale), cache()->get('general_setting')->decimal);
                switch ($sale->sale_status) {
                    case 1: $nestedData['status'] = '<div class="badge badge-success">' . __('db.Completed') . '</div>'; break;
                    case 2: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Pending') . '</div>'; break;
                    case 3: $nestedData['status'] = '<div class="badge badge-warning">' . __('db.Draft') . '</div>'; break;
                    case 4: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.Returned') . '</div>'; break;
                    case 5: $nestedData['status'] = '<div class="badge badge-info">' . __('db.Processing') . '</div>'; break;
                    case 6: $nestedData['status'] = '<div class="badge badge-danger">' . __('db.cooked') . '</div>'; break;
                    default: $nestedData['status'] = '<div class="badge badge-secondary">' . __('db.Draft') . '</div>'; break;
                }
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

    public function customerGroupPaymentData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_group_id = $request->input('customer_group_id');
        $customer_ids = Customer::where('customer_group_id', $customer_group_id)->pluck('id');
        $q = DB::table('payments')
           ->join('sales', 'payments.sale_id', '=', 'sales.id')
           ->join('customers', 'customers.id', '=', 'sales.customer_id')
           ->whereNull('sales.deleted_at')
           ->whereNull('payments.return_id')
           ->whereIn('sales.customer_id', $customer_ids)
           ->whereDate('payments.created_at', '>=', $request->input('starting_date'))->whereDate('payments.created_at', '<=', $request->input('ending_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'sales.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payments.*', 'sales.reference_no as sale_reference', 'customers.name as customer_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payments = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payments.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payments =  $q->orwhere([
                                ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payments.created_at', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orWhere([
                                ['customers.name', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payments.created_at', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orWhere([
                                    ['customers.name', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payments =  $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payments))
        {
            foreach ($payments as $key => $payment)
            {
                $nestedData['id'] = $payment->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payment->created_at));
                $nestedData['reference_no'] = $payment->payment_reference;
                $nestedData['sale_reference'] = $payment->sale_reference;
                $nestedData['customer'] = $payment->customer_name;
                $nestedData['amount'] = number_format($payment->amount, cache()->get('general_setting')->decimal);
                $nestedData['paying_method'] = $payment->paying_method;
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

    public function customerGroupQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_group_id = $request->input('customer_group_id');
        $customer_ids = Customer::where('customer_group_id', $customer_group_id)->pluck('id');
        $q = DB::table('quotations')
            ->join('customers', 'quotations.customer_id', '=', 'customers.id')
            ->leftJoin('suppliers', 'quotations.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->whereIn('quotations.customer_id', $customer_ids)
            ->whereDate('quotations.created_at', '>=', $request->input('starting_date'))->whereDate('quotations.created_at', '<=', $request->input('ending_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.supplier_id', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'suppliers.name as supplier_name', 'warehouses.name as warehouse_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orWhere([
                                ['customers.name', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orWhere([
                                    ['customers.name', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['warehouse'] = $quotation->warehouse_name;
                $nestedData['customer'] = $quotation->customer_name.' ['.($quotation->customer_number).']';
                if($quotation->supplier_id) {
                    $nestedData['supplier'] = $quotation->supplier_name;
                }
                else
                    $nestedData['supplier'] = 'N/A';
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function customerGroupReturnData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $customer_group_id = $request->input('customer_group_id');
        $customer_ids = Customer::where('customer_group_id', $customer_group_id)->pluck('id');
        $q = DB::table('returns')
            ->join('customers', 'returns.customer_id', '=', 'customers.id')
            ->join('warehouses', 'returns.warehouse_id', '=', 'warehouses.id')
            ->leftJoin(DB::raw('(select return_id, sum(amount) as refunded_amount from payments where return_id is not null and sale_id is not null group by return_id) as sale_return_refunds'), 'sale_return_refunds.return_id', '=', 'returns.id')
            ->whereIn('returns.customer_id', $customer_ids)
            ->whereDate('returns.created_at', '>=', $request->input('starting_date'))->whereDate('returns.created_at', '<=', $request->input('ending_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'returns.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('returns.id', 'returns.reference_no', DB::raw('COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) as grand_total'), 'returns.exchange_rate', 'returns.created_at', 'customers.name as customer_name', 'customers.phone_number as customer_number', 'warehouses.name as warehouse_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $returns = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('returns.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $returns =  $q->orwhere([
                                ['returns.reference_no', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['returns.created_at', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->orWhere([
                                ['customers.name', 'LIKE', "%{$search}%"],
                                ['returns.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['returns.reference_no', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['returns.created_at', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->orWhere([
                                    ['customers.name', 'LIKE', "%{$search}%"],
                                    ['returns.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $returns =  $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('returns.created_at', 'LIKE', "%{$search}%")->orwhere('returns.reference_no', 'LIKE', "%{$search}%")->orWhere('customers.name', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($returns))
        {
            foreach ($returns as $key => $sale)
            {
                $nestedData['id'] = $sale->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($sale->created_at));
                $nestedData['reference_no'] = $sale->reference_no;
                $nestedData['warehouse'] = $sale->warehouse_name;
                $nestedData['customer'] = $sale->customer_name.' ['.($sale->customer_number).']';
                $product_return_data = DB::table('returns')->join('product_returns', 'returns.id', '=', 'product_returns.return_id')
                                    ->join('products', 'product_returns.product_id', '=', 'products.id')
                                    ->where('returns.id', $sale->id)
                                    ->select('products.name as product_name', 'product_returns.qty', 'product_returns.sale_unit_id')
                                    ->get();
                foreach ($product_return_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($sale->grand_total / ($sale->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
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

    public function supplierReport(Request $request)
    {
        $supplier_id = $request->input('supplier_id');
        if($request->input('start_date')) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        }
        else {
            $start_date = date("Y-m-d", strtotime(date('Y-m-d', strtotime('-1 year', strtotime(date('Y-m-d') )))));
            $end_date = date("Y-m-d");
        }
        $lims_supplier_list = Supplier::where('is_active', true)->get();
        return view('backend.report.supplier_report',compact('start_date', 'end_date', 'supplier_id', 'lims_supplier_list'));
    }

    private function supplierReportReturnedAmount(int $purchaseId): float
    {
        return (float) DB::table('return_purchases')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where('purchase_id', $purchaseId)
            ->sum(DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total)'));
    }

    private function supplierReportPurchaseDue(object $purchase): float
    {
        $exchangeRate = $purchase->exchange_rate ?: 1;
        $grandTotal = $purchase->grand_total / $exchangeRate;
        $paidAmount = ($purchase->paid_amount ?? 0) / $exchangeRate;
        $returnedAmount = $this->supplierReportReturnedAmount($purchase->id);

        return $grandTotal - $returnedAmount - $paidAmount;
    }

    private function supplierReportTotalDue(Request $request): float
    {
        $q = DB::table('purchases')
            ->leftJoin(DB::raw('(select rp.purchase_id, sum(coalesce(pr.refunded_amount, rp.grand_total)) as returned_amount from return_purchases rp left join (select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) pr on pr.purchase_return_id = rp.id group by rp.purchase_id) as purchase_returns'), 'purchase_returns.purchase_id', '=', 'purchases.id')
            ->where('purchases.supplier_id', $request->input('supplier_id'))
            ->whereNull('purchases.deleted_at')
            ->where(function ($q) {
                $q->whereNull('purchases.purchase_type')
                    ->orWhereNotIn('purchases.purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
            })
            ->whereDate('purchases.created_at', '>=', $request->input('start_date'))
            ->whereDate('purchases.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'purchases.warehouse_id');

        if(!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));
            $q->where(function ($q) use ($search, $searchDate) {
                $q->whereDate('purchases.created_at', $searchDate)
                    ->orWhere('purchases.created_at', 'LIKE', "%{$search}%")
                    ->orWhere('purchases.reference_no', 'LIKE', "%{$search}%");
            });
        }

        return (float) $q->sum(DB::raw('((purchases.grand_total - COALESCE(purchases.paid_amount, 0)) / COALESCE(NULLIF(purchases.exchange_rate, 0), 1)) - COALESCE(purchase_returns.returned_amount, 0)'));
    }

    public function supplierPurchaseData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $supplier_id = $request->input('supplier_id');
        $q = DB::table('purchases')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
            ->where('purchases.supplier_id', $supplier_id)
            ->whereNull('purchases.deleted_at')
            ->where(function ($q) {
                $q->whereNull('purchases.purchase_type')
                ->orWhereNotIn('purchases.purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
            })
            ->whereDate('purchases.created_at', '>=', $request->input('start_date'))->whereDate('purchases.created_at', '<=', $request->input('end_date'));
        app(\App\Services\WarehouseAccessService::class)->scope($q, 'purchases.warehouse_id');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('purchases.id', 'purchases.reference_no', 'purchases.grand_total', 'purchases.paid_amount', 'purchases.status', 'purchases.created_at', 'warehouses.name as warehouse_name', 'purchases.exchange_rate')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $purchases =  $q->orwhere([
                                ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['purchases.created_at', 'LIKE', "%{$search}%"],
                                ['purchases.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['purchases.created_at', 'LIKE', "%{$search}%"],
                                    ['purchases.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $purchases =  $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('purchases.created_at', 'LIKE', "%{$search}%")->orwhere('purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($purchases))
        {
            foreach ($purchases as $key => $purchase)
            {
                $nestedData['id'] = $purchase->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($purchase->created_at));
                $nestedData['reference_no'] = $purchase->reference_no;
                $nestedData['warehouse'] = $purchase->warehouse_name;
                $product_purchase_data = DB::table('purchases')->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                                    ->join('products', 'product_purchases.product_id', '=', 'products.id')
                                    ->where('purchases.id', $purchase->id)
                                    ->whereNull('purchases.deleted_at')
                                    ->select('products.name as product_name', 'product_purchases.qty', 'product_purchases.purchase_unit_id')
                                    ->get();
                foreach ($product_purchase_data as $index => $product_purchase) {
                    if($product_purchase->purchase_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_purchase->purchase_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_purchase->product_name.' ('.number_format($product_purchase->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($purchase->grand_total / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['returned_amount'] = number_format($this->supplierReportReturnedAmount($purchase->id), cache()->get('general_setting')->decimal);
                $nestedData['paid'] = number_format($purchase->paid_amount / ($purchase->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
                $nestedData['balance'] = number_format($this->supplierReportPurchaseDue($purchase), cache()->get('general_setting')->decimal);
                if($purchase->status == 1){
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Completed').'</div>';
                    $status = __('db.Completed');
                }
                elseif($purchase->status == 2){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                    $status = __('db.Pending');
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-warning">'.__('db.Draft').'</div>';
                    $status = __('db.Draft');
                }
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "total_due"       => number_format($this->supplierReportTotalDue($request), cache()->get('general_setting')->decimal)
        );
        echo json_encode($json_data);
    }

    public function supplierPaymentData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $supplier_id = $request->input('supplier_id');
        $q = DB::table('payments')
           ->join('purchases', 'payments.purchase_id', '=', 'purchases.id')
           ->where('purchases.supplier_id', $supplier_id)
           ->whereNull('purchases.deleted_at')
           ->whereDate('payments.created_at', '>=', $request->input('start_date'))->whereDate('payments.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'payments.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('payments.*', 'purchases.reference_no as purchase_reference')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $payments = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('payments.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $payments =  $q->orwhere([
                                ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['payments.created_at', 'LIKE', "%{$search}%"],
                                ['payments.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['payments.payment_reference', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['payments.created_at', 'LIKE', "%{$search}%"],
                                    ['payments.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $payments =  $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('payments.created_at', 'LIKE', "%{$search}%")->orwhere('payments.payment_reference', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($payments))
        {
            foreach ($payments as $key => $payment)
            {
                $nestedData['id'] = $payment->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($payment->created_at));
                $nestedData['reference_no'] = $payment->payment_reference;
                $nestedData['purchase_reference'] = $payment->purchase_reference;
                $nestedData['amount'] = number_format($payment->amount, cache()->get('general_setting')->decimal);
                $nestedData['paying_method'] = $payment->paying_method;
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

    public function supplierReturnData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $supplier_id = $request->input('supplier_id');
        $q = DB::table('return_purchases')
            ->join('suppliers', 'return_purchases.supplier_id', '=', 'suppliers.id')
            ->join('warehouses', 'return_purchases.warehouse_id', '=', 'warehouses.id')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where('return_purchases.supplier_id', $supplier_id)
            ->whereDate('return_purchases.created_at', '>=', $request->input('start_date'))
            ->whereDate('return_purchases.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'return_purchases.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('return_purchases.id', 'return_purchases.reference_no', DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total) as grand_total'), 'return_purchases.exchange_rate', 'return_purchases.created_at', 'warehouses.name as warehouse_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $return_purchases = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('return_purchases.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $return_purchases =  $q->orwhere([
                                ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                                ['return_purchases.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['return_purchases.created_at', 'LIKE', "%{$search}%"],
                                ['return_purchases.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['return_purchases.reference_no', 'LIKE', "%{$search}%"],
                                    ['return_purchases.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['return_purchases.created_at', 'LIKE', "%{$search}%"],
                                    ['return_purchases.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $return_purchases =  $q->orwhere('return_purchases.created_at', 'LIKE', "%{$search}%")->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('return_purchases.created_at', 'LIKE', "%{$search}%")->orwhere('return_purchases.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($return_purchases))
        {
            foreach ($return_purchases as $key => $return)
            {
                $nestedData['id'] = $return->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($return->created_at));
                $nestedData['reference_no'] = $return->reference_no;
                $nestedData['warehouse'] = $return->warehouse_name;
                $product_return_data = DB::table('return_purchases')->join('purchase_product_return', 'return_purchases.id', '=', 'purchase_product_return.return_id')
                                    ->join('products', 'purchase_product_return.product_id', '=', 'products.id')
                                    ->where('return_purchases.id', $return->id)
                                    ->select('products.name as product_name', 'purchase_product_return.qty', 'purchase_product_return.purchase_unit_id')
                                    ->get();
                foreach ($product_return_data as $index => $product_return) {
                    if($product_return->purchase_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->purchase_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($return->grand_total / ($return->exchange_rate ?: 1), cache()->get('general_setting')->decimal);
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

    public function supplierQuotationData(Request $request)
    {
        $columns = array(
            1 => 'created_at',
            2 => 'reference_no',
        );

        $supplier_id = $request->input('supplier_id');
        $q = DB::table('quotations')
            ->join('suppliers', 'quotations.supplier_id', '=', 'suppliers.id')
            ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.id')
            ->join('warehouses', 'quotations.warehouse_id', '=', 'warehouses.id')
            ->where('quotations.supplier_id', $supplier_id)
            ->whereDate('quotations.created_at', '>=', $request->input('start_date'))->whereDate('quotations.created_at', '<=', $request->input('end_date'));

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start_date');
        $order = 'quotations.'.$columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $q = $q->select('quotations.id', 'quotations.reference_no', 'quotations.supplier_id', 'quotations.grand_total', 'quotations.quotation_status', 'quotations.created_at', 'customers.name as customer_name', 'warehouses.name as warehouse_name')
            ->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        if(empty($request->input('search.value'))) {
            $quotations = $q->get();
        }
        else
        {
            $search = $request->input('search.value');
            $q = $q->whereDate('quotations.created_at', '=' , date('Y-m-d', strtotime(str_replace('/', '-', $search))));
            if(Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $quotations =  $q->orwhere([
                                ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->orwhere([
                                ['quotations.created_at', 'LIKE', "%{$search}%"],
                                ['quotations.user_id', Auth::id()]
                            ])
                            ->get();
                $totalFiltered = $q->orwhere([
                                    ['quotations.reference_no', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->orwhere([
                                    ['quotations.created_at', 'LIKE', "%{$search}%"],
                                    ['quotations.user_id', Auth::id()]
                                ])
                                ->count();
            }
            else {
                $quotations =  $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->get();
                $totalFiltered = $q->orwhere('quotations.created_at', 'LIKE', "%{$search}%")->orwhere('quotations.reference_no', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($quotations))
        {
            foreach ($quotations as $key => $quotation)
            {
                $nestedData['id'] = $quotation->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($quotation->created_at));
                $nestedData['reference_no'] = $quotation->reference_no;
                $nestedData['warehouse'] = $quotation->warehouse_name;
                $nestedData['customer'] = $quotation->customer_name;
                $product_quotation_data = DB::table('quotations')->join('product_quotation', 'quotations.id', '=', 'product_quotation.quotation_id')
                                    ->join('products', 'product_quotation.product_id', '=', 'products.id')
                                    ->where('quotations.id', $quotation->id)
                                    ->select('products.name as product_name', 'product_quotation.qty', 'product_quotation.sale_unit_id')
                                    ->get();
                foreach ($product_quotation_data as $index => $product_return) {
                    if($product_return->sale_unit_id) {
                        $unit_data = DB::table('units')->select('unit_code')->find($product_return->sale_unit_id);
                        $unitCode = $unit_data->unit_code ?? '';
                    }
                    else
                        $unitCode = '';
                    if($index)
                        $nestedData['product'] .= '<br>'.$product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                    else
                        $nestedData['product'] = $product_return->product_name.' ('.number_format($product_return->qty, cache()->get('general_setting')->decimal).' '.$unitCode.')';
                }
                $nestedData['grand_total'] = number_format($quotation->grand_total, cache()->get('general_setting')->decimal);
                if($quotation->quotation_status == 1){
                    $nestedData['status'] = '<div class="badge badge-danger">'.__('db.Pending').'</div>';
                }
                else{
                    $nestedData['status'] = '<div class="badge badge-success">'.__('db.Sent').'</div>';
                }
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

    public function customerDueReportByDate(Request $request)
    {
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $customer_id = $request->customer_id ?? 0;

        $lims_sale_data = [];
        if ($customer_id) {
            $lims_sale_data = Sale::where('payment_status', '!=', 4)
                ->whereNull('deleted_at')
                ->where('customer_id', $request->customer_id)
                ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                ->get();
        } else {
            $lims_sale_data = Sale::where('payment_status', '!=', 4)
                ->whereNull('deleted_at')
                ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->when($this->own_data, fn($q) => $q->where('user_id', $this->current_user_id))
                ->get();
        }
        // return dd($lims_sale_data);
        $lims_customer_list = Customer::where('is_active', true)->get();
        return view('backend.report.due_report', compact('lims_sale_data', 'start_date', 'end_date', 'customer_id', 'lims_customer_list'));
    }

    public function customerDueReportData(Request $request)
    {
        $decimal  = cache()->get('general_setting')->decimal;
        $start    = intval($request->input('start'));
        $length   = $request->input('length');
        $search   = $request->input('search.value');
        $orderCol = intval($request->input('order.0.column'));
        $orderDir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        // Column index to actual DB column mapping
        $sortableColumns = [
            1 => DB::raw('sales.created_at'),
            2 => DB::raw('sales.reference_no'),
            3 => DB::raw('customers.name'),
            4 => DB::raw('sales.grand_total'),
            5 => DB::raw('returned_amount'),
            6 => DB::raw('sales.paid_amount'),
            7 => DB::raw('due'),
        ];

        $sortColumn = $sortableColumns[$orderCol] ?? DB::raw('sales.created_at');

        // -------------------------------------------------------
        // HELPER: build base query fresh every time (no clone)
        // -------------------------------------------------------
        $buildQuery = function () use ($request) {
            $q = DB::table('sales')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoin('returns', 'returns.sale_id', '=', 'sales.id')
                ->leftJoin(DB::raw('(select return_id, sum(amount) as refunded_amount from payments where return_id is not null and sale_id is not null group by return_id) as sale_return_refunds'), 'sale_return_refunds.return_id', '=', 'returns.id')
                ->where('sales.payment_status', '!=', 4)
                ->whereNull('sales.deleted_at')
                ->whereDate('sales.created_at', '>=', $request->start_date)
                ->whereDate('sales.created_at', '<=', $request->end_date);

            // Customer filter
            if ($request->filled('customer_id') && $request->customer_id != 0) {
                $q->where('sales.customer_id', $request->customer_id);
            }

            // Access control
            if (Auth::user()->role_id > 2) {
                if (config('staff_access') === 'own') {
                    $q->where('sales.user_id', Auth::id());
                } elseif (config('staff_access') === 'warehouse') {
                    $q->where('sales.warehouse_id', Auth::user()->warehouse_id);
                }
            }

            app(\App\Services\WarehouseAccessService::class)->scope($q, 'sales.warehouse_id');

            return $q;
        };

        // -------------------------------------------------------
        // HELPER: apply search on top of base query
        // -------------------------------------------------------
        $applySearch = function ($q, $search) {
            if (!empty($search)) {
                $searchDate = date('Y-m-d', strtotime(str_replace('/', '-', $search)));
                $q->where(function ($q) use ($search, $searchDate) {
                    $q->whereDate('sales.created_at', $searchDate)
                    ->orWhere('sales.reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('customers.name', 'LIKE', "%{$search}%")
                    ->orWhere('customers.phone_number', 'LIKE', "%{$search}%");
                });
            }
            return $q;
        };

        // -------------------------------------------------------
        // 1. TOTAL COUNT (no search)
        // -------------------------------------------------------
        $totalData = $buildQuery()
            ->distinct()
            ->count('sales.id');

        // -------------------------------------------------------
        // 2. FILTERED COUNT (with search)
        // -------------------------------------------------------
        $filteredQuery = $applySearch($buildQuery(), $search);
        $totalFiltered = (clone $filteredQuery)
            ->distinct()
            ->count('sales.id');

        // -------------------------------------------------------
        // 3. CARD TOTALS тАФ рж╕ржм filtered record ржПрж░ total
        // -------------------------------------------------------
        $cardQuery = $applySearch($buildQuery(), $search)
            ->select(
                DB::raw('SUM(sales.grand_total) as total_grand'),
                DB::raw('SUM(sales.paid_amount) as total_paid'),
                DB::raw('COALESCE(SUM(COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) / NULLIF(returns.exchange_rate, 0)), 0) as total_returned')
            )
            ->first();

        $total_grand    = $cardQuery->total_grand ?? 0;
        $total_paid     = $cardQuery->total_paid ?? 0;
        $total_returned = $cardQuery->total_returned ?? 0;
        $total_due      = $total_grand - $total_returned - $total_paid;

        // -------------------------------------------------------
        // 4. MAIN DATA QUERY тАФ with groupBy then orderBy
        // -------------------------------------------------------
        $limit = ($length != -1) ? intval($length) : $totalFiltered;

        $sales = $applySearch($buildQuery(), $search)
            ->select(
                'sales.id',
                'sales.reference_no',
                'sales.grand_total',
                'sales.exchange_rate',
                'sales.created_at',
                'sales.paid_amount',
                'customers.name as customer_name',
                'customers.phone_number as customer_phone',
                'customers.address as customer_address',
                DB::raw('COALESCE(SUM(COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) / NULLIF(returns.exchange_rate, 0)), 0) as returned_amount'),
                DB::raw("
                    CASE
                        WHEN sales.sale_type = 'opening balance'
                        THEN (sales.grand_total - sales.paid_amount)
                        ELSE (
                            sales.grand_total
                            - COALESCE(SUM(COALESCE(sale_return_refunds.refunded_amount, returns.grand_total) / NULLIF(returns.exchange_rate,0)),0)
                            - sales.paid_amount
                        )
                    END as due
                ")
            )
            ->groupBy(
                'sales.id',
                'sales.reference_no',
                'sales.grand_total',
                'sales.created_at',
                'sales.paid_amount',
                'customers.name',
                'customers.phone_number',
                'customers.address'
            )
            ->orderBy($sortColumn, $orderDir)
            ->offset($start)
            ->limit($limit)
            ->get();

        // -------------------------------------------------------
        // 5. FORMAT DATA
        // -------------------------------------------------------
        $data = [];
        foreach ($sales as $key => $sale) {
            $data[] = [
                'id'              => $sale->id,
                'key'             => $key,
                'date'            => date(config('date_format'), strtotime($sale->created_at)),
                'reference_no'    => $sale->reference_no,
                'customer'        => $sale->customer_name
                                    . '<br><small class="text-muted">' . $sale->customer_phone . '</small>'
                                    . ($sale->customer_address
                                        ? '<br><small class="text-muted">' . $sale->customer_address . '</small>'
                                        : ''),
                'grand_total'     => number_format($sale->grand_total / ($sale->exchange_rate ?: 1), $decimal),
                'returned_amount' => number_format($sale->returned_amount, $decimal),
                'paid'            => number_format(($sale->paid_amount ?? 0) / ($sale->exchange_rate ?: 1), $decimal),
                'due'             => number_format($sale->due, $decimal),
            ];
        }

        // -------------------------------------------------------
        // 6. JSON RESPONSE
        // -------------------------------------------------------
        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "total_grand"     => number_format($total_grand, $decimal),
            "total_returned"  => number_format($total_returned, $decimal),
            "total_paid"      => number_format($total_paid, $decimal),
            "total_due"       => number_format($total_due, $decimal),
        ]);
    }

    public function supplierDueReportByDate(Request $request)
    {
        $data = $request->all();
        $supplier_id = null;
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $q = Purchase::whereIn('payment_status', [1, 2, 3])
            ->whereNull('deleted_at')
            ->whereDate('updated_at', '>=', $start_date)
            ->whereDate('updated_at', '<=', $end_date);
        if($request->supplier_id) {
            $supplier_id = $request->supplier_id;
            $q = $q->where('supplier_id', $request->supplier_id);
        }
        $lims_purchase_data = $q->orderBy('updated_at', 'desc')->get();
        $lims_supplier_list = Supplier::where('is_active', true)->get();

        if ($request->ajax()) {
            return view('backend.report.partials.supplier_due_table', compact('lims_purchase_data', 'start_date', 'end_date', 'lims_supplier_list', 'supplier_id'))->render();
        }

        return view('backend.report.supplier_due_report', compact('lims_purchase_data', 'start_date', 'end_date', 'lims_supplier_list', 'supplier_id'));
    }
}
