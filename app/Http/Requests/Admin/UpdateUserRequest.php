<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('update', $target) ?? false);
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->getKey();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'locale' => ['nullable', Rule::in(['ar', 'en'])],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array', 'max:3'],
            'roles.*' => ['string', Rule::in(UserRole::values())],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated();

        unset($validated['is_admin'], $validated['id'], $validated['email_verified_at']);

        return $key === null ? $validated : data_get($validated, $key, $default);
    }

    public function messages(): array
    {
        return (new StoreUserRequest)->messages();
    }
}
