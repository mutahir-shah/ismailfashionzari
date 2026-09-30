<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCustomerRequest extends BaseFormRequest
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
        $rules = [
            'phone_number' => [
                'required',
                'max:255',
                Rule::unique('customers')->ignore($this->route('customer'))->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],
            'customer_group_id' => 'required|max:255',
            'customer_name' => 'required',
        ];

        // If 'user' is present, apply user validation rules
        if ($this->user == true) {
            $rules['name'] = [
                'required',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('is_deleted', false);
                })
            ];

            $rules['password'] = ['required', 'min:6'];
        }

        return $rules;
    }

    /**
     * Perform additional validation after the main validation rules.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->both == true) {
                    // Validate company_name
                    if (!$this->filled('company_name')) {
                        $validator->errors()->add('company_name', 'The company name is required when creating both user and supplier.');
                    } elseif ($this->companyNameExists()) {
                        $validator->errors()->add('company_name', 'The company name must be unique.');
                    }

                    // Validate email
                    if (!$this->filled('email')) {
                        $validator->errors()->add('email', 'The email is required when creating both user and supplier.');
                    } elseif ($this->emailExists()) {
                        $validator->errors()->add('email', 'The email must be unique.');
                    }
                }
            }
        ];
    }

    private function companyNameExists(): bool
    {
        return \DB::table('suppliers')
            ->where('company_name', $this->input('company_name'))
            ->where('is_active', 1)
            ->exists();
    }

    private function emailExists(): bool
    {
        return \DB::table('suppliers')
            ->where('email', $this->input('email'))
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Prepare input before validation.
     */
    protected function prepareForValidation()
    {
        if ($this->user == true) {
            $this->merge([
                'phone' => $this->input('phone_number'),
                'role_id' => 5,
                'is_active' => true,
                'is_deleted' => false,
                'password' => bcrypt($this->input('password')),
            ]);
        }
    }

    /**
     * Handle validation failure and return JSON response.
     */
    // protected function failedValidation(Validator $validator)
    // {
    //     throw new HttpResponseException(response()->json([
    //         'errors' => $validator->errors(),
    //     ], 422));
    // }
}
