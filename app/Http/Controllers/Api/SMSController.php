<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Customer;
use App\Models\ExternalService;
use App\Models\SmsTemplate;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SMSController extends Controller
{
    use ProvidesThemeBackgrounds;

    protected $_smsService;

    public function __construct(SMSService $smsService)
    {
        $this->_smsService = $smsService;
    }

    /**
     * Display a listing of SMS templates.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return new ErrorResource('User not authenticated.');
            }

            $role = Role::find($user->role_id);
            if (!$role) {
                return new ErrorResource('User role not found.');
            }

            // Check if the user has permission to access SMS templates
            if (!$role->hasPermissionTo('sms_setting') && !$role->hasPermissionTo('create_sms')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            // Get all SMS templates
            $templates = SmsTemplate::orderBy('id', 'desc')->get();

            // Format templates for datatable
            $templatesTable = $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'content' => $template->content,
                    'is_default' => $template->is_default
                        ? "<span class='badge badge-success'>Default</span>"
                        : "",
                    'is_default_value' => $template->is_default,
                    'is_default_ecommerce' => $template->is_default_ecommerce
                        ? "<span class='badge badge-success'>Default</span>"
                        : "",
                    'is_default_ecommerce_value' => $template->is_default_ecommerce,
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "SMS Templates",
                'row_height' => 5,
                'add_text' => 'Add SMS Template',
                'add_url' => '/sms-templates/create',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Content', 'field' => 'content', 'type' => 'text'],
                    ['label' => 'Default Sale', 'field' => 'is_default', 'type' => 'html'],
                    ['label' => 'Default E-Commerce', 'field' => 'is_default_ecommerce', 'type' => 'html'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'button',
                                'label' => 'Make Default',
                                'action' => [
                                    'api_url' => '/sms-templates/{id}/make-default',
                                    'type' => 'custom_api'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'is_default_value',
                                        'values' => [0, false, null],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'label' => 'Make Default E-Commerce',
                                'action' => [
                                    'api_url' => '/sms-templates/{id}/make-default-ecommerce',
                                    'type' => 'custom_api'
                                ],
                                'logics' => [
                                    [
                                        'field' => 'is_default_ecommerce_value',
                                        'values' => [0, false, null],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/sms-templates/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/sms-templates/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $templatesTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving SMS templates.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new SMS template.
     */
    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to create SMS templates.');
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add SMS Template",
                "submit_url" => "/sms-templates",
                "method" => "POST",
                "navigate_url" => "/sms-templates",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Template Name",
                        "placeholder" => "Enter template name",
                        "info" => "Enter a descriptive name for this SMS template",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "content",
                        "label" => "Content",
                        "placeholder" => "You can set following dynamic tags for a template:\n[reference], [customer], [sale_status], [payment_status] [sale_total]\nExample:\nHi [customer],\nThanks for the order. Order reference: [reference]. Order status: [sale_status] Sale Total: [sale_total]. Payment status: [payment_status].",
                        "multiline" => true,
                        "info" => "Use dynamic tags: [reference], [customer], [sale_status], [payment_status], [sale_total]",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_default",
                        "label" => "Default SMS Sale",
                        "value" => false,
                        "info" => "Set this template as default for sale SMS notifications",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_default_ecommerce",
                        "label" => "Default SMS E-Commerce",
                        "value" => false,
                        "info" => "Set this template as default for e-commerce SMS notifications",
                        "show_info_icon" => true,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the SMS template form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created SMS template in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to create SMS templates.');
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'content' => 'required|string|max:1000',
                'is_default' => 'nullable|boolean',
                'is_default_ecommerce' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return new ErrorResource($validator->errors()->first());
            }

            $data = $request->all();

            // Handle default settings
            if (isset($data['is_default']) && $data['is_default']) {
                SmsTemplate::where('is_default', true)->update(['is_default' => false]);
            }

            if (isset($data['is_default_ecommerce']) && $data['is_default_ecommerce']) {
                SmsTemplate::where('is_default_ecommerce', true)->update(['is_default_ecommerce' => false]);
            }

            SmsTemplate::create($data);

            return response()->json([
                'success' => true,
                'message' => 'SMS template created successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified SMS template.
     */
    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to edit SMS templates.');
            }

            $template = SmsTemplate::find($id);
            if (!$template) {
                return new ErrorResource('SMS template not found.');
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Update SMS Template",
                "submit_url" => "/sms-templates/" . $id,
                "method" => "PUT",
                "navigate_url" => "/sms-templates",
                "fields" => [
                    [
                        "type" => "hidden",
                        "name" => "smstemplate_id",
                        "value" => $template->id,
                    ],
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Template Name",
                        "placeholder" => "Enter template name",
                        "value" => $template->name,
                        "info" => "Enter a descriptive name for this SMS template",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "content",
                        "label" => "Content",
                        "placeholder" => "You can set following dynamic tags for a template:\n[reference], [customer], [sale_status], [payment_status] [sale_total]",
                        "value" => $template->content,
                        "multiline" => true,
                        "info" => "Use dynamic tags: [reference], [customer], [sale_status], [payment_status], [sale_total]",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_default",
                        "label" => "Default SMS Sale",
                        "value" => $template->is_default ? true : false,
                        "info" => "Set this template as default for sale SMS notifications",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "checkbox",
                        "name" => "is_default_ecommerce",
                        "label" => "Default SMS E-Commerce",
                        "value" => $template->is_default_ecommerce ? true : false,
                        "info" => "Set this template as default for e-commerce SMS notifications",
                        "show_info_icon" => true,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified SMS template in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to edit SMS templates.');
            }

            $template = SmsTemplate::find($id);
            if (!$template) {
                return new ErrorResource('SMS template not found.');
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'content' => 'required|string|max:1000',
                'is_default' => 'nullable|boolean',
                'is_default_ecommerce' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return new ErrorResource($validator->errors()->first());
            }

            $data = $request->all();

            // Handle default settings
            if (isset($data['is_default']) && $data['is_default']) {
                SmsTemplate::where('id', '!=', $template->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            } else {
                $data['is_default'] = false;
            }

            if (isset($data['is_default_ecommerce']) && $data['is_default_ecommerce']) {
                SmsTemplate::where('id', '!=', $template->id)
                    ->where('is_default_ecommerce', true)
                    ->update(['is_default_ecommerce' => false]);
            } else {
                $data['is_default_ecommerce'] = false;
            }

            $template->update($data);

            return response()->json([
                'success' => true,
                'message' => 'SMS template updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified SMS template from storage.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to delete SMS templates.');
            }

            $template = SmsTemplate::find($id);
            if (!$template) {
                return new ErrorResource('SMS template not found.');
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'SMS template deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Make the specified SMS template default for sales.
     */
    public function makeDefault($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to edit SMS templates.');
            }

            $template = SmsTemplate::find($id);
            if (!$template) {
                return new ErrorResource('SMS template not found.');
            }

            // Update all templates to remove default status
            SmsTemplate::where('is_default', true)->update(['is_default' => false]);

            // Set this template as default
            $template->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'SMS template set as default for sales successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while setting the default SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Make the specified SMS template default for e-commerce.
     */
    public function makeDefaultEcommerce($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit SMS templates
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource('Sorry! You are not allowed to edit SMS templates.');
            }

            $template = SmsTemplate::find($id);
            if (!$template) {
                return new ErrorResource('SMS template not found.');
            }

            // Update all templates to remove default e-commerce status
            SmsTemplate::where('is_default_ecommerce', true)->update(['is_default_ecommerce' => false]);

            // Set this template as default for e-commerce
            $template->update(['is_default_ecommerce' => true]);

            return response()->json([
                'success' => true,
                'message' => 'SMS template set as default for e-commerce successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while setting the default e-commerce SMS template.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating an SMS.
     */
    public function createSms()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to send SMS
            if (!$role->hasPermissionTo('create_sms')) {
                return new ErrorResource('Sorry! You are not allowed to send SMS.');
            }

            // Get customers and SMS templates
            $customers = Customer::where('is_active', true)->get();
            $templates = SmsTemplate::all();

            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name . ' [' . $customer->phone_number . ']',
                    'value' => $customer->phone_number,
                ];
            })->values()->toArray();

            $templateOptions = $templates->map(function ($template) {
                return [
                    'label' => $template->name,
                    'value' => $template->id,
                    'data' => ['content' => $template->content],
                ];
            })->values()->toArray();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Create SMS",
                "submit_url" => "/sms/send",
                "method" => "POST",
                "navigate_url" => "/sms-templates",
                "fields" => [
                    [
                        "type" => "select",
                        "name" => "template_id",
                        "label" => "SMS Template",
                        "placeholder" => "Select template",
                        "options" => $templateOptions,
                        "info" => "Choose a predefined SMS template",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "mobile",
                        "label" => "Mobile Numbers",
                        "placeholder" => "example: +8801*********,+8801*********",
                        "info" => "Enter mobile numbers separated by commas",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "message",
                        "label" => "Message",
                        "placeholder" => "Enter your SMS message",
                        "multiline" => true,
                        "info" => "Enter the SMS message content",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "helpertext",
                        "text" => "You can select customers to automatically add their mobile numbers to the field above.",
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the SMS form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SMS to specified numbers.
     */
    public function sendSms(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to send SMS
            if (!$role->hasPermissionTo('create_sms')) {
                return new ErrorResource('Sorry! You are not allowed to send SMS.');
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
                'message' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return new ErrorResource($validator->errors()->first());
            }

            $data = $request->all();

            // Get active SMS provider
            $smsProvider = ExternalService::where('active', true)->where('type', 'sms')->first();

            if (!$smsProvider) {
                return new ErrorResource('No active SMS provider found. Please configure SMS settings first.');
            }

            $smsData['sms_provider_name'] = $smsProvider->name;
            $smsData['details'] = $smsProvider->details;
            $smsData['message'] = $data['message'];
            $smsData['recipent'] = $data['mobile'];
            $numbers = explode(",", $data['mobile']);
            $smsData['numbers'] = $numbers;

            $this->_smsService->initialize($smsData);

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
