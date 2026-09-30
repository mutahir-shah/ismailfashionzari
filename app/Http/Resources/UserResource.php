<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'phone' => $this->phone,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'role_id' => $this->role_id,
            'biller_id' => $this->biller_id,
            'warehouse_id' => $this->warehouse_id,
            'is_active' => $this->is_active
        ];
    }
}
