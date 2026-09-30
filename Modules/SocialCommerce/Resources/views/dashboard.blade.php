@extends('backend.layout.main')

@section('content')
<style>
    .sc-page-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .sc-page-head h2 { margin: 0; font-size: 1.45rem; font-weight: 600; }
    .sc-filter-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sc-metric .wrapper { min-height: 118px; border: 1px solid #eef0f4; box-shadow: none; }
    .sc-table-card .card-header { min-height: 52px; }
    .sc-table-card .card-header h4 { margin: 0; font-size: 1rem; font-weight: 600; }
    .sc-empty { padding: 28px 12px; color: #6c757d; }
    .sc-channel { display: inline-flex; align-items: center; gap: 6px; }
    .sc-channel-dot { width: 8px; height: 8px; border-radius: 50%; background: #733686; display: inline-block; }
    @media (max-width: 767px) {
        .sc-page-head { align-items: stretch; }
        .sc-filter-form, .sc-filter-form select, .sc-filter-form button { width: 100%; }
        #custom-date-row .col-md-3, #custom-date-row .col-md-2 { margin-bottom: 8px; }
    }
</style>
<section class="dashboard">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="sc-page-head">
                    <h2>{{ __('db.Social Commerce Dashboard') }}</h2>
                    <form action="{{ route('socialcommerce.dashboard') }}" method="GET" class="sc-filter-form">
                        <select name="date_filter" class="form-control" onchange="if(this.value != 'custom') this.form.submit(); else document.getElementById('custom-date-row').style.display='flex';">
                            <option value="7" {{ $date_filter == '7' ? 'selected' : '' }}>{{ __('db.Last 7 Days') }}</option>
                            <option value="30" {{ $date_filter == '30' ? 'selected' : '' }}>{{ __('db.Last 30 Days') }}</option>
                            <option value="365" {{ $date_filter == '365' ? 'selected' : '' }}>{{ __('db.Last 365 Days') }}</option>
                            <option value="all" {{ $date_filter == 'all' ? 'selected' : '' }}>{{ __('db.All Time') }}</option>
                            <option value="custom" {{ $date_filter == 'custom' ? 'selected' : '' }}>{{ __('db.Custom Range') }}</option>
                        </select>
                    </form>
                </div>
                
                <form action="{{ route('socialcommerce.dashboard') }}" method="GET" id="custom-date-row" class="row mb-3" style="display: {{ $date_filter == 'custom' ? 'flex' : 'none' }};">
                    <input type="hidden" name="date_filter" value="custom">
                    <div class="col-md-3">
                        <label class="mb-1">{{ __('db.Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date ? $start_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">{{ __('db.End Date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date ? $end_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">{{ __('db.Filter') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Revenue -->
            <div class="col-md-3 sc-metric">
                <div class="wrapper count-title text-center">
                    <div class="icon"><i class="dripicons-wallet" style="color: #733686"></i></div>
                    <div class="name"><strong style="color: #733686">{{ __('db.Total Revenue') }}</strong></div>
                    <div class="count-number revenue-data">{{ number_format((float)$totalRevenue, config('decimal', 2), '.', '') }}</div>
                </div>
            </div>
            <!-- Orders -->
            <div class="col-md-3 sc-metric">
                <div class="wrapper count-title text-center">
                    <div class="icon"><i class="dripicons-shopping-bag" style="color: #ff8952"></i></div>
                    <div class="name"><strong style="color: #ff8952">{{ __('db.Total Orders') }}</strong></div>
                    <div class="count-number">{{ $totalOrders }}</div>
                </div>
            </div>
            <!-- Clicks -->
            <div class="col-md-3 sc-metric">
                <div class="wrapper count-title text-center">
                    <div class="icon"><i class="dripicons-graph-bar" style="color: #00c689"></i></div>
                    <div class="name"><strong style="color: #00c689">{{ __('db.Total Clicks') }}</strong></div>
                    <div class="count-number">{{ $totalClicks }}</div>
                </div>
            </div>
            <!-- Published Products -->
            <div class="col-md-3 sc-metric">
                <div class="wrapper count-title text-center">
                    <div class="icon"><i class="dripicons-tags" style="color: #297ff9"></i></div>
                    <div class="name"><strong style="color: #297ff9">{{ __('db.Published Products') }}</strong></div>
                    <div class="count-number">{{ $publishedProductsCount }}</div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Orders & Revenue by Source -->
            <div class="col-md-6">
                <div class="card sc-table-card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __('db.Performance by Social Channel') }}</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('db.Channel') }}</th>
                                    <th>{{ __('db.Orders') }}</th>
                                    <th>{{ __('db.Revenue') }}</th>
                                    <th>{{ __('db.Clicks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesBySource as $sourceSale)
                                    @php
                                        $sourceClicks = $clicksBySource->where('source', $sourceSale->social_channel)->first();
                                        $clicksCount = $sourceClicks ? $sourceClicks->count : 0;
                                        $channelLabel = $sourceSale->social_channel ? ucfirst($sourceSale->social_channel) : __('db.Direct/Other');
                                    @endphp
                                    <tr>
                                        <td><span class="sc-channel"><span class="sc-channel-dot"></span>{{ $channelLabel }}</span></td>
                                        <td>{{ $sourceSale->count }}</td>
                                        <td>{{ number_format((float)$sourceSale->revenue, config('decimal', 2), '.', '') }}</td>
                                        <td>{{ $clicksCount }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center sc-empty">{{ __('db.No social commerce sales found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-6">
                <div class="card sc-table-card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __('db.Top 5 Social Commerce Products') }}</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('db.Product') }}</th>
                                    <th>{{ __('db.Code') }}</th>
                                    <th>{{ __('db.Sold Qty Net') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->code }}</td>
                                        <td>{{ number_format((float)$product->sold_qty, 2, '.', '') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center sc-empty">{{ __('db.No social commerce sales found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
