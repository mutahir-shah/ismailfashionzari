@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Biller Report')}}</h3>
            </div>
            <form action="{{ route('report.biller') }}" method="post">
                @csrf
            <div class="row mb-3">
                <div class="col-md-4 offset-md-2 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{__('db.Choose Your Date')}}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <div class="input-group">
                                <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                <input type="hidden" name="start_date" value="{{$start_date}}" />
                                <input type="hidden" name="end_date" value="{{$end_date}}" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{__('db.Choose Biller')}}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <input type="hidden" name="biller_id_hidden" value="{{$biller_id}}" />
                            <select id="biller_id" name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                @foreach($lims_biller_list as $biller)
                                <option value="{{$biller->id}}">{{$biller->name}} ({{$biller->phone_number}})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="biller_id_hidden" value="{{$biller_id}}" />
            </form>
        </div>
    </div>
    <ul class="nav nav-tabs ml-4 mt-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#biller-sale" role="tab" data-toggle="tab">{{__('db.Sale')}}</a>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" href="#biller-purchase" role="tab" data-toggle="tab">{{__('db.Purchase')}}</a>
        </li> -->
        <li class="nav-item">
            <a class="nav-link" href="#biller-quotation" role="tab" data-toggle="tab">{{__('db.quotation')}}</a>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" href="#biller-transfer" role="tab" data-toggle="tab">{{__('db.Transfer')}}</a>
        </li> -->
        <li class="nav-item">
            <a class="nav-link" href="#biller-payments" role="tab" data-toggle="tab">{{__('db.Payment')}}</a>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" href="#biller-expense" role="tab" data-toggle="tab">{{__('db.Expense')}}</a>
        </li> -->
        <!-- <li class="nav-item">
            <a class="nav-link" href="#biller-payroll" role="tab" data-toggle="tab">{{__('db.Payroll')}}</a>
        </li> -->
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="biller-sale">
            <div class="table-responsive mb-4">
                <table id="sale-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-sale"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
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

        <div role="tabpanel" class="tab-pane fade" id="biller-purchase">
            <div class="table-responsive mb-4">
                <table id="purchase-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-purchase"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Supplier')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
                            <th>{{__('db.product')}} ({{__('db.qty')}})</th>
                            <th>{{__('db.grand total')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid Amount')}}</th>
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

        <div role="tabpanel" class="tab-pane fade" id="biller-quotation">
            <div class="table-responsive mb-4">
                <table id="quotation-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-quotation"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.customer')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
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

        <div role="tabpanel" class="tab-pane fade" id="biller-transfer">
            <div class="table-responsive mb-4">
                <table id="transfer-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-transfer"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.From')}}</th>
                            <th>{{__('db.To')}}</th>
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

        <div role="tabpanel" class="tab-pane fade" id="biller-payments">
            <div class="table-responsive mb-4">
                <table id="payment-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-payment"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Paid Method')}}</th>
                        </tr>
                    </thead>

                    <tfoot class="tfoot active">
                        <tr>
                            <th></th>
                            <th>{{__('db.Total')}}</th>
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="biller-expense">
            <div class="table-responsive mb-4">
                <table id="expense-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-expense"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Warehouse')}}</th>
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
                            <th></th>
                            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div role="tabpanel" class="tab-pane fade" id="biller-payroll">
            <div class="table-responsive mb-4">
                <table id="payroll-table" class="table table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="not-exported-payroll"></th>
                            <th>{{__('db.date')}}</th>
                            <th>{{__('db.reference')}}</th>
                            <th>{{__('db.Employee')}}</th>
                            <th>{{__('db.Amount')}} ({{ config('currency') }})</th>
                            <th>{{__('db.Method')}}</th>
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
    $("ul#report #biller-report-menu").addClass("active");

    var start_date = <?php echo json_encode($start_date); ?>;
    var end_date = <?php echo json_encode($end_date); ?>;
    var biller_id = <?php echo json_encode($biller_id); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#biller_id').val($('input[name="biller_id_hidden"]').val());
    $('.selectpicker').selectpicker('refresh');

    $('#sale-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"biller-sale-data",
            data:function (d) {
                let date_range = $('.daterangepicker-field').val().split(' To ');
                d.start_date = date_range[0];
                d.end_date = date_range[1];
                d.biller_id = $('#biller_id').val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "customer"},
            {"data": "warehouse"},
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

            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
    }

    function datatable_sum_sale(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.column( 8, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
    }

    $('#quotation-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"biller-quotation-data",
            data:function (d) {
                let date_range = $('.daterangepicker-field').val().split(' To ');
                d.start_date = date_range[0];
                d.end_date = date_range[1];
                d.biller_id = $('#biller_id').val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "customer"},
            {"data": "warehouse"},
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

    function datatable_sum_transfer(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
        }
        else {

            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
        }
    }

    $('#payment-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"biller-payment-data",
            data:function (d) {
                let date_range = $('.daterangepicker-field').val().split(' To ');
                d.start_date = date_range[0];
                d.end_date = date_range[1];
                d.biller_id = $('#biller_id').val();
            },
            dataType: "json",
            type:"post"
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
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

            $( dt_selector.column( 3 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 3, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 3 ).footer() ).html(formatCurrency(dt_selector.column( 3, {page:'current'} ).data().sum()));
        }
    }


    function datatable_sum_expense(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
        }
    }


    function datatable_sum_payroll(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.column( 4, {page:'current'} ).data().sum()));
        }
    }

    function applyFilter() {
        let date_range = $('.daterangepicker-field').val().split(' To ');

        let start_date = date_range[0];
        let end_date = date_range[1];

        $('input[name="start_date"]').val(start_date);
        $('input[name="end_date"]').val(end_date);

        $('#sale-table').DataTable().ajax.reload();
        $('#quotation-table').DataTable().ajax.reload();
        $('#payment-table').DataTable().ajax.reload();
    }

    $('#biller_id').on('change', function () {
        applyFilter();
    });

    $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {

        let start_date = picker.startDate.format('YYYY-MM-DD');
        let end_date = picker.endDate.format('YYYY-MM-DD');

        // 🔥 THIS LINE WAS MISSING
        $(this).val(start_date + ' To ' + end_date);

        $('input[name="start_date"]').val(start_date);
        $('input[name="end_date"]').val(end_date);

        applyFilter();
    });

</script>
@endpush
