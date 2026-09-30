<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HrmSettingRequest extends FormRequest
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
            'checkin' => ['required', 'date_format:h:ia'],
            'checkout' => ['required', 'date_format:h:ia', 'after:checkin'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'checkin.required' => 'Check-in time is required.',
            'checkin.date_format' => 'Check-in time must be in the format like 10:00am.',
            'checkout.required' => 'Check-out time is required.',
            'checkout.date_format' => 'Check-out time must be in the format like 6:00pm.',
            'checkout.after' => 'Check-out time must be after check-in time.',
        ];
    }
}
