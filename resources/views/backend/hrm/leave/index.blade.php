@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-error-message key="name" />
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        <div class="container-fluid mb-3">
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#createModal">
                <i class="ti ti-plus"></i> {{ __('db.Add Leave') }}
            </button>
        </div>

        <div class="table-responsive">
            <table id="leave-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.Employee') }}</th>
                        <th>{{ __('db.Leave Type') }}</th>
                        <th>{{ __('db.Start Date') }}</th>
                        <th>{{ __('db.End Date') }}</th>
                        <th>{{ __('db.Days') }}</th>
                        <th>{{ __('db.status') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leaves as $key => $leave)
                        <tr data-id="{{ $leave->id }}">
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $leave->employee->name ?? 'N/A' }}</td>
                            <td>{{ $leave->leaveType->name ?? 'N/A' }}</td>
                            <td>{{ $leave->start_date }}</td>
                            <td>{{ $leave->end_date }}</td>
                            <td>{{ $leave->days }}</td>
                            <td>
                                <form method="POST" action="{{ route('leave.update', $leave->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="Pending" {{ $leave->status == 'Pending' ? 'selected' : '' }}>Pending
                                        </option>
                                        <option value="Approved" {{ $leave->status == 'Approved' ? 'selected' : '' }}>
                                            Approved</option>
                                        <option value="Rejected" {{ $leave->status == 'Rejected' ? 'selected' : '' }}>
                                            Rejected</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('db.action') }}
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default"
                                        role="menu">
                                        <li>
                                            <button type="button" data-id="{{ $leave->id }}"
                                                data-employee="{{ $leave->employee_id }}"
                                                data-leave_types="{{ $leave->leave_types }}"
                                                data-start_date="{{ $leave->start_date }}"
                                                data-end_date="{{ $leave->end_date }}" class="edit-btn btn btn-link"
                                                data-toggle="modal" data-target="#editModal">
                                                <i class="ti ti-edit"></i> {{ __('db.Edit') }}
                                            </button>
                                        </li>
                                        <li class="divider"></li>
                                        <form action="{{ route('leave.destroy', $leave->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link text-danger"
                                                    onclick="return confirmDelete()">
                                                    <i class="ti ti-trash"></i> {{ __('db.Delete') }}
                                                </button>
                                            </li>
                                        </li>
                                        </form>
                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <!-- Create Leave Modal -->
    <div id="createModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('leave.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5>{{ __('db.Add Leave') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('db.Employee') }}</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Leave Type Select With Add Button -->
                    <div class="form-group">
                        <label>{{ __('db.Leave Type') }} *</label>
                        <div class="input-group">
                            <div style="width:calc(100% - 40px);" class="input-group-prepend">
                                <select class="form-control selectpicker" name="leave_types" id="leave_type_select"
                                    required>
                                    <option value="" selected disabled>{{ __('db.Leave Type') }}</option>
                                    @foreach ($leaveTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <span class="input-group-prepend">
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#createLeaveTypeModal">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </span>
                        </div>
                    </div>



                    <div class="form-group">
                        <label>{{ __('db.Start Date') }}</label>
                        <input type="text" name="start_date" class="form-control date"
                            value="{{ date(gen_setting()->date_format) }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.End Date') }}</label>
                        <input type="text" name="end_date" class="form-control date"
                            value="{{ date(gen_setting()->date_format) }}" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Leave Modal -->
    <div id="editModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('leave.update', 1) }}" method="POST" id="editForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5>{{ __('db.update_leave') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('db.Employee') }}</label>
                        <select name="employee_id" class="form-control" id="edit_employee" required>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.Leave Type') }}</label>
                        <select name="leave_types" class="form-control" id="edit_leave_type" required>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control" id="edit_start_date" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.End Date') }}</label>
                        <input type="date" name="end_date" class="form-control" id="edit_end_date" required>
                    </div>
                    <input type="hidden" name="leave_id" id="edit_id">
                    <div class="form-group">
                        <input type="submit" value="{{ __('db.update') }}" class="btn btn-primary">
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Leave Type Modal -->
    <div id="createLeaveTypeModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="leaveTypeAddForm">
                    @csrf
                    <div class="modal-header">
                        <h5>{{ __('db.Add Leave Type') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <div class="modal-body">

                        <div class="form-group">
                            <label>{{ __('db.name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>{{ __('db.Annual Quota') }}</label>
                            <input type="number" name="annual_quota" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>{{ __('db.paid_or_unpaid') }}</label>
                            <select name="encashable" class="form-control" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>{{ __('db.Carry Forward Limit') }}</label>
                            <input type="number" name="carry_forward_limit" class="form-control" required>
                        </div>

                        <input type="submit" class="btn btn-primary" value="{{ __('db.submit') }}">
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script>

        function confirmDelete() {
            return confirm("Are you sure want to delete?");
        }

        $(document).ready(function() {

            /*===============================
              EDIT BUTTON DATA LOAD
            =================================*/
            $('.edit-btn').on('click', function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_employee').val($(this).data('employee'));
                $('#edit_leave_type').val($(this).data('leave_types'));
                $('#edit_start_date').val($(this).data('start_date'));
                $('#edit_end_date').val($(this).data('end_date'));
                $('#editForm').attr('action', '/leave/' + $(this).data('id'));
            });


            /*===============================
              DATATABLE INITIALIZATION
            =================================*/
            $('#leave-table').DataTable({
                "order": [],
                'columnDefs': [{
                        "orderable": false,
                        'targets': [0, 7]
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
                buttons: [{
                        extend: 'pdf',
                        text: '<i class="ti ti-file-type-pdf"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'excel',
                        text: '<i class="ti ti-file-type-xls"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'csv',
                        text: '<i class="ti ti-file-type-csv"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'print',
                        text: '<i class="ti ti-printer"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                        footer: true
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="ti ti-eye"></i>',
                        columns: ':gt(0)'
                    }
                ]
            });


            /*========================================
              WHEN LEAVE TYPE ADD BUTTON IS CLICKED
              → CREATE MODAL HIDE
              → LEAVE TYPE MODAL OPEN
            ==========================================*/
            $("[data-target='#createLeaveTypeModal']").on("click", function() {
                $("#createModal").modal("hide");
            });

            /*========================================
              LEAVE TYPE MODAL HIDE হলে CREATE MODAL আবার SHOW হবে
            ==========================================*/
            $("#createLeaveTypeModal").on("hidden.bs.modal", function() {
                $("#createModal").modal("show");
            });


            /*========================================
              AJAX — ADD LEAVE TYPE
            ==========================================*/
            $("#leaveTypeAddForm").on("submit", function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('leave-type.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(res) {

                        // Add new type to select
                        let newOption =
                            `<option value="${res.id}" selected>${res.name}</option>`;
                        $("#leave_type_select").append(newOption);

                        // refresh selectpicker
                        $('.selectpicker').selectpicker('refresh');

                        // Hide Type Modal
                        $("#createLeaveTypeModal").modal('hide');

                        toastr.success("Leave Type Added Successfully");
                    },
                    error: function(err) {
                        toastr.error("Something went wrong!");
                    }
                });
            });

        });
    </script>
@endpush
