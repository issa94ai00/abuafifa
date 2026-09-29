<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Rules\PhoneNumber;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return match ($group) {
            'site' => [
                'name'        => ['nullable', 'string', 'max:150'],
                'title'       => ['nullable', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:500'],
                'currency'    => ['nullable', 'string', 'max:10', 'in:JOD,SYP,USD,EUR'],
                'email'       => ['nullable', 'email', 'max:190'],
                'logo'        => ['nullable', 'file', 'image', 'max:4096', new SafeImage(maxSizeKb: 4096)],
                'favicon'     => ['nullable', 'file', 'image', 'max:1024', new SafeImage(maxSizeKb: 1024)],
            ],
            'contact' => [
                'phone'         => ['nullable', 'string', 'max:30', new PhoneNumber],
                // The storefront renders this as a wa.me link, which only
                // resolves for a number that carries its country code.
                'whatsapp'      => ['nullable', 'string', 'max:30', new PhoneNumber(international: true)],
                'email'         => ['nullable', 'email', 'max:190'],
                'address_ar'    => ['nullable', 'string', 'max:500'],
                'address_en'    => ['nullable', 'string', 'max:500'],
                // Half a coordinate pair silently drops the map pin in the
                // wrong place, since the missing half falls back to a default.
                'map_lat'       => ['nullable', 'required_with:map_lng', 'numeric', 'between:-90,90'],
                'map_lng'       => ['nullable', 'required_with:map_lat', 'numeric', 'between:-180,180'],
                'working_hours' => ['nullable', 'string', 'max:255'],
            ],
            'seo' => [
                'default_title'       => ['nullable', 'string', 'max:180'],
                'default_description' => ['nullable', 'string', 'max:500'],
                'default_keywords'    => ['nullable', 'string', 'max:500'],
                'og_image'            => ['nullable', 'file', 'image', 'max:4096', new SafeImage(maxSizeKb: 4096)],
            ],
            'social' => [
                'links'            => ['nullable', 'array', 'max:20'],
                'links.*.platform' => ['required_with:links', 'string', 'max:60'],
                'links.*.url'      => ['required_with:links', 'url', 'max:500'],
                'links.*.icon'     => ['nullable', 'string', 'max:100'],
                'links.*.label_ar' => ['nullable', 'string', 'max:100'],
                'links.*.label_en' => ['nullable', 'string', 'max:100'],
                'links.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'links.*.is_active'  => ['nullable', 'boolean'],
            ],
            'homepage' => [
                'hero_title_ar'    => ['nullable', 'string', 'max:255'],
                'hero_subtitle_ar' => ['nullable', 'string', 'max:500'],
                'hero_image'       => ['nullable', 'file', 'image', 'max:6144', new SafeImage(maxSizeKb: 6144)],
                'show_featured'    => ['nullable', 'boolean'],
                'show_new_arrivals'=> ['nullable', 'boolean'],
                'show_offers'      => ['nullable', 'boolean'],
                'about_intro_ar'   => ['nullable', 'string', 'max:1000'],
                'about_intro_en'   => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };
    }

    /**
     * Collapse the spacing people paste in so two visually identical
     * numbers do not round-trip as different stored values.
     */
    protected function prepareForValidation(): void
    {
        $tidy = [];

        foreach (['phone', 'whatsapp'] as $key) {
            if (is_string($this->input($key))) {
                $tidy[$key] = trim(preg_replace('/\s+/u', ' ', $this->input($key)) ?? '');
            }
        }

        if ($tidy !== []) {
            $this->merge($tidy);
        }
    }

    public function messages(): array
    {
        return [
            'logo.image'               => 'شعار الموقع يجب أن يكون صورة.',
            'whatsapp.max'             => 'رقم واتساب طويل جداً.',
            'phone.max'                => 'رقم الهاتف طويل جداً.',
            'map_lat.between'          => 'خط العرض يجب أن يكون بين -90 و 90.',
            'map_lng.between'          => 'خط الطول يجب أن يكون بين -180 و 180.',
            'map_lat.required_with'    => 'أدخل خط العرض أيضاً، أو امسح الإحداثيَّين معاً.',
            'map_lng.required_with'    => 'أدخل خط الطول أيضاً، أو امسح الإحداثيَّين معاً.',
            'map_lat.numeric'          => 'خط العرض يجب أن يكون رقماً.',
            'map_lng.numeric'          => 'خط الطول يجب أن يكون رقماً.',
            'email.email'              => 'البريد الإلكتروني غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone'         => 'رقم الهاتف',
            'whatsapp'      => 'رقم واتساب',
            'email'         => 'البريد الإلكتروني',
            'address_ar'    => 'العنوان بالعربية',
            'address_en'    => 'العنوان بالإنجليزية',
            'map_lat'       => 'خط العرض',
            'map_lng'       => 'خط الطول',
            'working_hours' => 'ساعات العمل',
        ];
    }
}