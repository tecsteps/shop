<?php

namespace App\Http\Requests\Api\Admin;

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
        $storeId = (int) $this->route('storeId');

        return [
            'title' => 'required|string|max:255',
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => 'nullable|string|max:65535',
            'type' => ['required', Rule::in(['manual', 'automated'])],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => 'nullable|array',
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $storeId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'handle.regex' => 'The handle may only contain lowercase letters, numbers and hyphens.',
            'product_ids.*.exists' => 'One or more products do not belong to this store.',
        ];
    }
}
