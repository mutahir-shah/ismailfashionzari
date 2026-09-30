@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Customer Group Report')}}</h3>
            </div>
            <form action="{{ route('report.customer_group') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4 offset-md-2 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Your Date')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                                    <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                                    <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{__('db.Customer Group')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <select id="customer_group_id" name="customer_group_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    @foreach($lims_customer_group_list as $customer_group)
                                    <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
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
            <a class="nav-link active" href="#customer-group-sale" role="tab" data-toggle="tab">{{__('db.Sale')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#customer-group-payment" role="tab" data-toggle="tab">{{__('db.Payment')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#customer-group-quotation" role="tab" data-toggle="tab">{{__('db.quotation')}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#customer-group-return" role="tab" data-toggle="tab">{{__('db.return')}}</a>
        </li>
    </ul>
    <div class="tab-content">
        <!-- sale table -->
        <div role="tabpanel" class="tab-pane fade show active" id="customer-group-sale">
            <div class="table-responsive mb-4">
                <table id="sale-table" class="table table-hover" style="width: 100%">
                    <thead> 
                        <tr>
                            <th class="not-exported-sale"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}} No</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Returned Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Due')}} ({{ config('currency') }})</th>
                            <th>{{__('db.status')}}</th>
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
                        <th></th>
                        <th></th>
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- end sale table -->
        <!-- payment table -->
        <div role="tabpanel" class="tab-pane fade" id="customer-group-payment">
            <div class="table-responsive mb-4">
                <table id="payment-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-payment"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Payment Reference')}}</th>
                            <th>{{__('db.Sale Reference')}}</th>
                            <th>{{__('db.Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid Method')}}</th>
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
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- end payment table -->
        <!-- quotation table -->
        <div role="tabpanel" class="tab-pane fade" id="customer-group-quotation">
            <div class="table-responsive mb-4">
                <table id="quotation-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-quotation"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}} No</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.status')}}</th>
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
                        <th></th>
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- end quotation table -->
        <!-- return table -->
        <div role="tabpanel" class="tab-pane fade" id="customer-group-return">
            <div class="table-responsive mb-4">
                <table id="return-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-return"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}} No</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
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
                    </tfoot>
                </table>
            </div>
        </div>
        <!-- end return table -->
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #customer-group-report-menu").addClass("active");

    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date = <?php echo json_encode($ending_date); ?>;
    var customer_group_id = <?php echo json_encode($customer_group_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#customer_group_id").val(customer_group_id);
    $('.selectpicker').selectpicker('refresh');

    //retreiving sale table data
    $('#sale-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"customer-group-sale-data",
            data: function(d){
                d.starting_date = $('input[name="starting_date"]').val();
                d.ending_date = $('input[name="ending_date"]').val();
                d.customer_group_id = $('#customer_group_id').val();
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
            {"data": "returned_amount"},
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
                'targets': [0, 3, 4, 5, 6, 7, 8, 9, 10]
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

            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.column( 7, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.column( 9, { page: 'current' } ).data().sum()));
        }
    }

    //retreiving payment table data
    $('#payment-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"customer-group-payment-data",
            data: function(d){
                d.starting_date = $('input[name="starting_date"]').val();
                d.ending_date = $('input[name="ending_date"]').val();
                d.customer_group_id = $('#customer_group_id').val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "customer"},
            {"data": "reference_no"},
            {"data": "sale_reference"},
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
                'targets': [0, 2, 3, 4, 5, 6]
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

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
        }
    }

    //retreiving quotation table data
    $('#quotation-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"customer-group-quotation-data",
            data: function(d){
                d.starting_date = $('input[name="starting_date"]').val();
                d.ending_date = $('input[name="ending_date"]').val();
                d.customer_group_id = $('#customer_group_id').val();
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

            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
    }

    //retreiving return table data
    $('#return-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"customer-group-return-data",
            data: function(d){
                d.starting_date = $('input[name="starting_date"]').val();
                d.ending_date = $('input[name="ending_date"]').val();
                d.customer_group_id = $('#customer_group_id').val();
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
                'targets': [0, 3, 4, 5, 6]
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
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
        }
    }

    function reloadTables() {
        $('#sale-table').DataTable().ajax.reload();
        $('#payment-table').DataTable().ajax.reload();
        $('#quotation-table').DataTable().ajax.reload();
        $('#return-table').DataTable().ajax.reload();
    }

    // Trigger reload when customer group changes
    $('#customer_group_id').on('changed.bs.select', function () {
        reloadTables();
    });

    // Trigger reload when date changes
    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        // Update hidden inputs
        $('input[name="starting_date"]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name="ending_date"]').val(picker.endDate.format('YYYY-MM-DD'));

        reloadTables();
    });
    
</script>
@endpush
