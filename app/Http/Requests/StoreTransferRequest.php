<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
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

            // Get default unit for products without purchase_unit specified
            $defaultUnit = \App\Models\Unit::orderBy('id')->first();
            $defaultUnitName = $defaultUnit ? $defaultUnit->unit_name : 'pc';

            // Extract all required arrays from products
            $product_id = [];
            $product_code = [];
            $qty = [];
            $purchase_unit = [];
            $net_unit_cost = [];
            $tax_rate = [];
            $tax = [];
            $subtotal = [];
            $imei_number = [];
            $product_batch_id = [];

            foreach ($products as $product) {
                $product_id[] = $product['product_id'] ?? $product['id'] ?? null;
                $product_code[] = $product['code'] ?? '';
                $qty[] = $product['qty'] ?? 1;

                // Get unit name - look up from product if unit_id provided, otherwise use default
                if (isset($product['purchase_unit'])) {
                    $purchase_unit[] = $product['purchase_unit'];
                } elseif (isset($product['purchase_unit_id'])) {
                    $unit = \App\Models\Unit::find($product['purchase_unit_id']);
                    $purchase_unit[] = $unit ? $unit->unit_name : $defaultUnitName;
                } else {
                    $purchase_unit[] = $defaultUnitName;
                }

                $net_unit_cost[] = $product['net_unit_cost'] ?? $product['cost'] ?? 0;
                $tax_rate[] = $product['tax_rate'] ?? 0;
                $tax[] = $product['tax'] ?? 0;
                $subtotal[] = $product['subtotal'] ?? (($product['qty'] ?? 1) * ($product['net_unit_cost'] ?? $product['cost'] ?? 0));
                $imei_number[] = $product['imei_number'] ?? '';
                $product_batch_id[] = $product['product_batch_id'] ?? null;
            }

            $this->merge([
                'product_id' => $product_id,
                'product_code' => $product_code,
                'qty' => $qty,
                'purchase_unit' => $purchase_unit,
                'net_unit_cost' => $net_unit_cost,
                'tax_rate' => $tax_rate,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'imei_number' => $imei_number,
                'product_batch_id' => $product_batch_id,
            ]);
        }
    }
    public function rules(): array
    {
        return [
            'from_warehouse_id'   => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'qty'        => 'required|min:1',
            'document'       => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.required'   => 'Please select From warehouse.',
            'to_warehouse_id.required'   => 'Please select From warehouse.',
            'qty.required'           => 'Please add at least one product.',
            'document.mimes'          => 'The document must be a file of type: jpg, jpeg, png, gif, pdf, csv, docx, xlsx, txt.',
        ];
    }
}
