<?php

namespace Modules\Restaurant\Http\Controllers;

use Modules\Restaurant\Entities\Kitchens;
use App\Models\User;
use App\Models\Table;
use App\Models\Sale;
use App\Models\Roles;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class KitchenController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role || (!$role->hasPermissionTo('restaurant-kitchen') && Auth::user()->role_id > 2)) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $kitchens = Kitchens::all();
        $usersQuery = User::where('is_active', 1)->where('is_deleted', 0);
        if (Auth::user()->role_id > 2 && Auth::user()->warehouse_id) {
            $usersQuery->where('warehouse_id', Auth::user()->warehouse_id);
        }
        $users = $usersQuery->get();

        return view('restaurant::backend.kitchen.index', compact('kitchens', 'users'));
    }

    public function store(Request $request)
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $role = Role::find(Auth::user()->role_id);
        if (!$role || (!$role->hasPermissionTo('restaurant-kitchen') && Auth::user()->role_id > 2)) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $data = $request->only('name');
        $data['is_active'] = 1;

        if (Kitchens::create($data)) {
            Session::flash('message', 'Kitchen saved successfully.');
            Session::flash('type', 'success');
        } else {
            Session::flash('message', 'Failed to save kitchen.');
            Session::flash('type', 'danger');
        }
        return redirect()->back();
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role || (!$role->hasPermissionTo('restaurant-kitchen') && Auth::user()->role_id > 2)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $kitchen = Kitchens::find($id);
        return response()->json($kitchen);
    }

    public function update(Request $request)
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $role = Role::find(Auth::user()->role_id);
        if (!$role || (!$role->hasPermissionTo('restaurant-kitchen') && Auth::user()->role_id > 2)) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $kitchen = Kitchens::findOrFail($request->kitchenid);

        $prev_user = User::find($kitchen->user_id);
        if (!empty($prev_user) && !empty($prev_user->kitchen_id)) {
            $prev_kitchen = explode(',', $prev_user->kitchen_id);
            $del_kitchen = explode(',', (string) $kitchen->id);
            $prev_user->kitchen_id = implode(',', array_diff($prev_kitchen, $del_kitchen));
            $prev_user->save();
        }

        $kitchen->name = $request->name;
        $kitchen->user_id = $request->user_id ?: null;
        $kitchen->save();

        if ($request->user_id) {
            $user = User::find($request->user_id);
            if ($user) {
                if (!empty($user->kitchen_id)) {
                    $assigned = explode(',', $user->kitchen_id);
                    if (!in_array((string) $kitchen->id, $assigned)) {
                        $user->kitchen_id = $user->kitchen_id . ',' . $kitchen->id;
                    }
                } else {
                    $user->kitchen_id = (string) $kitchen->id;
                }
                $user->save();
            }
        }

        Session::flash('message', 'Kitchen saved successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $role = Role::find(Auth::user()->role_id);
        if (!$role || (!$role->hasPermissionTo('restaurant-kitchen') && Auth::user()->role_id > 2)) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        Kitchens::findOrFail($request->id)->delete();

        Session::flash('message', 'Kitchen deleted successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function dashboard()
    {
        $user = Auth::user();
        $isAdmin = (int) $user->role_id <= 2;
        $role = Role::find($user->role_id);
        if (!$isAdmin && (!$role || !$role->hasPermissionTo('restaurant-kitchen-dashboard'))) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $data = $this->buildDashboardData($user);

        return view('restaurant::backend.kitchen.dashboard', $data);
    }

    /**
     * AJAX endpoint to fetch refreshed kitchen dashboard content partial.
     */
    public function dashboardData(Request $request)
    {
        $user = Auth::user();
        $isAdmin = (int) $user->role_id <= 2;
        $role = Role::find($user->role_id);
        if (!$isAdmin && (!$role || !$role->hasPermissionTo('restaurant-kitchen-dashboard'))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $data = $this->buildDashboardData($user);
        $html = view('restaurant::backend.kitchen.partials.dashboard-content', $data)->render();

        return response()->json([
            'status'     => 'success',
            'html'       => $html,
            'updated_at' => date('H:i:s'),
            'timestamp'  => now()->toIso8601String(),
        ]);
    }

    /**
     * Shared builder for Kitchen Dashboard data to guarantee 100% query and scoping consistency.
     */
    protected function buildDashboardData($user): array
    {
        $isAdmin = (int) $user->role_id <= 2;
        $assignedKitchenIds = !empty($user->kitchen_id) ? array_map('intval', array_filter(explode(',', (string) $user->kitchen_id))) : [];
        $isChef = !empty($assignedKitchenIds);
        $isWaiter = (int) ($user->service_staff ?? 0) === 1;
        $isUnassigned = !$isAdmin && !$isChef && !$isWaiter;

        // Resolve visible Kitchens
        if ($isAdmin) {
            $kitchen_list = Kitchens::where('is_active', 1)->get();
        } elseif ($isChef) {
            $kitchen_list = Kitchens::whereIn('id', $assignedKitchenIds)->where('is_active', 1)->get();
        } else {
            $kitchen_list = collect();
        }

        // Base Query for Kitchen Preparation Items (pending=0 or preparing=1)
        $kitchenItemsQuery = DB::table('product_sales')
            ->join('sales', 'product_sales.sale_id', '=', 'sales.id')
            ->join('products', 'product_sales.product_id', '=', 'products.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('billers', 'sales.biller_id', '=', 'billers.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('tables', 'sales.table_id', '=', 'tables.id')
            ->leftJoin('users as waiters', 'sales.waiter_id', '=', 'waiters.id')
            ->whereNull('sales.deleted_at')
            ->whereNotNull('products.kitchen_id')
            ->where('products.kitchen_id', '>', 0)
            ->whereIn('product_sales.kitchen_status', [0, 1]) // 0=pending, 1=preparing
            ->whereIn('sales.sale_status', [5, 6])
            ->select(
                'product_sales.id as product_sale_id',
                'product_sales.sale_id',
                'product_sales.product_id',
                'product_sales.qty as total_quantity',
                'product_sales.kitchen_status',
                'product_sales.is_delivered',
                'products.name as product_name',
                'products.kitchen_id',
                'sales.created_at as sale_date',
                'sales.sale_status',
                'sales.reference_no',
                'sales.total_tax',
                'sales.total_discount',
                'sales.total_price',
                'sales.order_tax',
                'sales.order_tax_rate',
                'sales.order_discount',
                'sales.shipping_cost',
                'sales.grand_total',
                'sales.paid_amount',
                'sales.sale_note',
                'sales.staff_note',
                'sales.coupon_discount',
                'sales.document',
                'sales.exchange_rate',
                'sales.coupon_id',
                'sales.currency_id',
                'sales.warehouse_id',
                'users.name as user_name',
                'users.email as user_email',
                'warehouses.name as warehouse',
                'customers.name as customer_name',
                'customers.phone_number as customer_phone_number',
                'customers.address as customer_address',
                'customers.city as customer_city',
                'billers.name as biller_name',
                'billers.company_name as biller_company_name',
                'billers.email as biller_email',
                'billers.phone_number as biller_phone_number',
                'billers.address as biller_address',
                'billers.city as biller_city',
                'tables.name as table_name',
                'waiters.name as waiter_name',
                'sales.waiter_id',
                DB::raw('(SELECT GROUP_CONCAT(CONCAT(modifiers.name, IF(psm.qty > 1, CONCAT(" (x", psm.qty, ")"), "")) SEPARATOR ", ")
                          FROM product_sale_modifiers psm
                          JOIN modifiers ON psm.modifier_id = modifiers.id
                          WHERE psm.product_sale_id = product_sales.id) as modifier_labels')
            );

        if (!$isAdmin && $user->warehouse_id) {
            $kitchenItemsQuery->where('sales.warehouse_id', $user->warehouse_id);
        }

        if ($isChef && !$isAdmin) {
            $kitchenItemsQuery->whereIn('products.kitchen_id', $assignedKitchenIds);
        } elseif (!$isAdmin && !$isChef) {
            $kitchenItemsQuery->whereRaw('1 = 0'); // No kitchen items for waiter-only
        }

        $salesData = $kitchenItemsQuery->orderBy('product_sales.id', 'asc')->get()->map(function ($row) {
            $row->kitchen_id = (int) $row->kitchen_id;
            return $row;
        });

        // Query for Ready for Service orders (parent sale_status == 6)
        $readyOrdersQuery = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->join('billers', 'sales.biller_id', '=', 'billers.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->join('warehouses', 'sales.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('tables', 'sales.table_id', '=', 'tables.id')
            ->leftJoin('users as waiters', 'sales.waiter_id', '=', 'waiters.id')
            ->whereNull('sales.deleted_at')
            ->where('sales.sale_status', 6) // Ready for Service
            ->select(
                'sales.id as sale_id',
                'sales.created_at as sale_date',
                'sales.sale_status',
                'sales.reference_no',
                'sales.grand_total',
                'sales.paid_amount',
                'sales.item as total_quantity',
                'sales.sale_note',
                'sales.staff_note',
                'sales.total_tax',
                'sales.total_discount',
                'sales.total_price',
                'sales.order_tax',
                'sales.order_tax_rate',
                'sales.order_discount',
                'sales.shipping_cost',
                'sales.coupon_discount',
                'sales.document',
                'sales.currency_id',
                'sales.exchange_rate',
                'sales.coupon_id',
                'warehouses.name as warehouse',
                'users.name as user_name',
                'users.email as user_email',
                'customers.name as customer_name',
                'customers.phone_number as customer_phone_number',
                'customers.address as customer_address',
                'customers.city as customer_city',
                'billers.name as biller_name',
                'billers.company_name as biller_company_name',
                'billers.email as biller_email',
                'billers.phone_number as biller_phone_number',
                'billers.address as biller_address',
                'billers.city as biller_city',
                'tables.name as table_name',
                'waiters.name as waiter_name',
                'sales.waiter_id'
            );

        if (!$isAdmin && $user->warehouse_id) {
            $readyOrdersQuery->where('sales.warehouse_id', $user->warehouse_id);
        }

        if ($isWaiter && !$isAdmin) {
            $readyOrdersQuery->where('sales.waiter_id', $user->id);
        } elseif (!$isAdmin && !$isWaiter) {
            $readyOrdersQuery->whereRaw('1 = 0');
        }

        $readySalesData = $readyOrdersQuery->orderBy('sales.id', 'asc')->get();

        // Attach line items with modifier labels for ready orders
        $readySaleIds = $readySalesData->pluck('sale_id')->toArray();
        $readyItemsBySale = [];
        if (!empty($readySaleIds)) {
            $readyItems = DB::table('product_sales')
                ->join('products', 'product_sales.product_id', '=', 'products.id')
                ->whereIn('product_sales.sale_id', $readySaleIds)
                ->select(
                    'product_sales.sale_id',
                    'product_sales.id as product_sale_id',
                    'products.name as product_name',
                    'product_sales.qty',
                    'product_sales.kitchen_status',
                    DB::raw('(SELECT GROUP_CONCAT(CONCAT(modifiers.name, IF(psm.qty > 1, CONCAT(" (x", psm.qty, ")"), "")) SEPARATOR ", ")
                              FROM product_sale_modifiers psm
                              JOIN modifiers ON psm.modifier_id = modifiers.id
                              WHERE psm.product_sale_id = product_sales.id) as modifier_labels')
                )
                ->get();
            $readyItemsBySale = $readyItems->groupBy('sale_id');
        }

        $dateFormat = DB::table('general_settings')->value('date_format') ?: 'd-m-Y';

        return compact(
            'kitchen_list',
            'salesData',
            'readySalesData',
            'readyItemsBySale',
            'dateFormat',
            'isAdmin',
            'isChef',
            'isWaiter',
            'isUnassigned'
        );
    }

    /**
     * Item-level transition: Mark item as Preparing (kitchen_status = 1)
     */
    public function markItemPreparing($productSaleId)
    {
        return $this->transitionItemStatus((int) $productSaleId, 1);
    }

    /**
     * Item-level transition: Mark item as Ready (kitchen_status = 2)
     */
    public function markCooked($productSaleId)
    {
        return $this->transitionItemStatus((int) $productSaleId, 2);
    }

    /**
     * Transition a single product_sale item status and recompute parent sale status.
     * Enforces strict status transitions (0 -> 1 and 1 -> 2 only) with transaction locking.
     */
    protected function transitionItemStatus(int $productSaleId, int $targetStatus)
    {
        $user = Auth::user();
        $isAdmin = (int) $user->role_id <= 2;
        $role = Role::find($user->role_id);
        if (!$isAdmin && (!$role || !$role->hasPermissionTo('restaurant-kitchen-dashboard'))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($productSaleId, $targetStatus, $user) {
            $productSale = DB::table('product_sales')
                ->join('products', 'product_sales.product_id', '=', 'products.id')
                ->join('sales', 'product_sales.sale_id', '=', 'sales.id')
                ->where('product_sales.id', $productSaleId)
                ->whereNull('sales.deleted_at')
                ->lockForUpdate()
                ->select(
                    'product_sales.id',
                    'product_sales.sale_id',
                    'product_sales.kitchen_status',
                    'products.kitchen_id',
                    'sales.warehouse_id',
                    'sales.sale_status'
                )
                ->first();

            if (!$productSale) {
                return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
            }

            $isAdmin = (int) $user->role_id <= 2;
            $assignedKitchenIds = !empty($user->kitchen_id) ? array_map('intval', array_filter(explode(',', (string) $user->kitchen_id))) : [];

            // Validate warehouse scoping
            if (!$isAdmin && $user->warehouse_id && (int) $productSale->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized warehouse.'], 403);
            }

            // Validate kitchen station assignment
            if (!$isAdmin && !in_array((int) $productSale->kitchen_id, $assignedKitchenIds, true)) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized kitchen station.'], 403);
            }

            $currentStatus = (int) $productSale->kitchen_status;

            // Strict status transition rules:
            // Pending (0) -> Preparing (1)
            // Preparing (1) -> Ready (2)
            $allowed = false;
            if ($currentStatus === 0 && $targetStatus === 1) {
                $allowed = true;
            } elseif ($currentStatus === 1 && $targetStatus === 2) {
                $allowed = true;
            }

            if (!$allowed) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid status transition.'
                ], 422);
            }

            // Update item kitchen_status
            DB::table('product_sales')->where('id', $productSaleId)->update([
                'kitchen_status' => $targetStatus,
                'updated_at'     => now(),
            ]);

            // Auto-derive parent sale status
            $this->syncParentSaleStatus((int) $productSale->sale_id);

            $message = ($targetStatus === 1) ? 'Item marked as preparing.' : 'Item marked as ready.';

            return response()->json([
                'status'  => 'success',
                'message' => $message
            ]);
        });
    }

    /**
     * Waiter or Admin marks an entire Ready order as Served (sale_status = 1, kitchen_status = 3, is_delivered = 1).
     * Enforces that sale_status == 6 and waiter/admin authorization before transition.
     */
    public function markServed($saleId)
    {
        $user = Auth::user();
        $isAdmin = (int) $user->role_id <= 2;
        $role = Role::find($user->role_id);
        if (!$isAdmin && (!$role || !$role->hasPermissionTo('restaurant-kitchen-dashboard'))) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $isAdmin = (int) $user->role_id <= 2;
        $isWaiter = (int) ($user->service_staff ?? 0) === 1;

        // Chef-only user (or non-admin, non-waiter) cannot mark served
        if (!$isAdmin && !$isWaiter) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($saleId, $user, $isAdmin, $isWaiter) {
            $sale = DB::table('sales')
                ->where('id', $saleId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$sale) {
                return response()->json(['status' => 'error', 'message' => 'Sale not found.'], 404);
            }

            // Warehouse validation
            if (!$isAdmin && $user->warehouse_id && (int) $sale->warehouse_id !== (int) $user->warehouse_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized warehouse.'], 403);
            }

            // Waiter validation: cannot mark another waiter's order served
            if (!$isAdmin && $isWaiter) {
                if (empty($sale->waiter_id) || (int) $sale->waiter_id !== (int) $user->id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized waiter.'], 403);
                }
            }

            // State validation: Sale must be in Ready for Service (6) state
            if ((int) $sale->sale_status !== 6) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Order is not ready to be served.'
                ], 422);
            }

            // Mark all items served & delivered
            DB::table('product_sales')->where('sale_id', $saleId)->update([
                'kitchen_status' => 3, // Served
                'is_delivered'   => 1,
                'updated_at'     => now(),
            ]);

            // Complete the sale (releases table occupancy)
            DB::table('sales')->where('id', $saleId)->update([
                'sale_status' => 1, // Completed
                'updated_at'  => now(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Order marked as served successfully.'
            ]);
        });
    }

    /**
     * Synchronize parent sale status derived from item-level kitchen statuses.
     */
    protected function syncParentSaleStatus(int $saleId): void
    {
        $sale = DB::table('sales')->where('id', $saleId)->whereNull('deleted_at')->first();
        if (!$sale || in_array((int) $sale->sale_status, [1, 3, 4], true)) {
            return; // Completed, Draft, or Returned sales are not overwritten by KDS
        }

        $items = DB::table('product_sales')
            ->join('products', 'product_sales.product_id', '=', 'products.id')
            ->where('product_sales.sale_id', $saleId)
            ->select('product_sales.id', 'product_sales.kitchen_status', 'products.kitchen_id')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // Active kitchen items (items with active kitchen assignment)
        $kitchenItems = $items->filter(function ($item) {
            return !empty($item->kitchen_id) && (int) $item->kitchen_id > 0;
        });

        if ($kitchenItems->isEmpty()) {
            // No items require cooking -> Ready for Service
            DB::table('sales')->where('id', $saleId)->update(['sale_status' => 6]);
            return;
        }

        // If all items are served -> Completed
        if ($items->every(fn($i) => (int) $i->kitchen_status === 3)) {
            DB::table('sales')->where('id', $saleId)->update(['sale_status' => 1]);
            return;
        }

        // If all required kitchen items are ready (>= 2) -> Ready for Service (6)
        if ($kitchenItems->every(fn($i) => (int) $i->kitchen_status >= 2)) {
            DB::table('sales')->where('id', $saleId)->update(['sale_status' => 6]);
            return;
        }

        // Otherwise at least one kitchen item is pending/preparing -> Processing (5)
        DB::table('sales')->where('id', $saleId)->update(['sale_status' => 5]);
    }
}
