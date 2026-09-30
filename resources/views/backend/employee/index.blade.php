@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')
    
    <x-error-message key="name" />
    <x-error-message key="image" />
    <x-error-message key="email" />
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        @if (in_array('employees-add', $all_permission))
            <div class="container-fluid">
                <a href="{{ route('employees.create') }}" class="btn btn-info"><i class="ti ti-plus"></i>
                    {{ __('db.Add Employee') }}</a>
            </div>
        @endif
        <div class="table-responsive">
            <table id="employee-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.Image') }}</th>
                        <th>{{ __('db.name') }}</th>
                        <th>{{ __('db.email') }}</th>
                        <th>{{ __('db.Phone Number') }}</th>
                        <th>{{ __('db.Department') }}</th>
                        <th>{{ __('db.Address') }}</th>
                        <th>{{ __('db.Staff Id') }}</th>
                        <th>{{ __('db.Active') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_employee_all as $key => $employee)
                        @php $department = \App\Models\Department::find($employee->department_id); @endphp
                        <tr data-id="{{ $employee->id }}">
                            <td>{{ $key }}</td>
                            @if ($employee->image)
                                <td>
                                    <img src="{{ url('images/employee', $employee->image) }}" height="80" width="80">
                                </td>
                            @else
                                <td>No Image</td>
                            @endif
                            <td>{{ $employee->name }}</td>
                            <td>{{ $employee->email }}</td>
                            <td>{{ $employee->phone_number }}</td>
                            <td>{{ @$department->name }}</td>
                            <td>
                                {{ $employee->address }}
                                @if ($employee->city) {{ ', ' . $employee->city }} @endif
                                @if ($employee->state) {{ ', ' . $employee->state }} @endif
                                @if ($employee->postal_code) {{ ', ' . $employee->postal_code }} @endif
                                @if ($employee->country) {{ ', ' . $employee->country }} @endif
                            </td>
                            <td>{{ $employee->staff_id }}</td>
                            <td>
                                @if($employee->is_active)
                                    <div class="badge badge-success">{{trans('db.Active')}}</div>
                                @else
                                    <div class="badge badge-danger">{{trans('db.Inactive')}}</div>
                                @endif
                            </td>

                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('db.action') }}
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                        @if (in_array('employees-edit', $all_permission))
                                            <li>
                                                <button type="button" class="edit-btn btn btn-link"
                                                    data-id="{{ $employee->id }}"
                                                    data-name="{{ $employee->name }}"
                                                    data-email="{{ $employee->email }}"
                                                    data-phone_number="{{ $employee->phone_number }}"
                                                    data-department_id="{{ $employee->department_id }}"
                                                    data-address="{{ $employee->address }}"
                                                    data-city="{{ $employee->city }}"
                                                    data-country="{{ $employee->country }}"
                                                    data-staff_id="{{ $employee->staff_id }}"
                                                    data-basic_salary="{{ $employee->basic_salary }}"
                                                    data-shift_id="{{ $employee->shift_id }}"
                                                    data-designation_id="{{ $employee->designation_id }}"
                                                    data-role_id="{{ $employee->role_id }}"
                                                    data-warehouse_id="{{ $employee->warehouse_id }}"
                                                    data-biller_id="{{ $employee->biller_id }}"
                                                    data-user="{{ $employee->user ? 1 : 0 }}"
                                                    data-username="{{ $employee->user ? $employee->user->name : '' }}"
                                                    data-is_active="{{ $employee->is_active }}"
                                                    data-toggle="modal" data-target="#editModal">
                                                    <i class="ti ti-edit"></i> {{ __('db.edit') }}
                                                </button>
                                            </li>
                                        @endif
                                        <li class="divider"></li>
                                        @if (in_array('employees-delete', $all_permission))
                                            <form action="{{ route('employees.destroy', $employee->id) }}" method="post">
                                                @csrf
                                                @method('DELETE')
                                                <li>
                                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                                        <i class="ti ti-trash"></i> {{ __('db.delete') }}
                                                    </button>
                                                </li>
                                            </form>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <!-- Edit Employee Modal -->
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="editEmployeeLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="editEmployeeLabel" class="modal-title">{{ __('db.Update Employee') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                    </p>

                    <form action="{{ route('employees.update', 1) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <input type="hidden" name="employee_id" />

                        <div class="row">

                            <!-- Name -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.name') }} *</label>
                                <input type="text" name="name" required class="form-control">
                            </div>

                            <!-- Email -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.email') }} *</label>
                                <input type="email" name="email" required class="form-control">
                            </div>

                            <!-- Phone -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Phone Number') }} *</label>
                                <input type="text" name="phone_number" required class="form-control">
                            </div>

                            <!-- Address -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Address') }}</label>
                                <input type="text" name="address" class="form-control">
                            </div>

                            <!-- City -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.City') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>

                            <!-- Country -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Country') }}</label>
                                <input type="text" name="country" class="form-control">
                            </div>

                            <!-- Image -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Image') }}</label>
                                <input type="file" name="image" class="form-control">
                            </div>

                            <!-- Staff ID -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Staff Id') }}</label>
                                <input type="text" name="staff_id" class="form-control">
                            </div>

                            <!-- Basic Salary -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Basic Salary') }} *</label>
                                <input type="number" step="any" name="basic_salary" required class="form-control"
                                    placeholder="Enter basic salary">
                            </div>

                            <!-- Department -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Department') }} *</label>
                                <select class="form-control selectpicker" name="department_id" required>
                                    @foreach ($lims_department_list as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Shift -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Shift') }} *</label>
                                <select class="form-control selectpicker" name="shift_id">
                                    <option value="" disabled selected>{{ __('db.Select Shift') }}</option>
                                    @foreach ($lims_shift_list as $shift)
                                        <option value="{{ $shift->id }}">
                                            {{ $shift->name }}
                                            ({{ date('H:i', strtotime($shift->start_time)) }} -
                                            {{ date('H:i', strtotime($shift->end_time)) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Designation -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Designation') }} *</label>
                                <select class="form-control selectpicker" name="designation_id">
                                    <option value="" disabled selected>{{ __('db.Select Designation') }}</option>
                                    @foreach ($lims_designation_list as $designation)
                                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('db.select_warehouse') }}">
                                    <option value="">{{ __('db.select_warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Active Status -->
                            <div class="col-md-4 form-group">
                                <label>{{ __('db.Active') }} *</label>
                                <select name="is_active" class="form-control selectpicker" required>
                                    <option value="1">{{ __('db.Active') }}</option>
                                    <option value="0">{{ __('db.Inactive') }}</option>
                                </select>
                            </div>

                        </div>

                        <div class="form-group mt-4">
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

        @if (config('database.connections.saleprosaas_landlord'))
            if (localStorage.getItem("message")) {
                alert(localStorage.getItem("message"));
                localStorage.removeItem("message");
            }

            numberOfEmployee = <?php echo json_encode($numberOfEmployee); ?>;
            $.ajax({
                type: 'GET',
                async: false,
                url: '{{ route('package.fetchData', gen_setting()->package_id) }}',
                success: function(data) {
                    if (data['number_of_employee'] > 0 && data['number_of_product'] <= numberOfEmployee) {
                        $("a.add-employee-btn").addClass('d-none');
                    }
                }
            });
        @endif

        var employee_id = [];
        var user_verified = <?php echo json_encode(config('app.user_verified')); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function confirmDelete() {
            if (confirm("Are you sure want to delete?")) {
                return true;
            }
            return false;
        }

        $(document).on('click', '.edit-btn', function() {
            var employee = $(this);

            // Basic info
            $("#editModal input[name='employee_id']").val(employee.data('id'));
            $("#editModal input[name='name']").val(employee.data('name'));
            $("#editModal input[name='email']").val(employee.data('email'));
            $("#editModal input[name='phone_number']").val(employee.data('phone_number'));
            $("#editModal input[name='address']").val(employee.data('address'));
            $("#editModal input[name='city']").val(employee.data('city'));
            $("#editModal input[name='country']").val(employee.data('country'));
            $("#editModal input[name='staff_id']").val(employee.data('staff_id'));
            $("#editModal input[name='basic_salary']").val(employee.data('basic_salary'));

            // Select fields
            $("#editModal select[name='department_id']").val(employee.data('department_id'));
            $("#editModal select[name='shift_id']").val(employee.data('shift_id'));
            $("#editModal select[name='designation_id']").val(employee.data('designation_id'));
            $("#editModal select[name='warehouse_id']").val(employee.data('warehouse_id')); // ← warehouse
            $("#editModal select[name='is_active']").val(employee.data('is_active'));

            // Refresh selectpickers
            $('.selectpicker').selectpicker('refresh');
        });

        $('#employee-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
                "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{ __('db.Search') }}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            'columnDefs': [
                {
                    "orderable": false,
                    'targets': [0, 1, 8]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
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
            'select': {
                style: 'multi',
                selector: 'td:first-child'
            },
            'lengthMenu': [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    customize: function(doc) {
                        for (var i = 1; i < doc.content[1].table.body.length; i++) {
                            if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                var imagehtml = doc.content[1].table.body[i][0].text;
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                var src = regex.exec(imagehtml)[1];
                                var tempImage = new Image();
                                tempImage.src = src;
                                var canvas = document.createElement("canvas");
                                canvas.width = tempImage.width;
                                canvas.height = tempImage.height;
                                var ctx = canvas.getContext("2d");
                                ctx.drawImage(tempImage, 0, 0);
                                var imagedata = canvas.toDataURL("image/png");
                                delete doc.content[1].table.body[i][0].text;
                                doc.content[1].table.body[i][0].image = imagedata;
                                doc.content[1].table.body[i][0].fit = [30, 30];
                            }
                        }
                    },
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                if (column === 0 && (data.indexOf('<img src=') != -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    },
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                if (column === 0 && (data.indexOf('<img src=') != -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    },
                },
                {
                    extend: 'csv',
                    text: '<i title="export for device" class="ti ti-tablet"></i>',
                    className: 'export-for-device',
                    exportOptions: {
                        columns: [7, 2],
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                return data;
                            }
                        }
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            employee_id.length = 0;
                            $(':checkbox:checked').each(function(i) {
                                if (i) {
                                    employee_id[i - 1] = $(this).closest('tr').data('id');
                                }
                            });
                            if (employee_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'employees/deletebyselection',
                                    data: { employeeIdArray: employee_id },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove().draw(false);
                                            }
                                        });
                                        alert(data);
                                    }
                                });
                            } else if (!employee_id.length) {
                                alert('No employee is selected!');
                            }
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
    </script>
@endpush
