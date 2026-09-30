<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payingMethod = '';
        switch ($this->paying_method) {
            case 0:
                $payingMethod = 'Cash';
                break;
            case 1:
                $payingMethod = 'Cheque';
                break;
            case 2:
                $payingMethod = 'Credit Card';
                break;
        }

        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'employee_id' => $this->employee_id,
            'employee' => $this->when($this->relationLoaded('employee'), function () {
                return [
                    'id' => $this->employee->id,
                    'name' => $this->employee->name,
                    'email' => $this->employee->email ?? null,
                ];
            }),
            'account_id' => $this->account_id,
            'account' => $this->when($this->relationLoaded('account'), function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                    'account_no' => $this->account->account_no,
                ];
            }),
            'user_id' => $this->user_id,
            'amount' => (float) $this->amount,
            'paying_method' => $this->paying_method,
            'paying_method_label' => $payingMethod,
            'note' => $this->note,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
