@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-success-message key="message" />

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Challan Report</h3>
            </div>
            <form action="{{route('report.challan')}}" method="get">
                <div class="row mb-3">
                    <div class="col-md-3 offset-md-1  mt-3">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>Based On</strong> &nbsp;</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <select name="based_on" class="form-control">
                                        @if($based_on == 'created_at')
                                            <option selected value="created_at">Created Date</option>
                                            <option value="closing_date">Closing Date</option>
                                        @else
                                            <option value="created_at">Created Date</option>
                                            <option selected value="closing_date">Closing Date</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{__('db.Choose Your Date')}}</label>
                            <input type="text" class="daterangepicker-field form-control" value="{{$starting_date}} To {{$ending_date}}" required />
                            <input type="hidden" name="starting_date" value="{{$starting_date}}" />
                            <input type="hidden" name="ending_date" value="{{$ending_date}}" />
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive" id="report-table">
        @include('backend.report.partials.challan_table')
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')

    <script type="text/javascript">

        $("ul#report").siblings('a').attr('aria-expanded','true');
        $("ul#report").addClass("show");
        $("ul#report #challan-report-menu").addClass("active");

        function initializeChallanTable() {
            var table = $('#challan-table').DataTable( {
                "destroy": true,
                "order": [],
                'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0]
                    },
                    {
                        'checkboxes': {
                           'selectRow': true
                        },
                        'targets': 0
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
                        text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
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
                            columns: ':visible:not(.not-exported)',
                            rows: ':visible',
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
                        columns: ':gt(0)'
                    },
                ],
                drawCallback: function () {
                    var api = this.api();
                    datatable_sum(api, false);
                }
            });
        }

        initializeChallanTable();

        function reloadChallanTable() {
            var starting_date = $('input[name="starting_date"]').val();
            var ending_date = $('input[name="ending_date"]').val();
            var based_on = $('select[name="based_on"]').val();

            $.ajax({
                url: "{{ route('report.challan') }}",
                data: {
                    starting_date: starting_date,
                    ending_date: ending_date,
                    based_on: based_on
                },
                method: 'GET',
                beforeSend: function () {
                    $('#report-table').html('<div class="text-center"><i class="ti ti-spin fa-spinner"></i> Loading...</div>');
                },
                success: function (response) {
                    $('#report-table').html(response);
                    initializeChallanTable();
                }
            });
        }

        // auto reload on dropdown change
        $('select[name="based_on"]').on('change', function () {
            reloadChallanTable();
        });

        // auto reload on date change
        $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
            $('input[name="starting_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="ending_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            reloadChallanTable();
        });

        function datatable_sum(dt_selector, is_calling_first) {
            if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
                var rows = dt_selector.rows( '.selected' ).indexes();
                $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 12 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 12, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 13 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 13, { page: 'current' } ).data().sum()));
            }
            else {
                $( dt_selector.column( 6 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 7 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 8 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 9 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 10 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 11 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 12 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 12, { page: 'current' } ).data().sum()));
                $( dt_selector.column( 13 ).footer() ).html(formatCurrency(dt_selector.cells( rows, 13, { page: 'current' } ).data().sum()));
            }
        }

    </script>
@endpush
