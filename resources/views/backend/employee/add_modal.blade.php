@php
    $lims_department_list = \App\Models\Department::where('is_active', true)->get();
    $lims_shift_list = \App\Models\Shift::where('is_active', true)->get();
    $lims_designation_list = \App\Models\Designation::active()->get();
@endphp

<!-- add employee modal -->
<div id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('employees.store') }}" method="post" id="employee-quick-form">
                @csrf
                <div class="modal-header">
                    <h5 id="employeeModalLabel" class="modal-title">{{__('db.Add Employee')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.name')}} *</label>
                            <input type="text" name="employee_name" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.email')}} *</label>
                            <input type="email" name="email" required class="form-control" placeholder="example@example.com">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Phone Number')}} *</label>
                            <input type="text" name="phone_number" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Basic Salary')}} *</label>
                            <input type="number" step="any" name="basic_salary" required class="form-control" placeholder="Enter basic salary">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Department')}} *</label>
                            <select class="form-control selectpicker" name="department_id" required>
                                @foreach ($lims_department_list as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Shift')}} *</label>
                            <select class="form-control selectpicker" name="shift_id" required>
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
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Designation')}} *</label>
                            <select class="form-control selectpicker" name="designation_id" required>
                                <option value="" disabled selected>{{ __('db.Select Designation') }}</option>
                                @foreach ($lims_designation_list as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <!-- Flag for AJAX -->
                        <input type="hidden" name="ajax" value="1">
                        <input type="hidden" name="is_active" value="1">
                        <button type="button" class="btn btn-primary employee-submit-btn">{{__('db.submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.employee-submit-btn').off('click').on("click", function() {
            var form = $("#employee-quick-form");

            $.ajax({
                type:'POST',
                url:'{{route("employees.store")}}',
                data: form.serialize(),
                success:function(response) {
                    if(response['id']) {
                        key = response['id'];
                        value = response['name'];
                        
                        var targetSelects = ['employee_id[]', 'employee_id', 'edit_employee_id[]', 'edit_employee_id'];
                        targetSelects.forEach(function(nameAttr) {
                            var $select = $('select[name="'+nameAttr+'"]');
                            if($select.length) {
                                $select.append('<option value="'+ key +'">'+ value +'</option>');
                                // For multiple select, we might not want to override existing selections
                                if ($select.prop('multiple')) {
                                    var currentVal = $select.val() || [];
                                    currentVal.push(key);
                                    $select.val(currentVal).trigger('change');
                                } else {
                                    $select.val(key).trigger('change');
                                }
                            }
                        });
                        
                        $('.selectpicker').selectpicker('refresh');
                        $("#addEmployeeModal").modal('hide');
                        $("#employee-quick-form")[0].reset();
                    } else {
                        alert('Something went wrong!');
                    }
                },
                error:function(xhr) {
                    var msg = 'Error adding employee. Make sure all required fields are filled correctly and email is unique.';
                    if (xhr.responseText) {
                        try {
                            var err = JSON.parse(xhr.responseText);
                            if (err.errors) {
                                msg = Object.values(err.errors).flat().join('\n');
                            } else if (err.message) {
                                msg = err.message;
                            }
                        } catch(e) {}
                    }
                    alert(msg);
                }
            });
        });
    });
</script>
@endpush
