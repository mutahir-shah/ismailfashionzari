<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\Account;
use App\Models\MailSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\CustomerCreate;
use App\Mail\SupplierCreate;
use App\Mail\CustomerDeposit;
use Illuminate\Support\Facades\Mail;
use App\Models\CustomField;
use App\Http\Resources\ErrorResource;

class CustomerController extends Controller
{
    use \App\Traits\CacheForget;
    use \App\Traits\MailInfo;
    use \App\Traits\APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the customers module
            if (!$role->hasPermissionTo('customers-index')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = Customer::with('customerGroup', 'discountPlans')->where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%");
                });
            }

            $query = $query->orderBy('id', 'desc');
            $customers = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            // Format customers for datatable
            $customersTable = $customers->map(function ($customer) {
                // Customer Details
                $customerDetails = $customer->name;
                if ($customer->company_name) {
                    $customerDetails .= '<br>' . $customer->company_name;
                }
                if ($customer->email) {
                    $customerDetails .= '<br>' . $customer->email;
                }
                $customerDetails .= '<br>' . $customer->phone_number . '<br>' . $customer->address . '<br>' . $customer->city;
                if ($customer->country) {
                    $customerDetails .= '<br>' . $customer->country;
                }

                // Discount Plan
                $discountPlan = '';
                foreach ($customer->discountPlans as $index => $discount_plan) {
                    if ($index) {
                        $discountPlan .= ', ' . $discount_plan->name;
                    } else {
                        $discountPlan .= $discount_plan->name;
                    }
                }

                // Deposited Balance
                $depositedBalance = number_format($customer->deposit - $customer->expense, config('decimal'));

                // Total Due
                $returnedAmount = DB::table('sales')
                    ->join('returns', 'sales.id', '=', 'returns.sale_id')
                    ->where([
                        ['sales.customer_id', $customer->id],
                        ['sales.payment_status', '!=', 4]
                    ])
                    ->sum('returns.grand_total');

                $saleData = DB::table('sales')->where([
                    ['customer_id', $customer->id],
                    ['payment_status', '!=', 4]
                ])
                    ->selectRaw('SUM(grand_total) as grand_total, SUM(paid_amount) as paid_amount')
                    ->first();

                $totalDue = number_format(($saleData->grand_total ?? 0) - $returnedAmount - ($saleData->paid_amount ?? 0), config('decimal'));

                return [
                    'id' => $customer->id,
                    'customer_group' => $customer->customerGroup->name ?? 'N/A',
                    'customer_details' => $customerDetails,
                    'discount_plan' => $discountPlan,
                    'reward_point' => $customer->points ?? 0,
                    'deposited_balance' => $depositedBalance,
                    'total_due' => $totalDue,
                ];
            });

            return $this->withDashBackground([
                'title' => "Customers",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 8,
                'add_text' => 'Add Customer',
                'add_url' => '/customers/create',
                'import_url' => '/customers/import',
                'columns' => [
                    ['label' => 'Customer Group', 'field' => 'customer_group', 'type' => 'text'],
                    ['label' => 'Customer Details', 'field' => 'customer_details', 'type' => 'html'],
                    ['label' => 'Discount Plan', 'field' => 'discount_plan', 'type' => 'text'],
                    ['label' => 'Reward Points', 'field' => 'reward_point', 'type' => 'text'],
                    ['label' => 'Deposited Balance', 'field' => 'deposited_balance', 'type' => 'text'],
                    ['label' => 'Total Due', 'field' => 'total_due', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/customers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/customers/{id}',
                                    'type' => 'delete'
                                ]
                            ]
                        ]
                    ],
                ],
                'rows' => $customersTable,
                'pagination' => $pagination
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving customer data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('customers-add')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $customer_groups = CustomerGroup::where('is_active', true)->get()->map(function ($group) {
                return [
                    "value" => $group->id,
                    "label" => $group->name,
                ];
            });

            // Get custom fields for customer
            $custom_fields = CustomField::where('belongs_to', 'customer')->get();

            $fields = [
                [
                    "type" => "checkbox",
                    "name" => "both",
                    "label" => "Supplier too",
                    "info" => "Check this if this customer is also a supplier",
                ],
                [
                    "type" => "select",
                    "name" => "customer_group_id",
                    "label" => "Customer Group",
                    "options" => $customer_groups,
                ],
                [
                    "type" => "text",
                    "name" => "customer_name",
                    "label" => "Customer Name",
                    "placeholder" => "Enter customer name",
                ],
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter company name",
                ],
                [
                    "type" => "text",
                    "name" => "email",
                    "label" => "Email",
                    "placeholder" => "example@example.com",
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
                    "name" => "wa_number",
                    "label" => "WhatsApp Number",
                    "placeholder" => "Enter WhatsApp number",
                    "keyboard_type" => "phone",
                ],
                [
                    "type" => "text",
                    "name" => "tax_no",
                    "label" => "Tax Number",
                    "placeholder" => "Enter tax number",
                ],
                [
                    "type" => "text",
                    "name" => "address",
                    "label" => "Address",
                    "placeholder" => "Enter address",
                    "multiline" => true,
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
                    "info" => "Add Customer's old due amount here.",
                ],
                [
                    "type" => "text",
                    "name" => "deposit",
                    "label" => "Initial Deposit",
                    "placeholder" => "Enter initial deposit",
                    "keyboard_type" => "number",
                ],
                [
                    "type" => "text",
                    "name" => "credit_limit",
                    "label" => "Credit Limit",
                    "placeholder" => "Enter credit limit",
                    "keyboard_type" => "number",
                    "info" => "Leave it blank for unlimited credit",
                ],
            ];

            // Add custom fields dynamically
            foreach ($custom_fields as $field) {
                $fieldName = str_replace(' ', '_', strtolower($field->name));
                $fieldConfig = [
                    "name" => $fieldName,
                    "label" => $field->name,
                ];

                if ($field->is_required) {
                    $fieldConfig['required'] = true;
                }

                switch ($field->type) {
                    case 'text':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                    case 'number':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['keyboard_type'] = 'number';
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                    case 'textarea':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['multiline'] = true;
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                    case 'checkbox':
                        $fieldConfig['type'] = 'checkbox';
                        $fieldConfig['name'] = $fieldName . '[]';
                        if ($field->option) {
                            $fieldConfig['info'] = 'Options: ' . $field->option;
                        }
                        break;
                    case 'radio':
                        $fieldConfig['type'] = 'select';
                        if ($field->option) {
                            $options = explode(',', $field->option);
                            $fieldConfig['options'] = array_map(function ($opt) {
                                return ['value' => trim($opt), 'label' => trim($opt)];
                            }, $options);
                        }
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                    case 'select':
                        $fieldConfig['type'] = 'select';
                        if ($field->option) {
                            $options = explode(',', $field->option);
                            $fieldConfig['options'] = array_map(function ($opt) {
                                return ['value' => trim($opt), 'label' => trim($opt)];
                            }, $options);
                        }
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                    case 'multiselect':
                        $fieldConfig['type'] = 'select';
                        $fieldConfig['name'] = $fieldName . '[]';
                        $fieldConfig['enable_search'] = true;
                        if ($field->option) {
                            $options = explode(',', $field->option);
                            $fieldConfig['options'] = array_map(function ($opt) {
                                return ['value' => trim($opt), 'label' => trim($opt)];
                            }, $options);
                        }
                        break;
                    case 'date':
                        $fieldConfig['type'] = 'datepicker';
                        $fieldConfig['placeholder'] = 'Select date';
                        if ($field->default_value) {
                            $fieldConfig['value'] = $field->default_value;
                        }
                        break;
                }

                $fields[] = $fieldConfig;
            }

            // Add user creation fields
            $fields[] = [
                "type" => "helpertext",
                "text" => "User Account Information",
            ];
            $fields[] = [
                "type" => "checkbox",
                "name" => "user",
                "label" => "Create User Account",
                "info" => "Check this to create a user account for this customer",
            ];
            $fields[] = [
                "type" => "text",
                "name" => "name",
                "label" => "User Name",
                "placeholder" => "Enter username for login",
                "logics" => [
                    [
                        "field" => "user",
                        "values" => [true],
                    ],
                ],
            ];
            $fields[] = [
                "type" => "text",
                "name" => "password",
                "label" => "Password",
                "placeholder" => "Enter password",
                "keyboard_type" => "default",
                "logics" => [
                    [
                        "field" => "user",
                        "values" => [true],
                    ],
                ],
            ];
            $fields[] = [
                "type" => "hidden",
                "name" => "pos",
                "value" => 0,
            ];
            $fields[] = [
                "type" => "hidden",
                "name" => "is_active",
                "value" => 1,
            ];

            $formSchema = [
                "title" => "Add Customer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/customers",
                "method" => "POST",
                "navigate_url" => "/customers",
                "fields" => $fields,
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the form schema. Error: ' . $e->getMessage()), 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('customers-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $customer = Customer::where('is_active', true)->find($id);
            if (!$customer) {
                return response()->json(new ErrorResource('Customer not found.'), 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer retrieved successfully',
                'data' => new CustomerResource($customer)
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the customer.'), 500);
        }
    }

    public function edit($id)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('customers-edit')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $customer = Customer::find($id);
            if (!$customer || !$customer->is_active) {
                return response()->json(new ErrorResource('Customer not found.'), 404);
            }

            $customer_groups = CustomerGroup::where('is_active', true)->get()->map(function ($group) {
                return [
                    "value" => $group->id,
                    "label" => $group->name,
                ];
            });

            // Get custom fields for customer
            $custom_fields = CustomField::where([
                ['belongs_to', 'customer'],
                ['is_active', true]
            ])->get();

            $fields = [
                [
                    "type" => "select",
                    "name" => "customer_group_id",
                    "label" => "Customer Group",
                    "options" => $customer_groups,
                    "value" => $customer->customer_group_id,
                ],
                [
                    "type" => "text",
                    "name" => "customer_name",
                    "label" => "Customer Name",
                    "placeholder" => "Enter customer name",
                    "value" => $customer->name,
                ],
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter company name",
                    "value" => $customer->company_name,
                ],
                [
                    "type" => "text",
                    "name" => "email",
                    "label" => "Email",
                    "placeholder" => "example@example.com",
                    "keyboard_type" => "email",
                    "value" => $customer->email,
                ],
                [
                    "type" => "text",
                    "name" => "phone_number",
                    "label" => "Phone Number",
                    "placeholder" => "Enter phone number",
                    "keyboard_type" => "phone",
                    "value" => $customer->phone_number,
                ],
                [
                    "type" => "text",
                    "name" => "wa_number",
                    "label" => "WhatsApp Number",
                    "placeholder" => "Enter WhatsApp number",
                    "keyboard_type" => "phone",
                    "value" => $customer->wa_number,
                ],
                [
                    "type" => "text",
                    "name" => "tax_no",
                    "label" => "Tax Number",
                    "placeholder" => "Enter tax number",
                    "value" => $customer->tax_no,
                ],
                [
                    "type" => "text",
                    "name" => "address",
                    "label" => "Address",
                    "placeholder" => "Enter address",
                    "multiline" => true,
                    "value" => $customer->address,
                ],
                [
                    "type" => "text",
                    "name" => "city",
                    "label" => "City",
                    "placeholder" => "Enter city",
                    "value" => $customer->city,
                ],
                [
                    "type" => "text",
                    "name" => "state",
                    "label" => "State",
                    "placeholder" => "Enter state",
                    "value" => $customer->state,
                ],
                [
                    "type" => "text",
                    "name" => "postal_code",
                    "label" => "Postal Code",
                    "placeholder" => "Enter postal code",
                    "value" => $customer->postal_code,
                ],
                [
                    "type" => "text",
                    "name" => "country",
                    "label" => "Country",
                    "placeholder" => "Enter country",
                    "value" => $customer->country,
                ],
                [
                    "type" => "text",
                    "name" => "credit_limit",
                    "label" => "Credit Limit",
                    "placeholder" => "Enter credit limit",
                    "keyboard_type" => "number",
                    "info" => "Leave it blank for unlimited credit",
                    "value" => $customer->credit_limit,
                ],
            ];

            // Add custom fields dynamically with values
            foreach ($custom_fields as $field) {
                $fieldName = str_replace(' ', '_', strtolower($field->name));
                $fieldConfig = [
                    "name" => $fieldName,
                    "label" => $field->name,
                ];

                // Get the custom field value from customer
                $fieldValue = $customer->$fieldName ?? $field->default_value;

                if ($field->is_required) {
                    $fieldConfig['required'] = true;
                }

                switch ($field->type) {
                    case 'text':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'number':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['keyboard_type'] = 'number';
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'textarea':
                        $fieldConfig['type'] = 'text';
                        $fieldConfig['multiline'] = true;
                        $fieldConfig['placeholder'] = 'Enter ' . strtolower($field->name);
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'checkbox':
                        $fieldConfig['type'] = 'checkbox';
                        $fieldConfig['name'] = $fieldName . '[]';
                        if ($field->option) {
                            $fieldConfig['info'] = 'Options: ' . $field->option;
                        }
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'radio':
                    case 'select':
                        $fieldConfig['type'] = 'select';
                        if ($field->option) {
                            $options = explode(',', $field->option);
                            $fieldConfig['options'] = array_map(function ($opt) {
                                return ['value' => trim($opt), 'label' => trim($opt)];
                            }, $options);
                        }
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'multiselect':
                        $fieldConfig['type'] = 'select';
                        $fieldConfig['name'] = $fieldName . '[]';
                        $fieldConfig['enable_search'] = true;
                        if ($field->option) {
                            $options = explode(',', $field->option);
                            $fieldConfig['options'] = array_map(function ($opt) {
                                return ['value' => trim($opt), 'label' => trim($opt)];
                            }, $options);
                        }
                        $fieldConfig['value'] = $fieldValue;
                        break;
                    case 'date':
                        $fieldConfig['type'] = 'datepicker';
                        $fieldConfig['placeholder'] = 'Select date';
                        $fieldConfig['value'] = $fieldValue;
                        break;
                }

                $fields[] = $fieldConfig;
            }

            $fields[] = [
                "type" => "hidden",
                "name" => "is_active",
                "value" => 1,
            ];

            $formSchema = [
                "title" => "Edit Customer",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/customers/" . $id,
                "method" => "PUT",
                "navigate_url" => "/customers",
                "fields" => $fields,
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the form schema.'), 500);
        }
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer_data = $request->all();
        //return $customer_data;
        $customer_data['is_active'] = true;
        $prefixMessage = 'Customer';
        // if(isset($request->user)) {
        //     $customer_data['phone'] = $customer_data['phone_number'];
        //     $customer_data['role_id'] = 5;
        //     $customer_data['is_deleted'] = false;
        //     $customer_data['password'] = bcrypt($customer_data['password']);
        //     $user = User::create($customer_data);
        //     $customer_data['user_id'] = $user->id;
        //     $prefixMessage .= ', User';
        // }
        $customer_data['name'] = $customer_data['customer_name'];
        if (isset($request->both)) {
            Supplier::create($customer_data);
            $prefixMessage .= ' and Supplier';
        }

        $fullMessage = $prefixMessage . ' created successfully!';
        $mail_setting = MailSetting::latest()->first();
        $message = $this->mailAction($customer_data, $mail_setting, $request, $fullMessage);

        $lims_customer_data = Customer::create($customer_data);
        //inserting data for custom fields
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'customer')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($customer_data[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $customer_data[$field_name]);
                else
                    $custom_field_data[$field_name] = $customer_data[$field_name];
            }
        }
        if (count($custom_field_data))
            DB::table('customers')->where('id', $lims_customer_data->id)->update($custom_field_data);
        $this->cacheForget('customer_list');
        $customerInfo['id'] = $lims_customer_data->id;
        $customerInfo['name'] = $lims_customer_data->name;
        $customerInfo['phone_number'] = $lims_customer_data->phone_number;
        if (isset($customer_data['pos']))
            return $customerInfo;
        else
            return response()->json([
                'success' => true,
                'message' => $message,
                'navigate_url' => '/customers',
            ], 201);
    }

    protected function mailAction($data, $mailSetting, $request, $customMessage = null)
    {
        $message = $customMessage ?? 'Data inserted successfully';
        if (!$mailSetting) {
            $message = 'Data inserted successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
        } else if ($data['email'] && $mailSetting) {
            try {
                $this->setMailInfo($mailSetting);
                Mail::to($data['email'])->send(new CustomerCreate($data));
                if (isset($request->both))
                    Mail::to($data['email'])->send(new SupplierCreate($data));
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        }
        return $message;
    }

    public function update(StoreCustomerRequest $request, Customer $customer)
    {

        $input = $request->all();

        $input['both'] == true ? 1 : 0;

        $input['name'] = $input['customer_name'];
        $customer->update($input);
        //update custom field data
        $custom_field_data = [];
        $custom_fields = CustomField::where('belongs_to', 'customer')->select('name', 'type')->get();
        foreach ($custom_fields as $type => $custom_field) {
            $field_name = str_replace(' ', '_', strtolower($custom_field->name));
            if (isset($input[$field_name])) {
                if ($custom_field->type == 'checkbox' || $custom_field->type == 'multi_select')
                    $custom_field_data[$field_name] = implode(",", $input[$field_name]);
                else
                    $custom_field_data[$field_name] = $input[$field_name];
            }
        }
        if (count($custom_field_data))
            DB::table('customers')->where('id', $customer->id)->update($custom_field_data);
        $this->cacheForget('customer_list');

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => new CustomerResource($customer),
            'navigate_url' => '/customers',
        ], 200);
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create customers
            if (!$role->hasPermissionTo('customers-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import customers.',
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

                    $lims_customer_group_data = CustomerGroup::where('name', $data['customergroup'])->first();
                    if (!$lims_customer_group_data) {
                        continue; // Skip if customer group not found
                    }

                    $customer = Customer::firstOrNew(['name' => $data['name']]);
                    $customer->customer_group_id = $lims_customer_group_data->id;
                    $customer->name = $data['name'];
                    $customer->company_name = $data['companyname'] ?? null;
                    $customer->email = $data['email'] ?? null;
                    $customer->phone_number = $data['phonenumber'] ?? null;
                    $customer->address = $data['address'] ?? null;
                    $customer->city = $data['city'] ?? null;
                    $customer->state = $data['state'] ?? null;
                    $customer->postal_code = $data['postalcode'] ?? null;
                    $customer->country = $data['country'] ?? null;
                    $customer->is_active = true;
                    $customer->save();

                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('customer_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} customers.",
                    'navigate_url' => '/customers',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Customers",
                "submit_url" => "/customers/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, company_name, email, phone_number, tax_no, address, city, state, postal_code, country, points, deposit, expense, customer_group) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_customer.csv'),
                        "sample_file_name" => "sample_customer.csv",
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

    public function destroy(Customer $customer)
    {
        $customer->is_active = false;
        $customer->save();
        $this->cacheForget('customer_list');
        return response()->json([
            'success' => true,
            'message' => 'Customer has been deleted successfully.'
        ], 200);
    }
}
