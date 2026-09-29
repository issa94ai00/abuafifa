<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleFavoriteRequest extends FormRequest
{
    /**
     * Favourites belong to the signed-in user. The route already applies
     * `auth`; this makes the requirement explicit at the request level
     * too, so the rule survives a route change.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Restricted to products that are actually published, so the
            // endpoint cannot be used to probe for unpublished ids.
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->filled('product_id') && $this->route('product')) {
            $this->merge(['product_id' => $this->route('product')->id]);
        }
    }
}
