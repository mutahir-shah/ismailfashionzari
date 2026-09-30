<?php

namespace Modules\Restaurant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use DB;
use Cache;
use Auth;

class Restaurant
{
    public function handle(Request $request, Closure $next)
    {
        $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
            return DB::table('general_settings')->select('site_logo','expiry_date','developed_by', 'modules')->latest()->first();
        });

        if(!$general_setting) {
            \DB::unprepared(file_get_contents(public_path('tenant_necessary.sql')));
            $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
                return DB::table('general_settings')->latest()->first();
            });
            copy(public_path("landlord/images/logo/").$general_setting->site_logo, public_path("logo/").$general_setting->site_logo);
        }

        // if(in_array('restaurant',explode(',',$general_setting->modules))) {

        //     View::share('general_setting', $general_setting);

        //     $restaurant_setting =  Cache::remember('restaurant_setting', 60*60*24*365, function () {
        //         return DB::table('restaurant_settings')->latest()->first();
        //     });

        //     View::share('restaurant_setting', $restaurant_setting);

        //     $todayDate = date("Y-m-d");
        //     if($general_setting->expiry_date) {
        //         $expiry_date = date("Y-m-d", strtotime($general_setting->expiry_date));
        //         if($todayDate > $expiry_date) {
        //             auth()->logout();
        //             return redirect()->route('contactForRenewal');
        //         }
        //     }
        //     //setting language
        //     if(isset($_COOKIE['language'])) {
        //         \App::setLocale($_COOKIE['language']);
        //     }
        //     else {
        //         \App::setLocale('en');
        //     }

        //     return $next($request);
        // }
        // else {
        //     return redirect('dashboard');
        // }

        return redirect('dashboard');
    }
}
