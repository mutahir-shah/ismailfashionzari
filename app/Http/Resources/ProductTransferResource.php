<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTransferResource extends JsonResource
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
            'qty' => $this->qty,
            'unit' => $this->unit->unit_code,
            'tax' => $this->tax,
            'tax_rate' => $this->tax_rate,
            'total' => $this->total,
            'batch' => $this->batch ? $this->batch->batch_no : 'N/A'
        ];
    }
}
