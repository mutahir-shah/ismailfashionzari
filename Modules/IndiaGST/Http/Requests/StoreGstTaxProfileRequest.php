<?php

namespace Modules\IndiaGST\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\IndiaGST\Rules\GstTaxProfileOverlapRule;

class StoreGstTaxProfileRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasPermissionTo('gst_tax_profiles.manage');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'taxability_type' => 'required|string|in:Taxable,Zero-rated,Nil-rated,Exempt,Non-GST',
            'total_gst_rate' => 'required|numeric|min:0|max:100',
            'cess_calculation_type' => 'required|string|in:none,percentage,per_unit',
            'cess_rate' => 'nullable|numeric|min:0|max:100',
            'cess_amount_per_unit' => 'nullable|numeric|min:0',
            'effective_from' => [
                'required',
                'date',
                new GstTaxProfileOverlapRule($this->input('code'), $this->input('effective_to'))
            ],
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean'
        ];
    }
}
