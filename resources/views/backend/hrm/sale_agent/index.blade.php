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
    @if (in_array('sale-agents', $all_permission))
        <div class="container-fluid">
            <a href="{{ route('sale-agents.create') }}" class="btn btn-info"><i class="ti ti-plus"></i>
                {{ __('db.Add Sale Agent') }}</a>
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
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lims_sale_agent_all as $key => $agent)
                    @php $department = \App\Models\Department::find($agent->department_id); @endphp
                    <tr data-id="{{ $agent->id }}">
                        <td>{{ $key }}</td>
                        @if ($agent->image)
                            <td>
                                <img src="{{ url('images/employee', $agent->image) }}" height="80"
                                    width="80">
                            </td>
                        @else
                            <td>No Image</td>
                        @endif
                        <td>{{ $agent->name }}</td>
                        <td>{{ $agent->email }}</td>
                        <td>{{ $agent->phone_number }}</td>
                        <td>{{ @$department->name }}</td>
                        <td>{{ $agent->address }}
                            @if ($agent->city)
                                {{ ', ' . $agent->city }}
                            @endif
                            @if ($agent->state)
                                {{ ', ' . $agent->state }}
                            @endif
                            @if ($agent->postal_code)
                                {{ ', ' . $agent->postal_code }}
                            @endif
                            @if ($agent->country)
                                {{ ', ' . $agent->country }}
                            @endif
                        </td>
                        <td>{{ $agent->staff_id }}</td>

                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                    data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">{{ __('db.action') }}
                                    <span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default">

                                    @if (in_array('sale-agents', $all_permission))
                                        <li>
                                            <button type="button" class="edit-btn btn btn-link"
                                                data-id="{{ $agent->id }}" data-name="{{ $agent->name }}"
                                                data-email="{{ $agent->email }}"
                                                data-phone_number="{{ $agent->phone_number }}"
                                                data-department_id="{{ $agent->department_id }}"
                                                data-address="{{ $agent->address }}"
                                                data-city="{{ $agent->city }}"
                                                data-country="{{ $agent->country }}"
                                                {{-- data-staff_id="{{ $agent->staff_id }}" --}}
                                                @if (isset($agent->sales_target)) data-sales_target='@json($agent->sales_target)' @else data-sales_target='[]' @endif
                                                data-toggle="modal" data-target="#editModal">
                                                <i class="ti ti-edit"></i> {{ __('db.edit') }}
                                            </button>
                                        </li>
                                    @endif

                                    <li class="divider"></li>

                                    @if (in_array('sale-agents', $all_permission))
                                        <form action="{{ route('sale-agents.destroy', $agent->id) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link"
                                                    onclick="return confirmDelete()">
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

{{-- Edit Modal --}}
<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
    class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{ __('db.update_sale_agent') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                        aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic">
                    <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                </p>

                <form action="{{ route('sale-agents.update', 1) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="employee_id" />

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>{{ __('db.name') }} *</label>
                            <input type="text" name="name" required class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('db.Image') }}</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('db.email') }} *</label>
                            <input type="email" name="email" required class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('db.Phone Number') }} *</label>
                            <input type="text" name="phone_number" required class="form-control">
                        </div>

                        <div class="col-md-4 form-group">
                            <label>{{ __('db.Address') }} *</label>
                            <input type="text" name="address" required class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ __('db.City') }} *</label>
                            <input type="text" name="city" required class="form-control">
                        </div>

                        <div class="col-md-4 form-group">
                            <label>{{ __('db.Country') }}</label>
                            <input type="text" name="country" class="form-control">
                        </div>
                        {{-- <div class="col-md-4 form-group">
                            <label>{{ __('db.Staff Id') }}</label>
                            <input type="text" name="staff_id" class="form-control">
                        </div> --}}

                        {{-- Sale Agent Commission --}}
                        <div class="col-md-12 mt-4">
                            <div class="form-group d-none">
                                <label>
                                    <input type="checkbox" name="is_sale_agent" id="edit_is_sale_agent" value="1" hidden
                                        checked>
                                    {{ __('db.Is Sale Agent?') }}
                                </label>
                            </div>

                            <div id="edit-sale-agent-section" class="border p-3 rounded">
                                <h5>{{ __('db.Sales Target') }}</h5>
                                <div id="edit-commission-wrapper"></div>
                                <button type="button" class="btn btn-success mt-2"
                                    id="edit-add-more">{{ __('db.Add More') }}</button>
                            </div>
                        </div>
                        {{-- User Account Section --}}
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <input type="checkbox" name="user" id="add-user" value="1" />&nbsp;
                                <label for="add-user">{{ __('db.Add User') }}
                                    <x-info
                                        title="If checked, sale agent will be able to login with username and password you set"
                                        type="info" />
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3 user-input" style="display:none;">
                            <div class="form-group">
                                <label>{{ __('db.username') }} *</label>
                                <input type="text" name="username" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-3 user-input" style="display:none;">
                            <div class="form-group">
                                <label>{{ __('db.Password') }} *</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-3 user-role" style="display:none;">
                            <div class="form-group">
                                <label>{{ __('db.Role') }} *</label>
                                <select name="role_id" class="form-control">
                                    @foreach ($lims_role_list as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>


                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary" id="submit">{{ __('db.submit') }}</button>
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



        // Toggle user input fields when checkbox changes
        $("#editModal input[name='user']").on('change', function() {
            if ($(this).is(':checked')) {
                $('.user-input').show();
                $("#editModal input[name='name'], #editModal input[name='password']").prop('required', true);
            } else {
                $('.user-input').hide();
                $("#editModal input[name='name'], #editModal input[name='password']").prop('required', false);
            }
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
            'columnDefs': [{
                    "orderable": false,
                    'targets': [0, 1, 8]
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
                                    data: {
                                        employeeIdArray: employee_id
                                    },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove()
                                                    .draw(false);
                                            }
                                        });
                                        alert(data);
                                    }
                                });
                                // dt.rows({ page: 'current', selected: true }).remove().draw(false);
                            } else if (!employee_id.length)
                                alert('No employee is selected!');
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
        });



        let rowIndex = 0;

        $(document).on('click', '.edit-btn', function() {
            let row = $(this);
            let salesTargetData = row.data('sales_target') || [];

            $("#editModal input[name='employee_id']").val(row.data('id'));
            $("#editModal input[name='name']").val(row.data('name'));
            $("#editModal input[name='email']").val(row.data('email'));
            $("#editModal input[name='phone_number']").val(row.data('phone_number'));
            $("#editModal input[name='address']").val(row.data('address'));
            $("#editModal input[name='city']").val(row.data('city'));
            $("#editModal input[name='state']").val(row.data('state'));
            $("#editModal input[name='country']").val(row.data('country'));
            // $("#editModal input[name='staff_id']").val(row.data('staff_id'));

            // if (row.data('is_sale_agent') == 1) {
            //     $('#edit_is_sale_agent').prop('checked', true);
            //     $('#edit-sale-agent-section').show();
            // } else {
            //     $('#edit_is_sale_agent').prop('checked', false);
            //     $('#edit-sale-agent-section').hide();
            // }

            // Clear existing commission rows
            $('#edit-commission-wrapper').html('');
            rowIndex = 0;

            if (salesTargetData.length > 0) {
                salesTargetData.forEach(target => {
                    $('#edit-commission-wrapper').append(`
                <div class="commission-row row mb-2">
                    <div class="col-md-3">
                        <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From" value="${target.sales_from}">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To" value="${target.sales_to}">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent" value="${target.percent}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger remove-row">Cancel</button>
                    </div>
                </div>
            `);
                    rowIndex++;
                });
            } else if (row.data('is_sale_agent') == 1) {
                $('#edit-commission-wrapper').append(`
            <div class="commission-row row mb-2">
                <div class="col-md-3">
                    <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From">
                </div>
                <div class="col-md-3">
                    <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To">
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent" required>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger remove-row">Cancel</button>
                </div>
            </div>
        `);
            }
        });

        $('#edit-add-more').on('click', function() {
            $('#edit-commission-wrapper').append(`
        <div class="commission-row row mb-2">
            <div class="col-md-3">
                <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From">
            </div>
            <div class="col-md-3">
                <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To">
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent">
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger remove-row">Cancel</button>
            </div>
        </div>
    `);
            rowIndex++;
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('.commission-row').remove();
        });

        // Toggle Sales Target section when checkbox is changed
        $('#edit_is_sale_agent').on('change', function() {
            if ($(this).is(':checked')) {
                $('#edit-sale-agent-section').show();
                validateSalesTargets();
                if ($('#edit-commission-wrapper').children().length === 0) {
                    // only add empty row if no rows exist (new agent case)
                    $('#edit-commission-wrapper').append(`
                <div class="commission-row row mb-2">
                    <div class="col-md-3">
                        <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger remove-row">Cancel</button>
                    </div>
                </div>
            `);
                    rowIndex++;
                }

            } else {
                $('#edit-sale-agent-section').hide();
                // ❌ don't clear wrapper here
                // $('#edit-commission-wrapper').html('');
            }
        });

        // Toggle User Account Section
        $('#add-user').on('change', function() {
            if ($(this).is(':checked')) {
                $('.user-input, .user-role').show();
            } else {
                $('.user-input, .user-role').hide();
            }
        });


    // Remove row
    $(document).on('click', '.remove-row', function () {
        $(this).closest('.commission-row').remove();
        validateSalesTargets();
    });

    // Inline validation on input
    $(document).on("input", "#edit-commission-wrapper input", function() {
        validateSalesTargets();
    });

    function validateSalesTargets() {
        let rows = $("#edit-commission-wrapper .commission-row");
        let prevTo = null;
        let isValid = true;

        rows.each(function(index) {
            let fromInput = $(this).find("input[name*='[sales_from]']");
            let toInput   = $(this).find("input[name*='[sales_to]']");
            let errorMsg  = $(this).find(".error-message");

            let fromVal = parseFloat(fromInput.val()) || 0;
            let toVal   = parseFloat(toInput.val()) || 0;

            // reset
            errorMsg.text("");
            fromInput.removeClass("is-invalid");
            toInput.removeClass("is-invalid");

            // Rule 1: from < to
            if (fromVal >= toVal && toVal !== 0) {
                errorMsg.text("From must be less than To");
                fromInput.addClass("is-invalid");
                toInput.addClass("is-invalid");
                isValid = false;
            }

            // Rule 2: previous "to" < current "from"
            if (prevTo !== null && fromVal <= prevTo && fromVal !== 0) {
                errorMsg.text("This 'From' must be greater than previous row's 'To'");
                fromInput.addClass("is-invalid");
                isValid = false;
            }

            prevTo = toVal;
        });

        return isValid;
    }

    // Prevent invalid submit
    $("form").on("submit", function(e) {
        if (!validateSalesTargets()) {
            e.preventDefault();
        }
    });
    </script>
@endpush
