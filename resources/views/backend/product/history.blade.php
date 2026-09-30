@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
<style type="text/css">
    .btn-icon i{margin-right:5px}
    .top-fields{margin-top:10px;position: relative;}
    .top-fields label {font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9;}
    .top-fields input{font-size:13px;height:45px}
</style>
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2 mb-2">
                <h3 class="text-center">{{$product_data->name.' ['.$product_data->code.']'}}</h3>
            </div>
            <form id="history-filter" class="mb-3" action="{{ route('products.history') }}" method="GET">
                @csrf
                <div class="row ml-1">
                    <input type="hidden" name="product_id" value="{{$product_id}}">
                    <div class="col-md-3">
                        <div class="form-group  top-fields">
                            <label>{{__('db.date')}}</label>
                            <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                            <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                            <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                        </div>
                    </div>
                    <div class="col-md-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                        <div class="form-group  top-fields">
                            <label>{{__('db.Warehouse')}}</label>
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                <option value="0">{{__('db.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                        <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                        <span>Loading results...</span>
                    </div>
                    <!-- <div class="col-md-2 mt-2">
                        <div class="form-group">
                            <button class="btn btn-primary" id="filter-btn" type="submit">{{__('db.submit')}}</button>
                        </div>
                    </div> -->
                </div>
            </form>
        </div>
    </div>
    <ul class="nav nav-tabs ml-4 mt-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#product-sale" role="tab" data-toggle="tab">{{__('db.Sale')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#product-purchase" role="tab" data-toggle="tab">{{__('db.Purchase')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#product-sale-return" role="tab" data-toggle="tab">{{__('db.Sale Return')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#product-purchase-return" role="tab" data-toggle="tab">{{__('db.Purchase Return')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#product-adjustment" role="tab" data-toggle="tab">
                {{__('db.Adjustment')}}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#product-transfer" role="tab" data-toggle="tab">
                {{__('db.Transfer')}}
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <!-- sale table -->
        <div role="tabpanel" class="tab-pane fade show active" id="product-sale">
            <div class="table-responsive mb-4">
                <table id="sale-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-sale"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.qty')}}</th>
                            <th>{{__('db.Unit Price')}}</th>
                            <th>{{__('db.Subtotal')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <th></th>
                        <th>{{__('db.Total')}}</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- purchase table -->
        <div role="tabpanel" class="tab-pane fade" id="product-purchase">
            <div class="table-responsive mb-4">
                <table id="purchase-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-purchase"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.qty')}}</th>
                            <th>{{__('db.Unit Price')}}</th>
                            <th>{{__('db.Subtotal')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <th></th>
                        <th>{{__('db.Total')}}</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- sale return table -->
        <div role="tabpanel" class="tab-pane fade" id="product-sale-return">
            <div class="table-responsive mb-4">
                <table id="sale-return-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-sale-return"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.qty')}}</th>
                            <th>{{__('db.Unit Price')}}</th>
                            <th>{{__('db.Subtotal')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <th></th>
                        <th>{{__('db.Total')}}</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- purchase return table -->
        <div role="tabpanel" class="tab-pane fade" id="product-purchase-return">
            <div class="table-responsive mb-4">
                <table id="purchase-return-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-purchase-return"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.qty')}}</th>
                            <th>{{__('db.Unit Price')}}</th>
                            <th>{{__('db.Subtotal')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <th></th>
                        <th>{{__('db.Total')}}</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- adjustment table -->
        <div role="tabpanel" class="tab-pane fade" id="product-adjustment">
            <div class="table-responsive mb-4">
                <table id="adjustment-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.qty')}}</th>
                            <th>{{__('db.note')}}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <!-- transfer table -->
         <div role="tabpanel" class="tab-pane fade" id="product-transfer">
            <table id="transfer-table" class="table table-hover" style="width: 100%">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{__('db.date')}}</th>
                        <th>{{__('db.reference')}}</th>
                        <th>{{__('db.From')}}</th>
                        <th>{{__('db.To')}}</th>
                        <th>{{__('db.qty')}}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;
    var product_id = <?php echo json_encode($product_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('.nav-tabs li').on('click', function() {
        $('.daterangepicker-field').val(starting_date + ' To ' + ending_date);
        $("input[name=starting_date]").val(starting_date);
        $("input[name=ending_date]").val(ending_date);
        $("#warehouse_id").val(0);
        $("#warehouse_id").selectpicker('refresh');
    });

    $("#warehouse_id").val(warehouse_id);

    //retreiving sale table data
    var saleTable = $('#sale-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"sale-history-data",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "customer"},
            {"data": "qty"},
            {"data": "unit_price"},
            {"data": "sub_total"}
        ],
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
                'targets': [0, 3, 4, 5, 6, 7]
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
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_sale(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_sale(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_sale(dt, false);
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
            datatable_sum_sale(api, false);
        }
    });

    // function datatable_sum_sale(dt_selector, is_calling_first) {
    //     if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
    //         var rows = dt_selector.rows( '.selected' ).indexes();

    //         $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
    //     }
    //     else {
    //         $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
    //     }
    // }

    function datatable_sum_sale(dt, is_calling_first) {

        let total_qty = 0;
        let total_subtotal = 0;

        let rows;

        if (dt.rows('.selected').any() && is_calling_first) {
            rows = dt.rows('.selected').indexes();
        } else {
            rows = dt.rows({ page: 'current' }).indexes();
        }

        rows.each(function (index) {
            let row = dt.row(index).data();

            let qty = parseFloat(row.qty_value);
            let value = parseFloat(row.operation_value);
            let operator = row.operator;

            total_qty += parseFloat(row.qty_base);

            // subtotal sum (existing)
            total_subtotal += parseFloat(row.sub_total_value);
        });

        $(dt.column(5).footer()).html(total_qty.toFixed(2) + ' pcs'); // base unit
        $(dt.column(7).footer()).html(formatCurrency(total_subtotal));
    }

    //retreiving purchase table data
    var purchaseTable = $('#purchase-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"purchase-history-data",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "supplier"},
            {"data": "qty"},
            {"data": "unit_cost"},
            {"data": "sub_total"}
        ],
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
                'targets': [0, 3, 4, 5, 6, 7]
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
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_purchase(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_purchase(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_purchase(dt, false);
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
            datatable_sum_purchase(api, false);
        }
    });
    
    function datatable_sum_purchase(dt, is_calling_first) {

        let total_qty = 0;
        let total_subtotal = 0;

        let rows;

        if (dt.rows('.selected').any() && is_calling_first) {
            rows = dt.rows('.selected').indexes();
        } else {
            rows = dt.rows({ page: 'current' }).indexes();
        }

        rows.each(function (index) {
            let row = dt.row(index).data();

            total_qty += parseFloat(row.qty_base);

            total_subtotal += parseFloat(row.sub_total_value);
        });

        $(dt.column(5).footer()).html(total_qty.toFixed(2) + ' pcs');
        $(dt.column(7).footer()).html(formatCurrency(total_subtotal));
    }

    //retreiving sale return table data
    var saleReturnTable = $('#sale-return-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"sale-return-history-data",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "customer"},
            {"data": "qty"},
            {"data": "unit_price"},
            {"data": "sub_total"}
        ],
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
                'targets': [0, 3, 4, 5, 6, 7]
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
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_sale_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_sale_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-sale-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_sale_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_sale_return(dt, false);
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
            datatable_sum_sale_return(api, false);
        }
    });

    function datatable_sum_sale_return(dt, is_calling_first) {

        let total_qty = 0;
        let total_subtotal = 0;

        let rows;

        if (dt.rows('.selected').any() && is_calling_first) {
            rows = dt.rows('.selected').indexes();
        } else {
            rows = dt.rows({ page: 'current' }).indexes();
        }

        rows.each(function (index) {
            let row = dt.row(index).data();

            total_qty += parseFloat(row.qty_base);

            total_subtotal += parseFloat(row.sub_total_value);
        });

        $(dt.column(5).footer()).html(total_qty.toFixed(2) + ' pcs');
        $(dt.column(7).footer()).html(formatCurrency(total_subtotal));
    }

    //retreiving purchase return table data
    var purchaseReturnTable = $('#purchase-return-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"purchase-return-history-data",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "supplier"},
            {"data": "qty"},
            {"data": "unit_cost"},
            {"data": "sub_total"}
        ],
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
                'targets': [0, 3, 4, 5, 6, 7]
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
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_purchase_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_purchase_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-purchase-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_purchase_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_purchase_return(dt, false);
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
            datatable_sum_purchase_return(api, false);
        }
    });

    function datatable_sum_purchase_return(dt, is_calling_first) {

        let total_qty = 0;
        let total_subtotal = 0;

        let rows;

        if (dt.rows('.selected').any() && is_calling_first) {
            rows = dt.rows('.selected').indexes();
        } else {
            rows = dt.rows({ page: 'current' }).indexes();
        }

        rows.each(function (index) {
            let row = dt.row(index).data();

            total_qty += parseFloat(row.qty_base);

            total_subtotal += parseFloat(row.sub_total_value);
        });

        $(dt.column(5).footer()).html(total_qty.toFixed(2) + ' pcs');
        $(dt.column(7).footer()).html(formatCurrency(total_subtotal));
    }

    // retreiving adjustment table data
    var adjustmentTable = $('#adjustment-table').DataTable({
        processing: true,
        serverSide: false, // JSON parsing happens in PHP
        ajax: {
            url: "adjustment-history-data",
            type: "post",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json"
        },
        columns: [
            { data: 'key' },
            { data: 'date' },
            { data: 'reference' },
            { data: 'warehouse' },
            { data: 'qty' },
            { data: 'note' }
        ],
        order: [[1, 'desc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false }
        ],
    });
    var transferTable = $('#transfer-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "transfer-history-data",
            type: "post",
            data: function (d) {
                d.product_id = product_id;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json"
        },
        columns: [
            { data: 'key', orderable: false, searchable: false },
            { data: 'date' },
            { data: 'reference' },
            { data: 'from' },
            { data: 'to' },
            { data: 'qty' }
        ],
        order: [[1, 'desc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false }
        ],
    });

    function reloadActiveTable() {
        let table = null;

        if ($('#product-sale').hasClass('active')) {
            table = saleTable;
        } else if ($('#product-purchase').hasClass('active')) {
            table = purchaseTable;
        } else if ($('#product-sale-return').hasClass('active')) {
            table = saleReturnTable;
        } else if ($('#product-purchase-return').hasClass('active')) {
            table = purchaseReturnTable;
        } else if ($('#product-adjustment').hasClass('active')) {
            table = adjustmentTable;
        } else if ($('#product-transfer').hasClass('active')) {
            table = transferTable;
        }

        if (table) {
            table.ajax.reload();
        }
    }

    function attachLoader(table) {
        table.on('preXhr.dt', function () {
            $('#filter-loading').show();
        });

        table.on('xhr.dt', function () {
            $('#filter-loading').hide();
        });
    }

    // Run once
    attachLoader(saleTable);
    attachLoader(purchaseTable);
    attachLoader(saleReturnTable);
    attachLoader(purchaseReturnTable);
    attachLoader(adjustmentTable);
    attachLoader(transferTable);

    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('input[name=starting_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(picker.endDate.format('YYYY-MM-DD'));

        reloadActiveTable();
    });

    $('#warehouse_id').on('change', function () {
        reloadActiveTable();
    });

</script>
@endpush
