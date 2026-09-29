<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id'       => ['required', 'integer', 'exists:categories,id'],
            'name_ar'           => ['required', 'string', 'max:200'],
            'name_en'           => ['nullable', 'string', 'max:200'],
            'slug'              => ['nullable', 'string', 'max:240', 'alpha_dash'],
            'sku'               => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'short_description_ar' => ['nullable', 'string', 'max:500'],
            'short_description_en' => ['nullable', 'string', 'max:500'],
            'description_ar'    => ['nullable', 'string'],
            'description_en'    => ['nullable', 'string'],
            'price'             => ['required', 'numeric', 'min:0'],
            'sale_price'        => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'currency'          => ['nullable', 'string', 'max:10'],
            'dimensions_ar'     => ['nullable', 'string', 'max:255'],
            'dimensions_en'     => ['nullable', 'string', 'max:255'],
            'material_ar'       => ['nullable', 'string', 'max:150'],
            'material_en'       => ['nullable', 'string', 'max:150'],
            'color_ar'          => ['nullable', 'string', 'max:150'],
            'color_en'          => ['nullable', 'string', 'max:150'],
            'availability_status' => ['required', 'in:available,sold_out,on_order'],
            'is_featured'       => ['nullable', 'boolean'],
            'is_new'            => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
            'cover_image'       => ['nullable', 'file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
            'gallery_images'    => ['nullable', 'array', 'max:20'],
            'gallery_images.*'  => ['file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required'        => 'الاسم بالعربية مطلوب.',
            'price.required'          => 'السعر مطلوب.',
            'category_id.required'    => 'يجب اختيار التصنيف.',
            'sku.unique'              => 'رمز المنتج (SKU) مستخدم سابقاً.',
            'sale_price.lt'           => 'سعر التخفيض يجب أن يكون أقل من السعر الأساسي.',
        ];
    }
}