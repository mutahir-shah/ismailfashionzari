<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// use App\Http\Controllers\DemoAutoUpdateController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerGroupController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\BillerController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\DiscountPlanController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\IncomeCategoryController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\InvoiceSettingController;
use App\Http\Controllers\Api\BarcodeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\MoneyTransferController;
use App\Http\Controllers\Api\ReturnSaleController;
use App\Http\Controllers\Api\ReturnPurchaseController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SMSController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdjustmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\GiftCardController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\PackingSlipController;
use App\Http\Controllers\Api\ChallanController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RouteMapController;
use App\Http\Controllers\Api\StockCountController;
use App\Http\Controllers\Api\SetupController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\SaleAgentController;
use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\ActivityLogController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$middleware = ['api'];
if (config('database.connections.saleprosaas_landlord')) {
    $middleware[] = InitializeTenancyByDomain::class;
    $middleware[] = PreventAccessFromCentralDomains::class;
}

Route::group(['middleware' => ['api'], 'as' => 'api.'], function () {
    Route::get('/setup', [SetupController::class, 'setup'])->name('setup');
});

Route::middleware($middleware)->name('api.')->group(function () {
    Route::post('/check', [SetupController::class, 'checkLicense'])->name('check');
    if (class_exists(RouteMapController::class)) {
        Route::get('/offline-api-map', [RouteMapController::class, 'index'])->name('offline-api-map');
    }

    // Optional Mobile App: Authentication & Home Endpoints
    if (class_exists(RegisterController::class) || class_exists(LoginController::class) || class_exists(HomeController::class)) {
        Route::middleware('validate_mobile_token')->group(function () {
            if (class_exists(RegisterController::class)) {
                Route::get('/get-registration-form-data', [RegisterController::class, 'getRegistrationFormData']);
                Route::get('/register/create', [RegisterController::class, 'create']);
                Route::post('/register', [RegisterController::class, 'register']);
            }
            if (class_exists(LoginController::class)) {
                Route::get('/login/create', [LoginController::class, 'getLoginForm']);
                Route::post('/login', [LoginController::class, 'login']);
            }
            if (class_exists(HomeController::class)) {
                Route::controller(HomeController::class)->middleware(['auth:sanctum', 'warehouse.access'])->group(function () {
                    Route::get('/get-user', 'getUser');
                    Route::get('/dashboard', 'dashboard');
                });
            }
        });

        if (class_exists(HomeController::class)) {
            Route::controller(HomeController::class)->middleware(['auth:sanctum', 'warehouse.access'])->group(function () {
                Route::get('/get-user', 'getUser');
                Route::get('/dashboard', 'dashboard');
                Route::get('/dashboard-filter/{start_date}/{end_date}/{warehouse_id}', 'dashboard');
            });
        }
    }

    // Optional Mobile App: Authenticated API Resources
    if (class_exists(BrandController::class)) {
        Route::group(['middleware' => ['auth:sanctum', 'common', 'validate_mobile_token', 'warehouse.access']], function () {
        Route::get('test', [BrandController::class, 'test']);
        Route::resource('brands', BrandController::class);
        // Category import routes must come BEFORE resource route
        Route::get('categories/import', [CategoryController::class, 'import']);
        Route::post('categories/import', [CategoryController::class, 'import']);
        Route::resource('categories', CategoryController::class);
        Route::resource('units', UnitController::class);
        // Product routes - search and import must come BEFORE resource route
        Route::get('products/search', [ProductController::class, 'searchProducts']);
        Route::get('products/search/{query}', [ProductController::class, 'searchProducts']);
        Route::get('products/print-barcode/form', [ProductController::class, 'printBarcodeForm']);
        Route::get('products/import', [ProductController::class, 'import']);
        Route::resource('products', ProductController::class);
        Route::post('products/{id}', [ProductController::class, 'update']);
        // Supplier import routes must come BEFORE resource route
        Route::get('suppliers/import', [SupplierController::class, 'import']);
        Route::post('suppliers/import', [SupplierController::class, 'import']);
        Route::resource('suppliers', SupplierController::class);
        // Currency import routes must come BEFORE resource route
        Route::get('currencies/import', [CurrencyController::class, 'import']);
        Route::post('currencies/import', [CurrencyController::class, 'import']);
        Route::resource('currencies', CurrencyController::class);
        // Warehouse import routes must come BEFORE resource route
        Route::get('warehouses/import', [WarehouseController::class, 'import']);
        Route::post('warehouses/import', [WarehouseController::class, 'import']);
        Route::resource('warehouses', WarehouseController::class);
        // Tax import routes must come BEFORE resource route
        Route::get('taxes/import', [TaxController::class, 'import']);
        Route::post('taxes/import', [TaxController::class, 'import']);
        Route::resource('taxes', TaxController::class)->except(['show']);
        // Purchase import and payment routes must come BEFORE resource route
        Route::get('purchases/import', [PurchaseController::class, 'import']);
        Route::post('purchases/import', [PurchaseController::class, 'import']);
        Route::get('purchases/{id}/payments', [PurchaseController::class, 'getPayments']);
        Route::get('purchases/{id}/add-payment', [PurchaseController::class, 'addPaymentForm']);
        Route::resource('purchases', PurchaseController::class);
        // Customer import routes must come BEFORE resource route
        Route::get('customers/import', [CustomerController::class, 'import']);
        Route::post('customers/import', [CustomerController::class, 'import']);
        Route::resource('customers', CustomerController::class);
        Route::resource('billers', BillerController::class);
        // Customer group import routes must come BEFORE resource route
        Route::get('customer-groups/import', [CustomerGroupController::class, 'import']);
        Route::post('customer-groups/import', [CustomerGroupController::class, 'import']);
        Route::resource('customer-groups', CustomerGroupController::class);
        // Sale import and payment routes must come BEFORE resource route
        Route::get('sales/import', [SaleController::class, 'import']);
        Route::post('sales/import', [SaleController::class, 'import']);
        Route::get('sales/{id}/payments', [SaleController::class, 'getPayments']);
        Route::get('sales/{id}/add-payment', [SaleController::class, 'addPaymentForm']);
        Route::resource('sales', SaleController::class);
        Route::get('pos/create', [PosController::class, 'create']);
        Route::get('generate-code', [ProductController::class, 'generateCode']);
        Route::resource('discount-plans', DiscountPlanController::class);
        Route::resource('discounts', DiscountController::class);
        Route::get('discounts/product-search/{code}', [DiscountController::class, 'productSearch']);
        Route::resource('expense-categories', ExpenseCategoryController::class);
        Route::resource('expenses', ExpenseController::class);
        Route::resource('income-categories', IncomeCategoryController::class);
        Route::resource('incomes', IncomeController::class);

        Route::controller(ExpenseCategoryController::class)->group(function () {
            Route::get('expense-categories/gencode', 'generateCode');
            Route::post('expense-categories/deletebyselection', 'deleteBySelection');
        });

        Route::controller(ExpenseController::class)->group(function () {
        });

        // IncomeCategory & Income Start
        Route::controller(IncomeCategoryController::class)->group(function () {
            Route::get('income-categories/gencode', 'generateCode');
            Route::post('income-categories/deletebyselection', 'deleteBySelection');
        });

        Route::controller(IncomeController::class)->group(function () {
            Route::post('incomes/deletebyselection', 'deleteBySelection');
        });

        // IncomeCategory & Income End

        // Settings Start
        Route::controller(SettingController::class)->group(function () {
            Route::get('general-setting', 'generalSetting');
            Route::post('general-setting', 'generalSettingStore')->name('setting.generalStore');
            Route::get('theme-setting', 'themeSetting')->name('setting.themeSetting');
            Route::post('change-active-theme/{id}', 'changeActiveThemeSetting')->name('setting.changeActiveTheme');
            Route::get('pos-setting', 'posSetting');
            Route::post('pos-setting', 'posSettingStore')->name('setting.posStore');
            Route::get('mail-setting', 'mailSetting');
            Route::post('mail-settings', 'mailSettingStore')->name('setting.mailStore');
            Route::get('reward-point-setting', 'rewardPointSetting');
            Route::post('reward-point-settings', 'rewardPointSettingStore');
            Route::get('sms-setting', 'smsSetting');
            Route::post('sms-settings', 'smsSettingStore');
            Route::get('payment-gateway-setting', 'paymentGatewaySetting');
            Route::get('hrm-setting', 'hrmSetting');
            Route::prefix('setting')->group(function () {
                Route::post('hrm-setting', 'hrmSettingStore')->name('setting.hrmStore');
            });
            Route::post('payment-gateways', 'gatewayUpdate')->name('setting.gateway.update');
            Route::get('backup', 'backup')->name('setting.backup');
        });

        // Invoice Settings Start
        Route::controller(InvoiceSettingController::class)->group(function () {
            Route::prefix('invoice-settings')->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/set-default', 'setDefault');
            });
        });
        // Invoice Settings End

        // Barcode Settings Start
        Route::controller(BarcodeController::class)->group(function () {
            Route::prefix('barcodes')->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/set-default', 'setDefault');
            });
        });
        // Barcode Settings End

        // Languages Start
        Route::controller(LanguageController::class)->group(function () {
            Route::prefix('languages')->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
                Route::post('{id}/set-default', 'setDefault');
            });
        });
        // Languages End

        // Tables Start
        Route::controller(TableController::class)->group(function () {
            Route::prefix('tables')->group(function () {
                Route::get('/', 'index');
                Route::get('create', 'create');
                Route::post('/', 'store');
                Route::get('{id}/edit', 'edit');
                Route::put('{id}', 'update');
                Route::delete('{id}', 'destroy');
            });
        });
        // Tables End

        // Notifications
        Route::controller(NotificationController::class)->group(function () {
            Route::prefix('notifications')->group(function () {
                Route::get('/', 'index')->name('notifications.index');
                Route::post('store', 'store')->name('notifications.store');
                Route::get('mark-as-read', 'markAsRead')->name('notifications.markAsRead');
            });
        });

        Route::middleware('accounting.activated')->group(function () {
            Route::controller(AccountController::class)->group(function () {
                Route::get('make-default/{id}', 'makeDefault');
                Route::get('balancesheet', 'balanceSheet')->name('accounts.balancesheet');
                Route::post('account-statement', 'accountStatement')->name('accounts.statement');
                Route::get('accounts/all', 'accountsAll')->name('account.all');
            });
            Route::resource('accounts', AccountController::class);
        });

        Route::resource('money-transfers', MoneyTransferController::class);

        // Return Sale & Purchase
        Route::controller(ReturnSaleController::class)->group(function () {
            Route::prefix('return-sale')->group(function () {
            });
        });
        // Generate route must come BEFORE resource route
        Route::get('generate/return-reference', [ReturnSaleController::class, 'generateReference']);
        Route::resource('return-sale', ReturnSaleController::class)->except(['show']);


        Route::controller(ReturnPurchaseController::class)->group(function () {
            Route::prefix('return-purchase')->group(function () {
            });
        });
        Route::resource('return-purchase', ReturnPurchaseController::class)->except(['show']);
        Route::resource('transfers', TransferController::class);

        Route::controller(PosController::class)->group(function () {
            Route::get('pos/create', 'create');
            Route::get('pos/step-2', 'step2');
            Route::get('pos/product-search', 'productSearch');
            Route::get('pos/recent-sale', 'recentSale');
            Route::get('pos/recent-draft', 'recentDraft');
            Route::get('pos/sale/{id}', 'getSaleDetails');
            Route::post('pos/store', 'store');
            Route::get('pos/payment-offline', 'getOfflinePaymentSchema');
            Route::get('pos/payment-form/{id}', 'paymentForm');
            Route::post('pos/payment', 'processPayment');
            // Notification & Invoice
            Route::post('pos/send-mail', 'sendMail');
            Route::post('pos/send-sms', 'sendSMS');
            Route::post('pos/send-whatsapp', 'whatsappNotificationSend');
            Route::get('pos/invoice-data/{id}', 'getInvoiceData');
        });

        Route::controller(QuotationController::class)->group(function () {
            Route::prefix('quotations')->group(function () {
            });
        });

        Route::resource('quotations', QuotationController::class)->except(['show']);

        // HRM Routes
        Route::resource('adjustments', AdjustmentController::class);
        Route::controller(AdjustmentController::class)->group(function () {
        });

        Route::resource('employees', EmployeeController::class);
        Route::controller(EmployeeController::class)->group(function () {
        });

        Route::resource('departments', DepartmentController::class);
        Route::controller(DepartmentController::class)->group(function () {
            Route::post('departments/deletebyselection', 'deleteBySelection');
        });

        Route::resource('payroll', PayrollController::class);
        Route::controller(PayrollController::class)->group(function () {
            Route::post('payroll/deletebyselection', 'deleteBySelection');
        });

        Route::resource('holidays', HolidayController::class)->except(['show']);
        Route::controller(HolidayController::class)->group(function () {
            Route::get('holidays/{id}/approve', 'approve');
        });

        // Attendance import routes must come BEFORE resource route
        Route::get('attendances/import', [AttendanceController::class, 'import']);
        Route::post('attendances/import', [AttendanceController::class, 'import']);
        Route::resource('attendances', AttendanceController::class)->except(['show', 'edit', 'update']);
        Route::controller(AttendanceController::class)->group(function () {
            Route::delete('attendances/{date}/{employee_id}', 'destroy');
            Route::post('attendances/deletebyselection', 'deleteBySelection');
        });

        Route::resource('gift-cards', GiftCardController::class);
        Route::controller(GiftCardController::class)->group(function () {
            Route::get('gift-cards/generate-code', 'generateCode');
            Route::get('gift-cards/{id}/recharge', 'rechargeForm');
            Route::post('gift-cards/{id}/recharge', 'recharge');
            Route::post('gift-cards/deletebyselection', 'deleteBySelection');
        });

        // New Controllers
        Route::resource('coupons', CouponController::class);
        Route::controller(CouponController::class)->group(function () {
            Route::get('coupons/generate-code', 'generateCode');
            Route::post('coupons/deletebyselection', 'deleteBySelection');
        });

        Route::resource('couriers', CourierController::class)->except(['show']);

        Route::controller(DeliveryController::class)->group(function () {
            Route::get('delivery/create/{saleId}', 'create');
        });
        Route::resource('deliveries', DeliveryController::class)->except(['destroy']);

        Route::resource('packing-slips', PackingSlipController::class)->only(['index']);

        Route::resource('challans', ChallanController::class)->only(['index']);

        Route::resource('roles', RoleController::class);
        Route::controller(RoleController::class)->group(function () {
        });

        Route::resource('stock-counts', StockCountController::class)->except(['edit', 'update', 'destroy']);
        Route::controller(StockCountController::class)->group(function () {
            Route::get('stock-counts/{id}/finalize', 'finalize');
            Route::post('stock-counts/{id}/finalize', 'storeFinalize');
            Route::get('stock-counts/{id}/download-initial', 'downloadInitial');
            Route::get('stock-counts/{id}/download-final', 'downloadFinal');
        });

        // Report Routes - Clean Form-Based Structure
        Route::controller(ReportController::class)->group(function () {
            Route::prefix('reports')->group(function () {
                // Sale Report
                Route::get('sale-report/create', 'saleReportForm');
                Route::post('sale-report', 'saleReportHandler');
                Route::get('sale-report/table', 'saleReportTable');

                // Purchase Report  
                Route::get('purchase-report/create', 'purchaseReportForm');
                Route::post('purchase-report', 'purchaseReportHandler');
                Route::get('purchase-report/table', 'purchaseReportTable');

                // Customer Report
                Route::get('customer-report/create', 'customerReportForm');
                Route::post('customer-report', 'customerReportHandler');
                Route::get('customer-report/table', 'customerReportTable');

                // Supplier Report
                Route::get('supplier-report/create', 'supplierReportForm');
                Route::post('supplier-report', 'supplierReportHandler');
                Route::get('supplier-report/table', 'supplierReportTable');

                // Warehouse Report
                Route::get('warehouse-report/create', 'warehouseReportForm');
                Route::post('warehouse-report', 'warehouseReportHandler');
                Route::get('warehouse-report/table', 'warehouseReportTable');

                // Biller Report
                Route::get('biller-report/create', 'billerReportForm');
                Route::post('biller-report', 'billerReportHandler');
                Route::get('biller-report/table', 'billerReportTable');

                // User Report
                Route::get('user-report/create', 'userReportForm');
                Route::post('user-report', 'userReportHandler');
                Route::get('user-report/table', 'userReportTable');

                // Product Report
                Route::get('product-report/create', 'productReportForm');
                Route::post('product-report', 'productReportHandler');
                Route::get('product-report/table', 'productReportTable');

                // Profit/Loss Report
                Route::get('profit-loss-report/create', 'profitLossReportForm');
                Route::post('profit-loss-report', 'profitLossReportHandler');
                Route::get('profit-loss-report/table', 'profitLossReportTable');

                // Customer Group Report
                Route::get('customer-group-report/create', 'customerGroupReportForm');
                Route::post('customer-group-report', 'customerGroupReportHandler');
                Route::get('customer-group-report/table', 'customerGroupReportTable');

                // Payment Report
                Route::get('payment-report/create', 'paymentReportForm');
                Route::post('payment-report', 'paymentReportHandler');
                Route::get('payment-report/table', 'paymentReportTable');

                // Challan Report
                Route::get('challan-report/create', 'challanReportForm');
                Route::post('challan-report', 'challanReportHandler');
                Route::get('challan-report/table', 'challanReportTable');

                // Best Seller Report (Datatable - no form)
                Route::get('best-seller', 'bestSellerReport');

                // Daily/Monthly Sale Reports (Parameterized)
                Route::get('daily-sale/{year}/{month}', 'dailySaleReport');
                Route::get('monthly-sale/{year}', 'monthlySaleReport');

                // Daily/Monthly Purchase Reports (Parameterized)
                Route::get('daily-purchase/{year}/{month}', 'dailyPurchaseReport');
                Route::get('monthly-purchase/{year}', 'monthlyPurchaseReport');

                // Product Expiry Report (Datatable - no form)
                Route::get('product-expiry', 'productExpiryReport');

                // Quantity Alert Report (Datatable - no form)
                Route::get('quantity-alert', 'quantityAlertReport');

                // DSO Report
                Route::get('dso-report/create', 'dsoReportForm');
                Route::post('dso-report', 'dsoReportHandler');
                Route::get('dso-report/table', 'dsoReportTable');

                // Due Report (Customer)
                Route::get('due-report/create', 'dueReportForm');
                Route::post('due-report', 'dueReportHandler');
                Route::get('due-report/table', 'dueReportTable');

                // Warehouse Stock Report
                Route::get('warehouse-stock/create', 'warehouseStockReportForm');
                Route::post('warehouse-stock', 'warehouseStockReportHandler');
                Route::get('warehouse-stock/table', 'warehouseStockReportTable');

                // Balance Sheet Report (Custom view - no form)
                Route::get('balance-sheet', 'balanceSheetReport');

                // Account Statement Report
                Route::middleware('accounting.activated')->group(function () {
                    Route::get('account-statement/form', 'accountStatementForm');
                    Route::post('account-statement', 'accountStatementHandler');
                    Route::get('account-statement/table', 'accountStatementTable');
                });
            });
        });

        Route::controller(UserController::class)->group(function () {
        });

        // SMS Templates Routes
        Route::controller(SMSController::class)->group(function () {
            Route::get('sms/create', 'createSms')->name('sms.create');
            Route::post('sms/send', 'sendSms')->name('sms.send');
            Route::post('sms-templates/{id}/make-default', 'makeDefault')->name('sms-templates.makeDefault');
            Route::post('sms-templates/{id}/make-default-ecommerce', 'makeDefaultEcommerce')->name('sms-templates.makeDefaultEcommerce');
        });
        Route::resource('sms-templates', SMSController::class)->except(['show']);

        // Manufacturing
        if (class_exists(ProductionController::class)) {
            Route::resource('productions', ProductionController::class)->except(['show', 'edit', 'update']);
        }
        if (class_exists(RecipeController::class)) {
            Route::resource('recipes', RecipeController::class)->except(['show', 'edit', 'update']);
        }

        // People
        if (class_exists(SaleAgentController::class)) {
            Route::resource('sale-agents', SaleAgentController::class)->except(['show', 'edit', 'update']);
        }

        // Whatsapp
        if (class_exists(WhatsappController::class)) {
            Route::controller(WhatsappController::class)->group(function () {
                Route::get('whatsapp/settings', 'settings');
                Route::post('whatsapp/settings', 'updateSettings');
                Route::get('whatsapp/templates', 'templates');
                Route::delete('whatsapp/templates/{name}', 'deleteTemplate');
                Route::get('whatsapp/send', 'sendPage');
                Route::post('whatsapp/send', 'sendMessage');
            });
        }

        // Reports
        if (class_exists(ActivityLogController::class)) {
            Route::get('reports/activity-log', [ActivityLogController::class, 'index']);
        }

        Route::resource('users', UserController::class)->except(['show']);
        });
    }
});
