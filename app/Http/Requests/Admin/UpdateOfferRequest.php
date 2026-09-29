<?php

namespace App\Http\Requests\Admin;

use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $offer = $this->route('offer');

        return $offer instanceof Offer
            && ($this->user()?->can('update', $offer) ?? false);
    }

    public function rules(): array
    {
        $offerId = $this->route('offer')?->id ?? $this->route('id');

        return StoreOfferRequest::offerRules(
            Rule::unique('offers', 'slug')->ignore($offerId)
        );
    }

    public function messages(): array
    {
        return (new StoreOfferRequest)->messages();
    }

    public function attributes(): array
    {
        return (new StoreOfferRequest)->attributes();
    }
}
