<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialCommerce\Http\Controllers\DashboardController;
use Modules\SocialCommerce\Http\Controllers\FeedController;
use Modules\SocialCommerce\Http\Controllers\PublicSocialController;
use Modules\SocialCommerce\Http\Controllers\SocialCommerceController;
use Modules\SocialCommerce\Http\Controllers\WhatsAppActionController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// 1. Determine if this is a SaaS Landlord environment
$isSaaS = (bool) config('database.connections.saleprosaas_landlord');

// 2. Build Tenancy middleware array dynamically
$tenancyMiddleware = $isSaaS ? [InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class] : [];

// ==========================================
// ADMIN/COMMON ROUTES
// ==========================================
$middlewares = array_merge(
    $tenancyMiddleware,
    ['common', 'auth', 'active']
);

Route::middleware($middlewares)->group(function () {
    Route::prefix('socialcommerce')->name('socialcommerce.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view social commerce');
        Route::get('/', [SocialCommerceController::class, 'index'])->name('index')->middleware('permission:view social commerce');
        Route::get('/catalog', [SocialCommerceController::class, 'catalog'])->name('catalog')->middleware('permission:manage social catalogue');
        Route::post('/catalog/{id}/toggle', [SocialCommerceController::class, 'togglePublish'])->name('togglePublish')->middleware('permission:manage social catalogue');
        Route::get('/catalog/{id}/qr', [SocialCommerceController::class, 'generateQr'])->whereNumber('id')->name('qr')->middleware('permission:manage social catalogue');
        Route::get('/settings', [SocialCommerceController::class, 'settings'])->name('settings')->middleware('permission:manage social commerce settings');
        Route::post('/settings', [SocialCommerceController::class, 'updateSettings'])->name('updateSettings')->middleware('permission:manage social commerce settings');
        
        Route::get('/feed', [FeedController::class, 'index'])->name('feed')->middleware('permission:manage social commerce settings');
        Route::post('/feed/regenerate', [FeedController::class, 'regenerateToken'])->name('feed.regenerate')->middleware('permission:manage social commerce settings');
        Route::post('/feed/refresh', [FeedController::class, 'manualRefresh'])->name('feed.refresh')->middleware('permission:manage social commerce settings');

        // WhatsApp Actions
        Route::get('/wa-share/{id}/{action}', [WhatsAppActionController::class, 'shareOrder'])->name('wa.share.order')->middleware('permission:sales-index');
    });
});

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::middleware($tenancyMiddleware)->group(function () {
    Route::get('/social-commerce/feed/meta/{token}', [FeedController::class, 'metaCsv'])->name('socialcommerce.public.feed');
    Route::get('/social/{product}', [PublicSocialController::class, 'show'])->whereNumber('product')->name('socialcommerce.public.show');
    Route::post('/social/{product}/order', [PublicSocialController::class, 'placeOrder'])->whereNumber('product')->name('socialcommerce.public.order');
});

