@extends('backend.layout.main')
@section('content')
    <section>
        <div class="container-fluid">
            <h4 class="mt-3 mb-4">{{ __("db.HRM - Generate Payroll") }}</h4>

            {{-- Payroll Multiple Create Form --}}
            <form action="{{ route('payroll.storeMultiple') }}" method="POST">
                @csrf

            {{-- Hidden common values --}}
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="warehouse_id" value="{{ $warehouse_id }}">

            {{-- Payroll Header Section --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <h5 class="fw-bold mb-2">
                                {{ __("db.Payroll for") }} <strong>{{ date('F Y', strtotime($month)) }}</strong>
                            </h5>
                            <small class="text-muted d-block">
                                <b>{{ __("db.Warehouse") }}</b> :
                                {{ $warehouse_id ? $warehouse->name : __("db.All Warehouse") }}
                                <input type="hidden" name="location_id" value="{{ $warehouse_id }}">
                            </small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                {{ __("db.Payroll Group Name") }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="payroll_group_name" required
                                value="{{ __("db.Payroll for") }} {{ date('F Y', strtotime($month)) }}">
                        </div>

                        {{-- Account --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">{{ __('db.Account') }} *</label>
                        <select class="form-control selectpicker" name="account_id">
                            @foreach ($lims_account_list as $account)
                                <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                                    {{ $account->name }} [{{ $account->account_no }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                        {{-- <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                {{ __("db.status") }} <span class="text-danger">*</span>
                                <x-info title="Change status here to update payroll status for all selected employees." />
                            </label>
                            <select name="payroll_group_status" class="form-select select2 payroll-group-status">
                                <option value="">{{ __("db.change_status") }}</option>
                                <option value="draft">{{ __("db.Draft") }}</option>
                                <option value="final">{{ __("db.final") }}</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                {{ __("db.Payroll cannot be deleted if status is final") }}.
                            </small>
                        </div> --}}
                    </div>
                </div>
            </div>

            {{-- Payroll Details Section --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">

                        @foreach ($employees as $employee)
                            @php
                                $payroll = $employee->existing_payroll;
                            @endphp

                            <div class="col-md-12">
                                <div class="card mb-3 shadow-sm border-0">
                                    <div class="card-header bg-light">
                                        <strong>{{ $employee->name }}</strong>
                                        <small class="text-muted ms-2">
                                            {{ __("db.employee_id") }}: {{ $employee->id }}
                                        </small>
                                    </div>

                                    <div class="card-body p-0">
                                        <table class="table table-bordered align-middle mb-0">
                                            <tr>
                                                <td style="width:30%">
                                                    <strong>{{ $employee->name }}</strong><br><br>
                                                    <b>{{ __("db.Leaves") }} :</b> {{ $employee->total_leaves ?? 0 }} {{ __("db.Days") }} <br><br>
                                                    <b>{{ __("db.Work Duration") }} :</b> {{ $employee->total_work_hours ?? '0.00' }} {{ __("db.Hours") }} <br><br>
                                                    <b>{{ __("db.Attendance") }} :</b> {{ $employee->attendance_days ?? 0 }} {{ __("db.Days") }}
                                                </td>

                                                <td>
                                                    <div class="row g-2">

                                                        {{-- Hidden --}}
                                                        <input type="hidden"
                                                            name="payrolls[{{ $employee->id }}][employee_id]"
                                                            value="{{ $employee->id }}">
                                                        <input type="hidden" name="payrolls[{{ $employee->id }}][user_id]"
                                                            value="{{ $employee->user_id }}">
                                                        <input type="hidden" name="payrolls[{{ $employee->id }}][month]"
                                                            value="{{ $month }}">

                                                        {{-- Salary --}}
                                                        <div class="col-md-3">
                                                            <label>{{ __("db.Salary") }} *</label>
                                                            <input type="number" step="any"
                                                                name="payrolls[{{ $employee->id }}][salary_amount]"
                                                                class="form-control salary-input"
                                                                data-emp="{{ $employee->id }}"
                                                                value="{{ $payroll['salary'] ?? 0 }}">
                                                        </div>

                                                        {{-- Overtime --}}
                                                        <div class="col-md-2">
                                                            <label>{{ __("db.Overtime") }}</label>
                                                            <input type="number" step="any"
                                                                name="payrolls[{{ $employee->id }}][overtime]"
                                                                class="form-control overtime-input"
                                                                data-emp="{{ $employee->id }}"
                                                                value="{{ $payroll['overtime'] ?? 0 }}">
                                                        </div>

                                                        {{-- Commission --}}
                                                        <div class="col-md-2">
                                                            <label>{{ __("db.Commission") }}</label>
                                                            <input type="number" step="any"
                                                                name="payrolls[{{ $employee->id }}][commission]"
                                                                class="form-control comm-input"
                                                                data-emp="{{ $employee->id }}"
                                                                data-is-agent="{{ $employee->is_sale_agent ? 1 : 0 }}"
                                                                data-percent="{{ $employee->sale_commission_percent ?? 0 }}"
                                                                value="{{ $payroll['commission'] ?? 0 }}"
                                                                {{ $employee->is_sale_agent ? '' : 'readonly' }}>
                                                        </div>

                                                        {{-- Employee advance recovery --}}
                                                        <div class="col-md-2">
                                                            <label>{{ __("db.employee_advance_recovery") }}</label>
                                                            <input type="number" step="any"
                                                                name="payrolls[{{ $employee->id }}][expense]"
                                                                class="form-control prev-input"
                                                                data-emp="{{ $employee->id }}"
                                                                value="{{ $payroll['expense'] ?? 0 }}">
                                                        </div>

                                                        {{-- Total --}}
                                                        <div class="col-md-3">
                                                            <label>{{ __("db.Total Payable") }}</label>
                                                            <input type="number" step="any"
                                                                name="payrolls[{{ $employee->id }}][amount]"
                                                                class="form-control total-output"
                                                                data-emp="{{ $employee->id }}"
                                                                value="{{ $payroll['total_amount'] ?? 0 }}" readonly>
                                                        </div>

                                                        {{-- Status --}}
                                                        <div class="col-md-3">
                                                            <label>{{ __("db.Select Status") }}</label>
                                                            <select name="payrolls[{{ $employee->id }}][status]"
                                                                class="form-control selectpicker emp-status">
                                                                <option value="final">{{ __("db.final") }}</option>
                                                                <option value="draft">{{ __("db.Draft") }}</option>
                                                            </select>
                                                        </div>

                                                        {{-- Payment Method --}}
                                                        <div class="col-md-2">
                                                            <label>{{ __("db.Method") }} *</label>
                                                            <select name="payrolls[{{ $employee->id }}][paying_method]"
                                                                class="form-control selectpicker">
                                                                <option value="0" {{ ($payroll['method'] ?? '') == 0 ? 'selected' : '' }}>
                                                                    {{ __("db.Cash") }}
                                                                </option>
                                                                <option value="1" {{ ($payroll['method'] ?? '') == 1 ? 'selected' : '' }}>
                                                                    {{ __("db.Cheque") }}
                                                                </option>
                                                                <option value="2" {{ ($payroll['method'] ?? '') == 2 ? 'selected' : '' }}>
                                                                    {{ __("db.Credit Card") }}
                                                                </option>
                                                            </select>
                                                        </div>

                                                        {{-- Date --}}
                                                        <div class="col-md-2">
                                                            <label>{{ __("db.date") }}</label>
                                                            <input type="text" class="form-control date"
                                                                name="payrolls[{{ $employee->id }}][created_at]"
                                                                value="{{ $payroll['date'] ?? now()->format('d-m-Y') }}">
                                                        </div>

                                                        {{-- Note --}}
                                                        {{-- <div class="col-md-8">
                                                            <label>{{ __("db.note") }}</label>
                                                            <input type="text" class="form-control"
                                                                name="payrolls[{{ $employee->id }}][note]"
                                                                value="{{ $payroll['note'] ?? '' }}">
                                                        </div> --}}

                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ti ti-checkmark me-1"></i> {{ __("db.Submit All Payrolls") }}
                </button>
            </div>

            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // ========== STATUS SYNC ==============
            $('.payroll-group-status').on('change', function() {
                let selectedStatus = $(this).val(); // draft / final

                if (selectedStatus !== "") {
                    $('.emp-status').each(function() {
                        $(this).val(selectedStatus).change();
                    });
                }
            });

            // ================= CALCULATION ====================
            function calculateTotal(empId) {
                let salary = parseFloat($(`input.salary-input[data-emp='${empId}']`).val()) || 0;
                let expense = parseFloat($(`input.prev-input[data-emp='${empId}']`).val()) || 0;
                let overtime = parseFloat($(`input.overtime-input[data-emp='${empId}']`).val()) || 0;

                let commissionInput = parseFloat($(`input.comm-input[data-emp='${empId}']`).val()) || 0;

                let commission = (salary * commissionInput) / 100;
                let total = salary + commission + overtime - expense;

                $(`input.total-output[data-emp='${empId}']`).val(total.toFixed(2));
            }

            $('.salary-input, .prev-input, .comm-input, .overtime-input').on('input', function() {
                let empId = $(this).data('emp');
                calculateTotal(empId);
            });

            $('.salary-input').each(function() {
                calculateTotal($(this).data('emp'));
            });

        });
    </script>
@endpush
