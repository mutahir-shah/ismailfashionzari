<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'purchasecode' => 'required',
            'db_host' => 'required|string',
            'db_name' => ['required', 'regex:/^\S*$/'],
            'db_username' => ['required', 'regex:/^\S*$/'],
            'db_password' => ['required', 'regex:/^[^\s"\r\n]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'db_name.regex' => "The :attribute must not contain any whitespace",
            'db_username.regex' => "The :attribute must not contain any whitespace",
            'db_password.regex' => "The :attribute must not contain spaces, quotes, or line breaks",
        ];
    }
}
