@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Warehouse Report')}}</h3>
            </div>
            <form action="{{ route('report.warehouse') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-5 offset-md-1 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Your Date')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <input type="text" id="daterange" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                    <input type="hidden" name="start_date" value="{{$start_date}}" />
                                    <input type="hidden" name="end_date" value="{{$end_date}}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Warehouse')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}" />
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}" />
            </form>
        </div>
    </div>
    <ul class="nav nav-tabs ml-4 mt-3" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" href="#warehouse-sale" role="tab" data-toggle="tab">{{__('db.Sale')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#warehouse-purchase" role="tab" data-toggle="tab">{{__('db.Purchase')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#warehouse-quotation" role="tab" data-toggle="tab">{{__('db.quotation')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#warehouse-return" role="tab" data-toggle="tab">{{__('db.return')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#warehouse-expense" role="tab" data-toggle="tab">{{__('db.Expense')}}</a>
      </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="warehouse-sale">
            <div class="table-responsive mb-4">
                <table id="sale-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-sale"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}} No</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Due')}} ({{ config('currency') }})</th>
                            <th>{{__('db.status')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="warehouse-purchase">
            <div class="table-responsive mb-4">
                <table id="purchase-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-purchase"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}} No</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Due')}} ({{ config('currency') }})</th>
                            <th>{{__('db.status')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="warehouse-quotation">
            <div class="table-responsive mb-4">
                <table id="quotation-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-quotation"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.status')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="warehouse-return">
            <div class="table-responsive mb-4">
                <table id="return-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-return"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Biller')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="warehouse-expense">
            <div class="table-responsive mb-4">
                <table id="expense-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-expense"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.category')}}</th>
                            <th>{{__('db.Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.note')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #warehouse-report-menu").addClass("active");

    var start_date = <?php echo json_encode($start_date); ?>;
    var end_date = <?php echo json_encode($end_date); ?>;
    var warehouse_id = <?php echo json_encode($warehouse_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
    $('.selectpicker').selectpicker('refresh');

    $('#sale-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"warehouse-sale-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.warehouse_id = warehouse_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "customer"},
            {"data": "product"},
            {"data": "grand_total"},
            {"data": "paid"},
            {"data": "due"},
            {"data": "status"}
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
                'targets': [0, 3, 4, 5, 6, 7, 8]
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
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
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
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
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

    function datatable_sum_sale(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
    }

    $('#purchase-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"warehouse-purchase-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.warehouse_id = warehouse_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "supplier"},
            {"data": "product"},
            {"data": "grand_total"},
            {"data": "paid"},
            {"data": "balance"},
            {"data": "status"}
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
                'targets': [0, 3, 4, 5, 6, 7, 8]
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
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
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
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
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

    function datatable_sum_sale(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
    }

    $('#quotation-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"warehouse-quotation-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.warehouse_id = warehouse_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "customer"},
            {"data": "supplier"},
            {"data": "product"},
            {"data": "grand_total"},
            {"data": "status"}
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
                    columns: ':visible:Not(.not-exported-quotation)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_quotation(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_quotation(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-quotation)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_quotation(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_quotation(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-quotation)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_quotation(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_quotation(dt, false);
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
            datatable_sum_quotation(api, false);
        }
    });

    function datatable_sum_quotation(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
        }
    }

    $('#return-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"warehouse-return-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.warehouse_id = warehouse_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "customer"},
            {"data": "biller"},
            {"data": "product"},
            {"data": "grand_total"}
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
                'targets': [0, 3, 4, 5]
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
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_return(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_return(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_return(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_return(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_return(dt, false);
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
            datatable_sum_return(api, false);
        }
    });

    function datatable_sum_return(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
        }
    }

    $('#expense-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"warehouse-expense-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.warehouse_id = warehouse_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "category"},
            {"data": "amount"},
            {"data": "note"}
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
                'targets': [0, 5]
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
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_expense(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_expense(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_expense(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_expense(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-return)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_expense(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_expense(dt, false);
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
            datatable_sum_expense(api, false);
        }
    });

    function datatable_sum_expense(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.column( 4, {page:'current'} ).data().sum()));
        }
    }

    function reloadWarehouseReportData() {
        start_date = $('input[name="start_date"]').val();
        end_date = $('input[name="end_date"]').val();
        warehouse_id = $('select[name="warehouse_id"]').val();
        
        $('#sale-table').DataTable().ajax.reload();
        $('#purchase-table').DataTable().ajax.reload();
        $('#quotation-table').DataTable().ajax.reload();
        $('#return-table').DataTable().ajax.reload();
        $('#expense-table').DataTable().ajax.reload();
    }

    // Auto submit when warehouse changes
    $('#warehouse_id').on('changed.bs.select', function () {
        reloadWarehouseReportData();
    });

    // Auto submit when date range changes
    $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        let form = $(this).closest('form');

        // update hidden inputs
        form.find('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
        form.find('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));

        reloadWarehouseReportData();
    });

</script>
@endpush
