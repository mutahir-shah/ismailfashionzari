<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Models\User;
use App\Models\Customer;
use App\Models\Roles;
use App\Models\CustomerGroup;
use App\Models\Biller;
use App\Models\Warehouse;
use App\Models\ActiveThemeSetting;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;


class RegisterController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function getRegistrationFormData(Request $request)
    {
        // Include general settings for the client to use in registration flow
        $general_settings = DB::table('general_settings')->first();

        // Collect themes only when requested (used by dynamic Theme Settings screen)
        $includeThemes = $request->get('view') === 'theme-settings';

        $themes = collect();
        if ($includeThemes) {
            // Keep this liberal: some installs may not have active_for/app configured.
            // The app still needs to show available themes.
            $themes = ThemeSetting::active('app')
                ->orderBy('id', 'asc')
                ->get();
        }

        // Determine current theme (user-specific if sanctum token is present)
        $user = auth('sanctum')->user();
        $currentTheme = null;

        if ($user) {
            $active = ActiveThemeSetting::where('user_id', $user->id)
                ->where('device', 'app')
                ->latest()
                ->first();

            if ($active && $active->theme_id) {
                $currentTheme = ThemeSetting::active('app')
                    ->where('id', $active->theme_id)
                    ->first();
            }
        }

        if (!$currentTheme) {
            // Fallback to first available theme for app.
            $currentTheme = ThemeSetting::active('app')
                ->orderBy('id', 'asc')
                ->first();
        }

        $general_settings->site_logo = $currentTheme->site_logo ?? $general_settings->site_logo;
        $general_settings->dark_logo = $currentTheme->dark_logo ?? $general_settings->dark_logo;

        if ($includeThemes) {
            return response()->json($this->withAuthBackground([
                'success' => true,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'title' => 'Theme Settings',
                'view_type' => 'theme_settings',
                'themes' => $themes,
                'current_theme_setting' => $currentTheme,
                'general_settings' => $general_settings,
            ], 'app'), 200);
        }

        return response()->json($this->withAuthBackground([
            'success' => true,
            'debug_bar' => env('APP_DEBUG', false) ? true : false,
            'general_settings' => $general_settings,
            'current_theme_setting' => $currentTheme,
        ], 'app'), 200);
    }

    public function create()
    {
        $lims_role_list = Roles::where('is_active', true)->where('id', '!=', 1)->where('id', '!=', 2)->get()->map(function ($role) {
            return [
                'label' => $role->name,
                'value' => $role->id
            ];
        });

        $lims_customer_group_list = CustomerGroup::where('is_active', true)->get()->map(function ($group) {
            return [
                'label' => $group->name,
                'value' => $group->id
            ];
        });

        $lims_biller_list = Biller::where('is_active', true)->get()->map(function ($biller) {
            return [
                'label' => $biller->name,
                'value' => $biller->id
            ];
        });

        $lims_warehouse_list = Warehouse::where('is_active', true)->get()->map(function ($warehouse) {
            return [
                'label' => $warehouse->name,
                'value' => $warehouse->id
            ];
        });

        $warehouse_biller_role_ids = Roles::where('is_active', true)->where('id', '>', 2)->where('id', '!=', 5)->get()->map(function ($role) {
            return (string) $role->id;
        })->toArray();

        $customer_role_ids = ["5"];

        $formSchema = [
            "title" => "Register",
            "submit_url" => "/register",
            "method" => "POST",
            "show_app_bar" => false,
            "center_items" => true,
            "fields_after_button" => [
                [
                    "type" => "anchor",
                    "text" => "Already have an account?",
                    "highlight" => [
                        "text" => " Login Now",
                        "font_weight" => "bold",
                        "font_style" => "italic",
                    ],
                    "href" => "/login/create",
                    "href_type" => "form",
                    "text_align" => "center",
                    "font_size" => 20,
                    "font_weight" => "500",
                    "font_style" => "italic",
                ],
            ],
            "fields" => [
                [
                    "type" => "image",
                    "height" => 48,
                    "padding" => [
                        "top" => 0,
                        "bottom" => 10,
                    ]
                ],
                [
                    "type" => "helpertext",
                    "text" => "Create New Account",
                    "text_align" => "center",
                    "font_size" => 30,
                    "font_weight" => "bold",
                    "font_style" => "italic",
                    "padding" => [
                        "top" => 0,
                        "bottom" => 30,
                        "left" => 20,
                        "right" => 20,
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Username",
                    "placeholder" => "Enter Username",
                    "icon" => 'person'
                ],
                [
                    "type" => "text",
                    "name" => "email",
                    "label" => "Email",
                    "placeholder" => "Enter Email",
                    "keyboard_type" => "email",
                    "icon" => 'email'
                ],
                [
                    "type" => "text",
                    "name" => "phone_number",
                    "label" => "Phone Number",
                    "placeholder" => "Enter Phone Number",
                    "keyboard_type" => "phone",
                    "icon" => 'phone'
                ],
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter Company Name",
                    "icon" => 'store'
                ],
                [
                    "type" => "select",
                    "name" => "role_id",
                    "label" => "Role",
                    "value" => $lims_role_list->first() ? (string) $lims_role_list->first()['value'] : null,
                    "options" => $lims_role_list,
                    "icon" => 'people-setting'
                ],
                [
                    "type" => "select",
                    "name" => "warehouse_id",
                    "label" => "Warehouse",
                    "options" => $lims_warehouse_list,
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $warehouse_biller_role_ids
                        ]
                    ],
                    "icon" => 'manufacturing'
                ],
                [
                    "type" => "select",
                    "name" => "biller_id",
                    "label" => "Biller",
                    "options" => $lims_biller_list,
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $warehouse_biller_role_ids
                        ]
                    ],
                    "icon" => 'people'
                ],
                [
                    "type" => "text",
                    "name" => "customer_name",
                    "label" => "Customer Name",
                    "placeholder" => "Enter Customer Name",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "select",
                    "name" => "customer_group_id",
                    "label" => "Customer Group",
                    "options" => $lims_customer_group_list,
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "tax_no",
                    "label" => "Tax Number",
                    "placeholder" => "Enter Tax Number",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "city",
                    "label" => "City",
                    "placeholder" => "Enter City",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "state",
                    "label" => "State",
                    "placeholder" => "Enter State",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "postal_code",
                    "label" => "Postal Code",
                    "placeholder" => "Enter Postal Code",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "country",
                    "label" => "Country",
                    "placeholder" => "Enter Country",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ]
                ],
                [
                    "type" => "text",
                    "name" => "address",
                    "label" => "Address",
                    "placeholder" => "Enter Address",
                    "logics" => [
                        [
                            "field" => "role_id",
                            "values" => $customer_role_ids
                        ]
                    ],
                    "icon" => 'location'
                ],
                [
                    "type" => "password",
                    "name" => "password",
                    "label" => "Password",
                    "password" => true,
                    "placeholder" => "Enter Password",
                ],
                [
                    "type" => "password",
                    "name" => "password_confirmation",
                    "label" => "Confirm Password",
                    "password" => true,
                    "placeholder" => "Confirm Password",
                ],
            ]
        ];

        return response()->json($this->withAuthBackground($formSchema, 'app'));
    }

    public function register(RegistrationRequest $request)
    {
        try {
            // $validator = Validator::make($request->all(), [
            //     'name' => 'required|string|max:255|unique:users',
            //     'email' => [
            //         'email',
            //         'max:255',
            //             Rule::unique('users')->where(function ($query) {
            //             return $query->where('is_deleted', false);
            //         }),
            //     ],
            //     'password' => 'required|string|confirmed',
            // ]);


            // if ($validator->fails()) {
            //     return response()->json(['errors' => $validator->errors()], 422);
            // }

            // Begin creating a new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone_number,
                'company_name' => $request->company_name,
                'role_id' => $request->role_id,
                'biller_id' => $request->biller_id,
                'warehouse_id' => $request->warehouse_id,
                'is_active' => true,
                'is_deleted' => false,
                'password' => Hash::make($request->password),
            ]);

            // If the role is customer, create a customer entry
            if ($request->role_id == 5) {
                $data = $request->all();
                $data['name'] = $data['customer_name'];
                $data['user_id'] = $user->id;
                $data['is_active'] = true;
                Customer::create($data);
            }

            // Generate a Sanctum token for the user
            $token = $user->createToken('auth_token')->plainTextToken;
            $data['token'] = $token;
            $data['user'] = $user;
            // Return the response with the user and token
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'token' => $token,
                'data' => $data,
            ], 200);
        } catch (QueryException $e) {
            // Handle database-related errors (QueryException)
            return response()->json([
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            // Handle any other errors (general Exception)
            return response()->json([
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
