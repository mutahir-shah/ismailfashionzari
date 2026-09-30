<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if($this->supplier_id) {
            $supplier = $this->supplier;
        }
        else {
            $supplier = 'N/A';
        }
    
        if($this->quotation_status == 1) {
            $status = 'Pending';
        }
        else{
            $status = 'Sent';
        }
        
        return [
            'id' => $this->id,
            'date' => date(config('date_format'), strtotime($this->created_at->toDateString())),
            'reference_no' => $this->reference_no,
            'warehouse' => new WarehouseMinimalResource($this->warehouse),
            'biller' => new BillerResource($this->biller),
            'customer' => new CustomerResource($this->customer),
            'supplier' => $supplier,
            'status' => $status,
            'grand_total' => number_format($this->grand_total, config('decimal')),
            'products' => ProductQuotationResource::collection($this->products)
        ];
    }
}
