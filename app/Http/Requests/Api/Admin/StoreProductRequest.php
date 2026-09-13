<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['draft', 'active'])],
            'tags' => 'nullable|array|max:50',
            'tags.*' => 'string|max:255',
            'options' => 'nullable|array|max:3',
            'options.*.name' => 'required|string|max:255',
            'options.*.position' => 'required|integer|min:1|max:3|distinct',
            'variants' => 'required|array|min:1|max:100',
            'variants.*.sku' => 'required|string|max:255',
            'variants.*.barcode' => 'nullable|string|max:255',
            'variants.*.price_amount' => 'required|integer|min:0',
            'variants.*.compare_at_amount' => 'nullable|integer|min:0',
            'variants.*.currency' => 'nullable|string|size:3',
            'variants.*.weight_g' => 'nullable|integer|min:0',
            'variants.*.requires_shipping' => 'nullable|boolean',
            'variants.*.is_default' => 'nullable|boolean',
            'variants.*.position' => 'nullable|integer|min:0',
            'variants.*.status' => ['nullable', Rule::in(['active', 'archived'])],
            'variants.*.option_values' => 'nullable|array',
            'variants.*.option_values.*.option_name' => 'required|string|max:255',
            'variants.*.option_values.*.value' => 'required|string|max:255',
            'variants.*.inventory' => 'nullable|array',
            'variants.*.inventory.quantity_on_hand' => 'nullable|integer|min:0',
            'variants.*.inventory.policy' => ['nullable', Rule::in(['deny', 'continue'])],
            'collections' => 'nullable|array',
            'collections.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $storeId)],
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
            'variants.required' => 'At least one variant is required.',
            'variants.*.sku.required' => 'The sku field is required.',
            'variants.*.price_amount.required' => 'The price field is required.',
            'variants.*.price_amount.min' => 'The price must be zero or greater.',
            'collections.*.exists' => 'One or more collections do not belong to this store.',
        ];
    }
}
