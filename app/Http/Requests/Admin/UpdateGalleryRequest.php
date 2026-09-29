<?php

namespace App\Http\Requests\Admin;

use App\Models\Gallery;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gallery = $this->route('gallery');

        return $gallery instanceof Gallery
            && ($this->user()?->can('update', $gallery) ?? false);
    }

    public function rules(): array
    {
        $galleryId = $this->route('gallery')?->id ?? $this->route('id');

        return [
            'title_ar'        => ['required', 'string', 'max:200'],
            'title_en'        => ['nullable', 'string', 'max:200'],
            'slug'            => ['nullable', 'string', 'max:240', 'alpha_dash',
                Rule::unique('galleries', 'slug')->ignore($galleryId)],
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
        return (new StoreGalleryRequest)->messages();
    }
}