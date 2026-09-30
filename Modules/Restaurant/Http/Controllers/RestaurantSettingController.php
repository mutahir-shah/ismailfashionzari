<?php

namespace Modules\Restaurant\Http\Controllers;

use Illuminate\Http\Request;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Modules\Restaurant\Entities\Page;
use App\Traits\CacheForget;
use Session;
use Cache;
use DB;

class RestaurantSettingController extends Controller
{
    use CacheForget;

    public function index()
    {
        $settings = DB::table('restaurant_settings')->first();

        $warehouse_list = DB::table('warehouses')->where('is_active',1)->get();
        $biller_list = DB::table('billers')->where('is_active',1)->get();

        return view ('restaurant::backend.settings.index', compact('settings','warehouse_list','biller_list'));
    }

    public function update(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }
        
        $data = [
            'site_title'          => $request->site_title,
            'theme'               => 'default',
            'theme_font'          => 'Inter',
            'theme_color'         => '#101010',
            'is_rtl'              => $request->is_rtl,
            'store_phone'         => $request->store_phone,
            'store_email'         => $request->store_email,
            'store_address'       => $request->store_address,
            'warehouse_id'        => $request->warehouse_id,
            'biller_id'           => $request->biller_id,
            'contact_form_email'  => $request->contact_form_email,
            'flat_rate_shipping'  => $request->flat_rate_shipping,
            'custom_css'          => $request->custom_css,
            'custom_js'           => $request->custom_js,
            'chat_code'           => $request->chat_code,
            'analytics_code'      => $request->analytics_code,
            'fb_pixel_code'       => $request->fb_pixel_code,
        ];

        if(isset($request->logo)){
            $this->validate($request, [
                'logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);
        }

        if(isset($request->favicon)){
            $this->validate($request, [
                'favicon' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);
        }
       
        if(isset($request->logo)) { 
            $logo = $request->logo;
            if ($logo) {
                $ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName =  date("Ymdhis") . '.' . $ext;
                $logo->move(public_path('frontend/images/'), $imageName); 
                $img_lg = Image::make(public_path('frontend/images/'). $imageName)
                          ->resize(300, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                          })
                          ->save(public_path('frontend/images/'). $imageName);

                $manager = new ImageManager(Driver::class);
                $image = $manager->read(public_path('frontend/images/'). $imageName);
                $image->cover(300, 300)->save(public_path('frontend/images/'). $imageName, 100);

                $data['logo'] = $imageName;
            }

        }

        if(isset($request->favicon)) { 
            $favicon = $request->favicon;
            if ($favicon) {
                $ext = pathinfo($favicon->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;
                //return $imageName;  
                $favicon->move(public_path('frontend/images/'), $imageName);
                $manager = new ImageManager(Driver::class);
                $image = $manager->read(public_path('frontend/images/'). $imageName);
                $image->cover(50, 50)->save(public_path('frontend/images/'). $imageName, 100);

                $data['favicon'] = $imageName;
            }
        }
        if(isset($request->checkout_pages))
            $data['checkout_pages'] = json_encode($request->checkout_pages);

        $setting = DB::table('restaurant_settings')->first();
        if(isset($setting->id)){
            DB::table('restaurant_settings')->where('id', 1)->update($data);
        }else{
            DB::table('restaurant_settings')->insert($data);
        }

        Session::flash('message', 'Settings updated successfully.');
        Session::flash('type', 'success');

        $this->cacheForget('restaurant_setting');

        return redirect()->back();
    }

    public function floorplan()
    {
        return view ('restaurant::backend.settings.floorplan.index');
    }

    public function table()
    {
        return view ('restaurant::backend.settings.table');
    }

    public function dayTime()
    {
        $day_times = DB::table('day_time')->get();
        return view('restaurant::backend.settings.day-time', compact('day_times'));
    }

    public function dayTimeUpdate(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $days = $request->input('day');
        $start_times = $request->input('start_time');
        $end_times = $request->input('end_time');

        for ($i = 0; $i < count($days); $i++) {
            $day = trim($days[$i]);
            $start = $start_times[$i];
            $end = $end_times[$i];

            if ($day) {
                DB::table('day_time')
                    ->where('id', $i+1)
                    ->update([
                        'day' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                        'updated_at' => now(),
                    ]);
            }
        }

        Session::flash('message', 'Day time added successfully.');
        Session::flash('type', 'success');

        $this->cacheForget('day_time');

        return redirect()->back();
    }

}
