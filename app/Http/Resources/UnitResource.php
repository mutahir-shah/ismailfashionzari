<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "unit_code" => $this->unit_code,
            "unit_name" => $this->unit_name,
            "base_unit" => new UnitResource($this->baseUnit),
            "operator" => $this->operator,
            "operation_value" => $this->operation_value,
            "is_active" => $this->is_active,
        ];
    }
}
