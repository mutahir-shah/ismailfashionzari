<?php

namespace Modules\SocialCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Product_Sale;
use Modules\SocialCommerce\Entities\SocialCommerceClick;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $date_filter = $request->input('date_filter', '30'); // 'all', '7', '30', '365' or custom
        $start_date = null;
        $end_date = null;

        if ($date_filter === 'all') {
            $start_date = null;
            $end_date = null;
        } elseif ($date_filter === '7') {
            $start_date = Carbon::now()->subDays(6)->startOfDay();
            $end_date = Carbon::now()->endOfDay();
        } elseif ($date_filter === '30') {
            $start_date = Carbon::now()->subDays(29)->startOfDay();
            $end_date = Carbon::now()->endOfDay();
        } elseif ($date_filter === '365') {
            $start_date = Carbon::now()->subDays(364)->startOfDay();
            $end_date = Carbon::now()->endOfDay();
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $s = Carbon::parse($request->input('start_date'))->startOfDay();
            $e = Carbon::parse($request->input('end_date'))->endOfDay();
            if ($s->gt($e)) {
                $start_date = $e->startOfDay();
                $end_date = $s->endOfDay();
            } else {
                $start_date = $s;
                $end_date = $e;
            }
            $date_filter = 'custom';
        } else {
            $start_date = Carbon::now()->subDays(29)->startOfDay();
            $end_date = Carbon::now()->endOfDay();
            $date_filter = '30';
        }

        // 1. Published Products
        $publishedProductsCount = SocialProductSetting::where('is_published', 1)->count();

        // 2. Total Clicks
        $clicksQuery = SocialCommerceClick::query();
        if ($start_date && $end_date) {
            $clicksQuery->whereBetween('clicked_at', [$start_date, $end_date]);
        }
        $totalClicks = $clicksQuery->count();

        // Clicks by Source
        $clicksBySourceQuery = SocialCommerceClick::select('source', DB::raw('count(*) as count'));
        if ($start_date && $end_date) {
            $clicksBySourceQuery->whereBetween('clicked_at', [$start_date, $end_date]);
        }
        $clicksBySource = $clicksBySourceQuery->groupBy('source')->get();

        // 3. Orders and Revenue
        $saleQuery = Sale::where('sale_type', 'social_commerce')->whereNull('deleted_at');
        if ($start_date && $end_date) {
            $saleQuery->whereBetween('created_at', [$start_date, $end_date]);
        }
        
        $totalOrders = $saleQuery->count();
        $grossRevenue = $saleQuery->sum(DB::raw('(grand_total - shipping_cost) / COALESCE(NULLIF(exchange_rate, 0), 1)'));

        // Subtract Returns
        $returnsQuery = \App\Models\Returns::join('sales', 'returns.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'social_commerce')
            ->whereNull('sales.deleted_at');
        if ($start_date && $end_date) {
            $returnsQuery->whereBetween('returns.created_at', [$start_date, $end_date]);
        }
        $totalReturns = $returnsQuery->sum(DB::raw('returns.grand_total / COALESCE(NULLIF(returns.exchange_rate, 0), 1)'));

        $totalRevenue = $grossRevenue - $totalReturns;

        // Orders & Revenue by Source
        $salesBySourceQuery = Sale::where('sale_type', 'social_commerce')
            ->whereNull('deleted_at')
            ->select('social_channel', DB::raw('count(*) as count'), DB::raw('SUM((grand_total - shipping_cost) / COALESCE(NULLIF(exchange_rate, 0), 1)) as gross_revenue'));
        
        if ($start_date && $end_date) {
            $salesBySourceQuery->whereBetween('created_at', [$start_date, $end_date]);
        }
        
        $salesBySourceData = $salesBySourceQuery->groupBy('social_channel')->get();
        
        // Also get returns by source
        $returnsBySourceQuery = \App\Models\Returns::join('sales', 'returns.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'social_commerce')
            ->whereNull('sales.deleted_at')
            ->select('sales.social_channel', DB::raw('SUM(returns.grand_total / COALESCE(NULLIF(returns.exchange_rate, 0), 1)) as total_return'));
            
        if ($start_date && $end_date) {
            $returnsBySourceQuery->whereBetween('returns.created_at', [$start_date, $end_date]);
        }
        $returnsBySourceData = $returnsBySourceQuery->groupBy('sales.social_channel')->get()->keyBy('social_channel');

        $salesBySource = $salesBySourceData->map(function($item) use ($returnsBySourceData) {
            $returnAmt = $returnsBySourceData->has($item->social_channel) ? $returnsBySourceData[$item->social_channel]->total_return : 0;
            $item->revenue = $item->gross_revenue - $returnAmt;
            return $item;
        });

        // 4. Top Products
        $topProductsQuery = Product_Sale::join('sales', 'product_sales.sale_id', '=', 'sales.id')
            ->join('products', 'product_sales.product_id', '=', 'products.id')
            ->where('sales.sale_type', 'social_commerce')
            ->whereNull('sales.deleted_at')
            ->select('products.name', 'products.code', DB::raw('SUM(product_sales.qty - COALESCE(product_sales.return_qty, 0)) as sold_qty'));
            
        if ($start_date && $end_date) {
            $topProductsQuery->whereBetween('sales.created_at', [$start_date, $end_date]);
        }

        $topProducts = $topProductsQuery->groupBy('products.name', 'products.code', 'product_sales.product_id')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        return view('socialcommerce::dashboard', compact(
            'date_filter',
            'start_date',
            'end_date',
            'publishedProductsCount',
            'totalClicks',
            'clicksBySource',
            'totalOrders',
            'totalRevenue',
            'salesBySource',
            'topProducts'
        ));
    }
}
