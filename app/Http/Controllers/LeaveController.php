<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class LeaveController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('leave')) {
            $leaves = Leave::with(['employee', 'leaveType'])->latest()->get();
            $leaveTypes = LeaveType::all();
            $employees = Employee::query()->where('is_active',1)->get();
            return view('backend.hrm.leave.index', compact('leaves', 'leaveTypes', 'employees'));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_types' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $days = (strtotime($request->end_date) - strtotime($request->start_date)) / 86400 + 1;

        Leave::create([
            'employee_id' => $request->employee_id,
            'leave_types' => $request->leave_types,
            'start_date' =>  date("Y-m-d", strtotime(str_replace("/", "-", $request->input('start_date')))),
            'end_date' => date("Y-m-d", strtotime(str_replace("/", "-", $request->input('end_date')))),
            'days' => $days,
            'status' => 'Pending',
            'approver_id' => Auth::id()
        ]);

        return redirect()->back()->with('message', __('db.leave_request_added_successfully'));
    }

    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        // Check if status dropdown submitted
        if ($request->has('status')) {
            $leave->update([
                'status' => $request->status,
                'approver_id' => Auth::id()
            ]);
        } else {
            // Update via edit modal
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_types' => 'required|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            $days = (strtotime($request->end_date) - strtotime($request->start_date)) / 86400 + 1;

            $leave->update([
                'employee_id' => $request->employee_id,
                'leave_types' => $request->leave_types,
                'start_date' =>  date("Y-m-d", strtotime(str_replace("/", "-", $request->input('start_date')))),
                'end_date' => date("Y-m-d", strtotime(str_replace("/", "-", $request->input('end_date')))),
                'days' => $days,
            ]);

        }

        return redirect()->back()->with('message', __('db.leave_updated_successfully'));
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->delete();
        return redirect()->back()->with('message', __('db.leave_deleted_successfully'));
    }

    // Bulk delete via AJAX
    public function deleteBySelection(Request $request)
    {
        $ids = $request->leaveIdArray;
        Leave::whereIn('id', $ids)->delete();
        return response()->json(['message' => __('db.selected_leaves_deleted_successfully')]);
    }
}
