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
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">{{ __('db.Supplier Due Report') }}</h4>
            </div>
            <div class="card-body">
                <form id="filter-form" action="{{ route('report.supplierDueByDate') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <!-- Date Range -->
                        <div class="col-md-6">
                            <div class="form-group top-fields row daterangepicker-container">
                                <label class="d-tc">{{__('db.Choose Your Date')}}</label>
                                <div class="d-tc">
                                    <div class="input-group">
                                        <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                        <input type="hidden" name="start_date" value="{{$start_date}}" />
                                        <input type="hidden" name="end_date" value="{{$end_date}}" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Filter -->
                        <div class="col-md-6">
                            <div class="form-group top-fields row">
                                <label class="d-tc">{{ __('db.Supplier') }}</label>
                                <select id="supplier_id" name="supplier_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">{{ __('All Suppliers') }}</option>
                                    @foreach($lims_supplier_list as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $supplier_id == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }} ({{ $supplier->phone_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="table-container">
        @include('backend.report.partials.supplier_due_table')
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
@php
    $supplierDueLoadingText = __('db.Loading');
    if (is_array($supplierDueLoadingText)) {
        $supplierDueLoadingText = 'Loading';
    }
@endphp
<script type="text/javascript">

    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #supplier-due-report-menu").addClass("active");

    function initializeSupplierDueTable() {
        $('#report-table').DataTable( {
            "destroy": true,
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                 "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search":  '{{__("db.Search")}}',
                'paginate': {
                        'previous': '<i class="ti ti-chevron-left"></i>',
                        'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': 0
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
                    text: '<i title="export to excel" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
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
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
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
                        columns: ':visible:Not(.not-exported)',
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
                }
            ],
            drawCallback: function () {
                var api = this.api();
                datatable_sum(api, false);
            }
        } );
    }

    initializeSupplierDueTable();

    function reloadSupplierDueTable() {
        var formData = $('#filter-form').serialize();
        $.ajax({
            url: "{{ route('report.supplierDueByDate') }}",
            data: formData,
            method: 'POST',
            beforeSend: function () {
                $('#table-container').html('<div class="text-center mt-4"><i class="ti ti-spin fa-spinner"></i> ' + @json($supplierDueLoadingText) + '...</div>');
            },
            success: function (response) {
                $('#table-container').html(response);
                initializeSupplierDueTable();
            }
        });
    }

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(formatCurrency(dt_selector.column( 4, {page:'current'} ).data().sum()));
            $( dt_selector.column( 5 ).footer() ).html(formatCurrency(dt_selector.column( 5, {page:'current'} ).data().sum()));
            $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.column( 6, {page:'current'} ).data().sum()));
            $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.column( 7, {page:'current'} ).data().sum()));
        }
    }

    // Supplier select changes
    $('#supplier_id').on('changed.bs.select', function() {
        reloadSupplierDueTable();
    });

    // Date range changes
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
        reloadSupplierDueTable();
    });

</script>
@endpush
