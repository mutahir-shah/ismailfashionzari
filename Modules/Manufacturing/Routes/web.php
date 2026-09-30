<?php

use App\Http\Controllers\ProductController;
use Modules\Manufacturing\Http\Controllers\ProductionController;
use Modules\Manufacturing\Http\Controllers\RecipeController;
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
    ['common', 'auth', 'active', 'warehouse.access']
);

Route::prefix('manufacturing')->name('manufacturing.')->middleware($middlewares)->group(function () {

    // Production (extra endpoints)
    Route::controller(ProductionController::class)->prefix('productions')->group(function () {
        Route::post('production-data', 'productionData')->name('productions.data');
        Route::get('product_production/{id}', 'productProductionData')->name('productions.product-production');
    });

    // Resource routes
    Route::resource('productions', ProductionController::class)->except(['show']);
    Route::resource('recipes', RecipeController::class)->except(['show']);

    // Product related
    Route::post('products/product-data', [ProductController::class, 'productData'])
        ->name('products.data');

    Route::post('product-data', [RecipeController::class, 'productData'])
        ->name('recipes.product-data');

    Route::get('recipes/lims_product_search', [ProductController::class, 'limsProductSearch'])
        ->name('products.search');

    Route::get('recipes/get-ingredient-data', [RecipeController::class, 'getIngredientData'])
        ->name('recipes.get-ingredient-data');

    Route::post('get-ingredients', [ProductionController::class, 'getIngredients'])
        ->name('productions.get-ingredients');

    Route::post('products/getdata/{id}/{variant_id}', [ProductController::class, 'getData'])
        ->name('products.getdata');

    // Dashboard / index
    Route::get('/', [\Modules\Manufacturing\Http\Controllers\ManufacturingController::class, 'index'])
        ->name('dashboard');
});
