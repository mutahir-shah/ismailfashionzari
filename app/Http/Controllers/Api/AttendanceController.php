<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\HrmSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AttendanceController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('attendance')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            $general_setting = DB::table('general_settings')->latest()->first();

            // Get attendance data based on permissions
            if (Auth::user()->role_id > 2 && $general_setting->staff_access == 'own') {
                $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                    ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                    ->orderBy('attendances.date', 'desc')
                    ->where('attendances.user_id', Auth::id())
                    ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                    ->get()
                    ->groupBy(['date', 'employee_id']);
            } else {
                $lims_attendance_data = Attendance::leftJoin('employees', 'employees.id', '=', 'attendances.employee_id')
                    ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
                    ->orderBy('attendances.date', 'desc')
                    ->select(['attendances.*', 'employees.name as employee_name', 'users.name as user_name'])
                    ->get()
                    ->groupBy(['date', 'employee_id']);
            }

            $lims_attendance_all = [];
            foreach ($lims_attendance_data as $attendance_data) {
                foreach ($attendance_data as $data) {
                    $checkin_checkout = '';
                    foreach ($data as $key => $dt) {
                        $date = $dt->date;
                        $employee_name = $dt->employee_name;
                        $checkin_checkout .= (($dt->checkin != null) ? $dt->checkin : 'N/A') . ' - ' . (($dt->checkout != null) ? $dt->checkout : 'N/A') . '<br>';
                        $status = $dt->status;
                        $user_name = $dt->user_name;
                        $employee_id = $dt->employee_id;
                    }
                    $lims_attendance_all[] = [
                        'date' => $date,
                        'employee_name' => $employee_name,
                        'checkin_checkout' => $checkin_checkout,
                        'status' => $status,
                        'status_text' => $status == 1
                            ? "<span style='color: green;'>Present</span>"
                            : "<span style='color: red;'>Late</span>",
                        'user_name' => $user_name,
                        'employee_id' => $employee_id,
                    ];
                }
            }

            return $this->withDashBackground([
                'title' => "Attendance",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Attendance',
                'add_url' => '/attendances/create',
                'import_url' => '/attendances/import',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Employee', 'field' => 'employee_name', 'type' => 'text'],
                    ['label' => 'Check In - Check Out', 'field' => 'checkin_checkout', 'type' => 'html'],
                    ['label' => 'Status', 'field' => 'status_text', 'type' => 'html'],
                    ['label' => 'Created By', 'field' => 'user_name', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/attendances/{date}/{employee_id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $lims_attendance_all,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while retrieving attendance data.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('attendance')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Get active employees
            $employees = Employee::where('is_active', true)->get()->map(function ($employee) {
                return [
                    'value' => $employee->id,
                    'label' => $employee->name,
                ];
            });

            $formSchema = [
                "title" => "Add Attendance",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/attendances",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "employee_id[]",
                        "label" => "Employee",
                        "placeholder" => "Select employees",
                        "options" => $employees,
                        "enable_search" => true,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "date",
                        "label" => "Date",
                        "placeholder" => "Select date",
                    ],
                    [
                        "type" => "timepicker",
                        "name" => "checkin",
                        "label" => "Check In",
                        "placeholder" => "Select check in time",
                    ],
                    [
                        "type" => "timepicker",
                        "name" => "checkout",
                        "label" => "Check Out",
                        "placeholder" => "Select check out time",
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note",
                        "multiline" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "user_id",
                        "value" => Auth::id(),
                    ],
                ]
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the attendance creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('attendance')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'employee_id' => 'required|array',
                'employee_id.*' => 'exists:employees,id',
                'date' => 'required|date',
                'checkin' => 'required',
                'checkout' => 'nullable',
                'note' => 'nullable|string',
            ]);

            $employee_ids = $request->employee_id;
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            $checkin = $lims_hrm_setting_data ? $lims_hrm_setting_data->checkin : '09:00:00';

            foreach ($employee_ids as $id) {
                $data = [
                    'date' => date('Y-m-d', strtotime($request->date)),
                    'user_id' => Auth::id(),
                    'employee_id' => $id,
                    'checkin' => $request->checkin,
                    'checkout' => $request->checkout,
                    'note' => $request->note,
                ];

                // Check for duplicates
                $existingAttendance = Attendance::where('date', $data['date'])
                    ->where('employee_id', $id)
                    ->where('checkin', $data['checkin'])
                    ->first();

                if ($existingAttendance) {
                    return new ErrorResource([
                        'message' => "Duplicate entry: Check-in time '{$data['checkin']}' for Employee ID $id on {$data['date']} already exists.",
                    ]);
                }

                // Find existing attendance for the employee on that date
                $lims_attendance_data = Attendance::whereDate('date', $data['date'])
                    ->where('employee_id', $id)
                    ->first();

                if (!$lims_attendance_data) {
                    // Calculate status based on the check-in time
                    $diff = strtotime($checkin) - strtotime($data['checkin']);
                    $data['status'] = ($diff >= 0) ? 1 : 0;
                } else {
                    // If the record exists, preserve the previous status
                    $data['status'] = $lims_attendance_data->status;
                }

                // Insert the new attendance record
                Attendance::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance created successfully.',
                'navigate_url' => '/attendances',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the attendance.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create attendances
            if (!$role->hasPermissionTo('attendance')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import attendances.',
                ], 403);
            }

            // Handle POST request - process the import
            if ($request->isMethod('post')) {
                $request->validate([
                    'file' => 'required|file|mimes:csv',
                ]);

                $upload = $request->file('file');
                $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
                if ($ext != 'csv') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please upload a CSV file',
                    ], 400);
                }

                $filePath = $upload->getRealPath();
                $file = fopen($filePath, 'r');
                $header = fgetcsv($file);
                $escapedHeader = [];

                // Validate and escape header
                foreach ($header as $key => $value) {
                    $lheader = strtolower($value);
                    $escapedItem = preg_replace('/[^a-z_]/', '', $lheader);
                    array_push($escapedHeader, $escapedItem);
                }

                $importedCount = 0;
                // Loop through rows
                while ($columns = fgetcsv($file)) {
                    if ($columns[0] == "")
                        continue;

                    $data = array_combine($escapedHeader, $columns);

                    // Find employee
                    $employee = Employee::where('name', $data['employee'])->first();
                    if (!$employee) {
                        continue; // Skip if employee not found
                    }

                    $attendance = Attendance::firstOrNew([
                        'employee_id' => $employee->id,
                        'date' => $data['date']
                    ]);
                    $attendance->check_in = $data['checkin'] ?? null;
                    $attendance->check_out = $data['checkout'] ?? null;
                    $attendance->save();

                    $importedCount++;
                }

                fclose($file);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} attendances.",
                    'navigate_url' => '/attendances',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Attendances",
                "submit_url" => "/attendances/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (employee, date, check_in, check_out) and you must follow this. Date format: YYYY-MM-DD, Time format: HH:MM",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_attendance.csv'),
                        "sample_file_name" => "sample_attendance.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($formSchema, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($date, $employee_id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('attendance')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            Attendance::whereDate('date', $date)->where('employee_id', $employee_id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while deleting the attendance.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }

    public function deleteBySelection(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('attendance')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            $attendance_selected = $request['attendanceSelectedArray'];
            foreach ($attendance_selected as $att_selected) {
                Attendance::whereDate('date', $att_selected[0])->where('employee_id', $att_selected[1])->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while deleting attendances.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }
}
