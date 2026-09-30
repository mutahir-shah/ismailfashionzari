<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => date(config('date_format'), strtotime($this->created_at->toDateString())),
            'reference_no' => $this->reference_no,
            'sale_reference' => $this->sale ? $this->sale->reference_no : 'N/A',
            'warehouse' => new WarehouseResource($this->warehouse),
            'biller' => new BillerResource($this->biller),
            'customer' => new CustomerResource($this->customer),
            'grand_total' => number_format($this->grand_total, config('decimal')),
            'products' => ProductReturnResource::collection($this->products)
        ];
    }
}
