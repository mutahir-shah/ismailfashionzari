<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
            'icon'  => 'mimetypes:text/plain,image/png,image/jpeg,image/svg',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'is_sync_disable' => 'nullable|boolean',
            'woocommerce_category_id' => 'nullable|integer',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'featured' => 'nullable|boolean',
            'page_title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:1000',
        ];
    }
}
