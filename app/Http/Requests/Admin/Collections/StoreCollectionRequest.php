<?php

namespace App\Http\Requests\Admin\Collections;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['nullable', 'string'],
            'type' => ['nullable', 'string', Rule::in(['manual', 'automated'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'min:1'],
        ];
    }
}
