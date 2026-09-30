@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@section('content')

@push('css')
<style>
.bootstrap-select:not([class*="col-"]):not([class*="form-control"]):not(.input-group-btn) {width: auto;}
.legend{width: 10px;height: 10px;border-radius: 50%;margin: 0 5px;display: inline-block;}
.legend-label{font-size: 0.8em!important;color: #555;}
</style>
@endpush

    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    @php
        $color = '#733686';
        $color_rgba = 'rgba(115, 54, 134, 0.8)';
        if (gen_setting()->theme == 'default.css') {
            $color = '#733686';
            $color_rgba = 'rgba(115, 54, 134, 0.8)';
        } elseif (gen_setting()->theme == 'green.css') {
            $color = '#2ecc71';
            $color_rgba = 'rgba(46, 204, 113, 0.8)';
        } elseif (gen_setting()->theme == 'blue.css') {
            $color = '#3498db';
            $color_rgba = 'rgba(52, 152, 219, 0.8)';
        } elseif (gen_setting()->theme == 'dark.css') {
            $color = '#34495e';
            $color_rgba = 'rgba(52, 73, 94, 0.8)';
        }
    @endphp
    <div class="row">

        <div class="container-fluid">
            @php
                $lims_warehouse_list = App\Models\Warehouse::where('is_active', true)->get();
            @endphp

            @if (!config('database.connections.saleprosaas_landlord') && \Auth::user()->role_id <= 2)
                @if (isset($versionUpgradeData['alert_version_upgrade_enable']) &&
                        $versionUpgradeData['alert_version_upgrade_enable'] == true)
                    <div class="col-12">
                        <div id="alertSection" class="alert not-slide alert-primary alert-dismissible fade show" role="alert">
                            <p id="announce"><strong>Announce !!!</strong> A new version
                                {{ $versionUpgradeData['demo_version'] }} has been released. Please <i><b><a
                                            href="{{ route('new-release') }}">Click here</a></b></i> to check upgrade details.
                            </p>
                            <button type="button" id="closeButtonUpgrade" class="close" data-dismiss="alert"
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
            <div class="col-12">
                <div class="brand-text float-left mt-4">
                    <h3 style="font-size:1em">{{ __('db.welcome') }} <span>{{ Auth::user()->name }}</span></h3>
                </div>
                @if (in_array('restaurant', explode(',', cache()->get('general_setting')->modules)))
                    @if (Auth::user()->role_id > 2 && isset(Auth::user()->service_staff))
                        @php
                            $cooked = DB::table('sales')
                                ->where('waiter_id', Auth::user()->id)
                                ->where('sale_status', 5)
                                ->orWhere('sale_status', 6)
                                ->where('sales.created_at', '>=', now()->subDay())
                                ->count();
                        @endphp
                    @elseif(Auth::user()->role_id <= 2)
                        @php
                            $cooked = DB::table('sales')
                                ->where('sale_status', 6)
                                ->where('sales.created_at', '>=', now()->subDay())
                                ->count();
                        @endphp
                    @endif
                @endif
                @if (in_array('restaurant', explode(',', cache()->get('general_setting')->modules)) && isset($cooked) && $cooked > 0)
                    <a href="{{ route('restaurant.kitchen.dashboard') }}">
                        <div class="alert alert-warning alert-dismissible text-center mb-2">
                            <strong>{{ $cooked }} {{ __('db.Orders to serve') }}</strong>
                        </div>
                    </a>
                @endif

                @php
                    $revenue_profit_summary = $role_has_permissions_list
                        ->where('name', 'revenue_profit_summary')
                        ->first();
                @endphp
                @if ($revenue_profit_summary)
                    <div class="filter-toggle btn-group d-inline-block">
                        <div class="dashboard-filters">
                            @if (\Auth::user()->role_id <= 2)
                            {{-- Warehouse --}}

                            <div class="filter-toggle btn-group mt-0" style=" border: 1px solid #7c5cc4; border-radius: 5px;">
                                <select name="warehouse_id" class="selectpicker" id="warehouse_btn"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0" data-content="<i class='ti ti-map-pin mr-1'></i> {{ __('db.All Warehouse') }}">{{ __('db.All Warehouse') }}</option>

                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            {{-- Date Range --}}
                            <div id="dashboard-datepicker" class="ml-2">
                                <div class="input-group input-group-md">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="ti ti-calendar text-primary"></i>
                                        </span>
                                    </div>

                                    <input type="text"
                                        class="daterangepicker-field form-control border-left-0"
                                        placeholder="Select Date" style="padding-left:0;min-width: 200px;height:40px"/>

                                    <input type="hidden" name="start_date" value="" />
                                    <input type="hidden" name="end_date" value="" />
                                </div>
                            </div>

                        </div>

                    </div>

                @endif
            </div>
        </div>
    </div>
    <!-- Counts Section -->
    <section class="dashboard-counts pt-0">
        <div class="container-fluid">
            <div class="row">
                @if ($revenue_profit_summary)
                    <style>
                        .dashboard-filters {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        }
                        /* Modern Dashboard Widget Styles */
                        .dashboard-widget {
                            background: #fff;
                            border-radius: 15px; /* Large rounded corners */
                            padding: 20px;
                            margin-bottom: 24px;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* Soft shadow */
                            display: flex;
                            align-items: center;
                            transition: transform 0.2s;
                            border: 1px solid #f0f0f0;
                            text-decoration: none !important;
                        }

                        @media (max-width: 767px) {
                            .dashboard-filters {
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                gap: 10px;
                            }
                            .dashboard-widget {
                                flex-direction: column;
                                text-align: center;
                                margin-bottom: 15px
                            }
                            .widget-icon-container {
                                margin-right: 0 !important;
                                margin-bottom: 10px;
                            }
                        }

                        .dashboard-widget:hover {
                            transform: translateY(-5px);
                        }

                        .widget-icon-container {
                            width: 56px;
                            height: 56px;
                            border-radius: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 24px;
                            margin-right: 15px;
                        }

                        /* Light backgrounds for icons */
                        .bg-light-purple { background-color: #f3e8ff; color: #733686; }
                        .bg-light-cyan { background-color: #e0f7fa; color: #0584a0; }
                        .bg-light-orange { background-color: #fff3e0; color: #ff8952; }
                        .bg-light-red { background-color: #ffebee; color: #f66162; }
                        .bg-light-gold { background-color: #fef9c3; color: #d48519; }
                        .bg-light-yellow { background-color: #fefce8; color: #bdbb39; }
                        .bg-light-green { background-color: #ecfdf5; color: #00c689; }
                        .bg-light-blue { background-color: #eff6ff; color: #297ff9; }

                        .widget-content {
                            flex-grow: 1;
                        }

                        .widget-label {
                            font-size: 0.85rem;
                            color: #64748b;
                            margin-bottom: 4px;
                            font-weight: 500;
                            display: block;
                        }

                        .widget-value {
                            font-size: 1rem !important;
                            font-weight: 600;
                            color: #1e293b;
                            display: block;
                        }
                        .dark-mode .dashboard-widget {
                            background: #283046;
                            border: 1px solid #283046;
                        }
                        .dark-mode .widget-label {
                            color: #d0d2d6;
                        }

                        .dark-mode .widget-value {
                            color: #f0f0f0;
                        }
                        .spin-loading {
                            font-size: 16px !important;
                            display: inline-block; /* Required for rotation to work correctly */
                            animation: spin 1s linear infinite;
                        }

                        @keyframes spin {
                            from {
                                transform: rotate(0deg);
                            }
                            to {
                                transform: rotate(360deg);
                            }
                        }
                    </style>
                    <div class="col-md-12 mt-3">
                        <div class="row">
                            <div class="col-6 col-lg-3">
                                <a href="{{route('sales.index')}}" class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-purple">
                                        <i class="ti ti-chart-bar"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.Sale') }}</span>
                                        <span class="widget-value total_sale-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-6 col-lg-3">
                                <div class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-cyan">
                                        <i class="ti ti-invoice"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.sale_due') }}</span>
                                        <span class="widget-value invoice-due-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <a href="{{route('return-sale.index')}}" class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-orange">
                                        <i class="ti ti-arrow-back"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.Sale Return') }}</span>
                                        <span class="widget-value return-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-6 col-lg-3">
                                <div class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-red">
                                        <i class="ti ti-wallet"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.Expense') }}</span>
                                        <span class="widget-value expense-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <a href="{{route('purchases.index')}}" class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-gold">
                                        <i class="ti ti-download"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.Purchase') }}</span>
                                        <span class="widget-value total_purchase-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <!-- Count item widget-->
                            <div class="col-6 col-lg-3">
                                <div class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-yellow">
                                        <i class="ti ti-credit-card-refund"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.purchase_due') }}</span>
                                        <span class="widget-value purchase_due-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Count item widget-->
                            <div class="col-6 col-lg-3">
                                <a href="{{route('return-purchase.index')}}"  class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-green">
                                        <i class="ti ti-arrow-forward-up"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.Purchase Return') }}</span>
                                        <span class="widget-value purchase_return-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-6 col-lg-3">
                                <div class="dashboard-widget">
                                    <div class="widget-icon-container bg-light-blue">
                                        <i class="ti ti-trophy"></i>
                                    </div>
                                    <div class="widget-content">
                                        <span class="widget-label">{{ __('db.profit') }}</span>
                                        <span class="widget-value profit-data">
                                            {{ number_format((float) 0.00, gen_setting()->decimal, '.', '') }} <i class="ti ti-reload spin-loading" style="font-size: 2rem; color: #3b82f6;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @php
                    $cash_flow = $role_has_permissions_list->where('name', 'cash_flow')->first();
                @endphp
                @if ($cash_flow)
                    <div class="col-md-8 mt-2">
                        <div class="card line-chart">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>{{ __('db.Cash Flow') }}</h4>
                                <div class="legends ml-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="legend" style="background-color: #733686;"></span>
                                        <span class="legend-label">{{ __('db.Payment Recieved') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="legend" style="background-color: #6fb1b5;"></span>
                                        <span class="legend-label">{{ __('db.Payment Sent') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <canvas id="cashFlow" data-color = "{{ $color }}"
                                    data-color_rgba = "{{ $color_rgba }}"
                                    data-recieved = "{{ json_encode($payment_recieved) }}"
                                    data-sent = "{{ json_encode($payment_sent) }}"
                                    data-month = "{{ json_encode($month) }}"
                                    data-label1="{{ __('db.Payment Recieved') }}"
                                    data-label2="{{ __('db.Payment Sent') }}"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
                @php
                    $monthly_summary = $role_has_permissions_list->where('name', 'monthly_summary')->first();
                @endphp
                @if ($monthly_summary)
                    <div class="col-md-4 mt-2">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>{{ date('F') }} {{ date('Y') }}</h4>
                            </div>
                            <div class="pie-chart mb-2">
                                <canvas id="transactionChart" data-color = "{{ $color }}"
                                    data-color_rgba = "{{ $color_rgba }}" data-revenue="{{ $revenue }}"
                                    data-purchase="{{ $purchase }}" data-expense="{{ $expense }}"
                                    data-label1="{{ __('db.Purchase') }}" data-label2="{{ __('db.revenue') }}"
                                    data-label3="{{ __('db.Expense') }}" width="100" height="95"> </canvas>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                @php
                    $yearly_report = $role_has_permissions_list->where('name', 'yearly_report')->first();
                @endphp
                @if ($yearly_report)
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4>{{ __('db.yearly report') }}</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="saleChart" data-sale_chart_value = "{{ json_encode($yearly_sale_amount) }}"
                                    data-purchase_chart_value = "{{ json_encode($yearly_purchase_amount) }}"
                                    data-label1="{{ __('db.Purchased Amount') }}"
                                    data-label2="{{ __('db.Sold Amount') }}"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('db.Recent Transaction') }}</h4>
                            <div class="right-column">
                                <div class="badge badge-primary">{{ __('db.latest') }} 5</div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="#sale-latest" role="tab"
                                    data-toggle="tab">{{ __('db.Sale') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#purchase-latest" role="tab"
                                    data-toggle="tab">{{ __('db.Purchase') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#quotation-latest" role="tab"
                                    data-toggle="tab">{{ __('db.quotation') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#payment-latest" role="tab"
                                    data-toggle="tab">{{ __('db.Payment') }}</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane fade show active" id="sale-latest">
                                <div class="table-responsive">
                                    <table id="recent-sale" class="table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('db.date') }}</th>
                                                <th>{{ __('db.reference') }}</th>
                                                <th>{{ __('db.customer') }}</th>
                                                <th>{{ __('db.status') }}</th>
                                                <th>{{ __('db.grand total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane fade" id="purchase-latest">
                                <div class="table-responsive">
                                    <table id="recent-purchase" class="table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('db.date') }}</th>
                                                <th>{{ __('db.reference') }}</th>
                                                <th>{{ __('db.Supplier') }}</th>
                                                <th>{{ __('db.status') }}</th>
                                                <th>{{ __('db.grand total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane fade" id="quotation-latest">
                                <div class="table-responsive">
                                    <table id="recent-quotation" class="table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('db.date') }}</th>
                                                <th>{{ __('db.reference') }}</th>
                                                <th>{{ __('db.customer') }}</th>
                                                <th>{{ __('db.status') }}</th>
                                                <th>{{ __('db.grand total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane fade" id="payment-latest">
                                <div class="table-responsive">
                                    <table id="recent-payment" class="table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('db.date') }}</th>
                                                <th>{{ __('db.reference') }}</th>
                                                <th>{{ __('db.Amount') }}</th>
                                                <th>{{ __('db.Paid By') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('db.Best Seller') . ' ' . date('F') }}</h4>
                            <div class="right-column">
                                <div class="badge badge-primary">{{ __('db.top') }} 5</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="monthly-best-selling-qty" class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Product Details') }}</th>
                                        <th>{{ __('db.qty') }}</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('db.Best Seller') . ' ' . date('Y') . '(' . __('db.qty') . ')' }}</h4>
                            <div class="right-column">
                                <div class="badge badge-primary">{{ __('db.top') }} 5</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="yearly-best-selling-qty" class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Product Details') }}</th>
                                        <th>{{ __('db.qty') }}</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>{{ __('db.Best Seller') . ' ' . date('Y') . '(' . __('db.Price') . ')' }}</h4>
                            <div class="right-column">
                                <div class="badge badge-primary">{{ __('db.top') }} 5</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="yearly-best-selling-price" class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('db.Product Details') }}</th>
                                        <th>{{ __('db.grand total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


@endsection

@push('scripts')

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/chart.js/Chart.min.js'); }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'js/charts-custom.js'); }}"></script>

    <script type="text/javascript">
        let staff_warehouse_id = @json(auth()->user()->warehouse_id);
        let staff_role_id = @json(auth()->user()->role_id);

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/yearly-best-selling-price') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var url = '{{ url('/images/product') }}';
                    data.forEach(function(item) {
                        if (item.product_images)
                            var images = item.product_images.split(',');
                        else
                            var images = ['zummXD2dvAtI.png'];
                        $('#yearly-best-selling-price').find('tbody').append(
                            '<tr><td><div class="d-flex align-items-center"><img src="' +
                            url + '/' + images[0] +
                            '" width="30" height="25" class="ml-3 mr-3"> ' + item
                            .product_name + ' [' + item.product_code + ']</div></td><td>' +
                            formatCurrency(item.total_price / item.exchange_rate) + '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/yearly-best-selling-qty') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var url = '{{ url('/images/product') }}';
                    data.forEach(function(item) {
                        if (item.product_images)
                            var images = item.product_images.split(',');
                        else
                            var images = ['zummXD2dvAtI.png'];
                        $('#yearly-best-selling-qty').find('tbody').append(
                            '<tr><td><div class="d-flex align-items-center"><img src="' +
                            url + '/' + images[0] +
                            '" width="30" height="25" class="ml-3 mr-3"> ' + item
                            .product_name + ' [' + item.product_code + ']</div></td><td>' +
                            item.sold_qty + '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/monthly-best-selling-qty') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var url = '{{ url('/images/product') }}';
                    data.forEach(function(item) {
                        if (item.product_images)
                            var images = item.product_images.split(',');
                        else
                            var images = ['zummXD2dvAtI.png'];
                        $('#monthly-best-selling-qty').find('tbody').append(
                            '<tr><td><div class="d-flex align-items-center"><img src="' +
                            url + '/' + images[0] +
                            '" width="30" height="25" class="ml-3 mr-3"> ' + item
                            .product_name + ' [' + item.product_code + ']</div></td><td>' +
                            item.sold_qty + '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: "{{ url('/recent-sale') }}",
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(item) {
                        var sale_date = dateFormat(item.created_at.split('T')[0],
                            '{{ gen_setting()->date_format }}')
                        if (item.sale_status == 1) {
                            var status =
                                '<div class="badge badge-success">{{ __('db.Completed') }}</div>';
                        } else if (item.sale_status == 2) {
                            var status =
                                '<div class="badge badge-danger">{{ __('db.Pending') }}</div>';
                        } else if (item.sale_status == 5) {
                            var status =
                                '<div class="badge badge-primary">{{ __('db.Processing') }}</div>';
                        } else if (item.sale_status == 6) {
                            var status =
                                '<div class="badge badge-info">{{ __('db.cooked') }}</div>';
                        } else {
                            var status =
                                '<div class="badge badge-warning">{{ __('db.Draft') }}</div>';
                        }
                        $('#recent-sale').find('tbody').append('<tr><td>' + sale_date +
                            '</td><td>' + item.reference_no + '</td><td>' + item.name +
                            '</td><td>' + status + '</td><td>' + formatCurrency(item.grand_total/item.exchange_rate).toString()
                            .replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/recent-purchase') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(item) {
                        var payment_date = dateFormat(item.created_at.split('T')[0],
                            '{{ gen_setting()->date_format }}')
                        if (item.status == 1) {
                            var status =
                                '<div class="badge badge-success">{{ __('db.Recieved') }}</div>';
                        } else if (item.status == 2) {
                            var status =
                                '<div class="badge badge-danger">{{ __('db.Partial') }}</div>';
                        } else if (item.status == 3) {
                            var status =
                                '<div class="badge badge-danger">{{ __('db.Pending') }}</div>';
                        } else {
                            var status =
                                '<div class="badge badge-warning">{{ __('db.Ordered') }}</div>';
                        }
                        $('#recent-purchase').find('tbody').append('<tr><td>' + payment_date +
                            '</td><td>' + item.reference_no + '</td><td>' + item.name +
                            '</td><td>' + status + '</td><td>' + formatCurrency(item.grand_total/item.exchange_rate).toString()
                            .replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/recent-quotation') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(item) {
                        var quotation_date = dateFormat(item.created_at.split('T')[0],
                            '{{ gen_setting()->date_format }}')
                        if (item.quotation_status == 1) {
                            var status =
                                '<div class="badge badge-success">{{ __('db.Pending') }}</div>';
                        } else if (item.quotation_status == 2) {
                            var status =
                                '<div class="badge badge-danger">{{ __('db.Sent') }}</div>';
                        }
                        $('#recent-quotation').find('tbody').append('<tr><td>' +
                            quotation_date + '</td><td>' + item.reference_no + '</td><td>' +
                            item.name + '</td><td>' + status + '</td><td>' + formatCurrency(item
                            .grand_total).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") +
                            '</td></tr>');
                    })
                }
            });
        });

        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/recent-payment') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(item) {
                        var payment_date = dateFormat(item.created_at.split('T')[0],
                            '{{ gen_setting()->date_format }}')
                        $('#recent-payment').find('tbody').append('<tr><td>' + payment_date +
                            '</td><td>' + item.payment_reference + '</td><td>' + formatCurrency(item.amount/item.exchange_rate)
                            .toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") +
                            '</td><td>' + item.paying_method + '</td></tr>');
                    })
                }
            });
        });

        function dateFormat(inputDate, format) {
            const date = new Date(inputDate);
            //extract the parts of the date
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            //replace the month
            format = format.replace("m", month.toString().padStart(2, "0"));
            //replace the year
            format = format.replace("Y", year.toString());
            //replace the day
            format = format.replace("d", day.toString().padStart(2, "0"));
            return format;
        }


        $(document).ready(function() {
            $.ajax({
                url: '{{ url('/') }}',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#userShowModal').modal('show');
                    $('#user-id').text(data.id);
                    $('#user-name').text(data.name);
                    $('#user-email').text(data.email);
                }
            });
        })
        // Show and hide color-switcher
        $(".color-switcher .switcher-button").on('click', function() {
            $(".color-switcher").toggleClass("show-color-switcher", "hide-color-switcher", 300);
        });

        // Color Skins
        $('a.color').on('click', function() {
            /*var title = $(this).attr('title');
            $('#style-colors').attr('href', 'css/skin-' + title + '.css');
            return false;*/
            $.get('setting/general_setting/change-theme/' + $(this).data('color'), function(data) {});
            var style_link = $('#custom-style').attr('href').replace(/([^-]*)$/, $(this).data('color'));
            $('#custom-style').attr('href', style_link);
        });

        $(".date-btn").on("click", function() {
            $(".date-btn").removeClass("active");
            $(this).addClass("active");
            var start_date = $(this).data('start_date');
            var end_date = $(this).data('end_date');
            var warehouse_id = $("#warehouse_btn").val();
            console.log(warehouse_id);
            $.get('dashboard-filter/' + start_date + '/' + end_date + '/' + warehouse_id, function(data) {
                dashboardFilter(data);
            });
        });

        $("#warehouse_btn").on("change", function() {
            var warehouse_id = $(this).val();
            var start_date = $('input[name="start_date"]').val();
            var end_date = $('input[name="end_date"]').val();

            $.get('dashboard-filter/' + start_date + '/' + end_date + '/' + warehouse_id, function(data) {
                dashboardFilter(data);
            });
        });

        function dashboardFilter(data) {
            // data is an array:
            // [revenue, sale_return, profit, purchase_return, total_sale, invoice_due, total_purchase, purchase_due]

            $('.total_sale-data').hide();
            $('.total_sale-data').html(formatCurrency(parseFloat(data[4] ?? 0)));
            $('.total_sale-data').show(500);

            $('.revenue-data').hide();
            $('.revenue-data').html(formatCurrency(parseFloat(data[0] ?? 0)));
            $('.revenue-data').show(500);

            $('.invoice-due-data').hide();
            $('.invoice-due-data').html(formatCurrency(parseFloat(data[5] ?? 0)));
            $('.invoice-due-data').show(500);

            $('.return-data').hide();
            $('.return-data').html(formatCurrency(parseFloat(data[1] ?? 0)));
            $('.return-data').show(500);

            $('.total_purchase-data').hide();
            $('.total_purchase-data').html(formatCurrency(data[6] ?? 0));
            $('.total_purchase-data').show(500);

            $('.purchase_due-data').hide();
            $('.purchase_due-data').html(formatCurrency(data[7] ?? 0));
            $('.purchase_due-data').show(500);

            $('.expense-data').hide();
            $('.expense-data').html(formatCurrency(data[8] ?? 0));
            $('.expense-data').show(500);

            $('.purchase_return-data').hide();
            $('.purchase_return-data').html(formatCurrency(data[3] ?? 0));
            $('.purchase_return-data').show(500);

            $('.profit-data').hide();
            $('.profit-data').html(formatCurrency(data[2] ?? 0));
            $('.profit-data').show(500);
        }

        $(function () {

            var start = moment().subtract(29, 'days');
            var end = moment();

            // Override initial start/end ONLY once (page load)
            // @if(isset($start_date))
            //     start = moment("{{ $start_date }}", 'YYYY-MM-DD');
            // @endif

            // @if(isset($end_date))
            //     end = moment("{{ $end_date }}", 'YYYY-MM-DD');
            // @endif

            function applyDashboardFilter(start, end) {

                var start_date = start.format('YYYY-MM-DD');
                var end_date = end.format('YYYY-MM-DD');

                // visible field
                $('.daterangepicker-field').val(start_date + ' To ' + end_date);

                // hidden fields (NOW SAFE)
                $('input[name="start_date"]').val(start_date);
                $('input[name="end_date"]').val(end_date);

                // console.log(start_date+' '+end_date);

                $(".date-btn").removeClass("active");

                var warehouse_id = $("#warehouse_btn").val();
                if (warehouse_id === undefined || staff_role_id > 2) {
                    console.log(start_date, end_date);
                    warehouse_id = staff_warehouse_id;
                }

                $.get('dashboard-filter/' + start_date + '/' + end_date + '/' + warehouse_id, function (data) {
                    dashboardFilter(data);
                });
            }

            // 🔴 THIS is the important part
            $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
                applyDashboardFilter(picker.startDate, picker.endDate);
            });

            // initial dashboard load
            applyDashboardFilter(start, end);

        });

    </script>
@endpush
