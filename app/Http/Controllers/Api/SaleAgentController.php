<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Department;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Auth;
use DB;

class SaleAgentController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $search = $request->input('search', '');

            $query = Employee::with('user', 'department', 'warehouse')
                ->where('is_active', true)
                ->where('is_sale_agent', 1);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%");
                });
            }

            $agents = $query->orderBy('id', 'desc')->paginate($limit);

            $rows = $agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'phone' => $agent->phone_number,
                    'address' => $agent->address,
                    'department' => $agent->department->name ?? 'N/A',
                    'image_url' => $agent->image ? url('images/sale_agent', $agent->image) : null,
                ];
            });

            return $this->withDashBackground([
                'title' => 'Sale Agent List',
                'add_url' => '/sale-agents/create',
                'columns' => [
                    ['label' => 'Image', 'field' => 'image_url', 'type' => 'image'],
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
                    ['label' => 'Phone', 'field' => 'phone', 'type' => 'text'],
                    ['label' => 'Department', 'field' => 'department', 'type' => 'text'],
                    ['label' => 'Address', 'field' => 'address', 'type' => 'text'],
                    ['label' => 'Action', 'type' => 'action', 'action' => [
                        'type' => 'delete',
                        'api_url' => '/sale-agents/{id}',
                    ]],
                ],
                'rows' => $rows
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        $roles = Role::where('is_active', true)->where('id', '!=', 5)->get()->map(function ($r) {
            return ['label' => $r->name, 'value' => $r->id];
        });
        $warehouses = Warehouse::where('is_active', true)->get()->map(function ($w) {
            return ['label' => $w->name, 'value' => $w->id];
        });
        $billers = Biller::where('is_active', true)->get()->map(function ($b) {
            return ['label' => $b->name, 'value' => $b->id];
        });
        $departments = Department::where('is_active', true)->get()->map(function ($d) {
            return ['label' => $d->name, 'value' => $d->id];
        });

        $formSchema = [
            'title' => 'Add Sale Agent',
            'submit_url' => '/sale-agents',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => 'Name',
                    'placeholder' => 'Enter name',
                ],
                [
                    'type' => 'file',
                    'name' => 'image',
                    'label' => 'Image',
                ],
                [
                    'type' => 'text',
                    'name' => 'email',
                    'label' => 'Email',
                    'keyboard_type' => 'email',
                ],
                [
                    'type' => 'text',
                    'name' => 'phone_number',
                    'label' => 'Phone Number',
                    'keyboard_type' => 'phone',
                ],
                [
                    'type' => 'text',
                    'name' => 'address',
                    'label' => 'Address',
                ],
                [
                    'type' => 'text',
                    'name' => 'city',
                    'label' => 'City',
                ],
                [
                    'type' => 'text',
                    'name' => 'country',
                    'label' => 'Country',
                ],
                [
                    'type' => 'select',
                    'name' => 'department_id',
                    'label' => 'Department',
                    'options' => $departments,
                ],
                [
                    'type' => 'select',
                    'name' => 'warehouse_id',
                    'label' => 'Warehouse',
                    'options' => $warehouses,
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'user',
                    'label' => 'Add User',
                    'info' => 'Check to create a login account for this agent.',
                ],
                [
                    'type' => 'text',
                    'name' => 'password',
                    'label' => 'Password',
                    'logics' => [
                        [
                            'field' => 'user',
                            'values' => [true],
                        ]
                    ]
                ],
                [
                    'type' => 'select',
                    'name' => 'role_id',
                    'label' => 'Role',
                    'options' => $roles,
                    'logics' => [
                        [
                            'field' => 'user',
                            'values' => [true],
                        ]
                    ]
                ],
            ]
        ];
        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:employees,email',
            'phone_number' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10000',
            'user' => 'nullable',
            'password' => 'required_if:user,true',
            'role_id' => 'required_if:user,true',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except('image', 'user', 'password', 'role_id');
            $data['is_active'] = true;
            $data['is_sale_agent'] = 1;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;
                $image->move('public/images/sale_agent', $imageName);
                $data['image'] = $imageName;
            }

            if ($request->user) {
                // Create User
                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone_number'],
                    'role_id' => $request->role_id,
                    'is_active' => true,
                    'is_deleted' => false,
                    'password' => bcrypt($request->password),
                ];
                $user = User::create($userData);
                $data['user_id'] = $user->id;
            }

            Employee::create($data);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Sale Agent created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->is_active = false;
            $employee->save();
            return response()->json(['success' => true, 'message' => 'Sale Agent deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
