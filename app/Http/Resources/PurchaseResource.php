<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->status == 1)
            $purchaseStatus = 'Recieved';
        elseif($this->status == 1)
            $purchaseStatus = 'Partial';
        elseif($this->status == 1)
            $purchaseStatus = 'Pending';
        else
            $purchaseStatus = 'Ordered';
            
        return [
            'id' => $this->id,
            'date' => $this->created_at_formatted,
            'reference_no' => $this->reference_no,
            'purchase_status' => $purchaseStatus,
            'grand_total' => number_format($this->grand_total, config('decimal')),
            'return_amount' => number_format($this->returns->sum('grand_total'),config('decimal')),
            'paid_amount' => $this->paid_amount,
            'due' => $this->paid_amount,
            'payment_status' => $this->payment_status == 1 ? 'Due' : 'Paid',
            'exchange_rate' => $this->exchange_rate,
            'supplier' => new SupplierResource($this->supplier),
            'warehouse' => new WarehouseResource($this->warehouse),
            'currency' => new CurrencyResource($this->currency),
            'products' => ProductResource::collection($this->products)
        ];
    }
}
