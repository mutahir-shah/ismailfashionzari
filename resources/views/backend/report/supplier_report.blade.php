@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Supplier Report')}}</h3>
            </div>
            <form>
                <div class="row mb-3">
                    <div class="col-md-4 offset-md-1 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Your Date')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <input type="text" id="daterange" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Supplier')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <select id="supplier_id" name="supplier_id" class="selectpicker form-control" data-live-search="true">
                                    @foreach($lims_supplier_list as $supplier)
                                    <option value="{{$supplier->id}}">{{$supplier->name}} ({{$supplier->phone_number}})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <ul class="nav nav-tabs ml-4 mt-3" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" href="#supplier-purchase" role="tab" data-toggle="tab">{{__('db.Purchase')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#supplier-payments" role="tab" data-toggle="tab">{{__('db.Payment')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#supplier-return" role="tab" data-toggle="tab">{{__('db.return')}}</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#supplier-quotation" role="tab" data-toggle="tab">{{__('db.quotation')}}</a>
      </li>
    </ul>
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="supplier-purchase">
            <div class="table-responsive mb-4">
                <table id="purchase-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-purchase"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Returned Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Balance')}}</th>
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
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="supplier-payments">
            <div class="table-responsive mb-4">
                <table id="payment-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-payment"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.Payment Reference')}}</th>
                            <th>{{__('db.Purchase Reference')}}</th>
                            <th>{{__('db.Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid Method')}}</th>
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
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="supplier-return">
            <div class="table-responsive mb-4">
                <table id="return-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-return"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
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
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="supplier-quotation">
            <div class="table-responsive mb-4">
                <table id="quotation-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-quotation"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
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
    </div>
</section>



@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #supplier-report-menu").addClass("active");

    var start_date = <?php echo json_encode($start_date); ?>;
    var end_date = <?php echo json_encode($end_date); ?>;
    var supplier_id = <?php echo json_encode($supplier_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#supplier_id').val($('input[name="supplier_id_hidden"]').val());
    $('.selectpicker').selectpicker('refresh');

    var purchaseTable = $('#purchase-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"supplier-purchase-data",
            data: function(d) {
                d.start_date = start_date;
                d.end_date = end_date;
                d.supplier_id = supplier_id;
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "product"},
            {"data": "grand_total"},
            {"data": "returned_amount"},
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
                'targets': [0, 3, 4, 5, 6, 7, 8, 9]
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
                    columns: ':visible:Not(.not-exported-purchase)',
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
                    columns: ':visible:Not(.not-exported-purchase)',
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
        function currencyNumber(value) {
            return parseFloat(String(value || 0).replace(/,/g, '')) || 0;
        }

        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
        }
        else {
            var json = dt_selector.ajax && dt_selector.ajax.json ? dt_selector.ajax.json() : null;
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.column( 7, {page:'current'} ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(json && json.total_due !== undefined ? formatCurrency(currencyNumber(json.total_due)) : formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
        }
    }

    $('#payment-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"supplier-payment-data",
            data:{
                start_date: start_date,
                end_date: end_date,
                supplier_id: supplier_id
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "purchase_reference"},
            {"data": "amount"},
            {"data": "paying_method"}
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
                'targets': [0, 2, 3, 4]
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
                    columns: ':visible:Not(.not-exported-payment)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_payment(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_payment(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-payment)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_payment(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum_payment(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported-payment)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum_payment(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum_payment(dt, false);
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
            datatable_sum_payment(api, false);
        }
    });

    function datatable_sum_payment(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.column( 4, {page:'current'} ).data().sum()));
        }
    }

    $('#return-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"supplier-return-data",
            data:{
                start_date: start_date,
                end_date: end_date,
                supplier_id: supplier_id
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
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

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
        }
    }

    $('#quotation-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"supplier-quotation-data",
            data:{
                start_date: start_date,
                end_date: end_date,
                supplier_id: supplier_id
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

    // Supplier change
    $('#supplier_id').on('changed.bs.select', function() {
        supplier_id = $(this).val();
        reloadAllTables();
    });
    // Date range change
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        start_date = picker.startDate.format('YYYY-MM-DD');
        end_date = picker.endDate.format('YYYY-MM-DD');
        reloadAllTables();
    });
    // Reload function
    function reloadAllTables() {
        purchaseTable.ajax.reload();
        $('#payment-table').DataTable().ajax.reload();
        $('#return-table').DataTable().ajax.reload();
        $('#quotation-table').DataTable().ajax.reload();
    }

</script>
@endpush
