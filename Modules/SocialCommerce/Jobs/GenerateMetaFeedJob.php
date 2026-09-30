<?php

namespace Modules\SocialCommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use Illuminate\Support\Facades\Storage;

class GenerateMetaFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $settings = SocialCommerceSetting::first();
            if (!$settings || !$settings->is_active) {
                return;
            }

            $publishedSettings = SocialProductSetting::where('is_published', 1)->with('product')->get();
            $columns = ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'gtin', 'mpn'];

            $path = 'socialcommerce/meta_feed.csv';
            
            // Write to a temporary file first to avoid corruption if job dies halfway
            $tempFile = tmpfile();
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $file = fopen($tempPath, 'w');
            fputcsv($file, $columns);

            $resolver = new \Modules\SocialCommerce\Services\ProductLinkResolver();
            $currencyCode = config('currency_code', 'USD');

            foreach ($publishedSettings as $setting) {
                $product = $setting->product;
                if (!$product || !$product->is_active) continue;

                $id = $product->code ?: $product->id;
                $title = $setting->social_title ?: $product->name;
                $description = $setting->social_description ?: strip_tags($product->product_details);
                $description = $description ?: 'No description available';
                
                $availability = $product->qty > 0 ? 'in stock' : 'out of stock';
                $condition = 'new';
                
                $price = $product->price . ' ' . $currencyCode;
                $link = $resolver->resolve($product);
                
                $image = $setting->social_image 
                    ? asset('images/product/social/' . $setting->social_image) 
                    : asset('images/product/' . explode(',', $product->image)[0]);

                $brand = $product->brand ? $product->brand->title : 'Store';
                $gtin = ''; 
                $mpn = $product->code;

                fputcsv($file, [$id, $title, $description, $availability, $condition, $price, $link, $image, $brand, $gtin, $mpn]);
            }
            
            fclose($file);

            // Move temp file to storage (using Storage facade)
            Storage::disk('public')->put($path, file_get_contents($tempPath));

            // Update settings to success
            $settings->feed_last_generated = now();
            $settings->feed_pending_refresh = false;
            $settings->save();
            
        } catch (\Exception $e) {
            $settings = SocialCommerceSetting::first();
            if ($settings) {
                $settings->feed_last_failed = now();
                $settings->feed_latest_error = substr($e->getMessage(), 0, 500);
                $settings->feed_pending_refresh = false;
                $settings->save();
            }
            throw $e;
        }
    }
}
