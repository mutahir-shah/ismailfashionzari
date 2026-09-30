<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
        $paymentStatusRule = $this->input('pos') ? 'nullable' : 'required';

        return [
            'reference_no'   => 'nullable|string|max:191|unique:sales,reference_no',
            'customer_id'    => 'required|exists:customers,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'currency_id'    => 'required',
            'item'           => 'required|min:1',
            'sale_status'    => 'required',
            'payment_status' => $paymentStatusRule,
            'document'       => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }
    
    public function messages(): array
    {
        return [
            'reference_no.unique'     => 'The reference number must be unique.',
            'customer_id.required'    => 'Please select a customer.',
            'warehouse_id.required'   => 'Please select a warehouse.',
            'item.required'           => 'Please add at least one item.',
            'sale_status.required'    => 'Sale status is required.',
            'payment_status.required' => 'Payment status is required.',
            'document.mimes'          => 'The document must be a file of type: jpg, jpeg, png, gif, pdf, csv, docx, xlsx, txt.',
        ];
    }
}
