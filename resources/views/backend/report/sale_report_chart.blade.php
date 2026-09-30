@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main') @section('content')
<section class="forms">
    <div class="container-fluid">
    	<div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Sale Report Chart')}}</h3>
            </div>
            <form action="{{ route('report.saleChart') }}" method="POST">
                @csrf
                <div class="row ml-2">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><strong>{{__('db.Choose Your Date')}}</strong></label>
                            <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                            <input type="hidden" name="start_date" value="{{$start_date}}" />
                            <input type="hidden" name="end_date" value="{{$end_date}}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Warehouse')}}</strong> &nbsp;</label>
                            <input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}" />
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                <option value="0">{{__('db.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><strong>{{__('db.Time Period')}}</strong></label>
                            <select class="form-control" name="time_period">
                                @if($time_period == 'weekly')
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                @else
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>{{__('db.product_list')}}</strong></label>
                            <input type="text"
                                name="product_list"
                                class="form-control"
                                value="{{ $product_list ?? '' }}"
                                placeholder="{{__('db.Type product code seperated by comma')}}">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-12" id="chart-container">
            @include('backend.report.partials.sale_report_chart_table')
        </div>
    </div>
</section>

@endsection
@push('scripts')
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/chart.js/Chart.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'js/charts-custom.js') }}"></script>

    <script type="text/javascript">

        $("ul#report").siblings('a').attr('aria-expanded','true');
        $("ul#report").addClass("show");
        $("ul#report #sale-report-chart-menu").addClass("active");

        $('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
        $('.selectpicker').selectpicker('refresh');

        var sale_chart_instance = null;

        function initializeSaleChart() {
            var SALEREPORTCHART = $('#sale-report-chart');
            if (SALEREPORTCHART.length > 0) {
                var recieved = SALEREPORTCHART.data('recieved');
                var brandPrimary = SALEREPORTCHART.data('color');
                var brandPrimaryRgba = SALEREPORTCHART.data('color_rgba');
                var soldqty = SALEREPORTCHART.data('soldqty');
                var datepoints = SALEREPORTCHART.data('datepoints');
                var label1 = SALEREPORTCHART.data('label1');

                if (sale_chart_instance) {
                    sale_chart_instance.destroy();
                }

                sale_chart_instance = new Chart(SALEREPORTCHART, {
                    type: 'line',
                    data: {
                        labels: datepoints,
                        datasets: [
                            {
                                label: label1,
                                fill: true,
                                lineTension: 0.3,
                                backgroundColor: 'transparent',
                                borderColor: brandPrimary,
                                borderCapStyle: 'butt',
                                borderDash: [],
                                borderDashOffset: 0.0,
                                borderJoinStyle: 'miter',
                                borderWidth: 3,
                                pointBorderColor: brandPrimary,
                                pointBackgroundColor: "#fff",
                                pointBorderWidth: 5,
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: brandPrimary,
                                pointHoverBorderColor: "rgba(220,220,220,1)",
                                pointHoverBorderWidth: 2,
                                pointRadius: 1,
                                pointHitRadius: 10,
                                data: soldqty,
                                spanGaps: false
                            },
                        ]
                    }
                });
            }
        }

        function reloadSaleChart() {
            var formData = $('form').serialize();
            $.ajax({
                url: "{{ route('report.saleChart') }}",
                data: formData,
                method: 'POST',
                beforeSend: function () {
                    var loadingText = @json(__('db.Loading'));

                    $('#chart-container').html(
                        '<div class="text-center mt-4 mb-4">' +
                            '<i class="ti ti-loader-2 ti-spin"></i> ' +
                            loadingText +
                            '...' +
                        '</div>'
                    );
                },
                success: function (response) {
                    $('#chart-container').html(response);
                    initializeSaleChart();
                }
            });
        }

        // warehouse change
        $('#warehouse_id').on('change', function () {
            reloadSaleChart();
        });

        // time period change
        $('select[name="time_period"]').on('change', function () {
            reloadSaleChart();
        });

        // product list (with delay typing)
        var typingTimer;
        $('input[name="product_list"]').on('keyup', function (e) {
            clearTimeout(typingTimer);
            // Search if they type a comma, press enter, or delete characters (e.g. removing a comma)
            if (e.key === ',' || e.keyCode === 188 || e.key === 'Backspace' || e.keyCode === 8 || e.key === 'Delete' || e.keyCode === 46) {
                typingTimer = setTimeout(reloadSaleChart, 600);
            }
        });

        $('input[name="product_list"]').on('keydown', function () {
            clearTimeout(typingTimer);
        });

        // date range change
        $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            reloadSaleChart();
        });
    </script>
@endpush
