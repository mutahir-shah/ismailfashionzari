<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'date' => date("d-m-Y", strtotime($this->created_at)),
            'reference_no' => $this->reference_no,
            'warehouse' => $this->warehouse->name ?? 'N/A',
            'expense_category' => $this->expenseCategory->name ?? 'N/A',
            'amount' => number_format($this->amount, 2),
            'note' => substr($this->note ?? '', 0, 50) . (strlen($this->note ?? '') > 50 ? '...' : ''),
        ];
    }
}
