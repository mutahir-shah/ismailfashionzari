<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegistrationRequest extends FormRequest
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
           'success' => 'false',
           'name' => 'required|string|max:255|unique:users',
            'email' => [
                'email',
                'max:255',
                    Rule::unique('users')->where(function ($query) {
                        $query->where('is_deleted', false);
                }),
            ],
            'password' => 'required|string|confirmed',
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false, // Add success false in response
            'message' => 'You have done one or more mistakes in the form.', // Show first error message
            'errors' => $validator->errors(), // Show all validation errors
        ], 422));
    }
}
