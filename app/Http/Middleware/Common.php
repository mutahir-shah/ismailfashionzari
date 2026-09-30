<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Auth;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;

class Common
{
    use \App\Traits\TenantInfo;

    public function handle(Request $request, Closure $next)
    {

        //get general setting value
        $general_setting = gen_setting();

        // ✅ Timezone setup
        $timezone = $general_setting->timezone ?? config('app.timezone');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        $todayDate = date("Y-m-d");
        if(config('database.connections.saleprosaas_landlord')) {
            $subdomain = $this->getTenantId();
            
            $tenant = \App\Models\landlord\Tenant::find($subdomain);
            $tenantStatus = 1;
            if ($tenant) {
                $tenantStatus = isset($tenant->status) ? $tenant->status : 1;
            }

            if ($tenantStatus == 0) {
                auth()->logout();
                if (!request()->is('login')) {
                    return redirect('/login')->with('not_permitted', __('db.account_inactive'));
                }
            } elseif ($general_setting->expiry_date) {
                $expiry_date = date("Y-m-d", strtotime($general_setting->expiry_date));
                if($todayDate > $expiry_date) {
                    auth()->logout();
                    return redirect('https://'.env('CENTRAL_DOMAIN').'/contact-for-renewal?id='.$subdomain);
                }
            }
            View::share('subdomain', $subdomain);
        }
        //setting language
        $currentLocale = app()->getLocale();
        $translations = Cache::rememberForever("translations_{$currentLocale}", function () use ($currentLocale) {
            if (!Schema::hasTable('translations')) {
                return [];
            }
            return \App\Models\Translation::getTrnaslactionsByLocale($currentLocale);
        });
        if (!empty($translations)) {
            app('translator')->addLines($translations, $currentLocale);
        }

        View::composer(['backend.layout.0main_rtl', 'backend.layout.main'], function ($view) {
            $languages = Cache::rememberForever('languages_list', function () {
                if (Schema::hasTable('languages')) {
                    return Language::select('id', 'name')->orderBy('name')->get();
                }
                else {
                    return collect();
                }
            });

            $view->with('languages', $languages);
        });

        //setting theme customizer properties
        View::share('theme', $_COOKIE['theme'] ?? 'light');
        View::share('theme_color', $_COOKIE['theme_color'] ?? '#7c5cc4');
        View::share('theme_font', $_COOKIE['theme_font'] ?? 'inter');

        $currency_list = Cache::remember('currency_list', 60*60*24*365, function () {
            return \App\Models\Currency::where('is_active', true)->get();
        });

        $currency = Cache::remember('currency', 60*60*24*365, function () use ($general_setting, $currency_list) {
            // $general_setting->currency already holds the currency ID
            return $currency_list->find($general_setting->currency);
        });

        View::share('general_setting', $general_setting);
        View::share('currency', $currency);
        View::share('currency_list', $currency_list);
        config([
            'staff_access' => $general_setting->staff_access,
            'is_packing_slip' => $general_setting->is_packing_slip,
            'date_format' => $general_setting->date_format,
            'currency' => $currency->symbol ?? $currency->code ?? '',
            'currency_position' => $general_setting->currency_position ?? 'prefix',
            'decimal' => $general_setting->decimal ?? 2,
            'is_zatca' => $general_setting->is_zatca,
            'zatca_mode' => $general_setting->zatca_mode ?? ($general_setting->is_zatca ? 'phase1' : 'disabled'),
            'company_name' => $general_setting->company_name,
            'vat_registration_number' => $general_setting->vat_registration_number,
            'without_stock' => $general_setting->without_stock,
            'addons' => $general_setting->modules,
        ]);

        // Alert queries and view share moved to AppServiceProvider View Composer

        if (Auth::check()) {
            $roleId = Auth::user()->role_id;

            $role = Cache::remember("user_role_{$roleId}", 60*60*24*365, function () use ($roleId) {
                return DB::table('roles')->find($roleId);
            });
            View::share('role', $role);

            $permission_list = Cache::remember('permission_list', 60*60*24*365, function () {
                return DB::table('permissions')->get();
            });
            View::share('permission_list', $permission_list);

            $role_has_permissions = Cache::remember("role_has_permissions_{$roleId}", 60*60*24*365, function () use ($roleId) {
                return DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->get();
            });
            View::share('role_has_permissions', $role_has_permissions);

            $role_has_permissions_list = Cache::remember("role_has_permissions_list{$roleId}", 60*60*24*365, function () use ($roleId) {
                return DB::table('permissions')
                    ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_id', $roleId)
                    ->select('permissions.name')
                    ->get();
            });
            View::share('role_has_permissions_list', $role_has_permissions_list);
        }

        // categories_list moved to helpers.php as get_active_categories()

        return $next($request);
    }
}
