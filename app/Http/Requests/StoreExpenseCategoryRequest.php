<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'max:255','required',
                    Rule::unique('expense_categories','code')->ignore($this->route('expensecategory'))->where(function ($query) {
                       $query->where('is_active', 1);
                }),
            ],
            'name' => 'max:255|required'
        ];
    }
}
