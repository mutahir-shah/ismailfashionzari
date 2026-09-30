<?php
    $department_active     = $role_has_permissions_list->where('name', 'department')->first();
    $designations_active   = $role_has_permissions_list->where('name', 'designations')->first();
    $shift_active          = $role_has_permissions_list->where('name', 'shift')->first();
    $overtime_active       = $role_has_permissions_list->where('name', 'overtime')->first();
    $leave_type_active     = $role_has_permissions_list->where('name', 'leave-type')->first();
    $leave_active          = $role_has_permissions_list->where('name', 'leave')->first();
    $hrm_panel_active      = $role_has_permissions_list->where('name', 'hrm-panel')->first();
    $sale_agent_active     = $role_has_permissions_list->where('name', 'sale-agents')->first();

    $index_employee_active = $role_has_permissions_list->where('name', 'employees-index')->first();
    $attendance_active     = $role_has_permissions_list->where('name', 'attendance')->first();
    $payroll_active        = $role_has_permissions_list->where('name', 'payroll')->first();
    $holiday_active        = $role_has_permissions_list->where('name', 'holiday')->first();
?>

<section class="no-print mb-4">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border rounded shadow-sm mt-3 mx-3">
        <div class="container-fluid" style="background: #fffcf2">

            <a class="navbar-brand font-weight-bold text-primary" href="{{ route('hrm-panel') }}">
                <i class="ti ti-wallet mr-2"></i> {{ __("db.HRM") }}
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#hrmNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="hrmNavbar">
                <ul class="navbar-nav">

                    @if($index_employee_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('employees.index') ? 'active' : '' }}"
                           href="{{ route('employees.index') }}">
                            <i class="ti ti-clock mr-1"></i> {{ __("db.Employees") }}
                        </a>
                    </li>
                    @endif
                    @if($payroll_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payroll.index') ? 'active' : '' }}"
                           href="{{ route('payroll.index') }}">
                            <i class="ti ti-wallet mr-1"></i> {{ __("db.Payroll") }}
                        </a>
                    </li>
                    @endif

                    @if($attendance_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}"
                           href="{{ route('attendance.index') }}">
                            <i class="ti ti-time-reverse mr-1"></i> {{ __("db.Attendance") }}
                        </a>
                    </li>
                    @endif

                    @if($leave_type_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('leave-type.index') ? 'active' : '' }}"
                           href="{{ route('leave-type.index') }}">
                            <i class="ti ti-briefcase mr-1"></i> {{ __("db.Leave Type") }}
                        </a>
                    </li>
                    @endif

                    @if($leave_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('leave.index') ? 'active' : '' }}"
                           href="{{ route('leave.index') }}">
                            <i class="ti ti-to-do mr-1"></i> {{ __("db.Leaves") }}
                        </a>
                    </li>
                    @endif

                    @if($holiday_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('holidays.index') ? 'active' : '' }}"
                           href="{{ route('holidays.index') }}">
                            <i class="ti ti-calendar mr-1"></i> {{ __("db.Holiday") }}
                        </a>
                    </li>
                    @endif

                    @if($shift_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shift.index') ? 'active' : '' }}"
                           href="{{ route('shift.index') }}">
                            <i class="ti ti-calendar mr-1"></i> {{ __("db.Shift") }}
                        </a>
                    </li>
                    @endif

                    @if($overtime_active)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('overtime.index') ? 'active' : '' }}"
                           href="{{ route('overtime.index') }}">
                            <i class="ti ti-briefcase mr-1"></i> {{ __("db.Overtime") }}
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</section>

<style>
    section {
    padding: 0px !important;
}
.navbar-nav .nav-link {
    color: #444;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 5px;
}
.navbar-nav .nav-link:hover {
    background-color: #f5f7ff;
    color: #007bff;
}
.navbar-nav .nav-link.active {
    color: #0798bd !important;
    font-weight: 700;
}
</style>
