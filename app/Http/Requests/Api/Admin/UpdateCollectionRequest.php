<?php

namespace App\Http\Requests\Api\Admin;

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
        $storeId = (int) $this->route('storeId');
        $productExists = ['integer', Rule::exists('products', 'id')->where('store_id', $storeId)];

        return [
            'title' => 'sometimes|required|string|max:255',
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => 'sometimes|nullable|string|max:65535',
            'type' => ['sometimes', Rule::in(['manual', 'automated'])],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => 'sometimes|nullable|array',
            'product_ids.*' => $productExists,
            'add_product_ids' => 'sometimes|array',
            'add_product_ids.*' => $productExists,
            'remove_product_ids' => 'sometimes|array',
            'remove_product_ids.*' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'handle.regex' => 'The handle may only contain lowercase letters, numbers and hyphens.',
            'product_ids.*.exists' => 'One or more products do not belong to this store.',
            'add_product_ids.*.exists' => 'One or more products do not belong to this store.',
        ];
    }
}
