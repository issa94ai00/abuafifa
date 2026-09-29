<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Media::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'collection' => ['required', 'string', 'max:60', MediaCollection::validationRule()],
            'files'      => ['required', 'array', 'min:1', 'max:25'],
            'files.*'    => ['file', 'image', 'max:8192', new SafeImage(maxSizeKb: 8192)],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'يجب اختيار ملف واحد على الأقل.',
            'files.*.image'  => 'جميع الملفات المرفوعة يجب أن تكون صوراً.',
        ];
    }
}