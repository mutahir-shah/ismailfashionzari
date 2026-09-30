<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Table;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TableController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');

            // Retrieve tables (only active ones)
            $query = Table::where('is_active', true);
            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $tables = $query->orderBy('id', 'desc')->get();

            // Check if restaurant module is enabled
            $general_setting = GeneralSetting::latest()->first();
            $restaurantEnabled = $general_setting && in_array('restaurant', explode(',', $general_setting->modules));

            // Get floors if restaurant is enabled
            $floors = $restaurantEnabled ? DB::table('floors')->get()->keyBy('id') : collect();

            // Format tables for datatable
            $tablesTable = $tables->map(function ($table) use ($floors, $restaurantEnabled) {
                $row = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'number_of_person' => (string)$table->number_of_person,
                    'description' => $table->description ?? 'N/A',
                ];

                if ($restaurantEnabled) {
                    $row['floor'] = $table->floor_id && isset($floors[$table->floor_id])
                        ? $floors[$table->floor_id]->name
                        : 'N/A';
                }

                return $row;
            });

            // Build columns schema
            $columns = [
                [
                    'label' => 'Name',
                    'field' => 'name',
                    'type' => 'text',
                ],
                [
                    'label' => 'Number of Person',
                    'field' => 'number_of_person',
                    'type' => 'text',
                ],
                [
                    'label' => 'Description',
                    'field' => 'description',
                    'type' => 'text',
                ],
            ];

            if ($restaurantEnabled) {
                $columns[] = [
                    'label' => 'Floor',
                    'field' => 'floor',
                    'type' => 'text',
                ];
            }

            $columns[] = [
                'label' => 'Manage',
                'type' => 'row',
                'children' => [
                    [
                        'type' => 'action',
                        'icon' => 'edit',
                        'action' => [
                            'api_url' => '/tables/{id}/edit',
                            'type' => 'form'
                        ]
                    ],
                    [
                        'type' => 'action',
                        'icon' => 'delete',
                        'action' => [
                            'api_url' => '/tables/{id}',
                            'type' => 'delete'
                        ]
                    ]
                ]
            ];

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Tables",
                'row_height' => 5,
                'add_text' => 'Add Table',
                'add_url' => '/tables/create',
                'columns' => $columns,
                'rows' => $tablesTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving tables.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            // Check if restaurant module is enabled
            $general_setting = GeneralSetting::latest()->first();
            $restaurantEnabled = $general_setting && in_array('restaurant', explode(',', $general_setting->modules));

            $fields = [
                [
                    "type" => "group",
                    "label" => "Table Information",
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "name",
                            "label" => "Table Name *",
                            "placeholder" => "Enter table name",
                        ],
                        [
                            "type" => "text",
                            "name" => "number_of_person",
                            "label" => "Number of Person *",
                            "placeholder" => "Enter number of person",
                            "keyboard_type" => "number",
                        ],
                        [
                            "type" => "text",
                            "name" => "description",
                            "label" => "Description",
                            "placeholder" => "Enter description",
                            "multiline" => true,
                        ],
                    ],
                ],
            ];

            // Add floor selection if restaurant module is enabled
            if ($restaurantEnabled) {
                $floors = DB::table('floors')->get();
                $floorOptions = $floors->map(function ($floor) {
                    return [
                        'label' => $floor->name,
                        'value' => $floor->id,
                    ];
                })->toArray();

                $fields[0]['items'][] = [
                    "type" => "select",
                    "name" => "floor_id",
                    "label" => "Floor *",
                    "options" => $floorOptions,
                ];
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Table",
                "submit_url" => "/tables",
                "method" => "POST",
                "fields" => $fields,
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the form schema.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create tables
            if (!$role->hasPermissionTo('table')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'number_of_person' => 'required|integer|min:1',
                'floor_id' => 'nullable|integer|exists:floors,id',
            ]);

            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'number_of_person' => $request->number_of_person,
                'description' => $request->description ?? '',
                'floor_id' => $request->floor_id,
                'is_active' => true,
            ];

            $table = Table::create($data);

            // Handle restaurant floor plan if enabled
            $general_setting = GeneralSetting::latest()->first();
            if ($general_setting && in_array('restaurant', explode(',', $general_setting->modules)) && $request->floor_id) {
                $floor = DB::table('floors')->where('id', $request->floor_id)->first();
                if ($floor) {
                    $newTable = [
                        'id' => $table->id,
                        'x' => 0,
                        'y' => 0,
                        'width' => 100,
                        'height' => 100,
                        'name' => $table->name . '(' . $table->number_of_person . ')'
                    ];

                    $floorplan = json_decode($floor->floorplan, true) ?? [];
                    $floorplan[] = $newTable;

                    DB::table('floors')
                        ->where('id', $floor->id)
                        ->update(['floorplan' => json_encode($floorplan)]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Table created successfully.',
                'data' => $table,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while creating the table.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit tables
            if (!$role->hasPermissionTo('table')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $table = Table::where('is_active', true)->findOrFail($id);

            // Check if restaurant module is enabled
            $general_setting = GeneralSetting::latest()->first();
            $restaurantEnabled = $general_setting && in_array('restaurant', explode(',', $general_setting->modules));

            $fields = [
                [
                    "type" => "group",
                    "label" => "Table Information",
                    "items" => [
                        [
                            "type" => "text",
                            "name" => "name",
                            "label" => "Table Name *",
                            "placeholder" => "Enter table name",
                            "value" => $table->name,
                        ],
                        [
                            "type" => "text",
                            "name" => "number_of_person",
                            "label" => "Number of Person *",
                            "placeholder" => "Enter number of person",
                            "keyboard_type" => "number",
                            "value" => (string)$table->number_of_person,
                        ],
                        [
                            "type" => "text",
                            "name" => "description",
                            "label" => "Description",
                            "placeholder" => "Enter description",
                            "multiline" => true,
                            "value" => $table->description ?? '',
                        ],
                    ],
                ],
            ];

            // Add floor selection if restaurant module is enabled
            if ($restaurantEnabled) {
                $floors = DB::table('floors')->get();
                $floorOptions = $floors->map(function ($floor) {
                    return [
                        'label' => $floor->name,
                        'value' => $floor->id,
                    ];
                })->toArray();

                $fields[0]['items'][] = [
                    "type" => "select",
                    "name" => "floor_id",
                    "label" => "Floor *",
                    "options" => $floorOptions,
                    "value" => $table->floor_id,
                ];
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Table",
                "submit_url" => "/tables/" . $id,
                "method" => "PUT",
                "fields" => $fields,
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the form schema.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to update tables
            if (!$role->hasPermissionTo('table')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $table = Table::where('is_active', true)->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'number_of_person' => 'required|integer|min:1',
                'floor_id' => 'nullable|integer|exists:floors,id',
            ]);

            DB::beginTransaction();

            $oldFloorId = $table->floor_id;

            $data = [
                'name' => $request->name,
                'number_of_person' => $request->number_of_person,
                'description' => $request->description ?? '',
                'floor_id' => $request->floor_id,
            ];

            $table->update($data);

            // Handle restaurant floor plan if enabled
            $general_setting = GeneralSetting::latest()->first();
            if ($general_setting && in_array('restaurant', explode(',', $general_setting->modules))) {
                // If floor changed, move table from old floor to new floor
                if ($oldFloorId != $request->floor_id) {
                    // Remove from old floor
                    if ($oldFloorId) {
                        $oldFloor = DB::table('floors')->where('id', $oldFloorId)->first();
                        if ($oldFloor) {
                            $oldFloorplan = json_decode($oldFloor->floorplan, true) ?? [];
                            $updatedOldFloorplan = array_filter($oldFloorplan, function ($item) use ($id) {
                                return $item['id'] != $id;
                            });
                            DB::table('floors')
                                ->where('id', $oldFloorId)
                                ->update(['floorplan' => json_encode(array_values($updatedOldFloorplan))]);
                        }
                    }

                    // Add to new floor
                    if ($request->floor_id) {
                        $newFloor = DB::table('floors')->where('id', $request->floor_id)->first();
                        if ($newFloor) {
                            $newTable = [
                                'id' => (int)$id,
                                'x' => 0,
                                'y' => 0,
                                'width' => 100,
                                'height' => 100,
                                'name' => $request->name . '(' . $request->number_of_person . ')'
                            ];

                            $newFloorplan = json_decode($newFloor->floorplan, true) ?? [];
                            $newFloorplan[] = $newTable;

                            DB::table('floors')
                                ->where('id', $request->floor_id)
                                ->update(['floorplan' => json_encode($newFloorplan)]);
                        }
                    }
                } else {
                    // Same floor, just update name if needed
                    if ($request->floor_id) {
                        $floor = DB::table('floors')->where('id', $request->floor_id)->first();
                        if ($floor) {
                            $floorplan = json_decode($floor->floorplan, true) ?? [];
                            foreach ($floorplan as &$item) {
                                if ($item['id'] == $id) {
                                    $item['name'] = $request->name . '(' . $request->number_of_person . ')';
                                    break;
                                }
                            }
                            DB::table('floors')
                                ->where('id', $request->floor_id)
                                ->update(['floorplan' => json_encode($floorplan)]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully.',
                'data' => $table,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while updating the table.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete tables
            if (!$role->hasPermissionTo('table')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $table = Table::where('is_active', true)->findOrFail($id);

            DB::beginTransaction();

            // Soft delete by setting is_active to false
            $table->update(['is_active' => false]);

            // Handle restaurant floor plan if enabled
            $general_setting = GeneralSetting::latest()->first();
            if ($general_setting && in_array('restaurant', explode(',', $general_setting->modules)) && $table->floor_id) {
                $floor = DB::table('floors')->where('id', $table->floor_id)->first();
                if ($floor) {
                    $floorplan = json_decode($floor->floorplan, true) ?? [];
                    $updatedFloorplan = array_filter($floorplan, function ($item) use ($id) {
                        return $item['id'] != $id;
                    });
                    DB::table('floors')
                        ->where('id', $table->floor_id)
                        ->update(['floorplan' => json_encode(array_values($updatedFloorplan))]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Table deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while deleting the table.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
