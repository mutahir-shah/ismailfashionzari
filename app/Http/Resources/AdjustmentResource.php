<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'warehouse' => new WarehouseResource($this->warehouse),
            'note' => $this->note,
            'date' => $this->date,
            'is_active' => $this->is_active,
        ];
    }
}
