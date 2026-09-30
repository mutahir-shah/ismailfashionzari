<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use App\Models\Translation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\GeneralSetting;
use App\Services\PermissionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Path relative to the root of your project
        $helperFile = app_path('Helpers/helpers.php');

        if (file_exists($helperFile)) {
            require_once($helperFile);
        }
    }


    public function boot()
    {
        if (class_exists('App\Models\Sale')) {
            \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
                'sale' => \App\Models\Sale::class,
            ]);
        }
        Schema::defaultStringLength(191);
        $this->app->bind(\App\ViewModels\ISmsModel::class, \App\ViewModels\SmsModel::class);

        $viewComposerLogic = function () {
            View::composer('*', function ($view) {
                if (!isset($view->getData()['errors'])) {
                    try {
                        $view->with('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag()));
                    } catch (\Throwable $e) {
                        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
                    }
                }
            });

            View::composer(
                ['backend.layout.0main_rtl', 'backend.layout.main', 'backend.sale.pos'],
                function ($view) {
                    $general_setting = gen_setting();

                    $alert_product = Cache::remember('alert_product_count', 60 * 15, function () {
                        try {
                            if (!Schema::hasTable('products')) {
                                return 0;
                            }
                            return DB::table('products')
                                ->where('is_active', true)
                                ->whereColumn('alert_quantity', '>', 'qty')
                                ->count();
                        } catch (\Throwable $e) {
                            return 0;
                        }
                    });

                    $dso_alert_product_no = Cache::remember(
                        'dso_alert_count_' . date('Y-m-d'),
                        60 * 15,
                        function () {
                            try {
                                if (!Schema::hasTable('dso_alerts')) {
                                    return 0;
                                }
                                $record = DB::table('dso_alerts')
                                    ->select('number_of_products')
                                    ->whereDate('created_at', date('Y-m-d'))
                                    ->first();

                                return $record?->number_of_products ?? 0;
                            } catch (\Throwable $e) {
                                return 0;
                            }
                        }
                    );

                    $days = (int) ($general_setting->expiry_alert_days ?? 0);

                    $expire_alert_products = Cache::remember(
                        'expire_alert_count_' . $days,
                        60 * 15,
                        function () use ($days) {
                            try {
                                if (!Schema::hasTable('product_batches') || !Schema::hasTable('products')) {
                                    return 0;
                                }
                                return DB::table('product_batches')
                                    ->join('products', 'products.id', '=', 'product_batches.product_id')
                                    ->where('products.is_active', true)
                                    ->where('product_batches.qty', '>', 0)
                                    ->whereDate(
                                        'product_batches.expired_date',
                                        '<=',
                                        now()->addDays($days)->format('Y-m-d')
                                    )
                                    ->count();
                            } catch (\Throwable $e) {
                                return 0;
                            }
                        }
                    );

                    $pending_ecommerce_orders = 0;
                    try {
                        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'sale_type') && Schema::hasColumn('sales', 'sale_status')) {
                            $pending_ecommerce_orders = DB::table('sales')
                                ->where('sale_type', 'online')
                                ->where('sale_status', 2)
                                ->count();
                        }
                    } catch (\Throwable $e) {
                        $pending_ecommerce_orders = 0;
                    }

                    $user = Auth::user();
                    $role_has_permissions_list = collect();
                    if ($user?->role_id) {
                        try {
                            if (Schema::hasTable('permissions') && Schema::hasTable('role_has_permissions')) {
                                $roleId = $user->role_id;
                                if (app()->environment('testing') || app()->runningUnitTests()) {
                                    Cache::forget("role_has_permissions_list{$roleId}");
                                }
                                $role_has_permissions_list = Cache::remember("role_has_permissions_list{$roleId}", 60 * 60 * 24 * 365, function () use ($roleId) {
                                    return DB::table('permissions')
                                        ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                        ->where('role_id', $roleId)
                                        ->select('permissions.name')
                                        ->get();
                                });
                            }
                        } catch (\Throwable $e) {
                            $role_has_permissions_list = collect();
                        }
                    }

                    $languages = collect();
                    try {
                        if (Schema::hasTable('languages')) {
                            $languages = Cache::remember('languages_list', 60 * 60 * 24 * 365, function () {
                                return DB::table('languages')->get();
                            });
                        }
                    } catch (\Throwable $e) {
                        $languages = collect();
                    }

                    $view->with([
                        'alert_product' => $view->getData()['alert_product'] ?? $alert_product,
                        'dso_alert_product_no' => $view->getData()['dso_alert_product_no'] ?? $dso_alert_product_no,
                        'expire_alert_products' => $view->getData()['expire_alert_products'] ?? $expire_alert_products,
                        'pending_ecommerce_orders' => $view->getData()['pending_ecommerce_orders'] ?? $pending_ecommerce_orders,
                        'role_has_permissions_list' => $view->getData()['role_has_permissions_list'] ?? $role_has_permissions_list,
                        'languages' => $view->getData()['languages'] ?? $languages,
                        'errors' => $view->getData()['errors'] ?? session()->get('errors', new \Illuminate\Support\ViewErrorBag()),
                        'theme' => $view->getData()['theme'] ?? session()->get('theme', 'default.css'),
                        'theme_font' => $view->getData()['theme_font'] ?? session()->get('theme_font', 'inter'),
                        'theme_color' => $view->getData()['theme_color'] ?? session()->get('theme_color', '#7c5cc4'),
                        'product_qty_alert_active' => $view->getData()['product_qty_alert_active'] ?? false,
                    ]);
                }
            );
        };

        $translationLogic = function () {
            try {
                if (!DB::connection()->getDatabaseName()) {
                    return;
                }
            } catch (\Exception $e) {
                return;
            }

            try {
                if (isset($_COOKIE['language'])) {
                    App::setLocale($_COOKIE['language']);
                } elseif (Schema::hasTable('languages')) {
                    $language = DB::table('languages')->where('is_default', true)->first();
                    App::setLocale($language->language ?? 'en');
                } else {
                    App::setLocale('en');
                }

                if (Schema::hasTable('translations')) {
                    $currentLocale = App::getLocale();

                    $translations = Cache::rememberForever("translations_{$currentLocale}", function () use ($currentLocale) {
                        return \App\Models\Translation::getTrnaslactionsByLocale($currentLocale);
                    });

                    if (!empty($translations)) {
                        app('translator')->addLines($translations, $currentLocale);
                    }

                    $fallbackLocale = (string) config('app.fallback_locale', 'en');
                    if ($fallbackLocale !== $currentLocale) {
                        $fallbackTranslations = \App\Models\Translation::getTrnaslactionsByLocale($fallbackLocale);
                        if (!empty($fallbackTranslations)) {
                            app('translator')->addLines($fallbackTranslations, $fallbackLocale);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore translation load errors
            }
        };

        $maintenanceCheck = function () {
            if (!Schema::hasTable('general_settings')) {
                return;
            }

            $general_setting = GeneralSetting::latest()->first();

            if (!$general_setting || empty($general_setting->maintenance_allowed_ips)) {
                return;
            }

            $allowedIps = array_map(
                'trim',
                explode(',', $general_setting->maintenance_allowed_ips)
            );

            if (!in_array(request()->ip(), $allowedIps)) {
                abort(503);
            }
        };

        if (config('database.connections.saleprosaas_landlord')) {
            if (!app()->bound('tenancy')) {
                $locale = null;
                if (!request()->is('superadmin*') && isset($_COOKIE['frontend_language'])) {
                    $locale = $_COOKIE['frontend_language'];
                } elseif (config('database.connections.saleprosaas_landlord.database') && Schema::hasTable('languages')) {
                    $default_language = DB::table('languages')->where('is_default', true)->first();
                    $locale = $default_language->code ?? 'en';
                } else {
                    $locale = 'en';
                }

                App::setLocale($locale);

                $langFile = resource_path("lang/{$locale}.php");
                if (!file_exists($langFile)) {
                    $langFile = resource_path("lang/master.php");
                }

                $transData = include $langFile;
                $translations = [];
                foreach ($transData as $group => $items) {
                    foreach ($items as $key => $value) {
                        $translations["{$group}.{$key}"] = $value;
                    }
                }
                app('translator')->addLines($translations, $locale);
            }

            Event::listen(TenancyBootstrapped::class, function () use ($translationLogic, $maintenanceCheck, $viewComposerLogic) {
                $translationLogic();
                $maintenanceCheck();
                $viewComposerLogic();
            });
        } else {
            if (empty(config('database.connections.mysql.database'))) {
                if (!$this->app->runningInConsole() && !request()->is('install/*')) {
                    redirect('/install/step-1')->send();
                }
                return;
            }
            $translationLogic();
            $maintenanceCheck();
            $viewComposerLogic();
        }
    }
}
