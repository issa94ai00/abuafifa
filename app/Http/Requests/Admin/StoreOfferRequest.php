<?php

namespace App\Http\Requests\Admin;

use App\Models\Offer;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Offer::class) ?? false;
    }

    public function rules(): array
    {
        return static::offerRules(Rule::unique('offers', 'slug'));
    }

    /**
     * Shared by the update request so the two forms can never drift apart.
     *
     * @return array<string, mixed>
     */
    public static function offerRules(mixed $slugUniqueRule): array
    {
        return [
            'title_ar'       => ['required', 'string', 'max:200'],
            'title_en'       => ['nullable', 'string', 'max:200'],
            'slug'           => ['nullable', 'string', 'max:240', 'alpha_dash', $slugUniqueRule],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'image'          => ['nullable', 'file', 'image', 'max:5120', new SafeImage(maxSizeKb: 5120)],
            'remove_image'   => ['nullable', 'boolean'],
            'discount_type'  => ['required', 'in:percentage,fixed,custom_price'],
            // A percentage above 100 would price the product below zero.
            'discount_value' => ['nullable', 'required_if:discount_type,percentage,fixed', 'numeric', 'min:0',
                Rule::when(fn () => request()->input('discount_type') === 'percentage', ['max:100'])],
            'price_override' => ['nullable', 'required_if:discount_type,custom_price', 'numeric', 'min:0'],
            'starts_at'      => ['nullable', 'date'],
            'ends_at'        => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'      => ['nullable', 'boolean'],
            'sync_products'  => ['nullable', 'boolean'],
            'products'       => ['nullable', 'array'],
            'products.*'     => ['integer', 'distinct', 'exists:products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title_ar.required'          => 'عنوان العرض بالعربية مطلوب.',
            'title_ar.max'               => 'عنوان العرض بالعربية يجب ألا يتجاوز 200 حرف.',
            'slug.alpha_dash'            => 'الرابط يجب أن يحتوي على حروف وأرقام وشرطات فقط.',
            'slug.unique'                => 'هذا الرابط مستخدم في عرض آخر.',
            'discount_type.required'     => 'نوع الخصم مطلوب.',
            'discount_type.in'           => 'نوع الخصم غير صالح.',
            'discount_value.required_if' => 'قيمة الخصم مطلوبة عند اختيار نسبة أو مبلغ.',
            'discount_value.max'         => 'نسبة الخصم يجب ألا تتجاوز 100٪.',
            'discount_value.min'         => 'قيمة الخصم يجب ألا تكون بالسالب.',
            'price_override.required_if' => 'السعر المخصص مطلوب عند اختيار «سعر مخصص».',
            'ends_at.after_or_equal'     => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له.',
            'image.image'                => 'الملف المرفوع يجب أن يكون صورة.',
            'image.max'                  => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            'products.*.exists'          => 'أحد المنتجات المختارة لم يعد موجوداً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title_ar'       => 'العنوان بالعربية',
            'title_en'       => 'العنوان بالإنجليزية',
            'slug'           => 'الرابط',
            'description_ar' => 'الوصف بالعربية',
            'description_en' => 'الوصف بالإنجليزية',
            'discount_value' => 'قيمة الخصم',
            'price_override' => 'السعر المخصص',
            'starts_at'      => 'تاريخ البدء',
            'ends_at'        => 'تاريخ الانتهاء',
            'sort_order'     => 'ترتيب العرض',
        ];
    }
}
