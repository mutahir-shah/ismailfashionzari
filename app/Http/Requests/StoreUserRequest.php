<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
        $userId = $this->route('user'); // for edit routes (ignore current user)
    
        $rules = [
            'name' => [
                'max:255',
                Rule::unique('users')->ignore($userId)->where(function ($query) {
                    return $query->where('is_deleted', false);
                }),
            ],
            'email' => [
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)->where(function ($query) {
                    return $query->where('is_deleted', false);
                }),
            ],
            'role_id' => ['required', 'integer'],
        ];

        if ($this->input('role_id') == 5) {
            $rules['phone_number'] = [
                'required',
                'max:255',
                Rule::unique('customers')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ];
            $rules['customer_name'] = 'required|string|max:255';
        }
    
        return $rules;
    }
}
