<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneyTransferResource extends JsonResource
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
            'date' => date(config('date_format').' h:i:s a', strtotime($this->created_at)),
            'reference_no' => $this->reference_no,
            'from_account_id' => new AccountResource($this->fromAccount),
            'to_account_id' => new AccountResource($this->toAccount),
            'amount' => $this->amount
        ];
    }
}
