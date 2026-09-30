<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::info('Raw request data:', $this->all());

        return [
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt', 'max:10000'],
            'created_at' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'type' => ['nullable', 'string', 'in:expense,advance'],
        ];
    }
}
