@extends('backend.layout.main')
@push('css')
<style type="text/css">
    .top-fields { margin-top:10px; position: relative; }
    .top-fields label { font-size:11px; font-weight:600; margin-left:10px; padding:0 3px; position:absolute; top:-8px; z-index:9; background-color: white; }
    .top-fields input { font-size:13px; height:45px; }
</style>
@endpush
@section('content')
<section>
    <div class="container-fluid">
        <form id="filter-form">
            <div class="card mt-3 mb-2" id="filter-card">
                <div class="card-body">
                    <h3 class="text-center mt-2">{{__('db.Profit and Loss')}}</h3>
                    <button class="btn btn-primary btn-icon" onclick="window.print();" type="button" style="position: absolute; top: 15px; right: 15px"><i class="ti ti-printer"></i></button>
                    <div class="row mt-4">
                        <div class="col-md-4 offset-md-2">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{$warehouse->id}}" {{ isset($warehouse_id) && $warehouse_id == $warehouse->id ? 'selected' : '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group top-fields">
                                <label>{{__('db.date')}}</label>
                                <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                <input type="hidden" name="start_date" value="{{$start_date}}" />
                                <input type="hidden" name="end_date" value="{{$end_date}}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="report-content" class="table-responsive px-4 mb-5 pb-5">
        @if($inventory_close_required)
            <div class="alert alert-warning">{{ __('db.inventory_close_profit_provisional') }}</div>
        @endif
        <table class="table dataTable">
            <!-- Revenue -->
            <thead>
                <tr>
                    <th colspan="2"><h4>Revenue</h4></th>
                </tr>
                <tr>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenues as $revenue)
                <tr>
                    <td>{{$revenue->account_no}} - {{$revenue->name}}</td>
                    <td class="text-right">{{number_format((float)$revenue->net_balance, 2, '.', '')}}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <tr>
                    <th>Gross Revenue</th>
                    <th class="text-right">{{number_format((float)$gross_revenue, 2, '.', '')}}</th>
                </tr>
            </tfoot>

            <!-- Contra Revenue -->
            @if(count($contra_revenues) > 0)
            <thead>
                <tr>
                    <th colspan="2" class="pt-4"><h4>Contra Revenue</h4></th>
                </tr>
                <tr>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contra_revenues as $contra)
                <tr>
                    <td>{{$contra->account_no}} - {{$contra->name}}</td>
                    <td class="text-right">{{number_format((float)$contra->net_balance, 2, '.', '')}}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <tr>
                    <th>Total Contra Revenue</th>
                    <th class="text-right">{{number_format((float)$total_contra_revenue, 2, '.', '')}}</th>
                </tr>
            </tfoot>
            @endif

            <tfoot class="tfoot bg-light">
                <tr>
                    <th><h4>Net Revenue</h4></th>
                    <th class="text-right"><h4>{{number_format((float)$net_revenue, 2, '.', '')}}</h4></th>
                </tr>
            </tfoot>

            @if(count($cost_of_goods_sold) > 0)
            <thead><tr><th colspan="2" class="pt-4"><h4>{{ __('db.inventory_close_cogs') }}</h4></th></tr></thead>
            <tbody>
                @foreach($cost_of_goods_sold as $cogs)
                <tr><td>{{ $cogs->account_no }} - {{ $cogs->name }}</td><td class="text-right">{{ number_format((float)$cogs->net_balance, 2, '.', '') }}</td></tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active"><tr><th>{{ __('db.inventory_close_total_cogs') }}</th><th class="text-right">{{ number_format((float)$total_cost_of_goods_sold, 2, '.', '') }}</th></tr></tfoot>
            @endif
            <tfoot class="tfoot bg-light"><tr><th><h4>{{ __('db.inventory_close_gross_profit') }}</h4></th><th class="text-right"><h4>{{ number_format((float)$gross_profit, 2, '.', '') }}</h4></th></tr></tfoot>

            <!-- Expenses -->
            <thead>
                <tr>
                    <th colspan="2" class="pt-4"><h4>Expenses</h4></th>
                </tr>
                <tr>
                    <th>Account Name</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                <tr>
                    <td>{{$expense->account_no}} - {{$expense->name}}</td>
                    <td class="text-right">{{number_format((float)$expense->net_balance, 2, '.', '')}}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <tr>
                    <th>Total Expenses</th>
                    <th class="text-right">{{number_format((float)$total_expenses, 2, '.', '')}}</th>
                </tr>
            </tfoot>

            <!-- Net Profit -->
            <tfoot class="tfoot text-white" style="background-color: #2c3e50;">
                <tr>
                    <th class="pt-3 pb-3"><h4>Net Profit</h4></th>
                    <th class="text-right pt-3 pb-3"><h4>{{number_format((float)$net_profit, 2, '.', '')}}</h4></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {

        function loadReport() {
            $.ajax({
                url: window.location.pathname,
                type: "GET",
                data: $('#filter-form').serialize(),
                success: function(response) {
                    var newContent = $(response).find('#report-content').html();
                    $('#report-content').html(newContent);
                }
            });
        }

        $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            loadReport();
        });

        $('select[name="warehouse_id"]').on('change', function() {
            loadReport();
        });
    });
</script>
@endpush
@endsection
