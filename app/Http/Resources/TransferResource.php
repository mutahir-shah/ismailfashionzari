<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->is_sent == 1)
            $emailSent = 'Yes';
        else
            $emailSent = 'No';
            
            
        //Determina Transfer Status
        if($this->status == 1)
            $status = 'Completed';
        elseif($this->status == 2)
            $status = 'Pending';
        elseif($this->status == 3)
             $status = 'Sent';
    
        return [
            'id' => $this->id,
            'date' => date(config('date_format').' h:i:s a', strtotime($this->created_at)),
            'reference_no' => $this->reference_no,
            'from_warehouse' => new WarehouseResource($this->fromWarehouse),
            'to_warehouse' => new WarehouseResource($this->toWarehouse),
            'product_cost' => number_format($this->total_cost, config('decimal')),
            'product_tax' => number_format($this->total_tax, config('decimal')),
            'grand_total' => number_format($this->grand_total, config('decimal')),
            'status' => $status,
            'email_sent' => $emailSent,
            'products' => ProductTransferResource::collection($this->products)
        ];
    }
}
