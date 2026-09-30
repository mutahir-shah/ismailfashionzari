<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
           'id' => $this->id,
           'name' => $this->name,
           'phone' => $this->phone,
           'email' => $this->email,
           'address' => $this->address,
           'number_of_products' => $this->products->where('pivot.qty', '>', 0)->count(),
           'stock_quantity' => $this->products
            ->where('pivot.qty', '>', 0)
            ->sum('pivot.qty'),
        ];
    }
}
