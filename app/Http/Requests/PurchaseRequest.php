<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PurchaseRequest extends BaseFormRequest
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
            $recieved = [];
            $batch_no = [];
            $expired_date = [];
            $purchase_unit = [];
            $net_unit_cost = [];
            $net_unit_margin = [];
            $net_unit_price = [];
            $discount = [];
            $tax_rate = [];
            $tax = [];
            $subtotal = [];
            $imei_number = [];

            foreach ($products as $product) {
                $product_id[] = $product['product_id'] ?? $product['id'] ?? null;
                $product_code[] = $product['code'] ?? '';
                $qty[] = $product['qty'] ?? 1;
                $recieved[] = $product['recieved'] ?? $product['qty'] ?? 1;
                $batch_no[] = $product['batch_no'] ?? '';
                $expired_date[] = $product['expired_date'] ?? '';

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
                $net_unit_margin[] = $product['net_unit_margin'] ?? $product['margin'] ?? 0;
                $net_unit_price[] = $product['net_unit_price'] ?? $product['price'] ?? 0;
                $discount[] = $product['discount'] ?? 0;
                $tax_rate[] = $product['tax_rate'] ?? 0;
                $tax[] = $product['tax'] ?? 0;
                $subtotal[] = $product['subtotal'] ?? (($product['qty'] ?? 1) * ($product['net_unit_cost'] ?? $product['cost'] ?? 0));
                $imei_number[] = $product['imei_number'] ?? '';
            }

            $this->merge([
                'product_id' => $product_id,
                'product_code' => $product_code,
                'qty' => $qty,
                'recieved' => $recieved,
                'batch_no' => $batch_no,
                'expired_date' => $expired_date,
                'purchase_unit' => $purchase_unit,
                'net_unit_cost' => $net_unit_cost,
                'net_unit_margin' => $net_unit_margin,
                'net_unit_price' => $net_unit_price,
                'discount' => $discount,
                'tax_rate' => $tax_rate,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'imei_number' => $imei_number,
            ]);
        }
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
            'currency_id' => 'required',
            'exchange_rate' => 'required|numeric', // Ensures exchange_rate is a valid number
            'product_code' => 'required|array', // Ensures product_code is an array
            'product_code.*' => 'required|string', // Ensures each product_code value is a string
            'qty' => 'required|array', // Ensures qty is an array
            'qty.*' => 'required|integer|min:1', // Each qty must be a valid integer and at least 1
            'document' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
        ];
    }

    public function messages()
    {
        return [
            'warehouse_id.required' => 'select a warehouse',
            'currency_id.required' => 'currency field is required.',
            'exchange_rate.required' => 'The exchange rate is required.',
            'exchange_rate.numeric' => 'The exchange rate must be a valid number.',
            'product_code.required' => 'Please insert a product.',
            'qty.required' => 'At least one quantity is required.',
            'qty.array' => 'The quantities must be in an array format.',
            'qty.*.required' => 'Each quantity must be provided.',
            'qty.*.integer' => 'Each quantity must be a valid number.',
            'qty.*.min' => 'Each quantity must be at least 1.',
        ];
    }
}
