<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MailRequest;
use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ExternalService;
use App\Models\PosSetting;
use App\Models\MailSetting;
use App\Models\GeneralSetting;
use App\Models\HrmSetting;
use App\Models\RewardPointSetting;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Models\ActiveThemeSetting;
use App\Models\ThemeSetting;

class SettingController extends Controller
{
    use \App\Traits\CacheForget;
    use \App\Traits\TenantInfo;
    use ProvidesThemeBackgrounds;
    private $_smsService;

    public function __construct(SmsService $smsService)
    {
        $this->_smsService = $smsService;
    }

    public function generalSetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('general_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $generalSetting = GeneralSetting::latest()->first();

            // Ensure we have a general setting record
            if (!$generalSetting) {
                $generalSetting = new GeneralSetting();
            }

            $accounts = Account::where('is_active', true)->get();
            $currencies = Currency::get();

            // Get timezone options
            $timezones = [];
            $timestamp = time();
            $originalTimezone = date_default_timezone_get(); // Store original timezone
            foreach (timezone_identifiers_list() as $zone) {
                date_default_timezone_set($zone);
                $timezones[] = [
                    'label' => 'UTC/GMT ' . date('P', $timestamp) . ' - ' . $zone,
                    'value' => $zone,
                ];
            }
            date_default_timezone_set($originalTimezone); // Reset to original timezone

            $currencyOptions = $currencies->map(function ($currency) {
                return [
                    'label' => $currency->name ?? 'Unknown Currency',
                    'value' => $currency->id ?? 0,
                ];
            })->toArray();

            $accountOptions = $accounts->map(function ($account) {
                return [
                    'label' => ($account->name ?? 'Unknown Account') . ' [' . ($account->account_no ?? 'N/A') . ']',
                    'value' => $account->id ?? 0,
                ];
            })->toArray();

            // Build System Information items
            $systemInfoItems = [
                [
                    "type" => "text",
                    "name" => "site_title",
                    "label" => "System Title *",
                    "placeholder" => "Enter system title",
                    "value" => $generalSetting->site_title ?? '',
                ],
                [
                    "type" => "file",
                    "name" => "site_logo",
                    "label" => "System Logo",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "file",
                    "name" => "dark_logo",
                    "label" => "System Logo For Dark Mode",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "checkbox",
                    "name" => "is_rtl",
                    "label" => "RTL Layout",
                    "value" => ($generalSetting->is_rtl || $generalSetting->is_rtl == 1 || $generalSetting->is_rtl == "1") ? true : false,
                ],
            ];

            // Add ZATCA field only if saleprosaas_landlord is configured
            if (config('database.connections.saleprosaas_landlord')) {
                $systemInfoItems[] = [
                    "type" => "checkbox",
                    "name" => "is_zatca",
                    "label" => "ZATCA QrCode",
                    "value" => $generalSetting->is_zatca ? true : false,
                ];
            }

            // Add remaining system info items
            $systemInfoItems = array_merge($systemInfoItems, [
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter company name",
                    "value" => $generalSetting->company_name ?? '',
                ],
                [
                    "type" => "text",
                    "name" => "vat_registration_number",
                    "label" => "VAT Registration Number",
                    "placeholder" => "Enter VAT registration number",
                    "value" => $generalSetting->vat_registration_number ?? '',
                ],
                [
                    "type" => "select",
                    "name" => "timezone",
                    "label" => "Time Zone",
                    "options" => $timezones,
                    "value" => env('APP_TIMEZONE', 'UTC'),
                ],
            ]);

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "General Settings",
                "submit_url" => "/general-setting",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "System Information",
                        "items" => $systemInfoItems,
                    ],
                    [
                        "type" => "group",
                        "label" => "Business Settings",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "without_stock",
                                "label" => "Sale and Quotation without stock *",
                                "options" => [
                                    ["label" => "Yes", "value" => "yes"],
                                    ["label" => "No", "value" => "no"],
                                ],
                                "value" => $generalSetting->without_stock ?? 'no',
                            ],
                            [
                                "type" => "select",
                                "name" => "is_packing_slip",
                                "label" => "Packing Slip to manage orders/sales *",
                                "options" => [
                                    ["label" => "Enable", "value" => "1"],
                                    ["label" => "Disable", "value" => "0"],
                                ],
                                "value" => $generalSetting->is_packing_slip ?? 0,
                            ],
                            [
                                "type" => "select",
                                "name" => "currency",
                                "label" => "Currency *",
                                "options" => $currencyOptions,
                                "value" => $generalSetting->currency ?? ($currencies->first()->id ?? ''),
                            ],
                            [
                                "type" => "select",
                                "name" => "currency_position",
                                "label" => "Currency Position *",
                                "options" => [
                                    ["label" => "Prefix", "value" => "prefix"],
                                    ["label" => "Suffix", "value" => "suffix"],
                                ],
                                "value" => $generalSetting->currency_position ?? 'prefix',
                            ],
                            [
                                "type" => "text",
                                "name" => "decimal",
                                "label" => "Decimal *",
                                "placeholder" => "Enter number of decimal places",
                                "keyboard_type" => "number",
                                "value" => $generalSetting->decimal ?? '2',
                            ],
                            [
                                "type" => "select",
                                "name" => "staff_access",
                                "label" => "Staff Access *",
                                "options" => [
                                    ["label" => "All Records", "value" => "all"],
                                    ["label" => "Own Records", "value" => "own"],
                                    ["label" => "Warehouse Wise", "value" => "warehouse"],
                                ],
                                "value" => $generalSetting->staff_access ?? 'all',
                            ],
                            [
                                "type" => "select",
                                "name" => "show_products_details_in_purchase_table",
                                "label" => "Show Products Details in Purchase List *",
                                "options" => [
                                    ["label" => "Show", "value" => "1"],
                                    ["label" => "Hide", "value" => "0"],
                                ],
                                "value" => $generalSetting->show_products_details_in_purchase_table ?? 0,
                            ],
                            [
                                "type" => "select",
                                "name" => "show_products_details_in_sales_table",
                                "label" => "Show Products Details in Sales List *",
                                "options" => [
                                    ["label" => "Show", "value" => "1"],
                                    ["label" => "Hide", "value" => "0"],
                                ],
                                "value" => $generalSetting->show_products_details_in_sales_table ?? 0,
                            ],
                            [
                                "type" => "select",
                                "name" => "date_format",
                                "label" => "Date Format *",
                                "options" => [
                                    ["label" => "d-m-Y", "value" => "d-m-Y"],
                                    ["label" => "d/m/Y", "value" => "d/m/Y"],
                                    ["label" => "d.m.Y", "value" => "d.m.Y"],
                                    ["label" => "m-d-Y", "value" => "m-d-Y"],
                                    ["label" => "m/d/Y", "value" => "m/d/Y"],
                                    ["label" => "m.d.Y", "value" => "m.d.Y"],
                                    ["label" => "Y-m-d", "value" => "Y-m-d"],
                                    ["label" => "Y/m/d", "value" => "Y/m/d"],
                                    ["label" => "Y.m.d", "value" => "Y.m.d"],
                                ],
                                "value" => $generalSetting->date_format ?? 'd-m-Y',
                            ],
                            [
                                "type" => "text",
                                "name" => "developed_by",
                                "label" => "Developed By",
                                "placeholder" => "Enter developer name",
                                "value" => $generalSetting->developed_by ?? '',
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Invoice Settings",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "invoice_format",
                                "label" => "Invoice Format *",
                                "options" => [
                                    ["label" => "Standard", "value" => "standard"],
                                    ["label" => "Indian GST", "value" => "gst"],
                                ],
                                "value" => $generalSetting->invoice_format ?? 'standard',
                            ],
                            [
                                "type" => "select",
                                "name" => "state",
                                "label" => "State",
                                "options" => [
                                    ["label" => "Home State", "value" => "1"],
                                    ["label" => "Buyer State", "value" => "2"],
                                ],
                                "value" => $generalSetting->state ?? '1',
                                "logics" => [
                                    [
                                        "field" => "invoice_format",
                                        "values" => ["gst"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Product Expiry Settings",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "expiry_type",
                                "label" => "Expiry Type",
                                "options" => [
                                    ["label" => "Days", "value" => "days"],
                                    ["label" => "Months", "value" => "months"],
                                    ["label" => "Years", "value" => "years"],
                                ],
                                "value" => $generalSetting->expiry_type ?? 'days',
                            ],
                            [
                                "type" => "text",
                                "name" => "expiry_value",
                                "label" => "Expiry Value",
                                "placeholder" => "Enter expiry period",
                                "keyboard_type" => "number",
                                "value" => $generalSetting->expiry_value ?? '30',
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading general settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function changeActiveThemeSetting($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return new ErrorResource([
                    'message' => 'Unauthenticated.',
                ]);
            }

            $themeId = (int) $id;
            if ($themeId <= 0) {
                return new ErrorResource([
                    'message' => 'Theme ID is required.',
                ]);
            }

            $theme = ThemeSetting::active('app')->where('id', $themeId)->first();
            if (!$theme) {
                return new ErrorResource([
                    'message' => 'Theme not found or inactive.',
                ]);
            }

            DB::beginTransaction();

            // Persist selection for this user/device.
            ActiveThemeSetting::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device' => 'app',
                ],
                [
                    'theme_id' => $themeId,
                ]
            );

            DB::commit();

            return new SuccessResource([
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'message' => 'Active theme setting updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while saving active theme setting.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function themeSetting(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return new ErrorResource([
                    'message' => 'Unauthenticated.',
                ]);
            }

            $themes = ThemeSetting::active('app')
                ->orderBy('id', 'asc')
                ->get();

            $currentTheme = null;
            $active = ActiveThemeSetting::where('user_id', $user->id)
                ->where('device', 'app')
                ->latest()
                ->first();

            if ($active && $active->theme_id) {
                $currentTheme = ThemeSetting::active('app')
                    ->where('id', $active->theme_id)
                    ->first();
            }

            if (!$currentTheme) {
                $currentTheme = ThemeSetting::active('app')
                    ->orderBy('id', 'asc')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'title' => 'Theme Settings',
                'view_type' => 'theme_settings',
                'themes' => $themes,
                'current_theme_setting' => $currentTheme,
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading theme settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function posSetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('pos_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $posSetting = PosSetting::latest()->first();
            $warehouses = Warehouse::where('is_active', true)->get();
            $billers = Biller::where('is_active', true)->get();
            $customerGroups = CustomerGroup::where('is_active', true)->get();
            $customers = Customer::where('is_active', true)->get();

            $warehouseOptions = $warehouses->map(function ($warehouse) {
                return [
                    'label' => $warehouse->name,
                    'value' => $warehouse->id,
                ];
            })->toArray();

            $billerOptions = $billers->map(function ($biller) {
                return [
                    'label' => $biller->name,
                    'value' => $biller->id,
                ];
            })->toArray();

            $customerGroupOptions = $customerGroups->map(function ($group) {
                return [
                    'label' => $group->name,
                    'value' => $group->id,
                ];
            })->toArray();

            $customerOptions = $customers->map(function ($customer) {
                return [
                    'label' => $customer->name,
                    'value' => $customer->id,
                ];
            })->toArray();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "POS Settings",
                "submit_url" => "/pos-setting",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Default POS Settings",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "customer_id",
                                "label" => "Default Customer *",
                                "options" => $customerOptions,
                                "value" => $posSetting->customer_id ?? '',
                            ],
                            [
                                "type" => "select",
                                "name" => "biller_id",
                                "label" => "Default Biller *",
                                "options" => $billerOptions,
                                "value" => $posSetting->biller_id ?? '',
                            ],
                            [
                                "type" => "select",
                                "name" => "warehouse_id",
                                "label" => "Default Warehouse *",
                                "options" => $warehouseOptions,
                                "value" => $posSetting->warehouse_id ?? '',
                            ],
                            [
                                "type" => "text",
                                "name" => "product_number",
                                "label" => "Displayed Number of Product Row *",
                                "placeholder" => "Enter number of product rows",
                                "keyboard_type" => "number",
                                "value" => $posSetting->product_number ?? '',
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "keybord_active",
                                "label" => "Touchscreen Keyboard",
                                "value" => $posSetting ? ($posSetting->keybord_active ? true : false) : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_table",
                                "label" => "Table Management",
                                "value" => $posSetting ? ($posSetting->is_table ? true : false) : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "send_sms",
                                "label" => "Send SMS After Sale",
                                "value" => $posSetting ? ($posSetting->send_sms ? true : false) : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "cash_register",
                                "label" => "Cash Register",
                                "value" => $posSetting ? ($posSetting->cash_register ? true : false) : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "show_print_invoice",
                                "label" => "Show Print Invoice",
                                "value" => $posSetting ? ($posSetting->show_print_invoice ? true : false) : false,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Payment Options",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "payment_option_cash",
                                "label" => "Cash",
                                "value" => $posSetting && str_contains($posSetting->payment_options ?? '', 'cash') ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "payment_option_card",
                                "label" => "Card",
                                "value" => $posSetting && str_contains($posSetting->payment_options ?? '', 'card') ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "payment_option_cheque",
                                "label" => "Cheque",
                                "value" => $posSetting && str_contains($posSetting->payment_options ?? '', 'cheque') ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "payment_option_paypal",
                                "label" => "Paypal",
                                "value" => $posSetting && str_contains($posSetting->payment_options ?? '', 'paypal') ? true : false,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "payment_option_stripe",
                                "label" => "Stripe",
                                "value" => $posSetting && str_contains($posSetting->payment_options ?? '', 'stripe') ? true : false,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Invoice Settings",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "invoice_size",
                                "label" => "Invoice Size *",
                                "options" => [
                                    ["label" => "Letter", "value" => "letter"],
                                    ["label" => "A4", "value" => "a4"],
                                    ["label" => "Thermal", "value" => "thermal"],
                                ],
                                "value" => $posSetting->invoice_option ?? 'a4',
                            ],
                            [
                                "type" => "select",
                                "name" => "thermal_invoice_size",
                                "label" => "Thermal Invoice Size",
                                "options" => [
                                    ["label" => "57mm", "value" => "57"],
                                    ["label" => "58mm", "value" => "58"],
                                    ["label" => "80mm", "value" => "80"],
                                ],
                                "value" => $posSetting->thermal_invoice_size ?? '80',
                                "logics" => [
                                    [
                                        "field" => "invoice_size",
                                        "values" => ["thermal"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading POS settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function mailSetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('mail_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $mailSetting = MailSetting::latest()->first();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Mail Settings",
                "submit_url" => "/mail-settings",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Mail Configuration",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "driver",
                                "label" => "Mail Driver *",
                                "options" => [
                                    ["label" => "SMTP", "value" => "smtp"],
                                    ["label" => "Mail", "value" => "mail"],
                                    ["label" => "Sendmail", "value" => "sendmail"],
                                ],
                                "value" => $mailSetting->driver ?? 'smtp',
                            ],
                            [
                                "type" => "text",
                                "name" => "host",
                                "label" => "Mail Host *",
                                "placeholder" => "Enter mail host",
                                "value" => $mailSetting->host ?? '',
                                "logics" => [
                                    [
                                        "field" => "driver",
                                        "values" => ["smtp"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "port",
                                "label" => "Mail Port *",
                                "placeholder" => "Enter mail port",
                                "keyboard_type" => "number",
                                "value" => $mailSetting->port ?? '',
                                "logics" => [
                                    [
                                        "field" => "driver",
                                        "values" => ["smtp"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "from_address",
                                "label" => "From Address *",
                                "placeholder" => "Enter from email address",
                                "keyboard_type" => "email",
                                "value" => $mailSetting->from_address ?? '',
                            ],
                            [
                                "type" => "text",
                                "name" => "from_name",
                                "label" => "From Name *",
                                "placeholder" => "Enter from name",
                                "value" => $mailSetting->from_name ?? '',
                            ],
                            [
                                "type" => "text",
                                "name" => "username",
                                "label" => "Username *",
                                "placeholder" => "Enter username",
                                "value" => $mailSetting->username ?? '',
                                "logics" => [
                                    [
                                        "field" => "driver",
                                        "values" => ["smtp"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "text",
                                "name" => "password",
                                "label" => "Password *",
                                "placeholder" => "Enter password",
                                "value" => $mailSetting->password ?? '',
                                "logics" => [
                                    [
                                        "field" => "driver",
                                        "values" => ["smtp"],
                                    ],
                                ],
                            ],
                            [
                                "type" => "select",
                                "name" => "encryption",
                                "label" => "Encryption",
                                "options" => [
                                    ["label" => "TLS", "value" => "tls"],
                                    ["label" => "SSL", "value" => "ssl"],
                                    ["label" => "None", "value" => ""],
                                ],
                                "value" => $mailSetting->encryption ?? '',
                                "logics" => [
                                    [
                                        "field" => "driver",
                                        "values" => ["smtp"],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading mail settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rewardPointSetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('reward_point_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $rewardPointSetting = RewardPointSetting::latest()->first();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Reward Point Settings",
                "submit_url" => "/reward-point-settings",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Reward Point Configuration",
                        "items" => [
                            [
                                "type" => "checkbox",
                                "name" => "is_active",
                                "label" => "Active Reward Point",
                                "value" => $rewardPointSetting ? ($rewardPointSetting->is_active ? true : false) : false,
                            ],
                            [
                                "type" => "text",
                                "name" => "per_point_amount",
                                "label" => "Per Point Amount *",
                                "placeholder" => "Enter amount per point",
                                "keyboard_type" => "number",
                                "value" => $rewardPointSetting->per_point_amount ?? '',
                            ],
                            [
                                "type" => "text",
                                "name" => "minimum_amount",
                                "label" => "Minimum Amount to Redeem *",
                                "placeholder" => "Enter minimum amount",
                                "keyboard_type" => "number",
                                "value" => $rewardPointSetting->minimum_amount ?? '',
                            ],
                            [
                                "type" => "text",
                                "name" => "duration",
                                "label" => "Point Duration (Days) *",
                                "placeholder" => "Enter point validity duration",
                                "keyboard_type" => "number",
                                "value" => $rewardPointSetting->duration ?? '',
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading reward point settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function rewardPointSettingStore(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('reward_point_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            // Validate reward point settings data
            $request->validate([
                'per_point_amount' => 'required|numeric|min:0',
                'minimum_amount' => 'required|numeric|min:0',
                'duration' => 'required|integer|min:1',
            ]);

            $data = $request->all();
            $data['is_active'] = $request->has('is_active') ? true : false;

            $rewardPointSetting = RewardPointSetting::latest()->first();
            if ($rewardPointSetting) {
                $rewardPointSetting->update($data);
            } else {
                RewardPointSetting::create($data);
            }

            return new SuccessResource([
                'message' => 'Reward point settings updated successfully.',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating reward point settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function smsSetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $settings = ExternalService::all();
            $twilio = [];
            $clickatell = [];

            foreach ($settings as $setting) {
                if ($setting->name == 'twilio') {
                    $twilio['sms_id'] = $setting->id ?? '';
                    $twilio['active'] = $setting->active ?? '';
                    $twilio['details'] = json_decode($setting->details) ?? '';
                }

                if ($setting->name == 'clickatell') {
                    $clickatell['sms_id'] = $setting->id ?? '';
                    $clickatell['active'] = $setting->active ?? '';
                    $clickatell['details'] = json_decode($setting->details) ?? '';
                }
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "SMS Settings",
                "submit_url" => "/sms-settings",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "SMS Gateway Selection",
                        "items" => [
                            [
                                "type" => "select",
                                "name" => "gateway",
                                "label" => "SMS Gateway *",
                                "options" => [
                                    ["label" => "Twilio", "value" => "twilio"],
                                    ["label" => "Clickatell", "value" => "clickatell"],
                                ],
                                "value" => ($twilio['active'] ?? false) ? 'twilio' : (($clickatell['active'] ?? false) ? 'clickatell' : ''),
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "active",
                                "label" => "Active SMS Gateway",
                                "value" => (($twilio['active'] ?? false) || ($clickatell['active'] ?? false)) ? true : false,
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Twilio Configuration",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "account_sid",
                                "label" => "Account SID *",
                                "placeholder" => "Enter Twilio Account SID",
                                "value" => isset($twilio['details']) && is_object($twilio['details']) ? ($twilio['details']->account_sid ?? '') : '',
                            ],
                            [
                                "type" => "text",
                                "name" => "auth_token",
                                "label" => "Auth Token *",
                                "placeholder" => "Enter Twilio Auth Token",
                                "value" => isset($twilio['details']) && is_object($twilio['details']) ? ($twilio['details']->auth_token ?? '') : '',
                            ],
                            [
                                "type" => "text",
                                "name" => "twilio_number",
                                "label" => "Twilio Number *",
                                "placeholder" => "Enter Twilio Phone Number",
                                "keyboard_type" => "phone",
                                "value" => isset($twilio['details']) && is_object($twilio['details']) ? ($twilio['details']->twilio_number ?? '') : '',
                            ],
                        ],
                        "logics" => [
                            [
                                "field" => "gateway",
                                "values" => ["twilio"],
                            ],
                        ],
                    ],
                    [
                        "type" => "group",
                        "label" => "Clickatell Configuration",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "api_key",
                                "label" => "API Key *",
                                "placeholder" => "Enter Clickatell API Key",
                                "value" => isset($clickatell['details']) && is_object($clickatell['details']) ? ($clickatell['details']->api_key ?? '') : '',
                            ],
                        ],
                        "logics" => [
                            [
                                "field" => "gateway",
                                "values" => ["clickatell"],
                            ],
                        ],
                    ],
                    [
                        "type" => "hidden",
                        "name" => "twilio_sms_id",
                        "value" => $twilio['sms_id'] ?? '',
                    ],
                    [
                        "type" => "hidden",
                        "name" => "clickatell_sms_id",
                        "value" => $clickatell['sms_id'] ?? '',
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading SMS settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function smsSettingStore(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('sms_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $data = $request->all();
            $data['active'] = $request->has('active') ? 1 : 0;

            if ($data['gateway'] == 'twilio') {
                $request->validate([
                    'account_sid' => 'required|string',
                    'auth_token' => 'required|string',
                    'twilio_number' => 'required|string',
                ]);

                $twilioDetails = [
                    'account_sid' => $data['account_sid'],
                    'auth_token' => $data['auth_token'],
                    'twilio_number' => $data['twilio_number'],
                ];

                $twilioSetting = ExternalService::where('name', 'twilio')->first();
                if ($twilioSetting) {
                    $twilioSetting->update([
                        'details' => json_encode($twilioDetails),
                        'active' => $data['active'],
                    ]);
                } else {
                    ExternalService::create([
                        'name' => 'twilio',
                        'type' => 'sms',
                        'details' => json_encode($twilioDetails),
                        'active' => $data['active'],
                    ]);
                }

                // Deactivate other SMS services if this one is active
                if ($data['active']) {
                    ExternalService::where('name', '!=', 'twilio')->where('type', 'sms')->update(['active' => 0]);
                }
            } elseif ($data['gateway'] == 'clickatell') {
                $request->validate([
                    'api_key' => 'required|string',
                ]);

                $clickatellDetails = [
                    'api_key' => $data['api_key'],
                ];

                $clickatellSetting = ExternalService::where('name', 'clickatell')->first();
                if ($clickatellSetting) {
                    $clickatellSetting->update([
                        'details' => json_encode($clickatellDetails),
                        'active' => $data['active'],
                    ]);
                } else {
                    ExternalService::create([
                        'name' => 'clickatell',
                        'type' => 'sms',
                        'details' => json_encode($clickatellDetails),
                        'active' => $data['active'],
                    ]);
                }

                // Deactivate other SMS services if this one is active
                if ($data['active']) {
                    ExternalService::where('name', '!=', 'clickatell')->where('type', 'sms')->update(['active' => 0]);
                }
            }

            return new SuccessResource([
                'message' => 'SMS settings updated successfully.',
            ]);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating SMS settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generalSettingStore(Request $request)
    {
        // Only validate site_logo if it's actually a file upload
        if ($request->hasFile('site_logo')) {
            $request->validate([
                'site_logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            if ($request->hasFile('dark_logo')) {
                $request->validate([
                    'dark_logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
                ]);
            }
        }

        $data = $request->except('site_logo', 'dark_logo', 'token');
        // return $data;
        //writting timezone info in .env file
        $path = app()->environmentFilePath();
        $searchArray = array('APP_TIMEZONE=' . env('APP_TIMEZONE'));
        $replaceArray = array('APP_TIMEZONE=' . $data['timezone']);

        file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));

        // Convert checkbox values properly (handle "0", "1", 0, 1, true, false)
        $data['is_rtl'] = isset($data['is_rtl']) && ($data['is_rtl'] === true || $data['is_rtl'] === 1 || $data['is_rtl'] === '1');
        $data['is_zatca'] = isset($data['is_zatca']) && ($data['is_zatca'] === true || $data['is_zatca'] === 1 || $data['is_zatca'] === '1');

        $general_setting = GeneralSetting::latest()->first();
        $general_setting->id = 1;
        $general_setting->site_title = $data['site_title'];
        $general_setting->is_rtl = $data['is_rtl'];
        $general_setting->is_zatca = $data['is_zatca'];
        $general_setting->company_name = $data['company_name'];
        $general_setting->vat_registration_number = $data['vat_registration_number'];
        $general_setting->currency = $data['currency'];
        $general_setting->currency_position = $data['currency_position'];
        $general_setting->decimal = $data['decimal'];
        $general_setting->staff_access = $data['staff_access'];
        $general_setting->without_stock = $data['without_stock'];
        $general_setting->is_packing_slip = $data['is_packing_slip'];
        $general_setting->date_format = $data['date_format'];
        $general_setting->developed_by = $data['developed_by'];
        $general_setting->invoice_format = $data['invoice_format'];
        $general_setting->state = $data['state'];
        $general_setting->expiry_type = $data['expiry_type'];
        $general_setting->expiry_value = $data['expiry_value'];
        $logo = $request->site_logo;
        $darkLogo = $request->dark_logo;
        if ($logo) {
            $this->fileDelete('logo/', $general_setting->site_logo);

            $ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
            $logoName = date("Ymdhis") . '.' . $ext;

            if (config('database.connections.saleprosaas_landlord')) {
                $logoName = $this->getTenantId() . '_' . $logoName;
            }

            $logo->move(public_path('logo'), $logoName);
            $general_setting->site_logo = $logoName;
        }
        if ($darkLogo) {
            $this->fileDelete('logo/', $general_setting->dark_logo);

            $ext = pathinfo($darkLogo->getClientOriginalName(), PATHINFO_EXTENSION);
            $darkLogoName = date("Ymdhis") . '_dark.' . $ext;

            if (config('database.connections.saleprosaas_landlord')) {
                $darkLogoName = $this->getTenantId() . '_' . $darkLogoName;
            }

            $darkLogo->move(public_path('logo'), $darkLogoName);
            $general_setting->dark_logo = $darkLogoName;
        }

        $general_setting->save();
        cache()->forget('general_setting');

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully.',
        ], 200);
    }

    public function posSettingStore(Request $request)
    {
        try {
            $data = $request->all();

            // Handle payment options from checkboxes
            $paymentOptions = [];
            if (isset($data['payment_option_cash']) && $data['payment_option_cash']) {
                $paymentOptions[] = 'cash';
            }
            if (isset($data['payment_option_card']) && $data['payment_option_card']) {
                $paymentOptions[] = 'card';
            }
            if (isset($data['payment_option_cheque']) && $data['payment_option_cheque']) {
                $paymentOptions[] = 'cheque';
            }
            if (isset($data['payment_option_paypal']) && $data['payment_option_paypal']) {
                $paymentOptions[] = 'paypal';
            }
            if (isset($data['payment_option_stripe']) && $data['payment_option_stripe']) {
                $paymentOptions[] = 'stripe';
            }

            $options = !empty($paymentOptions) ? implode(',', $paymentOptions) : 'none';

            $pos_setting = PosSetting::firstOrNew(['id' => 1]);
            $pos_setting->id = 1;
            $pos_setting->customer_id = $data['customer_id'];
            $pos_setting->warehouse_id = $data['warehouse_id'];
            $pos_setting->biller_id = $data['biller_id'];
            $pos_setting->product_number = $data['product_number'];
            $pos_setting->payment_options = $options;
            $pos_setting->invoice_option = $data['invoice_size'];
            $pos_setting->thermal_invoice_size = $data['thermal_invoice_size'] ?? '80';

            // Handle checkboxes
            $pos_setting->keybord_active = isset($data['keybord_active']) ? (bool)$data['keybord_active'] : false;
            $pos_setting->is_table = isset($data['is_table']) ? (bool)$data['is_table'] : false;
            $pos_setting->send_sms = isset($data['send_sms']) ? (bool)$data['send_sms'] : false;
            $pos_setting->cash_register = isset($data['cash_register']) ? (bool)$data['cash_register'] : false;
            $pos_setting->show_print_invoice = isset($data['show_print_invoice']) ? (bool)$data['show_print_invoice'] : false;

            $pos_setting->save();
            cache()->forget('pos_setting');

            return response()->json([
                'success' => true,
                'message' => 'POS settings updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            return [
                'message' => 'An error occurred while updating POS settings.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function mailSettingStore(MailRequest $request)
    {
        $data = $request->all();
        $mail_setting = MailSetting::latest()->first();
        if (!$mail_setting)
            $mail_setting = new MailSetting;
        $mail_setting->driver = $data['driver'];
        $mail_setting->host = $data['host'];
        $mail_setting->port = $data['port'];
        $mail_setting->from_address = $data['from_address'];
        $mail_setting->from_name = $data['from_name'];
        $mail_setting->username = $data['username'];
        $mail_setting->password = trim($data['password']);
        $mail_setting->encryption = $data['encryption'];
        $mail_setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully.',
            'data' => $mail_setting,
        ], 200);
    }

    public function paymentGatewaySetting()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('payment_gateway_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $paymentGateways = DB::table('external_services')->where('type', 'payment')->get();
            $availableModules = ['pos', 'ecommerce'];

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Payment Gateway Settings",
                "submit_url" => "/payment-gateways",
                "method" => "POST",
                "fields" => []
            ];

            foreach ($paymentGateways as $index => $gateway) {
                $moduleStatus = json_decode($gateway->module_status, true) ?? [];
                $selectedModules = [];
                foreach ($availableModules as $module) {
                    if (!empty($moduleStatus[$module]) && $moduleStatus[$module]) {
                        $selectedModules[] = $module;
                    }
                }

                // Parse gateway details
                $lines = explode(';', $gateway->details);
                $keys = explode(',', $lines[0]);
                $vals = isset($lines[1]) ? explode(',', $lines[1]) : [];
                $details = array_combine($keys, array_pad($vals, count($keys), ''));

                // Create group for this gateway
                $gatewayFields = [
                    [
                        "type" => "hidden",
                        "name" => "pg_name_{$index}",
                        "value" => $gateway->name,
                    ],
                    [
                        "type" => "select",
                        "name" => "module_status_{$index}",
                        "label" => "Active Modules",
                        "multiple" => true,
                        "options" => [
                            ["label" => "POS", "value" => "pos"],
                            ["label" => "Ecommerce", "value" => "ecommerce"],
                        ],
                        "value" => $selectedModules,
                    ]
                ];

                // Add fields for each detail key
                foreach ($details as $key => $value) {
                    if ($key === 'Mode') {
                        $gatewayFields[] = [
                            "type" => "select",
                            "name" => $gateway->name . '_' . str_replace(' ', '_', $key),
                            "label" => $key,
                            "options" => [
                                ["label" => "Sandbox", "value" => "sandbox"],
                                ["label" => "Live", "value" => "live"],
                            ],
                            "value" => $value,
                        ];
                    } else {
                        $gatewayFields[] = [
                            "type" => "text",
                            "name" => $gateway->name . '_' . str_replace(' ', '_', $key),
                            "label" => $key,
                            "placeholder" => "Enter " . $key,
                            "value" => $value,
                        ];
                    }
                }

                $formSchema["fields"][] = [
                    "type" => "group",
                    "label" => $gateway->name . " Details",
                    "items" => $gatewayFields
                ];
            }

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving payment gateway settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function gatewayUpdate(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (!$role->hasPermissionTo('payment_gateway_setting')) {
            return redirect('/dashboard')
                ->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }

        if (!env('USER_VERIFIED')) {
            Session::flash('message', 'This feature is disabled for demo!');
            Session::flash('type', 'error');
            return redirect()->back();
        }

        // Fetch all payment gateways from the database
        $gateways = DB::table('external_services')->where('type', 'payment')->get();

        // Define all possible modules (e.g., "salepro", "ecommerce")
        $allModules = ['pos', 'ecommerce'];

        // Get inputs - now using underscore notation instead of arrays
        $allRequestData = $request->all();

        // Extract payment gateway names
        $pgs = [];
        $moduleStatuses = [];

        foreach ($allRequestData as $key => $value) {
            if (strpos($key, 'pg_name_') === 0) {
                $index = str_replace('pg_name_', '', $key);
                $pgs[$index] = $value;
            } elseif (strpos($key, 'module_status_') === 0) {
                $index = str_replace('module_status_', '', $key);
                $moduleStatuses[$index] = is_array($value) ? $value : [$value];
            }
        }

        foreach ($pgs as $index => $pg) {
            $gateway = $gateways->where('name', $pg)->first();

            if (!$gateway) {
                continue; // Skip if gateway not found
            }

            // Update the `details` field
            $lines = explode(';', $gateway->details);
            $keys = explode(',', $lines[0]);
            $vals = [];
            foreach ($keys as $key) {
                $para = $pg . '_' . str_replace(' ', '_', $key);
                $val = $request->$para ?? ''; // Default to empty string if null
                array_push($vals, $val);
            }
            $lines[1] = implode(',', $vals);
            $details = $lines[0] . ';' . $lines[1];

            // Update `module_status` field
            $selectedModules = $moduleStatuses[$index] ?? []; // Selected modules for this gateway
            $selectedModules = is_array($selectedModules) ? $selectedModules : [$selectedModules];

            // Create a status array with all modules
            $moduleStatusArray = [];
            foreach ($allModules as $module) {
                $moduleStatusArray[$module] = in_array($module, $selectedModules);
            }

            $moduleStatusJson = json_encode($moduleStatusArray);

            // Update the gateway in the database
            DB::table('external_services')
                ->where('name', $pg)
                ->update([
                    'details' => $details,
                    'module_status' => $moduleStatusJson,
                    'active' => 1, // Default to active
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully.',
        ], 200);
    }

    public function hrmSetting()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return new ErrorResource([
                    'message' => 'User not authenticated.',
                ]);
            }

            $role = Role::find($user->role_id);
            if (!$role) {
                return new ErrorResource([
                    'message' => 'User role not found.',
                ]);
            }

            // Check if the user has permission to access settings
            if (!$role->hasPermissionTo('hrm_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $hrmSetting = HrmSetting::latest()->first();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "HRM Settings",
                "submit_url" => "/setting/hrm-setting",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "timepicker",
                        "name" => "checkin",
                        "label" => "Default CheckIn",
                        "placeholder" => "Select check-in time",
                        "value" => $hrmSetting ? $hrmSetting->checkin : '',
                        "format_specifier" => "HH:mm",
                    ],
                    [
                        "type" => "timepicker",
                        "name" => "checkout",
                        "label" => "Default CheckOut",
                        "placeholder" => "Select check-out time",
                        "value" => $hrmSetting ? $hrmSetting->checkout : '',
                        "format_specifier" => "HH:mm",
                    ],
                ]
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving HRM settings.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function hrmSettingStore(Request $request)
    {
        $data = $request->all();
        $lims_hrm_setting_data = HrmSetting::firstOrNew(['id' => 1]);
        $lims_hrm_setting_data->checkin = $data['checkin'];
        $lims_hrm_setting_data->checkout = $data['checkout'];
        $lims_hrm_setting_data->save();

        return response()->json([
            'success' => true,
            'message' => 'Data created successfully.',
        ], 201);
    }

    public function backup()
    {
        if (!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');

        // Database configuration
        $host = env('DB_HOST');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        if (!config('database.connections.saleprosaas_landlord'))
            $database_name = env('DB_DATABASE');
        else
            $database_name = env('DB_PREFIX') . $this->getTenantId();

        // Get connection object and set the charset
        $conn = mysqli_connect($host, $username, $password, $database_name);
        $conn->set_charset("utf8");


        // Get All Table Names From the Database
        $tables = array();
        $sql = "SHOW TABLES";
        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }

        $sqlScript = "";
        foreach ($tables as $table) {

            // Prepare SQLscript for creating table structure
            $query = "SHOW CREATE TABLE $table";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_row($result);

            $sqlScript .= "\n\n" . $row[1] . ";\n\n";


            $query = "SELECT * FROM $table";
            $result = mysqli_query($conn, $query);

            $columnCount = mysqli_num_fields($result);

            // Prepare SQLscript for dumping data for each table
            for ($i = 0; $i < $columnCount; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $sqlScript .= "INSERT INTO $table VALUES(";
                    for ($j = 0; $j < $columnCount; $j++) {
                        $row[$j] = $row[$j];

                        if (isset($row[$j])) {
                            $sqlScript .= '"' . $row[$j] . '"';
                        } else {
                            $sqlScript .= '""';
                        }
                        if ($j < ($columnCount - 1)) {
                            $sqlScript .= ',';
                        }
                    }
                    $sqlScript .= ");\n";
                }
            }

            $sqlScript .= "\n";
        }

        if (!empty($sqlScript)) {
            // Save the SQL script to a backup file
            $backup_file_name = public_path() . '/' . $database_name . '_backup_' . time() . '.sql';
            //return $backup_file_name;
            $fileHandler = fopen($backup_file_name, 'w+');
            $number_of_lines = fwrite($fileHandler, $sqlScript);
            fclose($fileHandler);

            $zip = new ZipArchive();
            $zipFileName = $database_name . '_backup_' . time() . '.zip';
            $zip->open(public_path() . '/' . $zipFileName, ZipArchive::CREATE);
            $zip->addFile($backup_file_name, $database_name . '_backup_' . time() . '.sql');
            $zip->close();

            // Download the SQL backup file to the browser
            /*header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backup_file_name));
            ob_clean();
            flush();
            readfile($backup_file_name);
            exec('rm ' . $backup_file_name); */
        }
        return response()->json([
            'success' => true,
            'message' => 'Data backup successfull.',
        ], 200);
    }


    public function checkLicense(Request $request)
    {
        $request->validate([
            'install_url' => 'required|url',
            'license_key' => 'required|string',
        ]);


        // Get the domain part from install_url
        $installDomain = parse_url($request->install_url, PHP_URL_HOST);

        // Get the allowed main domain from APP_URL
        $allowedDomain = parse_url(env('APP_URL'), PHP_URL_HOST);

        // Extract main domain part (like xyz.com)
        $allowedMainDomain = implode('.', array_slice(explode('.', $allowedDomain), -2));

        // Extract main domain from install URL
        $installMainDomain = implode('.', array_slice(explode('.', $installDomain), -2));
        // Check if install domain matches allowed domain
        if ($installMainDomain !== $allowedMainDomain) {
            return response()->json(['message' => 'Invalid domain.'], 403);
        }

        // Check license key logic (replace this with actual check)
        // if ($request->license_key !== 'EXPECTED_KEY') {
        //     return response()->json(['message' => 'Invalid license key.'], 401);
        // }

        // Generate a token (if your GeneralSetting table has one token for app)
        $token = Str::random(60);

        $generalSetting = GeneralSetting::first();
        $generalSetting->app_key = $token;
        $generalSetting->save();

        return response()->json([
            'token' => $token
        ]);
    }
}
