<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'user' => $this->user_id,
            'both' => (bool) $this->both,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'tax_no' => $this->tax_no,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'points' => $this->points,
            'deposit' => $this->deposit,
            'expense' => $this->expense,
            'is_active' => (bool) $this->is_active,
            'customer_group' => new CustomerGroupResource($this->customerGroup),
            'discount_plan' => 'N/A',
            'total_due' => 0,
        ];
    }
}
