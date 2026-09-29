<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id'      => ['nullable', 'integer', 'exists:categories,id'],
            'name_ar'        => ['required', 'string', 'max:150'],
            'name_en'        => ['nullable', 'string', 'max:150'],
            'slug'           => ['nullable', 'string', 'max:200', 'alpha_dash',
                Rule::unique('categories', 'slug')],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'image'          => ['nullable', 'file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required'     => 'اسم التصنيف بالعربية مطلوب.',
            'image.image'          => 'الملف المرفوع ليس صورة صالحة.',
            'slug.unique'          => 'الرابط مستخدم سابقاً.',
        ];
    }
}