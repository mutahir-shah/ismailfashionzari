<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends BaseFormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255',Rule::unique('products')->ignore($this->route('product'))->where(function ($query) {
                    $query->where('is_active', 1);
                }),],
            'code' => ['required','string','max:255',Rule::unique('products')->ignore($this->route('product'))->where(function ($query) {
                    $query->where('is_active', 1);
                }),],
            'type' => 'required|string|in:standard,digital,combo,service',
            'barcode_symbology' => 'required|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'purchase_unit_id' => 'required|exists:units,id',
            'sale_unit_id' => 'required|exists:units,id',
            'cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => 'The product name is required.',
            'name.unique' => 'The product name must be unique.',
            'code.required' => 'The product code is required.',
            'code.unique' => 'The product code must be unique.',
            'type.required' => 'The product type is required.',
            'type.in' => 'The product type must be one of: standard, digital, combo, or service.',
            'barcode_symbology.required' => 'The barcode symbology is required.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category does not exist.',
            'unit_id.required' => 'The unit field is required.',
            'unit_id.exists' => 'The selected unit does not exist.',
            'purchase_unit_id.required' => 'The purchase unit field is required.',
            'purchase_unit_id.exists' => 'The selected purchase unit does not exist.',
            'sale_unit_id.required' => 'The sale unit field is required.',
            'sale_unit_id.exists' => 'The selected sale unit does not exist.',
            'cost.required' => 'The cost field is required.',
            'cost.numeric' => 'The cost must be a valid number.',
            'cost.min' => 'The cost must be at least 0.',
            'price.required' => 'The price field is required.',
            'price.numeric' => 'The price must be a valid number.',
            'price.min' => 'The price must be at least 0.',
        ];
    }
}
