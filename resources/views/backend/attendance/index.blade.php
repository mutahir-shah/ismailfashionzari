@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@section('content')

@push('css')
    @include('backend.layout.partials.datatable_css')

    <link rel="preload" href="{{ asset($asset_prefix . 'vendor/jquery-timepicker/jquery.timepicker.min.css') }}" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="{{ asset($asset_prefix . 'vendor/jquery-timepicker/jquery.timepicker.min.css') }}" rel="stylesheet">
    </noscript>
@endpush

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid mt-4">

        <!-- Buttons -->
        <div class="mb-3 d-flex">
            <button class="btn btn-info mr-2" data-toggle="modal" data-target="#createModal">
                <i class="ti ti-plus"></i> {{ __('db.Add Attendance') }}
            </button>

            <button class="btn btn-secondary mr-2" type="button" data-toggle="collapse" data-target="#importCsv"
                aria-expanded="false" aria-controls="importCsv">
                <i class="ti ti-upload"></i> {{ __('db.Import CSV') }}
            </button>

            <!-- Filter Button -->
            <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#filterSection"
                aria-expanded="false" aria-controls="filterSection">
                <i class="ti ti-filter"></i> {{ __('db.Filter') }}
            </button>
        </div>

        <!-- Filter Collapsible -->
        <div class="collapse mb-4" id="filterSection">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <form id="attendanceFilterForm">
                        <div class="row">

                            <!-- Date -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.date') }}</label>
                                <input type="date" name="date" class="form-control" id="filterDate">
                            </div>

                            <!-- Employee -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Employee') }}</label>
                                <select name="employee_id" class="form-control selectpicker" id="filterEmployee" data-live-search="true" title="Select Employee...">
                                    @foreach($lims_employee_list as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Status') }}</label>
                                <select name="status" class="form-control" id="filterStatus">
                                    <option value="">All</option>
                                    <option value="Present">{{ __('db.Present') }}</option>
                                    <option value="Absent">{{ __('db.absent') }}</option>
                                    <option value="Leave">{{ __('db.leave') }}</option>
                                </select>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse" class="form-control selectpicker" id="filterWarehouse" data-live-search="true" title="Select Warehouse...">
                                    <option value="">All</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import CSV Collapsible -->
        <div class="collapse mb-4" id="importCsv">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <form action="{{ route('attendances.importDeviceCsv') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>{{ __('db.Attendance Device Date Format') }}</label>
                                <select name="Attendance_Device_date_format" class="form-control">
                                    <option value="">Select</option>
                                    <option value="d/m/Y">dd/mm/yyyy(23/05/2022)</option>
                                    <option value="m/d/Y">mm/dd/yyyy(05/23/2022)</option>
                                    <option value="Y/m/d">yyyy/mm/dd(2022/05/23)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ __('db.Upload File') }}</label>
                                <input type="file" class="form-control-file" name="file" accept=".xlsx, .xls, .csv">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="ti ti-check-square-o"></i> {{ __('db.Save') }}
                                </button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </div>
                        <small class="text-muted">
                            * CSV file date format must match selected format.<br>
                            * Do not change first line or column order.<br>
                            * Max file size 2MB.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="attendance-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.date') }}</th>
                    <th>{{ __('db.Employee') }}</th>
                    <th>{{ __('db.CheckIn') }} - {{ __('db.CheckOut') }}</th>
                    <th>{{ __('db.status') }}</th>
                    <th>{{ __('db.Created By') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lims_attendance_all as $key => $attendance)
                <tr data-date="{{ $attendance['date'] }}"
                    data-employee_id="{{ $attendance['employee_id'] }}"
                    data-warehouse="{{ $attendance['warehouse_id'] ?? '' }}">
                    <td>{{ $key }}</td>
                    <td>{{ date(gen_setting()->date_format, strtotime($attendance['date'])) }}</td>
                    <td>{{ $attendance['employee_name'] }}</td>
                    <td>{!! $attendance['checkin_checkout'] !!}</td>
                    <td>
                        @if ($attendance['status'])
                            <span class="badge badge-success">{{ __('db.Present') }}</span>
                        @else
                            <span class="badge badge-danger">{{ __('db.Late') }}</span>
                        @endif
                    </td>
                    <td>{{ $attendance['user_name'] }}</td>
                    <td>
                        <div class="btn-group">
                            <form action="{{ route('attendances.delete', [$attendance['date'], $attendance['employee_id']]) }}" method="post">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirmDelete()">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Attendance')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <form action="{{ route('attendance.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Employee')}} *</label>
                        <select class="form-control selectpicker" name="employee_id[]" required data-live-search="true" data-live-search-style="begins" title="Select Employee..." multiple>
                            @foreach($lims_employee_list as $employee)
                            <option value="{{$employee->id}}">{{$employee->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.date')}} *</label>
                        <input type="text" name="date" class="form-control date" value="{{date(gen_setting()->date_format)}}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.CheckIn')}} *</label>
                        <input type="text" id="checkin" name="checkin" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->checkin}}@endif" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.CheckOut')}} *</label>
                        <input type="text" id="checkout" name="checkout" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->checkout}}@endif" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{__('db.note')}}</label>
                        <textarea name="note" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/jquery/jquery.timepicker.min.js') }}"></script>
    <script type="text/javascript">

        $("ul#hrm").siblings('a').attr('aria-expanded','true');
        $("ul#hrm").addClass("show");
        $("ul#hrm #attendance-menu").addClass("active");

        function confirmDelete() {
            return confirm("Are you sure want to delete?");
        }

        var date = $('.date');
        date.datepicker({
        format: "dd-mm-yyyy",
        autoclose: true,
        todayHighlight: true
        });

        $('#checkin, #checkout').timepicker({
            'step': 15,
        });

        var table = $('#attendance-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{__("db.records per page")}}',
                "info": '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{__("db.Search")}}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 6]
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
                        rows: ':visible',
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                    },
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                    },
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                    },
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function ( e, dt, node, config ) {
                        var attendance_selected = [];
                        var user_verified = <?php echo json_encode(config('app.user_verified'))?>;
                        if(user_verified == '1') {
                            var rows_selected = dt.column(0).checkboxes.selected();
                            $.each(rows_selected, function(index, rowId){
                                var row_single = dt.row( rowId ).nodes()[0];
                                attendance_selected[index] = [$(row_single).data('date'),
                                                        $(row_single).data('employee_id')];
                            });

                            if(attendance_selected.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type:'POST',
                                    url:'attendance/deletebyselection',
                                    data:{ attendanceSelectedArray: attendance_selected },
                                    success:function(data){
                                        alert(data);
                                        dt.rows(rows_selected).remove().draw();
                                    }
                                });
                            } else if(!attendance_selected.length)
                                alert('Nothing is selected!');
                        } else {
                            alert('This feature is disable for demo!');
                        }
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        });

        // Attendance Filter
        $('#filterDate, #filterEmployee, #filterStatus, #filterWarehouse').on('change', function() {
            var date = $('#filterDate').val();
            var employee_id = $('#filterEmployee').val();
            var status = $('#filterStatus').val();
            var warehouse = $('#filterWarehouse').val();

            table.rows().every(function(){
                var data = this.node();
                var show = true;

                if(date) show = show && ($(data).data('date') === date);
                if(employee_id && employee_id.length > 0) show = show && (employee_id.includes($(data).data('employee_id').toString()));

                if(status) {
                    var statusText = $(data).find('td:eq(4)').text().trim();
                    show = show && ((status === 'Present' && statusText === '{{ __("db.Present") }}') ||
                                    (status === 'Absent' && statusText === '{{ __("db.absent") }}') ||
                                    (status === 'Leave' && statusText === '{{ __("db.leave") }}'));
                }

                if(warehouse) show = show && ($(data).data('warehouse').toString() === warehouse);

                if(show) $(data).show();
                else $(data).hide();
            });
        });

    </script>
@endpush
