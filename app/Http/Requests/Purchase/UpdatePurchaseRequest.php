<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_code' => 'required|array',
            'product_code.*' => 'required|string',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }
    
    public function messages()
    {
        return [
            'warehouse_id.required' => 'Select a warehouse',
            'product_code.required' => 'Please insert a product.',
            'qty.required' => 'At least one quantity is required.',
            'qty.array' => 'The quantities must be in an array format.',
            'qty.*.required' => 'Each quantity must be provided.',
            'qty.*.numeric' => 'Each quantity must be a valid number.',
            'qty.*.min' => 'Each quantity must be at least 0.01.',
        ];
    }
}
