<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use Auth;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SaleAgentController extends Controller
{
    use TenantInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);

        if ($role && $role->hasPermissionTo('sale-agents')) {

            // Get all permission names for current role
            $permissions = Role::findByName($role->name)->permissions;
            $all_permission = [];
            foreach ($permissions as $permission) {
                $all_permission[] = $permission->name;
            }
            if (empty($all_permission)) {
                $all_permission[] = 'dummy text';
            }

            // Sale agents (employees flagged as sale agents)
            $lims_sale_agent_all = Employee::with('user')
                ->where('is_active', true)
                ->where('is_sale_agent', 1)
                ->get();

            // Auxiliary lists used by the blade (create / edit forms)
            $lims_department_list = Department::where('is_active', true)->get();
            $lims_role_list       = Role::where('is_active', true)->where('id', '!=', 5)->get();
            $lims_warehouse_list  = \App\Models\Warehouse::where('is_active', true)->get();
            $lims_biller_list     = \App\Models\Biller::where('is_active', true)->get();
            $lims_shift_list      = \App\Models\Shift::where('is_active', true)->get();
            $lims_designation_list= \App\Models\Designation::where('is_active', true)->get();

            $numberOfEmployee = Employee::where('is_active', true)->count();

            return view('backend.hrm.sale_agent.index', compact(
                'lims_role_list',
                'lims_sale_agent_all',
                'lims_department_list',
                'lims_warehouse_list',
                'lims_biller_list',
                'lims_shift_list',
                'lims_designation_list',
                'all_permission',
                'numberOfEmployee'
            ));
        } else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }


    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('employees-add')){
            $lims_role_list = Role::where('is_active', true)->where('id','!=',5)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_department_list = Department::where('is_active', true)->get();
            $numberOfEmployee = Employee::where('is_active', true)->count();
            $numberOfUserAccount = User::where('is_active', true)->count();


            return view('backend.hrm.sale_agent.create', compact('lims_role_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_department_list', 'numberOfEmployee', 'numberOfUserAccount'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }
    public function store(Request $request)
    {
        
        try {
            $data = $request->except('image');
            $message = 'Sale Agent created successfully';

            if (isset($data['user'])) {
                $this->validate($request, [
                    'name' => [
                        'max:255',
                        Rule::unique('users')->where(function ($query) {
                            return $query->where('is_deleted', false);
                        }),
                    ],
                    'email' => [
                        'email',
                        'max:255',
                        Rule::unique('users')->where(function ($query) {
                            return $query->where('is_deleted', false);
                        }),
                    ],
                    'role_id' => 'required|exists:roles,id', // added role validation
                ]);

                $data['is_active'] = true;
                $data['is_deleted'] = false;
                $data['password'] = bcrypt($data['password']);
                $data['phone'] = $data['phone_number'];

                app(\App\Services\WarehouseAccessService::class)->assertValidUserAssignment(
                    (int) $data['role_id'],
                    $data['warehouse_id'] ?? null
                );

                $user = User::create($data);
                $user = User::latest()->first();
                $data['user_id'] = $user->id;
                $message = 'Employee created successfully and added to user list';
            }

            // Validation in employee table
            $this->validate($request, [
                'email' => [
                    'max:255',
                    Rule::unique('employees')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            $image = $request->image;
            if ($image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis");

                if (!config('database.connections.saleprosaas_landlord')) {
                    $imageName = $imageName . '.' . $ext;
                    $image->move(public_path('images/sale_agent'), $imageName);
                } else {
                    $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    $image->move(public_path('images/sale_agent'), $imageName);
                }

                $data['image'] = $imageName;
            }

            $data['name'] = $data['name'];
            $data['is_active'] = true;
            $data['is_sale_agent'] = 1;
            $store = Employee::create($data);

            return redirect('sale-agents')->with('message', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();

        } catch (\Exception $e) {
            Log::error('Sale Agent Store Error: '.$e->getMessage());
            return redirect()->back()
                ->with('error', 'Something went wrong: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function update(Request $request, $id)
    {
        $lims_employee_data = Employee::find($request['employee_id']);
        if($lims_employee_data->user_id){
            $this->validate($request, [
                'name' => [
                    'max:255',
                    Rule::unique('users')->ignore($lims_employee_data->user_id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
                'email' => [
                    'email',
                    'max:255',
                        Rule::unique('users')->ignore($lims_employee_data->user_id)->where(function ($query) {
                        return $query->where('is_deleted', false);
                    }),
                ],
            ]);
        }
        //validation in employee table
        $this->validate($request, [
            'email' => [
                'email',
                'max:255',
                    Rule::unique('employees')->ignore($lims_employee_data->id)->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $data = $request->except('image');
        if ($lims_employee_data->user_id) {
            $linkedUser = User::find($lims_employee_data->user_id);
            $warehouseId = $data['warehouse_id'] ?? $lims_employee_data->warehouse_id;
            $roleId = (int) ($data['role_id'] ?? $linkedUser->role_id);
            app(\App\Services\WarehouseAccessService::class)->assertValidUserAssignment($roleId, $warehouseId, false, $linkedUser);
            $linkedUser->warehouse_id = $warehouseId;
            $linkedUser->role_id = $roleId;
            $linkedUser->save();
        }
        $image = $request->image;
        if ($image) {
            $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/employee'), $imageName);
            }
            $data['image'] = $imageName;
        }
        $lims_employee_data->is_sale_agent = 1;
        $lims_employee_data->update($data);
        return redirect('sale-agents')->with('message', __('db.Employee updated successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $employee_id = $request['employeeIdArray'];
        foreach ($employee_id as $id) {
            $lims_employee_data = Employee::find($id);
            if($lims_employee_data->user_id){
                $lims_user_data = User::find($lims_employee_data->user_id);
                $lims_user_data->is_deleted = true;
                $lims_user_data->save();
            }
            $lims_employee_data->is_active = false;
            $lims_employee_data->save();

            $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);
        }

        return 'Employee deleted successfully!';
    }


    public function destroy($id)
    {
        $lims_employee_data = Employee::find($id);
        if($lims_employee_data->user_id){
            $lims_user_data = User::find($lims_employee_data->user_id);
            $lims_user_data->is_deleted = true;
            $lims_user_data->save();
        }

        $this->fileDelete(public_path('images/employee/'), $lims_employee_data->image);

        // if($lims_employee_data->image && !config('database.connections.saleprosaas_landlord')) {
        //     unlink('images/employee/'.$lims_employee_data->image);
        // }
        // elseif($lims_employee_data->image) {
        //     unlink('images/employee/'.$lims_employee_data->image);
        // }

        $lims_employee_data->is_active = false;
        $lims_employee_data->save();
        return redirect('sale-agents')->with('not_permitted', __('db.Employee deleted successfully'));
    }
}
