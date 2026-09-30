<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
         'id' => $this->product_id,
         'qty' => $this->qty,
         'tax' => $this->tax,
         'tax_rate' => $this->tax_rate,
         'discount' => $this->discount,
         'total' => $this->total,
        ];
    }
}
