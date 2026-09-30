<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeCategoryRequest extends FormRequest
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
         return [
            'code' => [
                'max:255','required',
                    Rule::unique('income_categories','code')->ignore($this->route('incomecategory'))->where(function ($query) {
                       $query->where('is_active', 1);
                }),
            ],
            'name' => 'max:255|required'
        ];
    }
}
