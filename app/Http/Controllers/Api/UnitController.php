<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UnitResource;
use App\Http\Resources\ErrorResource;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use App\Services\getDataService;

class UnitController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $units = Unit::where('is_active', true)->with('baseUnit')->orderBy('id', 'desc')->get();
            // Map units to the required list schema structure
            $unitsTable = $units->map(function ($unit) {
                $baseUnitName = $unit->baseUnit ? $unit->baseUnit->unit_name : 'Base Unit';

                return [
                    'id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'unit_name' => $unit->unit_name,
                    'base_unit' => $baseUnitName,
                    'operation' => ($unit->operator && $unit->operation_value) ? $unit->operator . ' ' . $unit->operation_value : 'N/A',
                ];
            });

            return $this->withDashBackground([
                'title' => "Units",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Unit',
                'add_url' => '/units/create',
                'columns' => [
                    ['label' => 'Unit Code', 'field' => 'unit_code', 'type' => 'text'],
                    ['label' => 'Unit Name', 'field' => 'unit_name', 'type' => 'text'],
                    ['label' => 'Base Unit', 'field' => 'base_unit', 'type' => 'text'],
                    ['label' => 'Operation', 'field' => 'operation', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/units/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/units/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $unitsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving units data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $baseUnits = Unit::where('is_active', true)->get();
            $baseUnitOptions = $baseUnits->map(function ($unit) {
                return [
                    'label' => $unit->unit_name,
                    'value' => $unit->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add Unit",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/units",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "unit_code",
                        "label" => "Unit Code",
                        "placeholder" => "Enter unit code",
                    ],
                    [
                        "type" => "text",
                        "name" => "unit_name",
                        "label" => "Unit Name",
                        "placeholder" => "Enter unit name",
                    ],
                    [
                        "type" => "select",
                        "name" => "base_unit",
                        "label" => "Base Unit",
                        "options" => array_merge([["label" => "None (This is a base unit)", "value" => ""]], $baseUnitOptions),
                        "info" => "Select a base unit if this unit is derived from another unit",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "select",
                        "name" => "operator",
                        "label" => "Operator",
                        "options" => [
                            ["label" => "Multiply (*)", "value" => "*"],
                            ["label" => "Divide (/)", "value" => "/"],
                            ["label" => "Add (+)", "value" => "+"],
                            ["label" => "Subtract (−)", "value" => "−"],
                        ],
                        "info" => "How this unit relates to the base unit",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "operation_value",
                        "label" => "Operation Value",
                        "placeholder" => "Enter numeric value",
                        "keyboard_type" => "number",
                        "info" => "The value used with the operator",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading unit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'unit_code' => [
                    'required',
                    'max:255',
                    Rule::unique('units')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'unit_name' => [
                    'required',
                    'max:255',
                    Rule::unique('units')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'base_unit' => 'nullable|integer|exists:units,id',
                'operator' => 'nullable|string|in:*,/,+,−',
                'operation_value' => 'nullable|numeric',
            ]);

            $input = $request->all();
            $input['is_active'] = true;

            if (empty($input['base_unit'])) {
                $input['operator'] = '*';
                $input['operation_value'] = 1;
            }

            $unit = Unit::create($input);

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully.',
                'navigate_url' => '/units',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the unit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Unit $unit)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new UnitResource($unit->load('baseUnit')),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving unit details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $unit = Unit::findOrFail($id);
            $baseUnits = Unit::where('is_active', true)->where('id', '!=', $id)->get();
            $baseUnitOptions = $baseUnits->map(function ($baseUnit) {
                return [
                    'label' => $baseUnit->unit_name,
                    'value' => $baseUnit->id,
                ];
            })->toArray();

            $formSchema = [
                "title" => "Edit Unit",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/units/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "unit_code",
                        "label" => "Unit Code",
                        "placeholder" => "Enter unit code",
                        "value" => $unit->unit_code,
                    ],
                    [
                        "type" => "text",
                        "name" => "unit_name",
                        "label" => "Unit Name",
                        "placeholder" => "Enter unit name",
                        "value" => $unit->unit_name,
                    ],
                    [
                        "type" => "select",
                        "name" => "base_unit",
                        "label" => "Base Unit",
                        "value" => $unit->base_unit,
                        "options" => array_merge([["label" => "None (This is a base unit)", "value" => ""]], $baseUnitOptions),
                        "info" => "Select a base unit if this unit is derived from another unit",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "select",
                        "name" => "operator",
                        "label" => "Operator",
                        "value" => $unit->operator,
                        "options" => [
                            ["label" => "Multiply (*)", "value" => "*"],
                            ["label" => "Divide (/)", "value" => "/"],
                            ["label" => "Add (+)", "value" => "+"],
                            ["label" => "Subtract (−)", "value" => "−"],
                        ],
                        "info" => "How this unit relates to the base unit",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "operation_value",
                        "label" => "Operation Value",
                        "placeholder" => "Enter numeric value",
                        "keyboard_type" => "number",
                        "value" => $unit->operation_value,
                        "info" => "The value used with the operator",
                        "show_info_icon" => true,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading unit edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'unit_code' => [
                    'required',
                    'max:255',
                    Rule::unique('units')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'unit_name' => [
                    'required',
                    'max:255',
                    Rule::unique('units')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'base_unit' => 'nullable|integer|exists:units,id',
                'operator' => 'nullable|string|in:*,/,+,−',
                'operation_value' => 'nullable|numeric',
            ]);

            $input = $request->all();

            if (!isset($input['base_unit']) || !$input['base_unit']) {
                $input['operator'] = '*';
                $input['operation_value'] = 1;
            }

            $unit = Unit::findOrFail($id);
            $unit->update($input);

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully.',
                'navigate_url' => '/units',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the unit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete units
            if (!$role->hasPermissionTo('unit')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $unit = Unit::findOrFail($id);
            $unit->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully.',
                'navigate_url' => '/units',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the unit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
