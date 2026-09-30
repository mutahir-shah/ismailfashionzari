<?php

namespace Modules\IndiaGST\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GstTaxProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('indiagst::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('indiagst::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\Modules\IndiaGST\Http\Requests\StoreGstTaxProfileRequest $request): RedirectResponse
    {
        \Modules\IndiaGST\Entities\GstTaxProfile::create($request->validated());
        return redirect()->back()->with('message', __('indiagst::app.tax_profile_created'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('indiagst::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('indiagst::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\Modules\IndiaGST\Http\Requests\UpdateGstTaxProfileRequest $request, $id): RedirectResponse
    {
        $profile = \Modules\IndiaGST\Entities\GstTaxProfile::findOrFail($id);
        $profile->update($request->validated());
        return redirect()->back()->with('message', __('indiagst::app.tax_profile_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Not used as we soft-delete / deactivate
    }
}
