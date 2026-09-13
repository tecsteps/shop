<?php

namespace App\Http\Requests\Api\Admin;

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
        $storeId = (int) $this->route('storeId');

        return [
            'title' => 'sometimes|required|string|max:255',
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => 'sometimes|nullable|string|max:65535',
            'vendor' => 'sometimes|nullable|string|max:255',
            'product_type' => 'sometimes|nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'tags' => 'sometimes|nullable|array|max:50',
            'tags.*' => 'string|max:255',
            'options' => 'sometimes|nullable|array|max:3',
            'options.*.name' => 'required|string|max:255',
            'options.*.position' => 'required|integer|min:1|max:3|distinct',
            'variants' => 'sometimes|array|min:1|max:100',
            'variants.*.id' => 'sometimes|integer',
            'variants.*.sku' => 'sometimes|nullable|string|max:255',
            'variants.*.barcode' => 'sometimes|nullable|string|max:255',
            'variants.*.price_amount' => 'sometimes|integer|min:0',
            'variants.*.compare_at_amount' => 'sometimes|nullable|integer|min:0',
            'variants.*.currency' => 'sometimes|nullable|string|size:3',
            'variants.*.weight_g' => 'sometimes|nullable|integer|min:0',
            'variants.*.requires_shipping' => 'sometimes|boolean',
            'variants.*.is_default' => 'sometimes|boolean',
            'variants.*.position' => 'sometimes|integer|min:0',
            'variants.*.status' => ['sometimes', Rule::in(['active', 'archived'])],
            'variants.*.option_values' => 'sometimes|nullable|array',
            'variants.*.option_values.*.option_name' => 'required|string|max:255',
            'variants.*.option_values.*.value' => 'required|string|max:255',
            'variants.*.inventory' => 'sometimes|nullable|array',
            'variants.*.inventory.quantity_on_hand' => 'sometimes|nullable|integer|min:0',
            'variants.*.inventory.policy' => ['sometimes', 'nullable', Rule::in(['deny', 'continue'])],
            'collections' => 'sometimes|nullable|array',
            'collections.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $storeId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'handle.regex' => 'The handle may only contain lowercase letters, numbers and hyphens.',
            'collections.*.exists' => 'One or more collections do not belong to this store.',
        ];
    }
}
