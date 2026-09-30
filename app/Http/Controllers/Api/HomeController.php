<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DataRetrievalService;
use App\Models\Sale;
use App\Models\Returns;
use App\Models\ReturnPurchase;
use App\Models\ProductPurchase;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payroll;
use App\Models\Quotation;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Product_Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RewardPointSetting;
use App\Models\Product_Warehouse;
use App\Models\ThemeSetting;
use App\Models\ActiveThemeSetting;
use App\Models\Unit;
use Cache;
use DB;
use Auth;
use Printing;
use Rawilk\Printing\Contracts\Printer;
use Spatie\Permission\Models\Role;

class HomeController extends Controller
{
    use ProvidesThemeBackgrounds;

    private $_dataRetrievalService;

    public function __construct(DataRetrievalService $dataRetrievalService)
    {
        $this->_dataRetrievalService = $dataRetrievalService;
    }

    /**
     * Resolve dashboard widget colors.
     *
     * Uses the active ThemeSetting's `chart_colors` (if provided) to allow
     * customization from the Theme Settings screen.
     */
    private function resolveDashboardWidgetColors(): array
    {
        $fallback = [
            // Stat cards
            'stat_revenue' => '#34d399',
            'stat_profit' => '#60a5fa',
            'stat_sale_return' => '#fbbf24',
            'stat_purchase_return' => '#c084fc',

            // Charts
            'line_received' => '#22c55e',
            'line_sent' => '#fbbf24',
            'pie_purchase' => '#3b82f6',
            'pie_revenue' => '#22c55e',
            'pie_expense' => '#f43f5e',
            'bar_sold' => '#2dd4bf',
            'bar_purchased' => '#a3e635',
        ];

        $themeSetting = $this->activeThemeSetting('app');
        if ($themeSetting === null) {
            return $fallback;
        }

        $customColors = $this->normalizeHexColors($themeSetting->chart_colors);
        if (empty($customColors)) {
            return $fallback;
        }

        $resolved = $fallback;
        $keys = array_keys($fallback);
        foreach ($keys as $i => $key) {
            if (!empty($customColors[$i])) {
                $resolved[$key] = $customColors[$i];
            }
        }

        return $resolved;
    }

    /**
     * Build permission-based drawer/sidebar menu
     * 
     * @return array
     */
    private function buildDrawer()
    {
        // Get user permissions
        $role_has_permissions_list = Cache::remember('role_has_permissions_list' . Auth::user()->role_id, 60 * 60 * 24 * 365, function () {
            return DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_id', Auth::user()->role_id)
                ->select('permissions.name')
                ->get();
        });

        // Helper function to check permission
        $hasPermission = function ($permissionName) use ($role_has_permissions_list) {
            return $role_has_permissions_list->where('name', $permissionName)->first() !== null;
        };

        // Helper function to check multiple permissions (OR logic)
        $hasAnyPermission = function ($permissionNames) use ($role_has_permissions_list) {
            foreach ($permissionNames as $permissionName) {
                if ($role_has_permissions_list->where('name', $permissionName)->first() !== null) {
                    return true;
                }
            }
            return false;
        };

        $drawer = [];

        // Dashboard - always visible
        $drawer[] = [
            'title' => 'Dashboard',
            'icon' => 'dashboard',
            'group' => false,
            'type' => 'custom',
            'api_url' => '/dashboard'
        ];

        // Product section
        if ($hasAnyPermission(['category', 'brand', 'unit', 'products-index', 'products-add', 'print_barcode', 'adjustment', 'stock_count'])) {
            $productLinks = [];

            if ($hasPermission('category')) {
                $productLinks[] = ['title' => 'Category', 'group' => false, 'type' => 'datatable', 'api_url' => '/categories'];
            }
            if ($hasPermission('brand')) {
                $productLinks[] = ['title' => 'Brand', 'group' => false, 'type' => 'datatable', 'api_url' => '/brands'];
            }
            if ($hasPermission('unit')) {
                $productLinks[] = ['title' => 'Unit', 'group' => false, 'type' => 'datatable', 'api_url' => '/units'];
            }
            if ($hasPermission('products-index')) {
                $productLinks[] = ['title' => 'Product List', 'group' => false, 'type' => 'datatable', 'api_url' => '/products?page=1'];
            }
            if ($hasPermission('products-add')) {
                $productLinks[] = ['title' => 'Add Product', 'group' => false, 'type' => 'form', 'api_url' => '/products/create'];
            }
            if ($hasPermission('print_barcode')) {
                $productLinks[] = ['title' => 'Print Barcode', 'group' => false, 'type' => 'form', 'api_url' => '/products/print-barcode/form'];
            }
            if ($hasPermission('adjustment')) {
                $productLinks[] = ['title' => 'Adjustment List', 'group' => false, 'type' => 'datatable', 'api_url' => '/adjustments'];
                $productLinks[] = ['title' => 'Add Adjustment', 'group' => false, 'type' => 'form', 'api_url' => '/adjustments/create'];
            }
            if ($hasPermission('stock_count')) {
                $productLinks[] = ['title' => 'Stock Count', 'group' => false, 'type' => 'datatable', 'api_url' => '/stock-counts'];
            }

            if (!empty($productLinks)) {
                $drawer[] = [
                    'title' => 'Product',
                    'icon' => 'product',
                    'group' => true,
                    'links' => $productLinks,
                ];
            }
        }

        // Purchase section
        if ($hasAnyPermission(['purchases-index', 'purchases-add'])) {
            $purchaseLinks = [];

            if ($hasPermission('purchases-index')) {
                $purchaseLinks[] = ['title' => 'Purchase List', 'group' => false, 'type' => 'datatable', 'api_url' => '/purchases?page=1'];
            }
            if ($hasPermission('purchases-add')) {
                $purchaseLinks[] = ['title' => 'Add Purchase', 'group' => false, 'type' => 'form', 'api_url' => '/purchases/create'];
                $purchaseLinks[] = ['title' => 'Import Purchase by CSV', 'group' => false, 'type' => 'form', 'api_url' => '/purchases/import'];
            }
            if ($hasPermission('purchase-return-index')) {
                $purchaseLinks[] = ['title' => 'Purchase Return', 'group' => false, 'type' => 'datatable', 'api_url' => '/return-purchase?page=1'];
            }

            if (!empty($purchaseLinks)) {
                $drawer[] = [
                    'title' => 'Purchase',
                    'icon' => 'purchase',
                    'group' => true,
                    'links' => $purchaseLinks,
                ];
            }
        }

        // Sale section
        if ($hasAnyPermission(['sales-index', 'sales-add', 'packing_slip_challan', 'gift_card', 'coupon', 'delivery'])) {
            $saleLinks = [];

            if ($hasPermission('sales-index')) {
                $saleLinks[] = ['title' => 'Sale List', 'group' => false, 'type' => 'datatable', 'api_url' => '/sales?page=1'];
            }
            if ($hasPermission('sales-add')) {
                $saleLinks[] = ['title' => 'POS', 'group' => false, 'type' => 'form', 'api_url' => '/pos/create'];
                $saleLinks[] = ['title' => 'Add Sale', 'group' => false, 'type' => 'form', 'api_url' => '/sales/create'];
                $saleLinks[] = ['title' => 'Import Sale by CSV', 'group' => false, 'type' => 'form', 'api_url' => '/sales/import'];
            }
            if ($hasPermission('packing_slip_challan')) {
                $saleLinks[] = ['title' => 'Packing Slip List', 'group' => false, 'type' => 'datatable', 'api_url' => '/packing-slips'];
                $saleLinks[] = ['title' => 'Challan List', 'group' => false, 'type' => 'datatable', 'api_url' => '/challans'];
            }
            if ($hasPermission('delivery')) {
                $saleLinks[] = ['title' => 'Delivery List', 'group' => false, 'type' => 'datatable', 'api_url' => '/deliveries?page=1'];
            }
            if ($hasPermission('gift_card')) {
                $saleLinks[] = ['title' => 'Gift Card List', 'group' => false, 'type' => 'datatable', 'api_url' => '/gift-cards?page=1'];
            }
            if ($hasPermission('coupon')) {
                $saleLinks[] = ['title' => 'Coupon List', 'group' => false, 'type' => 'datatable', 'api_url' => '/coupons?page=1'];
            }
            if ($hasPermission('sales-index')) {
                $saleLinks[] = ['title' => 'Courier List', 'group' => false, 'type' => 'datatable', 'api_url' => '/couriers?page=1'];
            }
            if ($hasPermission('returns-index')) {
                $saleLinks[] = ['title' => 'Sale Return', 'group' => false, 'type' => 'datatable', 'api_url' => '/return-sale?page=1'];
            }

            if (!empty($saleLinks)) {
                $drawer[] = [
                    'title' => 'Sale',
                    'icon' => 'sale',
                    'group' => true,
                    'links' => $saleLinks,
                ];
            }
        }

        // Manufacturing section - check if user has permission
        if ($hasPermission('manufacturing')) {
            $drawer[] = [
                'title' => 'Manufacturing',
                'icon' => 'manufacturing',
                'group' => true,
                'links' => [
                    ['title' => 'Production List', 'group' => false, 'type' => 'datatable', 'api_url' => '/productions?page=1'],
                    ['title' => 'Add Production', 'group' => false, 'type' => 'form', 'api_url' => '/productions/create'],
                    ['title' => 'Recipe', 'group' => false, 'type' => 'datatable', 'api_url' => '/recipes'],
                ],
            ];
        }

        // WhatsApp section
        $drawer[] = [
            'title' => 'WhatsApp',
            'icon' => 'whatsapp',
            'group' => true,
            'links' => [
                ['title' => 'WhatsApp Settings', 'group' => false, 'type' => 'form', 'api_url' => '/whatsapp/settings'],
                ['title' => 'Message Templates', 'group' => false, 'type' => 'datatable', 'api_url' => '/whatsapp/templates'],
                ['title' => 'Send Message', 'group' => false, 'type' => 'form', 'api_url' => '/whatsapp/send'],
            ],
        ];

        // Expense section
        if ($hasPermission('expenses-index')) {
            $expenseLinks = [];

            $expenseLinks[] = ['title' => 'Expense Category', 'group' => false, 'type' => 'datatable', 'api_url' => '/expense-categories'];
            $expenseLinks[] = ['title' => 'Expense List', 'group' => false, 'type' => 'datatable', 'api_url' => '/expenses?page=1'];

            if ($hasPermission('expenses-add')) {
                $expenseLinks[] = ['title' => 'Add Expense', 'group' => false, 'type' => 'form', 'api_url' => '/expenses/create'];
            }

            $drawer[] = [
                'title' => 'Expense',
                'icon' => 'expense',
                'group' => true,
                'links' => $expenseLinks,
            ];
        }

        // Income section
        if ($hasPermission('incomes-index')) {
            $incomeLinks = [];

            $incomeLinks[] = ['title' => 'Income Category', 'group' => false, 'type' => 'datatable', 'api_url' => '/income-categories'];
            $incomeLinks[] = ['title' => 'Income List', 'group' => false, 'type' => 'datatable', 'api_url' => '/incomes?page=1'];

            if ($hasPermission('incomes-add')) {
                $incomeLinks[] = ['title' => 'Add Income', 'group' => false, 'type' => 'form', 'api_url' => '/incomes/create'];
            }

            $drawer[] = [
                'title' => 'Income',
                'icon' => 'income',
                'group' => true,
                'links' => $incomeLinks,
            ];
        }

        // Quotation section
        if ($hasAnyPermission(['quotes-index', 'quotes-add'])) {
            $quotationLinks = [];

            if ($hasPermission('quotes-index')) {
                $quotationLinks[] = ['title' => 'Quotation List', 'group' => false, 'type' => 'datatable', 'api_url' => '/quotations?page=1'];
            }
            if ($hasPermission('quotes-add')) {
                $quotationLinks[] = ['title' => 'Add Quotation', 'group' => false, 'type' => 'form', 'api_url' => '/quotations/create'];
            }

            if (!empty($quotationLinks)) {
                $drawer[] = [
                    'title' => 'Quotation',
                    'icon' => 'quotation',
                    'group' => true,
                    'links' => $quotationLinks,
                ];
            }
        }

        // Transfer section
        if ($hasAnyPermission(['transfers-index', 'transfers-add'])) {
            $transferLinks = [];

            if ($hasPermission('transfers-index')) {
                $transferLinks[] = ['title' => 'Transfer List', 'group' => false, 'type' => 'datatable', 'api_url' => '/transfers?page=1'];
            }
            if ($hasPermission('transfers-add')) {
                $transferLinks[] = ['title' => 'Add Transfer', 'group' => false, 'type' => 'form', 'api_url' => '/transfers/create'];
                $transferLinks[] = ['title' => 'Import Transfer by CSV', 'group' => false, 'type' => 'form', 'api_url' => '/transfers/import'];
            }

            if (!empty($transferLinks)) {
                $drawer[] = [
                    'title' => 'Transfer',
                    'icon' => 'transfer',
                    'group' => true,
                    'links' => $transferLinks,
                ];
            }
        }

        // Return section - Removed as it is now integrated into Purchase and Sale sections

        // Accounting section
        if ($hasAnyPermission(['account-index', 'money-transfer', 'balance-sheet', 'account-statement'])) {
            $accountingLinks = [];

            if ($hasPermission('account-index')) {
                $accountingLinks[] = ['title' => 'Account List', 'group' => false, 'type' => 'datatable', 'api_url' => '/accounts?page=1'];
                $accountingLinks[] = ['title' => 'Add Account', 'group' => false, 'type' => 'form', 'api_url' => '/accounts/create'];
            }
            if ($hasPermission('money-transfer')) {
                $accountingLinks[] = ['title' => 'Money Transfer', 'group' => false, 'type' => 'datatable', 'api_url' => '/money-transfers?page=1'];
            }
            if ($hasPermission('balance-sheet')) {
                $accountingLinks[] = ['title' => 'Balance Sheet', 'group' => false, 'type' => 'datatable', 'api_url' => '/reports/balance-sheet'];
            }
            if ($hasPermission('account-statement')) {
                $accountingLinks[] = ['title' => 'Account Statement', 'group' => false, 'type' => 'form', 'api_url' => '/reports/account-statement/form'];
            }

            if (!empty($accountingLinks)) {
                $drawer[] = [
                    'title' => 'Accounting',
                    'icon' => 'accounting',
                    'group' => true,
                    'links' => $accountingLinks,
                ];
            }
        }

        // HRM section
        if ($hasAnyPermission(['department', 'employees-index', 'attendance', 'payroll', 'holiday'])) {
            $hrmLinks = [];

            if ($hasPermission('department')) {
                $hrmLinks[] = ['title' => 'Department', 'group' => false, 'type' => 'datatable', 'api_url' => '/departments'];
            }
            if ($hasPermission('employees-index')) {
                $hrmLinks[] = ['title' => 'Employee', 'group' => false, 'type' => 'datatable', 'api_url' => '/employees'];
            }
            if ($hasPermission('attendance')) {
                $hrmLinks[] = ['title' => 'Attendance', 'group' => false, 'type' => 'datatable', 'api_url' => '/attendances'];
            }
            if ($hasPermission('payroll')) {
                $hrmLinks[] = ['title' => 'Payroll', 'group' => false, 'type' => 'datatable', 'api_url' => '/payroll'];
            }
            if ($hasPermission('holiday')) {
                $hrmLinks[] = ['title' => 'Holiday', 'group' => false, 'type' => 'datatable', 'api_url' => '/holidays'];
            }

            if (!empty($hrmLinks)) {
                $drawer[] = [
                    'title' => 'HRM',
                    'icon' => 'hrm',
                    'group' => true,
                    'links' => $hrmLinks,
                ];
            }
        }

        // People section
        if ($hasAnyPermission(['users-index', 'customers-index', 'billers-index', 'suppliers-index'])) {
            $peopleLinks = [];

            if ($hasPermission('users-index')) {
                $peopleLinks[] = ['title' => 'User List', 'group' => false, 'type' => 'datatable', 'api_url' => '/users?page=1'];
                $peopleLinks[] = ['title' => 'Add User', 'group' => false, 'type' => 'form', 'api_url' => '/users/create'];
                if ($hasPermission('sale-agents')) {
                    $peopleLinks[] = ['title' => 'Sale Agents', 'group' => false, 'type' => 'datatable', 'api_url' => '/sale-agents?page=1'];
                }
            }
            if ($hasPermission('customers-index')) {
                $peopleLinks[] = ['title' => 'Customer List', 'group' => false, 'type' => 'datatable', 'api_url' => '/customers?page=1'];
                $peopleLinks[] = ['title' => 'Add Customer', 'group' => false, 'type' => 'form', 'api_url' => '/customers/create'];
            }
            if ($hasPermission('billers-index')) {
                $peopleLinks[] = ['title' => 'Biller List', 'group' => false, 'type' => 'datatable', 'api_url' => '/billers?page=1'];
                $peopleLinks[] = ['title' => 'Add Biller', 'group' => false, 'type' => 'form', 'api_url' => '/billers/create'];
            }
            if ($hasPermission('suppliers-index')) {
                $peopleLinks[] = ['title' => 'Supplier List', 'group' => false, 'type' => 'datatable', 'api_url' => '/suppliers?page=1'];
                $peopleLinks[] = ['title' => 'Add Supplier', 'group' => false, 'type' => 'form', 'api_url' => '/suppliers/create'];
            }

            if (!empty($peopleLinks)) {
                $drawer[] = [
                    'title' => 'People',
                    'icon' => 'people',
                    'group' => true,
                    'links' => $peopleLinks,
                ];
            }
        }

        // Reports section - Permission checks for each report type
        $reportLinks = [];

        if (Auth::user()->role_id <= 2) {
            $reportLinks[] = ['title' => 'Activity Log', 'group' => false, 'type' => 'datatable', 'api_url' => '/reports/activity-log'];
        }

        if ($hasPermission('profit-loss')) {
            $reportLinks[] = ['title' => 'Profit/Loss Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/profit-loss-report/create'];
        }
        if ($hasPermission('best-seller')) {
            $reportLinks[] = ['title' => 'Best Seller', 'group' => false, 'type' => 'custom', 'api_url' => '/reports/best-seller'];
        }
        if ($hasPermission('product-report')) {
            $reportLinks[] = ['title' => 'Product Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/product-report/create'];
        }
        if ($hasPermission('sale-report')) {
            $reportLinks[] = ['title' => 'Sale Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/sale-report/create'];
        }
        if ($hasPermission('daily-sale')) {
            $reportLinks[] = ['title' => 'Daily Sale', 'group' => false, 'type' => 'custom', 'api_url' => '/reports/daily-sale/' . date('Y') . '/' . date('m')];
        }
        if ($hasPermission('monthly-sale')) {
            $reportLinks[] = ['title' => 'Monthly Sale', 'group' => false, 'type' => 'custom', 'api_url' => '/reports/monthly-sale/' . date('Y')];
        }
        if ($hasPermission('payment-report')) {
            $reportLinks[] = ['title' => 'Payment Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/payment-report/create'];
        }
        if ($hasPermission('product-expiry-report')) {
            $reportLinks[] = ['title' => 'Product Expiry Report', 'group' => false, 'type' => 'datatable', 'api_url' => '/reports/product-expiry'];
        }
        if ($hasPermission('quantity-alert')) {
            $reportLinks[] = ['title' => 'Quantity Alert', 'group' => false, 'type' => 'datatable', 'api_url' => '/reports/quantity-alert'];
        }
        if ($hasPermission('purchase-report')) {
            $reportLinks[] = ['title' => 'Purchase Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/purchase-report/create'];
        }
        if ($hasPermission('daily-purchase')) {
            $reportLinks[] = ['title' => 'Daily Purchase', 'group' => false, 'type' => 'custom', 'api_url' => '/reports/daily-purchase/' . date('Y') . '/' . date('m')];
        }
        if ($hasPermission('monthly-purchase')) {
            $reportLinks[] = ['title' => 'Monthly Purchase', 'group' => false, 'type' => 'custom', 'api_url' => '/reports/monthly-purchase/' . date('Y')];
        }
        if ($hasPermission('dso-report')) {
            $reportLinks[] = ['title' => 'DSO Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/dso-report/create'];
        }
        if ($hasPermission('user-report')) {
            $reportLinks[] = ['title' => 'User Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/user-report/create'];
        }
        if ($hasPermission('biller-report')) {
            $reportLinks[] = ['title' => 'Biller Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/biller-report/create'];
        }
        if ($hasPermission('customer-report')) {
            $reportLinks[] = ['title' => 'Customer Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/customer-report/create'];
        }
        if ($hasPermission('supplier-report')) {
            $reportLinks[] = ['title' => 'Supplier Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/supplier-report/create'];
        }
        if ($hasPermission('due-report')) {
            $reportLinks[] = ['title' => 'Due Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/due-report/create'];
        }
        if ($hasPermission('warehouse-report')) {
            $reportLinks[] = ['title' => 'Warehouse Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/warehouse-report/create'];
        }
        if ($hasPermission('warehouse-stock')) {
            $reportLinks[] = ['title' => 'Warehouse Stock Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/warehouse-stock/create'];
        }
        if ($hasPermission('customer-group-report')) {
            $reportLinks[] = ['title' => 'Customer Group Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/customer-group-report/create'];
        }
        if ($hasPermission('challan-report')) {
            $reportLinks[] = ['title' => 'Challan Report', 'group' => false, 'type' => 'form', 'api_url' => '/reports/challan-report/create'];
        }

        if (!empty($reportLinks)) {
            $drawer[] = [
                'title' => 'Reports',
                'icon' => 'reports',
                'group' => true,
                'links' => $reportLinks,
            ];
        }

        // Settings section - Always visible but some items permission-based
        $settingsLinks = [
            [
                'title' => 'Theme Settings',
                'icon' => 'theming',
                'group' => false,
                'type' => 'custom',
                'api_url' => '/theme-setting',
            ],
        ];

        if ($hasPermission('role')) {
            $settingsLinks[] = ['title' => 'Role Permission', 'group' => false, 'type' => 'datatable', 'api_url' => '/roles'];
        }
        if ($hasPermission('sms_template')) {
            $settingsLinks[] = ['title' => 'SMS Template', 'group' => false, 'type' => 'datatable', 'api_url' => '/sms-templates'];
        }
        if ($hasPermission('custom_field')) {
            $settingsLinks[] = ['title' => 'Custom Field List', 'group' => false, 'type' => 'datatable', 'api_url' => '/custom-fields'];
        }
        if ($hasPermission('discount_plan')) {
            $settingsLinks[] = ['title' => 'Discount Plan', 'group' => false, 'type' => 'datatable', 'api_url' => '/discount-plans'];
        }
        if ($hasPermission('discount')) {
            $settingsLinks[] = ['title' => 'Discount', 'group' => false, 'type' => 'datatable', 'api_url' => '/discounts'];
        }
        if ($hasPermission('notification')) {
            $settingsLinks[] = ['title' => 'All Notification', 'group' => false, 'type' => 'datatable', 'api_url' => '/notifications'];
            $settingsLinks[] = ['title' => 'Send Notification', 'group' => false, 'type' => 'form', 'api_url' => '/notifications/create'];
        }
        if ($hasPermission('warehouse')) {
            $settingsLinks[] = ['title' => 'Warehouse', 'group' => false, 'type' => 'datatable', 'api_url' => '/warehouses'];
        }
        if (in_array('restaurant', explode(',', config('addons', '')))) {
            $settingsLinks[] = ['title' => 'Tables', 'group' => false, 'type' => 'datatable', 'api_url' => '/tables'];
        }
        if ($hasPermission('customer_group')) {
            $settingsLinks[] = ['title' => 'Customer Group', 'group' => false, 'type' => 'datatable', 'api_url' => '/customer-groups'];
        }
        if ($hasPermission('currency')) {
            $settingsLinks[] = ['title' => 'Currency', 'group' => false, 'type' => 'datatable', 'api_url' => '/currencies'];
        }
        if ($hasPermission('tax')) {
            $settingsLinks[] = ['title' => 'Tax', 'group' => false, 'type' => 'datatable', 'api_url' => '/taxes'];
        }

        // User Profile - always visible
        $settingsLinks[] = [
            'title' => 'User Profile',
            'group' => true,
            'links' => [
                ['title' => 'Update Profile', 'group' => false, 'type' => 'form', 'api_url' => '/profile/update'],
                ['title' => 'Update Passwords', 'group' => false, 'type' => 'form', 'api_url' => '/profile/update-password'],
            ],
        ];

        if ($hasPermission('send_sms')) {
            $settingsLinks[] = ['title' => 'Create SMS', 'group' => false, 'type' => 'form', 'api_url' => '/sms/create'];
        }
        if ($hasPermission('general_setting')) {
            $settingsLinks[] = ['title' => 'General Settings', 'group' => false, 'type' => 'form', 'api_url' => '/general-setting'];
        }
        if ($hasPermission('mail_setting')) {
            $settingsLinks[] = ['title' => 'Mail Settings', 'group' => false, 'type' => 'form', 'api_url' => '/mail-setting'];
        }
        if ($hasPermission('reward_point_setting')) {
            $settingsLinks[] = ['title' => 'Reward Point Settings', 'group' => false, 'type' => 'form', 'api_url' => '/reward-point-setting'];
        }
        if ($hasPermission('sms_setting')) {
            $settingsLinks[] = ['title' => 'SMS Settings', 'group' => false, 'type' => 'form', 'api_url' => '/sms-setting'];
        }
        if ($hasPermission('general_setting')) {
            $settingsLinks[] = ['title' => 'Payment Gateways', 'group' => false, 'type' => 'form', 'api_url' => '/payment-gateway-setting'];
            $settingsLinks[] = ['title' => 'POS Settings', 'group' => false, 'type' => 'form', 'api_url' => '/pos-setting'];
            $settingsLinks[] = ['title' => 'HRM Settings', 'group' => false, 'type' => 'form', 'api_url' => '/hrm-setting'];
            $settingsLinks[] = ['title' => 'Invoice Settings', 'group' => false, 'type' => 'datatable', 'api_url' => '/invoice-settings'];
        }
        if ($hasPermission('barcode_setting')) {
            $settingsLinks[] = ['title' => 'Barcode Settings', 'group' => false, 'type' => 'datatable', 'api_url' => '/barcodes'];
        }
        if ($hasPermission('general_setting')) {
            $settingsLinks[] = ['title' => 'Languages', 'group' => false, 'type' => 'datatable', 'api_url' => '/languages'];
        }

        $drawer[] = [
            'title' => 'Settings',
            'icon' => 'settings',
            'group' => true,
            'links' => $settingsLinks,
        ];

        // Logout - always visible
        $drawer[] = [
            'title' => 'Logout',
            'icon' => 'logout',
            'group' => false,
            'action' => 'logout',
        ];

        $themeSetting = $this->activeThemeSetting('app');

        $sidebarCorner = $themeSetting?->sidebar_corner;
        if (!in_array($sidebarCorner, ['rounded', 'none'], true)) {
            $sidebarCorner = 'rounded';
        }

        $sidebarStyle = $themeSetting?->sidebar_style;
        if (!in_array($sidebarStyle, ['normal', 'floating'], true)) {
            $sidebarStyle = 'normal';
        }

        $sidebarColors = [
            'sidebar_item_inactive_color' => $this->normalizeHexColor($themeSetting?->sidebar_item_inactive_color),
            'sidebar_item_active_color' => $this->normalizeHexColor($themeSetting?->sidebar_item_active_color),
            'sidebar_item_inactive_dark_color' => $this->normalizeHexColor($themeSetting?->sidebar_item_inactive_dark_color),
            'sidebar_item_active_dark_color' => $this->normalizeHexColor($themeSetting?->sidebar_item_active_dark_color),
            'sidebar_subitem_inactive_color' => $this->normalizeHexColor($themeSetting?->sidebar_subitem_inactive_color),
            'sidebar_subitem_active_color' => $this->normalizeHexColor($themeSetting?->sidebar_subitem_active_color),
            'sidebar_subitem_inactive_dark_color' => $this->normalizeHexColor($themeSetting?->sidebar_subitem_inactive_dark_color),
            'sidebar_subitem_active_dark_color' => $this->normalizeHexColor($themeSetting?->sidebar_subitem_active_dark_color),
        ];

        return [
            'sidebar_style' => $sidebarStyle,
            'sidebar_corner' => $sidebarCorner,
            'drawer' => $drawer,
        ] + array_filter($sidebarColors, static fn($v) => $v !== null);
    }

    public function generalSetting()
    {
        return $general_setting =  Cache::remember('general_setting', 60 * 60 * 24 * 365, function () {
            return DB::table('general_settings')->latest()->first();
        });
    }

    public function getUser(Request $request)
    {
        if (auth()->check()) {


            // Build permission-based drawer
            $drawer = $this->buildDrawer();
            $user = Auth::user();
            $active = ActiveThemeSetting::where('user_id', $user->id)
                ->where('device', 'app')
                ->latest()
                ->first();

            if ($active && $active->theme_id) {
                $currentTheme = ThemeSetting::active('app')
                    ->where('id', $active->theme_id)
                    ->first();
            }

            return response()->json([
                'drawer' => $drawer,
                'current_theme_setting' => $currentTheme ?? $this->activeThemeSetting('app'),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token is invalid or expired.',
        ], 401);
    }

    /**
     * Dynamic Statistics Dashboard - Returns widget-based structure
     * Similar to custom_view_screen for maximum flexibility
     */
    public function dashboard()
    {
        try {
            config()->set('database.connections.mysql.strict', false);
            DB::reconnect();

            $user = Auth::user();
            $role = Role::find($user->role_id);
            $general_setting = Cache::remember('general_setting', 60 * 60 * 24 * 365, function () {
                return DB::table('general_settings')->latest()->first();
            });

            // Calculate date ranges
            $start_date = date("Y-m-01");
            $end_date = date("Y-m-t");

            // Calculate statistics
            $stats = $this->calculateDashboardStats($user, $start_date, $end_date);

            // Calculate chart data for cash flow (last 6 months)
            $payment_recieved = [];
            $payment_sent = [];
            $month = [];
            $start = strtotime(date('Y-m-01', strtotime('-6 month', strtotime(date('Y-m-d')))));
            $end = strtotime(date('Y-m-' . date('t', mktime(0, 0, 0, date("m"), 1, date("Y")))));

            while ($start < $end) {
                $chart_start_date = date("Y-m", $start) . '-' . '01';
                $chart_end_date = date("Y-m", $start) . '-' . date('t', mktime(0, 0, 0, date("m", $start), 1, date("Y", $start)));

                if ($user->role_id > 2 && $general_setting->staff_access == 'own') {
                    $recieved_amount = DB::table('payments')->whereNotNull('sale_id')->whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('amount');
                    $sent_amount = DB::table('payments')->whereNotNull('purchase_id')->whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('amount');
                    $return_amount = Returns::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('grand_total');
                    $purchase_return_amount = ReturnPurchase::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('grand_total');
                    $expense_amount = Expense::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('amount');
                    $payroll_amount = Payroll::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('amount');
                } else {
                    $recieved_amount = DB::table('payments')->whereNotNull('sale_id')->whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('amount');
                    $sent_amount = DB::table('payments')->whereNotNull('purchase_id')->whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('amount');
                    $return_amount = Returns::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('grand_total');
                    $purchase_return_amount = ReturnPurchase::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('grand_total');
                    $expense_amount = Expense::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('amount');
                    $payroll_amount = Payroll::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('amount');
                }
                $sent_amount = $sent_amount + $return_amount + $expense_amount + $payroll_amount;

                $payment_recieved[] = number_format((float)($recieved_amount + $purchase_return_amount), $general_setting->decimal, '.', '');
                $payment_sent[] = number_format((float)$sent_amount, $general_setting->decimal, '.', '');
                $month[] = date("F", strtotime($chart_start_date));
                $start = strtotime("+1 month", $start);
            }

            // Calculate yearly sale and purchase chart data
            $yearly_sale_amount = [];
            $yearly_purchase_amount = [];
            $start = strtotime(date("Y") . '-01-01');
            $end = strtotime(date("Y") . '-12-31');
            while ($start < $end) {
                $chart_start_date = date("Y") . '-' . date('m', $start) . '-' . '01';
                $chart_end_date = date("Y") . '-' . date('m', $start) . '-' . date('t', mktime(0, 0, 0, date("m", $start), 1, date("Y", $start)));
                if ($user->role_id > 2 && $general_setting->staff_access == 'own') {
                    $sale_amount = Sale::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('grand_total');
                    $purchase_amount = Purchase::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->where('user_id', $user->id)->sum('grand_total');
                } else {
                    $sale_amount = Sale::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('grand_total');
                    $purchase_amount = Purchase::whereDate('created_at', '>=', $chart_start_date)->whereDate('created_at', '<=', $chart_end_date)->sum('grand_total');
                }
                $yearly_sale_amount[] = number_format((float)$sale_amount, $general_setting->decimal, '.', '');
                $yearly_purchase_amount[] = number_format((float)$purchase_amount, $general_setting->decimal, '.', '');
                $start = strtotime("+1 month", $start);
            }

            // Build dynamic widgets
            $widgets = [];

            $widgetColors = $this->resolveDashboardWidgetColors();

            // Stat Cards Widget
            $widgets[] = [
                'type' => 'stat_cards',
                'cards' => [
                    [
                        'title' => 'Revenue',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($stats['revenue'], $general_setting->decimal)
                            : number_format($stats['revenue'], $general_setting->decimal) . ' ' . config('currency'),
                        'icon' => 'chartBar',
                        'color' => $widgetColors['stat_revenue'],
                    ],
                    [
                        'title' => 'Profit',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($stats['profit'], $general_setting->decimal)
                            : number_format($stats['profit'], $general_setting->decimal) . ' ' . config('currency'),
                        'icon' => 'trophy',
                        'color' => $widgetColors['stat_profit'],
                    ],
                    [
                        'title' => 'Sale Return',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($stats['return'], $general_setting->decimal)
                            : number_format($stats['return'], $general_setting->decimal) . ' ' . config('currency'),
                        'icon' => 'forward',
                        'color' => $widgetColors['stat_sale_return'],
                    ],
                    [
                        'title' => 'Purchase Return',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($stats['purchase_return'], $general_setting->decimal)
                            : number_format($stats['purchase_return'], $general_setting->decimal) . ' ' . config('currency'),
                        'icon' => 'backward',
                        'color' => $widgetColors['stat_purchase_return'],
                    ],
                ],
            ];

            // Cash Flow Chart Widget
            $widgets[] = [
                'type' => 'line_chart',
                'title' => 'Cash Flow',
                'data' => [
                    'labels' => $month,
                    'datasets' => [
                        [
                            'label' => 'Payment Received',
                            'data' => $payment_recieved,
                            'borderColor' => $widgetColors['line_received'],
                        ],
                        [
                            'label' => 'Payment Sent',
                            'data' => $payment_sent,
                            'borderColor' => $widgetColors['line_sent'],
                        ],
                    ],
                ],
            ];

            // Monthly Summary Pie Chart Widget
            $widgets[] = [
                'type' => 'pie_chart',
                'title' => date('F') . ' ' . date('Y'),
                'data' => [
                    'labels' => ['Purchase', 'Revenue', 'Expense'],
                    'datasets' => [
                        [
                            'data' => [
                                number_format((float)$stats['purchase'], $general_setting->decimal, '.', ''),
                                number_format((float)$stats['revenue'], $general_setting->decimal, '.', ''),
                                number_format((float)$stats['expense'], $general_setting->decimal, '.', ''),
                            ],
                            'backgroundColor' => [
                                $widgetColors['pie_purchase'], // Purchase
                                $widgetColors['pie_revenue'], // Revenue
                                $widgetColors['pie_expense'], // Expense
                            ],
                        ],
                    ],
                ],
            ];

            // Yearly Sale/Purchase Bar Chart Widget
            $widgets[] = [
                'type' => 'bar_chart',
                'title' => 'Sale & Purchase - ' . date('Y'),
                'data' => [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'datasets' => [
                        [
                            'label' => 'Sold Amount',
                            'data' => $yearly_sale_amount,
                            'backgroundColor' => $widgetColors['bar_sold'],
                        ],
                        [
                            'label' => 'Purchased Amount',
                            'data' => $yearly_purchase_amount,
                            'backgroundColor' => $widgetColors['bar_purchased'],
                        ],
                    ],
                ],
            ];

            // Recent Transactions Tabs Widget
            $widgets[] = [
                'type' => 'tabbed_table',
                'title' => 'Recent Transactions',
                'tabs' => [
                    [
                        'title' => 'Sales',
                        'columns' => [
                            ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                            ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                            ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                            ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                            ['label' => 'Total', 'field' => 'grand_total', 'type' => 'text'],
                        ],
                        'rows' => $this->recentSale()->toArray(),
                        'row_height' => 4,
                    ],
                    [
                        'title' => 'Purchases',
                        'columns' => [
                            ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                            ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                            ['label' => 'Supplier', 'field' => 'supplier', 'type' => 'text'],
                            ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                            ['label' => 'Total', 'field' => 'grand_total', 'type' => 'text'],
                        ],
                        'rows' => $this->recentPurchase()->toArray(),
                        'row_height' => 4,
                    ],
                    [
                        'title' => 'Quotations',
                        'columns' => [
                            ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                            ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                            ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                            ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                            ['label' => 'Total', 'field' => 'grand_total', 'type' => 'text'],
                        ],
                        'rows' => $this->recentQuotation()->toArray(),
                        'row_height' => 4,
                    ],
                    [
                        'title' => 'Payments',
                        'columns' => [
                            ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                            ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                            ['label' => 'Sale Ref', 'field' => 'sale_reference', 'type' => 'text'],
                            ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                        ],
                        'rows' => $this->recentPayment()->toArray(),
                        'row_height' => 4,
                    ],
                ],
            ];

            // Best Seller Tables
            $widgets[] = [
                'type' => 'table',
                'title' => 'Best Seller ' . date('F'),
                'columns' => [
                    ['label' => 'Product', 'field' => 'product', 'type' => 'text'],
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Qty', 'field' => 'qty', 'type' => 'text'],
                ],
                'rows' => $this->monthlyBestSellingQty()->map(function ($item) {
                    return [
                        'product' => $item['name'],
                        'code' => $item['code'],
                        'qty' => $item['qty'],
                        'image' => $item['image'],
                    ];
                })->toArray(),
                'row_height' => 4,
            ];

            $widgets[] = [
                'type' => 'table',
                'title' => 'Best Seller ' . date('Y') . ' (Qty)',
                'columns' => [
                    ['label' => 'Product', 'field' => 'product', 'type' => 'text'],
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Qty', 'field' => 'qty', 'type' => 'text'],
                ],
                'rows' => $this->yearlyBestSellingQty()->map(function ($item) {
                    return [
                        'product' => $item['name'],
                        'code' => $item['code'],
                        'qty' => $item['qty'],
                        'image' => $item['image'],
                    ];
                })->toArray(),
                'row_height' => 4,
            ];

            $widgets[] = [
                'type' => 'table',
                'title' => 'Best Seller ' . date('Y') . ' (Price)',
                'columns' => [
                    ['label' => 'Product', 'field' => 'product', 'type' => 'text'],
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Total', 'field' => 'grand_total', 'type' => 'text'],
                ],
                'rows' => $this->yearlyBestSellingPrice()->map(function ($item) {
                    return [
                        'product' => $item['name'],
                        'code' => $item['code'],
                        'grand_total' => $item['grand_total'],
                        'image' => $item['image'],
                    ];
                })->toArray(),
                'row_height' => 4,
            ];

            config()->set('database.connections.mysql.strict', true);
            DB::reconnect();

            return response()->json($this->withDashBackground([
                'success' => true,
                'view_type' => 'statistics',
                'title' => 'Welcome ' . $user->name,
                'widgets' => $widgets,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 'app'));
        } catch (\Exception $e) {
            return response()->json($this->withDashBackground([
                'success' => false,
                'message' => 'An error occurred while loading the statistics.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 'app'), 500);
        }
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStats($user, $start_date, $end_date)
    {
        if ($user->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $product_sale_data = Sale::join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                ->select(DB::raw('product_sales.product_id, product_sales.product_batch_id, product_sales.sale_unit_id, sum(product_sales.qty) as sold_qty, sum(product_sales.return_qty) as return_qty, sum(product_sales.total) as sold_amount'))
                ->where('sales.user_id', $user->id)
                ->whereDate('sales.created_at', '>=', $start_date)
                ->whereDate('sales.created_at', '<=', $end_date)
                ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')
                ->get();
            $product_cost = $this->calculateAverageCOGS($product_sale_data);
            $revenue = Sale::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum(DB::raw('grand_total - shipping_cost'));
            $return = Returns::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $purchase_return = ReturnPurchase::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $expense = Expense::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum('amount');
            $income = Income::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum('amount');
            $purchase = Purchase::whereDate('created_at', '>=', $start_date)->where('user_id', $user->id)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $revenue = $revenue - $return + $income;
            $profit = $revenue + $purchase_return - $product_cost - $expense;
        } else {
            $product_sale_data = Product_Sale::join('sales', 'product_sales.sale_id', '=', 'sales.id')
                ->select(DB::raw('product_sales.product_id, product_sales.product_batch_id, product_sales.sale_unit_id, sum(product_sales.qty) as sold_qty, sum(product_sales.return_qty) as return_qty, sum(product_sales.total) as sold_amount'))
                ->whereDate('sales.created_at', '>=', $start_date)
                ->whereDate('sales.created_at', '<=', $end_date)
                ->groupBy('product_sales.product_id', 'product_sales.product_batch_id')
                ->get();
            $product_cost = $this->calculateAverageCOGS($product_sale_data);
            $revenue = Sale::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum(DB::raw('grand_total - shipping_cost'));
            $expense = Expense::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');
            $income = Income::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amount');
            $return = Returns::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $purchase_return = ReturnPurchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $purchase = Purchase::whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('grand_total');
            $revenue = $revenue - $return + $income;
            $profit = $revenue + $purchase_return - $product_cost - $expense;
        }

        return [
            'revenue' => $revenue,
            'profit' => $profit,
            'return' => $return,
            'purchase_return' => $purchase_return,
            'expense' => $expense,
            'purchase' => $purchase,
        ];
    }

    public function recentSale()
    {
        //get general setting value
        $general_setting = $this->generalSetting();

        if (Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $recent_sales = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')->select('sales.id', 'sales.reference_no', 'sales.sale_status', 'sales.created_at', 'sales.grand_total', 'sales.user_id', 'customers.name')->orderBy('id', 'desc')->where('sales.user_id', Auth::id())->take(5)->get();
        } else {
            $recent_sales = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')->select('sales.id', 'sales.reference_no', 'sales.sale_status', 'sales.created_at', 'sales.grand_total', 'customers.name')->orderBy('id', 'desc')->take(5)->get();
        }

        $formatted_sales = $recent_sales->map(function ($sale) use ($general_setting) {
            if ($sale->sale_status == 1)
                $status = 'Completed';
            if ($sale->sale_status == 2)
                $status = 'Pending';
            else
                $status = 'Draft';
            return [
                'id' => $sale->id,
                'date' => date($general_setting->date_format, strtotime($sale->created_at)),
                'reference_no' => $sale->reference_no,
                'customer' => $sale->name,
                'status' => $status,
                'grand_total' => number_format((float)$sale->grand_total, $general_setting->decimal, '.', ''), // format grand total
            ];
        });

        return $formatted_sales;
    }

    public function recentPurchase()
    {
        $general_setting = $this->generalSetting();

        if (Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $recent_purchases = Purchase::leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')->select('purchases.id', 'purchases.reference_no', 'purchases.status', 'purchases.created_at', 'purchases.grand_total', 'purchases.user_id', 'suppliers.name')->orderBy('id', 'desc')->where('purchases.user_id', Auth::id())->take(5)->get();
        } else {
            $recent_purchases = Purchase::leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')->select('purchases.id', 'purchases.reference_no', 'purchases.status', 'purchases.created_at', 'purchases.grand_total', 'suppliers.name')->orderBy('id', 'desc')->take(5)->get();
        }

        $formatted_purchases = $recent_purchases->map(function ($purchase) use ($general_setting) {
            if ($purchase->status == 1)
                $status = 'Recieved';
            if ($purchase->status == 2)
                $status = 'Partial';
            if ($purchase->status == 3)
                $status = 'Pending';
            else
                $status = 'Ordered';
            return [
                'id' => $purchase->id,
                'date' => date($general_setting->date_format, strtotime($purchase->created_at)),
                'reference_no' => $purchase->reference_no,
                'supplier' => $purchase->name,
                'status' => $status,
                'grand_total' => number_format((float)$purchase->grand_total, $general_setting->decimal, '.', ''), // format grand total
            ];
        });

        return $formatted_purchases;
    }

    public function recentQuotation()
    {
        $general_setting = $this->generalSetting();

        if (Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $recent_quotations = Quotation::join('customers', 'customers.id', '=', 'quotations.customer_id')->select('quotations.id', 'quotations.reference_no', 'quotations.quotation_status', 'quotations.created_at', 'quotations.grand_total', 'quotations.user_id', 'customers.name')->orderBy('id', 'desc')->where('quotations.user_id', Auth::id())->take(5)->get();
        } else {
            $recent_quotations = Quotation::join('customers', 'customers.id', '=', 'quotations.customer_id')->select('quotations.id', 'quotations.reference_no', 'quotations.quotation_status', 'quotations.created_at', 'quotations.grand_total', 'customers.name')->orderBy('id', 'desc')->take(5)->get();
        }

        $quotations = $recent_quotations->map(function ($quotation) use ($general_setting) {
            if ($quotation->quotation_status == 1)
                $status = 'Pending';
            else if ($quotation->quotation_status == 2)
                $status = 'Sent';

            return [
                'id' => $quotation->id,
                'date' => date($general_setting->date_format, strtotime($quotation->created_at)),
                'reference_no' => $quotation->reference_no,
                'customer' => $quotation->name,
                'status' => $status,
                'grand_total' => number_format((float)$quotation->grand_total, $general_setting->decimal, '.', ''), // format grand total
            ];
        });

        return $quotations;
    }

    public function recentPayment()
    {
        $general_setting = $this->generalSetting();

        if (Auth::user()->role_id > 2 && cache()->get('general_setting')->staff_access == 'own') {
            $recent_payments = Payment::select('id', 'payment_reference', 'amount', 'paying_method', 'created_at', 'user_id')->orderBy('id', 'desc')->where('user_id', Auth::id())->take(5)->get();
        } else {
            $recent_payments = Payment::select('id', 'payment_reference', 'amount', 'paying_method', 'created_at')->orderBy('id', 'desc')->take(5)->get();
        }

        $payments = $recent_payments->map(function ($payment) use ($general_setting) {

            return [
                'id' => $payment->id,
                'date' => date($general_setting->date_format, strtotime($payment->created_at)),
                'reference_no' => $payment->payment_reference,
                'amount' => number_format((float)$payment->amount, $general_setting->decimal, '.', ''), // format grand total
                'paid_by' => $payment->paying_method
            ];
        });

        return $payments;
    }

    public function monthlyBestSellingQty()
    {
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $start_date = date("Y") . '-' . date("m") . '-' . '01';
        $end_date = date("Y") . '-' . date("m") . '-' . date('t', mktime(0, 0, 0, date("m"), 1, date("Y")));
        $best_selling_qty = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
            ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images, sum(product_sales.qty) as sold_qty'))
            ->whereDate('product_sales.created_at', '>=', $start_date)
            ->whereDate('product_sales.created_at', '<=', $end_date)
            ->groupBy('products.code')
            ->orderBy('sold_qty', 'desc')
            ->take(5)
            ->get();

        // return $best_selling_qty;

        // $bests = $best_selling_qty->map(function ($best) {

        //     return [
        //         'name' => $best->product_name,
        //         'code' => $best->product_code,
        //         'image' => url('images/product/' . $best->product_images),
        //         'qty' => $best->sold_qty,
        //     ];
        // });
        $bests = $best_selling_qty->map(function ($best) {
            // Get the first image only
            $images = explode(',', $best->product_images);
            $firstImage = trim($images[0]); // remove extra space if any

            return [
                'name' => $best->product_name,
                'code' => $best->product_code,
                'image' => url('images/product/' . $firstImage),
                'qty' => $best->sold_qty,
            ];
        });
        return $bests;
    }

    public function yearlyBestSellingPrice()
    {
        $general_setting = $this->generalSetting();
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $yearly_best_selling_price = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
            ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images, sum(total) as total_price'))
            ->whereDate('product_sales.created_at', '>=', date("Y") . '-01-01')
            ->whereDate('product_sales.created_at', '<=', date("Y") . '-12-31')
            ->groupBy('products.code')
            ->orderBy('total_price', 'desc')
            ->take(5)
            ->get();

        //  $bests = $yearly_best_selling_price->map(function ($best)  use ($general_setting) {

        //     return [
        //         'name' => $best->product_name,
        //         'code' => $best->product_code,
        //         'image' => url('images/product/' . $best->product_images),
        //         'grand_total' => number_format((float)$best->total_price,$general_setting->decimal, '.', ''),
        //     ];
        // });

        // $bests = $yearly_best_selling_price->map(function ($best) use ($general_setting) {
        //     // Split and take the first image from the comma-separated list
        //     $images = explode(',', $best->product_images);
        //     $firstImage = trim($images[0]);

        //     return [
        //         'name' => $best->product_name,
        //         'code' => $best->product_code,
        //         'image' => url('images/product/' . $firstImage),
        //         'grand_total' => number_format((float)$best->total_price, $general_setting->decimal, '.', ''),
        //     ];
        // });

        $bests = $yearly_best_selling_price->map(function ($best) use ($general_setting) {
            // Split and take the first image from the comma-separated list
            $images = explode(',', $best->product_images);
            $firstImage = trim($images[0]);

            // Check if the image file exists
            $imagePath = public_path('images/product/' . $firstImage);
            $finalImage = file_exists($imagePath)
                ? url('images/product/' . $firstImage)
                : url('images/zummXD2dvAtI.png');

            return [
                'name' => $best->product_name,
                'code' => $best->product_code,
                'image' => $finalImage,
                'grand_total' => number_format((float)$best->total_price, $general_setting->decimal, '.', ''),
            ];
        });

        return $bests;
    }

    public function yearlyBestSellingQty()
    {
        //making strict mode false for this query
        config()->set('database.connections.mysql.strict', false);
        DB::reconnect();
        $yearly_best_selling_qty = Product_Sale::join('products', 'products.id', '=', 'product_sales.product_id')
            ->select(DB::raw('products.name as product_name, products.code as product_code, products.image as product_images, sum(product_sales.qty) as sold_qty'))
            ->whereDate('product_sales.created_at', '>=', date("Y") . '-01-01')
            ->whereDate('product_sales.created_at', '<=', date("Y") . '-12-31')
            ->groupBy('products.code')
            ->orderBy('sold_qty', 'desc')
            ->take(5)
            ->get();

        // $bests = $yearly_best_selling_qty->map(function ($best) {
        //     return [
        //         'name' => $best->product_name,
        //         'code' => $best->product_code,
        //         'image' => url('images/product/' . $best->product_images),
        //         'qty' => $best->sold_qty,
        //     ];
        // });
        // $bests = $yearly_best_selling_qty->map(function ($best) {
        //     // Extract the first image from a comma-separated list
        //     $images = explode(',', $best->product_images);
        //     $firstImage = trim($images[0]);

        //     return [
        //         'name' => $best->product_name,
        //         'code' => $best->product_code,
        //         'image' => url('images/product/' . $firstImage),
        //         'qty' => $best->sold_qty,
        //     ];
        // });

        $bests = $yearly_best_selling_qty->map(function ($best) {
            // Get first image
            $images = explode(',', $best->product_images);
            $firstImage = trim($images[0]);

            // Path to check file existence
            $imagePath = public_path('images/product/' . $firstImage);

            // Use default image if the file doesn't exist
            $finalImage = file_exists($imagePath)
                ? url('images/product/' . $firstImage)
                : url('images/zummXD2dvAtI.png');

            return [
                'name' => $best->product_name,
                'code' => $best->product_code,
                'image' => $finalImage,
                'qty' => $best->sold_qty,
            ];
        });


        return $bests;
    }

    public function calculateAverageCOGS($product_sale_data)
    {
        $product_cost = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            $product_data = Product::select('type', 'product_list', 'variant_list', 'qty_list')->find($product_sale->product_id);
            if ($product_data && $product_data->type == 'combo') {
                $product_list = explode(",", $product_data->product_list);
                if ($product_data->variant_list)
                    $variant_list = explode(",", $product_data->variant_list);
                else
                    $variant_list = [];
                $qty_list = explode(",", $product_data->qty_list);

                foreach ($product_list as $index => $product_id) {
                    if (count($variant_list) && $variant_list[$index]) {
                        $product_purchase_data = ProductPurchase::where([
                            ['product_id', $product_id],
                            ['variant_id', $variant_list[$index]]
                        ])
                            ->select('recieved', 'purchase_unit_id', 'total')
                            ->get();
                    } else {
                        $product_purchase_data = ProductPurchase::where('product_id', $product_id)
                            ->select('recieved', 'purchase_unit_id', 'total')
                            ->get();
                    }
                    $total_received_qty = 0;
                    $total_purchased_amount = 0;
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $qty_list[$index];
                    $units = Unit::select('id', 'operator', 'operation_value')->get();
                    foreach ($product_purchase_data as $key => $product_purchase) {
                        $purchase_unit_data = $units->where('id', $product_purchase->purchase_unit_id)->first();
                        if ($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;
                        $total_purchased_amount += $product_purchase->total;
                    }
                    if ($total_received_qty)
                        $averageCost = $total_purchased_amount / $total_received_qty;
                    else
                        $averageCost = 0;
                    $product_cost += $sold_qty * $averageCost;
                }
            } else {
                if ($product_sale->product_batch_id) {
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['product_batch_id', $product_sale->product_batch_id]
                    ])
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                } elseif ($product_sale->variant_id) {
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['variant_id', $product_sale->variant_id]
                    ])
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                } else {
                    $product_purchase_data = ProductPurchase::where('product_id', $product_sale->product_id)
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                }
                $total_received_qty = 0;
                $total_purchased_amount = 0;
                $units = Unit::select('id', 'operator', 'operation_value')->get();
                if ($product_sale->sale_unit_id) {
                    $sale_unit_data = $units->where('id', $product_sale->sale_unit_id)->first();
                    if ($sale_unit_data->operator == '*')
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) * $sale_unit_data->operation_value;
                    else
                        $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty) / $sale_unit_data->operation_value;
                } else {
                    $sold_qty = ($product_sale->sold_qty - $product_sale->return_qty);
                }
                foreach ($product_purchase_data as $key => $product_purchase) {
                    $purchase_unit_data = $units->where('id', $product_purchase->purchase_unit_id)->first();
                    if ($purchase_unit_data) {
                        if ($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;
                        $total_purchased_amount += $product_purchase->total;
                    }
                }
                if ($total_received_qty)
                    $averageCost = $total_purchased_amount / $total_received_qty;
                else
                    $averageCost = 0;
                $product_cost += $sold_qty * $averageCost;
            }
        }
        return $product_cost;
    }
}
