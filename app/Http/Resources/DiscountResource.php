<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
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
            'applicable_for' => $this->applicable_for,
            'product_list' => $this->product_list,
            'valid_from' => $this->valid_from,
            'valid_till' => $this->valid_till,
            'type' => $this->type,
            'value' => $this->value,
            'minimum_qty' => $this->minimum_qty,
            'maximum_qty' => $this->maximum_qty,
            'days' => $this->days,
            'is_active' => $this->is_active,
            'discount_plans' => $this->whenLoaded('discountPlans'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
