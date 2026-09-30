@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Add Employee')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <!-- Image -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Image')}}</label>
                                        <input type="file" name="image" class="form-control">
                                        @if($errors->has('image'))
                                        <span><strong>{{ $errors->first('image') }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Name -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.name')}} *</label>
                                        <input type="text" name="employee_name" required class="form-control">
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.email')}} *</label>
                                        <input type="email" name="email" placeholder="example@example.com" required class="form-control">
                                        @if($errors->has('email'))
                                        <span><strong>{{ $errors->first('email') }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Phone Number')}} *</label>
                                        <input type="text" name="phone_number" required class="form-control">
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Address')}}</label>
                                        <input type="text" name="address" class="form-control">
                                    </div>
                                </div>

                                <!-- City -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.City')}}</label>
                                        <input type="text" name="city" class="form-control">
                                    </div>
                                </div>

                                <!-- Country -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Country')}}</label>
                                        <input type="text" name="country" class="form-control">
                                    </div>
                                </div>

                                <!-- Basic Salary -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Basic Salary')}} *</label>
                                        <input type="number" step="any" name="basic_salary" required class="form-control" placeholder="Enter basic salary">
                                    </div>
                                </div>

                                <!-- Staff ID -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Staff Id')}}</label>
                                        <input type="text" name="staff_id" class="form-control">
                                    </div>
                                </div>

                                <!-- Role -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Role')}} *</label>
                                        <select name="role_id" class="selectpicker form-control" required>
                                            @foreach($lims_role_list as $role)
                                            <option value="{{$role->id}}">{{$role->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Warehouse -->
                                <div class="col-md-4" id="warehouse">
                                    <div class="form-group">
                                        <label>{{__('db.Warehouse')}} *</label>
                                        <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Warehouse...">
                                            @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Biller -->
                                <div class="col-md-4" id="biller">
                                    <div class="form-group">
                                        <label>{{__('db.Biller')}} *</label>
                                        <select name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                            @foreach($lims_biller_list as $biller)
                                            <option value="{{$biller->id}}">{{$biller->name}} ({{$biller->company_name}})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Active Status -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Active')}} *</label>
                                        <select name="is_active" class="selectpicker form-control" required>
                                            <option value="1">{{__('db.Active')}}</option>
                                            <option value="0">{{__('db.Inactive')}}</option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <hr>

                            <div class="row">
                                <!-- Department -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Department')}} *</label>
                                        <div class="input-group">
                                            <div style="width:calc(100% - 40px);" class="input-group-prepend">
                                                <select class="form-control selectpicker" name="department_id" required>
                                                    <option value="" selected disabled>{{__('db.Select Department')}}</option>
                                                    @foreach($lims_department_list as $department)
                                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <span class="input-group-prepend">
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDepartmentModal"><i class="ti ti-plus"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shift -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Shift')}} *</label>
                                        <div class="input-group">
                                            <div style="width:calc(100% - 40px);" class="input-group-prepend">
                                                <select class="form-control selectpicker" name="shift_id" required>
                                                    <option value="" selected disabled>{{__('db.Select Shift')}}</option>
                                                    @foreach($lims_shift_list as $shift)
                                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ date('H:i', strtotime($shift->start_time)) }} - {{ date('H:i', strtotime($shift->end_time)) }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <span class="input-group-prepend">
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createShiftModal"><i class="ti ti-plus"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Designation -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Designation')}} *</label>
                                        <div class="input-group">
                                            <div style="width:calc(100% - 40px);" class="input-group-prepend">
                                                <select class="form-control selectpicker" name="designation_id" required>
                                                    <option value="" selected disabled>{{__('db.Select Designation')}}</option>
                                                    @foreach($lims_designation_list as $designation)
                                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <span class="input-group-prepend">
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDesignationModal"><i class="ti ti-plus"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <hr>

                            <!-- User Section -->
                            <div class="row user-section">
                                <div class="col-md-4">
                                    <div class="form-group mt-4">
                                        <input type="checkbox" name="user" value="1" />
                                        <label>{{__('db.Add User')}} <x-info title="If checked, employee will be able to login with username and password you set" type="info" /></label>
                                    </div>
                                </div>

                                <div class="col-md-4 user-input">
                                    <div class="form-group">
                                        <label>{{__('db.username')}} *</label>
                                        <input type="text" name="name" required class="form-control">
                                        @if($errors->has('name'))
                                        <span><strong>{{ $errors->first('name') }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-4 user-input">
                                    <div class="form-group">
                                        <label>{{__('db.Password')}} *</label>
                                        <input required type="text" name="password" class="form-control">
                                    </div>
                                </div>

                                {{-- Sale Agent Commission Section --}}
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="is_sale_agent" id="is_sale_agent" value="1">
                                            {{ __('db.Is Sale Agent?') }}
                                        </label>
                                    </div>

                                    <div id="sale-agent-section" class="border p-3 rounded" style="display: none;">
                                        <h5>{{ __('db.Sales Target') }}</h5>
                                        <div id="commission-wrapper">
                                            <div class="commission-row row mb-2">
                                                <div class="col-md-3">
                                                    <input type="number" name="sales_target[0][sales_from]"
                                                        class="form-control" placeholder="{{ __("db.Total Sales Amount From") }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" name="sales_target[0][sales_to]"
                                                        class="form-control" placeholder="{{ __('db.Total Sales Amount To') }}">
                                                    <small class="text-danger error-message"></small>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" step="0.01" name="sales_target[0][percent]"
                                                        class="form-control" placeholder="{{ __('db.Commission Percent') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-danger remove-row">{{ __("db.Cancel") }}</button>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-success mt-2"
                                            id="add-more">{{ __('db.Add More') }}</button>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group mt-4">
                                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Department Modal -->
<div id="createDepartmentModal" class="modal fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('departments.store') }}" method="POST" id="addDepartmentForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{__('db.Add Department')}}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type department name') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Shift Modal -->
<div id="createShiftModal" class="modal fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('shift.store') }}" method="POST" id="addShift">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{__('db.Add Shift')}}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type shift name') }}">
                    </div>
                    <div class="form-group">
                        <label>{{__('db.start_time')}} *</label>
                        <input type="time" name="start_time" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__('db.end_time')}} *</label>
                        <input type="time" name="end_time" required class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Designation Modal -->
<div id="createDesignationModal" class="modal fade text-left" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('designations.store') }}" method="POST" id="addDesignation">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{__('db.Add Designation')}}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{__('db.name')}} *</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type designation name') }}">
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#hrm").siblings('a').attr('aria-expanded', 'true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #employee-menu").addClass("active");

    $('#warehouse').hide();
    $('#biller').hide();


    // Page load-এ চেকবক্সের state অনুযায়ী hide/show
    if ($('input[name="user"]').is(':checked')) {
        $('.user-input').show();
        $('input[name="name"]').prop('required', true);
        $('input[name="password"]').prop('required', true);
    } else {
        $('.user-input').hide();
        $('input[name="name"]').prop('required', false);
        $('input[name="password"]').prop('required', false);
    }

    $('input[name="user"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.user-input').show(300);
            $('input[name="name"]').prop('required', true);
            $('input[name="password"]').prop('required', true);
        } else {
            $('.user-input').hide(300);
            $('input[name="name"]').prop('required', false);
            $('input[name="password"]').prop('required', false);
        }
    });

    $('select[name="role_id"]').on('change', function() {
        if ($(this).val() > 2) {
            $('#warehouse').show(400);
            $('#biller').show(400);
            $('select[name="warehouse_id"]').prop('required', true);
            $('select[name="biller_id"]').prop('required', true);
        } else {
            $('#warehouse').hide(400);
            $('#biller').hide(400);
            $('select[name="warehouse_id"]').prop('required', false);
            $('select[name="biller_id"]').prop('required', false);
        }
    });

    // Sale agent commission toggle
    $('#is_sale_agent').on('change', function() {
        const isChecked = this.checked;

        $('#sale-agent-section').toggle(isChecked);

        const section = $('#sale-agent-section');

        // Toggle required attribute
        section.find('input, select').prop('required', isChecked);

        // Clear when unchecked
        if (!isChecked) {
            section.find('input').val('');
            section.find('.error-message').text('');

            // Keep only first row
            section.find('.commission-row').not(':first').remove();
        }
    });

    // Add more commission rows
    let rowIndex = 1;
    $('#add-more').on('click', function() {
        $('#commission-wrapper').append(`
        <div class="commission-row row mb-2">
            <div class="col-md-3">
                <input type="number" name="sales_target[${rowIndex}][sales_from]" class="form-control" placeholder="Total Sales Amount From">
            </div>
            <div class="col-md-3">
                <input type="number" name="sales_target[${rowIndex}][sales_to]" class="form-control" placeholder="Total Sales Amount To">
                <small class="text-danger error-message"></small>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="sales_target[${rowIndex}][percent]" class="form-control" placeholder="Commission Percent" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger remove-row">Cancel</button>
            </div>
        </div>
    `);
        rowIndex++;
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('.commission-row').remove();
        validateSalesTargets();
    });

    // Inline validation on input
    $(document).on("input", "#commission-wrapper input", function() {
        validateSalesTargets();
    });

    function validateSalesTargets() {
        let rows = $("#commission-wrapper .commission-row");
        let prevTo = null;
        let isValid = true;

        rows.each(function(index) {
            let fromInput = $(this).find("input[name*='[sales_from]']");
            let toInput = $(this).find("input[name*='[sales_to]']");
            let errorMsg = $(this).find(".error-message");

            let fromVal = parseFloat(fromInput.val()) || 0;
            let toVal = parseFloat(toInput.val()) || 0;

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
        if ($("#is_sale_agent").is(":checked")) {
            if (!validateSalesTargets()) {
                e.preventDefault();
            }
        }
    });


    $('#addDepartmentForm').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('departments.store') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $('#createDepartmentModal').modal('hide');
                $('select[name="department_id"]').append(
                    `<option value="${res.id}" selected>${res.name}</option>`
                );
                $('select[name="department_id"]').selectpicker('refresh');
                alert("Department added!");
            },
            error: function(err) {
                alert("Something went wrong!");
            }
        });
    });

    $('#addShift').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('shift.store') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $('#createShiftModal').modal('hide');
                $('select[name="shift_id"]').append(
                    `<option value="${res.id}" selected>${res.name}</option>`
                );
                $('select[name="shift_id"]').selectpicker('refresh');
                alert("Shift added!");
            },
            error: function(err) {
                alert("Something went wrong!");
            }
        });
    });

    $('#addDesignation').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();
        $.ajax({
            url: "{{ route('designations.store') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $('#createDesignationModal').modal('hide');
                $('select[name="designation_id"]').append(
                    `<option value="${res.id}" selected>${res.name}</option>`
                );
                $('select[name="designation_id"]').selectpicker('refresh');
                alert("Designation added!");
            },
            error: function(err) {
                alert("Something went wrong!");
            }
        });
    });
</script>
@endpush