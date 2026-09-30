<?php

namespace Modules\SocialCommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use App\Models\Product;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    /**
     * Admin Feed Management Page
     */
    public function index()
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);
        
        if (!$settings->feed_token) {
            $settings->feed_token = Str::random(40);
            $settings->save();
        }

        $feedUrl = url('/social-commerce/feed/meta/' . $settings->feed_token);
        $includedCount = SocialProductSetting::where('is_published', 1)
            ->whereHas('product', function($q){ $q->where('is_active', true); })->count();
        $totalCount = Product::where('is_active', true)->count();
        $excludedCount = $totalCount - $includedCount;

        return view('socialcommerce::feed', compact('settings', 'feedUrl', 'includedCount', 'excludedCount'));
    }

    /**
     * Regenerate Feed Token
     */
    public function regenerateToken()
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);
        $settings->feed_token = Str::random(40);
        $settings->save();

        return redirect()->back()->with('message', __('db.Feed token regenerated successfully. Update your Meta Commerce Manager settings with the new URL.'));
    }

    /**
     * Public Meta CSV Feed Generation
     */
    public function metaCsv($token)
    {
        $settings = SocialCommerceSetting::first();

        if (!$settings || $settings->feed_token !== $token) {
            abort(403, __('db.Invalid Feed Token'));
        }

        if (!$settings->is_active) {
            abort(404, __('db.Social Commerce is disabled'));
        }

        // Only fetch published active products
        $publishedSettings = SocialProductSetting::where('is_published', 1)->with('product')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=meta_catalog_feed.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Output the cached file if it exists, otherwise generate it synchronously once
        $path = 'socialcommerce/meta_feed.csv';

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            $updated = \Illuminate\Support\Facades\DB::table('social_commerce_settings')
                ->where('feed_pending_refresh', false)
                ->update(['feed_pending_refresh' => true]);

            if ($updated > 0) {
                \Modules\SocialCommerce\Jobs\GenerateMetaFeedJob::dispatch();
            }

            return response(__('db.Feed is currently being generated. Please try again later.'), 503)
                ->header('Retry-After', 60);
        }

        return response()->file(storage_path('app/public/' . $path), $headers);
    }

    /**
     * Manually trigger queue refresh
     */
    public function manualRefresh()
    {
        $updated = \Illuminate\Support\Facades\DB::table('social_commerce_settings')
            ->where('feed_pending_refresh', false)
            ->update(['feed_pending_refresh' => true]);

        if ($updated > 0) {
            \Modules\SocialCommerce\Jobs\GenerateMetaFeedJob::dispatch();
            return redirect()->back()->with('message', __('db.Feed regeneration has been queued successfully.'));
        }

        return redirect()->back()->with('message', __('db.Feed regeneration is already in progress.'));
    }
}
