<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'handle' => ['sometimes', 'string', 'max:255'], 'description_html' => ['nullable', 'string'], 'vendor' => ['nullable', 'string', 'max:255'], 'product_type' => ['nullable', 'string', 'max:255'], 'tags' => ['sometimes', 'array'], 'status' => ['sometimes', 'in:draft,active,archived'], 'options' => ['sometimes', 'array', 'max:3']];
    }
}
