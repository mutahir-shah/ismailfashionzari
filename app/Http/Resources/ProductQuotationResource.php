<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductQuotationResource extends JsonResource
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
            'name' => $this->product->name,
            'code' => $this->product->code,
            'qty' => $this->qty,
            'tax' => $this->tax,
            'tax_rate' => $this->tax_rate,
            'discount' => $this->discount,
            'total' => $this->total,
            'batch' => $this->batch ? $this->batch->batch_no : 'N/A'
        ];
    }
}
