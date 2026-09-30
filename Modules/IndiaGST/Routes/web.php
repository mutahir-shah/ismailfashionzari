<?php

use Illuminate\Support\Facades\Route;
use Modules\IndiaGST\Http\Controllers\IndiaGSTController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::prefix('indiagst')->name('indiagst.')->group(function () {
        Route::get('/', 'IndiaGSTController@index')->name('index');

        Route::resource('tax-profiles', 'GstTaxProfileController');
        Route::resource('generic-tax-mappings', 'GstGenericTaxMappingController');
        
        Route::get('calculation-preview', 'GstCalculationPreviewController@index')->name('calculation.preview');
        Route::post('calculation-preview', 'GstCalculationPreviewController@calculate')->name('calculation.calculate');
    });
});
