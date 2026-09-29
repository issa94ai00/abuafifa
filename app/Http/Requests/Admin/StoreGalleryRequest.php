<?php

namespace App\Http\Requests\Admin;

use App\Models\Gallery;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Gallery::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title_ar'        => ['required', 'string', 'max:200'],
            'title_en'        => ['nullable', 'string', 'max:200'],
            'slug'            => ['nullable', 'string', 'max:240', 'alpha_dash',
                Rule::unique('galleries', 'slug')],
            'description_ar'  => ['nullable', 'string'],
            'description_en'  => ['nullable', 'string'],
            'cover_image'     => ['nullable', 'file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
            'remove_cover'    => ['nullable', 'boolean'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['nullable', 'boolean'],
            'images'          => ['nullable', 'array', 'max:60'],
            'images.*'        => ['file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
        ];
    }

    public function messages(): array
    {
        return ['title_ar.required' => 'عنوان المعرض بالعربية مطلوب.'];
    }
}