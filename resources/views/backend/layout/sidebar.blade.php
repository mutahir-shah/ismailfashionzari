<ul id="side-main-menu" class="side-menu list-unstyled">
    <li><a class="{{ request()->is('dashboard') ? 'active' : '' }}" href="{{url('/dashboard')}}"> <i class="ti ti-dashboard"></i><span>{{__('db.dashboard')}}</span></a></li>

    @canany([
    'customers-index',
    'suppliers-index',
    'users-index',
    'sale-agents',
    'billers-index'
    ])
    <li>
        <a href="#people" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-users"></i><span>{{__('db.People')}}</span></a>

        <ul id="people" class="collapse list-unstyled ">
            @can('customers-index')
            <li id="customer-list-menu"><a class="{{ request()->is('customer') ? 'active' : '' }}" href="{{route('customer.index')}}">{{__('db.Customer List')}}</a></li>
            @endcan
            @can('suppliers-index')
            <li id="supplier-list-menu"><a class="{{ request()->is('supplier') ? 'active' : '' }}" href="{{route('supplier.index')}}">{{__('db.Supplier List')}}</a></li>
            @endcan
            @can('users-index')
            <li id="user-list-menu"><a class="{{ request()->is('user') ? 'active' : '' }}" href="{{route('user.index')}}">{{__('db.User List')}}</a></li>
            @endcan
            @can('sale-agents')
            <li id="sale-agent-menu"><a class="{{ request()->is('sale-agents') ? 'active' : '' }}" href="{{route('sale-agents.index')}}">{{__('db.Sale Agents')}}</a></li>
            @endcan
            @can('billers-index')
            <li id="biller-list-menu"><a class="{{ request()->is('biller') ? 'active' : '' }}" href="{{route('biller.index')}}">{{__('db.Biller List')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany([
    'categories-index',
    'brand',
    'unit',
    'products-index',
    'products-add',
    'print_barcode',
    'adjustment',
    'stock_count',
    'damage-stock'
    ])
    <li>
        <a href="#product" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-cube"></i><span>{{__('db.product')}}</span></a>

        <ul id="product" class="collapse list-unstyled ">
            @can('categories-index')
            <li id="category-menu"><a class="{{ request()->is('category') ? 'active' : '' }}" href="{{route('category.index')}}">{{__('db.category')}}</a></li>
            @endcan
            @can('brand')
            <li id="brand-menu"><a class="{{ request()->is('brand') ? 'active' : '' }}" href="{{route('brand.index')}}">{{__('db.Brand')}}</a></li>
            @endcan
            @can('unit')
            <li id="unit-menu"><a class="{{ request()->is('unit') ? 'active' : '' }}" href="{{route('unit.index')}}">{{__('db.Unit')}}</a></li>
            @endcan
            @can('products-index')
            <li id="product-list-menu"><a class="{{ request()->is('products') ? 'active' : '' }}" href="{{route('products.index')}}">{{__('db.product_list')}}</a></li>
            @endcan
            @can('products-add')
            <li id="product-create-menu"><a class="{{ request()->is('products/create') ? 'active' : '' }}" href="{{route('products.create')}}">{{__('db.add_product')}}</a></li>
            @endcan
            @can('print_barcode')
            <li id="printBarcode-menu"><a class="{{ request()->is('products/print_barcode') ? 'active' : '' }}" href="{{route('product.printBarcode')}}">{{__('db.print_barcode')}}</a></li>
            @endcan
            @can('adjustment')
            <li id="adjustment-list-menu"><a class="{{ request()->is('qty_adjustment') ? 'active' : '' }}" href="{{route('qty_adjustment.index')}}">{{__('db.Adjustment List')}}</a></li>
            <li id="adjustment-create-menu"><a class="{{ request()->is('qty_adjustment/create') ? 'active' : '' }}" href="{{route('qty_adjustment.create')}}">{{__('db.Add Adjustment')}}</a></li>
            @endcan
            @can('stock_count')
            <li id="stock-count-menu"><a class="{{ request()->is('stock-count') ? 'active' : '' }}" href="{{route('stock-count.index')}}">{{__('db.Stock Count')}}</a></li>
            @endcan

            @can('damage-stock')
            <li id="damage-stock-list-menu"><a class="{{ request()->is('damage-stock') ? 'active' : '' }}" href="{{route('damage-stock.index')}}">{{__('db.Damage List')}}</a></li>
            @endcan

        </ul>
    </li>
    @endcanany

    @canany([
    'purchases-index',
    'purchases-add',
    'purchases-import',
    'purchase-return-index'
    ])
    <li>
        <a href="#purchase" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-credit-card-pay"></i><span>{{__('db.Purchase')}}</span></a>

        <ul id="purchase" class="collapse list-unstyled ">
            @can('purchases-index')
            <li id="purchase-list-menu"><a class="{{ request()->is('purchases') ? 'active' : '' }}" href="{{route('purchases.index')}}">{{__('db.Purchase List')}}</a></li>
            @endcan
            @can('purchases-add')
            <li id="purchase-create-menu"><a class="{{ request()->is('purchases/create') ? 'active' : '' }}" href="{{route('purchases.create')}}">{{__('db.Add Purchase')}}</a></li>
            @endcan
            @can('purchases-import')
            <li id="purchase-import-menu"><a class="{{ request()->is('purchases/purchase_by_csv') ? 'active' : '' }}" href="{{url('purchases/purchase_by_csv')}}">{{__('db.Import Purchase By CSV')}}</a></li>
            @endcan
            @can('purchase-return-index')
            <li id="purchase-return-menu"><a class="{{ request()->is('return-purchase') ? 'active' : '' }}" href="{{route('return-purchase.index')}}">{{__('db.Purchase Return')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany([
    'sales-index',
    'sales-add',
    'sales-import',
    'packing_slip_challan',
    'delivery',
    'gift_card',
    'coupon',
    'returns-index'
    ])
    <li>
        <a href="#sale" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-shopping-cart"></i><span>{{__('db.Sale')}}</span></a>

        <ul id="sale" class="collapse list-unstyled ">
            @can('sales-index')
            <li id="sale-list-menu"><a class="{{ request()->is('sales') ? 'active' : '' }}" href="{{route('sales.index')}}">{{__('db.Sale List')}}</a></li>
            <li id="installment-list-menu"><a class="{{ request()->is('installmentplan') ? 'active' : '' }}" href="{{route('installmentplan.index')}}">{{__('db.Instalment List')}}</a></li>
            @endcan
            @can('sales-add')
            <li><a class="{{ request()->is('pos') ? 'active' : '' }}" href="{{route('sale.pos')}}">POS</a></li>
            <li id="sale-create-menu"><a class="{{ request()->is('sales/create') ? 'active' : '' }}" href="{{route('sales.create')}}">{{__('db.Add Sale')}}</a></li>
            @endcan
            @can('sales-import')
            <li id="sale-import-menu"><a class="{{ request()->is('sales/sale_by_csv') ? 'active' : '' }}" href="{{url('sales/sale_by_csv')}}">{{__('db.Import Sale By CSV')}}</a></li>
            @endcan
            @can('packing_slip_challan')
            <li id="packing-list-menu"><a class="{{ request()->is('packing-slips') ? 'active' : '' }}" href="{{route('packingSlip.index')}}">{{__('db.Packing Slip List')}}</a></li>
            <li id="challan-list-menu"><a class="{{ request()->is('challans') ? 'active' : '' }}" href="{{route('challan.index')}}">{{__('db.Challan List')}}</a></li>
            @endcan
            @can('delivery')
            <li id="delivery-menu"><a class="{{ request()->is('delivery') ? 'active' : '' }}" href="{{route('delivery.index')}}">{{__('db.Delivery List')}}</a></li>
            @endcan
            @can('gift_card')
            <li id="gift-card-menu"><a class="{{ request()->is('gift_cards') ? 'active' : '' }}" href="{{route('gift_cards.index')}}">{{__('db.Gift Card List')}}</a> </li>
            @endcan
            @can('coupon')
            <li id="coupon-menu"><a class="{{ request()->is('coupons') ? 'active' : '' }}" href="{{route('coupons.index')}}">{{__('db.Coupon List')}}</a> </li>
            @endcan
            <li id="courier-menu"><a class="{{ request()->is('couriers') ? 'active' : '' }}" href="{{route('couriers.index')}}">{{__('db.Courier List')}}</a> </li>
            @can('returns-index')
            <li id="sale-return-menu"><a class="{{ request()->is('return-sale') ? 'active' : '' }}" href="{{route('return-sale.index')}}">{{__('db.Sale Return')}}</a></li>
            @endcan
            @can('exchange-index')
            <li id="exchange-menu"><a class="{{ request()->is('exchange') ? 'active' : '' }}" href="{{route('exchange.index')}}">{{__('db.Sale Exchange')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany(['quotes-index', 'quotes-add'])
    <li>
        <a href="#quotation" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-file-like"></i><span>{{__('db.quotation')}}</span></a>

        <ul id="quotation" class="collapse list-unstyled ">
            @can('quotes-index')
            <li id="quotation-list-menu"><a class="{{ request()->is('quotations') ? 'active' : '' }}" href="{{route('quotations.index')}}">{{__('db.Quotation List')}}</a></li>
            @endcan
            @can('quotes-add')
            <li id="quotation-create-menu"><a class="{{ request()->is('quotations/create') ? 'active' : '' }}" href="{{route('quotations.create')}}">{{__('db.Add Quotation')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany([
    'transfers-index',
    'transfers-add',
    'transfers-import'
    ])
    <li>
        <a href="#transfer" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-transfer"></i><span>{{__('db.Transfer')}}</span></a>
        <ul id="transfer" class="collapse list-unstyled ">
            @can('transfers-index')
            <li id="transfer-list-menu"><a class="{{ request()->is('transfers') ? 'active' : '' }}" href="{{route('transfers.index')}}">{{__('db.Transfer List')}}</a></li>
            @endcan
            @can('transfers-add')
            <li id="transfer-create-menu"><a class="{{ request()->is('transfers/create') ? 'active' : '' }}" href="{{route('transfers.create')}}">{{__('db.Add Transfer')}}</a></li>
            @endcan
            @can('transfers-import')
            <li id="transfer-import-menu"><a class="{{ request()->is('transfers/transfer_by_csv') ? 'active' : '' }}" href="{{url('transfers/transfer_by_csv')}}">{{__('db.Import Transfer By CSV')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany([
    'expense-categories',
    'expenses-index',
    'expenses-add'
    ])
    <li>
        <a href="#expense" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-wallet"></i><span>{{__('db.Expense')}}</span></a>

        <ul id="expense" class="collapse list-unstyled ">
            @can('expense-categories')
            <li id="exp-cat-menu"><a class="{{ request()->is('expense_categories') ? 'active' : '' }}" href="{{route('expense_categories.index')}}">{{__('db.Expense Category')}}</a></li>
            @endcan
            @can('expenses-index')
            <li id="exp-list-menu"><a class="{{ request()->is('expenses') ? 'active' : '' }}" href="{{route('expenses.index')}}">{{__('db.Expense List')}}</a></li>
            @endcan
            @can('expenses-add')
            <li><a id="add-expense" href=""> {{__('db.Add Expense')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @canany([
    'income-categories',
    'incomes-index',
    'incomes-add'
    ])
    <li>
        <a href="#income" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-cash-plus"></i><span>{{__('db.Income')}}</span></a>
        <ul id="income" class="collapse list-unstyled ">
            @can('income-categories')
            <li id="income-cat-menu"><a class="{{ request()->is('income_categories') ? 'active' : '' }}" href="{{route('income_categories.index')}}">{{__('db.Income Category')}}</a></li>
            @endcan
            @can('incomes-index')
            <li id="income-list-menu"><a class="{{ request()->is('incomes') ? 'active' : '' }}" href="{{route('incomes.index')}}">{{__('db.Income List')}}</a></li>
            @endcan
            @can('incomes-add')
            <li><a id="add-income" href=""> {{__('db.Add Income')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @php
        $accountingMenuPermissions = app(\App\Services\PermissionService::class)->getAuthenticatedUserPermissions();
        $doubleEntryAccounting = app(\App\Services\AccountingModeService::class)->isDoubleEntryAuthoritative();
        $accountingMenuVisible = !empty(array_intersect($accountingMenuPermissions, [
            'account-index',
            'chart-of-accounts-manage',
            'semantic-account-mappings-manage',
            'money-transfer',
            'balance-sheet',
            'account-statement',
        ]));
    @endphp
    @if($accountingMenuVisible)
    <li class="">
        <a href="#account" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-briefcase"></i><span>{{__('db.Accounting')}}</span></a>

        <ul id="account" class="collapse list-unstyled ">
            @if($doubleEntryAccounting)
            @if(in_array('account-index', $accountingMenuPermissions, true))
            <li><a class="{{ request()->is('accounting/reconciliation') ? 'active' : '' }}" href="{{route('accounting.reconciliation.index')}}">{{ __('db.accounting_health_title') }}</a></li>
            @endif
            @if(in_array('account-statement', $accountingMenuPermissions, true))
            <li><a class="{{ request()->is('accounting/trial-balance') ? 'active' : '' }}" href="{{route('accounting.trialBalance')}}">{{ __('db.Trial Balance') }}</a></li>
            <li><a class="{{ request()->is('accounting/general-ledger') ? 'active' : '' }}" href="{{route('accounting.generalLedger')}}">{{ __('db.General Ledger') }}</a></li>
            @endif
            @if(in_array('balance-sheet', $accountingMenuPermissions, true))
            <li><a class="{{ request()->is('accounting/balance-sheet') ? 'active' : '' }}" href="{{route('accounting.balanceSheet')}}">{{ __('db.Balance Sheet') }}</a></li>
            <li><a class="{{ request()->is('accounting/profit-loss') ? 'active' : '' }}" href="{{route('accounting.profitAndLoss')}}">{{ __('db.Profit and Loss') }}</a></li>
            <li><a class="{{ request()->is('accounting/cash-flow-statement') ? 'active' : '' }}" href="{{route('accounting.cashFlowStatement')}}">{{ __('db.Cash Flow Statement') }}</a></li>
            @endif
            @if(in_array('account-index', $accountingMenuPermissions, true))
            <li><a class="{{ request()->is('accounting/inventory-close*') ? 'active' : '' }}" href="{{route('accounting.inventory-close.index')}}">{{ __('db.inventory_close_title') }}</a></li>
            @endif
            @if(in_array('chart-of-accounts-manage', $accountingMenuPermissions, true))
            <li id="chart-of-accounts-menu"><a class="{{ request()->is('accounting/chart-of-accounts*') ? 'active' : '' }}" href="{{route('accounting.chart-of-accounts.index')}}">{{ __('db.chart_of_accounts_title') }}</a></li>
            @endif
            @if(in_array('semantic-account-mappings-manage', $accountingMenuPermissions, true))
            <li id="semantic-account-mappings-menu"><a class="{{ request()->is('accounting/semantic-account-mappings*') ? 'active' : '' }}" href="{{route('accounting.semantic-mappings.index')}}">{{ __('db.semantic_account_mappings_title') }}</a></li>
            @endif
            @if(in_array('account-index', $accountingMenuPermissions, true))
            <li id="account-list-menu"><a class="{{ request()->is('accounts') ? 'active' : '' }}" href="{{route('accounts.index')}}">{{__('db.payment_accounts_title')}}</a></li>
            @endif
            @if(in_array('account-add', $accountingMenuPermissions, true))
            <li><a id="add-account" href="">{{__('db.payment_account_add')}}</a></li>
            @endif
            @if(in_array('money-transfer', $accountingMenuPermissions, true))
            <li id="money-transfer-menu"><a class="{{ request()->is('money-transfers') ? 'active' : '' }}" href="{{route('money-transfers.index')}}">{{__('db.Money Transfer')}}</a></li>
            @endif
            @else
            @if(in_array('account-index', $accountingMenuPermissions, true))
            <li id="account-list-menu"><a class="{{ request()->is('accounts') ? 'active' : '' }}" href="{{route('accounts.index')}}">{{__('db.Account List')}}</a></li>
            @endif
            @if(in_array('account-add', $accountingMenuPermissions, true))
            <li><a id="add-account" href="">{{__('db.Add Account')}}</a></li>
            @endif
            @if(in_array('money-transfer', $accountingMenuPermissions, true))
            <li id="money-transfer-menu"><a class="{{ request()->is('money-transfers') ? 'active' : '' }}" href="{{route('money-transfers.index')}}">{{__('db.Money Transfer')}}</a></li>
            @endif
            @if(in_array('balance-sheet', $accountingMenuPermissions, true))
            <li id="balance-sheet-menu"><a class="{{ request()->is('balancesheet') ? 'active' : '' }}" href="{{route('accounts.balancesheet')}}">{{__('db.Balance Sheet')}}</a></li>
            @endif
            @if(in_array('account-statement', $accountingMenuPermissions, true))
            <li id="account-statement-menu"><a id="account-statement" class="{{ request()->is('account-statement') ? 'active' : '' }}">{{__('db.Account Statement')}}</a></li>
            @endif
            @endif
        </ul>
    </li>
    @endif

    @canany([
    'department',
    'designations',
    'shift',
    'employees-index',
    'attendance',
    'holiday',
    'overtime',
    'leave-type',
    'leave',
    'payroll'
    ])
    <li class="">
        <a href="#hrm" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-user-scan"></i><span>{{__('db.HRM')}}</span></a>

        <ul id="hrm" class="collapse list-unstyled ">
            @can('department')
            <li id="dept-menu"><a class="{{ request()->is('departments') ? 'active' : '' }}" href="{{route('departments.index')}}">{{__('db.Department')}}</a></li>
            @endcan
            @can('designations')
            <li id="designations-menu"><a class="{{ request()->is('designations') ? 'active' : '' }}" href="{{route('designations.index')}}">{{__('db.Designation')}}</a></li>
            @endcan
            @can('shift')
            <li id="shift-menu"><a class="{{ request()->is('shift') ? 'active' : '' }}" href="{{route('shift.index')}}">{{__('db.Shift')}}</a></li>
            @endcan
            @can('employees-index')
            <li id="employee-menu"><a class="{{ request()->is('employees') ? 'active' : '' }}" href="{{route('employees.index')}}">{{__('db.Employee')}}</a></li>
            @endcan
            @can('attendance')
            <li id="attendance-menu"><a class="{{ request()->is('attendance') ? 'active' : '' }}" href="{{route('attendance.index')}}">{{__('db.Attendance')}}</a></li>
            @endcan
            @can('holiday')
            <li id="holiday-menu"><a class="{{ request()->is('holidays') ? 'active' : '' }}" href="{{route('holidays.index')}}">{{__('db.Holiday')}}</a></li>
            @endcan
            @can('overtime')
            <li id="overtime-menu"><a class="{{ request()->is('overtime') ? 'active' : '' }}" href="{{route('overtime.index')}}">{{__('db.Overtime')}}</a></li>
            @endcan
            @can('leave-type')
            <li id="leave-type-menu"><a class="{{ request()->is('leave-type') ? 'active' : '' }}" href="{{route('leave-type.index')}}">{{__('db.Leave Type')}}</a></li>
            @endcan
            @can('leave')
            <li id="leave-menu"><a class="{{ request()->is('leave') ? 'active' : '' }}" href="{{route('leave.index')}}">{{__('db.Leaves')}}</a></li>
            @endcan
            @can('payroll')
            <li id="payroll-menu"><a class="{{ request()->is('payroll') ? 'active' : '' }}" href="{{route('payroll.index')}}">{{__('db.Payroll')}}</a></li>
            @endcan
        </ul>
    </li>
    @endcanany

    @if(optional(\Auth::user())->role_id <= 2)
        <li>
        <a class="{{ request()->is('qr/') ? 'active' : '' }}"
            href="{{ route('qr.index') }}">
            <i class="ti ti-qrcode" style="font-size: 20px"></i><span>{{ __('db.Catalogue QR') }}</span>
        </a>
        </li>
        @endif

        @can('booking')
        <li>
            <a class="{{ request()->is('bookings/calendar') ? 'active' : '' }}"
                href="{{ route('booking.index') }}">
                <i class="ti ti-calendar-clock"></i><span>{{ __('db.Booking') }}</span>
            </a>
        </li>
        @endcan

        @canany(['production-view', 'production-add', 'recipe-view'])
        <li>
            <a href="#manufacturing" aria-expanded="false" data-toggle="collapse">
                <i class="ti ti-building-factory"></i><span>{{ __('db.Manufacturing') }}</span>
            </a>

            <ul id="manufacturing" class="collapse list-unstyled">

                @can('production-view')
                <li id="production-list-menu">
                    <a class="{{ request()->is('manufacturing/productions') ? 'active' : '' }}" href="{{ route('manufacturing.productions.index') }}">
                        {{ __('db.Production List') }}
                    </a>
                </li>
                @endcan

                @can('production-add')
                <li id="production-create-menu">
                    <a class="{{ request()->is('manufacturing/productions/create') ? 'active' : '' }}" href="{{ route('manufacturing.productions.create') }}">
                        {{ __('db.Add Production') }}
                    </a>
                </li>
                @endcan

                @can('recipe-view')
                <li id="recipe-list-menu">
                    <a class="{{ request()->is('manufacturing/recipes') ? 'active' : '' }}" href="{{ route('manufacturing.recipes.index') }}">
                        {{ __('db.Recipe') }}
                    </a>
                </li>
                @endcan

            </ul>
        </li>
        @endcanany

        @canany([
        'repair-dashboard',
        'repair-service-index',
        'repair-service-add',
        'repair-device-type'
        ])
        <li>
            <a href="#repair" aria-expanded="false" data-toggle="collapse">
                <i class="ti ti-tool"></i><span>{{ __('db.Repair') }}</span>
            </a>

            <ul id="repair" class="collapse list-unstyled">

                @can('repair-dashboard')
                <li id="repair-dashboard-menu">
                    <a class="{{ request()->is('repair/') ? 'active' : '' }}"
                        href="{{ route('repair.dashboard') }}">
                        {{ __('db.Repair Dashboard') }}
                    </a>
                </li>
                @endcan

                @can('repair-service-index')
                <li id="service-list-menu">
                    <a class="{{ request()->is('repair/service') ? 'active' : '' }}"
                        href="{{ route('repair.service.index') }}">
                        {{ __('db.Service Jobs') }}
                    </a>
                </li>
                @endcan

                @can('repair-service-add')
                <li id="service-create-menu">
                    <a class="{{ request()->is('repair/service/create') ? 'active' : '' }}"
                        href="{{ route('repair.service.create') }}">
                        {{ __('db.Add Service Job') }}
                    </a>
                </li>
                @endcan

                @can('repair-device-type')
                <li id="device-type-menu">
                    <a class="{{ request()->is('repair/device-types*') ? 'active' : '' }}"
                        href="{{ route('repair.device-types.index') }}">
                        <span>{{ __('db.Device Types') }}</span>
                    </a>
                </li>
                @endcan

            </ul>
        </li>
        @endcanany

        @canany([
        'project_project_list',
        'project_category_list',
        'project_task_list'
        ])
        @include('project::backend.layout.sidebar-menu')
        @endcanany

        @if (in_array('woocommerce',explode(',',gen_setting()->modules)))
        <li><a class="{{ request()->is('woocommerce') ? 'active' : '' }}" href="{{route('woocommerce.index')}}"> <i class="ti ti-brand-wordpress"></i><span>WooCommerce</span></a></li>
        @endif

        @if(in_array('ecommerce',explode(',',gen_setting()->modules)))
        <li>
            <a href="#ecommerce" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-shopping-bag"></i><span>eCommerce</span></a>
            <ul id="ecommerce" class="collapse list-unstyled ">
                @include('ecommerce::backend.layout.sidebar-menu')
            </ul>
        </li>
        @endif

        @if(file_exists(base_path('Modules/ZatcaIntegrationKsa')) && \Illuminate\Support\Facades\Route::has('zatca.ksa.dashboard'))
        @canany(['zatca.dashboard', 'zatca.settings', 'zatca.onboarding', 'zatca.documents', 'zatca.retry'])
        <li>
            <a href="#zatca-ksa" aria-expanded="{{ request()->is('zatca-ksa*') ? 'true' : 'false' }}" data-toggle="collapse">
                <i class="ti ti-file-certificate"></i><span>ZATCA KSA</span>
            </a>
            <ul id="zatca-ksa" class="collapse list-unstyled {{ request()->is('zatca-ksa*') ? 'show' : '' }}">
                @can('zatca.dashboard')
                <li><a class="{{ request()->is('zatca-ksa') ? 'active' : '' }}" href="{{ route('zatca.ksa.dashboard') }}">Dashboard</a></li>
                @endcan
                @can('zatca.settings')
                <li><a class="{{ request()->is('zatca-ksa/settings*') ? 'active' : '' }}" href="{{ route('zatca.ksa.settings') }}">Configuration</a></li>
                @endcan
                @can('zatca.documents')
                <li><a class="{{ request()->is('zatca-ksa/documents*') ? 'active' : '' }}" href="{{ route('zatca.ksa.documents') }}">Document Log</a></li>
                @endcan
            </ul>
        </li>
        @endcanany
        @endif

        @canany(['restaurant-floor', 'restaurant-table', 'restaurant-reservation', 'restaurant-menu-type', 'restaurant-modifier-group', 'restaurant-kitchen', 'restaurant-kitchen-dashboard', 'restaurant-pos'])
        <li>
            <a href="#restaurant" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-chef-hat"></i><span>{{ __('db.restaurant') }}</span></a>
            <ul id="restaurant" class="collapse list-unstyled ">
            @include('restaurant::backend.layout.sidebar-menu')
            </ul>
        </li>
        @endcanany

        @if(in_array('gym', explode(',', gen_setting()->modules)))
        <li>
            <a href="#gym" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-trophy"></i><span>Gym</span></a>
            <ul id="gym" class="collapse list-unstyled ">
                <li id="gym-dashboard-menu"><a class="{{ request()->is('gym/dashboard') ? 'active' : '' }}" href="{{route('gym.dashboard')}}">Dashboard</a></li>
                <li id="gym-member-menu"><a class="{{ request()->is('gym/members*') ? 'active' : '' }}" href="{{route('gym.members.index')}}">Members</a></li>
                <li id="gym-attendance-menu"><a class="{{ request()->is('gym/attendance*') ? 'active' : '' }}" href="{{route('gym.attendance.index')}}">Attendance</a></li>
                <li id="gym-package-menu"><a class="{{ request()->is('gym/packages*') ? 'active' : '' }}" href="{{route('gym.packages.index')}}">Packages</a></li>
                <li id="gym-class-menu"><a class="{{ request()->is('gym/classes*') ? 'active' : '' }}" href="{{route('gym.classes.index')}}">Classes</a></li>
                <li id="gym-setting-menu"><a class="{{ request()->is('gym/setting*') ? 'active' : '' }}" href="{{route('gym.settings.index')}}">Settings</a></li>
            </ul>
        </li>
        @endif

        @if(file_exists(base_path('Modules/Tailoring')))
        @canany([
        'tailoring-dashboard',
        'tailoring-measurements-index',
        'tailoring-orders-index',
        'tailoring-orders-add',
        'tailoring-templates-index',
        'tailoring-materials-manage',
        'tailoring-production-board',
        'tailoring-notifications-manage',
        'tailoring-delivery-manage',
        'tailoring-reports',
        'tailoring-catalog.view'
        ])

        @include('tailoring::layouts.partials.catalog_sidebar')
        
        @endcanany
        @endif

        @can('ai-assistant-index')
            @if(\Illuminate\Support\Facades\Route::has('ai-assistant.index'))
            <li class="{{ request()->is('ai/ai-assistant*') || request()->is('ai-assistant*') ? 'active' : '' }}">
                <a href="{{ route('ai-assistant.index') }}">
                    <i class="ti ti-sparkle-2"></i><span>{{ __('aiassistant::app.ai_assistant') }}</span>
                </a>
            </li>
            @endif
        @endcan

        @can('sidebar_whatsapp')
        <li class="">
            <a href="#whatsapp" aria-expanded="false" data-toggle="collapse">
                <i class="ti ti-brand-whatsapp"></i><span>{{ __('db.whatsapp') }}</span>
            </a>
            <ul id="whatsapp" class="collapse list-unstyled">
                <li id="whatsapp-settings-menu">
                    <a class="{{ request()->is('whatsapp/settings') ? 'active' : '' }}" href="{{ route('whatsapp.settings') }}">
                        {{ __('db.whatsapp_settings') }}
                    </a>
                </li>

                <li id="whatsapp-templates-menu">
                    <a class="{{ request()->is('whatsapp/templates') ? 'active' : '' }}" href="{{ route('whatsapp.templates') }}">
                        {{ __('db.message_templates') }}
                    </a>
                </li>

                <li id="whatsapp-send-menu">
                    <a class="{{ request()->is('whatsapp/send') ? 'active' : '' }}" href="{{ route('whatsapp.send.page') }}">
                        {{ __('db.send_message') }}
                    </a>
                </li>
            </ul>
        </li>
        @endcan

        @canany(['view social commerce'])
        <li>
            <a href="#social-commerce" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-share"></i><span>{{ __('db.Social Commerce') }}</span></a>
            <ul id="social-commerce" class="collapse list-unstyled ">
                <li id="sc-dashboard-menu"><a class="{{ request()->is('socialcommerce/dashboard') || request()->is('socialcommerce') ? 'active' : '' }}" href="{{ route('socialcommerce.dashboard') }}">{{ __('db.Dashboard') }}</a></li>
                @can('manage social catalogue')
                <li id="sc-catalog-menu"><a class="{{ request()->is('socialcommerce/catalog') ? 'active' : '' }}" href="{{ route('socialcommerce.catalog') }}">{{ __('db.Catalog') }}</a></li>
                @endcan
                @can('manage social commerce settings')
                <li id="sc-feed-menu"><a class="{{ request()->is('socialcommerce/feed') ? 'active' : '' }}" href="{{ route('socialcommerce.feed') }}">{{ __('db.Meta Feed') }}</a></li>
                @endcan
                @can('manage social commerce settings')
                <li id="sc-settings-menu"><a class="{{ request()->is('socialcommerce/settings') ? 'active' : '' }}" href="{{ route('socialcommerce.settings') }}">{{ __('db.Settings') }}</a></li>
                @endcan
            </ul>
        </li>
        @endcanany

        @canany([
        'profit-loss',
        'best-seller',
        'product-report',
        'stock-report',
        'daily-sale',
        'monthly-sale',
        'daily-purchase',
        'daily-purchase',
        'monthly-purchase',
        'sale-report',
        'sale-report-chart',
        'payment-report',
        'purchase-report',
        'customer-report',
        'due-report',
        'supplier-report',
        'supplier-due-report',
        'warehouse-report',
        'warehouse-stock-report',
        'product-expiry-report',
        'product-qty-alert',
        'dso-report',
        'user-report',
        'biller-report',
        'tax-report'
        ])
        <li>
            <a href="#report" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-report"></i><span>{{__('db.Reports')}}</span></a>

            <ul id="report" class="collapse list-unstyled ">
                @if(optional(\Auth::user())->role_id <= 2)
                    <li id="activity-log-menu"><a class="{{ request()->is('setting/activity-log') ? 'active' : '' }}" href="{{route('setting.activityLog')}}">{{__('db.Activity Log')}}</a>
                </li>
                @endif
                @can('tax-report')
                <li id="tax-report-menu">
                    <a class="{{ request()->is('report/tax-report*') ? 'active' : '' }}" href="{{ route('report.tax.index') }}">{{ __('db.Tax Report') }}</a>
                </li>
                @endcan
                @can('profit-loss')
                <li id="profit-loss-report-menu">
                    <a class="{{ request()->is('report/profit-loss') ? 'active' : '' }}" href="{{url('report/profit-loss')}}">{{__('db.profit_loss_report')}}</a>
                </li>
                <li id="profitability-product-report-menu">
                    <a class="{{ request()->is('report/profitability/product') ? 'active' : '' }}" href="{{route('report.profitability.product')}}">{{__('db.profit_by_product')}}</a>
                </li>
                <li id="profitability-category-report-menu">
                    <a class="{{ request()->is('report/profitability/category') ? 'active' : '' }}" href="{{route('report.profitability.category')}}">{{__('db.profit_by_category')}}</a>
                </li>
                <li id="profitability-brand-report-menu">
                    <a class="{{ request()->is('report/profitability/brand') ? 'active' : '' }}" href="{{route('report.profitability.brand')}}">{{__('db.profit_by_brand')}}</a>
                </li>
                <li id="profitability-customer-report-menu">
                    <a class="{{ request()->is('report/profitability/customer') ? 'active' : '' }}" href="{{route('report.profitability.customer')}}">{{__('db.profit_by_customer')}}</a>
                </li>
                <li id="profitability-location-report-menu">
                    <a class="{{ request()->is('report/profitability/location') ? 'active' : '' }}" href="{{route('report.profitability.location')}}">{{__('db.profit_by_location')}}</a>
                </li>
                <li id="profitability-invoice-report-menu">
                    <a class="{{ request()->is('report/profitability/invoice') ? 'active' : '' }}" href="{{route('report.profitability.invoice')}}">{{__('db.profit_by_invoice')}}</a>
                </li>
                @endcan
                @can('best-seller')
                <li id="best-seller-report-menu">
                    <a class="{{ request()->is('report/best_seller') ? 'active' : '' }}" href="{{url('report/best_seller')}}">{{__('db.Best Seller')}}</a>
                </li>
                @endcan
                @can('product-report')
                <li id="product-report-menu">
                    <a class="{{ request()->is('report/product_report') ? 'active' : '' }}" href="{{url('report/product_report')}}">{{__('db.Product Report')}}</a>
                </li>
                @endcan
                @can('stock-report')
                <li>
                    <a class="{{ request()->is('report/stock') ? 'active' : '' }}" href="{{ route('report.stock') }}">
                        {{ __('db.Stock Report') }}
                    </a>
                </li>
                @endcan
                @can('daily-sale')
                <li id="daily-sale-report-menu">
                    <a class="{{ request()->is('report/daily_sale/'.date('Y').'/'.date('m')) ? 'active' : '' }}" href="{{url('report/daily_sale/'.date('Y').'/'.date('m'))}}">{{__('db.Daily Sale')}}</a>
                </li>
                @endcan
                @can('monthly-sale')
                <li id="monthly-sale-report-menu">
                    <a class="{{ request()->is('report/monthly_sale/'.date('Y')) ? 'active' : '' }}" href="{{url('report/monthly_sale/'.date('Y'))}}">{{__('db.Monthly Sale')}}</a>
                </li>
                @endcan
                @can('daily-purchase')
                <li id="daily-purchase-report-menu">
                    <a class="{{ request()->is('report/daily_purchase/'.date('Y').'/'.date('m')) ? 'active' : '' }}" href="{{url('report/daily_purchase/'.date('Y').'/'.date('m'))}}">{{__('db.Daily Purchase')}}</a>
                </li>
                @endcan
                @can('monthly-purchase')
                <li id="monthly-purchase-report-menu">
                    <a class="{{ request()->is('report/monthly_purchase/'.date('Y')) ? 'active' : '' }}" href="{{url('report/monthly_purchase/'.date('Y'))}}">{{__('db.Monthly Purchase')}}</a>
                </li>
                @endcan
                @can('sale-report')
                <li id="sale-report-menu">
                    <form action="{{route('report.sale')}}" method="post" id="sale-report-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <input type="hidden" name="warehouse_id" value="0" />
                        <a id="sale-report-link" class="{{ request()->is('report/sale_report') ? 'active' : '' }}" href="">{{__('db.Sale Report')}}</a>
                    </form>
                </li>
                @endcan
                <li id="challan-report-menu">
                    <a class="{{ request()->is('report/challan-report') ? 'active' : '' }}" href="{{route('report.challan')}}"> {{__('db.Challan Report')}}</a>
                </li>
                @can('sale-report-chart')
                <li id="sale-report-chart-menu">
                    <form action="{{route('report.saleChart')}}" method="post" id="sale-report-chart-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <input type="hidden" name="warehouse_id" value="0" />
                        <input type="hidden" name="time_period" value="weekly" />
                        <a id="sale-report-chart-link" class="{{ request()->is('report/sale-report-chart') ? 'active' : '' }}" href="">{{__('db.Sale Report Chart')}}</a>
                    </form>
                </li>
                @endcan
                @can('payment-report')
                <li id="payment-report-menu">
                    <form action="{{route('report.paymentByDate')}}" method="post" id="payment-report-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <a id="payment-report-link" class="{{ request()->is('report/payment_report_by_date') ? 'active' : '' }}" href="">{{__('db.Payment Report')}}</a>
                    </form>
                </li>
                @endcan
                @can('purchase-report')
                <li id="purchase-report-menu">
                    <form action="{{route('report.purchase')}}" method="post" id="purchase-report-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m').'-'.'01'}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <input type="hidden" name="warehouse_id" value="0" />
                        <a id="purchase-report-link" class="{{ request()->is('report/purchase') ? 'active' : '' }}" href="">{{__('db.Purchase Report')}}</a>
                    </form>
                </li>
                @endcan
                @can('customer-report')
                <li id="customer-report-menu">
                    <a class="{{ request()->is('report/customer_report') ? 'active' : '' }}" id="customer-report-link" href="">{{__('db.Customer Report')}}</a>
                </li>
                @endcan
                @can('customer-report')
                <li id="customer-report-menu">
                    <a id="customer-group-report-link" class="{{ request()->is('report/customer-group') ? 'active' : '' }}" href="">{{__('db.Customer Group Report')}}</a>
                </li>
                @endcan
                @can('due-report')
                <li id="due-report-menu">
                    <form action="{{route('report.customerDueByDate')}}" method="post" id="customer-due-report-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m-d', strtotime('-1 year'))}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <a id="due-report-link" class="{{ request()->is('report/customer-due-report') ? 'active' : '' }}" href="">{{__('db.Customer Due Report')}}</a>
                    </form>
                </li>
                @endcan
                @can('supplier-report')
                <li id="supplier-report-menu">
                    <a id="supplier-report-link" class="{{ request()->is('report/supplier') ? 'active' : '' }}" href="">{{__('db.Supplier Report')}}</a>
                </li>
                @endcan
                @can('supplier-due-report')
                <li id="supplier-due-report-menu">
                    <form action="{{route('report.supplierDueByDate')}}" method="post" id="supplier-due-report-form">
                        @csrf
                        <input type="hidden" name="start_date" value="{{date('Y-m-d', strtotime('-1 year'))}}" />
                        <input type="hidden" name="end_date" value="{{date('Y-m-d')}}" />
                        <a id="supplier-due-report-link" class="{{ request()->is('report/supplier-due-report') ? 'active' : '' }}" href="">{{__('db.Supplier Due Report')}}</a>
                    </form>
                </li>
                @endcan
                @can('warehouse-report')
                <li id="warehouse-report-menu">
                    <a id="warehouse-report-link" class="{{ request()->is('report/warehouse_report') ? 'active' : '' }}">{{__('db.Warehouse Report')}}</a>
                </li>
                @endcan
                @can('warehouse-stock-report')
                <li id="warehouse-stock-report-menu">
                    <a class="{{ request()->is('report/warehouse_stock') ? 'active' : '' }}" href="{{route('report.warehouseStock')}}">{{__('db.Warehouse Stock Chart')}}</a>
                </li>
                @endcan
                @can('product-expiry-report')
                <li id="productExpiry-report-menu">
                    <a class="{{ request()->is('report/product-expiry') ? 'active' : '' }}" href="{{route('report.productExpiry')}}">{{__('db.Product Expiry Report')}}</a>
                </li>
                @endcan
                @can('product-qty-alert')
                <li id="qtyAlert-report-menu">
                    <a class="{{ request()->is('report/product_quantity_alert') ? 'active' : '' }}" href="{{route('report.qtyAlert')}}">{{__('db.Product Quantity Alert')}}</a>
                </li>
                @endcan
                @can('dso-report')
                <li id="daily-sale-objective-menu">
                    <a class="{{ request()->is('report/daily-sale-objective') ? 'active' : '' }}" href="{{route('report.dailySaleObjective')}}">{{__('db.Daily Sale Objective Report')}}</a>
                </li>
                @endcan
                @can('user-report')
                <li id="user-report-menu">
                    <a id="user-report-link" class="{{ request()->is('report/user_report') ? 'active' : '' }}" href="">{{__('db.User Report')}}</a>
                </li>
                @endcan
                @can('biller-report')
                <li id="biller-report-menu">
                    <a id="biller-report-link" class="{{ request()->is('report/biller_report') ? 'active' : '' }}">{{__('db.Biller Report')}}</a>
                </li>
                @endcan
                <li id="installment-report-menu"><a class="{{ request()->is('report/installment') ? 'active' : '' }}" href="{{route('report.installment')}}">{{__('db.Instalment Report')}}</a></li>
                <li id="cash-register-report-menu">
                    <a class="{{ request()->is('cash-register') ? 'active' : '' }}" href="{{route('cashRegister.index')}}">{{__('db.Cash Register')}}</a>
                </li>
            </ul>
        </li>
        @endcanany

@canany([
'invoice_setting',
'role_permission',
'custom_field',
'discount_plan',
'discount',
'all_notification',
'send_notification',
'warehouse',
'customer_group',
'currency',
'tax',
'create_sms',
'backup_database',
'general_setting',
'mail_setting',
'reward_point_setting',
'sms_setting',
'payment_gateway_setting',
'pos_setting',
'hrm_setting',
'barcode_setting',
'language_setting'
])
<li>
    <a href="#setting" aria-expanded="false" data-toggle="collapse"> <i class="ti ti-settings"></i><span>{{__('db.settings')}}</span></a>

    <ul id="setting" class="collapse list-unstyled ">

        @can('role_permission')
        <li id="role-menu"><a class="{{ request()->is('role') ? 'active' : '' }}" href="{{route('role.index')}}">{{__('db.Role Permission')}}</a></li>
        @endcan

        @can('general_setting')
        <li id="general-setting-menu"><a class="{{ request()->is('setting/general_setting') ? 'active' : '' }}" href="{{route('setting.general')}}">{{__('db.General Setting')}}</a></li>
        @if (in_array('api',explode(',',gen_setting()->modules)))
        <li id="theme-settings-menu"><a class="{{ request()->is('setting/theme-settings') ? 'active' : '' }}" href="{{ route('setting.themeSettings.index') }}">{{__('db.app_theme_settings')}}</a></li>
        @endif
        @endcan

        @can('language_setting')
        <li id="languages"><a class="{{ request()->is('languages') ? 'active' : '' }}" href="{{route('languages')}}"> {{__('db.Languages')}}</a></li>
        @endcan

        @can('warehouse')
        <li id="warehouse-menu"><a class="{{ request()->is('warehouse') ? 'active' : '' }}" href="{{route('warehouse.index')}}">{{__('db.Warehouse')}}</a></li>
        @endcan

        @if(optional(\Auth::user())->role_id <= 2)
            <li id="table-menu"><a class="{{ request()->is('tables') ? 'active' : '' }}" href="{{route('tables.index')}}">{{__('db.Tables')}}</a>
</li>
@endif

@can('discount_plan')
<li id="discount-plan-list-menu"><a class="{{ request()->is('discount-plans') ? 'active' : '' }}" href="{{route('discount-plans.index')}}">{{__('db.Discount Plan')}}</a></li>
@endcan

@can('discount')
<li id="discount-list-menu"><a class="{{ request()->is('discounts') ? 'active' : '' }}" href="{{route('discounts.index')}}">{{__('db.Discount')}}</a></li>
@endcan

@can('customer_group')
<li id="customer-group-menu"><a class="{{ request()->is('customer_group') ? 'active' : '' }}" href="{{route('customer_group.index')}}">{{__('db.Customer Group')}}</a></li>
@endcan

@can('reward_point_setting')
<li id="reward-point-setting-menu"><a class="{{ request()->is('setting/reward-point-setting') ? 'active' : '' }}" href="{{route('setting.rewardPoint')}}">{{__('db.Reward Point Setting')}}</a></li>
@endcan

@can('currency')
<li id="currency-menu"><a class="{{ request()->is('currency') ? 'active' : '' }}" href="{{route('currency.index')}}">{{__('db.Currency')}}</a></li>
@endcan

@can('tax')
<li id="tax-menu"><a class="{{ request()->is('tax') ? 'active' : '' }}" href="{{route('tax.index')}}">{{__('db.Tax')}}</a></li>
@endcan

<li id="user-menu"><a class="{{ request()->is('user/profile/*') ? 'active' : '' }}" href="{{route('user.profile', ['id' => Auth::id()])}}">{{__('db.User Profile')}}</a></li>

@can('mail_setting')
<li id="mail-setting-menu"><a class="{{ request()->is('setting/mail_setting') ? 'active' : '' }}" href="{{route('setting.mail')}}">{{__('db.Mail Setting')}}</a></li>
@endcan

@can('sms_setting')
<li id="sms-setting-menu"><a class="{{ request()->is('setting/sms_setting') ? 'active' : '' }}" href="{{route('setting.sms')}}">{{__('db.SMS Setting')}}</a></li>
@endcan

@can('create_sms')
<li id="create-sms-menu"><a class="{{ request()->is('setting/createsms') ? 'active' : '' }}" href="{{route('setting.createSms')}}">{{__('db.Create SMS')}}</a></li>
<li><a class="{{ request()->is('smstemplates') ? 'active' : '' }}" href="{{route('smstemplates.index')}}">{{__('db.SMS Template')}}</a></li>
@endcan

@can('pos_setting')
<li id="pos-setting-menu"><a class="{{ request()->is('setting/pos_setting') ? 'active' : '' }}" href="{{route('setting.pos')}}">POS {{__('db.settings')}}</a></li>
@endcan

@can('payment_gateway_setting')
<li id="payment-gateway-setting-menu"><a class="{{ request()->is('setting/payment-gateways/list') ? 'active' : '' }}" href="{{route('setting.gateway')}}">{{__('db.Payment Gateways')}}</a></li>
@endcan

@if(optional(\Auth::user())->role_id <= 2)
    <li id="printer-menu"><a class="{{ request()->is('printers') ? 'active' : '' }}" href="{{route('printers.index')}}">{{__('db.Receipt Printers')}}</a></li>
    @endif

    @can ('invoice_setting')
    <li id="invoice-menu"><a class="{{ request()->is('setting/invoice') ? 'active' : '' }}" href="{{route('settings.invoice.index')}}">{{__('db.Invoice Settings')}}</a></li>
    @endcan

    @can('barcode_setting')
    <li id="barcode-setting-menu"><a class="{{ request()->is('barcodes') ? 'active' : '' }}" href="{{route('barcodes.index')}}"> {{__('db.Barcode Settings')}}</a></li>
    @endcan

    @can('hrm_setting')
    <li id="hrm-setting-menu"><a class="{{ request()->is('setting/hrm_setting') ? 'active' : '' }}" href="{{route('setting.hrm')}}"> {{__('db.HRM Setting')}}</a></li>
    @endcan

    @can('all_notification')
    <li id="notification-list-menu">
        <a class="{{ request()->is('notifications') ? 'active' : '' }}" href="{{route('notifications.index')}}">{{__('db.All Notification')}}</a>
    </li>
    @endcan

    @can('send_notification')
    <li id="notification-menu">
        <a href="" id="send-notification">{{__('db.Send Notification')}}</a>
    </li>
    @endcan

    <li id="notification-setting">
        <a href="{{ route('notifications.settings') }}">
            <span>Notification Settings</span>
        </a>
    </li>

    @can('custom_field')
    <li id="custom-field-list-menu"><a class="{{ request()->is('custom-fields') ? 'active' : '' }}" href="{{route('custom-fields.index')}}">{{__('db.Custom Field List')}}</a></li>
    @endcan

    @can('backup_database')
    <li><a class="{{ request()->is('backup') ? 'active' : '' }}" href="{{route('setting.backup')}}">{{__('db.Backup Database')}}</a></li>
    @endcan
    </ul>
    </li>
    @endcanany

    @if(config('database.connections.saleprosaas_landlord'))
    @php
    tenancy()->central(function () use (&$disable_tenant_support_tickets) {
    $disable_tenant_support_tickets = DB::table('general_settings')->latest()
    ->first()->disable_tenant_support_tickets;
    });
    @endphp
    @if(!$disable_tenant_support_tickets)
    <li><a class="{{ request()->is('tickets') ? 'active' : '' }}" href="{{route('tickets.index')}}"><i class="ti ti-ticket"></i> {{__('db.support_tickets')}}</a></li>
    @endif
    @endif

    @can ('addons')
    @if(optional(\Auth::user())->role_id != 5)
    @if (in_array('api',explode(',',gen_setting()->modules)))
    <li><a class="{{ request()->is('setting/app_setting') ? 'active' : '' }}" href="{{route('setting.app')}}"> <i class="ti ti-device-mobile"></i><span>{{__('db.App Setting')}}</span></a></li>
    @endif
    @endif
    @endcan

    @can ('addons')
    @if(optional(\Auth::user())->role_id != 5)

    @if(!config('database.connections.saleprosaas_landlord'))
    <li><a class="{{ request()->is('addon-list') ? 'active' : '' }}" href="{{url('addon-list')}}" id="addon-list"> <i class="ti ti-flag"></i><span>{{__('db.Addons')}}</span></a></li>
    @endif
    @endif
    @endcan
    </ul>
