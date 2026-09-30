<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessDataCollection;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantInfo;

class InvoiceSettingController extends Controller
{
    use TenantInfo;

    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access invoice settings
            if (!$role->hasPermissionTo('invoice_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            // Retrieve invoice settings
            $query = InvoiceSetting::query();
            if (!empty($search)) {
                $query->where('template_name', 'LIKE', "%{$search}%");
            }

            $invoiceSettings = $query->orderBy('id', 'desc')->get();

            // Format invoice settings for datatable
            $invoiceSettingsTable = $invoiceSettings->map(function ($invoiceSetting) {
                return [
                    'id' => $invoiceSetting->id,
                    'template_name' => $invoiceSetting->template_name,
                    'size' => ucfirst($invoiceSetting->size),
                    'is_default_value' => $invoiceSetting->is_default,
                    'is_default' => $invoiceSetting->is_default ? "<span style='color: green; font-weight: bold;'>Default</span>" : "<span style='color: gray;'>-</span>",
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Invoice Settings",
                "row_height" => 5,
                "add_url" => "/invoice-settings/create",
                "columns" => [
                    [
                        "label" => "Template Name",
                        "field" => "template_name",
                        "type" => "text"
                    ],
                    [
                        "label" => "Size",
                        "field" => "size",
                        "type" => "text"
                    ],
                    [
                        "label" => "Default",
                        "field" => "is_default",
                        "type" => "html",
                    ],
                    [
                        "label" => "Manage",
                        "type" => "row",
                        "children" => [
                            [
                                "type" => "button",
                                "label" => "Set Default",
                                "action" => [
                                    "api_url" => "/invoice-settings/{id}/set-default",
                                    "type" => "custom_api"
                                ],
                                "logics" => [
                                    [
                                        "field" => "is_default_value",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                            [
                                "type" => "action",
                                "icon" => "edit",
                                "action" => [
                                    "api_url" => "/invoice-settings/{id}/edit",
                                    "type" => "form"
                                ]
                            ],
                            [
                                "type" => "action",
                                "icon" => "delete",
                                "action" => [
                                    "api_url" => "/invoice-settings/{id}",
                                    "type" => "delete"
                                ]
                            ]
                        ]
                    ]
                ],
                "rows" => $invoiceSettingsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving invoice settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Invoice Setting",
                "submit_url" => "/invoice-settings",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Basic Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "template_name",
                                "label" => "Template Name *",
                                "placeholder" => "Enter template name",
                            ],
                            [
                                "type" => "select",
                                "name" => "size",
                                "label" => "Invoice Size *",
                                "options" => [
                                    ["label" => "A4", "value" => "a4"],
                                    ["label" => "Letter", "value" => "letter"],
                                    ["label" => "Legal", "value" => "legal"],
                                ],
                                "value" => "a4",
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default Template",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "status",
                                "label" => "Active Status",
                                "value" => true,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Template Configuration",
                        "items" => [
                            [
                                "type" => "file",
                                "name" => "preview_invoice",
                                "label" => "Preview Invoice Template",
                                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "text",
                                "name" => "primary_color",
                                "label" => "Primary Color",
                                "placeholder" => "#000000",
                            ],
                            [
                                "type" => "text",
                                "name" => "footer_text",
                                "label" => "Footer Text",
                                "placeholder" => "Enter footer text",
                                "multiline" => true,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Display Options",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "show_barcode",
                                "label" => "Show Barcode",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_qr_code",
                                "label" => "Show QR Code",
                                "value" => false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_description",
                                "label" => "Show Product Description",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_in_words",
                                "label" => "Show Amount in Words",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_warehouse_info",
                                "label" => "Show Warehouse Information",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_bill_to_info",
                                "label" => "Show Bill To Information",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_biller_info",
                                "label" => "Show Biller Information",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_paid_info",
                                "label" => "Show Payment Information",
                                "value" => true,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "hide_total_due",
                                "label" => "Hide Total Due",
                                "value" => false,
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

            // Check if the user has permission to create invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'template_name' => 'required|string|max:255',
                'size' => 'required|string|in:a4,letter,legal',
            ]);

            DB::beginTransaction();

            $data = [
                'template_name' => $request->template_name,
                'size' => $request->size,
                'primary_color' => $request->primary_color ?? '#000000',
                'footer_text' => $request->footer_text ?? '',
                'status' => isset($request->status) ? 1 : 0,
                'is_default' => isset($request->is_default) ? 1 : 0,
            ];

            // Handle file upload
            if ($request->hasFile('preview_invoice')) {
                $data['preview_invoice'] = $this->uploadInvoiceTemplate($request->file('preview_invoice'));
            }

            // Handle display options
            $showColumn = [
                'show_barcode' => isset($request->show_barcode) ? 1 : 0,
                'show_qr_code' => isset($request->show_qr_code) ? 1 : 0,
                'show_description' => isset($request->show_description) ? 1 : 0,
                'show_in_words' => isset($request->show_in_words) ? 1 : 0,
                'show_warehouse_info' => isset($request->show_warehouse_info) ? 1 : 0,
                'show_bill_to_info' => isset($request->show_bill_to_info) ? 1 : 0,
                'show_biller_info' => isset($request->show_biller_info) ? 1 : 0,
                'show_paid_info' => isset($request->show_paid_info) ? 1 : 0,
                'hide_total_due' => isset($request->hide_total_due) ? 1 : 0,
            ];
            $data['show_column'] = json_encode($showColumn);

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                InvoiceSetting::where('is_default', 1)->update(['is_default' => 0]);
            }

            // If setting as active, unset other active
            if ($data['status']) {
                InvoiceSetting::where('status', 1)->update(['status' => 0]);
            }

            $invoiceSetting = InvoiceSetting::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice setting created successfully.',
                'data' => $invoiceSetting,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while creating the invoice setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $invoiceSetting = InvoiceSetting::findOrFail($id);
            $showColumn = json_decode($invoiceSetting->show_column, true) ?? [];

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Invoice Setting",
                "submit_url" => "/invoice-settings/" . $id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Basic Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "template_name",
                                "label" => "Template Name *",
                                "placeholder" => "Enter template name",
                                "value" => $invoiceSetting->template_name,
                            ],
                            [
                                "type" => "select",
                                "name" => "size",
                                "label" => "Invoice Size *",
                                "options" => [
                                    ["label" => "A4", "value" => "a4"],
                                    ["label" => "Letter", "value" => "letter"],
                                    ["label" => "Legal", "value" => "legal"],
                                ],
                                "value" => $invoiceSetting->size,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default Template",
                                "value" => (bool)$invoiceSetting->is_default,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "status",
                                "label" => "Active Status",
                                "value" => (bool)$invoiceSetting->status,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Template Configuration",
                        "items" => [
                            [
                                "type" => "file",
                                "name" => "preview_invoice",
                                "label" => "Preview Invoice Template",
                                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                                "multiple" => false,
                            ],
                            [
                                "type" => "text",
                                "name" => "primary_color",
                                "label" => "Primary Color",
                                "placeholder" => "#000000",
                                "value" => $invoiceSetting->primary_color ?? '#000000',
                            ],
                            [
                                "type" => "text",
                                "name" => "footer_text",
                                "label" => "Footer Text",
                                "placeholder" => "Enter footer text",
                                "multiline" => true,
                                "value" => $invoiceSetting->footer_text ?? '',
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Display Options",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "show_barcode",
                                "label" => "Show Barcode",
                                "value" => (bool)($showColumn['show_barcode'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_qr_code",
                                "label" => "Show QR Code",
                                "value" => (bool)($showColumn['show_qr_code'] ?? false),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_description",
                                "label" => "Show Product Description",
                                "value" => (bool)($showColumn['show_description'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_in_words",
                                "label" => "Show Amount in Words",
                                "value" => (bool)($showColumn['show_in_words'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_warehouse_info",
                                "label" => "Show Warehouse Information",
                                "value" => (bool)($showColumn['show_warehouse_info'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_bill_to_info",
                                "label" => "Show Bill To Information",
                                "value" => (bool)($showColumn['show_bill_to_info'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_biller_info",
                                "label" => "Show Biller Information",
                                "value" => (bool)($showColumn['show_biller_info'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_paid_info",
                                "label" => "Show Payment Information",
                                "value" => (bool)($showColumn['show_paid_info'] ?? true),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "hide_total_due",
                                "label" => "Hide Total Due",
                                "value" => (bool)($showColumn['hide_total_due'] ?? false),
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

            // Check if the user has permission to update invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $invoiceSetting = InvoiceSetting::findOrFail($id);

            $request->validate([
                'template_name' => 'required|string|max:255',
                'size' => 'required|string|in:a4,letter,legal',
            ]);

            DB::beginTransaction();

            $data = [
                'template_name' => $request->template_name,
                'size' => $request->size,
                'primary_color' => $request->primary_color ?? '#000000',
                'footer_text' => $request->footer_text ?? '',
                'status' => isset($request->status) ? 1 : 0,
                'is_default' => isset($request->is_default) ? 1 : 0,
            ];

            // Handle file upload
            if ($request->hasFile('preview_invoice')) {
                $data['preview_invoice'] = $this->uploadInvoiceTemplate($request->file('preview_invoice'));
            }

            // Handle display options
            $showColumn = [
                'show_barcode' => isset($request->show_barcode) ? 1 : 0,
                'show_qr_code' => isset($request->show_qr_code) ? 1 : 0,
                'show_description' => isset($request->show_description) ? 1 : 0,
                'show_in_words' => isset($request->show_in_words) ? 1 : 0,
                'show_warehouse_info' => isset($request->show_warehouse_info) ? 1 : 0,
                'show_bill_to_info' => isset($request->show_bill_to_info) ? 1 : 0,
                'show_biller_info' => isset($request->show_biller_info) ? 1 : 0,
                'show_paid_info' => isset($request->show_paid_info) ? 1 : 0,
                'hide_total_due' => isset($request->hide_total_due) ? 1 : 0,
            ];
            $data['show_column'] = json_encode($showColumn);

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                InvoiceSetting::where('is_default', 1)->where('id', '!=', $id)->update(['is_default' => 0]);
            }

            // If setting as active, unset other active
            if ($data['status']) {
                InvoiceSetting::where('status', 1)->where('id', '!=', $id)->update(['status' => 0]);
            }

            $invoiceSetting->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice setting updated successfully.',
                'data' => $invoiceSetting,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while updating the invoice setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setDefault($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to modify invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            DB::beginTransaction();

            // Unset current default
            InvoiceSetting::where('is_default', 1)->update(['is_default' => 0]);

            // Set new default
            $invoiceSetting = InvoiceSetting::findOrFail($id);
            $invoiceSetting->update(['is_default' => 1]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice setting set as default successfully.',
                'data' => $invoiceSetting,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while setting default invoice setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete invoice settings
            if (!$role->hasPermissionTo('invoice_create_edit_delete')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $invoiceSetting = InvoiceSetting::findOrFail($id);

            // Prevent deletion of default template
            if ($invoiceSetting->is_default) {
                return new ErrorResource([
                    'message' => 'Cannot delete the default invoice template.',
                ]);
            }

            $invoiceSetting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice setting deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while deleting the invoice setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function uploadInvoiceTemplate($file)
    {
        if ($file) {
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . '.' . $ext;

            if (config('database.connections.saleprosaas_landlord')) {
                $imageName = $this->getTenantId() . '_' . $imageName;
            }

            // Save original file first
            $file->move(public_path('invoices/'), $imageName);

            $manager = new ImageManager(new GdDriver());
            $image = $manager->read(public_path('invoices/') . $imageName);

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Only resize if wider than 300px
            if ($originalWidth > 300) {
                $newWidth = 300;
                $newHeight = intval(($originalHeight / $originalWidth) * $newWidth);

                // Resize explicitly
                $image->resize($newWidth, $newHeight);
            }

            // Save resized image
            if (!file_exists(public_path("invoices/small")) && !is_dir(public_path("invoices/small"))) {
                mkdir(public_path("invoices/small"), 0755, true);
            }
            $image->save(public_path('invoices/small/' . $imageName), quality: 100);

            return $imageName;
        }

        return null;
    }
}
