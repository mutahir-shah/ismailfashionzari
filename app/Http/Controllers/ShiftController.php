<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\HrmSetting;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Auth;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('shift')) {
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $lims_shift_all = Shift::where('is_active', true)->get();

            $lims_hrm_setting_data = HrmSetting::latest()->first();
            return view('backend.hrm.shift.index', compact('lims_shift_all','lims_hrm_setting_data'));

        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'max:255',
                Rule::unique('shifts')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        $data['start_time'] = Carbon::parse($request->start_time)->format('H:i:s');
        $data['end_time']   = Carbon::parse($request->end_time)->format('H:i:s');
        $data['grace_in'] = $request->grace_in ?? 0;
        $data['grace_out'] = $request->grace_out ?? 0;
        $shift = Shift::create($data);
        if($request->ajax()){
             return response()->json($shift);
        }

        return redirect('shift')->with('message', __('db.shift_created_successfully'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'max:255',
                Rule::unique('shifts')->ignore($request->shift_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        $data['start_time'] = Carbon::parse($request->start_time)->format('H:i:s');
        $data['end_time']   = Carbon::parse($request->end_time)->format('H:i:s');
        $data['grace_in'] = $request->grace_in ?? 0;
        $data['grace_out'] = $request->grace_out ?? 0;
        $data['is_active'] = true;
        $lims_shift_data = Shift::find($data['shift_id']);
        $lims_shift_data->update($data);

        return redirect('shift')->with('message', __('db.shift_updated_successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $shift_id = $request['shiftIdArray'];
        foreach ($shift_id as $id) {
            $lims_shift_data = Shift::find($id);
            $lims_shift_data->is_active = false;
            $lims_shift_data->save();
        }
        return 'Shift deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_shift_data = Shift::find($id);
        $lims_shift_data->is_active = false;
        $lims_shift_data->save();
        return redirect('shift')->with('message', __('db.shift_deleted_successfully'));
    }
}
