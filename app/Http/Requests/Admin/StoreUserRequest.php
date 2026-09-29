<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Authorisation is a policy decision, not a `return true`.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'locale' => ['nullable', Rule::in(['ar', 'en'])],
            'is_active' => ['nullable', 'boolean'],

            // Role names are matched against the enum, so an arbitrary
            // string — including one that exists in the roles table but
            // is not a back-office role — is rejected.
            'roles' => ['nullable', 'array', 'max:3'],
            'roles.*' => ['string', Rule::in(UserRole::values())],
        ];
    }

    /**
     * Only the listed keys are ever read. `is_admin` is not among them:
     * staff membership is derived from the assigned roles in the
     * controller, never taken from the request.
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated();

        unset($validated['is_admin'], $validated['id'], $validated['email_verified_at']);

        return $key === null ? $validated : data_get($validated, $key, $default);
    }

    public function messages(): array
    {
        return [
            'password.min' => 'كلمة المرور يجب ألا تقل عن 12 حرفاً وتحتوي على أحرف وأرقام ورموز.',
            'email.unique' => 'البريد الإلكتروني مستخدم سابقاً.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'phone.unique' => 'رقم الهاتف مستخدم سابقاً.',
            'phone.regex' => 'رقم الهاتف يحتوي على رموز غير مسموحة.',
            'roles.*.in' => 'الدور المحدد غير معروف.',
        ];
    }
}
