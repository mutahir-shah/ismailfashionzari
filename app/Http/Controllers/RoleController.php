<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Roles;
use Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Traits\TenantInfo;

class RoleController extends Controller
{
    use TenantInfo;
    public function index()
    {
        if(Auth::user()->role_id <= 2) {
            $lims_role_all = Roles::where('is_active', true)->get();
            return view('backend.role.create', compact('lims_role_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('roles')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $data = $request->all();
        Roles::create($data);
        return redirect('role')->with('message', __('db.Data inserted successfully'));
    }

    public function edit($id)
    {
        if(Auth::user()->role_id <= 2) {
            $lims_role_data = Roles::find($id);
            return $lims_role_data;
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'max:255',
                Rule::unique('roles')->ignore($request->role_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
        ]);

        $input = $request->all();
        $lims_role_data = Roles::where('id', $input['role_id'])->first();
        $lims_role_data->update($input);
        return redirect('role')->with('message', __('db.Data updated successfully'));
    }

    public function permission($id)
    {
        $all_features = $this->features();
        if(Auth::user()->role_id <= 2) {
            $lims_role_data = Roles::find($id);
            $permissions = Role::findByName($lims_role_data->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';
            return view('backend.role.permission', compact('lims_role_data', 'all_permission', 'all_features'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function setPermission(Request $request)
    {
        if(!config('app.user_verified'))
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));

        $lims_permissions = Permission::pluck('name')->toArray();

        $selected_permissions = array_keys($request->except('_token', 'role_id', 'permissions'));
        $selected_permissions = array_merge($selected_permissions, (array) $request->input('permissions', []));
        $selected_permissions = array_unique($selected_permissions);

        $lims_new_request_permissions = array_diff(
            $selected_permissions,
            $lims_permissions
        );

        $lims_permissions = array_merge($lims_permissions, $lims_new_request_permissions);
        $lims_permissions = array_unique($lims_permissions);

        $role = Role::firstOrCreate(['id' => $request['role_id']]);

        foreach ($lims_permissions as $permission_name) {
            $permission = Permission::firstOrCreate(['name' => $permission_name]);

            if(in_array($permission_name, $selected_permissions)) {
                if(!$role->hasPermissionTo($permission_name)) {
                    $role->givePermissionTo($permission);
                }
            }
            else {
                if($permission) $role->revokePermissionTo($permission_name);
            }
        }

        cache()->forget('permissions');
        cache()->forget('role_has_permissions_list' . $request['role_id']);
        app(\App\Services\PermissionService::class)->clearRolePermissionsCache($request['role_id']);

        return redirect('role')->with('message', __('db.Permission updated successfully'));
    }

    public function destroy($id)
    {
        if(!config('app.user_verified'))
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        $lims_role_data = Roles::find($id);
        $lims_role_data->is_active = false;
        $lims_role_data->save();
        return redirect('role')->with('not_permitted', __('db.Data deleted successfully'));
    }
}
