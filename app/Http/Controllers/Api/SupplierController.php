<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Purchase;
use App\Models\CashRegister;
use App\Models\Account;
use App\Models\Payment;
use App\Models\MailSetting;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\SupplierCreate;
use App\Mail\CustomerCreate;
use Illuminate\Support\Facades\Mail;
use App\Traits\TenantInfo;

class SupplierController extends Controller
{
    use \App\Traits\MailInfo;
    use \App\Traits\APIPaginationTrait, TenantInfo;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the supplier module
            if (!$role->hasPermissionTo('suppliers-index')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            // Get suppliers with their due amounts
            $query = Supplier::where('is_active', true);
            $suppliers = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format suppliers for datatable
            $suppliersTable = $suppliers->map(function ($supplier) {
                // Calculate total due
                $returnedAmount = DB::table('purchases')
                    ->join('return_purchases', 'purchases.id', '=', 'return_purchases.purchase_id')
                    ->where([
                        ['purchases.supplier_id', $supplier->id],
                        ['purchases.payment_status', 1]
                    ])
                    ->sum('return_purchases.grand_total');

                $purchaseData = Purchase::where([
                    ['supplier_id', $supplier->id],
                    ['payment_status', 1]
                ])
                    ->selectRaw('SUM(grand_total) as grand_total, SUM(paid_amount) as paid_amount')
                    ->first();

                $totalDue = ($purchaseData->grand_total ?? 0) - ($returnedAmount ?? 0) - ($purchaseData->paid_amount ?? 0);

                // Build supplier details HTML
                $supplierDetails = $supplier->name;
                $supplierDetails .= '<br>' . ($supplier->company_name ?? '');
                if ($supplier->vat_number) {
                    $supplierDetails .= '<br>' . $supplier->vat_number;
                }
                $supplierDetails .= '<br>' . ($supplier->email ?? '');
                $supplierDetails .= '<br>' . ($supplier->phone_number ?? '');
                $supplierDetails .= '<br>' . ($supplier->address ?? '') . ', ' . ($supplier->city ?? '');
                if ($supplier->state) {
                    $supplierDetails .= ', ' . $supplier->state;
                }
                if ($supplier->postal_code) {
                    $supplierDetails .= ', ' . $supplier->postal_code;
                }
                if ($supplier->country) {
                    $supplierDetails .= ', ' . $supplier->country;
                }

                return [
                    'id' => $supplier->id,
                    'image_url' => $supplier->image
                        ? url('images/supplier', $supplier->image)
                        : url('images/product/zummXD2dvAtI.png'),
                    'supplier_details' => $supplierDetails,
                    'total_due' => number_format($totalDue, 2),
                ];
            });

            return $this->withDashBackground([
                'title' => "Suppliers",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Supplier',
                'add_url' => '/suppliers/create',
                'import_url' => '/suppliers/import',
                'columns' => [
                    [
                        'label' => 'Image',
                        'field' => 'image_url',
                        'type' => 'image',
                    ],
                    [
                        'label' => 'Supplier Details',
                        'field' => 'supplier_details',
                        'type' => 'html',
                    ],
                    [
                        'label' => 'Total Due',
                        'field' => 'total_due',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/suppliers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/suppliers/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ]
                ],
                'rows' => $suppliersTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while retrieving supplier data.');
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add suppliers
            if (!$role->hasPermissionTo('suppliers-add')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            // Get customer groups for the both checkbox option
            $customerGroups = CustomerGroup::where('is_active', true)->get();
            $customerGroupOptions = $customerGroups->map(function ($group) {
                return [
                    'label' => $group->name,
                    'value' => $group->id
                ];
            })->toArray();

            $formSchema = [
                "title" => "Add a New Supplier",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/suppliers",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "checkbox",
                        "name" => "both",
                        "label" => "Both Customer and Supplier",
                        "value" => false,
                    ],
                    [
                        "type" => "select",
                        "name" => "customer_group_id",
                        "label" => "Customer Group",
                        "options" => $customerGroupOptions,
                        "info" => "Only required when creating both customer and supplier",
                        "logics" => [
                            [
                                "field" => "both",
                                "values" => [true],
                            ],
                        ],
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter supplier name",
                    ],
                    [
                        "type" => "file",
                        "name" => "image",
                        "label" => "Image",
                        "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                        "multiple" => false,
                    ],
                    [
                        "type" => "text",
                        "name" => "company_name",
                        "label" => "Company Name",
                        "placeholder" => "Enter company name",
                    ],
                    [
                        "type" => "text",
                        "name" => "vat_number",
                        "label" => "VAT Number",
                        "placeholder" => "Enter VAT number",
                        "keyboard_type" => "number",
                    ],
                    [
                        "type" => "text",
                        "name" => "email",
                        "label" => "Email",
                        "placeholder" => "Enter email",
                        "keyboard_type" => "email",
                    ],
                    [
                        "type" => "text",
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                    ],
                    [
                        "type" => "text",
                        "name" => "city",
                        "label" => "City",
                        "placeholder" => "Enter city",
                    ],
                    [
                        "type" => "text",
                        "name" => "state",
                        "label" => "State",
                        "placeholder" => "Enter state",
                    ],
                    [
                        "type" => "text",
                        "name" => "postal_code",
                        "label" => "Postal Code",
                        "placeholder" => "Enter postal code",
                    ],
                    [
                        "type" => "text",
                        "name" => "country",
                        "label" => "Country",
                        "placeholder" => "Enter country",
                    ],
                    [
                        "type" => "text",
                        "name" => "opening_balance",
                        "label" => "Opening Balance (Due)",
                        "placeholder" => "Enter opening balance",
                        "keyboard_type" => "number",
                        "info" => "Add Supplier's old due amount here.",
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
            return new ErrorResource('An error occurred while loading the form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to add suppliers
            if (!$role->hasPermissionTo('suppliers-add')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $this->validate($request, [
                'company_name' => [
                    'max:255',
                    Rule::unique('suppliers')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'email' => [
                    'max:255',
                    Rule::unique('suppliers')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            //validation for customer if create both user and supplier
            if (isset($request->both)) {
                $this->validate($request, [
                    'phone_number' => [
                        'max:255',
                        Rule::unique('customers')->where(function ($query) {
                            return $query->where('is_active', 1);
                        }),
                    ],
                ]);
            }

            $lims_supplier_data = $request->except('image', 'token');
            $lims_supplier_data['is_active'] = true;
            $image = $request->image;
            if ($image) {
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['company_name']);
                $imageName = $imageName . '.' . $ext;

                if (config('database.connections.saleprosaas_landlord')) {
                    $imageName = $this->getTenantId() . '_' . $imageName;
                }

                $image->move(public_path('images/supplier'), $imageName);
                $lims_supplier_data['image'] = $imageName;
            }
            $supplier = Supplier::create($lims_supplier_data);

            // create dummy purchase if supplier has opening balance (due)
            if (isset($lims_supplier_data['opening_balance']) && $lims_supplier_data['opening_balance'] > 0) {
                $lims_purchase_data = new Purchase();
                $lims_purchase_data->reference_no = 'sob-' . date("Ymd") . '-' . date("his"); //customer opening balance
                $lims_purchase_data->supplier_id = $supplier->id;
                $lims_purchase_data->user_id = Auth::id();
                $lims_purchase_data->warehouse_id = 1;
                $lims_purchase_data->item = 0;
                $lims_purchase_data->total_qty = 0;
                $lims_purchase_data->total_discount = 0;
                $lims_purchase_data->total_tax = 0;
                $lims_purchase_data->total_cost = $lims_supplier_data['opening_balance'];
                $lims_purchase_data->grand_total = $lims_supplier_data['opening_balance'];
                $lims_purchase_data->status = 1; // completed
                $lims_purchase_data->payment_status = 1; // pending
                $lims_purchase_data->paid_amount = 0;
                $lims_purchase_data->purchase_type = 'Opening balance';
                $lims_purchase_data->created_at = '1970-01-01 12:00:00';
                $lims_purchase_data->save();
            }

            $message = 'Supplier';
            if (isset($request->both)) {
                Customer::create($lims_supplier_data);
                $message .= ' and Customer';
            }
            $mail_setting = MailSetting::latest()->first();
            if ($lims_supplier_data['email'] && $mail_setting) {
                $this->setMailInfo($mail_setting);
                try {
                    Mail::to($lims_supplier_data['email'])->send(new SupplierCreate($lims_supplier_data));
                    if (isset($request->both))
                        Mail::to($lims_supplier_data['email'])->send(new CustomerCreate($lims_supplier_data));
                    $message .= ' created successfully!';
                } catch (\Exception $e) {
                    $message .= ' created successfully. Please setup your mail setting to send mail.';
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully.',
                'data' => $supplier,
                'navigate_url' => '/suppliers',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return new ErrorResource($e->validator->errors());
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while creating the supplier.');
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view suppliers
            if (!$role->hasPermissionTo('suppliers-index')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $supplier = Supplier::where('id', $id)->where('is_active', true)->first();

            if (!$supplier) {
                return new ErrorResource('Supplier not found.');
            }

            return new SupplierResource($supplier);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while retrieving the supplier.');
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit suppliers
            if (!$role->hasPermissionTo('suppliers-edit')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $supplier = Supplier::where('id', $id)->where('is_active', true)->first();

            if (!$supplier) {
                return new ErrorResource('Supplier not found.');
            }

            $formSchema = [
                "title" => "Update Supplier",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/suppliers/" . $id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter supplier name",
                        "value" => $supplier->name,
                    ],
                    [
                        "type" => "file",
                        "name" => "image",
                        "label" => "Image",
                        "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                        "multiple" => false,
                    ],
                    [
                        "type" => "text",
                        "name" => "company_name",
                        "label" => "Company Name",
                        "placeholder" => "Enter company name",
                        "value" => $supplier->company_name,
                    ],
                    [
                        "type" => "text",
                        "name" => "vat_number",
                        "label" => "VAT Number",
                        "placeholder" => "Enter VAT number",
                        "keyboard_type" => "number",
                        "value" => $supplier->vat_number,
                    ],
                    [
                        "type" => "text",
                        "name" => "email",
                        "label" => "Email",
                        "placeholder" => "Enter email",
                        "keyboard_type" => "email",
                        "value" => $supplier->email,
                    ],
                    [
                        "type" => "text",
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                        "value" => $supplier->phone_number,
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "value" => $supplier->address,
                    ],
                    [
                        "type" => "text",
                        "name" => "city",
                        "label" => "City",
                        "placeholder" => "Enter city",
                        "value" => $supplier->city,
                    ],
                    [
                        "type" => "text",
                        "name" => "state",
                        "label" => "State",
                        "placeholder" => "Enter state",
                        "value" => $supplier->state,
                    ],
                    [
                        "type" => "text",
                        "name" => "postal_code",
                        "label" => "Postal Code",
                        "placeholder" => "Enter postal code",
                        "value" => $supplier->postal_code,
                    ],
                    [
                        "type" => "text",
                        "name" => "country",
                        "label" => "Country",
                        "placeholder" => "Enter country",
                        "value" => $supplier->country,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while loading the edit form.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit suppliers
            if (!$role->hasPermissionTo('suppliers-edit')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $this->validate($request, [
                'company_name' => [
                    'max:255',
                    Rule::unique('suppliers')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'email' => [
                    'max:255',
                    Rule::unique('suppliers')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            $lims_supplier_data = Supplier::findOrFail($id);

            $input = $request->except('image', 'token');
            $image = $request->image;
            if ($image) {
                $this->fileDelete(public_path('images/supplier/'), $lims_supplier_data->image);

                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['company_name']);
                $imageName = $imageName . '.' . $ext;

                if (config('database.connections.saleprosaas_landlord')) {
                    $imageName = $this->getTenantId() . '_' . $imageName;
                }

                $image->move(public_path('images/supplier'), $imageName);
                $input['image'] = $imageName;
            }

            $lims_supplier_data->update($input);

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully.',
                'data' => $lims_supplier_data,
                'navigate_url' => '/suppliers',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return new ErrorResource($e->validator->errors());
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while updating the supplier.');
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create suppliers
            if (!$role->hasPermissionTo('suppliers-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import suppliers.',
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
                    $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                    array_push($escapedHeader, $escapedItem);
                }

                $importedCount = 0;
                // Loop through rows
                while ($columns = fgetcsv($file)) {
                    if ($columns[0] == "")
                        continue;

                    $data = array_combine($escapedHeader, $columns);

                    $supplier = Supplier::firstOrNew(['company_name' => $data['companyname']]);
                    $supplier->name = $data['name'];
                    $supplier->company_name = $data['companyname'];
                    $supplier->vat_number = $data['vatnumber'] ?? null;
                    $supplier->email = $data['email'] ?? null;
                    $supplier->phone_number = $data['phonenumber'] ?? null;
                    $supplier->address = $data['address'] ?? null;
                    $supplier->city = $data['city'] ?? null;
                    $supplier->state = $data['state'] ?? null;
                    $supplier->postal_code = $data['postalcode'] ?? null;
                    $supplier->country = $data['country'] ?? null;
                    $supplier->is_active = true;
                    $supplier->save();

                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('supplier_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} suppliers.",
                    'navigate_url' => '/suppliers',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Suppliers",
                "submit_url" => "/suppliers/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, company_name, email, phone_number, address, city, state, postal_code, country) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_supplier.csv'),
                        "sample_file_name" => "sample_supplier.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'), 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete suppliers
            if (!$role->hasPermissionTo('suppliers-delete')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $lims_supplier_data = Supplier::findOrFail($id);
            $lims_supplier_data->is_active = false;
            $lims_supplier_data->save();
            $this->fileDelete(public_path('images/supplier/'), $lims_supplier_data->image);

            return response()->json([
                'success' => true,
                'message' => 'Supplier has been deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while deleting the supplier.');
        }
    }

    public function clearDue(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to clear due
            if (!$role->hasPermissionTo('suppliers-edit')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $lims_due_purchase_data = Purchase::select('id', 'warehouse_id', 'grand_total', 'paid_amount', 'payment_status')
                ->where([
                    ['payment_status', 1],
                    ['supplier_id', $request->supplier_id]
                ])->get();
            $total_paid_amount = $request->amount;
            foreach ($lims_due_purchase_data as $key => $purchase_data) {
                if ($total_paid_amount == 0)
                    break;
                $due_amount = $purchase_data->grand_total - $purchase_data->paid_amount;
                $lims_cash_register_data = CashRegister::select('id')
                    ->where([
                        ['user_id', Auth::id()],
                        ['warehouse_id', $purchase_data->warehouse_id],
                        ['status', 1]
                    ])->first();
                if ($lims_cash_register_data)
                    $cash_register_id = $lims_cash_register_data->id;
                else
                    $cash_register_id = null;
                $account_data = Account::select('id')->where('is_default', 1)->first();
                if ($total_paid_amount >= $due_amount) {
                    $paid_amount = $due_amount;
                    $payment_status = 2;
                } else {
                    $paid_amount = $total_paid_amount;
                    $payment_status = 1;
                }
                Payment::create([
                    'payment_reference' => 'ppr-' . date("Ymd") . '-' . date("his"),
                    'purchase_id' => $purchase_data->id,
                    'user_id' => Auth::id(),
                    'cash_register_id' => $cash_register_id,
                    'account_id' => $account_data->id,
                    'amount' => $paid_amount,
                    'change' => 0,
                    'paying_method' => 'Cash',
                    'payment_note' => $request->note
                ]);
                $purchase_data->paid_amount += $paid_amount;
                $purchase_data->payment_status = $payment_status;
                $purchase_data->save();
                $total_paid_amount -= $paid_amount;
            }
            return response()->json([
                'success' => true,
                'message' => 'Due cleared successfully'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while clearing the due.');
        }
    }

    public function importSupplier(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to import suppliers
            if (!$role->hasPermissionTo('suppliers-add')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            $upload = $request->file('file');
            $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
            if ($ext != 'csv')
                return new ErrorResource('Please upload a CSV file');
            $filename = $upload->getClientOriginalName();
            $filePath = $upload->getRealPath();
            //open and read
            $file = fopen($filePath, 'r');
            $header = fgetcsv($file);
            $escapedHeader = [];
            //validate
            foreach ($header as $key => $value) {
                $lheader = strtolower($value);
                $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                array_push($escapedHeader, $escapedItem);
            }
            //looping through other columns
            while ($columns = fgetcsv($file)) {
                if ($columns[0] == "")
                    continue;
                foreach ($columns as $key => $value) {
                    $value = preg_replace('/\D/', '', $value);
                }
                $data = array_combine($escapedHeader, $columns);

                $supplier = Supplier::firstOrNew(['company_name' => $data['companyname']]);
                $supplier->name = $data['name'];
                $supplier->image = $data['image'];
                $supplier->vat_number = $data['vatnumber'];
                $supplier->email = $data['email'];
                $supplier->phone_number = $data['phonenumber'];
                $supplier->address = $data['address'];
                $supplier->city = $data['city'];
                $supplier->state = $data['state'];
                $supplier->postal_code = $data['postalcode'];
                $supplier->country = $data['country'];
                $supplier->is_active = true;
                $supplier->save();
                $message = 'Supplier Imported Successfully';

                $mail_setting = MailSetting::latest()->first();

                if ($data['email'] && $mail_setting) {
                    try {
                        Mail::to($data['email'])->send(new SupplierCreate($data));
                    } catch (\Exception $e) {
                        $message = 'Supplier imported successfully. Please setup your mail setting to send mail.';
                    }
                }
            }
            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while importing suppliers.');
        }
    }

    public function suppliersAll()
    {
        try {
            $lims_supplier_list = DB::table('suppliers')->where('is_active', true)->get();

            $suppliers = $lims_supplier_list->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name . ' (' . $supplier->phone_number . ')'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource('An error occurred while retrieving suppliers.');
        }
    }
}
