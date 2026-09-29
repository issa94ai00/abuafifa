<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            && ($this->user()?->can('update', $category) ?? false);
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('id');

        return [
            'parent_id'      => ['nullable', 'integer', 'exists:categories,id',
                Rule::notIn([$categoryId])],
            'name_ar'        => ['required', 'string', 'max:150'],
            'name_en'        => ['nullable', 'string', 'max:150'],
            'slug'           => ['nullable', 'string', 'max:200', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image'          => ['nullable', 'file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreCategoryRequest)->messages();
    }
}