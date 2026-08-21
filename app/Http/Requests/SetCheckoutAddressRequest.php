<?php

namespace App\Http\Requests;

use App\Models\Checkout;
use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $requiresShipping = $this->checkoutRequiresShipping();
        $useShippingAsBilling = $this->boolean('use_shipping_as_billing', true);
        $rules = [
            'use_shipping_as_billing' => ['sometimes', 'boolean'],
            ...$this->addressRules('shipping_address', $requiresShipping),
        ];

        if (! $useShippingAsBilling) {
            $rules = [...$rules, ...$this->addressRules('billing_address', true)];
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function addressRules(string $key, bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';
        $root = $required ? 'required' : 'nullable';

        return [
            $key => [$root, 'array'],
            "{$key}.first_name" => [$presence, 'string', 'max:255'],
            "{$key}.last_name" => [$presence, 'string', 'max:255'],
            "{$key}.address1" => [$presence, 'string', 'max:500'],
            "{$key}.address2" => ['sometimes', 'nullable', 'string', 'max:500'],
            "{$key}.company" => ['sometimes', 'nullable', 'string', 'max:255'],
            "{$key}.city" => [$presence, 'string', 'max:255'],
            "{$key}.province" => ['sometimes', 'nullable', 'string', 'max:255'],
            "{$key}.province_code" => ['sometimes', 'nullable', 'string', 'max:10'],
            "{$key}.country" => [$presence, 'string', 'max:255'],
            "{$key}.country_code" => [$presence, 'string', 'regex:/^[A-Z]{2}$/'],
            "{$key}.postal_code" => [$presence, 'string', 'max:20'],
            "{$key}.phone" => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    private function checkoutRequiresShipping(): bool
    {
        $checkoutId = $this->route('checkoutId');

        if (! is_numeric($checkoutId)) {
            return true;
        }

        $checkout = Checkout::query()->with('cart.lines.variant')->find((int) $checkoutId);

        return $checkout?->cart?->lines?->contains(fn ($line): bool => (bool) $line->variant?->requires_shipping) ?? true;
    }
}
