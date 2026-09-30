<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Barcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BarcodeController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            // Retrieve barcode settings (only custom ones)
            $query = Barcode::where('is_custom', true);
            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $barcodes = $query->orderBy('id', 'desc')->get();

            // Format barcodes for datatable
            $barcodesTable = $barcodes->map(function ($barcode) {
                return [
                    'id' => $barcode->id,
                    'name' => $barcode->name,
                    'description' => $barcode->description ?? 'N/A',
                    'size' => $barcode->width . '" x ' . $barcode->height . '"',
                    'is_default' => $barcode->is_default ? "<span style='color: green; font-weight: bold;'>Default</span>"
                        : "<span style='color: gray;'>-</span>",
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Barcode Settings",
                'row_height' => 5,
                'add_text' => 'Add Setting',
                'add_url' => '/barcodes/create',
                'columns' => [
                    [
                        'label' => 'Name',
                        'field' => 'name',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Description',
                        'field' => 'description',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Size (W x H)',
                        'field' => 'size',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Default',
                        'field' => 'is_default',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'button',
                                'label' => 'Set Default',
                                'action' => [
                                    'api_url' => '/barcodes/{id}/set-default',
                                    'type' => 'custom_api'
                                ],
                                "logics" => [
                                    [
                                        "field" => "is_default",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/barcodes/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/barcodes/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $barcodesTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving barcode settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Barcode Sticker Setting",
                "submit_url" => "/barcodes",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Basic Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Sticker Sheet Setting Name *",
                                "placeholder" => "Enter sticker sheet setting name",
                            ],
                            [
                                "type" => "text",
                                "name" => "description",
                                "label" => "Description",
                                "placeholder" => "Enter description",
                                "multiline" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_continuous",
                                "label" => "Continuous feed or rolls",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default",
                                "value" => false,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "is_custom",
                                "value" => 1,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Sticker Dimensions (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "width",
                                "label" => "Width of Sticker *",
                                "placeholder" => "Enter width",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "text",
                                "name" => "height",
                                "label" => "Height of Sticker *",
                                "placeholder" => "Enter height",
                                "keyboard_type" => "number",
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Margins (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "top_margin",
                                "label" => "Additional Top Margin *",
                                "placeholder" => "Enter top margin",
                                "keyboard_type" => "number",
                                "value" => "0",
                            ],
                            [
                                "type" => "text",
                                "name" => "left_margin",
                                "label" => "Additional Left Margin *",
                                "placeholder" => "Enter left margin",
                                "keyboard_type" => "number",
                                "value" => "0",
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Paper Settings (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "paper_width",
                                "label" => "Paper Width *",
                                "placeholder" => "Enter paper width",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "text",
                                "name" => "paper_height",
                                "label" => "Paper Height *",
                                "placeholder" => "Enter paper height",
                                "keyboard_type" => "number",
                                "logics" => [
                                    [
                                        "field" => "is_continuous",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Layout Settings",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "stickers_in_one_row",
                                "label" => "Stickers in One Row *",
                                "placeholder" => "Enter number of stickers per row",
                                "keyboard_type" => "number",
                            ],
                            [
                                "type" => "text",
                                "name" => "stickers_in_one_sheet",
                                "label" => "No of Stickers per Sheet *",
                                "placeholder" => "Enter number of stickers per sheet",
                                "keyboard_type" => "number",
                                "logics" => [
                                    [
                                        "field" => "is_continuous",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "row_distance",
                                "label" => "Distance between Two Rows *",
                                "placeholder" => "Enter row distance",
                                "keyboard_type" => "number",
                                "value" => "0",
                            ],
                            [
                                "type" => "text",
                                "name" => "col_distance",
                                "label" => "Distance between Two Columns *",
                                "placeholder" => "Enter column distance",
                                "keyboard_type" => "number",
                                "value" => "0",
                            ],
                        ],
                    ],
                ],
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

            // Check if the user has permission to create barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'width' => 'required|numeric|min:0.1',
                'height' => 'required|numeric|min:0.1',
                'top_margin' => 'required|numeric|min:0',
                'left_margin' => 'required|numeric|min:0',
                'paper_width' => 'required|numeric|min:0.1',
                'stickers_in_one_row' => 'required|integer|min:1',
                'row_distance' => 'required|numeric|min:0',
                'col_distance' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'description' => $request->description ?? '',
                'width' => $request->width,
                'height' => $request->height,
                'top_margin' => $request->top_margin,
                'left_margin' => $request->left_margin,
                'row_distance' => $request->row_distance,
                'col_distance' => $request->col_distance,
                'stickers_in_one_row' => $request->stickers_in_one_row,
                'paper_width' => $request->paper_width,
                'is_custom' => 1,
                'is_default' => isset($request->is_default) ? 1 : 0,
            ];

            if (isset($request->is_continuous) && $request->is_continuous) {
                $data['is_continuous'] = 1;
                $data['stickers_in_one_sheet'] = 28;
                $data['paper_height'] = 0;
            } else {
                $data['is_continuous'] = 0;
                $data['stickers_in_one_sheet'] = $request->stickers_in_one_sheet ?? 1;
                $data['paper_height'] = $request->paper_height ?? 0;
            }

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                Barcode::where('is_default', 1)->update(['is_default' => 0]);
            }

            $barcode = Barcode::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barcode setting created successfully.',
                'data' => $barcode,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while creating the barcode setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $barcode = Barcode::where('is_custom', true)->findOrFail($id);

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Barcode Sticker Setting",
                "submit_url" => "/barcodes/" . $id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Basic Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Sticker Sheet Setting Name *",
                                "placeholder" => "Enter sticker sheet setting name",
                                "value" => $barcode->name,
                            ],
                            [
                                "type" => "text",
                                "name" => "description",
                                "label" => "Description",
                                "placeholder" => "Enter description",
                                "multiline" => true,
                                "value" => $barcode->description ?? '',
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_continuous",
                                "label" => "Continuous feed or rolls",
                                "value" => (bool)$barcode->is_continuous,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default",
                                "value" => (bool)$barcode->is_default,
                            ],
                            [
                                "type" => "hidden",
                                "name" => "is_custom",
                                "value" => 1,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Sticker Dimensions (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "width",
                                "label" => "Width of Sticker *",
                                "placeholder" => "Enter width",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->width,
                            ],
                            [
                                "type" => "text",
                                "name" => "height",
                                "label" => "Height of Sticker *",
                                "placeholder" => "Enter height",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->height,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Margins (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "top_margin",
                                "label" => "Additional Top Margin *",
                                "placeholder" => "Enter top margin",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->top_margin,
                            ],
                            [
                                "type" => "text",
                                "name" => "left_margin",
                                "label" => "Additional Left Margin *",
                                "placeholder" => "Enter left margin",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->left_margin,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Paper Settings (In Inches)",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "paper_width",
                                "label" => "Paper Width *",
                                "placeholder" => "Enter paper width",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->paper_width,
                            ],
                            [
                                "type" => "text",
                                "name" => "paper_height",
                                "label" => "Paper Height *",
                                "placeholder" => "Enter paper height",
                                "keyboard_type" => "number",
                                "value" => (string)($barcode->paper_height ?? 0),
                                "logics" => [
                                    [
                                        "field" => "is_continuous",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Layout Settings",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "stickers_in_one_row",
                                "label" => "Stickers in One Row *",
                                "placeholder" => "Enter number of stickers per row",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->stickers_in_one_row,
                            ],
                            [
                                "type" => "text",
                                "name" => "stickers_in_one_sheet",
                                "label" => "No of Stickers per Sheet *",
                                "placeholder" => "Enter number of stickers per sheet",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->stickers_in_one_sheet,
                                "logics" => [
                                    [
                                        "field" => "is_continuous",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "row_distance",
                                "label" => "Distance between Two Rows *",
                                "placeholder" => "Enter row distance",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->row_distance,
                            ],
                            [
                                "type" => "text",
                                "name" => "col_distance",
                                "label" => "Distance between Two Columns *",
                                "placeholder" => "Enter column distance",
                                "keyboard_type" => "number",
                                "value" => (string)$barcode->col_distance,
                            ],
                        ],
                    ],
                ],
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

            // Check if the user has permission to update barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $barcode = Barcode::where('is_custom', true)->findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'width' => 'required|numeric|min:0.1',
                'height' => 'required|numeric|min:0.1',
                'top_margin' => 'required|numeric|min:0',
                'left_margin' => 'required|numeric|min:0',
                'paper_width' => 'required|numeric|min:0.1',
                'stickers_in_one_row' => 'required|integer|min:1',
                'row_distance' => 'required|numeric|min:0',
                'col_distance' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'description' => $request->description ?? '',
                'width' => $request->width,
                'height' => $request->height,
                'top_margin' => $request->top_margin,
                'left_margin' => $request->left_margin,
                'row_distance' => $request->row_distance,
                'col_distance' => $request->col_distance,
                'stickers_in_one_row' => $request->stickers_in_one_row,
                'paper_width' => $request->paper_width,
                'is_custom' => 1,
                'is_default' => isset($request->is_default) ? 1 : 0,
            ];

            if (isset($request->is_continuous) && $request->is_continuous) {
                $data['is_continuous'] = 1;
                $data['stickers_in_one_sheet'] = 28;
                $data['paper_height'] = 0;
            } else {
                $data['is_continuous'] = 0;
                $data['stickers_in_one_sheet'] = $request->stickers_in_one_sheet ?? 1;
                $data['paper_height'] = $request->paper_height ?? 0;
            }

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                Barcode::where('is_default', 1)->where('id', '!=', $id)->update(['is_default' => 0]);
            }

            $barcode->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barcode setting updated successfully.',
                'data' => $barcode,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while updating the barcode setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setDefault($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to modify barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            DB::beginTransaction();

            // Unset current default
            Barcode::where('is_default', 1)->update(['is_default' => 0]);

            // Set new default
            $barcode = Barcode::where('is_custom', true)->findOrFail($id);
            $barcode->update(['is_default' => 1]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barcode setting set as default successfully.',
                'data' => $barcode,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while setting default barcode setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete barcode settings
            if (!$role->hasPermissionTo('barcode_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $barcode = Barcode::where('is_custom', true)->findOrFail($id);

            // Prevent deletion of default barcode setting
            if ($barcode->is_default) {
                return new ErrorResource([
                    'message' => 'Cannot delete the default barcode setting.',
                ]);
            }

            $barcode->delete();

            return response()->json([
                'success' => true,
                'message' => 'Barcode setting deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the barcode setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
