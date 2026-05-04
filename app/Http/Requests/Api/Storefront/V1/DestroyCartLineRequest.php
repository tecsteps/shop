<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCartLineRequest extends FormRequest
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
            'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
            'expected_version' => ['required_without:cart_version', 'integer', 'min:1'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) ($this->validated('cart_version') ?? $this->validated('expected_version'));
    }
}
