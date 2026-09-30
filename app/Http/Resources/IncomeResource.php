<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
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
            'date' => date('d M, Y', strtotime($this->created_at)),
            'reference_no' => $this->reference_no,
            'warehouse' => $this->warehouse ? $this->warehouse->name : 'N/A',
            'income_category' => $this->incomeCategory ? $this->incomeCategory->name : 'N/A',
            'amount' => config('currency_position') == 'prefix'
                ? config('currency') . ' ' . number_format($this->amount, 2)
                : number_format($this->amount, 2) . ' ' . config('currency'),
            'note' => $this->note,
        ];
    }
}
