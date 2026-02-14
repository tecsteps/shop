<?php

namespace App\Http\Requests\Admin\Collections;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
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
            'type' => ['sometimes', 'string', Rule::in(['manual', 'automated'])],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'min:1'],
            'add_product_ids' => ['sometimes', 'array'],
            'add_product_ids.*' => ['integer', 'min:1'],
            'remove_product_ids' => ['sometimes', 'array'],
            'remove_product_ids.*' => ['integer', 'min:1'],
        ];
    }
}
