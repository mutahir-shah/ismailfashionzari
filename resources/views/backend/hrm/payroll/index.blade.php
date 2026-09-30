@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <section>
        <div class="container-fluid">

            <!-- Add Multiple Payroll Button -->
            <button class="btn btn-success" data-toggle="modal" data-target="#addMultipleModal">
                <i class="ti ti-plus"></i> {{ __('db.Generate Payroll') }}
            </button>

            <div class="d-inline-block ml-2">
                <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="false" aria-controls="filterCollapse">
                    <i class="ti ti-filter"></i> {{ __('db.Filter') }}
                </button>
            </div>

            <div class="collapse mt-3" id="filterCollapse">
                <div class="card card-body">
                    <div class="row g-3">
                        <!-- Employee Filter -->
                        <div class="col-md-4">
                            <label>{{ __('db.Employee') }}</label>
                            <select id="filterEmployee" class="form-control selectpicker" data-live-search="true"
                                title="Select Employee">
                                <option value="">{{ __('db.All') }}</option>
                                @foreach ($lims_employee_list as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Month Filter -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('db.select_month') }}:</label>
                                <input type="text" id="filterMonth" class="form-control filter-month-picker" />
                            </div>
                        </div>

                        <!-- Date Filter -->
                        <div class="col-md-4">
                            <label>{{ __('db.date') }}</label>
                            <input type="date" id="filterDate" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Add Multiple Payroll Modal -->
        <div id="addMultipleModal" class="modal fade text-left" tabindex="-1" role="dialog"
            aria-labelledby="multiplePayrollLabel" aria-hidden="true">
            <div role="document" class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="ti ti-x"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <form action="{{ route('payroll.generateCards') }}" method="POST">
                            @csrf
                            <div class="row g-3">

                            {{-- Warehouse --}}
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.Warehouse') }} *</label>
                                <select id="warehouseSelect" name="warehouse_id" class="form-control selectpicker"
                                    data-live-search="true" title="{{ __('db.select_warehouse') }}" required>
                                    <option value="0">{{ __('db.All Warehouse') }}</option>
                                    @foreach ($lims_warehouse_list as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Month --}}
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.select_month') }}:</label>
                                <input type="text" name="month" id="modalMonthPicker" class="form-control modal-month-picker" required />
                            </div>

                            {{-- Employee Multi Select --}}
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.Employees') }} *</label>
                                <div class="mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        id="selectAllEmployees">{{ __('db.Select All') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        id="deselectAllEmployees">{{ __('db.Deselect All') }}</button>
                                </div>
                                <select id="employeeMultiple" name="employee_ids[]"
                                    class="form-control selectpicker"
                                    multiple
                                    required
                                    data-live-search="true"
                                    title="{{ __('db.select_employees') }}"
                                    data-actions-box="true">
                                    {{-- Options will load dynamically --}}
                                </select>
                            </div>
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-checkmark"></i> {{ __('db.Submit Payrolls') }}
                            </button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="payroll-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.date') }}</th>
                        <th>{{ __('db.reference') }}</th>
                        <th>{{ __('db.Employee') }}</th>
                        <th>{{ __('db.Account') }}</th>
                        <th>{{ __('db.Amount') }}</th>
                        <th>{{ __('db.Method') }}</th>
                        <th>{{ __('db.Month') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_payroll_all as $key => $payroll)
                        @php
                            $employee = \App\Models\Employee::find($payroll->employee_id);
                            $account = \App\Models\Account::find($payroll->account_id);
                            $monthDate = null;
                            if (!empty($payroll->month)) {
                                try {
                                    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $payroll->month);
                                } catch (\Exception $e) {
                                    $monthDate = null;
                                }
                            }
                        @endphp
                        <tr data-id="{{ $payroll->id }}" data-employee_id="{{ $payroll->employee_id }}"
                            data-month="{{ $payroll->month }}" data-date="{{ $payroll->created_at->format('Y-m-d') }}">
                            <td>{{ $key }}</td>
                            <td>{{ date(gen_setting()->date_format, strtotime($payroll->created_at->toDateString())) }}</td>
                            <td>{{ $payroll->reference_no }}</td>
                            <td>{{ $employee->name }}</td>
                            <td>{{ @$account->name }}</td>
                            <td>{{ number_format((float) $payroll->amount, gen_setting()->decimal, '.', '') }}</td>
                            @if ($payroll->paying_method == 0)
                                <td>{{ __('db.Cash') }}</td>
                            @elseif($payroll->paying_method == 1)
                                <td>{{ __('db.Cheque') }}</td>
                            @else
                                <td>{{ __('db.Credit Card') }}</td>
                            @endif
                            <td>{{ $monthDate ? $monthDate->format('F Y') : $payroll->month }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('db.action') }}
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>

                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">

                                        <!-- Edit Payroll -->
                                        <li>
                                            @php $amountArray = json_decode($payroll->amount_array, true); @endphp
                                            <button type="button" class="btn btn-link edit-btn"
                                                data-id="{{ $payroll->id }}"
                                                data-date="{{ $payroll->created_at->format('Y-m-d') }}"
                                                data-employee="{{ $payroll->employee_id }}"
                                                data-account="{{ $payroll->account_id }}"
                                                data-amount="{{ $payroll->amount }}"
                                                data-paying_method="{{ $payroll->paying_method }}"
                                                data-note="{{ $payroll->note }}"
                                                data-month="{{ $payroll->month }}"
                                                data-salary="{{ $amountArray['salary'] ?? 0 }}"
                                                data-commission="{{ $amountArray['commission'] ?? 0 }}"
                                                data-prev="{{ $amountArray['previous'] ?? 0 }}"
                                                data-toggle="modal"
                                                data-target="#editModal">
                                                <i class="ti ti-edit"></i> {{ __('db.Edit') }}
                                            </button>
                                        </li>

                                        <li>
                                            @php
                                                $amountArray = json_decode($payroll->amount_array, true);
                                                $payingMethodText = $payroll->paying_method == 0 ? 'Cash' : ($payroll->paying_method == 1 ? 'Cheque' : 'Credit Card');
                                            @endphp
                                            <button type="button" class="btn btn-link view-btn"
                                                data-id="{{ $payroll->id }}"
                                                data-employee_name="{{ $employee->name }}"
                                                data-leaves="{{ $payroll->leaves ?? 0 }}"
                                                data-work_duration="{{ $payroll->work_duration ?? 0 }}"
                                                data-attendance="{{ $payroll->attendance ?? 0 }}"
                                                data-month="{{ $monthDate ? $monthDate->format('F Y') : $payroll->month }}"
                                                data-salary="{{ $amountArray['salary'] ?? 0 }}"
                                                data-commission="{{ $amountArray['commission'] ?? 0 }}"
                                                data-transactions="{{ $amountArray['previous'] ?? 0 }}"
                                                data-amount="{{ $amountArray['total'] ?? $payroll->amount }}"
                                                data-paying_method_text="{{ $payingMethodText }}"
                                                data-note="{{ $payroll->note }}"
                                                data-toggle="modal"
                                                data-target="#viewModal">
                                                <i class="ti ti-eye"></i> {{ __('db.View') }}
                                            </button>
                                        </li>

                                        <li class="divider"></li>

                                        <!-- Delete -->
                                        <form action="{{ route('payroll.destroy', $payroll->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <li>
                                                <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                                    <i class="ti ti-trash"></i> {{ __('db.Delete') }}
                                                </button>
                                            </li>
                                        </form>

                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th>{{ __('db.Total') }}:</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>


    <!-- Create Payroll Modal -->
    <div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="italic text-muted">
                        <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                    </p>

                    <form action="{{ route('payroll.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">

                        {{-- Employee --}}
                        <div class="col-md-6 form-group">
                            <label>
                                {{ __('db.Employee') }} *
                                <x-info title="Select the employee for whom this payroll is being added" />
                            </label>
                            <select class="form-control selectpicker" name="employee_id" id="employee_id" required
                                data-live-search="true" title="Select Employee...">
                                @foreach ($lims_employee_list as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Month --}}
                        <div class="col-md-6 form-group">
                            <label>
                                {{ __('db.Month') }} *
                                <x-info title="Select the month for which payroll is being processed" />
                            </label>
                            <input type="month" name="month" id="monthSelect" class="form-control" required>
                        </div>

                        {{-- Salary Amount --}}
                        <div class="col-md-6 form-group">
                            <label>
                                {{ __('db.salary_amount') }}
                                <x-info title="Salary Amount = Basic Salary + (Allowances - Deductions)" />
                            </label>
                            <input type="number" step="any" name="salary_amount" id="salaryAmount" class="form-control">
                        </div>

                        {{-- Previous Transactions --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Expense') }} <x-info title="{{ __('db.loan_advance_expense') }}" /></label>
                            <input type="number" step="any" name="previous_transactions" id="previousTransactions" class="form-control">
                        </div>

                        {{-- Sale Commission --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.sale_commission') }} <x-info title="Sale Commission = (Total Sale × Target Commission %) / 100" /></label>
                            <input type="number" step="any" name="commission" id="commissionAmount" class="form-control">
                        </div>

                        {{-- Total Payable --}}
                        <div class="col-md-6 form-group">
                            <label>
                                {{ __('db.Total') }}
                                <x-info title="Total Payable = (Salary + Sale Commission) - Previous Transactions" />
                            </label>
                            <input type="number" step="any" name="amount" id="totalPayable" class="form-control">
                        </div>

                        {{-- Date --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.date') }}</label>
                            <input type="text" name="created_at" class="form-control date"
                                placeholder="{{ __('db.Choose date') }}" value="{{ date('d-m-Y') }}" />
                        </div>

                        {{-- Account --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Account') }} *</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach ($lims_account_list as $account)
                                    <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                                        {{ $account->name }} [{{ $account->account_no }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Payment Method --}}
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Method') }} *</label>
                            <select class="form-control selectpicker" name="paying_method" required>
                                <option value="0">{{ __('db.Cash') }}</option>
                                <option value="1">{{ __('db.Cheque') }}</option>
                                <option value="2">{{ __('db.Credit Card') }}</option>
                            </select>
                        </div>

                        {{-- Note --}}
                        <div class="col-md-12 form-group">
                            <label>{{ __('db.note') }}</label>
                            <textarea name="note" rows="3" class="form-control"
                                placeholder="Write any note about this payroll..."></textarea>
                        </div>

                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-checkmark"></i> {{ __('db.submit') }}
                        </button>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header">
                    <h5 id="editModalLabel" class="modal-title">
                        <i class="ti ti-wallet"></i> {{ __('db.Update Payroll') }}
                    </h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="italic text-muted">
                        <small>{{ __('db.The field labels marked with are required input fields') }}.</small>
                    </p>

                    <form action="{{ route('payroll.update', 1) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="payroll_id" id="editPayrollId">

                    <div class="row g-3">
                        <!-- Employee -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Employee') }} *</label>
                            <select class="form-control selectpicker" name="employee_id" id="editEmployee" required
                                data-live-search="true" title="Select Employee...">
                                @foreach ($lims_employee_list as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Month -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Month') }} *</label>
                            <input type="month" name="month" id="editMonth" class="form-control" required>
                        </div>

                        <!-- Salary Amount -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.salary_amount') }}</label>
                            <input type="number" step="any" name="salary_amount" id="editSalaryAmount"
                                class="form-control salary-input" data-emp="editEmp">
                        </div>

                        <!-- Previous Transactions -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.transactions') }}</label>
                            <input type="number" step="any" name="previous_transactions" id="editPreviousTransactions"
                                class="form-control prev-input" data-emp="editEmp">
                        </div>

                        <!-- Sale Commission -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.sale_commission') }}</label>
                            <input type="number" step="any" name="commission" id="editCommissionAmount"
                                class="form-control comm-input" data-emp="editEmp" data-percent="0">
                        </div>

                        <!-- Total Payable -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Total') }}</label>
                            <input type="number" step="any" name="amount" id="editTotalPayable"
                                class="form-control total-output" data-emp="editEmp" readonly>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.date') }}</label>
                            <input type="text" name="created_at" id="editDate" class="form-control date"
                                placeholder="{{ __('db.Choose date') }}">
                        </div>

                        <!-- Account -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Account') }} *</label>
                            <select class="form-control selectpicker" name="account_id" id="editAccount">
                                @foreach ($lims_account_list as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->name }} [{{ $account->account_no }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Method') }} *</label>
                            <select class="form-control selectpicker" name="paying_method" id="editPayingMethod" required>
                                <option value="0">{{ __('db.Cash') }}</option>
                                <option value="1">{{ __('db.Cheque') }}</option>
                                <option value="2">{{ __('db.Credit Card') }}</option>
                            </select>
                        </div>

                        <!-- Note -->
                        <div class="col-md-12 form-group">
                            <label>{{ __('db.note') }}</label>
                            <textarea name="note" id="editNote" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-checkmark"></i> {{ __('db.submit') }}
                        </button>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Payroll Modal -->
    <div id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header">
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <!-- Employee Info -->
                    <div class="mb-4 text-center">
                        <h4 id="viewEmployee" class="fw-bold">-----</h4>
                        <p class="text-muted mb-0">{{ __('db.payroll_attendance_overview') }}</p>
                    </div>

                    <div class="row text-center">
                        <!-- Leaves -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="text-muted">{{ __('db.Leaves') }}</h6>
                                    <h3 class="fw-bold text-danger" id="viewLeaves">0 {{ __('db.Days') }}</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Work Duration -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="text-muted">{{ __('db.Work Duration') }}</h6>
                                    <h3 class="fw-bold text-success" id="viewWorkDuration">0.00 {{ __('db.hour') }}</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="text-muted">{{ __('db.Attendance') }}</h6>
                                    <h3 class="fw-bold text-primary" id="viewAttendance">0 {{ __('db.Days') }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Info -->
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.Month') }}:</label>
                            <p id="viewMonth">--</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.salary_amount') }}:</label>
                            <p id="viewSalaryAmount">--</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.sale_commission') }}:</label>
                            <p id="viewCommissionAmount">--</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.Expense') }}:</label>
                            <p id="viewPreviousTransactions">--</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.Total Payable') }}:</label>
                            <p id="viewTotalPayable">--</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">{{ __('db.Payment Method') }}:</label>
                            <p id="viewPayingMethod">--</p>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-bold">{{ __('db.note') }}:</label>
                            <p id="viewNote">--</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">

        // ─── Flatpickr Month Pickers ─────────────────────────────────────────────────
        // Filter month picker (outside modal)
        flatpickr(".filter-month-picker", {
            defaultDate: "today",
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                })
            ]
        });

        // Modal month picker (inside modal)
        flatpickr(".modal-month-picker", {
            defaultDate: "today",
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                })
            ]
        });
        // ─────────────────────────────────────────────────────────────────────────────


        var payroll_id = [];
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

        // ─── DataTable ───────────────────────────────────────────────────────────────
        $('#payroll-table').DataTable({
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
                    'targets': [0, 1, 6]
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
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
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
                    exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                    action: function(e, dt, button, config) {
                        datatable_sum(dt, true);
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                        datatable_sum(dt, false);
                    },
                    footer: true
                },
                {
                    text: '<i title="delete" class="ti ti-x"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            payroll_id.length = 0;
                            $(':checkbox:checked').each(function(i) {
                                if (i) {
                                    payroll_id[i - 1] = $(this).closest('tr').data('id');
                                }
                            });
                            if (payroll_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'payroll/deletebyselection',
                                    data: { payrollIdArray: payroll_id },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove().draw(false);
                                            }
                                        });
                                        alert(data);
                                    }
                                });
                            } else if (!payroll_id.length) {
                                alert('No payroll is selected!');
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
            drawCallback: function() {
                var api = this.api();
                datatable_sum(api, false);
            }
        });

        function datatable_sum(dt_selector, is_calling_first) {
            if (dt_selector.rows('.selected').any() && is_calling_first) {
                var rows = dt_selector.rows('.selected').indexes();
                $(dt_selector.column(5).footer()).html(
                    dt_selector.cells(rows, 5, { page: 'current' }).data().sum().toFixed({{ gen_setting()->decimal }})
                );
            } else {
                $(dt_selector.column(5).footer()).html(
                    dt_selector.cells(rows, 5, { page: 'current' }).data().sum().toFixed({{ gen_setting()->decimal }})
                );
            }
        }
        // ─────────────────────────────────────────────────────────────────────────────


        // ─── Create Modal: Fetch Payroll Data ────────────────────────────────────────
        function fetchPayrollData() {
            let employee_id = $('#employee_id').val();
            let month = $('#monthSelect').val();

            if (employee_id && month) {
                $.ajax({
                    url: "{{ route('payroll.monthlyData') }}",
                    type: "GET",
                    data: { employee_id: employee_id, month: month },
                    success: function(data) {
                        $('#salaryAmount').val(data.salary);
                        $('#previousTransactions').val(data.transactions);
                        $('#commissionAmount').val(data.commission);
                        calculateTotal();
                    },
                    error: function() {
                        alert('Error loading payroll data!');
                    }
                });
            }
        }

        function calculateTotal() {
            let salary       = parseFloat($('#salaryAmount').val()) || 0;
            let transactions = parseFloat($('#previousTransactions').val()) || 0;
            let commission   = parseFloat($('#commissionAmount').val()) || 0;
            let total = (salary + commission) - transactions;
            $('#totalPayable').val(total.toFixed(2));
        }

        $('#employee_id, #monthSelect').on('change', function() {
            fetchPayrollData();
        });

        $('#salaryAmount, #previousTransactions, #commissionAmount').on('keyup change', function() {
            calculateTotal();
        });
        // ─────────────────────────────────────────────────────────────────────────────


        $(document).ready(function() {

            // ─── Load Employees via AJAX (selectpicker) ──────────────────────────────
            function loadEmployees(warehouse_id) {
                warehouse_id = warehouse_id || 0;

                $.ajax({
                    url: "{{ route('payroll.getEmployeesByWarehouse') }}",
                    type: "GET",
                    data: { warehouse_id: warehouse_id },
                    success: function(data) {
                        let $select = $('#employeeMultiple');

                        // Destroy existing selectpicker instance
                        try { $select.selectpicker('destroy'); } catch(e) {}

                        // Clear & rebuild options
                        $select.empty();

                        if (data.length > 0) {
                            $.each(data, function(i, emp) {
                                $select.append(new Option(emp.name, emp.id, false, false));
                            });
                            $select.prop('disabled', false);
                        } else {
                            $select.append(new Option('No employees available', '', false, false));
                            $select.prop('disabled', true);
                        }

                        // Re-initialize selectpicker
                        $select.selectpicker({
                            liveSearch: true,
                            actionsBox: true,
                            title: data.length > 0 ? '{{ __('db.select_employees') }}' : 'No employees available'
                        });
                    },
                    error: function() {
                        alert('Error loading employees!');
                    }
                });
            }

            // Load all employees on page ready
            loadEmployees(0);

            // Reload on warehouse change
            $('#warehouseSelect').on('change', function() {
                loadEmployees($(this).val() || 0);
            });

            // Select All
            $('#selectAllEmployees').on('click', function() {
                let $select = $('#employeeMultiple');
                if (!$select.prop('disabled')) {
                    $select.find('option').prop('selected', true);
                    $select.selectpicker('refresh');
                }
            });

            // Deselect All
            $('#deselectAllEmployees').on('click', function() {
                let $select = $('#employeeMultiple');
                if (!$select.prop('disabled')) {
                    $select.find('option').prop('selected', false);
                    $select.selectpicker('refresh');
                }
            });
            // ─────────────────────────────────────────────────────────────────────────


            // ─── View Modal ───────────────────────────────────────────────────────────
            $(document).on('click', '.view-btn', function() {
                let payroll = $(this).data();
                $('#viewEmployee').text(payroll.employee_name);
                $('#viewLeaves').text(payroll.leaves + ' days');
                $('#viewWorkDuration').text(payroll.work_duration + ' hour');
                $('#viewAttendance').text(payroll.attendance + ' Days');
                $('#viewMonth').text(payroll.month);
                $('#viewSalaryAmount').text(payroll.salary);
                $('#viewCommissionAmount').text(payroll.commission);
                $('#viewPreviousTransactions').text(payroll.transactions);
                $('#viewTotalPayable').text(payroll.amount);
                $('#viewPayingMethod').text(payroll.paying_method_text);
                $('#viewNote').text(payroll.note || '--');
            });
            // ─────────────────────────────────────────────────────────────────────────


            // ─── Edit Modal ───────────────────────────────────────────────────────────
            function calculateEditTotal(empId) {
                let salary     = parseFloat($(`input.salary-input[data-emp='${empId}']`).val()) || 0;
                let prev       = parseFloat($(`input.prev-input[data-emp='${empId}']`).val()) || 0;
                let commission = parseFloat($(`input.comm-input[data-emp='${empId}']`).val()) || 0;

                // commission in edit modal is stored as a direct amount (not percent)
                let total = salary + commission - prev;
                $(`input.total-output[data-emp='${empId}']`).val(total.toFixed(2));
            }

            $('#editSalaryAmount, #editPreviousTransactions, #editCommissionAmount').on('input', function() {
                calculateEditTotal($(this).data('emp'));
            });

            $(document).on('click', '.edit-btn', function() {
                let empId = 'editEmp';

                $('#editPayrollId').val($(this).data('id'));
                $('#editEmployee').val($(this).data('employee')).selectpicker('refresh');
                $('#editMonth').val($(this).data('month'));
                $('#editSalaryAmount').val($(this).data('salary')).data('emp', empId);
                $('#editPreviousTransactions').val($(this).data('prev')).data('emp', empId);
                $('#editCommissionAmount').val($(this).data('commission')).data('emp', empId);
                $('#editTotalPayable').data('emp', empId);
                $('#editDate').val($(this).data('date'));
                $('#editAccount').val($(this).data('account')).selectpicker('refresh');
                $('#editPayingMethod').val($(this).data('paying_method')).selectpicker('refresh');
                $('#editNote').val($(this).data('note'));

                calculateEditTotal(empId);
            });
            // ─────────────────────────────────────────────────────────────────────────


            // ─── Filter DataTable ─────────────────────────────────────────────────────
            $('#filterEmployee, #filterMonth, #filterDate').on('change keyup', function() {
                let employee = $('#filterEmployee').val();
                let month    = $('#filterMonth').val();
                let date     = $('#filterDate').val();

                let table = $('#payroll-table').DataTable();

                table.rows().every(function() {
                    let row     = this.node();
                    let rowData = $(row).data();
                    let show    = true;

                    if (employee && rowData.employee_id != employee) show = false;
                    if (month && rowData.month != month) show = false;
                    if (date && rowData.date != date) show = false;

                    $(row).toggle(show);
                });
            });
            // ─────────────────────────────────────────────────────────────────────────

        });
    </script>
@endpush
