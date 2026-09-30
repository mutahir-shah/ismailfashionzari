<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'image'=> 'images/supplier/'.$this->image,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'total_due' => number_format($this->purchases->sum('grand_total') - $this->returnPurchases->sum('grand_total') - $this->purchases->sum('paid_amount'), 2, '.', '')
        ];
    }
}
