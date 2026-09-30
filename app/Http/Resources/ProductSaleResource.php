<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductSaleResource extends JsonResource
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
        
        // Determine stock worth
        if (config('currency_position') == 'prefix') {
            $stockWorth = config('currency') . ' ' . ($this->qty * $this->price) . ' / ' . config('currency') . ' ' . ($this->qty * $this->cost);
        } else {
            $stockWorth = ($this->qty * $this->price) . ' ' . config('currency') . ' / ' . ($this->qty * $this->cost) . ' ' . config('currency');
        }
        
        return [
            'id' => $this->id,
            'image' => $imageUrl,
            'name' => Str::limit($this->name, 30, '...'),
            'code' => $this->code,
            'brand' => new BrandResource($this->brand),
            'category' => new CategoryResource($this->category),
            'quantity' => $this->warehouses->sum('pivot.qty'),
            'unit' => new UnitResource($this->unit),
            'cost' => number_format($this->cost,2),
            'price' => number_format($this->price,2),
            'stock_worth' => $stockWorth,
            'tax' => new TaxResource($this->tax),
            'tax_method' => $this->tax_method == '1' ? "Exclusive" : "Inclusive",
            'sale_batch'=> $this->pivot->product_batch_id,
            'sale_qty'=> $this->pivot->qty,
            'sale_return_qty'=> $this->pivot->return_qty,
            'sale_net_unit_price'=> $this->pivot->net_unit_price,
            'sale_tax'=> $this->pivot->tax,
            'sale_tax_rate'=> $this->pivot->tax_rate,
            'sale_discount'=> $this->pivot->discount,
            'sale_total'=> $this->pivot->total,
            'sale_is_delivered'=> $this->pivot->is_delivered,
        ];
    }
}