<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\ProductPurchase;
use App\Models\CustomerGroup;
use App\Models\Payment;
use App\Models\Challan;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Quotation;
use App\Models\Transfer;
use App\Models\ExpenseCategory;
use App\Models\Payroll;
use App\Models\Account;
use App\Models\MoneyTransfer;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ProvidesThemeBackgrounds;

    /**
     * Sale Report Form
     * Returns form schema for sale report filters
     */
    public function saleReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sale-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = [];
            foreach ($warehouses as $warehouse) {
                $warehouseOptions[] = [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            }

            $formSchema = [
                "title" => "Sales",
                "submit_url" => "/reports/sale-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "options" => $warehouseOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the sale report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Sale Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function saleReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sale-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Sale::where('warehouse_id', $request->warehouse_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No sale data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale report data found successfully.',
                'navigate_url' => '/reports/sale-report/table?warehouse_id=' . $request->warehouse_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the sale report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Purchase Report Form
     * Returns form schema for purchase report filters
     */
    public function purchaseReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = [];
            foreach ($warehouses as $warehouse) {
                $warehouseOptions[] = [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            }

            $formSchema = [
                "title" => "Purchases",
                "submit_url" => "/reports/purchase-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "options" => $warehouseOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the purchase report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Purchase Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function purchaseReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Purchase::where('warehouse_id', $request->warehouse_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No purchase data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase report data found successfully.',
                'navigate_url' => '/reports/purchase-report/table?warehouse_id=' . $request->warehouse_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the purchase report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Customer Report Form
     * Returns form schema for customer report filters
     */
    public function customerReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customers = Customer::where('is_active', true)->get();
            $customerOptions = [];
            foreach ($customers as $customer) {
                $customerOptions[] = [
                    'label' => $customer->name . ' (' . $customer->phone_number . ')',
                    'value' => $customer->id
                ];
            }

            $formSchema = [
                "title" => "Customer",
                "submit_url" => "/reports/customer-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_id",
                        "label" => "Customer",
                        "options" => $customerOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the customer report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Customer Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function customerReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'customer_id' => 'required|integer|exists:customers,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Sale::where('customer_id', $request->customer_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No customer data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer report data found successfully.',
                'navigate_url' => '/reports/customer-report/table?customer_id=' . $request->customer_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the customer report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Supplier Report Form
     * Returns form schema for supplier report filters
     */
    public function supplierReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('supplier-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $suppliers = Supplier::where('is_active', true)->get();
            $supplierOptions = [];
            foreach ($suppliers as $supplier) {
                $supplierOptions[] = [
                    'label' => $supplier->name . ' (' . $supplier->phone_number . ')',
                    'value' => $supplier->id
                ];
            }

            $formSchema = [
                "title" => "Supplier",
                "submit_url" => "/reports/supplier-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "supplier_id",
                        "label" => "Supplier",
                        "options" => $supplierOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the supplier report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Supplier Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function supplierReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('supplier-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'supplier_id' => 'required|integer|exists:suppliers,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Purchase::where('supplier_id', $request->supplier_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No supplier data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Supplier report data found successfully.',
                'navigate_url' => '/reports/supplier-report/table?supplier_id=' . $request->supplier_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the supplier report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Warehouse Report Form
     * Returns form schema for warehouse report filters
     */
    public function warehouseReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('warehouse-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = [];
            foreach ($warehouses as $warehouse) {
                $warehouseOptions[] = [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            }

            $formSchema = [
                "title" => "Warehouse",
                "submit_url" => "/reports/warehouse-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "options" => $warehouseOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the warehouse report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Warehouse Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function warehouseReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('warehouse-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria (sales, purchases, expenses in warehouse)
            $dataExists = Sale::where('warehouse_id', $request->warehouse_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Purchase::where('warehouse_id', $request->warehouse_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Expense::where('warehouse_id', $request->warehouse_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No warehouse data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Warehouse report data found successfully.',
                'navigate_url' => '/reports/warehouse-report/table?warehouse_id=' . $request->warehouse_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the warehouse report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Biller Report Form
     * Returns form schema for biller report filters
     */
    public function billerReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('biller-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $billers = Biller::where('is_active', true)->get();
            $billerOptions = [];
            foreach ($billers as $biller) {
                $billerOptions[] = [
                    'label' => $biller->name . ' (' . $biller->company_name . ')',
                    'value' => $biller->id
                ];
            }

            $formSchema = [
                "title" => "Biller",
                "submit_url" => "/reports/biller-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "biller_id",
                        "label" => "Biller",
                        "options" => $billerOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the biller report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Biller Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function billerReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('biller-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'biller_id' => 'required|integer|exists:billers,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Sale::where('biller_id', $request->biller_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No biller data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Biller report data found successfully.',
                'navigate_url' => '/reports/biller-report/table?biller_id=' . $request->biller_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'tabbeddatatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the biller report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * User Report Form
     * Returns form schema for user report filters
     */
    public function userReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('user-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $users = User::where('is_active', true)->get();
            $userOptions = [];
            foreach ($users as $user) {
                $userOptions[] = [
                    'label' => $user->name . ' (' . $user->email . ')',
                    'value' => $user->id
                ];
            }

            $formSchema = [
                "title" => "User",
                "submit_url" => "/reports/user-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "user_id",
                        "label" => "User",
                        "options" => $userOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the user report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * User Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function userReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('user-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria (user activities like sales, purchases)
            $dataExists = Sale::where('user_id', $request->user_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Purchase::where('user_id', $request->user_id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user activity data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'User report data found successfully.',
                'navigate_url' => '/reports/user-report/table?user_id=' . $request->user_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'tabbeddatatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the user report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Product Report Form
     * Returns form schema for product report filters
     */
    public function productReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('product-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $products = Product::where('is_active', true)->get();
            $productOptions = [];
            foreach ($products as $product) {
                $productOptions[] = [
                    'label' => $product->name . ' (' . $product->code . ')',
                    'value' => $product->id
                ];
            }

            $warehouses = Warehouse::where('is_active', true)->get();
            $warehouseOptions = [];
            foreach ($warehouses as $warehouse) {
                $warehouseOptions[] = [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id
                ];
            }

            $formSchema = [
                "title" => "Product",
                "submit_url" => "/reports/product-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "product_id",
                        "label" => "Product",
                        "options" => $productOptions,
                    ],
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "options" => $warehouseOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the product report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Product Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function productReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('product-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'product_id' => 'required|integer|exists:products,id',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria (product in warehouse during date range)
            $dataExists = Product_Sale::where('product_id', $request->product_id)
                ->whereHas('sale', function ($query) use ($request, $startDate, $endDate) {
                    $query->where('warehouse_id', $request->warehouse_id)
                        ->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate);
                })
                ->exists() ||
                ProductPurchase::where('product_id', $request->product_id)
                ->whereHas('purchase', function ($query) use ($request, $startDate, $endDate) {
                    $query->where('warehouse_id', $request->warehouse_id)
                        ->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate);
                })
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No product data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product report data found successfully.',
                'navigate_url' => '/reports/product-report/table?product_id=' . $request->product_id . '&warehouse_id=' . $request->warehouse_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the product report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Profit/Loss Report Form
     * Returns form schema for profit/loss report filters
     */
    public function profitLossReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('profit-loss')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Profit/Loss",
                "submit_url" => "/reports/profit-loss-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the profit/loss report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Profit/Loss Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function profitLossReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('profit-loss')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria (sales or purchases for profit/loss calculation)
            $dataExists = Sale::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Purchase::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Expense::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists() ||
                Income::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No profit/loss data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profit/loss report data found successfully.',
                'navigate_url' => '/reports/profit-loss-report/table?start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'custom',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the profit/loss report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Customer Group Report Form
     * Returns form schema for customer group report filters
     */
    public function customerGroupReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-group-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customerGroups = CustomerGroup::where('is_active', true)->get();
            $customerGroupOptions = [];
            foreach ($customerGroups as $group) {
                $customerGroupOptions[] = [
                    'label' => $group->name,
                    'value' => $group->id
                ];
            }

            $formSchema = [
                "title" => "Customer Group",
                "submit_url" => "/reports/customer-group-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_group_id",
                        "label" => "Customer Group",
                        "options" => $customerGroupOptions,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the customer group report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Customer Group Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function customerGroupReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-group-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
                'customer_group_id' => 'required|integer|exists:customer_groups,id',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria (sales from customers in this group)
            $dataExists = Sale::whereHas('customer', function ($query) use ($request) {
                $query->where('customer_group_id', $request->customer_group_id);
            })
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No customer group data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer group report data found successfully.',
                'navigate_url' => '/reports/customer-group-report/table?customer_group_id=' . $request->customer_group_id . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the customer group report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Payment Report Form
     * Returns form schema for payment report filters
     */
    public function paymentReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('payment-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Payments",
                "submit_url" => "/reports/payment-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the payment report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Payment Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function paymentReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('payment-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Payment::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment report data found successfully.',
                'navigate_url' => '/reports/payment-report/table?payment_method=' . urlencode($request->payment_method) . '&start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Challan Report Form
     * Returns form schema for challan report filters
     */
    public function challanReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('challan-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Challans",
                "submit_url" => "/reports/challan-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                        "format_specifier" => "yyyy-MM-dd",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the challan report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Challan Report Form Handler
     * Validates form data and checks if report data exists
     */
    public function challanReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('challan-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $request->validate([
                'date_range' => 'required|string',
            ]);

            // Parse date range
            $dateRange = explode(' - ', $request->date_range);
            if (count($dateRange) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date range format.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            // Check if data exists for the given criteria
            $dataExists = Challan::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->exists();

            if (!$dataExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'No challan data found for the selected criteria.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Challan report data found successfully.',
                'navigate_url' => '/reports/challan-report/table?start_date=' . $startDate . '&end_date=' . $endDate,
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the challan report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Best Seller Report - Datatable
     * Returns best selling products
     */
    public function bestSellerReport()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('best-seller')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get last 3 months data (current month + 2 previous months)
            $start = strtotime(date("Y-m", strtotime("-2 months")) . '-01');
            $end = strtotime(date("Y") . '-' . date("m") . '-31');

            $product = [];
            $sold_qty = [];

            while ($start <= $end) {
                $number_of_day = date('t', mktime(0, 0, 0, date('m', $start), 1, date('Y', $start)));
                $start_date = date("Y-m", $start) . '-' . '01';
                $end_date = date("Y-m", $start) . '-' . $number_of_day;

                $best_selling_qty = Product_Sale::selectRaw('product_id, sum(qty) as sold_qty')
                    ->whereDate('created_at', '>=', $start_date)
                    ->whereDate('created_at', '<=', $end_date)
                    ->groupBy('product_id')
                    ->orderBy('sold_qty', 'desc')
                    ->take(1)
                    ->get();

                if (!count($best_selling_qty)) {
                    $product[] = '';
                    $sold_qty[] = 0;
                }

                foreach ($best_selling_qty as $best_seller) {
                    $product_data = Product::find($best_seller->product_id);
                    if ($product_data) {
                        $product[] = $product_data->name . ': ' . $product_data->code;
                        $sold_qty[] = $best_seller->sold_qty;
                    }
                }
                $start = strtotime("+1 month", $start);
            }

            $start_month = date("F Y", strtotime('-2 month'));
            $current_month = date("F Y");

            return response()->json([
                'view_type' => 'chart',
                'chart_type' => 'bar',
                'title' => "Best Sellers",
                'subtitle' => "From $start_month - $current_month",
                'chart_data' => [
                    'labels' => $product,
                    'datasets' => [
                        [
                            'label' => 'Sale Qty',
                            'data' => $sold_qty,
                            'backgroundColor' => [
                                'rgba(115, 54, 134, 0.8)',
                                'rgba(115, 54, 134, 0.8)',
                                'rgba(115, 54, 134, 0.8)',
                            ],
                            'borderColor' => [
                                '#733686',
                                '#733686',
                                '#733686',
                            ],
                            'borderWidth' => 1,
                        ]
                    ]
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the best seller report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Daily Sale Report - Custom View
     * Returns daily sales for a specific month
     */
    public function dailySaleReport($year, $month)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('daily-sale')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get all days data for the month
            $start = 1;
            $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
            $calendar_days = [];

            while ($start <= $number_of_day) {
                if ($start < 10)
                    $date = $year . '-' . $month . '-0' . $start;
                else
                    $date = $year . '-' . $month . '-' . $start;

                $query1 = array(
                    'SUM(total_discount) AS total_discount',
                    'SUM(order_discount) AS order_discount',
                    'SUM(total_tax) AS total_tax',
                    'SUM(order_tax) AS order_tax',
                    'SUM(shipping_cost) AS shipping_cost',
                    'SUM(grand_total) AS grand_total'
                );

                $sale_data = Sale::whereDate('created_at', $date)
                    ->selectRaw(implode(',', $query1))
                    ->get();

                $day_data = [];
                if ($sale_data[0]->total_discount) {
                    $day_data[] = [
                        'label' => 'Product Discount',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->total_discount, 2)
                            : number_format($sale_data[0]->total_discount, 2) . ' ' . config('currency')
                    ];
                }
                if ($sale_data[0]->order_discount) {
                    $day_data[] = [
                        'label' => 'Order Discount',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->order_discount, 2)
                            : number_format($sale_data[0]->order_discount, 2) . ' ' . config('currency')
                    ];
                }
                if ($sale_data[0]->total_tax) {
                    $day_data[] = [
                        'label' => 'Product Tax',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->total_tax, 2)
                            : number_format($sale_data[0]->total_tax, 2) . ' ' . config('currency')
                    ];
                }
                if ($sale_data[0]->order_tax) {
                    $day_data[] = [
                        'label' => 'Order Tax',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->order_tax, 2)
                            : number_format($sale_data[0]->order_tax, 2) . ' ' . config('currency')
                    ];
                }
                if ($sale_data[0]->shipping_cost) {
                    $day_data[] = [
                        'label' => 'Shipping Cost',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->shipping_cost, 2)
                            : number_format($sale_data[0]->shipping_cost, 2) . ' ' . config('currency')
                    ];
                }
                if ($sale_data[0]->grand_total) {
                    $day_data[] = [
                        'label' => 'Grand Total',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($sale_data[0]->grand_total, 2)
                            : number_format($sale_data[0]->grand_total, 2) . ' ' . config('currency')
                    ];
                }

                $calendar_days[$start] = [
                    'day' => (string)$start,
                    'data' => $day_data,
                    'is_today' => ($year . '-' . $month . '-' . $start == date('Y-m-d'))
                ];

                $start++;
            }

            $start_day = date('w', strtotime($year . '-' . $month . '-01')) + 1; // 1 = Sunday, 7 = Saturday
            $prev_year = date('Y', strtotime('-1 month', strtotime($year . '-' . $month . '-01')));
            $prev_month = date('m', strtotime('-1 month', strtotime($year . '-' . $month . '-01')));
            $next_year = date('Y', strtotime('+1 month', strtotime($year . '-' . $month . '-01')));
            $next_month = date('m', strtotime('+1 month', strtotime($year . '-' . $month . '-01')));

            // Convert associative array to indexed array for JSON
            $days_array = array_values($calendar_days);

            return response()->json([
                'view_type' => 'calendar',
                'title' => 'Daily Sale',
                'subtitle' => date("F", strtotime($year . '-' . $month . '-01')) . ' ' . $year,
                'calendar_data' => [
                    'year' => (string)$year,
                    'month' => (string)$month,
                    'month_name' => date("F", strtotime($year . '-' . $month . '-01')),
                    'number_of_days' => (string)$number_of_day,
                    'start_day' => (string)$start_day, // 1-7, where 1 = Sunday
                    'days' => $days_array,
                    'prev_year' => (string)$prev_year,
                    'prev_month' => (string)$prev_month,
                    'next_year' => (string)$next_year,
                    'next_month' => (string)$next_month,
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the daily sale report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Monthly Sale Report - Custom View
     * Returns monthly sales for a specific year
     */
    public function monthlySaleReport($year)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('monthly-sale')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $monthlySales = Sale::whereYear('created_at', $year)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as total_sales, SUM(grand_total) as total_amount')
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->get();

            $months = [];
            $salesCount = [];
            $salesAmount = [];

            foreach ($monthlySales as $item) {
                $months[] = date('F', mktime(0, 0, 0, $item->month, 1));
                $salesCount[] = (int)$item->total_sales;
                $salesAmount[] = (float)$item->total_amount;
            }

            return response()->json([
                'view_type' => 'chart',
                'chart_type' => 'bar',
                'title' => "Monthly Sale - " . $year,
                'subtitle' => "Year " . $year,
                'chart_data' => [
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Total Sales',
                            'data' => $salesCount,
                            'backgroundColor' => array_fill(0, count($months), 'rgba(115, 54, 134, 0.8)'),
                            'borderColor' => array_fill(0, count($months), '#733686'),
                            'borderWidth' => 1,
                        ]
                    ]
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the monthly sale report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Daily Purchase Report - Custom View
     * Returns daily purchases for a specific month
     */
    public function dailyPurchaseReport($year, $month)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('daily-purchase')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get all days data for the month
            $start = 1;
            $number_of_day = date('t', mktime(0, 0, 0, $month, 1, $year));
            $calendar_days = [];

            while ($start <= $number_of_day) {
                if ($start < 10)
                    $date = $year . '-' . $month . '-0' . $start;
                else
                    $date = $year . '-' . $month . '-' . $start;

                $query1 = array(
                    'SUM(total_discount) AS total_discount',
                    'SUM(order_discount) AS order_discount',
                    'SUM(total_tax) AS total_tax',
                    'SUM(order_tax) AS order_tax',
                    'SUM(shipping_cost) AS shipping_cost',
                    'SUM(grand_total) AS grand_total'
                );

                $purchase_data = Purchase::whereDate('created_at', $date)
                    ->selectRaw(implode(',', $query1))
                    ->get();

                $day_data = [];
                if ($purchase_data[0]->total_discount) {
                    $day_data[] = [
                        'label' => 'Product Discount',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->total_discount, 2)
                            : number_format($purchase_data[0]->total_discount, 2) . ' ' . config('currency')
                    ];
                }
                if ($purchase_data[0]->order_discount) {
                    $day_data[] = [
                        'label' => 'Order Discount',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->order_discount, 2)
                            : number_format($purchase_data[0]->order_discount, 2) . ' ' . config('currency')
                    ];
                }
                if ($purchase_data[0]->total_tax) {
                    $day_data[] = [
                        'label' => 'Product Tax',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->total_tax, 2)
                            : number_format($purchase_data[0]->total_tax, 2) . ' ' . config('currency')
                    ];
                }
                if ($purchase_data[0]->order_tax) {
                    $day_data[] = [
                        'label' => 'Order Tax',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->order_tax, 2)
                            : number_format($purchase_data[0]->order_tax, 2) . ' ' . config('currency')
                    ];
                }
                if ($purchase_data[0]->shipping_cost) {
                    $day_data[] = [
                        'label' => 'Shipping Cost',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->shipping_cost, 2)
                            : number_format($purchase_data[0]->shipping_cost, 2) . ' ' . config('currency')
                    ];
                }
                if ($purchase_data[0]->grand_total) {
                    $day_data[] = [
                        'label' => 'Grand Total',
                        'value' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($purchase_data[0]->grand_total, 2)
                            : number_format($purchase_data[0]->grand_total, 2) . ' ' . config('currency')
                    ];
                }

                $calendar_days[$start] = [
                    'day' => (string)$start,
                    'data' => $day_data,
                    'is_today' => ($year . '-' . $month . '-' . $start == date('Y-m-d'))
                ];

                $start++;
            }

            $start_day = date('w', strtotime($year . '-' . $month . '-01')) + 1; // 1 = Sunday, 7 = Saturday
            $prev_year = date('Y', strtotime('-1 month', strtotime($year . '-' . $month . '-01')));
            $prev_month = date('m', strtotime('-1 month', strtotime($year . '-' . $month . '-01')));
            $next_year = date('Y', strtotime('+1 month', strtotime($year . '-' . $month . '-01')));
            $next_month = date('m', strtotime('+1 month', strtotime($year . '-' . $month . '-01')));

            // Convert associative array to indexed array for JSON
            $days_array = array_values($calendar_days);

            return response()->json([
                'view_type' => 'calendar',
                'title' => 'Daily Purchase',
                'subtitle' => date("F", strtotime($year . '-' . $month . '-01')) . ' ' . $year,
                'calendar_data' => [
                    'year' => (string)$year,
                    'month' => (string)$month,
                    'month_name' => date("F", strtotime($year . '-' . $month . '-01')),
                    'number_of_days' => (string)$number_of_day,
                    'start_day' => (string)$start_day, // 1-7, where 1 = Sunday
                    'days' => $days_array,
                    'prev_year' => (string)$prev_year,
                    'prev_month' => (string)$prev_month,
                    'next_year' => (string)$next_year,
                    'next_month' => (string)$next_month,
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the daily purchase report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Monthly Purchase Report - Custom View
     * Returns monthly purchases for a specific year
     */
    public function monthlyPurchaseReport($year)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('monthly-purchase')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $monthlyPurchases = Purchase::whereYear('created_at', $year)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as total_purchases, SUM(grand_total) as total_amount')
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->get();

            $months = [];
            $purchasesCount = [];
            $purchasesAmount = [];

            foreach ($monthlyPurchases as $item) {
                $months[] = date('F', mktime(0, 0, 0, $item->month, 1));
                $purchasesCount[] = (int)$item->total_purchases;
                $purchasesAmount[] = (float)$item->total_amount;
            }

            return response()->json([
                'view_type' => 'chart',
                'chart_type' => 'bar',
                'title' => "Monthly Purchase - " . $year,
                'subtitle' => "Year " . $year,
                'chart_data' => [
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Total Purchases',
                            'data' => $purchasesCount,
                            'backgroundColor' => array_fill(0, count($months), 'rgba(115, 54, 134, 0.8)'),
                            'borderColor' => array_fill(0, count($months), '#733686'),
                            'borderWidth' => 1,
                        ]
                    ]
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the monthly purchase report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Product Expiry Report - Datatable
     * Returns products expiring soon
     */
    public function productExpiryReport()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('product-expiry-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get products expiring in next 30 days
            $expiryProducts = Product::where('is_active', true)
                ->whereNotNull('expired_date')
                ->whereDate('expired_date', '>=', date('Y-m-d'))
                ->whereDate('expired_date', '<=', date('Y-m-d', strtotime('+30 days')))
                ->orderBy('expired_date', 'ASC')
                ->get();

            $expiryRows = $expiryProducts->map(function ($product) {
                $daysToExpiry = round((strtotime($product->expired_date) - time()) / (60 * 60 * 24));
                $status = $daysToExpiry <= 7 ? 'Critical' : ($daysToExpiry <= 15 ? 'Warning' : 'Notice');
                $statusColor = $daysToExpiry <= 7 ? 'red' : ($daysToExpiry <= 15 ? 'orange' : 'blue');

                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'expiry_date' => date('d M, Y', strtotime($product->expired_date)),
                    'days_to_expiry' => $daysToExpiry,
                    'quantity' => $product->qty,
                    'status' => "<span style='color: $statusColor; font-weight: bold;'>$status</span>",
                ];
            });

            return [
                'title' => "Product Expiry",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Product Name', 'field' => 'product_name', 'type' => 'text'],
                    ['label' => 'Product Code', 'field' => 'product_code', 'type' => 'text'],
                    ['label' => 'Expiry Date', 'field' => 'expiry_date', 'type' => 'text'],
                    ['label' => 'Days to Expiry', 'field' => 'days_to_expiry', 'type' => 'text'],
                    ['label' => 'Quantity', 'field' => 'quantity', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                ],
                'rows' => $expiryRows,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the product expiry report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Quantity Alert Report - Datatable
     * Returns products below alert quantity
     */
    public function quantityAlertReport()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('quantity-alert')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Get products below alert quantity
            $alertProducts = Product::where('is_active', true)
                ->whereColumn('qty', '<=', 'alert_quantity')
                ->orderBy('qty', 'ASC')
                ->get();

            $alertRows = $alertProducts->map(function ($product) {
                $shortage = $product->alert_quantity - $product->qty;
                $statusColor = $product->qty == 0 ? 'red' : 'orange';
                $status = $product->qty == 0 ? 'Out of Stock' : 'Low Stock';

                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'current_quantity' => $product->qty,
                    'alert_quantity' => $product->alert_quantity,
                    'shortage' => $shortage,
                    'status' => "<span style='color: $statusColor; font-weight: bold;'>$status</span>",
                ];
            });

            return [
                'title' => "Quantity Alert",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Product Name', 'field' => 'product_name', 'type' => 'text'],
                    ['label' => 'Product Code', 'field' => 'product_code', 'type' => 'text'],
                    ['label' => 'Current Qty', 'field' => 'current_quantity', 'type' => 'text'],
                    ['label' => 'Alert Qty', 'field' => 'alert_quantity', 'type' => 'text'],
                    ['label' => 'Shortage', 'field' => 'shortage', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                ],
                'rows' => $alertRows,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the quantity alert report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * DSO Report Form (Days Sales Outstanding)
     * Returns form schema for DSO report
     */
    public function dsoReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('dso-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "DSO (Daily Sale Objective)",
                "submit_url" => "/reports/dso-report",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "daterangepicker",
                        "name" => "date_range",
                        "label" => "Date Range",
                        "placeholder" => "Select date range",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the DSO report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * DSO Report Handler
     * Processes DSO report form and returns data
     */
    public function dsoReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('dso-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $dateRange = $request->input('date_range');
            list($startDate, $endDate) = explode(' to ', $dateRange);

            $sales = Sale::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->get();

            $totalRevenue = $sales->sum('grand_total');
            $accountsReceivable = $sales->where('payment_status', '!=', 4)->sum('grand_total');
            $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
            $dso = $days > 0 ? ($accountsReceivable / ($totalRevenue / $days)) : 0;

            return response()->json([
                'success' => true,
                'view_type' => 'report',
                'title' => "DSO",
                'summary' => [
                    'Total Revenue' => config('currency') . ' ' . number_format($totalRevenue, 2),
                    'Accounts Receivable' => config('currency') . ' ' . number_format($accountsReceivable, 2),
                    'DSO (Days)' => number_format($dso, 2) . ' days',
                ],
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the DSO report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Due Report Form (Customer Due)
     * Returns form schema for customer due report
     */
    public function dueReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('due-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Direct navigation to due report table without form fields
            return response()->json([
                'success' => true,
                'message' => 'Loading customer due report.',
                'navigate_url' => '/reports/due-report',
                'navigate_type' => 'datatable',
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the due report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Due Report Handler
     * Processes customer due report
     */
    public function dueReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('due-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customers = Customer::where('is_active', true)->get();

            $dueData = $customers->map(function ($customer) {
                $totalSales = Sale::where('customer_id', $customer->id)->sum('grand_total');
                $totalPaid = Payment::where('customer_id', $customer->id)->sum('paying_amount');
                $due = $totalSales - $totalPaid;

                if ($due > 0) {
                    return [
                        'customer_name' => $customer->name,
                        'customer_phone' => $customer->phone_number ?? 'N/A',
                        'total_sales' => number_format($totalSales, 2),
                        'total_paid' => number_format($totalPaid, 2),
                        'due_amount' => number_format($due, 2),
                    ];
                }
                return null;
            })->filter();

            return [
                'title' => "Customer Due",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Customer Name', 'field' => 'customer_name', 'type' => 'text'],
                    ['label' => 'Phone', 'field' => 'customer_phone', 'type' => 'text'],
                    ['label' => 'Total Sales', 'field' => 'total_sales', 'type' => 'text'],
                    ['label' => 'Total Paid', 'field' => 'total_paid', 'type' => 'text'],
                    ['label' => 'Due Amount', 'field' => 'due_amount', 'type' => 'text'],
                ],
                'rows' => $dueData->values(),
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the due report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Warehouse Stock Report Form
     * Returns form schema for warehouse stock report
     */
    public function warehouseStockReportForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('warehouse-stock')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouses = Warehouse::where('is_active', true)
                ->get()
                ->map(function ($warehouse) {
                    return [
                        'label' => $warehouse->name,
                        'value' => $warehouse->id,
                    ];
                });

            $formSchema = [
                "title" => "Warehouse Stock",
                "submit_url" => "/reports/warehouse-stock",
                "method" => "POST",
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "navigate_url" => "",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "warehouse_id",
                        "label" => "Warehouse",
                        "options" => $warehouses,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the warehouse stock report form.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Warehouse Stock Report Handler
     * Processes warehouse stock report
     */
    public function warehouseStockReportHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('warehouse-stock')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouseId = $request->input('warehouse_id');
            $warehouse = Warehouse::find($warehouseId);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            $products = Product::where('is_active', true)
                ->where('warehouse_id', $warehouseId)
                ->get();

            $stockData = $products->map(function ($product) {
                return [
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => $product->qty,
                    'cost' => number_format($product->cost, 2),
                    'price' => number_format($product->price, 2),
                    'total_value' => number_format($product->qty * $product->cost, 2),
                ];
            });

            return [
                'title' => "Stock - " . $warehouse->name,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Product Name', 'field' => 'product_name', 'type' => 'text'],
                    ['label' => 'Product Code', 'field' => 'product_code', 'type' => 'text'],
                    ['label' => 'Quantity', 'field' => 'quantity', 'type' => 'text'],
                    ['label' => 'Cost', 'field' => 'cost', 'type' => 'text'],
                    ['label' => 'Price', 'field' => 'price', 'type' => 'text'],
                    ['label' => 'Total Value', 'field' => 'total_value', 'type' => 'text'],
                ],
                'rows' => $stockData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the warehouse stock report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Profit/Loss Report Table
     * Returns profit/loss report with summary boxes matching web version
     */
    public function profitLossReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('profit-loss')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query1 = array(
                'SUM(grand_total) AS grand_total',
                'SUM(shipping_cost) AS shipping_cost',
                'SUM(paid_amount) AS paid_amount',
                'SUM(total_tax + order_tax) AS tax',
                'SUM(total_discount + order_discount) AS discount'
            );
            $query2 = array(
                'SUM(grand_total) AS grand_total',
                'SUM(total_tax + order_tax) AS tax'
            );

            // Get Purchase data
            $purchase = Purchase::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->selectRaw(implode(',', $query1))
                ->first();
            $totalPurchase = Purchase::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            // Get Sale data
            $sale = Sale::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->selectRaw(implode(',', $query1))
                ->first();
            $totalSale = Sale::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            // Get Returns data (if Returns model exists)
            $returnGrandTotal = 0;
            $returnTax = 0;
            $totalReturn = 0;
            if (class_exists('\App\Models\Returns')) {
                $return = \App\Models\Returns::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->selectRaw(implode(',', $query2))
                    ->first();
                $returnGrandTotal = $return->grand_total ?? 0;
                $returnTax = $return->tax ?? 0;
                $totalReturn = \App\Models\Returns::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();
            }

            // Get Purchase Returns data (if ReturnPurchase model exists)
            $purchaseReturnGrandTotal = 0;
            $purchaseReturnTax = 0;
            $totalPurchaseReturn = 0;
            if (class_exists('\App\Models\ReturnPurchase')) {
                $purchaseReturn = \App\Models\ReturnPurchase::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->selectRaw(implode(',', $query2))
                    ->first();
                $purchaseReturnGrandTotal = $purchaseReturn->grand_total ?? 0;
                $purchaseReturnTax = $purchaseReturn->tax ?? 0;
                $totalPurchaseReturn = \App\Models\ReturnPurchase::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();
            }

            // Get Expense, Income, Payroll
            $expense = Expense::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->sum('amount');
            $totalExpenseCount = Expense::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            $income = Income::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->sum('amount');
            $totalIncomeCount = Income::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            $payroll = 0;
            $totalPayrollCount = 0;
            if (class_exists('\App\Models\Payroll')) {
                $payroll = \App\Models\Payroll::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->sum('amount');
                $totalPayrollCount = \App\Models\Payroll::whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->count();
            }

            // Get Payment data
            $paymentReceived = Payment::whereNotNull('sale_id')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->sum('amount');
            $paymentReceivedNumber = Payment::whereNotNull('sale_id')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            $paymentSent = Payment::whereNotNull('purchase_id')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->sum('amount');
            $paymentSentNumber = Payment::whereNotNull('purchase_id')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count();

            // Calculate product cost (simplified - using purchase total)
            $productCost = $purchase->grand_total ?? 0;
            $productTax = $purchase->tax ?? 0;

            // Calculate profits
            $grossProfit = ($sale->grand_total ?? 0) - $productCost;
            $netProfit = (($sale->grand_total ?? 0) - ($sale->shipping_cost ?? 0) - ($sale->tax ?? 0))
                - ($productCost - $productTax)
                - ($returnGrandTotal - $returnTax)
                + ($purchaseReturnGrandTotal - $purchaseReturnTax)
                - $expense
                + $income;
            $cashInHand = $paymentReceived - $paymentSent - $returnGrandTotal + $purchaseReturnGrandTotal - $expense - $payroll;

            $currency = config('currency');
            $formatMoney = function ($amount) use ($currency) {
                $formatted = number_format($amount, 2);
                return config('currency_position') == 'prefix'
                    ? $currency . ' ' . $formatted
                    : $formatted . ' ' . $currency;
            };

            // Build summary boxes
            $summaryBoxes = [
                [
                    'title' => 'Purchase',
                    'icon' => 'shopping_cart',
                    'items' => [
                        ['label' => 'Grand Total', 'value' => $formatMoney($purchase->grand_total ?? 0)],
                        ['label' => 'Purchase', 'value' => (string)$totalPurchase],
                        ['label' => 'Paid', 'value' => $formatMoney($purchase->paid_amount ?? 0)],
                        ['label' => 'Tax', 'value' => $formatMoney($purchase->tax ?? 0)],
                        ['label' => 'Discount', 'value' => $formatMoney($purchase->discount ?? 0)],
                    ],
                ],
                [
                    'title' => 'Sale',
                    'icon' => 'attach_money',
                    'items' => [
                        ['label' => 'Grand Total', 'value' => $formatMoney($sale->grand_total ?? 0)],
                        ['label' => 'Shipping Cost', 'value' => $formatMoney($sale->shipping_cost ?? 0)],
                        ['label' => 'Sale', 'value' => (string)$totalSale],
                        ['label' => 'Paid', 'value' => $formatMoney($sale->paid_amount ?? 0)],
                        ['label' => 'Tax', 'value' => $formatMoney($sale->tax ?? 0)],
                        ['label' => 'Discount', 'value' => $formatMoney($sale->discount ?? 0)],
                    ],
                ],
                [
                    'title' => 'Sale Return',
                    'icon' => 'sync',
                    'items' => [
                        ['label' => 'Grand Total', 'value' => $formatMoney($returnGrandTotal)],
                        ['label' => 'Return', 'value' => (string)$totalReturn],
                        ['label' => 'Tax', 'value' => $formatMoney($returnTax)],
                    ],
                ],
                [
                    'title' => 'Purchase Return',
                    'icon' => 'sync',
                    'items' => [
                        ['label' => 'Grand Total', 'value' => $formatMoney($purchaseReturnGrandTotal)],
                        ['label' => 'Return', 'value' => (string)$totalPurchaseReturn],
                        ['label' => 'Tax', 'value' => $formatMoney($purchaseReturnTax)],
                    ],
                ],
                [
                    'title' => 'Profit / Loss',
                    'icon' => 'monetization_on',
                    'items' => [
                        ['label' => 'Sale', 'value' => $formatMoney($sale->grand_total ?? 0)],
                        ['label' => 'Product Cost', 'value' => '- ' . $formatMoney($productCost)],
                        ['label' => 'Profit', 'value' => $formatMoney($grossProfit), 'color' => $grossProfit >= 0 ? 'green' : 'red'],
                    ],
                ],
                [
                    'title' => 'Net Profit / Net Loss',
                    'icon' => 'account_balance',
                    'highlight' => true,
                    'items' => [
                        ['label' => 'Net Profit', 'value' => $formatMoney($netProfit), 'color' => $netProfit >= 0 ? 'green' : 'red', 'large' => true],
                    ],
                ],
                [
                    'title' => 'Payment Received',
                    'icon' => 'arrow_downward',
                    'items' => [
                        ['label' => 'Amount', 'value' => $formatMoney($paymentReceived)],
                        ['label' => 'Received', 'value' => (string)$paymentReceivedNumber],
                    ],
                ],
                [
                    'title' => 'Payment Sent',
                    'icon' => 'arrow_upward',
                    'items' => [
                        ['label' => 'Amount', 'value' => $formatMoney($paymentSent)],
                        ['label' => 'Sent', 'value' => (string)$paymentSentNumber],
                    ],
                ],
                [
                    'title' => 'Expense',
                    'icon' => 'money_off',
                    'items' => [
                        ['label' => 'Amount', 'value' => $formatMoney($expense)],
                        ['label' => 'Expense', 'value' => (string)$totalExpenseCount],
                    ],
                ],
                [
                    'title' => 'Income',
                    'icon' => 'trending_up',
                    'items' => [
                        ['label' => 'Amount', 'value' => $formatMoney($income)],
                        ['label' => 'Income', 'value' => (string)$totalIncomeCount],
                    ],
                ],
                [
                    'title' => 'Payroll',
                    'icon' => 'people',
                    'items' => [
                        ['label' => 'Amount', 'value' => $formatMoney($payroll)],
                        ['label' => 'Payroll', 'value' => (string)$totalPayrollCount],
                    ],
                ],
                [
                    'title' => 'Cash in Hand',
                    'icon' => 'account_balance_wallet',
                    'items' => [
                        ['label' => 'Received', 'value' => $formatMoney($paymentReceived)],
                        ['label' => 'Sent', 'value' => '- ' . $formatMoney($paymentSent)],
                        ['label' => 'Sale Return', 'value' => '- ' . $formatMoney($returnGrandTotal)],
                        ['label' => 'Purchase Return', 'value' => $formatMoney($purchaseReturnGrandTotal)],
                        ['label' => 'Expense', 'value' => '- ' . $formatMoney($expense)],
                        ['label' => 'Payroll', 'value' => '- ' . $formatMoney($payroll)],
                        ['label' => 'In Hand', 'value' => $formatMoney($cashInHand), 'color' => $cashInHand >= 0 ? 'green' : 'red'],
                    ],
                ],
            ];

            return response()->json([
                'view_type' => 'summary',
                'title' => "Profit/Loss",
                'subtitle' => "From " . date('d M, Y', strtotime($startDate)) . " to " . date('d M, Y', strtotime($endDate)),
                'summary_boxes' => $summaryBoxes,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the profit/loss report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Sale Report Table
     * Returns sale report data as datatable
     */
    public function saleReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('sale-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouseId = $request->input('warehouse_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $sales = Sale::with(['customer', 'biller'])
                ->where('warehouse_id', $warehouseId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'desc')
                ->get();

            $salesData = $sales->map(function ($sale) {
                return [
                    'date' => date('d M, Y', strtotime($sale->created_at)),
                    'reference' => $sale->reference_no,
                    'customer' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
                    'biller' => $sale->biller ? $sale->biller->name : 'N/A',
                    'grand_total' => number_format($sale->grand_total, 2),
                    'paid' => number_format($sale->paid_amount, 2),
                    'due' => number_format($sale->grand_total - $sale->paid_amount, 2),
                    'status' => $sale->sale_status == 1 ? 'Completed' : 'Pending',
                ];
            });

            return [
                'title' => "Sales",
                'subtitle' => "From " . date('d M, Y', strtotime($startDate)) . " to " . date('d M, Y', strtotime($endDate)),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Biller', 'field' => 'biller', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Paid', 'field' => 'paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                ],
                'rows' => $salesData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the sale report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Purchase Report Table
     * Returns purchase report data as datatable
     */
    public function purchaseReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('purchase-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $warehouseId = $request->input('warehouse_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $purchases = Purchase::with(['supplier'])
                ->where('warehouse_id', $warehouseId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'desc')
                ->get();

            $purchasesData = $purchases->map(function ($purchase) {
                return [
                    'date' => date('d M, Y', strtotime($purchase->created_at)),
                    'reference' => $purchase->reference_no,
                    'supplier' => $purchase->supplier ? $purchase->supplier->name : 'N/A',
                    'grand_total' => number_format($purchase->grand_total, 2),
                    'paid' => number_format($purchase->paid_amount, 2),
                    'due' => number_format($purchase->grand_total - $purchase->paid_amount, 2),
                    'status' => $purchase->status == 1 ? 'Received' : 'Pending',
                ];
            });

            return [
                'title' => "Purchases",
                'subtitle' => "From " . date('d M, Y', strtotime($startDate)) . " to " . date('d M, Y', strtotime($endDate)),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Supplier', 'field' => 'supplier', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Paid', 'field' => 'paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                ],
                'rows' => $purchasesData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the purchase report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Customer Report Table
     */
    public function customerReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customerId = $request->input('customer_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $customer = Customer::find($customerId);
            $sales = Sale::where('customer_id', $customerId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'desc')
                ->get();

            $salesData = $sales->map(function ($sale) {
                return [
                    'date' => date('d M, Y', strtotime($sale->created_at)),
                    'reference' => $sale->reference_no,
                    'grand_total' => number_format($sale->grand_total, 2),
                    'paid' => number_format($sale->paid_amount, 2),
                    'due' => number_format($sale->grand_total - $sale->paid_amount, 2),
                ];
            });

            return [
                'title' => "Customer - " . ($customer ? $customer->name : 'N/A'),
                'subtitle' => "From " . date('d M, Y', strtotime($startDate)) . " to " . date('d M, Y', strtotime($endDate)),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Paid', 'field' => 'paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                ],
                'rows' => $salesData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the customer report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    /**
     * Supplier Report Table
     */
    public function supplierReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('supplier-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $supplierId = $request->input('supplier_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $supplier = Supplier::find($supplierId);
            $purchases = Purchase::where('supplier_id', $supplierId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'desc')
                ->get();

            $purchasesData = $purchases->map(function ($purchase) {
                return [
                    'date' => date('d M, Y', strtotime($purchase->created_at)),
                    'reference' => $purchase->reference_no,
                    'grand_total' => number_format($purchase->grand_total, 2),
                    'paid' => number_format($purchase->paid_amount, 2),
                    'due' => number_format($purchase->grand_total - $purchase->paid_amount, 2),
                ];
            });

            return [
                'title' => "Supplier - " . ($supplier ? $supplier->name : 'N/A'),
                'subtitle' => "From " . date('d M, Y', strtotime($startDate)) . " to " . date('d M, Y', strtotime($endDate)),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                    ['label' => 'Paid', 'field' => 'paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                ],
                'rows' => $purchasesData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the supplier report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Warehouse Report Table
    public function warehouseReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('warehouse-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $warehouse_id = $request->input('warehouse_id');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if (!$warehouse_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse ID is required.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $warehouse = Warehouse::find($warehouse_id);
            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Get sales for this warehouse
            $salesQuery = Sale::where('warehouse_id', $warehouse_id);
            if ($start_date && $end_date) {
                $salesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $totalSales = $salesQuery->sum('grand_total');
            $salesCount = $salesQuery->count();

            // Get purchases for this warehouse
            $purchasesQuery = Purchase::where('warehouse_id', $warehouse_id);
            if ($start_date && $end_date) {
                $purchasesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $totalPurchases = $purchasesQuery->sum('grand_total');
            $purchasesCount = $purchasesQuery->count();

            $rows = [
                [
                    'metric' => 'Total Sales',
                    'count' => (string) $salesCount,
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($totalSales, 2)
                        : number_format($totalSales, 2) . " " . config('currency'),
                ],
                [
                    'metric' => 'Total Purchases',
                    'count' => (string) $purchasesCount,
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($totalPurchases, 2)
                        : number_format($totalPurchases, 2) . " " . config('currency'),
                ],
            ];

            return [
                'title' => "Warehouse - " . $warehouse->name,
                'subtitle' => $start_date && $end_date
                    ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                    : "All Time",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Metric', 'field' => 'metric', 'type' => 'text'],
                    ['label' => 'Count', 'field' => 'count', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                ],
                'rows' => $rows,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the warehouse report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Biller Report Table
    // Biller Report Table (Tabbed Datatable)
    public function billerReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('biller-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $biller_id = $request->input('biller_id');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if (!$biller_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biller ID is required.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $biller = Biller::find($biller_id);
            if (!$biller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Biller not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Sales Tab
            $salesQuery = Sale::where('biller_id', $biller_id)
                ->with(['customer', 'warehouse']);
            if ($start_date && $end_date) {
                $salesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $sales = $salesQuery->orderBy('created_at', 'desc')->get();

            $salesRows = $sales->map(function ($sale) {
                return [
                    date('d M Y', strtotime($sale->created_at)),
                    $sale->reference_no,
                    $sale->customer ? $sale->customer->name : 'N/A',
                    $sale->warehouse ? $sale->warehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->grand_total, 2)
                        : number_format($sale->grand_total, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->paid_amount, 2)
                        : number_format($sale->paid_amount, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->grand_total - $sale->paid_amount, 2)
                        : number_format($sale->grand_total - $sale->paid_amount, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Quotations Tab
            $quotationsQuery = Quotation::where('biller_id', $biller_id)
                ->with(['customer', 'warehouse']);
            if ($start_date && $end_date) {
                $quotationsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $quotations = $quotationsQuery->orderBy('created_at', 'desc')->get();

            $quotationsRows = $quotations->map(function ($quotation) {
                return [
                    date('d M Y', strtotime($quotation->created_at)),
                    $quotation->reference_no,
                    $quotation->customer ? $quotation->customer->name : 'N/A',
                    $quotation->warehouse ? $quotation->warehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($quotation->grand_total, 2)
                        : number_format($quotation->grand_total, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Payments Tab
            // Get payments for sales made by this biller
            $saleIds = Sale::where('biller_id', $biller_id)->pluck('id');
            $paymentsQuery = Payment::whereIn('sale_id', $saleIds);
            if ($start_date && $end_date) {
                $paymentsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

            $paymentsRows = $payments->map(function ($payment) {
                $saleRef = $payment->sale_id ? Sale::find($payment->sale_id)?->reference_no : 'N/A';

                return [
                    date('d M Y', strtotime($payment->created_at)),
                    $saleRef,
                    $payment->paying_method,
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($payment->paying_amount, 2)
                        : number_format($payment->paying_amount, 2) . config('currency'),
                    $payment->payment_note ?? 'N/A',
                ];
            })->values()->toArray();

            $subtitle = $start_date && $end_date
                ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                : "All Time";

            return [
                'title' => "Biller - " . $biller->name,
                'subtitle' => $subtitle,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'tabs' => [
                    [
                        'title' => 'Sales',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Customer', 'Warehouse', 'Grand Total', 'Paid', 'Due'],
                        'rows' => $salesRows,
                    ],
                    [
                        'title' => 'Quotations',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Customer', 'Warehouse', 'Grand Total'],
                        'rows' => $quotationsRows,
                    ],
                    [
                        'title' => 'Payments',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Payment Type', 'Amount', 'Note'],
                        'rows' => $paymentsRows,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the biller report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // User Report Table (Tabbed Datatable)
    public function userReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('user-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $user_id = $request->input('user_id');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if (!$user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $reportUser = User::find($user_id);
            if (!$reportUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Sales Tab
            $salesQuery = Sale::where('user_id', $user_id)
                ->with(['customer', 'warehouse']);
            if ($start_date && $end_date) {
                $salesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $sales = $salesQuery->orderBy('created_at', 'desc')->get();

            $salesRows = $sales->map(function ($sale) {
                return [
                    date('d M Y', strtotime($sale->created_at)),
                    $sale->reference_no,
                    $sale->customer ? $sale->customer->name : 'N/A',
                    $sale->warehouse ? $sale->warehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->grand_total, 2)
                        : number_format($sale->grand_total, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->paid_amount, 2)
                        : number_format($sale->paid_amount, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($sale->grand_total - $sale->paid_amount, 2)
                        : number_format($sale->grand_total - $sale->paid_amount, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Purchases Tab
            $purchasesQuery = Purchase::where('user_id', $user_id)
                ->with(['supplier', 'warehouse']);
            if ($start_date && $end_date) {
                $purchasesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $purchases = $purchasesQuery->orderBy('created_at', 'desc')->get();

            $purchasesRows = $purchases->map(function ($purchase) {
                return [
                    date('d M Y', strtotime($purchase->created_at)),
                    $purchase->reference_no,
                    $purchase->supplier ? $purchase->supplier->name : 'N/A',
                    $purchase->warehouse ? $purchase->warehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($purchase->grand_total, 2)
                        : number_format($purchase->grand_total, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($purchase->paid_amount, 2)
                        : number_format($purchase->paid_amount, 2) . config('currency'),
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($purchase->grand_total - $purchase->paid_amount, 2)
                        : number_format($purchase->grand_total - $purchase->paid_amount, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Quotations Tab
            $quotationsQuery = Quotation::where('user_id', $user_id)
                ->with(['customer', 'warehouse']);
            if ($start_date && $end_date) {
                $quotationsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $quotations = $quotationsQuery->orderBy('created_at', 'desc')->get();

            $quotationsRows = $quotations->map(function ($quotation) {
                return [
                    date('d M Y', strtotime($quotation->created_at)),
                    $quotation->reference_no,
                    $quotation->customer ? $quotation->customer->name : 'N/A',
                    $quotation->warehouse ? $quotation->warehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($quotation->grand_total, 2)
                        : number_format($quotation->grand_total, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Transfers Tab
            $transfersQuery = Transfer::where('user_id', $user_id)
                ->with(['fromWarehouse', 'toWarehouse']);
            if ($start_date && $end_date) {
                $transfersQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $transfers = $transfersQuery->orderBy('created_at', 'desc')->get();

            $transfersRows = $transfers->map(function ($transfer) {
                return [
                    date('d M Y', strtotime($transfer->created_at)),
                    $transfer->reference_no,
                    $transfer->fromWarehouse ? $transfer->fromWarehouse->name : 'N/A',
                    $transfer->toWarehouse ? $transfer->toWarehouse->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($transfer->grand_total, 2)
                        : number_format($transfer->grand_total, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Payments Tab
            $paymentsQuery = Payment::where('user_id', $user_id);
            if ($start_date && $end_date) {
                $paymentsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

            $paymentsRows = $payments->map(function ($payment) {
                $saleRef = $payment->sale_id ? Sale::find($payment->sale_id)?->reference_no : null;
                $purchaseRef = $payment->purchase_id ? Purchase::find($payment->purchase_id)?->reference_no : null;

                return [
                    date('d M Y', strtotime($payment->created_at)),
                    $saleRef ?: $purchaseRef ?: 'N/A',
                    $payment->paying_method,
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($payment->paying_amount, 2)
                        : number_format($payment->paying_amount, 2) . config('currency'),
                ];
            })->values()->toArray();

            // Expenses Tab
            $expensesQuery = Expense::where('user_id', $user_id);
            if ($start_date && $end_date) {
                $expensesQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $expenses = $expensesQuery->orderBy('created_at', 'desc')->get();

            $expensesRows = $expenses->map(function ($expense) {
                $category = ExpenseCategory::find($expense->expense_category_id);

                return [
                    date('d M Y', strtotime($expense->created_at)),
                    $category ? $category->name : 'N/A',
                    $expense->warehouse_id ? Warehouse::find($expense->warehouse_id)?->name : 'N/A',
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($expense->amount, 2)
                        : number_format($expense->amount, 2) . config('currency'),
                    $expense->note ?? 'N/A',
                ];
            })->values()->toArray();

            // Payroll Tab
            $payrollsQuery = Payroll::where('user_id', $user_id);
            if ($start_date && $end_date) {
                $payrollsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $payrolls = $payrollsQuery->orderBy('created_at', 'desc')->get();

            $payrollsRows = $payrolls->map(function ($payroll) {
                return [
                    date('d M Y', strtotime($payroll->created_at)),
                    $payroll->reference_no,
                    config('currency_position') == 'prefix'
                        ? config('currency') . number_format($payroll->amount, 2)
                        : number_format($payroll->amount, 2) . config('currency'),
                    $payroll->paying_method,
                ];
            })->values()->toArray();

            $subtitle = $start_date && $end_date
                ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                : "All Time";

            return [
                'title' => "User - " . $reportUser->name,
                'subtitle' => $subtitle,
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'tabs' => [
                    [
                        'title' => 'Sales',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Customer', 'Warehouse', 'Grand Total', 'Paid', 'Due'],
                        'rows' => $salesRows,
                    ],
                    [
                        'title' => 'Purchases',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Supplier', 'Warehouse', 'Grand Total', 'Paid', 'Due'],
                        'rows' => $purchasesRows,
                    ],
                    [
                        'title' => 'Quotations',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Customer', 'Warehouse', 'Grand Total'],
                        'rows' => $quotationsRows,
                    ],
                    [
                        'title' => 'Transfers',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'From Warehouse', 'To Warehouse', 'Grand Total'],
                        'rows' => $transfersRows,
                    ],
                    [
                        'title' => 'Payments',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Payment Type', 'Amount'],
                        'rows' => $paymentsRows,
                    ],
                    [
                        'title' => 'Expenses',
                        'row_height' => 5,
                        'columns' => ['Date', 'Category', 'Warehouse', 'Amount', 'Note'],
                        'rows' => $expensesRows,
                    ],
                    [
                        'title' => 'Payroll',
                        'row_height' => 5,
                        'columns' => ['Date', 'Reference', 'Amount', 'Payment Method'],
                        'rows' => $payrollsRows,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the user report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Product Report Table
    public function productReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('product-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $product_id = $request->input('product_id');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if (!$product_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product ID is required.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $product = Product::find($product_id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Get sales for this product
            $salesQuery = Product_Sale::where('product_id', $product_id);
            if ($start_date && $end_date) {
                $salesQuery->whereHas('sale', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('created_at', [$start_date, $end_date]);
                });
            }
            $totalSold = $salesQuery->sum('qty');
            $salesRevenue = $salesQuery->sum('total');

            // Get purchases for this product
            $purchasesQuery = ProductPurchase::where('product_id', $product_id);
            if ($start_date && $end_date) {
                $purchasesQuery->whereHas('purchase', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('created_at', [$start_date, $end_date]);
                });
            }
            $totalPurchased = $purchasesQuery->sum('qty');
            $purchaseCost = $purchasesQuery->sum('total');

            $rows = [
                [
                    'metric' => 'Current Stock',
                    'quantity' => (string) $product->qty,
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($product->qty * $product->price, 2)
                        : number_format($product->qty * $product->price, 2) . " " . config('currency'),
                ],
                [
                    'metric' => 'Total Sold',
                    'quantity' => (string) $totalSold,
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($salesRevenue, 2)
                        : number_format($salesRevenue, 2) . " " . config('currency'),
                ],
                [
                    'metric' => 'Total Purchased',
                    'quantity' => (string) $totalPurchased,
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($purchaseCost, 2)
                        : number_format($purchaseCost, 2) . " " . config('currency'),
                ],
            ];

            return [
                'title' => "Product",
                'subtitle' => $start_date && $end_date
                    ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                    : "All Time",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Metric', 'field' => 'metric', 'type' => 'text'],
                    ['label' => 'Quantity', 'field' => 'quantity', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                ],
                'rows' => $rows,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the product report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Customer Group Report Table
    public function customerGroupReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('customer-group-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $customer_group_id = $request->input('customer_group_id');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            if (!$customer_group_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer Group ID is required.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 400);
            }

            $customerGroup = CustomerGroup::find($customer_group_id);
            if (!$customerGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer Group not found.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 404);
            }

            // Get all customers in this group
            $customers = Customer::where('customer_group_id', $customer_group_id)
                ->where('is_active', true)
                ->get();

            $rows = [];
            foreach ($customers as $customer) {
                $salesQuery = Sale::where('customer_id', $customer->id);
                if ($start_date && $end_date) {
                    $salesQuery->whereBetween('created_at', [$start_date, $end_date]);
                }

                $totalSales = $salesQuery->sum('grand_total');
                $totalPaid = $salesQuery->sum('paid_amount');
                $salesCount = $salesQuery->count();

                if ($salesCount > 0) {
                    $rows[] = [
                        'customer' => $customer->name,
                        'sales_count' => (string) $salesCount,
                        'total_sales' => config('currency_position') == 'prefix'
                            ? config('currency') . " " . number_format($totalSales, 2)
                            : number_format($totalSales, 2) . " " . config('currency'),
                        'total_paid' => config('currency_position') == 'prefix'
                            ? config('currency') . " " . number_format($totalPaid, 2)
                            : number_format($totalPaid, 2) . " " . config('currency'),
                        'due' => config('currency_position') == 'prefix'
                            ? config('currency') . " " . number_format($totalSales - $totalPaid, 2)
                            : number_format($totalSales - $totalPaid, 2) . " " . config('currency'),
                    ];
                }
            }

            return [
                'title' => "Customer Group - " . $customerGroup->name,
                'subtitle' => $start_date && $end_date
                    ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                    : "All Time",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Sales Count', 'field' => 'sales_count', 'type' => 'text'],
                    ['label' => 'Total Sales', 'field' => 'total_sales', 'type' => 'text'],
                    ['label' => 'Total Paid', 'field' => 'total_paid', 'type' => 'text'],
                    ['label' => 'Due', 'field' => 'due', 'type' => 'text'],
                ],
                'rows' => $rows,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the customer group report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Payment Report Table
    public function paymentReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('payment-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            $paymentsQuery = Payment::where('is_active', true);
            if ($start_date && $end_date) {
                $paymentsQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

            $paymentsData = $payments->map(function ($payment) {
                $paymentType = 'N/A';
                $reference = 'N/A';

                if ($payment->sale_id) {
                    $paymentType = 'Sale Payment';
                    $sale = Sale::find($payment->sale_id);
                    $reference = $sale ? $sale->reference_no : 'N/A';
                } elseif ($payment->purchase_id) {
                    $paymentType = 'Purchase Payment';
                    $purchase = Purchase::find($payment->purchase_id);
                    $reference = $purchase ? $purchase->reference_no : 'N/A';
                }

                return [
                    'date' => date('d M Y', strtotime($payment->created_at)),
                    'type' => $paymentType,
                    'reference' => $reference,
                    'payment_method' => $payment->paying_method ?? 'N/A',
                    'amount' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($payment->amount, 2)
                        : number_format($payment->amount, 2) . " " . config('currency'),
                ];
            });

            return [
                'title' => "Payments",
                'subtitle' => $start_date && $end_date
                    ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                    : "All Time",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Type', 'field' => 'type', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Payment Method', 'field' => 'payment_method', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                ],
                'rows' => $paymentsData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the payment report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // Challan Report Table
    public function challanReportTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('challan-report')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                    "debug_bar" => env("APP_DEBUG", false) ? true : false,
                ], 403);
            }

            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');

            $challansQuery = Challan::where('is_active', true);
            if ($start_date && $end_date) {
                $challansQuery->whereBetween('created_at', [$start_date, $end_date]);
            }
            $challans = $challansQuery->orderBy('created_at', 'desc')->get();

            $challansData = $challans->map(function ($challan) {
                $customer = Customer::find($challan->customer_id);
                $warehouse = Warehouse::find($challan->warehouse_id);

                return [
                    'date' => date('d M Y', strtotime($challan->created_at)),
                    'reference' => $challan->reference_no,
                    'customer' => $customer ? $customer->name : 'N/A',
                    'warehouse' => $warehouse ? $warehouse->name : 'N/A',
                    'status' => ucfirst($challan->status ?? 'pending'),
                    'grand_total' => config('currency_position') == 'prefix'
                        ? config('currency') . " " . number_format($challan->grand_total, 2)
                        : number_format($challan->grand_total, 2) . " " . config('currency'),
                ];
            });

            return [
                'title' => "Challans",
                'subtitle' => $start_date && $end_date
                    ? "Period: " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date))
                    : "All Time",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Customer', 'field' => 'customer', 'type' => 'text'],
                    ['label' => 'Warehouse', 'field' => 'warehouse', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                    ['label' => 'Grand Total', 'field' => 'grand_total', 'type' => 'text'],
                ],
                'rows' => $challansData,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the challan report.',
                'error' => $e->getMessage(),
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
            ], 500);
        }
    }

    // DSO Report Table - delegates to handler which returns datatable
    public function dsoReportTable(Request $request)
    {
        // DSO handler already returns a datatable, just call it with date_range param
        $request->merge(['date_range' => 'all']);
        return $this->dsoReportHandler($request);
    }

    // Due Report Table - delegates to handler which returns datatable
    public function dueReportTable(Request $request)
    {
        // Due handler already returns a datatable, just call it with generate_report flag
        $request->merge(['generate_report' => true]);
        return $this->dueReportHandler($request);
    }

    // Warehouse Stock Report Table - delegates to handler which returns datatable
    public function warehouseStockReportTable(Request $request)
    {
        // Warehouse stock handler already returns a datatable
        return $this->warehouseStockReportHandler($request);
    }

    /**
     * Balance Sheet Report - Custom View
     * Displays all accounts with their credit/debit balances
     */
    public function balanceSheetReport()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('balance-sheet')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $lims_account_list = Account::where('is_active', true)->get();
            $accountsTable = [];

            foreach ($lims_account_list as $account) {
                $payment_received = Payment::whereNotNull('sale_id')
                    ->where('account_id', $account->id)
                    ->sum('amount');

                $payment_sent = Payment::whereNotNull('purchase_id')
                    ->where('account_id', $account->id)
                    ->sum('amount');

                $returns = DB::table('returns')
                    ->where('account_id', $account->id)
                    ->sum('grand_total');

                $return_purchase = DB::table('return_purchases')
                    ->where('account_id', $account->id)
                    ->sum('grand_total');

                $expenses = DB::table('expenses')
                    ->where('account_id', $account->id)
                    ->sum('amount');

                $payrolls = DB::table('payrolls')
                    ->where('account_id', $account->id)
                    ->sum('amount');

                $sent_money_via_transfer = MoneyTransfer::where('from_account_id', $account->id)
                    ->sum('amount');

                $received_money_via_transfer = MoneyTransfer::where('to_account_id', $account->id)
                    ->sum('amount');

                $credit_amount = $payment_received + $return_purchase + $received_money_via_transfer + $account->initial_balance;
                $debit_amount = $payment_sent + $returns + $expenses + $payrolls + $sent_money_via_transfer;
                $balance = $credit_amount - $debit_amount;

                $accountsTable[] = [
                    'id' => $account->id,
                    'account_name' => $account->name,
                    'account_number' => $account->account_no,
                    'credit' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($credit_amount, 2)
                        : number_format($credit_amount, 2) . ' ' . config('currency'),
                    'debit' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($debit_amount, 2)
                        : number_format($debit_amount, 2) . ' ' . config('currency'),
                    'balance' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($balance, 2)
                        : number_format($balance, 2) . ' ' . config('currency'),
                ];
            }

            return [
                'title' => 'Balance Sheet',
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Account Name', 'field' => 'account_name', 'type' => 'text'],
                    ['label' => 'Account Number', 'field' => 'account_number', 'type' => 'text'],
                    ['label' => 'Credit', 'field' => 'credit', 'type' => 'text'],
                    ['label' => 'Debit', 'field' => 'debit', 'type' => 'text'],
                    ['label' => 'Balance', 'field' => 'balance', 'type' => 'text'],
                ],
                'rows' => $accountsTable,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the balance sheet.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    /**
     * Account Statement Form
     */
    public function accountStatementForm()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('account-statement')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $accounts = Account::where('is_active', true)->get();
            $accountOptions = $accounts->map(function ($account) {
                return [
                    'label' => $account->name . ' (' . $account->account_no . ')',
                    'value' => $account->id,
                ];
            })->toArray();

            $formSchema = [
                'title' => 'Account Statement',
                'submit_url' => '/reports/account-statement',
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'account_id',
                        'label' => 'Account',
                        'options' => $accountOptions,
                        'info' => 'Select the account to view statement',
                        'show_info_icon' => true,
                    ],
                    [
                        'type' => 'daterangepicker',
                        'name' => 'date_range',
                        'label' => 'Date Range',
                        'placeholder' => 'Select date range',
                        'format_specifier' => 'yyyy-MM-dd',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'type',
                        'label' => 'Transaction Type',
                        'options' => [
                            ['label' => 'All', 'value' => '0'],
                            ['label' => 'Debit Only', 'value' => '1'],
                            ['label' => 'Credit Only', 'value' => '2'],
                        ],
                        'info' => 'Filter by transaction type',
                        'show_info_icon' => true,
                    ],
                ],
            ];

            return [
                ...$formSchema,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the form.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    /**
     * Account Statement Handler
     */
    public function accountStatementHandler(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('account-statement')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate input
            $validated = $request->validate([
                'account_id' => 'required|exists:accounts,id',
                'date_range' => 'required|string',
                'type' => 'required|in:0,1,2',
            ]);

            // Parse date range
            $dateRange = explode(' to ', $validated['date_range']);
            $startDate = $dateRange[0] ?? date('Y-m-d');
            $endDate = $dateRange[1] ?? date('Y-m-d');

            // Store in session or pass as query params
            $queryParams = http_build_query([
                'account_id' => $validated['account_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $validated['type'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statement generated successfully.',
                'navigate_type' => 'datatable',
                'navigate_url' => '/reports/account-statement/table?' . $queryParams,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    /**
     * Account Statement Table
     */
    public function accountStatementTable(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('account-statement')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $accountId = $request->input('account_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $type = $request->input('type');

            $account = Account::findOrFail($accountId);

            $transactions = [];

            // Credit transactions (type 0 or 2)
            if ($type == '0' || $type == '2') {
                // Sale payments
                $salePayments = Payment::whereNotNull('sale_id')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($salePayments as $payment) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($payment->created_at)),
                        'reference' => $payment->payment_reference,
                        'type' => 'Sale Payment',
                        'credit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($payment->amount, 2)
                            : number_format($payment->amount, 2) . ' ' . config('currency'),
                        'debit' => '-',
                        'credit_raw' => $payment->amount,
                        'debit_raw' => 0,
                    ];
                }

                // Money transfer received
                $receivedTransfers = MoneyTransfer::where('to_account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($receivedTransfers as $transfer) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($transfer->created_at)),
                        'reference' => $transfer->reference_no,
                        'type' => 'Money Transfer (Received)',
                        'credit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($transfer->amount, 2)
                            : number_format($transfer->amount, 2) . ' ' . config('currency'),
                        'debit' => '-',
                        'credit_raw' => $transfer->amount,
                        'debit_raw' => 0,
                    ];
                }

                // Purchase returns
                $purchaseReturns = DB::table('return_purchases')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($purchaseReturns as $return) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($return->created_at)),
                        'reference' => $return->reference_no,
                        'type' => 'Purchase Return',
                        'credit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($return->grand_total, 2)
                            : number_format($return->grand_total, 2) . ' ' . config('currency'),
                        'debit' => '-',
                        'credit_raw' => $return->grand_total,
                        'debit_raw' => 0,
                    ];
                }
            }

            // Debit transactions (type 0 or 1)
            if ($type == '0' || $type == '1') {
                // Purchase payments
                $purchasePayments = Payment::whereNotNull('purchase_id')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($purchasePayments as $payment) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($payment->created_at)),
                        'reference' => $payment->payment_reference,
                        'type' => 'Purchase Payment',
                        'credit' => '-',
                        'debit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($payment->amount, 2)
                            : number_format($payment->amount, 2) . ' ' . config('currency'),
                        'credit_raw' => 0,
                        'debit_raw' => $payment->amount,
                    ];
                }

                // Expenses
                $expenses = DB::table('expenses')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($expenses as $expense) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($expense->created_at)),
                        'reference' => $expense->reference_no,
                        'type' => 'Expense',
                        'credit' => '-',
                        'debit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($expense->amount, 2)
                            : number_format($expense->amount, 2) . ' ' . config('currency'),
                        'credit_raw' => 0,
                        'debit_raw' => $expense->amount,
                    ];
                }

                // Returns
                $returns = DB::table('returns')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($returns as $return) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($return->created_at)),
                        'reference' => $return->reference_no,
                        'type' => 'Sale Return',
                        'credit' => '-',
                        'debit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($return->grand_total, 2)
                            : number_format($return->grand_total, 2) . ' ' . config('currency'),
                        'credit_raw' => 0,
                        'debit_raw' => $return->grand_total,
                    ];
                }

                // Payroll
                $payrolls = DB::table('payrolls')
                    ->where('account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($payrolls as $payroll) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($payroll->created_at)),
                        'reference' => $payroll->reference_no,
                        'type' => 'Payroll',
                        'credit' => '-',
                        'debit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($payroll->amount, 2)
                            : number_format($payroll->amount, 2) . ' ' . config('currency'),
                        'credit_raw' => 0,
                        'debit_raw' => $payroll->amount,
                    ];
                }

                // Money transfer sent
                $sentTransfers = MoneyTransfer::where('from_account_id', $accountId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->get();

                foreach ($sentTransfers as $transfer) {
                    $transactions[] = [
                        'date' => date('d M, Y', strtotime($transfer->created_at)),
                        'reference' => $transfer->reference_no,
                        'type' => 'Money Transfer (Sent)',
                        'credit' => '-',
                        'debit' => config('currency_position') == 'prefix'
                            ? config('currency') . ' ' . number_format($transfer->amount, 2)
                            : number_format($transfer->amount, 2) . ' ' . config('currency'),
                        'credit_raw' => 0,
                        'debit_raw' => $transfer->amount,
                    ];
                }
            }

            // Sort by date
            usort($transactions, function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Calculate totals
            $totalCredit = array_sum(array_column($transactions, 'credit_raw'));
            $totalDebit = array_sum(array_column($transactions, 'debit_raw'));
            $balance = $totalCredit - $totalDebit;

            return [
                'title' => 'Account Statement: ' . $account->name,
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Type', 'field' => 'type', 'type' => 'text'],
                    ['label' => 'Credit', 'field' => 'credit', 'type' => 'text'],
                    ['label' => 'Debit', 'field' => 'debit', 'type' => 'text'],
                ],
                'rows' => $transactions,
                'footer' => [
                    'Total Credit' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($totalCredit, 2)
                        : number_format($totalCredit, 2) . ' ' . config('currency'),
                    'Total Debit' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($totalDebit, 2)
                        : number_format($totalDebit, 2) . ' ' . config('currency'),
                    'Balance' => config('currency_position') == 'prefix'
                        ? config('currency') . ' ' . number_format($balance, 2)
                        : number_format($balance, 2) . ' ' . config('currency'),
                ],
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the account statement.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }
}
