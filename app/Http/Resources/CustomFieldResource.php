<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldResource extends JsonResource
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
            'belongs_to' => $this->belongs_to,
            'type' => $this->type,
            'default_value' => $this->default_value,
            'option_value' => $this->option_value,
            'grid_value' => $this->grid_value,
            'is_table' => $this->is_table,
            'is_invoice' => $this->is_invoice,
            'is_required' => $this->is_required ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>',
            'is_table' => $this->is_table ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>',
            'is_admin' => $this->is_admin,
            'is_disable' => $this->is_disable,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
