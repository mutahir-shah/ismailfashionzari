<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert products array to legacy format if present
        if ($this->has('products') && is_array($this->products)) {
            $products = $this->products;

            // Extract all required arrays from products
            $product_id = [];
            $product_code = [];
            $qty = [];
            $unit_cost = [];
            $action = [];

            foreach ($products as $product) {
                $product_id[] = $product['product_id'] ?? $product['id'] ?? null;
                $product_code[] = $product['code'] ?? '';
                $qty[] = $product['qty'] ?? 1;
                $unit_cost[] = $product['unit_cost'] ?? $product['cost'] ?? 0;
                $action[] = $product['action'] ?? '+'; // Default to addition
            }

            $this->merge([
                'item' => count($products),
                'total_qty' => array_sum($qty),
                'product_id' => $product_id,
                'product_code' => $product_code,
                'qty' => $qty,
                'unit_cost' => $unit_cost,
                'action' => $action,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|array',
            'qty' => 'required|array',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Please select a warehouse.',
            'product_id.required' => 'Please add at least one product.',
            'qty.required' => 'Quantities are required.',
            'document.mimes' => 'The document must be a file of type: jpg, jpeg, png, gif, pdf, csv, docx, xlsx, txt.',
        ];
    }
}
