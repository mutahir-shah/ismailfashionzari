<?php

use Illuminate\Support\Facades\Route;
use Modules\Repair\Http\Controllers\DeviceTypeController;
use Modules\Repair\Http\Controllers\RepairController;
use Modules\Repair\Http\Controllers\ServiceJobController;
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
    ['auth', 'verified', 'common', 'warehouse.access']
);

Route::prefix('repair')->name('repair.')->middleware($middlewares)->group(function () {


    // ── Dashboard ──────────────────────────────────────────────────────────
    Route::get('dashboard', [RepairController::class, 'index'])->name('dashboard');

    // Resource (index, create, store, show, edit, update, destroy)
    Route::resource('service', ServiceJobController::class);

    // DataTable AJAX
    Route::post('service/service-data', [ServiceJobController::class, 'serviceData'])
        ->name('service.data');

    // Parts & Billing page
    Route::get('service/{id}/parts', [ServiceJobController::class, 'partsAndBilling'])
        ->name('service.parts');

    // Fetch Print / Details Content HTML
    Route::get('service/{id}/print-content', [ServiceJobController::class, 'printContent'])
        ->name('service.print_content');

    // Parts Data AJAX
    Route::get('service/{id}/parts-data', [ServiceJobController::class, 'partsData'])
        ->name('service.parts-data');

    // Parts AJAX
    Route::post('service/{id}/add-part', [ServiceJobController::class, 'addPart'])
        ->name('service.add-part');
        
    Route::delete('service/{id}/remove-part/{itemId}', [ServiceJobController::class, 'removePart'])
        ->name('service.remove-part');

    Route::post('service/{id}/update-part/{itemId}',[ServiceJobController::class, 'updatePart'])->name('service.update-part');   // ← NEW

    // Charges AJAX
    Route::post('service/{id}/update-charges', [ServiceJobController::class, 'updateCharges'])
        ->name('service.update-charges');

    // Payments AJAX
    Route::post('service/{id}/add-payment', [ServiceJobController::class, 'addPayment'])
        ->name('service.add-payment');
    Route::delete('service/{id}/delete-payment/{paymentId}', [ServiceJobController::class, 'deletePayment'])
        ->name('service.delete-payment');

    // Quick status update AJAX
    Route::post('service/{id}/add-update', [ServiceJobController::class, 'addUpdate'])
        ->name('service.add-update');

    Route::get('lims_product_search', [ServiceJobController::class, 'limsProductSearch'])
            ->name('products.search');

    // ✅ Custom routes আগে
    Route::post('device-types/deletebyselection', [DeviceTypeController::class, 'deleteBySelection'])
        ->name('device-types.deletebyselection');

    Route::post('device-types/import', [DeviceTypeController::class, 'importDeviceType'])
        ->name('device-types.import');

    Route::post('device-types/export', [DeviceTypeController::class, 'exportDeviceType'])
        ->name('device-types.export');

    if (method_exists(DeviceTypeController::class, 'forCategory')) {
        Route::get('device-types-for-category', [DeviceTypeController::class, 'forCategory'])
            ->name('device-types.for-category');
    }

    // ✅ Resource route পরে
    Route::resource('device-types', DeviceTypeController::class)->except(['create', 'show']);

    // toggle এ {device_type} আছে তাই এটা resource এর পরে থাকলেও সমস্যা নেই
    if (method_exists(DeviceTypeController::class, 'toggleActive')) {
        Route::post('device-types/{device_type}/toggle', [DeviceTypeController::class, 'toggleActive'])
            ->name('device-types.toggle');
    }
});

