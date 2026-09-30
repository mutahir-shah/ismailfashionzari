<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Models\GeneralSetting;
use App\Models\MobileToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\ThemeSetting;
use App\Models\landlord\Tenant;

class LoginController extends Controller
{
    use ProvidesThemeBackgrounds;


    public function getLoginForm()
    {

        $themeSetting = ThemeSetting::active('app')->first();

        $formSchema = [
            "title" => "Login",
            "submit_url" => "/login",
            "method" => "POST",
            "show_app_bar" => false,
            "center_items" => true,
            "fields_after_button" => [
                [
                    "type" => "anchor",
                    "text" => "Don't have an account?",
                    "highlight" => [
                        "text" => " Register",
                        "font_weight" => "bold",
                        "font_style" => "italic",
                    ],
                    "href" => "/register/create",
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
                    "text" => "Login to Your Account",
                    "text_align" => "center",
                    "font_size" => 30,
                    "font_weight" => "bold",
                    "font_style" => "italic",
                    "padding" => [
                        "top" => 0,
                        "bottom" => 20,
                        "left" => 20,
                        "right" => 20,
                    ]
                ],
                [
                    "type" => "space",
                    "height" => 20,
                ],
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Username",
                    "placeholder" => "Enter Username",
                    "keyboard_type" => "email",
                    "icon" => 'person'
                ],
                [
                    "type" => "text",
                    "name" => "password",
                    "password" => true,
                    "label" => "Password",
                    "placeholder" => "Enter Password",
                    "icon" => 'key'
                ],
                [
                    "type" => "anchor",
                    "text" => "Forgot Password?",
                    "href" => "/forgot-password",
                    "href_type" => "form",
                    "text_align" => "right",
                    "padding" => [
                        "right" => 10,
                    ]
                ],
            ],
        ];
        return response()->json($this->withAuthBackground($formSchema, 'app'));
    }

    public function login(LoginRequest $request)
    {


        try {
            $input = $request->validated();
            $fieldType = filter_var($input['name'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

            // Check if the username/email exists
            $user = \App\Models\User::where($fieldType, $input['name'])->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Username is incorrect',
                    'errors' => ['name' => ['Username is incorrect']],
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 401);
            }


            if (!auth()->attempt([$fieldType => $input['name'], 'password' => $input['password']])) {
                return response()->json([
                    'message' => 'Password is incorrect',
                    'errors' => ['password' => ['Password is incorrect']],
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 401);
            }

            // Generate an API token for the user
            $user = auth()->user();
            $token = $user->createToken('API Token')->plainTextToken;
            $data['token'] = $token;
            $data['user'] = $user;
            $data['user_id'] = $user->id;
            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'action' => 'login',
                'navigate_type' => 'action',
                'navigate_params' => $data,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage(),
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }
}
