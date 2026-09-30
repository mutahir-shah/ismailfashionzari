<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\HrmSetting;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class HRMController extends Controller
{
    /**
     * Show the HRM panel main page with tabs
     */
    public function index()
    {
        return view('backend.hrm.panel');
    }

    /**
     * Attendance tab content
     */
    public function attendance()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('attendance')) {
            $lims_employee_list = Employee::where('is_active', true)->get();
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $general_setting = DB::table('general_settings')->latest()->first();
            if(Auth::user()->role_id > 2 && $general_setting->staff_access == 'own')
            $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                ->orderBy('attendances.date', 'desc')
                ->where('attendances.user_id', Auth::id())
                ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                ->get()
                ->groupBy(['date','employee_id']);
            else
            $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                ->orderBy('attendances.date', 'desc')
                ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                ->get()
                ->groupBy(['date','employee_id']);

            $lims_attendance_all= [];
            foreach ($lims_attendance_data as  $attendance_data) {
                foreach ($attendance_data as $data) {
                    $checkin_checkout = '';
                    foreach ($data as $key => $dt) {
                        $date = $dt->date;
                        $employee_name = $dt->employee_name;
                        $checkin_checkout .= (($dt->checkin != null) ? $dt->checkin : 'N/A'). ' - ' .(($dt->checkout != null) ? $dt->checkout : 'N/A'). '<br>';
                        $status = $dt->status;
                        $user_name = $dt->user_name;
                        $employee_id = $dt->employee_id;
                    }
                    $lims_attendance_all[] = ['date'=>$date, 'employee_name'=>$employee_name,
                                            'checkin_checkout'=>$checkin_checkout, 'status'=>$status,
                                            'user_name'=>$user_name, 'employee_id'=>$employee_id];
                }
            }
            return view('backend.hrm.panel.attendance', compact('lims_employee_list', 'lims_hrm_setting_data', 'lims_attendance_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    /**
     * Leave tab content
     */
    public function leave()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('leave')) {
            $leaves = Leave::with(['employee', 'leaveType'])->latest()->get();
            $leaveTypes = LeaveType::all();
            $employees = Employee::all();
            return view('backend.hrm.panel.leave', compact('leaves', 'leaveTypes', 'employees'));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    /**
     * Holiday tab content
     */
    public function holiday()
    {
        $data = [
            ['name' => 'New Year', 'start_date' => now()->startOfYear()->format('Y-m-d'), 'end_date' => now()->startOfYear()->addDay()->format('Y-m-d'), 'region' => 'All'],
            ['name' => 'Independence Day', 'start_date' => '2025-03-26', 'end_date' => '2025-03-26', 'region' => 'All'],
        ];

        return view('backend.hrm.holiday', compact('data'));
    }

    /**
     * Payroll tab content
     */
    public function payroll()
    {
        $data = [
            ['employee_name' => 'John Doe', 'month' => now()->format('F Y'), 'amount' => 5000, 'status' => 'Paid'],
            ['employee_name' => 'Jane Smith', 'month' => now()->format('F Y'), 'amount' => 5000, 'status' => 'Paid'],
        ];

        return view('backend.hrm.payroll', compact('data'));
    }

    /**
     * Transaction tab content
     */
    public function transaction()
    {
        $data = [
            ['employee_name' => 'John Doe', 'type' => 'Salary', 'amount' => 5000, 'date' => now()->format('Y-m-d')],
            ['employee_name' => 'Jane Smith', 'type' => 'Bonus', 'amount' => 1000, 'date' => now()->format('Y-m-d')],
        ];

        return view('backend.hrm.transaction', compact('data'));
    }

    /**
     * Report tab content
     */
    public function report()
    {
        $report_summary = [
            'total_employees' => 10,
            'total_present_today' => 8,
            'total_leave_today' => 2,
        ];

        return view('backend.hrm.report', compact('report_summary'));
    }

    /**
     * HRM Settings tab content
     */
    public function settings()
    {
        $settings = [
            'attendance_start' => '09:00',
            'attendance_end' => '17:00',
            'default_leave_days' => 20,
        ];

        return view('backend.hrm.settings', compact('settings'));
    }
}
