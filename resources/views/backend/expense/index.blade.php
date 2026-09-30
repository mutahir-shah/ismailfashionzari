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
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        
        @if (in_array('expenses-add', $all_permission))
            <button class="btn btn-info" data-toggle="modal" data-target="#expense-modal"><i class="ti ti-plus"></i>
                {{ __('db.Add Expense') }}</button>
        @endif
        <button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
            <i class="ti ti-filter"></i> {{ __('db.Filter') }}
        </button>
        <div class="card">
            <div class="card-body" id="filter-card" style="display: none;">
                <form action="{{ route('expenses.index') }}" method="get">
                    <div class="row mb-3">
                        <div class="col-md-3 offset-md-2 mt-3">
                            <div class="form-group  top-fields">
                                <label>{{ __('db.Choose Your Date') }}</label>
                                <input type="text" class="daterangepicker-field form-control"
                                    value="{{ $starting_date }} To {{ $ending_date }}" required />
                                <input type="hidden" name="starting_date" value="{{ $starting_date }}" />
                                <input type="hidden" name="ending_date" value="{{ $ending_date }}" />
                            </div>
                        </div>
                        <div class="col-md-3 mt-3 @if (\Auth::user()->role_id > 2) {{ 'd-none' }} @endif">
                            <div class="form-group top-fields">
                                <label>{{ __('db.Choose Warehouse') }}</label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control"
                                    data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        @if ($warehouse->id == $warehouse_id)
                                            <option selected value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @else
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mt-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                            <div class="form-group  top-fields">
                                <label>{{__('db.Expense Category')}}</label>
                                <select id="expense_category_id" name="expense_category_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                    <option value="0">{{__('db.all_categories')}}</option>
                                    @foreach($expense_category_list as $expense_category)
                                        @if($expense_category->id == $expense_category_id)
                                            <option selected value="{{$expense_category->id}}">{{$expense_category->name}}</option>
                                        @else
                                            <option value="{{$expense_category->id}}">{{$expense_category->name}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                            <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                            <span>Loading results...</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table id="expense-table" class="table expense-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.date') }}</th>
                    <th>{{ __('db.reference') }} No</th>
                    <th>{{ __('db.Warehouse') }}</th>
                    <th>{{ __('db.category') }}</th>
                    <th>{{ __('db.Amount') }}</th>
                    <th>{{ __('db.note') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{ __('db.Total') }}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
    class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Update Expense') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                        aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic">
                    <small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                <form action="{{ route('expenses.update', 1) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                <?php
                $lims_expense_category_list = DB::table('expense_categories')->where('is_active', true)->get();
                if (Auth::user()->role_id > 2) {
                    $lims_warehouse_list = DB::table('warehouses')
                        ->where([['is_active', true], ['id', Auth::user()->warehouse_id]])
                        ->get();
                } else {
                    $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
                }
                ?>
                <div class="form-group">
                    <input type="hidden" name="expense_id">
                    <label>{{ __('db.reference') }}</label>
                    <p id="reference">{{ 'er-' . date('Ymd') . '-' . date('his') }}</p>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{ __('db.date') }}</label>
                        <input type="text" name="created_at" class="form-control date"
                            placeholder="{{ __('Choose date') }}" />
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('db.Expense Category') }} *</label>
                        <select name="expense_category_id" class="selectpicker form-control" required
                            data-live-search="true" data-live-search-style="begins" title="Select Expense Category...">
                            @foreach ($lims_expense_category_list as $expense_category)
                                <option value="{{ $expense_category->id }}">{{ $expense_category->name }} ({{ $expense_category->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('db.Warehouse') }} *</label>
                        <select name="warehouse_id" class="selectpicker form-control" required data-live-search="true"
                            data-live-search-style="begins" title="Select Warehouse...">
                            @foreach ($lims_warehouse_list as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('db.Amount') }} *</label>
                        <input type="number" name="amount" step="any" required class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('Tax') }} ({{ __('db.Inclusive') }})</label>
                        <select name="tax_id" class="selectpicker form-control" data-live-search="true" title="Select Tax...">
                            <option value="">{{ __('No Tax') }}</option>
                            @foreach ($lims_tax_list as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Employee + Type (Hidden by default) -->
                    <div id="edit_employee_fields" style="display:none; width:100%;" class="col-md-12">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Employee') }} *</label>
                                <select name="employee_id" id="edit_employee_id" class="selectpicker form-control"
                                    data-live-search="true">
                                    @foreach (\App\Models\Employee::where('is_active', 1)->get() as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Type') }} *</label>
                                <select name="type" id="edit_type" class="form-control">
                                    <option value="expense">{{ __('db.Expense') }}</option>
                                    <option value="advance">{{ __('db.advance') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{ __('db.employee_advance_payment_account') }}</label>
                        <select class="form-control selectpicker" name="account_id">
                            @foreach ($lims_account_list as $account)
                                @if ($account->is_default)
                                    <option selected value="{{ $account->id }}">{{ $account->name }}
                                        [{{ $account->account_no }}]</option>
                                @else
                                    <option value="{{ $account->id }}">{{ $account->name }}
                                        [{{ $account->account_no }}]</option>
                                @endif
                            @endforeach
                        </select>
                    </div>




                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('db.Attach Document') }}</label>
                            <i class="ti ti-info-circle" data-toggle="tooltip"
                                title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                            <input type="file" name="document" class="form-control" />
                            @if ($errors->has('extension'))
                                <span>
                                    <strong>{{ $errors->first('extension') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                </div>
                <div class="form-group">
                    <label>{{ __('db.note') }}</label>
                    <textarea name="note" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $('#toggle-filter').on('click', function() {
        $('#filter-card').slideToggle('slow');
    });

    var expense_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified')); ?>;
    var all_permission = <?php echo json_encode($all_permission); ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Edit button click
    $(document).on('click', 'button.open-Editexpense_categoryDialog', function() {
        var url = "expenses/";
        var id = $(this).data('id').toString();
        url = url.concat(id).concat("/edit");

        $.get(url, function(data) {

            // Basic fields
            $('#editModal #reference').text(data['reference_no']);
            $("#editModal input[name='created_at']").val(data['date']);
            $("#editModal select[name='warehouse_id']").val(data['warehouse_id']);
            $("#editModal select[name='expense_category_id']").val(data['expense_category_id']);
            $("#editModal select[name='account_id']").val(data['account_id']);
            $("#editModal input[name='amount']").val(data['amount']);
            $("#editModal select[name='tax_id']").val(data['tax_id'] || '');
            $("#editModal input[name='expense_id']").val(data['id']);
            $("#editModal textarea[name='note']").val(data['note']);

            // Employee Expense Logic
            if (data['expense_category_id'] == 0) {
                $("#edit_employee_fields").show();
                $("#edit_employee_id").val(data['employee_id']);
                $("#edit_type").val(data['type']);
            } else {
                $("#edit_employee_fields").hide();
            }

            $('.selectpicker').selectpicker('refresh');
        });
    });

    // Category change event inside modal
    $(document).on("change", "#editModal select[name='expense_category_id']", function() {
        if ($(this).val() == 0) {
            $("#edit_employee_fields").show();
        } else {
            $("#edit_employee_fields").hide();
        }
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    var expenseTable = $('#expense-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "expenses/expense-data",
            type: "post",
            data: function (d) {
                d.all_permission = all_permission;
                d.starting_date  = $("input[name=starting_date]").val();
                d.ending_date    = $("input[name=ending_date]").val();
                d.warehouse_id   = $("#warehouse_id").val();
            },
            dataType: "json"
        },
        createdRow: function(row, data, dataIndex) {
            $(row).attr('data-expense_id', data['id']);
        },
        columns: [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "expenseCategory"},
            {"data": "amount"},
            {"data": "note"},
            {"data": "options"}
        ],
        'language': {

            'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
            "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{ __('db.Search') }}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        order: [
            ['1', 'desc']
        ],
        'columnDefs': [{
                "orderable": false,
                'targets': [0, 3, 4, 6, 7]
            },
            {
                'render': function(data, type, row, meta) {
                    if (type === 'display') {
                        data =
                            '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
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
        'select': {
            style: 'multi',
            selector: 'td:first-child'
        },
        'lengthMenu': [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [{
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
                footer: true
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
                footer: true
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
                footer: true
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
                footer: true
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function(e, dt, node, config) {
                    if (user_verified == '1') {
                        expense_id.length = 0;
                        $(':checkbox:checked').each(function(i) {
                            if (i) {
                                expense_id[i - 1] = $(this).closest('tr').data('expense_id');
                            }
                        });
                        if (expense_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type: 'POST',
                                url: 'expenses/deletebyselection',
                                data: {
                                    expenseIdArray: expense_id
                                },
                                success: function(data) {
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({
                                        page: 'current',
                                        selected: true
                                    }).remove().draw(false);
                                }
                            });
                        } else if (!expense_id.length)
                            alert('Nothing is selected!');
                    } else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
        drawCallback: function() {
            var api = this.api();
            datatable_sum(api, false);
        }
    });

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows('.selected').any() && is_calling_first) {
            var rows = dt_selector.rows('.selected').indexes();
            $(dt_selector.column(5).footer()).html(dt_selector.cells(rows, 5, {
                page: 'current'
            }).data().sum().toFixed({{ gen_setting()->decimal }}));
        } else {
            $(dt_selector.column(5).footer()).html(dt_selector.cells(rows, 5, {
                page: 'current'
            }).data().sum().toFixed({{ gen_setting()->decimal }}));
        }
    }

    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {
        $('input[name=starting_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(picker.endDate.format('YYYY-MM-DD'));
        saleTable.ajax.reload();
    });

    $('#warehouse_id, #expense_category_id').on('change', function () {
        expenseTable.ajax.reload();
    });

    // Show loader on request
    expenseTable.on('preXhr.dt', function () {
        $('#filter-loading').show();
    });

    // Hide loader after draw
    expenseTable.on('xhr.dt', function () {
        $('#filter-loading').hide();
    });

    if (all_permission.indexOf("expenses-delete") == -1)
        $('.buttons-delete').addClass('d-none');
</script>
@endpush
