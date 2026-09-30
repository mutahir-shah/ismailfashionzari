<?php

namespace Modules\IndiaGST\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GstCalculationPreviewController extends Controller
{
    public function index()
    {
        abort_if(!auth()->user()->hasPermissionTo('gst_calculation.preview'), 403, 'Unauthorized access');
        
        $states = \Modules\IndiaGST\Entities\IndiaGstState::all();
        $taxProfiles = \Modules\IndiaGST\Entities\GstTaxProfile::where('is_active', true)->get();
        
        return view('indiagst::calculation_preview.index', compact('states', 'taxProfiles'));
    }

    public function calculate(Request $request, \Modules\IndiaGST\Services\Calculation\TaxCalculationManager $manager, \Modules\IndiaGST\Services\Calculation\IndiaGstCalculationInputAssembler $assembler)
    {
        abort_if(!auth()->user()->hasPermissionTo('gst_calculation.preview'), 403, 'Unauthorized access');

        $input = $assembler->assemble($request->all(), auth()->user());

        $result = $manager->calculateIndiaGst($input);

        return response()->json($result);
    }
}
