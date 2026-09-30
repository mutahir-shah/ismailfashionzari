<!DOCTYPE html>
<html dir="@if (Config::get('app.locale') == 'ar' || gen_setting()->is_rtl) {{ 'rtl' }} @endif">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @php
        $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
    @endphp
    <link rel="icon" type="image/png"
        href="{{ url('logo', gen_setting()->favicon ?? gen_setting()->site_logo) }}" />
    <title>{{ gen_setting()->site_title }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap.min.css') }}" type="text/css">

    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    </noscript>

    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-select.min.css') }}" rel="stylesheet">
    </noscript>
    <!-- Icons CSS-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    @if (optional(Route::current())->getName() != '/')
        <link rel="preload" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/awesome-bootstrap-checkbox.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link href="{{ asset($asset_prefix . 'vendor/bootstrap/css/awesome-bootstrap-checkbox.css') }}" rel="stylesheet">
        </noscript>
        <!-- date range stylesheet-->
        <link rel="preload" href="{{ asset($asset_prefix . 'vendor/daterange/css/daterangepicker.min.css') }}" as="style"
            onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link href="{{ asset($asset_prefix . 'vendor/daterange/css/daterangepicker.min.css') }}" rel="stylesheet">
        </noscript>
    @endif

    <link rel="stylesheet" href="{{ asset($asset_prefix . 'css/style.default.css') }}" id="theme-stylesheet" type="text/css">
    


    @if (Config::get('app.locale') == 'ar' || gen_setting()->is_rtl)
        <!-- RTL css -->
        <link rel="stylesheet" href="{{ asset($asset_prefix . 'vendor/bootstrap/css/bootstrap-rtl.min.css') }}" type="text/css">
        <link rel="stylesheet" href="{{ asset($asset_prefix . 'css/custom-rtl.css') }}" type="text/css" id="custom-style">
    @endif

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>
    <!-- Google fonts -->
    @if (gen_setting()->font_css)
        {!! gen_setting()->font_css !!}
    @else
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap"
            rel="stylesheet">
    @endif

    @stack('css')

    <!-- Custom CSS from general settings -->
    {!! gen_setting()->custom_css !!}
</head>

<body class="@if (($theme ?? 'default') == 'dark') dark-mode @endif  @if (optional(Route::current())->getName() == 'sale.pos') pos-page @endif">
    <!-- Side Navbar -->
    <nav class="side-navbar shrink d-print-none">
        <span class="brand-big">
            <a href="{{ url('/dashboard') }}">
                @if (gen_setting()->site_logo)
                    <img src="{{ url('logo', gen_setting()->site_logo) }}" width="115">
                @else
                    <h1 class="d-inline">{{ gen_setting()->site_title }}</h1>
                @endif
            </a>
        </span>
        @include('backend.layout.sidebar')
    </nav>

    <div class="page">
        <!-- navbar-->
        @if (optional(Route::current())->getName() != 'sale.pos')
            <header class="container-fluid">
                <nav class="navbar">
                    <a id="toggle-btn" class="menu-btn"><i class="ti ti-menu-deep"> </i></a>

                    <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                        @php
                            $role_has_permissions_list = $role_has_permissions_list ?? collect();
                        @endphp
                        <div class="dropdown d-none d-lg-block">

                            <a class="btn-pos btn-sm" type="button" data-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-plus"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php
                                $category_permission_active = $role_has_permissions_list->where('name', 'category')->first();
                                ?>
                                @if ($category_permission_active)
                                    <li class="dropdown-item"><a data-toggle="modal"
                                            data-target="#category-modal">{{ __('db.Add Category') }}</a></li>
                                @endif
                                <?php
                                $add_permission_active = $role_has_permissions_list->where('name', 'products-add')->first();
                                ?>
                                @if ($add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('products.create') }}">{{ __('db.add_product') }}</a></li>
                                @endif
                                <?php
                                $add_permission_active = $role_has_permissions_list->where('name', 'purchases-add')->first();
                                ?>
                                @if ($add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('purchases.create') }}">{{ __('db.Add Purchase') }}</a>
                                    </li>
                                @endif
                                <?php
                                $sale_add_permission_active = $role_has_permissions_list->where('name', 'sales-add')->first();
                                ?>
                                @if ($sale_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('sales.create') }}">{{ __('db.Add Sale') }}</a></li>
                                @endif
                                <?php
                                $expense_add_permission_active = $role_has_permissions_list->where('name', 'expenses-add')->first();
                                ?>
                                @if ($expense_add_permission_active)
                                    <li class="dropdown-item"><a data-toggle="modal" data-target="#expense-modal">
                                            {{ __('db.Add Expense') }}</a></li>
                                @endif
                                <?php
                                $quotation_add_permission_active = $role_has_permissions_list->where('name', 'quotes-add')->first();
                                ?>
                                @if ($quotation_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('quotations.create') }}">{{ __('db.Add Quotation') }}</a>
                                    </li>
                                @endif
                                <?php
                                $transfer_add_permission_active = $role_has_permissions_list->where('name', 'transfers-add')->first();
                                ?>
                                @if ($transfer_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('transfers.create') }}">{{ __('db.Add Transfer') }}</a>
                                    </li>
                                @endif
                                <?php
                                $return_add_permission_active = $role_has_permissions_list->where('name', 'returns-add')->first();
                                ?>
                                @if ($return_add_permission_active)
                                    <li class="dropdown-item"><a href="#" data-toggle="modal"
                                            data-target="#add-sale-return"> {{ __('db.Add Return') }}</a></li>
                                @endif
                                <?php
                                $purchase_return_add_permission_active = $role_has_permissions_list->where('name', 'purchase-return-add')->first();
                                ?>
                                @if ($purchase_return_add_permission_active)
                                    <li class="dropdown-item"><a href="#" data-toggle="modal"
                                            data-target="#add-purchase-return"> {{ __('db.Add Purchase Return') }}</a>
                                    </li>
                                @endif
                                <?php
                                $user_add_permission_active = $role_has_permissions_list->where('name', 'users-add')->first();
                                ?>
                                @if ($user_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('user.create') }}">{{ __('db.Add User') }}</a></li>
                                @endif
                                <?php
                                $customer_add_permission_active = $role_has_permissions_list->where('name', 'customers-add')->first();
                                ?>
                                @if ($customer_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('customer.create') }}">{{ __('db.Add Customer') }}</a>
                                    </li>
                                @endif
                                <?php
                                $biller_add_permission_active = $role_has_permissions_list->where('name', 'billers-add')->first();
                                ?>
                                @if ($biller_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('biller.create') }}">{{ __('db.Add Biller') }}</a></li>
                                @endif
                                <?php
                                $supplier_add_permission_active = $role_has_permissions_list->where('name', 'suppliers-add')->first();
                                ?>
                                @if ($supplier_add_permission_active)
                                    <li class="dropdown-item"><a
                                            href="{{ route('supplier.create') }}">{{ __('db.Add Supplier') }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <?php
                        $business_reset_permission_active = $role_has_permissions_list->where('name', 'business_reset')->first();

                        $sale_add_permission_active = $role_has_permissions_list->where('name', 'sales-add')->first();

                        $product_qty_alert_active = $role_has_permissions_list->where('name', 'product-qty-alert')->first();

                        $general_setting_permission_active = $role_has_permissions_list->where('name', 'general_setting')->first();

                        $language_setting_active = $role_has_permissions_list->where('name', 'language_setting')->first();

                        ?>
                        @if ($sale_add_permission_active)
                            <li class="nav-item"><a class="btn-pos btn-sm" href="{{ route('sale.pos') }}"><i
                                        class="ti ti-shopping-bag"></i><span> POS</span></a></li>
                        @endif

                        @if (config('database.connections.saleprosaas_landlord'))
                            <li class="nav-item"><a target="_blank"
                                    href="{{ 'https://' . env('CENTRAL_DOMAIN') . '/contact-for-renewal?id=' . $subdomain }}"
                                    data-toggle="tooltip" title="{{ __('Renew Subscription') }}"><i
                                        class="ti ti-clockwise"></i></a></li>
                        @endif
                        <li class="nav-item d-none d-lg-block"><a id="btnFullscreen" data-toggle="tooltip"
                                title="{{ __('Full Screen') }}"><i class="ti ti-arrows-maximize"></i></a></li>
                        @if (optional(\Auth::user())->role_id <= 2)
                            <li class="nav-item d-none d-lg-block"><a href="{{ route('cashRegister.index') }}" data-toggle="tooltip"
                                    title="{{ __('Cash Register List') }}"><i class="ti ti-archive"></i></a></li>
                        @endif

                        @php
                            $alertProductCount = (int) ($alert_product ?? 0);
                            $dsoAlertProductCount = (int) ($dso_alert_product_no ?? 0);
                            $expireAlertProductCount = (int) ($expire_alert_products ?? 0);
                            $pendingEcommerceOrdersCount = (int) ($pending_ecommerce_orders ?? 0);

                            $userUnreadCount = Auth::user()
                                ? Auth::user()
                                    ->unreadNotifications()
                                    ->whereDate('data->reminder_date', now()->toDateString())
                                    ->count()
                                : 0;

                            $total_notifications =
                                $alertProductCount +
                                $dsoAlertProductCount +
                                $expireAlertProductCount +
                                $pendingEcommerceOrdersCount +
                                $userUnreadCount;
                        @endphp

                        <li class="nav-item" id="notification-icon">

                            <a rel="nofollow" data-toggle="tooltip" title="{{ __('Notifications') }}"
                                class="nav-link dropdown-item">
                                <i class="ti ti-bell"></i>

                                @if ($total_notifications > 0)
                                    <span class="badge badge-danger notification-number">{{ $total_notifications }}</span>
                                @endif
                            </a>

                            <style>
                                .custom-notifications-list {
                                    width: 550px;
                                    max-width: 95vw;
                                    padding: 0;
                                    border-radius: 12px;
                                    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
                                    border: 1px solid #f0f0f0;
                                    background: #fff;
                                    right: 0 !important;
                                    left: auto !important;
                                }
                                .custom-notifications-list .dropdown-header {
                                    font-weight: 700;
                                    font-size: 15px;
                                    padding: 16px 20px;
                                    border-bottom: 1px solid #f0f0f0;
                                    color: #2c3e50;
                                    background: #ffffff;
                                    border-radius: 12px 12px 0 0;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                }
                                .custom-notifications-list .dropdown-header .badge {
                                    font-size: 11px;
                                    padding: 5px 8px;
                                    border-radius: 20px;
                                }
                                .custom-notifications-list .notifications {
                                    border-bottom: 1px solid #f9f9f9;
                                    padding: 0;
                                    margin: 0;
                                    list-style: none;
                                }
                                .custom-notifications-list .notifications:last-child {
                                    border-bottom: none;
                                }
                                .custom-notifications-list .notifications a {
                                    display: flex;
                                    align-items: flex-start;
                                    padding: 16px 20px;
                                    text-decoration: none;
                                    color: #555;
                                    transition: all 0.2s ease;
                                }
                                .custom-notifications-list .notifications a:hover {
                                    background: #f8faff;
                                }
                                .notify-icon-box {
                                    width: 42px;
                                    height: 42px;
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    flex-shrink: 0;
                                    margin-right: 15px;
                                }
                                .notify-icon-box i {
                                    font-size: 20px;
                                }
                                .notify-text {
                                    flex-grow: 1;
                                }
                                .notify-text h6 {
                                    margin: 0 0 4px 0;
                                    font-size: 14px;
                                    font-weight: 600;
                                    color: #2c3e50;
                                }
                                .notify-text span {
                                    font-size: 13px;
                                    color: #7f8c8d;
                                    line-height: 1.4;
                                    display: block;
                                }
                                /* Variations */
                                .bg-qty { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
                                .bg-dso { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
                                .bg-expire { background: rgba(211, 84, 0, 0.1); color: #d35400; }
                                .bg-order { background: rgba(52, 152, 219, 0.1); color: #3498db; }
                                .bg-reminder { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
                            </style>

                            <ul class="right-sidebar custom-notifications-list">
                                <li class="dropdown-header">
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <div>
                                            <span>{{ __('Notifications') }}</span>
                                            @if($total_notifications > 0)
                                                <span class="badge badge-danger ml-2">{{ $total_notifications }} New</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-link text-muted p-0" onclick="$(this).closest('.nav-item').removeClass('show'); $(this).closest('.right-sidebar').removeClass('show');" style="font-size: 16px; text-decoration: none;">
                                            <i class="ti ti-close"></i>
                                        </button>
                                    </div>
                                </li>

                                {{-- No notifications --}}
                                @if ($total_notifications == 0)
                                    <li class="notifications text-center">
                                        <span class="text-muted d-block py-4">
                                            {{ __('db.no_notifications_available') }}
                                        </span>
                                    </li>
                                @else
                                    {{-- Quantity Alert --}}
                                    @if ($alertProductCount > 0)
                                        <li class="notifications">
                                            <a href="{{ route('report.qtyAlert') }}">
                                                <div class="notify-icon-box bg-qty">
                                                    <i class="ti ti-alert"></i>
                                                </div>

                                                <div class="notify-text">
                                                    <h6>{{ __('db.quantity_alert') }}</h6>
                                                    <span>
                                                        <strong>{{ $alertProductCount }}</strong>
                                                        {{ __('db.product_exceeds_alert_quantity') }}
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Daily Sale Objective Alert --}}
                                    @if ($dsoAlertProductCount > 0)
                                        <li class="notifications">
                                            <a href="{{ route('report.dailySaleObjective') }}">
                                                <div class="notify-icon-box bg-dso">
                                                    <i class="ti ti-target"></i>
                                                </div>

                                                <div class="notify-text">
                                                    <h6>{{ __('db.daily_sale_objective') }}</h6>
                                                    <span>
                                                        {{ __('db.products_below_objective', [
                                                            'count' => $dsoAlertProductCount,
                                                        ]) }}
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Product Expiry Alert --}}
                                    @if ($expireAlertProductCount > 0)
                                        <li class="notifications">
                                            <a href="{{ route('report.productExpiry') }}">
                                                <div class="notify-icon-box bg-expire">
                                                    <i class="ti ti-time"></i>
                                                </div>

                                                <div class="notify-text">
                                                    <h6>{{ __('db.product_expiry') }}</h6>
                                                    <span>
                                                        {{ __('db.products_expiring_within_days', [
                                                            'count' => $expireAlertProductCount,
                                                            'days' => $general_setting->expiry_alert_days ?? 30,
                                                        ]) }}
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pending Ecommerce Orders --}}
                                    @if ($pendingEcommerceOrdersCount > 0)
                                        <li class="notifications">
                                            <a href="{{ route('sales.index') }}">
                                                <div class="notify-icon-box bg-order">
                                                    <i class="ti ti-shopping-cart"></i>
                                                </div>

                                                <div class="notify-text">
                                                    <h6>{{ __('db.new_online_orders') }}</h6>
                                                    <span>
                                                        {{ __('db.pending_ecommerce_orders_count', [
                                                            'count' => $pendingEcommerceOrdersCount,
                                                        ]) }}
                                                    </span>
                                                </div>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Reminder Notifications --}}
                                    @foreach (
                                        optional(Auth::user())->unreadNotifications
                                            ?->where('data.reminder_date', date('Y-m-d')) ?? []
                                        as $notification
                                    )
                                        @php
                                            $documentName = $notification->data['document_name'] ?? null;
                                            $message = $notification->data['message'] ?? '';
                                        @endphp

                                        <li class="notifications">
                                            <a
                                                href="{{ $documentName
                                                    ? url('documents/notification', $documentName)
                                                    : '#' }}"
                                                @if ($documentName) target="_blank" rel="noopener noreferrer" @endif
                                            >
                                                <div class="notify-icon-box bg-reminder">
                                                    <i class="ti ti-bell"></i>
                                                </div>

                                                <div class="notify-text">
                                                    <h6>{{ __('db.reminder') }}</h6>
                                                    <span>{{ $message }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a rel="nofollow" title="{{ __('db.language') }}" data-toggle="tooltip"
                                class="nav-link dropdown-item"><i class="ti ti-world"></i></a>
                            <ul class="right-sidebar">
                                @foreach ($languages ?? [] as $language)
                                    <li>
                                        <a href="{{ url('language_switch/' . $language->id) }}"
                                            class="btn btn-link">{{ $language->name }}</a>
                                    </li>
                                @endforeach

                                @if (!config('app.user_verified'))
                                    @if ($language_setting_active)
                                        <li id="languages"
                                            style="background-color: #f0f0f0; padding: 10px; border-radius: 5px;">
                                            <a href="{{ route('languages') }}"> {{ __('db.Languages') }} <span
                                                    style="font-size: 16px;">→</span></i></a>
                                        </li>
                                    @endif
                                @endif
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a rel="nofollow" data-toggle="tooltip" class="nav-link dropdown-item"><i
                                    class="ti ti-user"></i> <span>{{ ucfirst(optional(Auth::user())->name) }}</span> <i
                                    class="ti ti-angle-down"></i>
                            </a>
                            <ul class="right-sidebar">
                                <li>
                                    <a href="{{ route('user.profile', ['id' => Auth::id() ?: 1]) }}"><i
                                            class="ti ti-user"></i> {{ __('db.profile') }}</a>
                                </li>
                                @if ($general_setting_permission_active)
                                    <li>
                                        <a href="{{ route('setting.general') }}"><i class="ti ti-settings"></i>
                                            {{ __('db.settings') }}</a>
                                    </li>
                                @endif
                                <li>
                                    <a href="{{ url('my-transactions/' . date('Y') . '/' . date('m')) }}"><i
                                            class="ti ti-calendar-dollar"></i> {{ __('db.My Transaction') }}</a>
                                </li>
                                @if (optional(Auth::user())->role_id != 5)
                                    <li>
                                        <a href="{{ url('holidays/my-holiday/' . date('Y') . '/' . date('m')) }}"><i
                                                class="ti ti-luggage"></i> {{ __('db.My Holiday') }}</a>
                                    </li>
                                @endif
                                @if ($business_reset_permission_active)

                                    <li><a href="{{ route('setting.freshBusinessReset') }}"><i class="ti ti-alert-triangle"></i> {{ __('db.fresh_reset_title') }}</a></li>
                                @endif
                                <li>
                                    <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();"><i
                                            class="ti ti-power"></i>
                                        {{ __('db.logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </header>
        @endif


        <div id="content" class="animate-bottom">
            @yield('content')
        </div>

        <footer class="main-footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <p>&copy; {{ gen_setting()->site_title }} | {{ __('Developed') }} {{ __('By') }}
                            <span class="external">{{ gen_setting()->developed_by }}</span> | V
                            {{ env('VERSION') }}</p>
                    </div>
                </div>
                @if (config('app.user_verified') != true)
                <a href="https://wa.me/8801924756759" target="_blank">
                    <div class="contact-button" style="display: flex;justify-content: center;align-items: center;background-color: #9fe870;border-radius: 50%;bottom: 20px;height: 70px;right: 20px;width: 70px;font-size: 30px;color: #f5f6f7;text-align: center;line-height: 64px;position: fixed;z-index: 999;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#101010" class="bi bi-whatsapp" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"></path>
                        </svg>
                    </div>
                    <span style="background-color: #9fe870;border-radius: 15px;bottom: 10px;height: 18px;right: 5px;width: 100px;font-size: 12px;font-weight:600;color: #111;text-align: center;line-height: 1.5;position: fixed;z-index: 999;">Have Questions?</span>
                </a>
                @endif
            </div>
        </footer>

        @include('backend.layout.modals.notification')

        @include('backend.layout.modals.category')

        @include('backend.layout.modals.expense')
        @include('backend.layout.modals.income')

        @include('backend.layout.modals.sale_return')

        @include('backend.layout.modals.sale_exchange')

        @include('backend.layout.modals.purchase_return')

        @include('backend.layout.modals.account')

        @if(app(\App\Services\AccountingModeService::class)->isLegacy())
            @include('backend.layout.modals.account_statement')
        @endif

        @include('backend.layout.modals.warehouse')

        @include('backend.layout.modals.user')

        @include('backend.layout.modals.biller')

        @include('backend.layout.modals.customer')

        @include('backend.layout.modals.customer_group')

        @include('backend.layout.modals.supplier')
    </div>

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/bootstrap-datepicker.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/popper.js/umd/popper.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/bootstrap/js/bootstrap-select.min.js') }}"></script>

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery.cookie/jquery.cookie.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery-validation/jquery.validate.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'js/front.js') }}"></script>

    @if (optional(Route::current())->getName() != '/')
        <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/daterange/js/moment.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/daterange/js/knockout-3.4.2.js') }}"></script>
        <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/daterange/js/daterangepicker.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/tinymce/js/tinymce/tinymce.min.js') }}"></script>

    @endif

        <script>
        window.SaleproConfig = {
            theme: '{{ $theme ?? "default" }}',
            alertProduct: <?php echo json_encode($alertProductCount + $dsoAlertProductCount + $expireAlertProductCount + $pendingEcommerceOrdersCount); ?>,
            switchThemeDarkUrl: '{{ route("switchTheme", "dark") }}',
            switchThemeLightUrl: '{{ route("switchTheme", "light") }}',
            currency: "{{ config('currency') }}",
            currency_position: "{{ config('currency_position') }}",
            decimal: {{ config('decimal') }},
            starting_date: '{{ $starting_date ?? $start_date ?? "" }}',
            ending_date: '{{ $ending_date ?? $end_date ?? "" }}',
            isRtl: {{ (Config::get('app.locale') == 'ar' || gen_setting()->is_rtl) ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset($asset_prefix . 'js/layout-custom.js') }}"></script>

    @include('backend.layout.theme_customizer')
    @stack('scripts')
</body>
</html>
