@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush


@section('content')

@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('message') }}
    </div>
@endif
@if(session()->has('not_permitted'))
    <div class="alert alert-danger alert-dismissible text-center">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('not_permitted') }}
    </div>
@endif

<section>
    <div class="container-fluid">

        {{-- ── Action Buttons ── --}}
        <a href="{{ route('repair.service.create') }}" class="btn btn-info btn-icon">
            <i class="ti ti-plus"></i> {{ __('db.add_service_job') }}
        </a>&nbsp;
        <button class="btn btn-warning btn-icon" type="button" id="toggle-filter">
            <i class="ti ti-filter"></i> {{ __('db.filter_service_jobs') }}
        </button>

        {{-- ── Filter Card ── --}}
        <div class="card mt-3 mb-2">
            <div class="card-body" id="filter-card" style="display: none;">
                <div class="row mt-2">
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{ __('db.date') }}</label>
                            <input type="text" id="daterange-display" class="daterangepicker-field form-control"
                                value="{{ $starting_date }} To {{ $ending_date }}" />
                            <input type="hidden" id="starting_date" value="{{ $starting_date }}" />
                            <input type="hidden" id="ending_date" value="{{ $ending_date }}" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{ __('db.Warehouse') }}</label>
                            <select id="filter_warehouse" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                <option value="0">{{ __('db.All Warehouse') }}</option>
                                @foreach($lims_warehouse_list as $wh)
                                    <option value="{{ $wh->id }}" {{ $warehouse_id == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{ __('db.service_type') }}</label>
                            <select id="filter_type" class="form-control">
                                <option value="">{{ __('db.all_types') }}</option>
                                <option value="device"  {{ $service_type == 'device'  ? 'selected' : '' }}>{{ __('db.device') }}</option>
                                <option value="vehicle" {{ $service_type == 'vehicle' ? 'selected' : '' }}>{{ __('db.vehicle') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group top-fields">
                            <label>{{ __('db.status') }}</label>
                            <select id="filter_status" class="form-control">
                                <option value="">{{ __('db.all_status') }}</option>
                                <option value="pending"     {{ $status == 'pending'     ? 'selected' : '' }}>{{ __('db.Pending') }}</option>
                                <option value="diagnosed"   {{ $status == 'diagnosed'   ? 'selected' : '' }}>{{ __('db.diagnosed') }}</option>
                                <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>{{ __('db.in_progress') }}</option>
                                <option value="completed"   {{ $status == 'completed'   ? 'selected' : '' }}>{{ __('db.Completed') }}</option>
                                <option value="delivered"   {{ $status == 'delivered'   ? 'selected' : '' }}>{{ __('db.Delivered') }}</option>
                                <option value="cancelled"   {{ $status == 'cancelled'   ? 'selected' : '' }}>{{ __('db.Cancel') }}</option>
                            </select>
                        </div>
                    </div>
                    <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                        <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                        <span>{{ __('db.loading_results') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── DataTable ── --}}
    <div class="table-responsive">
        <table id="service-table" class="table service-list mt-0" style="width:100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.date') }}</th>
                    <th>{{ __('db.reference') }}</th>
                    <th>{{ __('db.customer') }}</th>
                    <th>{{ __('db.Type') }}</th>
                    <th>{{ __('db.Title') }}</th>
                    <th>{{ __('db.status') }}</th>
                    <th>{{ __('db.priority') }}</th>
                    <th>{{ __('db.Warehouse') }}</th>
                    <th>{{ __('db.Total') }}</th>
                    <th>{{ __('db.Due') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{ __('db.Total') }}</th>
                <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
                <th></th><th></th><th></th>
            </tfoot>
        </table>
    </div>
</section>

{{-- ── Job Details Modal ── --}}
<div id="job-details-modal" tabindex="-1" role="dialog" aria-labelledby="jobModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button id="print-btn" type="button" class="btn btn-default btn-sm">
                            <i class="ti ti-printer"></i> {{ __('db.print') }}
                        </button>
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                </div>
            </div>
            <div id="job-modal-content" class="modal-body p-4" style="background:#fff;">
                <div class="text-center"><i class="ti ti-loader ti-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<style>
    .btn-icon i { margin-right: 5px; }
    .top-fields { margin-top: 10px; position: relative; }
    .top-fields label { font-size: 11px; font-weight: 600; margin-left: 10px; padding: 0 3px; position: absolute; top: -8px; z-index: 9; }
    .top-fields input { font-size: 13px; height: 45px; }
</style>

<script type="text/javascript">

    $("ul#repair").siblings('a').attr('aria-expanded', 'true');
    $("ul#repair").addClass("show");
    $("ul#repair #service-list-menu").addClass("active");

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var starting_date = <?php echo json_encode($starting_date); ?>;
    var ending_date   = <?php echo json_encode($ending_date); ?>;
    var warehouse_id  = <?php echo json_encode($warehouse_id); ?>;
    var status        = <?php echo json_encode($status); ?>;
    var service_type  = <?php echo json_encode($service_type); ?>;
    var currentJob    = null;

    $('#toggle-filter').on('click', function () {
        $('#filter-card').slideToggle('slow');
    });

    $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
        $('#starting_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#ending_date').val(picker.endDate.format('YYYY-MM-DD'));
        serviceTable.ajax.reload();
    });

    $('#filter_warehouse, #filter_type, #filter_status').on('change', function () {
        serviceTable.ajax.reload();
    });

    function confirmDelete() {
        return confirm('{{ __('db.delete_confirmation') }}');
    }

    var serviceTable = $('#service-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('repair.service.data') }}",
            type: 'POST',
            data: function (d) {
                d.starting_date = $('#starting_date').val();
                d.ending_date   = $('#ending_date').val();
                d.warehouse_id  = $('#filter_warehouse').val();
                d.status        = $('#filter_status').val();
                d.service_type  = $('#filter_type').val();
            }
        },
        createdRow: function (row, data) {
            $(row).addClass('service-link');
            $(row).attr('data-job', data['job_data']);
        },
        columns: [
            { data: 'key' },
            { data: 'date' },
            { data: 'reference_no' },
            { data: 'customer' },
            { data: 'service_type' },
            { data: 'title' },
            { data: 'status' },
            { data: 'priority' },
            { data: 'warehouse' },
            { data: 'total_amount' },
            { data: 'due_amount' },
            { data: 'options' },
        ],
        language: {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            'info': '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            'search': '{{__("db.Search")}}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, targets: [0, 4, 6, 7, 11] },
            {
                render: function (data, type, row, meta) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                checkboxes: {
                    selectRow: true,
                    selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                targets: [0]
            }
        ],
        select: { style: 'multi', selector: 'td:first-child' },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                footer: true
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
    });

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows('.selected').any() && is_calling_first) {
            var rows = dt_selector.rows('.selected').indexes();
            $(dt_selector.column(9).footer()).html(dt_selector.cells(rows, 9, { page: 'current' }).data().sum().toFixed({{ $general_setting->decimal }}));
            $(dt_selector.column(10).footer()).html(dt_selector.cells(rows, 10, { page: 'current' }).data().sum().toFixed({{ $general_setting->decimal }}));
        } else {
            $(dt_selector.column(9).footer()).html(dt_selector.column(9, { page: 'current' }).data().sum().toFixed({{ $general_setting->decimal }}));
            $(dt_selector.column(10).footer()).html(dt_selector.column(10, { page: 'current' }).data().sum().toFixed({{ $general_setting->decimal }}));
        }
    }

    serviceTable.on('preXhr.dt', function () { $('#filter-loading').show(); });
    serviceTable.on('xhr.dt',    function () { $('#filter-loading').hide(); });

    $(document).on('click', 'tr.service-link td:not(:first-child, :last-child)', function () {
        var job = JSON.parse($(this).parent().attr('data-job'));
        showJobDetails(job);
    });

    function showJobDetails(job) {
        currentJob = job;
        $('#job-modal-content').html('<div class="text-center"><i class="ti ti-loader ti-spin"></i> Loading...</div>');
        $('#job-details-modal').modal('show');
        
        $.get('{{ url("repair/service") }}/' + job.id + '/print-content', function (data) {
            $('#job-modal-content').html(data);
        }).fail(function() {
            $('#job-modal-content').html('<div class="text-danger text-center">Failed to load details.</div>');
        });
    }

    $('#print-btn').on('click', function () {
        var a = window.open('');
        a.document.write('<html><head>');
        a.document.write('</head><body>');
        a.document.write($('#job-modal-content').html());
        a.document.write('</body></html>');
        a.document.close();
        a.focus();
        setTimeout(function () { a.print(); a.close(); }, 500);
    });

</script>
@endpush
