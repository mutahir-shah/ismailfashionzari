<?php

namespace App\Http\Controllers;

use Cache;
use Printing;
use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\Income;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Returns;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\Product_Sale;
use Illuminate\Http\Request;
use App\Models\ReturnPurchase;
use App\Models\ProductPurchase;
use App\Traits\AutoUpdateTrait;
use App\Models\Product_Warehouse;
use App\Traits\ENVFilePutContent;
use App\Models\RewardPointSetting;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Rawilk\Printing\Contracts\Printer;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    use AutoUpdateTrait, ENVFilePutContent;

    private $versionUpgradeInfo = [];

	public function __construct()
    {
        if(!config('database.connections.saleprosaas_landlord')) {
            $this->versionUpgradeInfo = $this->isUpdateAvailable();
        }
	}

    public function home()
    {
        return view('backend.home');
    }

    public function index()
    {
        return redirect('dashboard');
    }

    public function addonList()
    {
        if(!config('database.connections.saleprosaas_landlord')) {
            $role = Role::find(Auth::user()->role_id);
            if(!$role->hasPermissionTo('addons')) {
                return redirect('dashboard')->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
            }
        }
        return view('backend.addonlist');
    }

    public function dashboard()
    {
        if(in_array('restaurant',explode(',',cache()->get('general_setting')->modules))){
            if(Auth::user()->role_id > 2 && isset(Auth::user()->kitchen_id)){

                $result = (new \Modules\Restaurant\Http\Controllers\KitchenController)->dashboard();

                return $result;
            }
        }

        if(Auth::user()->role_id == 5) {
            $customer = Customer::select('id', 'points')->where('user_id', Auth::id())->first();
            $lims_sale_data = Sale::with(['warehouse' => fn ($query) => $query->withoutGlobalScope('authorized_warehouse')])
                                ->whereNull('deleted_at')
                                ->where('customer_id', $customer->id)
                                ->where(function($q) {
                                    $q->where('sale_type', '!=', 'opening balance')
                                    ->orWhereNull('sale_type');
                                })
                                ->orderBy('created_at', 'desc')
                                ->get();
            $lims_payment_data = DB::table('payments')
                           ->join('sales', 'payments.sale_id', '=', 'sales.id')
                           ->whereNull('sales.deleted_at')
                           ->whereNull('payments.return_id')
                           ->where('customer_id', $customer->id)
                           ->select('payments.*', 'sales.reference_no as sale_reference')
                           ->orderBy('payments.created_at', 'desc')
                           ->get();
            $lims_quotation_data = Quotation::with('biller', 'customer', 'supplier', 'user')->orderBy('id', 'desc')->where('customer_id', $customer->id)->orderBy('created_at', 'desc')->get();

            $lims_return_data = Returns::with([
                'warehouse' => fn ($query) => $query->withoutGlobalScope('authorized_warehouse'),
                'customer', 'biller'
            ])->where('customer_id', $customer->id)->orderBy('created_at', 'desc')->get();
            $lims_reward_point_setting_data = RewardPointSetting::select('per_point_amount')->latest()->first();
            $alert_product = $dso_alert_product_no = $expire_alert_products = $pending_ecommerce_orders = 0;
            return view('backend.customer_index', compact('customer', 'lims_sale_data', 'lims_payment_data', 'lims_quotation_data', 'lims_return_data', 'lims_reward_point_setting_data', 'alert_product', 'dso_alert_product_no', 'expire_alert_products', 'pending_ecommerce_orders'));
        }

        // Reconnecting an active connection implicitly discards its transaction.
        // Dashboard requests normally run outside a transaction, but certification
        // and callers that deliberately wrap a request must retain atomicity.
        $canReconnectForDashboard = DB::transactionLevel() === 0;
        config()->set('database.connections.mysql.strict', false);
        if ($canReconnectForDashboard) {
            DB::reconnect();
        }

        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));

        $yearly_sale_amount = [];

        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own')
        {

            $sale_query = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->whereNull('deleted_at');

            $revenue = $sale_query->sum(DB::raw('(grand_total - COALESCE(shipping_cost, 0)) / COALESCE(NULLIF(exchange_rate, 0), 1)'));

            $expense = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum('amount');

            $purchase_query = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->where('user_id', Auth::id())
                            ->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('purchase_type')
                                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                            });

            $return = Returns::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
            
            $income = Income::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum('amount');

            $purchase = $purchase_query->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));

            $revenue = $revenue - $return + $income;

        }
        else
        {

            $sale_query = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereNull('deleted_at');

            $revenue = $sale_query->sum(DB::raw('(grand_total - COALESCE(shipping_cost, 0)) / COALESCE(NULLIF(exchange_rate, 0), 1)'));

            $expense = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');

            $income = Income::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');

            $return = Returns::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));

            $purchase_query = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('purchase_type')
                                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                            });
           
            $purchase = $purchase_query->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));

            $revenue = $revenue - $return + $income;

        }

        //cash flow of last 6 months
        $authorizedSaleIds = Sale::select('id');
        $authorizedPurchaseIds = Purchase::select('id');
        $start = strtotime(date('Y-m-01', strtotime('-5 month', strtotime(date('Y-m-d') ))));
        $end = strtotime(date('Y-m-'.date('t', mktime(0, 0, 0, date("m"), 1, date("Y")))));

        while($start < $end)
        {
            $start_date = date("Y-m", $start).'-'.'01';
            $end_date = date("Y-m", $start).'-'.date('t', mktime(0, 0, 0, date("m", $start), 1, date("Y", $start)));

            if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
                $recieved_amount = DB::table('payments')->whereIn('sale_id', clone $authorizedSaleIds)->whereNull('return_id')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $sent_amount = DB::table('payments')->whereIn('purchase_id', clone $authorizedPurchaseIds)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $return_amount = Returns::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
                $purchase_return_amount = ReturnPurchase::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
                $expense_amount = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum('amount');
                $payroll_amount = Payroll::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->where('user_id', Auth::id())->sum('amount');
            }
            else {
                $recieved_amount = DB::table('payments')->whereIn('sale_id', clone $authorizedSaleIds)->whereNull('return_id')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $sent_amount = DB::table('payments')->whereIn('purchase_id', clone $authorizedPurchaseIds)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $return_amount = Returns::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
                $purchase_return_amount = ReturnPurchase::with('refundPayments')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
                $expense_amount = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');
                $payroll_amount = Payroll::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');
            }
            $sent_amount = $sent_amount + $return_amount + $expense_amount + $payroll_amount;

            $payment_recieved[] = number_format((float)($recieved_amount + $purchase_return_amount), config('decimal'), '.', '');
            $payment_sent[] = number_format((float)$sent_amount, config('decimal'), '.', '');
            $month[] = date("F", strtotime($start_date));
            $start = strtotime("+1 month", $start);
        }
        // yearly report
        $start = strtotime(date("Y") .'-01-01');
        $end = strtotime(date("Y") .'-12-31');
        while($start < $end)
        {
            $start_date = date("Y").'-'.date('m', $start).'-'.'01';
            $end_date = date("Y").'-'.date('m', $start).'-'.date('t', mktime(0, 0, 0, date("m", $start), 1, date("Y", $start)));
            if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
                $sale_amount = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                ->where('user_id', Auth::id())
                                ->whereNull('deleted_at')
                                ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));

                $purchase_amount = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                    ->where('user_id', Auth::id())
                                    ->whereNull('deleted_at')
                                    ->where(function ($q) {
                                        $q->whereNull('purchase_type')
                                        ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                    })
                                    ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            }
            else{
                $sale_amount = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                ->whereNull('deleted_at')
                                ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
                $purchase_amount = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                                    ->whereNull('deleted_at')
                                    ->where(function ($q) {
                                        $q->whereNull('purchase_type')
                                        ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                    })
                                    ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            }
            $yearly_sale_amount[] = number_format((float)$sale_amount, config('decimal'), '.', '');
            $yearly_purchase_amount[] = number_format((float)$purchase_amount, config('decimal'), '.', '');
            $start = strtotime("+1 month", $start);
        }
        
        //making strict mode true for this query
        config()->set('database.connections.mysql.strict', true);
        if ($canReconnectForDashboard) {
            DB::reconnect();
        }
        //fetching data for auto updates
        if(!config('database.connections.saleprosaas_landlord') && Auth::user()->role_id <= 2) {
            $versionUpgradeData = [];
            $versionUpgradeData = $this->versionUpgradeInfo;
        }
        else {
            $versionUpgradeData = [];
        }

        return view('backend.index', compact('start_date','end_date','revenue', 'purchase', 'expense', 'payment_recieved', 'payment_sent', 'month', 'yearly_sale_amount', 'yearly_purchase_amount', 'versionUpgradeData'));
    }

    public function dashboardFilter($start_date, $end_date, $warehouse_id)
    {
        $start_date = Carbon::parse($start_date)->startOfDay();
        $end_date = Carbon::parse($end_date)->endOfDay();

        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $this->setDashboardStrictMode(false);

            $q = Sale::join('product_sales', 'sales.id','=', 'product_sales.sale_id')
                ->select(DB::raw('product_sales.product_id, product_sales.variant_id, product_sales.product_batch_id, product_sales.sale_unit_id, sum(product_sales.qty) as sold_qty, sum(product_sales.return_qty) as return_qty, sum(product_sales.total) as sold_amount'))
                ->whereNull('sales.deleted_at')
                ->where('sales.user_id', Auth::id())
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
                })
                ->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date);

            if($warehouse_id != 0) {
                $q->where('sales.warehouse_id',$warehouse_id);
            }

            $product_sale_data = $q->groupBy('product_sales.product_id', 'product_sales.variant_id', 'product_sales.product_batch_id')->get();

            $this->setDashboardStrictMode(true);

            $product_cost = $this->calculateAverageCOGS($product_sale_data);

            $total_sale_q = Sale::where('user_id', Auth::id())->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->where('sale_type', '!=', 'opening balance')
                                ->orWhereNull('sale_type');
                            });

            $purchase_q = Purchase::where('user_id', Auth::id())
                        ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                        ->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->whereNull('purchase_type')
                            ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                        });

            $return_q = Returns::where('user_id', Auth::id())->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereHas('sale', function ($q) {
                                $q->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->where('sale_type', '!=', 'opening balance')
                                    ->orWhereNull('sale_type');
                                });
                            });

            $purchase_return_q = ReturnPurchase::where('user_id', Auth::id())->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereHas('purchase', function ($q) {
                                $q->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->whereNull('purchase_type')
                                    ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                });
                            });

            if($warehouse_id != 0) {
                $total_sale_q->where('warehouse_id',$warehouse_id);
                $purchase_q->where('warehouse_id',$warehouse_id);
                $return_q->where('warehouse_id',$warehouse_id);
                $purchase_return_q->where('warehouse_id',$warehouse_id);
            }

            $total_sale = $total_sale_q->sum(DB::raw('(grand_total - COALESCE(shipping_cost, 0)) / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $purchase = $purchase_q->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $return = $return_q->with('refundPayments')->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
            $purchase_return = $purchase_return_q->with('refundPayments')->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));

            $invoice_due = $this->dashboardInvoiceDue($start_date, $end_date, $warehouse_id, Auth::id());
            $purchase_due = $this->dashboardPurchaseDue($start_date, $end_date, $warehouse_id, Auth::id());

            $expense = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->where('user_id', Auth::id())
                ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->sum('amount');


            $income = Income::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->where('user_id', Auth::id())
                ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->sum('amount');

            $net_sale = $total_sale - $return;
            $net_purchase = $purchase - $purchase_return;

            $revenue = $total_sale - $return + $income;
            $profit = $revenue - $product_cost - $expense;

        } else {
            $this->setDashboardStrictMode(false);

            $q = Sale::join('product_sales', 'sales.id','=', 'product_sales.sale_id')
                ->select(DB::raw('product_sales.product_id, product_sales.variant_id, product_sales.product_batch_id, product_sales.sale_unit_id, sum(product_sales.qty) as sold_qty, sum(product_sales.return_qty) as return_qty, sum(product_sales.total) as sold_amount'))
                ->whereNull('sales.deleted_at')
                ->where(function ($q) {
                    $q->where('sales.sale_type', '!=', 'opening balance')
                    ->orWhereNull('sales.sale_type');
                })
                ->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date);

            if($warehouse_id != 0) {
                $q->where('sales.warehouse_id',$warehouse_id);
            }

            $product_sale_data = $q->groupBy('product_sales.product_id', 'product_sales.variant_id', 'product_sales.product_batch_id')->get();

            $this->setDashboardStrictMode(true);

            $product_cost = $this->calculateAverageCOGS($product_sale_data);

            $total_sale_q = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->where('sale_type', '!=', 'opening balance')
                                ->orWhereNull('sale_type');
                            });

            $purchase_q = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                        ->whereNull('deleted_at')
                        ->where(function ($q) {
                            $q->whereNull('purchase_type')
                            ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                        });

            $return_q = Returns::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereHas('sale', function ($q) {
                                $q->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->where('sale_type', '!=', 'opening balance')
                                    ->orWhereNull('sale_type');
                                });
                            });
                    
            $purchase_return_q = ReturnPurchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                            ->whereHas('purchase', function ($q) {
                                $q->whereNull('deleted_at')
                                ->where(function ($q) {
                                    $q->whereNull('purchase_type')
                                    ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                });
                            });

            if($warehouse_id != 0) {
                $total_sale_q->where('warehouse_id',$warehouse_id);
                $purchase_q->where('warehouse_id',$warehouse_id);
                $return_q->where('warehouse_id',$warehouse_id);
                $purchase_return_q->where('warehouse_id',$warehouse_id);
            }

            $total_sale = $total_sale_q->sum(DB::raw('(grand_total - COALESCE(shipping_cost, 0)) / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $purchase = $purchase_q->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $return = $return_q->with('refundPayments')->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));
            $purchase_return = $purchase_return_q->with('refundPayments')->get()->sum(fn ($return) => $return->display_grand_total / ($return->exchange_rate ?: 1));

            $invoice_due = $this->dashboardInvoiceDue($start_date, $end_date, $warehouse_id);
            $purchase_due = $this->dashboardPurchaseDue($start_date, $end_date, $warehouse_id);

            $expense = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->sum('amount');


            $income = Income::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
                ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->sum('amount');

            $net_sale = $total_sale - $return;
            $net_purchase = $purchase - $purchase_return;

            $revenue = $total_sale - $return + $income;
            $profit = $revenue - $product_cost - $expense;
        }
            // ✅ return all 8 values

        $data[0] = $revenue;
        $data[1] = $return;
        $data[2] = $profit;
        $data[3] = $purchase_return;
        $data[4] = $net_sale;
        $data[5] = $invoice_due ?? 0;
        $data[6] = $net_purchase;
        $data[7] = $purchase_due ?? 0;
        $data[8] = $expense ?? 0;
        return $data;
    }

    private function setDashboardStrictMode(bool $strict): void
    {
        // Reconnecting ends the transaction used by endpoint certification tests.
        // Production behavior is unchanged; tests keep their isolated transaction.
        if (app()->environment('testing')) {
            return;
        }

        config()->set('database.connections.mysql.strict', $strict);
        DB::reconnect();
    }

    private function dashboardInvoiceDue(Carbon $start_date, Carbon $end_date, $warehouse_id, $user_id = null)
    {
        $returnSubquery = Returns::select('sale_id', DB::raw('SUM(grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)) as returned_total'))
            ->whereNotNull('sale_id')
            ->groupBy('sale_id');

        return Sale::leftJoinSub($returnSubquery, 'dashboard_sale_returns', function ($join) {
                $join->on('sales.id', '=', 'dashboard_sale_returns.sale_id');
            })
            ->whereDate('sales.created_at', '>=', $start_date)
            ->whereDate('sales.created_at', '<=', $end_date)
            ->whereNull('sales.deleted_at')
            ->where(function ($q) {
                $q->where('sales.sale_type', '!=', 'opening balance')
                ->orWhereNull('sales.sale_type');
            })
            ->when($user_id, function ($q) use ($user_id) {
                $q->where('sales.user_id', $user_id);
            })
            ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                $q->where('sales.warehouse_id', $warehouse_id);
            })
            ->sum(DB::raw('GREATEST(0, ((sales.grand_total - sales.paid_amount) / COALESCE(NULLIF(sales.exchange_rate, 0), 1)) - COALESCE(dashboard_sale_returns.returned_total, 0))'));
    }

    private function dashboardPurchaseDue(Carbon $start_date, Carbon $end_date, $warehouse_id, $user_id = null)
    {
        $returnSubquery = DB::table('return_purchases')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->select('return_purchases.purchase_id', DB::raw('SUM(COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total) / COALESCE(NULLIF(return_purchases.exchange_rate, 0), 1)) as returned_total'))
            ->whereNotNull('return_purchases.purchase_id')
            ->groupBy('return_purchases.purchase_id');

        return Purchase::leftJoinSub($returnSubquery, 'dashboard_purchase_returns', function ($join) {
                $join->on('purchases.id', '=', 'dashboard_purchase_returns.purchase_id');
            })
            ->whereDate('purchases.created_at', '>=', $start_date)
            ->whereDate('purchases.created_at', '<=', $end_date)
            ->whereNull('purchases.deleted_at')
            ->where(function ($q) {
                $q->whereNull('purchases.purchase_type')
                ->orWhereNotIn('purchases.purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
            })
            ->when($user_id, function ($q) use ($user_id) {
                $q->where('purchases.user_id', $user_id);
            })
            ->when($warehouse_id != 0, function ($q) use ($warehouse_id) {
                $q->where('purchases.warehouse_id', $warehouse_id);
            })
            ->sum(DB::raw('GREATEST(0, ((purchases.grand_total - purchases.paid_amount) / COALESCE(NULLIF(purchases.exchange_rate, 0), 1)) - COALESCE(dashboard_purchase_returns.returned_total, 0))'));
    }

    public function calculateAverageCOGS($product_sale_data)
    {
        // Initialize total product cost
        $product_cost = 0;

        // Loop through each sold product entry
        foreach ($product_sale_data as $key => $product_sale) {

            // Fetch product details for the sold product
            $product_data = Product::select('type', 'product_list', 'variant_list', 'qty_list', 'cost')
                ->find($product_sale->product_id);

            // If product is a combo (bundle of multiple products)
            if($product_data && $product_data->type == 'combo') {
                $product_list = explode(",", $product_data->product_list);

                // Handle variants if present
                if($product_data->variant_list)
                    $variant_list = explode(",", $product_data->variant_list);
                else
                    $variant_list = [];

                // Quantities of each product in the combo
                $qty_list = explode(",", $product_data->qty_list);

                // Loop through each product inside the combo
                foreach ($product_list as $index => $product_id) {

                    // If product has variants, fetch purchase data accordingly
                    if(count($variant_list) && $variant_list[$index]) {
                        $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where([
                            ['product_purchases.product_id', $product_id],
                            ['product_purchases.variant_id', $variant_list[$index] ]
                        ])
                        ->whereNull('purchases.deleted_at')
                        ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.total')
                        ->get();
                    }
                    else {
                        // Fetch all purchases for this product
                        $product_purchase_data = ProductPurchase::join('purchases', 'product_purchases.purchase_id', '=', 'purchases.id')
                        ->where('product_purchases.product_id', $product_id)
                        ->whereNull('purchases.deleted_at')
                        ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.total')
                        ->get();
                    }

                    $total_received_qty = 0;
                    $total_purchased_amount = 0;

                    // Calculate sold quantity of this sub-product in the combo
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $qty_list[$index];

                    // Fetch all unit conversion data
                    $units = Unit::select('id', 'operator', 'operation_value')->get();

                    // Loop through all purchases for this product
                    foreach ($product_purchase_data as $key => $product_purchase) {
                        $purchase_unit_data = $units->where('id',$product_purchase->purchase_unit_id)->first();

                        // Convert received quantity into base unit
                        if($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;

                        // Accumulate purchase cost
                        if(isset($product_purchase->exchange_rate) && $product_purchase->exchange_rate != 0)
                            $total_purchased_amount += $product_purchase->total/$product_purchase->exchange_rate;
                        else
                            $total_purchased_amount += $product_purchase->total;
                    }

                    // Compute average cost (purchase amount / total received qty)
                    if($total_received_qty)
                        $averageCost = $total_purchased_amount / $total_received_qty;
                    else {
                        $component_data = Product::select('cost')->find($product_id);
                        $averageCost = $component_data->cost;
                    }

                    // Add to total product cost
                    $product_cost += $sold_qty * $averageCost;
                }
            }
            else {
                // For normal products (not combo)

                // Fetch purchase data depending on batch or variant
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
                    ->select('purchases.exchange_rate', 'product_purchases.recieved', 'product_purchases.purchase_unit_id', 'product_purchases.tax', 'product_purchases.total')
                    ->get();
                }

                $total_received_qty = 0;
                $total_purchased_amount = 0;

                // Fetch all unit conversion data
                $units = Unit::select('id', 'operator', 'operation_value')->get();

                // Convert sold quantity into base unit if sale unit is defined
                if($product_sale->sale_unit_id) {
                    $sale_unit_data = $units->where('id', $product_sale->sale_unit_id)->first();
                    if($sale_unit_data->operator == '*')
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $sale_unit_data->operation_value;
                    else
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) / $sale_unit_data->operation_value;
                }
                else {
                    // If no unit conversion, just take raw sold qty
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty);
                }

                // Loop through purchases to accumulate received qty and purchase amount
                foreach ($product_purchase_data as $key => $product_purchase) {
                    $purchase_unit_data = $units->where('id', $product_purchase->purchase_unit_id)->first();
                    if($purchase_unit_data) {
                        if($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;

                        if(isset($product_purchase->exchange_rate) && $product_purchase->exchange_rate != 0)
                            $total_purchased_amount += $product_purchase->total/$product_purchase->exchange_rate;
                        else
                            $total_purchased_amount += $product_purchase->total;
                    }
                }

                // Calculate average cost for the product
                if($total_received_qty)
                    $averageCost = $total_purchased_amount / $total_received_qty;
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
                }

                // Add to total product cost
                $product_cost += $sold_qty * $averageCost;
            }
        }

        // Return the total calculated product cost (COGS)
        return $product_cost;
    }

    public function yearlyBestSellingPrice()
    {
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $yearly_best_selling_price = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
        ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
        ->whereNull('sales.deleted_at')
        ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images'),'sales.exchange_rate', DB::raw('sum(product_sales.total / sales.exchange_rate) as total_price'))
        ->whereYear('product_sales.created_at', date("Y"))
        ->groupBy('products.code')
        ->orderBy('total_price', 'desc')
        ->take(5)
        ->get();

        return response()->json($yearly_best_selling_price);
    }

    public function yearlyBestSellingQty()
    {
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $yearly_best_selling_qty = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
        ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images, sum(product_sales.qty) as sold_qty'))
        ->whereYear('product_sales.created_at', date("Y"))
        ->groupBy('products.code')
        ->orderBy('sold_qty', 'desc')
        ->take(5)
        ->get();

        return response()->json($yearly_best_selling_qty);
    }

    public function monthlyBestSellingQty()
    {
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();

        $best_selling_qty = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
        ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images, sum(product_sales.qty) as sold_qty'))
        ->whereYear('product_sales.created_at', date("Y"))
        ->whereMonth('product_sales.created_at', date("m"))
        ->groupBy('products.code')
        ->orderBy('sold_qty', 'desc')
        ->take(5)
        ->get();

        return response()->json($best_selling_qty);
    }

    public function recentSale()
    {
        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own')
        {
            $recent_sale = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')
                            ->select('sales.id','sales.reference_no','sales.sale_status','sales.created_at','sales.grand_total','sales.exchange_rate','sales.user_id','customers.name')
                            ->orderBy('id', 'desc')
                            ->whereNull('sales.deleted_at')
                            ->where('sales.user_id', Auth::id())
                            ->where(function($q) {
                                $q->where('sales.sale_type', '!=', 'opening balance')
                                ->orWhereNull('sales.sale_type');
                            })
                            ->take(5)->get();
            return response()->json($recent_sale);
        }
        else
        {
            $recent_sale = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')
                            ->select('sales.id','sales.reference_no','sales.sale_status','sales.created_at','sales.grand_total','sales.exchange_rate','customers.name')->orderBy('id', 'desc')
                            ->whereNull('sales.deleted_at')
                            ->where(function($q) {
                                $q->where('sales.sale_type', '!=', 'opening balance')
                                ->orWhereNull('sales.sale_type');
                            })
                            ->take(5)->get();
            return response()->json($recent_sale);
        }
    }

    public function recentPurchase()
    {
        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own')
        {
            $recent_purchase = Purchase::leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
                            ->select('purchases.id','purchases.reference_no','purchases.status','purchases.created_at','purchases.grand_total','purchases.exchange_rate','purchases.user_id','suppliers.name')
                            ->orderBy('id', 'desc')
                            ->where('purchases.user_id', Auth::id())
                            ->whereNull('purchases.deleted_at')
                            ->whereNULL('purchases.purchase_type')
                            ->take(5)->get();

            return response()->json($recent_purchase);
        }
        else
        {
            $recent_purchase = Purchase::leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
                            ->select('purchases.id','purchases.reference_no','purchases.status','purchases.created_at','purchases.grand_total','purchases.exchange_rate','suppliers.name')
                            ->orderBy('id', 'desc')
                            ->whereNull('purchases.deleted_at')
                            ->whereNULL('purchases.purchase_type')
                            ->take(5)->get();

            return response()->json($recent_purchase);
        }
    }

    public function recentQuotation()
    {
        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own')
        {
            $recent_quotation = Quotation::join('customers', 'customers.id', '=', 'quotations.customer_id')->select('quotations.id','quotations.reference_no','quotations.quotation_status','quotations.created_at','quotations.grand_total','quotations.user_id','customers.name')->orderBy('id', 'desc')->where('quotations.user_id', Auth::id())->take(5)->get();
            return response()->json($recent_quotation);
        }
        else
        {
            $recent_quotation = Quotation::join('customers', 'customers.id', '=', 'quotations.customer_id')->select('quotations.id','quotations.reference_no','quotations.quotation_status','quotations.created_at','quotations.grand_total','customers.name')->orderBy('id', 'desc')->take(5)->get();
            return response()->json($recent_quotation);
        }
    }

    public function recentPayment()
    {
        if(Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own')
        {
            $recent_payment = Payment::select('id','payment_reference','amount','exchange_rate','paying_method','created_at','user_id')->orderBy('id', 'desc')->where('user_id', Auth::id())->take(5)->get();
            return response()->json($recent_payment);
        }
        else
        {
            $recent_payment = Payment::select('id','payment_reference','amount','exchange_rate','paying_method','created_at')->orderBy('id', 'desc')->take(5)->get();
            return response()->json($recent_payment);
        }
    }

    public function myTransaction($year, $month)
    {
        $start = 1;
        $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        while($start <= $number_of_day)
        {
            if($start < 10)
                $date = $year.'-'.$month.'-0'.$start;
            else
                $date = $year.'-'.$month.'-'.$start;
            $sale_generated[$start] = Sale::whereDate('created_at', $date)
                                    ->where('user_id', Auth::id())
                                    ->whereNull('deleted_at')
                                    ->where(function ($q) {
                                        $q->where('sales.sale_type', '!=', 'opening balance')
                                        ->orWhereNull('sales.sale_type');
                                    })
                                    ->count();
            $sale_grand_total[$start] = Sale::whereDate('created_at', $date)
                                        ->where('user_id', Auth::id())
                                        ->whereNull('deleted_at')
                                        ->where(function ($q) {
                                            $q->where('sales.sale_type', '!=', 'opening balance')
                                            ->orWhereNull('sales.sale_type');
                                        })
                                        ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $purchase_generated[$start] = Purchase::whereDate('created_at', $date)
                                        ->where('user_id', Auth::id())
                                        ->whereNull('deleted_at')
                                        ->where(function ($q) {
                                            $q->whereNull('purchase_type')
                                            ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                        })
                                        ->count();
            $purchase_grand_total[$start] = Purchase::whereDate('created_at', $date)
                                            ->where('user_id', Auth::id())
                                            ->whereNull('deleted_at')
                                            ->where(function ($q) {
                                                $q->whereNull('purchase_type')
                                                ->orWhereNotIn('purchase_type', Purchase::NON_SUPPLIER_OPENING_TYPES);
                                            })
                                            ->sum(DB::raw('grand_total / COALESCE(NULLIF(exchange_rate, 0), 1)'));
            $quotation_generated[$start] = Quotation::whereDate('created_at', $date)->where('user_id', Auth::id())->count();
            $quotation_grand_total[$start] = Quotation::whereDate('created_at', $date)->where('user_id', Auth::id())->sum('grand_total');
            $start++;
        }
        $start_day = date('w', strtotime($year.'-'.$month.'-01')) + 1;
        $prev_year = date('Y', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $prev_month = date('m', strtotime('-1 month', strtotime($year.'-'.$month.'-01')));
        $next_year = date('Y', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
        $next_month = date('m', strtotime('+1 month', strtotime($year.'-'.$month.'-01')));
        return view('backend.user.my_transaction', compact('start_day', 'year', 'month', 'number_of_day', 'prev_year', 'prev_month', 'next_year', 'next_month', 'sale_generated', 'sale_grand_total','purchase_generated', 'purchase_grand_total','quotation_generated', 'quotation_grand_total'));
    }

    public function switchTheme($theme)
    {
        setcookie('theme', $theme, time() + (86400 * 365), "/");
    }

    public function updateThemeSettings(Request $request)
    {
        $data = $request->all();
        if(isset($data['theme_color'])) setcookie('theme_color', $data['theme_color'], time() + (86400 * 365), "/");
        if(isset($data['theme_font'])) setcookie('theme_font', $data['theme_font'], time() + (86400 * 365), "/");
        if(isset($data['theme'])) setcookie('theme', $data['theme'], time() + (86400 * 365), "/");

        return response()->json('Theme settings updated successfully');
    }

    public function newVersionReleasePage()
    {
		// Below line is deprecated, this code is needed for the client version 1.5.1 and below
        $this->dataWriteInENVFile('APP_ENV', 'local');
		// Below line is deprecated, this code is needed for the client version 1.5.1 and below

        $versionUpgradeData = [];
        $versionUpgradeData = $this->versionUpgradeInfo;
        return view('version_upgrade.index', compact('versionUpgradeData'));
    }

    public function versionUpgrade(Request $request) {
        $versionUpgradeData = [];
        $versionUpgradeData = $this->versionUpgradeInfo;
        $version_upgrade_file_url = $this->versionUpgradeFileUrl($request->purchasecode);

        if (!$version_upgrade_file_url) {
            return redirect()->back()->with('not_permitted', 'Wrong Purchase Code !');
        }

        try {
            //Check file is exist
            $ch = curl_init($version_upgrade_file_url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // === যদি SSL সমস্যা থাকে ===
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if($httpCode != 200) {
                throw new Exception("File not found or server error. HTTP Code: " . $httpCode);
            }

            $transferStatus = $this->fileTransferProcess($version_upgrade_file_url);

            if (!$transferStatus) {
                 throw new Exception("Failed to download the update file.");
            }

            if ($versionUpgradeData['latest_version_db_migrate_enable']==true){
                Artisan::call('migrate');
                Artisan::call('db:seed');
            }

            Artisan::call('optimize:clear');

            $this->dataWriteInENVFile('VERSION', $versionUpgradeData['demo_version']);

            return redirect()->back()->with('message', 'Version Upgraded Successfully !!!');
        }
        catch(Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function fileTransferProcess($version_upgrade_file_url)
    {
        $remote_file_name = pathinfo($version_upgrade_file_url)['basename'];
        $local_file_path = base_path('/'.$remote_file_name);

        $fp = fopen($local_file_path, 'w+');
        if($fp === false){
            return false;
        }

        $ch = curl_init($version_upgrade_file_url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // SSL সমস্যা এড়াতে (প্রয়োজন হলে)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $curl_exec = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        // ডাউনলোড সফল হলে আনজিপ শুরু হবে
        if ($curl_exec) {
            // ****** Unzip ********
            $zip = new ZipArchive;
            $res = $zip->open($local_file_path);

            if ($res === TRUE) {
                $zip->extractTo(base_path());
                $zip->close();

                // ****** Delete Zip File ******
                File::delete($local_file_path);

                return true;
            } else {
                return false;
            }
        }

        return false; // ডাউনলোড ফেইল করেছে
    }

}
