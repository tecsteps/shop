<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartLineRequest extends FormRequest
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
        return [
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cart_version' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function expectedVersion(): ?int
    {
        $version = $this->validated('cart_version') ?? $this->validated('expected_version');

        return $version === null ? null : (int) $version;
    }
}
