<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CustomFieldResource;
use App\Http\Resources\ErrorResource;
use App\Models\CustomField;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CustomFieldController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customFields = CustomField::orderBy('id', 'desc')->get();
            return $this->withDashBackground([
                'title' => "Custom Fields",
                'row_height' => 5,
                'add_text' => 'Add Custom Field',
                'add_url' => '/custom-fields/create',
                'columns' => [
                    ['label' => 'Field Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Belongs To', 'field' => 'belongs_to', 'type' => 'text'],
                    ['label' => 'Type', 'field' => 'type', 'type' => 'text'],
                    ['label' => 'Default Value', 'field' => 'default_value', 'type' => 'text'],
                    ['label' => 'Required', 'field' => 'is_required', 'type' => 'html'],
                    ['label' => 'Show in Table', 'field' => 'is_table', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/custom-fields/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/custom-fields/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => CustomFieldResource::collection($customFields),
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving custom fields data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "title" => "Add Custom Field",
                "submit_url" => "/custom-fields",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Field Name",
                        "placeholder" => "Enter field name",
                    ],
                    [
                        "type" => "select",
                        "name" => "belongs_to",
                        "label" => "Belongs To",
                        "options" => [
                            ["label" => "Product", "value" => "product"],
                            ["label" => "Sale", "value" => "sale"],
                            ["label" => "Purchase", "value" => "purchase"],
                            ["label" => "Customer", "value" => "customer"],
                        ],
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Field Type",
                        "options" => [
                            ["label" => "Text", "value" => "text"],
                            ["label" => "Number", "value" => "number"],
                            ["label" => "Textarea", "value" => "textarea"],
                            ["label" => "Date Picker", "value" => "date_picker"],
                            ["label" => "Select", "value" => "select"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "default_value_1",
                        "label" => "Default Value (for text/number)",
                        "placeholder" => "Enter default value",
                    ],
                    [
                        "type" => "editor",
                        "name" => "default_value_2",
                        "label" => "Default Value (for textarea)",
                    ],
                    [
                        "type" => "text",
                        "name" => "option_value",
                        "label" => "Option Values (for select)",
                        "placeholder" => "Enter options separated by comma",
                        "info" => "For select type fields, enter options separated by commas",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "grid_value",
                        "label" => "Grid Value (1-12)",
                        "placeholder" => "Enter grid value (1-12)",
                        "keyboard_type" => "number",
                        "info" => "Bootstrap grid size for form layout",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "group",
                        "label" => "Field Options",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "is_table",
                                "label" => "Show in Table",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_invoice",
                                "label" => "Show in Invoice",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_required",
                                "label" => "Required Field",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_admin",
                                "label" => "Admin Only",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_disable",
                                "label" => "Disabled",
                                "value" => false,
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading custom field form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'belongs_to' => 'required|string|in:product,sale,purchase,customer',
                'type' => 'required|string|in:text,number,textarea,date_picker,select',
                'grid_value' => 'required|integer|min:1|max:12',
            ]);

            $data = $request->all();

            // Get table name based on belongs_to
            $tableName = $this->getTableName($data['belongs_to']);
            if (!$tableName) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Invalid belongs_to value.',
                ], 400);
            }

            $columnName = str_replace(" ", "_", strtolower($data['name']));

            // Determine data type for database column
            if ($data['type'] == 'number') {
                $dataType = 'double';
            } elseif ($data['type'] == 'textarea') {
                $dataType = 'text';
            } else {
                $dataType = 'varchar(255)';
            }

            $sqlStatement = "ALTER TABLE " . $tableName . " ADD `" . $columnName . "` " . $dataType;

            if (!empty($data['default_value_1'])) {
                $sqlStatement .= " DEFAULT '" . $data['default_value_1'] . "'";
                $data['default_value'] = $data['default_value_1'];
            } elseif (!empty($data['default_value_2'])) {
                $sqlStatement .= " DEFAULT '" . $data['default_value_2'] . "'";
                $data['default_value'] = $data['default_value_2'];
            }

            DB::statement($sqlStatement);

            // Prepare data for custom fields table
            $data['is_table'] = $request->has('is_table');
            $data['is_invoice'] = $request->has('is_invoice');
            $data['is_required'] = $request->has('is_required');
            $data['is_admin'] = $request->has('is_admin');
            $data['is_disable'] = $request->has('is_disable');

            CustomField::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Custom Field created successfully.',
                'navigate_url' => '/custom-fields'
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the custom field.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(CustomField $customField)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new CustomFieldResource($customField),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving custom field details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customField = CustomField::findOrFail($id);

            $formSchema = [
                "title" => "Edit Custom Field",
                "submit_url" => "/custom-fields/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Field Name",
                        "placeholder" => "Enter field name",
                        "value" => $customField->name,
                    ],
                    [
                        "type" => "select",
                        "name" => "belongs_to",
                        "label" => "Belongs To",
                        "value" => $customField->belongs_to,
                        "options" => [
                            ["label" => "Product", "value" => "product"],
                            ["label" => "Sale", "value" => "sale"],
                            ["label" => "Purchase", "value" => "purchase"],
                            ["label" => "Customer", "value" => "customer"],
                        ],
                    ],
                    [
                        "type" => "select",
                        "name" => "type",
                        "label" => "Field Type",
                        "value" => $customField->type,
                        "options" => [
                            ["label" => "Text", "value" => "text"],
                            ["label" => "Number", "value" => "number"],
                            ["label" => "Textarea", "value" => "textarea"],
                            ["label" => "Date Picker", "value" => "date_picker"],
                            ["label" => "Select", "value" => "select"],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "default_value_1",
                        "label" => "Default Value (for text/number)",
                        "placeholder" => "Enter default value",
                        "value" => ($customField->type !== 'textarea') ? $customField->default_value : '',
                    ],
                    [
                        "type" => "editor",
                        "name" => "default_value_2",
                        "label" => "Default Value (for textarea)",
                        "value" => ($customField->type === 'textarea') ? $customField->default_value : '',
                    ],
                    [
                        "type" => "text",
                        "name" => "option_value",
                        "label" => "Option Values (for select)",
                        "placeholder" => "Enter options separated by comma",
                        "value" => $customField->option_value,
                        "info" => "For select type fields, enter options separated by commas",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "grid_value",
                        "label" => "Grid Value (1-12)",
                        "placeholder" => "Enter grid value (1-12)",
                        "keyboard_type" => "number",
                        "value" => $customField->grid_value,
                        "info" => "Bootstrap grid size for form layout",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "group",
                        "label" => "Field Options",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "is_table",
                                "label" => "Show in Table",
                                "value" => $customField->is_table ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_invoice",
                                "label" => "Show in Invoice",
                                "value" => $customField->is_invoice ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_required",
                                "label" => "Required Field",
                                "value" => $customField->is_required ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_admin",
                                "label" => "Admin Only",
                                "value" => $customField->is_admin ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_disable",
                                "label" => "Disabled",
                                "value" => $customField->is_disable ? true : false,
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading custom field edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Validate the request
            $request->validate([
                'belongs_to' => 'required|string|in:product,sale,purchase,customer',
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:text,number,textarea,date_picker,select',
                'grid_value' => 'required|integer|min:1|max:12',
            ]);

            // Retrieve the custom field record
            $customField = CustomField::findOrFail($id);

            // Map the 'belongs_to' value to table names
            $tableName = $this->getTableName($customField->belongs_to);

            if (!$tableName || !Schema::hasTable($tableName)) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'The specified table does not exist.',
                ], 400);
            }

            // Update custom field record in the database
            $customField->update([
                'belongs_to' => $request->belongs_to,
                'name' => $request->name,
                'type' => $request->type,
                'default_value' => $request->input('default_value_1') ?? $request->input('default_value_2'),
                'option_value' => $request->input('option_value'),
                'grid_value' => $request->grid_value,
                'is_table' => $request->has('is_table'),
                'is_invoice' => $request->has('is_invoice'),
                'is_required' => $request->has('is_required'),
                'is_admin' => $request->has('is_admin'),
                'is_disable' => $request->has('is_disable'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Custom Field updated successfully.',
                'navigate_url' => '/custom-fields'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the custom field.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete custom fields
            if (!$role->hasPermissionTo('custom_field')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $customField = CustomField::findOrFail($id);

            // Determine the table name based on 'belongs_to' field
            $tableName = $this->getTableName($customField->belongs_to);

            if (!$tableName) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Invalid custom field table.',
                ], 400);
            }

            // Convert the custom field name to a column name (lowercase, spaces replaced by underscores)
            $columnName = str_replace(" ", "_", strtolower($customField->name));

            // Check if the column exists and drop it
            if (Schema::hasColumn($tableName, $columnName)) {
                // Drop the column from the table
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->dropColumn($columnName);
                });
            }

            // Delete the custom field data from the database
            $customField->delete();

            return response()->json([
                'success' => true,
                'message' => 'Custom Field deleted successfully.',
                'navigate_url' => '/custom-fields'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the custom field.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getTableName($belongsTo)
    {
        return [
            'product' => 'products',
            'sale' => 'sales',
            'purchase' => 'purchases',
            'customer' => 'customers',
        ][$belongsTo] ?? null;
    }
}
