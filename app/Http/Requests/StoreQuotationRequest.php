<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'warehouse_id.required' => 'Please select a warehouse.',
            'products.required' => 'Please add at least one product.',
            'products.min' => 'Please add at least one product.',
            'document.mimes' => 'The document must be a file of type: jpg, jpeg, png, gif, pdf, csv, docx, xlsx, txt.',
        ];
    }
}
