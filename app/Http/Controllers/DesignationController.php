<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Designation;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;

class DesignationController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('department')) {
            $lims_designation_all = Designation::where('is_active', true)->get();
            return view('backend.hrm.designation.index', compact('lims_designation_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('designations')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        $data['is_active'] = true;
        $designation = Designation::create($data);
        if($request->ajax()){
            return response()->json($designation);
        }
        return redirect('designations')->with('message', __('db.designation_created_successfully'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'name' => [
                'max:255',
                Rule::unique('designations')->ignore($request->designation_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        $lims_designation_data = Designation::find($data['designation_id']);
        $lims_designation_data->update($data);
        return redirect('designations')->with('message', __('db.designation_updated_successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $designation_id = $request['designationIdArray'];
        foreach ($designation_id as $id) {
            $lims_designation_data = Designation::find($id);
            $lims_designation_data->is_active = false;
            $lims_designation_data->save();
        }
        return 'Designation deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_designation_data = Designation::find($id);
        $lims_designation_data->is_active = false;
        $lims_designation_data->save();
        return redirect('designations')->with('message', __('db.designation_deleted_successfully'));
    }
}
