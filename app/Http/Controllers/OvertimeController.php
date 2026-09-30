<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class OvertimeController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
            if ($role->hasPermissionTo('overtime')) {
                $overtimes = Overtime::with('employee')->get();
                $employees = Employee::all();
                return view('backend.hrm.overtime.index', compact('overtimes', 'employees'));
            } else {
                return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
            }
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0'
        ]);
        $data = $request->all();
        $data['date'] =  date("Y-m-d", strtotime(str_replace("/", "-", $request->input('date'))));
        Overtime::create($data);
        return redirect()->back()->with('message', 'Overtime added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected'
        ]);
        $data = $request->all();
        $data['date'] =  date("Y-m-d", strtotime(str_replace("/", "-", $request->input('date'))));
        $overtime = Overtime::findOrFail($id);
        $overtime->update($data);

        return redirect()->back()->with('message', 'Overtime updated successfully');
    }

    public function destroy($id)
    {
        $overtime = Overtime::findOrFail($id);
        $overtime->delete();
        return redirect()->back()->with('message', 'Overtime deleted successfully');
    }
}
