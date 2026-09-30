<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product_image = explode(",", $this->image);
        $product_image = htmlspecialchars($product_image[0]);

        // Process product image
        if ($product_image && $product_image != 'zummXD2dvAtI.png') {
            $smallImagePath = public_path("images/product/small/{$product_image}");
            $largeImagePath = public_path("images/product/{$product_image}");

            if (file_exists($smallImagePath)) {
                $imageUrl = url("images/product/small/{$product_image}");
            } elseif (file_exists($largeImagePath)) {
                $imageUrl = url("images/product/{$product_image}");
            } else {
                $imageUrl = url("images/zummXD2dvAtI.png");
            }
        } else {
            $imageUrl = url("images/zummXD2dvAtI.png");
        }

        // Calculate quantity based on product type
        if ($this->type == 'standard') {
            $qty = $this->warehouses->sum('pivot.qty');
        } else {
            $qty = $this->qty;
        }

        // Determine stock worth
        if (config('currency_position') == 'prefix') {
            $stockWorth = config('currency') . ' ' . ($qty * $this->price) . ' / ' . config('currency') . ' ' . ($qty * $this->cost);
        } else {
            $stockWorth = ($qty * $this->price) . ' ' . config('currency') . ' / ' . ($qty * $this->cost) . ' ' . config('currency');
        }

        // Handle combo products
        $data = [
            'id' => $this->id,
            'image_url' => $imageUrl,
            'name' => Str::limit($this->name, 30),
            'code' => $this->code,
            'brand' => $this->brand ? $this->brand->title : "N/A",
            'category' => $this->category ? $this->category->name : "N/A",
            'quantity' => "<span style='font-size: 20px;'>" . $qty . "</span>",
            'unit' => $this->unit ? $this->unit->unit_name : 'N/A',
            'cost' => number_format($this->cost, 2),
            'price' => number_format($this->price, 2),
            'stock_worth' => $stockWorth,
        ];

        // Add combo product specific fields
        if ($this->type == 'combo') {
            $data['wastage_percent'] = $this->wastage_percent ?? 'N/A';
            $combo_unit_id = $this->combo_unit_id ?? 'N/A';
            if ($combo_unit_id != 'N/A') {
                $combo_unit_arr = explode(',', $combo_unit_id);
                $units = \App\Models\Unit::whereIn('id', $combo_unit_arr)->pluck('unit_name', 'id')->toArray();
                $combo_unit_names = array_map(function ($id) use ($units) {
                    return $units[$id] ?? '';
                }, $combo_unit_arr);
                $data['combo_unit'] = implode(',', $combo_unit_names);
            } else {
                $data['combo_unit'] = 'N/A';
            }
        } else {
            $data['combo_unit'] = 'N/A';
        }

        return $data;
    }
}
