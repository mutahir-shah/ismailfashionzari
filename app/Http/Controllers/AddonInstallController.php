<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\landlord\Tenant;


class AddonInstallController extends Controller
{
    public function saasInstall(Request $request)
    {
        $data = [
            'purchase_code' => $request->purchase_code,
            'product' => 'saas'
        ];
        $path = '';
        $module = 'saas';
        return $this->addonIstallUnzipMigrateRemoveTempFolder($data, $path, $module);
    }

    public function ecommerceInstall(Request $request)
    {
        $data = [
            'purchase_code' => $request->purchase_code,
            'product' => (config('database.connections.saleprosaas_landlord')) ? 'saas_ecom' : 'ecom'
        ];
        $path = '/Modules/';
        $module = 'ecommerce';
        return $this->addonIstallUnzipMigrateRemoveTempFolder($data, $path, $module);
    }

    public function woocommerceInstall(Request $request)
    {
        $data = [
            'purchase_code' => $request->purchase_code,
            'product' => (config('database.connections.saleprosaas_landlord')) ? 'saas_wcom' : 'wcom'
        ];
        $path = '/Modules/';
        $module = 'woocommerce';
        return $this->addonIstallUnzipMigrateRemoveTempFolder($data, $path, $module);
    }

    public function apiInstall(Request $request)
    {
        $data = [
            'purchase_code' => $request->purchase_code,
            'product' => (config('database.connections.saleprosaas_landlord')) ? 'saas_api' : 'api'
        ];
        $path = '/app/Http/Controllers/';
        $module = 'api';
        return $this->addonIstallUnzipMigrateRemoveTempFolder($data, $path, $module);
    }

    public function addonIstallUnzipMigrateRemoveTempFolder($data, $path, $module)
    {
        $db_str = '';
        if(!config('database.connections.saleprosaas_landlord')) {
            $db_str = 'db.';
        }
        if(!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __($db_str.'This feature is disable for demo!'));
        }

        $url = 'https://lion-coders.com/api/addon-install/';
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POSTREDIR, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
        $response = curl_exec($ch);
        curl_close($ch);

        if($response != 0) {
            $remote_file_path = $response;
            $remote_file_name = basename($remote_file_path);
            $local_file_path = base_path($path.$remote_file_name);

            $fp = fopen($local_file_path, 'w+');
            if($fp === false){
                return false;
            }

            $ch = curl_init($remote_file_path);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // SSL সমস্যা এড়াতে (প্রয়োজন হলে)
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $curl_exec = curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            
            // ডাউনলোড সফল হলে আনজিপ শুরু হবে
            if ($curl_exec) {
                // ****** Unzip ********
                $zip = new ZipArchive;
                $file = $local_file_path;
                $res = $zip->open($file);
                if ($res === TRUE) {
                    $zip->extractTo(base_path($path));
                    $zip->close();
                    // ****** Delete Zip File ******
                    File::delete($file);
                }

                if ($module == 'saas') {
                    return redirect('/');
                }

                if(!config('database.connections.saleprosaas_landlord')) {
                    if ($module == 'ecommerce' || $module == 'woocommerce') {
                        Artisan::call('module:migrate', ['--force' => true]);
                    }

                    if ($module != 'saas') {
                        $settings = DB::table('general_settings')->select('id','modules')->first();
                        if(isset($settings->modules) && (!in_array($module,explode(',',$settings->modules)))){
                            $new_modules = $settings->modules.','.$module;
                        }else{
                            $new_modules = $module;
                        }
                        DB::table('general_settings')->where('id',1)->update(['modules'=>$new_modules]);
                    }

                    if ($module == 'ecommerce') {
                        $this->categorySlug();
                        $this->brandSlug();
                        $this->productSlug();
                    }
                }
                else {
                    $tenant_all = Tenant::all();
                    if(count($tenant_all)) {
                        \Artisan::call('tenants:migrate');
                    }
                }

                $data = [
                    'path' => $response,
                ];
                $url = 'https://lion-coders.com/api/addon-db/';
                $ch = curl_init(); // Initialize cURL
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_POSTREDIR, 3);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
                $res = curl_exec($ch);
                curl_close($ch);
            }
            return redirect()->back()->with('message', __($db_str.'Add-on installed successfully!'));
        }
        else {
            return redirect()->back()->with('not_permitted', __($db_str.'Wrong purchase code!'));
        }
    }

    public function categorySlug()
    {
        $catgories = Category::select('id','name','slug')->get();
        foreach($catgories as $cat){
            $cat->slug = Str::slug($cat->name, '-');
            $cat->save();
        }
    }

    public function brandSlug()
    {
        $brands = Brand::select('id','title','slug')->get();
        foreach($brands as $brand){
            $brand->slug = Str::slug($brand->title, '-');
            $brand->save();
        }
    }

    public function productSlug()
    {
        $products = Product::select('id','name','slug')->get();
        foreach($products as $product){
            $product->slug = Str::slug($product->name, '-');
            $product->save();
        }
    }

}
