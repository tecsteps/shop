<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'tags' => ['sometimes', 'nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'price_amount' => ['sometimes', 'integer', 'min:0'],
            'compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'requires_shipping' => ['sometimes', 'boolean'],
        ];
    }
}
