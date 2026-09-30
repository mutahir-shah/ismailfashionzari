<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!function_exists('normalize_to_sql_datetime')) {
    function normalize_to_sql_datetime($input, $useCurrentTime = false)
    {
        if (empty($input)) {
            return Carbon::now()->format('Y-m-d H:i:s');
        }

        $input = trim($input);

        // Replace multiple possible separators with "-"
        $normalized = preg_replace('/[\/\.\s]+/', '-', $input);

        // Formats to test (you can add more if needed)
        $formats = [
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'm-d-Y',
            'm/d/Y',
            'm.d.Y',
            'Y-m-d',
            'Y/m/d',
            'Y.m.d',
        ];

        foreach ($formats as $fmt) {
            try {
                $date = Carbon::createFromFormat($fmt, $normalized);

                if ($date !== false) {
                    if ($useCurrentTime) {
                        // inject current time if only date provided
                        $date->setTimeFrom(Carbon::now());
                    }
                    return $date->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // just continue to next format
            }
        }

        // fallback: try Carbon::parse (loose parsing)
        try {
            $date = Carbon::parse($input);
            if ($useCurrentTime) {
                $date->setTimeFrom(Carbon::now());
            }
            return $date->format('Y-m-d') . ' ' . date('H:i:s');
        } catch (\Exception $e) {
            // totally failed → return current datetime
            return Carbon::now()->format('Y-m-d H:i:s');
        }
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = null, $position = null, $decimal = null, $mask = false)
    {
        if ($mask) {
            return '****';
        }

        $currency = $currency ?? config('currency');
        $position = $position ?? config('currency_position');
        $decimal  = $decimal ?? config('decimal');

        $formatted = number_format((float) $amount, $decimal, '.', ',');

        if ($position == 'prefix') {
            return $currency . ' ' . $formatted;
        }

        return $formatted . ' ' . $currency;
    }
}

if (! function_exists('gen_setting')) {
    function gen_setting()
    {
        try {
            return Cache::remember('general_setting', 60 * 60 * 24 * 365, function () {
                $setting = null;
                try {
                    if (Schema::hasTable('general_settings')) {
                        $setting = DB::table('general_settings')->latest()->first();
                    }
                } catch (\Throwable $e) {
                    $setting = null;
                }

                $currencyId = 1;
                try {
                    if (Schema::hasTable('currencies')) {
                        $currencyId = DB::table('currencies')->value('id') ?: 1;
                    }
                } catch (\Throwable $e) {
                    $currencyId = 1;
                }

                $defaults = [
                    'id' => 1,
                    'site_title' => 'SalePro',
                    'site_logo' => 'logo.png',
                    'dark_logo' => null,
                    'is_rtl' => false,
                    'currency' => $currencyId,
                    'currency_position' => 'prefix',
                    'staff_access' => 'own',
                    'date_format' => 'd-m-Y',
                    'theme' => 'default',
                    'is_packing_slip' => false,
                    'decimal' => 2,
                    'is_zatca' => false,
                    'company_name' => 'SalePro',
                    'vat_registration_number' => '',
                    'without_stock' => 'no',
                    'show_products_details_in_sales_table' => false,
                    'font_css' => null,
                    'pos_css' => null,
                    'auth_css' => null,
                    'custom_css' => null,
                    'favicon' => null,
                    'developed_by' => 'LionCoders',
                    'phone' => '',
                    'email' => '',
                    'invoice_format' => 'standard',
                    'state' => 1,
                    'modules' => '',
                ];

                if (!$setting) {
                    return (object) $defaults;
                }

                foreach ($defaults as $key => $value) {
                    if (!property_exists($setting, $key)) {
                        $setting->{$key} = $value;
                    }
                }

                return $setting;
            });
        } catch (\Throwable $e) {
            return (object) [
                'id' => 1,
                'site_title' => 'SalePro',
                'site_logo' => 'logo.png',
                'dark_logo' => null,
                'is_rtl' => false,
                'currency' => 1,
                'currency_position' => 'prefix',
                'staff_access' => 'own',
                'date_format' => 'd-m-Y',
                'theme' => 'default',
                'is_packing_slip' => false,
                'decimal' => 2,
                'is_zatca' => false,
                'company_name' => 'SalePro',
                'vat_registration_number' => '',
                'without_stock' => 'no',
                'show_products_details_in_sales_table' => false,
                'font_css' => null,
                'pos_css' => null,
                'auth_css' => null,
                'custom_css' => null,
                'favicon' => null,
                'developed_by' => 'LionCoders',
                'phone' => '',
                'email' => '',
                'invoice_format' => 'standard',
                'state' => 1,
                'modules' => '',
            ];
        }
    }
}

if (! function_exists('get_active_warehouses')) {
    function get_active_warehouses()
    {
        return Cache::remember('warehouse_list', 60 * 60 * 24 * 365, function () {
            return DB::table('warehouses')->where('is_active', true)->get();
        });
    }
}

if (! function_exists('get_active_units')) {
    function get_active_units()
    {
        return Cache::remember('unit_list', 60 * 60 * 24 * 365, function () {
            return DB::table('units')->where('is_active', true)->get();
        });
    }
}

if (! function_exists('get_active_taxes')) {
    function get_active_taxes()
    {
        return Cache::remember('tax_list', 60 * 60 * 24 * 365, function () {
            return DB::table('taxes')->where('is_active', true)->get();
        });
    }
}

if (! function_exists('get_active_categories')) {
    function get_active_categories()
    {
        return Cache::remember('categories_list', 60 * 60 * 24 * 365, function () {
            return DB::table('categories')->where('is_active', true)->get();
        });
    }
}
