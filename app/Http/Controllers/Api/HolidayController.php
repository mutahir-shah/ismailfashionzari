<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class HolidayController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('holiday')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            // Check if the user has permission to view all holidays
            if ($role->hasPermissionTo('holiday')) {
                $holidays = Holiday::with('user')->orderBy('id', 'desc')->get();
            } else {
                // Only show user's own holidays
                $holidays = Holiday::with('user')->where('user_id', Auth::id())->orderBy('id', 'desc')->get();
            }

            // Format holidays for datatable
            $holidayRows = $holidays->map(function ($holiday) use ($role) {
                $dateFormat = config('date_format', 'd M, Y');

                $row = [
                    'id' => $holiday->id,
                    'date' => date($dateFormat, strtotime($holiday->created_at)),
                    'created_by' => $holiday->user->name ?? 'N/A',
                    'from' => date($dateFormat, strtotime($holiday->from_date)),
                    'to' => date($dateFormat, strtotime($holiday->to_date)),
                    'note' => $holiday->note ?? '',
                    'is_approved' => $holiday->is_approved,
                    'is_approved_text' => $holiday->is_approved
                        ? "<span style='color: green;'>Approved</span>"
                        : "<span style='color: orange;'>Pending</span>",
                ];

                return $row;
            });

            $approvePermission = $role->hasPermissionTo('holiday');

            $manageChildren = [
                [
                    'type' => 'action',
                    'icon' => 'edit',
                    'action' => [
                        'api_url' => '/holidays/{id}/edit',
                        'type' => 'form'
                    ]
                ],
                [
                    'type' => 'action',
                    'icon' => 'delete',
                    'action' => [
                        'api_url' => '/holidays/{id}',
                        'type' => 'delete'
                    ]
                ],
            ];

            // Add approve button if user has permission
            if ($approvePermission) {
                array_unshift($manageChildren, [
                    'type' => 'button',
                    'label' => 'Approve',
                    'action' => [
                        'api_url' => '/holidays/{id}/approve',
                        'type' => 'custom_api'
                    ],
                    'logics' => [
                        [
                            'field' => 'is_approved',
                            'values' => [false, 0],
                        ],
                    ],
                ]);
            }

            return $this->withDashBackground([
                'title' => "Holidays",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Holiday',
                'add_url' => '/holidays/create',
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'Created By', 'field' => 'created_by', 'type' => 'text'],
                    ['label' => 'From', 'field' => 'from', 'type' => 'text'],
                    ['label' => 'To', 'field' => 'to', 'type' => 'text'],
                    ['label' => 'Note', 'field' => 'note', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'is_approved_text', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => $manageChildren
                    ],
                ],
                'rows' => $holidayRows,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while retrieving holidays data.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }

    public function create()
    {
        try {
            $formSchema = [
                "title" => "Add Holiday",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/holidays",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "from_date",
                        "label" => "From Date",
                        "placeholder" => "Select from date",
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "to_date",
                        "label" => "To Date",
                        "placeholder" => "Select to date",
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
                'message' => 'An error occurred while loading the holiday creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'note' => 'nullable|string',
            ]);

            $data = [
                'from_date' => date("Y-m-d", strtotime($request->from_date)),
                'to_date' => date("Y-m-d", strtotime($request->to_date)),
                'user_id' => Auth::id(),
                'note' => $request->note,
            ];

            $role = Role::find(Auth::user()->role_id);
            if ($role->hasPermissionTo('holiday')) {
                $data['is_approved'] = true;
            } else {
                $data['is_approved'] = false;
            }

            Holiday::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Holiday created successfully.',
                'navigate_url' => '/holidays',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the holiday.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            // Check if user can edit this holiday
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('holiday') && $holiday->user_id != Auth::id()) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to edit this holiday.',
                ]), 403);
            }

            $formSchema = [
                "title" => "Edit Holiday",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/holidays/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "datepicker",
                        "name" => "from_date",
                        "label" => "From Date",
                        "placeholder" => "Select from date",
                        "value" => $holiday->from_date,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "to_date",
                        "label" => "To Date",
                        "placeholder" => "Select to date",
                        "value" => $holiday->to_date,
                    ],
                    [
                        "type" => "text",
                        "name" => "note",
                        "label" => "Note",
                        "placeholder" => "Enter note",
                        "multiline" => true,
                        "value" => $holiday->note ?? '',
                    ],
                ]
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the holiday edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            // Check if user can update this holiday
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('holiday') && $holiday->user_id != Auth::id()) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to update this holiday.',
                ]), 403);
            }

            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'note' => 'nullable|string',
            ]);

            $data = [
                'from_date' => date("Y-m-d", strtotime($request->from_date)),
                'to_date' => date("Y-m-d", strtotime($request->to_date)),
                'note' => $request->note,
            ];

            $holiday->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully.',
                'navigate_url' => '/holidays',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the holiday.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function approve($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('holiday')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to approve holidays.',
                ]), 403);
            }

            $holiday = Holiday::findOrFail($id);
            $holiday->is_approved = true;
            $holiday->save();

            return response()->json([
                'success' => true,
                'message' => 'Holiday approved successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while approving the holiday.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            // Check if user can delete this holiday
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('holiday') && $holiday->user_id != Auth::id()) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to delete this holiday.',
                ]), 403);
            }

            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Holiday deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while deleting the holiday.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }
}
