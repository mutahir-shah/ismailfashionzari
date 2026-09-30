<?php

namespace App\Http\Requests;

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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert products array to legacy format if present
        if ($this->has('products') && is_array($this->products)) {
            $products = $this->products;

            // Get default unit for products without sale_unit specified
            $defaultUnit = \App\Models\Unit::orderBy('id')->first();
            $defaultUnitName = $defaultUnit ? $defaultUnit->unit_name : 'pc';

            // Extract all required arrays from products
            $product_id = [];
            $product_code = [];
            $qty = [];
            $sale_unit = [];
            $net_unit_price = [];
            $discount = [];
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
                if (isset($product['sale_unit'])) {
                    $sale_unit[] = $product['sale_unit'];
                } elseif (isset($product['sale_unit_id'])) {
                    $unit = \App\Models\Unit::find($product['sale_unit_id']);
                    $sale_unit[] = $unit ? $unit->unit_name : $defaultUnitName;
                } else {
                    $sale_unit[] = $defaultUnitName;
                }

                $net_unit_price[] = $product['net_unit_price'] ?? $product['price'] ?? 0;
                $discount[] = $product['discount'] ?? 0;
                $tax_rate[] = $product['tax_rate'] ?? 0;
                $tax[] = $product['tax'] ?? 0;
                $subtotal[] = $product['subtotal'] ?? (($product['qty'] ?? 1) * ($product['net_unit_price'] ?? $product['price'] ?? 0));
                $imei_number[] = $product['imei_number'] ?? '';
                $product_batch_id[] = $product['product_batch_id'] ?? null;
            }

            $this->merge([
                'item' => count($products),
                'total_qty' => array_sum($qty),
                'total_discount' => array_sum($discount),
                'total_tax' => array_sum($tax),
                'total_price' => array_sum($subtotal),
                'grand_total' => array_sum($subtotal) + $this->input('shipping_cost', 0) - $this->input('order_discount', 0),
                'product_id' => $product_id,
                'product_code' => $product_code,
                'qty' => $qty,
                'sale_unit' => $sale_unit,
                'net_unit_price' => $net_unit_price,
                'discount' => $discount,
                'tax_rate' => $tax_rate,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'imei_number' => $imei_number,
                'product_batch_id' => $product_batch_id,
                'paid_amount' => $this->input('paid_amount', 0),
                'pos' => $this->input('pos', 0),
                'coupon_active' => $this->input('coupon_active', 0),
                'coupon_id' => $this->input('coupon_id', null),
                'draft' => $this->input('draft', 0),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'reference_no'   => 'nullable|string|max:191|unique:sales,reference_no',
            'customer_id'    => 'required|exists:customers,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'item'           => 'required|min:1',
            'sale_status'    => 'required',
            'payment_status' => 'required',
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
