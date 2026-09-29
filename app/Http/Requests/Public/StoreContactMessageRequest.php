<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * The contact form is public by design; abuse is handled by the
     * `throttle:contact` limiter on the route and the honeypot below.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],

            // Honeypot: a hidden field real visitors never fill in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * Only the message fields are returned. `is_read` and `ip_address`
     * are set by the controller, so a crafted body cannot mark its own
     * message read or forge the recorded IP.
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = collect(parent::validated())
            ->only(['name', 'phone', 'email', 'subject', 'message'])
            ->all();

        return $key === null ? $validated : data_get($validated, $key, $default);
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // A bot that tripped the honeypot gets the same generic error as
        // any other invalid submission — no hint about what gave it away.
        if ($validator->errors()->has('website')) {
            throw ValidationException::withMessages([
                'message' => 'تعذّر إرسال الرسالة، يرجى المحاولة لاحقاً.',
            ]);
        }

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الرجاء إدخال الاسم.',
            'phone.required' => 'الرجاء إدخال رقم الهاتف.',
            'phone.regex' => 'رقم الهاتف غير صالح.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'message.required' => 'الرجاء كتابة الرسالة.',
            'message.min' => 'الرسالة قصيرة جداً.',
        ];
    }
}
