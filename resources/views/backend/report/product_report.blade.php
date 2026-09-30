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
</style>
<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Product Report')}}</h3>
            </div>
            <form>
                <div class="row mb-3 product-report-filter">
                    <div class="col-md-3 offset-md-2 mt-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Choose Your Date')}}</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                    <input type="hidden" name="start_date" value="{{$start_date}}" />
                                    <input type="hidden" name="end_date" value="{{$end_date}}" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Choose Warehouse')}}</label>
                            <select name="warehouse_id" class="selectpicker form-control" data-live-search="true">
                                <option value="0">{{__('db.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.category')}}</label>
                            <select name="category_id" class="selectpicker form-control" data-live-search="true">
                                <option value="0">All Category</option>
                                @foreach (get_active_categories() as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="product-report-table" class="table table-hover" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.product') }}</th>
                    <th>{{ __('db.category') }}</th>
                    <th>{{ __('db.imei_numbers') }}</th>

                    @if (auth()->user()->role_id < 3)
                        <th>{{ __('db.Purchased Amount') }}</th>
                        <th>{{ __('db.Purchased') }} {{ __('db.qty') }}</th>
                    @endif

                    <th>{{ __('db.Sold Amount') }}</th>
                    <th>{{ __('db.Tax') }} ({{ config('currency') }})</th>
                    <th>{{ __('db.Sold') }} {{ __('db.qty') }}</th>
                    <th>Returned Amount</th>
                    <th>Returned Qty</th>

                    @if (auth()->user()->role_id < 3)
                        <th>Purchase Returned Amount</th>
                        <th>Purchase Returned Qty</th>
                    @endif

                    <th>{{ __('db.profit') }} ({{ config('currency') }})</th>
                    <th>{{ __('db.In Stock') }}</th>

                    @if (auth()->user()->role_id < 3)
                        <th>{{ __('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')' }}</th>
                    @endif
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <tr>
                    <th></th>
                    <th></th>
                    <th>{{ __('db.Total') }}</th>
                    <th></th>

                    @if (auth()->user()->role_id < 3)
                        <th></th>
                        <th></th>
                    @endif

                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>

                    @if (auth()->user()->role_id < 3)
                        <th></th>
                        <th></th>
                    @endif

                    <th></th>
                    <th></th>

                    @if (auth()->user()->role_id < 3)
                        <th></th>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var warehouse_id = <?php echo json_encode($warehouse_id)?>;
    var category_id = <?php echo json_encode($category_id)?>;
    $('.product-report-filter select[name="warehouse_id"]').val(warehouse_id);
    $('.product-report-filter select[name="category_id"]').val(category_id);
    $('.selectpicker').selectpicker('refresh');

    var userRole = @json(auth()->user()->role_id);
    var columns = [
        { data: "key" },
        { data: "name" },
        { data: "category" },
        { data: "imei_numbers", name: 'imei_numbers' }
    ];
    if (userRole < 3) {
        columns.push(
            { data: "purchased_amount" },
            { data: "purchased_qty" }
        );
    }
    columns = columns.concat([
        { data: "sold_amount" },
        { data: "tax" },
        { data: "sold_qty" },
        { data: "returned_amount" },
        { data: "returned_qty" }
    ]);
    if (userRole < 3) {
        columns.push(
            { data: "purchase_returned_amount" },
            { data: "purchase_returned_qty" }
        );
    }
    columns.push(
        { data: "profit" },
        { data: "in_stock" }
    );
    if (userRole < 3) {
        columns.push(
            { data: "stock_worth" }
        );
    }

    targets = [0, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    if (userRole < 3)
        targets = [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];

    var product_report_table = $('#product-report-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url: "{{ url('report/product_report_data') }}",
            data:function(d){
                d.start_date = $(".product-report-filter input[name=start_date]").val();
                d.end_date = $(".product-report-filter input[name=end_date]").val();
                d.warehouse_id = $(".product-report-filter select[name=warehouse_id]").val();
                d.category_id = $(".product-report-filter select[name=category_id]").val();
            },
            dataType: "json",
            type:"post",
            /*success:function(data){
                console.log(data);
            }*/
            error: function(xhr, status, error) {
                console.error(xhr.responseText, 'error-hi'); // Debugging: Check for errors
            }
        },
        /*"createdRow": function( row, data, dataIndex ) {
            console.log(data);
            $(row).addClass('purchase-link');
            //$(row).attr('data-purchase', data['purchase']);
        },*/
        columns: columns,
        'createdRow': function(row, data, dataIndex) {
            // Apply the scrollable class to the IMEI numbers cell
            if (data.imei_numbers != 'N/A') {
                $('td', row).eq(3).attr('style', 'max-height: 100px; overflow-y: auto; word-break: break-word; white-space: normal; display: block; padding-right: 10px; width: 130px;');
            }
        },
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                targets: targets
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            if (column === 0) {
                                data = String(data);
                                data = data.split("<br/>").join("\n");
                            }
                            if (column === 2) {
                                data = String(data);
                                data = data.split("<br/>").join("\n");
                            }
                            return data ?? "";
                        }
                    }
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );

    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));

        product_report_table.ajax.reload();
    });

    // On warehouse change
    $('.product-report-filter select[name="warehouse_id"]').on('change', function () {
        product_report_table.ajax.reload();
    });

    // On category change
    $('.product-report-filter select[name="category_id"]').on('change', function () {
        product_report_table.ajax.reload();
    });

    function stock_worth_price_cost_from_string(values) {
        stock_worth_price = 0;
        stock_worth_cost = 0;
        for (let i = 0; i < values.length; i++) {
            value = values[i].split(' ');
            [divident, divisor] = [value[1], value[4]];
            stock_worth_price += Number(divident);
            stock_worth_cost += Number(divisor);
        }

        return [stock_worth_price, stock_worth_cost];
    }

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum()));
            if (userRole < 3) {
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 12 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 12, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 13 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 13, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 14 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 14, { page: 'current' } ).data().sum()));

                [stock_worth_price, stock_worth_cost] = stock_worth_price_cost_from_string(dt_selector.column(15, { page: 'current' }).data());

                $( dt_selector.column( 15 ).footer() ).html(stock_worth_price.toFixed({{gen_setting()->decimal}}) + ' / ' + stock_worth_cost.toFixed({{gen_setting()->decimal}}));
            }
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.column( 4, {page:'current'} ).data().sum()));
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.column( 7, {page:'current'} ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
            $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.column( 9, {page:'current'} ).data().sum()));
            $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.column( 10, {page:'current'} ).data().sum()));
            if (userRole < 3) {
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.column( 11, {page:'current'} ).data().sum()));
                $( dt_selector.column( 12 ).footer() ).html(formatCurrency(dt_selector.column( 12, {page:'current'} ).data().sum()));
                $( dt_selector.column( 13 ).footer() ).html(formatCurrency(dt_selector.column( 13, {page:'current'} ).data().sum()));
                $( dt_selector.column( 14 ).footer() ).html(formatCurrency(dt_selector.column( 14, {page:'current'} ).data().sum()));

                [stock_worth_price, stock_worth_cost] = stock_worth_price_cost_from_string(dt_selector.column(15, { page: 'current' }).data());

                $( dt_selector.column( 15 ).footer() ).html(stock_worth_price.toFixed({{gen_setting()->decimal}}) + ' / ' + stock_worth_cost.toFixed({{gen_setting()->decimal}}));
            }
        }
    }
</script>
@endpush
