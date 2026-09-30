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
use DB;

class SetupController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function setup(Request $request)
    {
        $showSetup = false;
        if (config('database.connections.saleprosaas_landlord') && !tenant()) {
            $tenantId = $request->input('tenant_id');
            $tenant = Tenant::findOrFail($tenantId);
            $general_settings = $tenant->run(function () {
                return DB::table('general_settings')->first();
            });
            $install_url = "https://" . $tenantId . '.' . env('CENTRAL_DOMAIN');
            $showSetup = true;
        } else {
            $general_settings = GeneralSetting::first();
            $install_url = config('app.url');
        }

        if (!env('USER_VERIFIED')) {
            $showSetup = true;
        }

        $themeSetting = ThemeSetting::active('app')->first();

        return response()->json([
            'success' => true,
            'general_settings' => $general_settings,
            'current_theme_setting' => $themeSetting,
            'auth_background' => $this->buildAuthBackground($themeSetting),
            'dash_background' => $this->buildDashBackground($themeSetting),
            'install_url' => $install_url,
            'app_key' => $general_settings->app_key,
            'is_demo' => !env('USER_VERIFIED'),
            'show_setup' => $showSetup,
            'debug_bar' => env('APP_DEBUG', false) ? true : false,
        ]);
    }

    public function checkLicense(Request $request)
    {
        $request->validate([
            'install_url' => 'required|url',
            'app_key' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        $generalSetting = GeneralSetting::first();

        $appKey = $generalSetting->app_key;

        // Check app key logic (replace this with actual check)
        if ($request->app_key !== $appKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid App key.'
            ], 200);
        }

        // Generate a token (if your GeneralSetting table has one token for app)
        $token = Str::random(60);

        // $generalSetting = GeneralSetting::first();
        // $generalSetting->token = Hash::make($token);
        // $generalSetting->save();
        MobileToken::create([
            'name'        => ucwords($request->platform) ?? null,  // optional — if you’re collecting device name
            'ip'          => $request->ip ?? $_SERVER['REMOTE_ADDR'],
            'location'    => $request->location ?? null,
            'token'       => Hash::make($token),
            'is_active'   => true,
            'last_active' => now(),
        ]);
        // return response()->json([
        //     'token' => $token
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => (object) ['token' => $token],
            'navigate_url' => '/login/create',
        ], 200);
    }
}
