<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Transform products array from Flutter format to legacy format
        if ($this->has('products') && is_array($this->products)) {
            $products = $this->products;

            // Initialize arrays for legacy format
            $productIds = [];
            $productCodes = [];
            $qtys = [];
            $saleUnits = [];
            $netUnitPrices = [];
            $discounts = [];
            $taxRates = [];
            $taxes = [];
            $totals = [];

            foreach ($products as $product) {
                $productIds[] = $product['id'] ?? null;
                $productCodes[] = $product['code'] ?? '';
                $qtys[] = $product['qty'] ?? 1;
                $saleUnits[] = $product['sale_unit'] ?? [];
                $netUnitPrices[] = $product['price'] ?? 0;
                $discounts[] = $product['discount'] ?? 0;
                $taxRates[] = $product['tax'] ?? 0;

                // Calculate tax amount
                $qty = $product['qty'] ?? 1;
                $price = $product['price'] ?? 0;
                $taxRate = $product['tax'] ?? 0;
                $taxAmount = ($qty * $price * $taxRate) / 100;
                $taxes[] = $taxAmount;

                // Subtotal is already calculated by Flutter
                $totals[] = $product['subtotal'] ?? 0;
            }

            // Calculate item count and grand total
            $itemCount = count($products);
            $grandTotal = array_sum($totals);

            // Merge transformed data
            $this->merge([
                'product_id' => $productIds,
                'product_code' => $productCodes,
                'qty' => $qtys,
                'sale_unit' => $saleUnits,
                'net_unit_price' => $netUnitPrices,
                'discount' => $discounts,
                'tax_rate' => $taxRates,
                'tax' => $taxes,
                'subtotal' => $totals,
                'item' => $itemCount,
                'total_qty' => array_sum($qtys),
                'total_discount' => array_sum($discounts),
                'total_tax' => array_sum($taxes),
                'total_price' => $grandTotal,
                'grand_total' => $grandTotal,
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
