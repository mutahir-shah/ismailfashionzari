<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Shift;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    use TenantInfo;

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('employees-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';


            $lims_employee_all = Employee::where('is_active', true)->with(['user'])->get();
            $lims_department_list = Department::where('is_active', true)->get();
            $numberOfEmployee = $lims_employee_all->count();
            $lims_shift_list = Shift::where('is_active', true)->get();
            $lims_designation_list = Designation::active()->get();
            $lims_role_list = Role::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();

            return view('backend.employee.index', compact(
                'lims_biller_list',
                'lims_warehouse_list',
                'lims_role_list',
                'lims_designation_list',
                'lims_shift_list',
                'lims_employee_all',
                'lims_department_list',
                'all_permission',
                'numberOfEmployee'
            ));
        }
        else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('employees-add')){
            $lims_role_list = Role::where('is_active', true)->get();
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $lims_biller_list = Biller::where('is_active', true)->get();
            $lims_department_list = Department::where('is_active', true)->get();
            $lims_shift_list = Shift::where('is_active', true)->get();
            $lims_designation_list = Designation::active()->get();

            $numberOfEmployee = Employee::where('is_active', true)->count();
            $numberOfUserAccount = User::where('is_active', true)->count();

            return view('backend.employee.create', compact(
                'lims_role_list',
                'lims_warehouse_list',
                'lims_biller_list',
                'lims_department_list',
                'numberOfEmployee',
                'numberOfUserAccount',
                'lims_shift_list',
                'lims_designation_list'
            ));
        }
        else {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
    }

    public function store(Request $request)
    {
        $data = $request->except('image');
        $message = 'Employee created successfully';

        $data['name'] = $data['employee_name'];
        $data['is_active'] = $request->is_active ?? true;

        // Handle user creation if checkbox selected
        if(isset($data['user'])){
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
            ]);

            $data['is_deleted'] = false;
            $data['password'] = bcrypt($data['password']);
            $data['phone'] = $data['phone_number'];

            app(\App\Services\WarehouseAccessService::class)->assertValidUserAssignment(
                (int) ($data['role_id'] ?? 0),
                $data['warehouse_id'] ?? null
            );

            User::create($data);
            $user = User::latest()->first();
            $data['user_id'] = $user->id;
            $message = 'Employee created successfully and added to user list';
        }

        // Validation for employee table
        $this->validate($request, [
            'email' => [
                'max:255',
                Rule::unique('employees')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        // Handle employee image upload
        $image = $request->image;
        if ($image) {
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

        $isSaleAgent = $data['is_sale_agent'] ?? 0;

        $employee = Employee::create([
            'name'                  => $data['name'],
            'email'                 => $data['email'] ?? null,
            'phone_number'          => $data['phone_number'] ?? null,
            'address'               => $data['address'] ?? null,
            'city'                  => $data['city'] ?? null,
            'country'               => $data['country'] ?? null,
            'basic_salary'          => $data['basic_salary'] ?? null,
            'staff_id'              => $data['staff_id'] ?? null,
            'department_id'         => $data['department_id'] ?? null,
            'shift_id'              => $data['shift_id'] ?? null,
            'designation_id'        => $data['designation_id'] ?? null,
            'role_id'               => $data['role_id'] ?? null,
            'warehouse_id'          => $data['warehouse_id'] ?? null, // ← warehouse_id
            'biller_id'             => $data['biller_id'] ?? null,
            'user_id'               => $data['user_id'] ?? null,
            'image'                 => $data['image'] ?? null,
            'is_active'             => $request->is_active ?? true,
            'is_sale_agent'         => $isSaleAgent,
            'sales_target'          => $isSaleAgent == 1 ? ($data['sales_target'] ?? null) : null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'id' => $employee->id,
                'name' => $employee->name,
                'message' => $message
            ]);
        }

        if($isSaleAgent){
            return redirect('sale-agents')->with('message', $message);
        }

        return redirect('employees')->with('message', $message);
    }

    public function update(Request $request, $id)
    {
        $lims_employee_data = Employee::find($request->employee_id);

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

        $this->validate($request, [
            'email' => [
                'email',
                'max:255',
                Rule::unique('employees')->ignore($lims_employee_data->id)->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
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
        // Handle image update
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
        $lims_employee_data->update([
            'name'           => $data['name'],
            'email'          => $data['email'] ?? $lims_employee_data->email,
            'phone_number'   => $data['phone_number'] ?? $lims_employee_data->phone_number,
            'address'        => $data['address'] ?? $lims_employee_data->address,
            'city'           => $data['city'] ?? $lims_employee_data->city,
            'country'        => $data['country'] ?? $lims_employee_data->country,
            'basic_salary'   => $data['basic_salary'] ?? $lims_employee_data->basic_salary,
            'staff_id'       => $data['staff_id'] ?? $lims_employee_data->staff_id,
            'department_id'  => $data['department_id'] ?? $lims_employee_data->department_id,
            'shift_id'       => $data['shift_id'] ?? $lims_employee_data->shift_id,
            'designation_id' => $data['designation_id'] ?? $lims_employee_data->designation_id,
            'role_id'        => $data['role_id'] ?? $lims_employee_data->role_id,
            'warehouse_id'   => $data['warehouse_id'] ?? $lims_employee_data->warehouse_id, // ← warehouse_id
            'biller_id'      => $data['biller_id'] ?? $lims_employee_data->biller_id,
            'user_id'        => $data['user_id'] ?? $lims_employee_data->user_id,
            'image'          => $data['image'] ?? $lims_employee_data->image,
            'is_active'      => $request->is_active ?? $lims_employee_data->is_active,
        ]);
        return redirect('employees')->with('message', __('db.Employee updated successfully'));
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

        $lims_employee_data->is_active = false;
        $lims_employee_data->save();
        return redirect('employees')->with('not_permitted', __('db.Employee deleted successfully'));
    }
}
