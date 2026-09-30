<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\PayrollResource;
use App\Http\Resources\SuccessResource;
use App\Models\Account;
use App\Models\Employee;
use App\Models\GeneralSetting;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class PayrollController extends Controller
{
    use ProvidesThemeBackgrounds;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $search = $request->input('search', '');

            // Retrieve payrolls based on user role
            $query = Payroll::with(['employee', 'account']);

            if ($user->role_id > 2 && $general_setting->staff_access == 'own') {
                $query->where('user_id', $user->id);
            }

            if (!empty($search)) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })->orWhere('reference_no', 'LIKE', "%{$search}%");
            }

            $payrolls = $query->orderBy('id', 'desc')->get();

            // Format payrolls for table
            $payrollsTable = $payrolls->map(function ($payroll, $index) use ($general_setting) {
                $payingMethod = '';
                switch ($payroll->paying_method) {
                    case 0:
                        $payingMethod = 'Cash';
                        break;
                    case 1:
                        $payingMethod = 'Cheque';
                        break;
                    case 2:
                        $payingMethod = 'Credit Card';
                        break;
                }

                return [
                    'id' => $payroll->id,
                    'date' => $payroll->created_at ? $payroll->created_at->format($general_setting->date_format ?? 'Y-m-d') : '',
                    'reference_no' => $payroll->reference_no,
                    'employee' => $payroll->employee ? $payroll->employee->name : 'N/A',
                    'account' => $payroll->account ? $payroll->account->name : 'N/A',
                    'amount' => number_format((float)$payroll->amount, $general_setting->decimal ?? 2, '.', ''),
                    'paying_method' => $payingMethod,
                ];
            });

            return $this->withDashBackground([
                'title' => "Payroll",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Payroll',
                'add_url' => '/payroll/create',
                'columns' => [
                    ['label' => '#', 'field' => 'id', 'type' => 'text'],
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Reference', 'field' => 'reference_no', 'type' => 'text'],
                    ['label' => 'Employee', 'field' => 'employee', 'type' => 'text'],
                    ['label' => 'Account', 'field' => 'account', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Method', 'field' => 'paying_method', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/payroll/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/payroll/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $payrollsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving payroll data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $employees = Employee::where('is_active', true)->get();
            $accounts = Account::where('is_active', true)->get();

            $employeeOptions = $employees->map(function ($employee) {
                return [
                    'label' => $employee->name,
                    'value' => $employee->id,
                ];
            })->toArray();

            $accountOptions = $accounts->map(function ($account) {
                return [
                    'label' => $account->name . ' [' . $account->account_no . ']',
                    'value' => $account->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Payroll",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/payroll",
                "method" => "POST",
                "navigate_url" => "/payroll",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Payroll Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Choose date",
                                "format_specifier" => "dd-MM-yyyy",
                            ],
                            [
                                "type" => "select",
                                "name" => "employee_id",
                                "label" => "Employee",
                                "options" => $employeeOptions,
                            ],
                            [
                                "type" => "select",
                                "name" => "account_id",
                                "label" => "Account",
                                "options" => $accountOptions,
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "select",
                                "name" => "paying_method",
                                "label" => "Payment Method",
                                "options" => [
                                    ['label' => 'Cash', 'value' => 0],
                                    ['label' => 'Cheque', 'value' => 1],
                                    ['label' => 'Credit Card', 'value' => 2],
                                ],
                            ],
                            [
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the create form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0',
                'paying_method' => 'required|in:0,1,2',
                'note' => 'nullable|string',
                'created_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            $data = $request->all();

            // Set created_at date
            if (isset($data['created_at']) && !empty($data['created_at'])) {
                $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at'])));
            } else {
                $data['created_at'] = date("Y-m-d");
            }

            // Generate reference number
            $data['reference_no'] = 'payroll-' . date("Ymd") . '-' . date("his");
            $data['user_id'] = $user->id;

            // Create payroll
            $payroll = Payroll::create($data);

            // Load relationships for response
            $payroll->load(['employee', 'account']);

            return new SuccessResource([
                'message' => 'Payroll created successfully',
                'data' => new PayrollResource($payroll),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the payroll.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $payroll = Payroll::with(['employee', 'account'])->findOrFail($id);

            // Check ownership for staff users
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $payroll->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Access denied. You can only view your own payroll records.',
                ]);
            }

            return new SuccessResource([
                'data' => new PayrollResource($payroll),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'Payroll not found.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $payroll = Payroll::with(['employee', 'account'])->findOrFail($id);

            // Check ownership for staff users
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $payroll->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Access denied. You can only edit your own payroll records.',
                ]);
            }

            $employees = Employee::where('is_active', true)->get();
            $accounts = Account::where('is_active', true)->get();

            $employeeOptions = $employees->map(function ($employee) {
                return [
                    'label' => $employee->name,
                    'value' => $employee->id,
                ];
            })->toArray();

            $accountOptions = $accounts->map(function ($account) {
                return [
                    'label' => $account->name . ' [' . $account->account_no . ']',
                    'value' => $account->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Update Payroll",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/payroll/" . $id,
                "method" => "PUT",
                "navigate_url" => "/payroll",
                "fields" => [

                    [
                        "type" => "group",
                        "label" => "Payroll Information",
                        "items" => [
                            [
                                "type" => "datepicker",
                                "name" => "created_at",
                                "label" => "Date",
                                "placeholder" => "Choose date",
                                "value" => $payroll->created_at ? $payroll->created_at->format('d-m-Y') : '',
                                "format_specifier" => "dd-MM-yyyy",
                            ],
                            [
                                "type" => "select",
                                "name" => "employee_id",
                                "label" => "Employee",
                                "options" => $employeeOptions,
                                "value" => $payroll->employee_id,
                            ],
                            [
                                "type" => "select",
                                "name" => "account_id",
                                "label" => "Account",
                                "options" => $accountOptions,
                                "value" => $payroll->account_id,
                            ],
                            [
                                "type" => "text",
                                "name" => "amount",
                                "label" => "Amount",
                                "placeholder" => "Enter amount",
                                "value" => $payroll->amount,
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "select",
                                "name" => "paying_method",
                                "label" => "Payment Method",
                                "options" => [
                                    ['label' => 'Cash', 'value' => 0],
                                    ['label' => 'Cheque', 'value' => 1],
                                    ['label' => 'Credit Card', 'value' => 2],
                                ],
                                "value" => $payroll->paying_method,
                            ],
                            [
                                "type" => "editor",
                                "name" => "note",
                                "label" => "Note",
                                "value" => $payroll->note ?? '',
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "payroll_id",
                        "value" => $payroll->id,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $payroll = Payroll::findOrFail($id);

            // Check ownership for staff users
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $payroll->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Access denied. You can only edit your own payroll records.',
                ]);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric|min:0',
                'paying_method' => 'required|in:0,1,2',
                'note' => 'nullable|string',
                'created_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return new ErrorResource([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ]);
            }

            $data = $request->all();

            // Set created_at date
            if (isset($data['created_at']) && !empty($data['created_at'])) {
                $data['created_at'] = date("Y-m-d", strtotime(str_replace("/", "-", $data['created_at'])));
            } else {
                $data['created_at'] = date("Y-m-d");
            }

            // Update payroll
            $payroll->update($data);

            // Load relationships for response
            $payroll->load(['employee', 'account']);

            return new SuccessResource([
                'message' => 'Payroll updated successfully',
                'data' => new PayrollResource($payroll),
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the payroll.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $payroll = Payroll::findOrFail($id);

            // Check ownership for staff users
            if ($user->role_id > 2 && $general_setting->staff_access == 'own' && $payroll->user_id != $user->id) {
                return new ErrorResource([
                    'message' => 'Access denied. You can only delete your own payroll records.',
                ]);
            }

            $payroll->delete();

            return new SuccessResource([
                'message' => 'Payroll deleted successfully',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the payroll.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete multiple payrolls by selection
     */
    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the payroll module
            if (!$role->hasPermissionTo('payroll')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $payrollIds = $request->input('payrollIdArray', []);

            if (empty($payrollIds)) {
                return new ErrorResource([
                    'message' => 'No payroll records selected for deletion.',
                ]);
            }

            $general_setting = GeneralSetting::latest()->first();
            $query = Payroll::whereIn('id', $payrollIds);

            // Check ownership for staff users
            if ($user->role_id > 2 && $general_setting->staff_access == 'own') {
                $query->where('user_id', $user->id);
            }

            $payrolls = $query->get();
            $deletedCount = $payrolls->count();

            foreach ($payrolls as $payroll) {
                $payroll->delete();
            }

            return new SuccessResource([
                'message' => "Deleted {$deletedCount} payroll record(s) successfully",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting payroll records.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
