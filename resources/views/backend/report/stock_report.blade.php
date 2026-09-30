@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush


@section('content')
<style type="text/css">
    .top-fields{margin-top:10px;position: relative;}
    .top-fields label {font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9;}
    .top-fields input{font-size:13px;height:45px}
    .dt-buttons{width: 100%}
    .metric-card {border-radius: 10px; border: 1px solid #ddd; padding: 15px; text-align: center; box-shadow: 0 5px 5px aliceblue;}
    .metric-value{font-size: 18px; font-weight: 600; margin-top: 5px;}
</style>
<section class="forms">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{ __('db.Stock Report') }}</h3>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">{{ __('db.Stock Value by Cost') }}</div>
                            <div class="metric-value">
                                {{ config('currency') }}
                                <span id="closing_stock_cost">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">{{ __('db.Stock Value by Price') }}</div>
                            <div class="metric-value">
                                {{ config('currency') }}
                                <span id="closing_stock_price">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">{{ __('db.profit') }}</div>
                            <div class="metric-value">
                                {{ config('currency') }}
                                <span id="potential_profit">0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">{{ __('db.Profit Margin') }}</div>
                            <div class="metric-value">
                                <span id="profit_margin">0.00</span>
                            </div>
                        </div>
                    </div>

                </div>
                <hr>
                <div class="row mb-3 product-report-filter">

                    {{-- Warehouse --}}
                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label>
                                {{ __('db.Choose Warehouse') }}
                            </label>
                            <select id="warehouse_id"
                                    class="selectpicker form-control"
                                    data-live-search="true">
                                <option value="0">{{ __('db.All Warehouse') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}"
                                        {{ $warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label>
                                {{ __('db.category') }}
                            </label>
                            <select id="category_id"
                                    class="selectpicker form-control"
                                    data-live-search="true">
                                <option value="0">{{ __('db.Select Category') }}</option>
                                @foreach (get_active_categories() as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Stock Status --}}
                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label>
                                {{ __('db.status') }}
                            </label>
                            <select id="stock_status"
                                    class="selectpicker form-control">
                                <option value="">{{ __('db.All') }}</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive mt-3">
        <table id="stock-table" class="table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('db.Code') }}</th>
                    <th>{{ __('db.Product') }}</th>
                    <th>{{ __('db.Variant') }}</th>
                    <th>{{ __('db.category') }}</th>
                    <th>{{ __('db.Warehouse') }}</th>
                    <th>{{ __('db.Unit Cost') }} ({{ config('currency') }})</th>
                    <th>{{ __('db.Unit Price') }} ({{ config('currency') }})</th>
                    <th>{{ __('db.stock') }}</th>
                    <th>{{ __('db.Stock Value by Cost') }}</th>
                    <th>{{ __('db.Stock Value by Price') }}</th>
                    <th>{{ __('db.profit') }} ({{ config('currency') }})</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <tr>
                    <th colspan="7" class="text-right">
                        {{ __('db.Total') }}:
                    </th>
                    <th id="total_qty"></th>
                    <th id="total_cost_value"></th>
                    <th id="total_price_value"></th>
                    <th id="total_profit"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
$(document).ready(function(){

    // Initialize DataTable first
    var stockTable = $('#stock-table').DataTable({
        processing: true,
        serverSide: true,
        dom: '<"row align-items-center"' +
            '<"col-md-4"l>' +
            '<"col-md-4 text-center"f>' +
            '<"col-md-4 text-end"B>' +
        '>rtip',
        ajax: {
            url: "{{ route('report.stock-data') }}",
            type: "POST",
            data: function(d){
                d._token = "{{ csrf_token() }}";
                d.warehouse_id = parseInt($('#warehouse_id').val());
                d.category_id = parseInt($('#category_id').val());
                d.stock_status = $('#stock_status').val();
            }
        },
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        columns: [
            { data: 'code' },
            { data: 'name' },
            { data: 'variant' },
            { data: 'category' },
            { data: 'warehouse' },
            { data: 'cost' },
            { data: 'price' },
            { data: 'qty' },
            { data: 'stock_cost' },
            { data: 'stock_price' },
            { data: 'profit' }
        ],
        drawCallback: function(settings){
            if(settings.json && settings.json.footer){
                $('#total_qty').html(settings.json.footer.total_qty);
                $('#total_cost_value').html(settings.json.footer.total_cost_value);
                $('#total_price_value').html(settings.json.footer.total_price_value);
                $('#total_profit').html(settings.json.footer.total_profit);
            }
            if(settings.json && settings.json.summary){
                $('#closing_stock_cost').html(settings.json.summary.closing_stock_cost);
                $('#closing_stock_price').html(settings.json.summary.closing_stock_price);
                $('#potential_profit').html(settings.json.summary.potential_profit);
                $('#profit_margin').html(settings.json.summary.profit_margin);
            }
        },
        order: [[0, 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
            }
        ],
        buttons: [
            {
                extend: "pdfHtml5",
                text: '<i class="ti ti-file-type-pdf"></i>',
                footer: true,
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ":visible:not(.not-exported)"
                }
            },
            {
                extend: "csvHtml5",
                text: '<i class="ti ti-file-type-csv"></i>',
                footer: true,
                exportOptions: {
                    columns: ":visible:not(.not-exported)"
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    });

    // Reload DataTable on other filter changes
    $('#warehouse_id, #category_id, #stock_status').on('change', function(){
        stockTable.ajax.reload();
    });

});
</script>
@endpush