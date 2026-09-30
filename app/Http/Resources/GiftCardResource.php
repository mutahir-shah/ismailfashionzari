<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = '';
        $clientType = '';

        if ($this->customer_id && $this->customer) {
            $client = $this->customer->name;
            $clientType = 'customer';
        } elseif ($this->user_id && $this->user) {
            $client = $this->user->name;
            $clientType = 'user';
        }

        $balance = (float) $this->amount - (float) $this->expense;
        $isExpired = $this->expired_date < date("Y-m-d");

        return [
            'id' => $this->id,
            'card_no' => $this->card_no,
            'amount' => (float) $this->amount,
            'expense' => (float) $this->expense,
            'balance' => $balance,
            'customer_id' => $this->customer_id,
            'customer' => $this->when($this->relationLoaded('customer') && $this->customer, function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone_number' => $this->customer->phone_number ?? null,
                ];
            }),
            'user_id' => $this->user_id,
            'user' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email ?? null,
                ];
            }),
            'client_name' => $client,
            'client_type' => $clientType,
            'expired_date' => $this->expired_date,
            'is_expired' => $isExpired,
            'expired_date_formatted' => $this->expired_date ? date('d-m-Y', strtotime($this->expired_date)) : null,
            'created_by' => $this->created_by,
            'creator' => $this->when($this->relationLoaded('creator') && $this->creator, function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
