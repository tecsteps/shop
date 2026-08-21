<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'handle' => ['sometimes', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:active,draft,archived'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['required', 'integer', 'between:1,3'],
            'options.*.values' => ['nullable', 'array'],
            'options.*.values.*.value' => ['required', 'string', 'max:255'],
            'options.*.values.*.position' => ['nullable', 'integer', 'min:1'],
            'variants' => ['sometimes', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['sometimes', 'string', 'max:255'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['sometimes', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'nullable', 'string', 'size:3', 'uppercase'],
            'variants.*.weight_g' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.position' => ['sometimes', 'integer', 'min:1'],
            'variants.*.status' => ['sometimes', 'in:active,archived'],
            'variants.*.option_values' => ['sometimes', 'array'],
            'variants.*.option_values.*.option_name' => ['required', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required', 'string', 'max:255'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', 'in:deny,continue'],
            'remove_variant_ids' => ['sometimes', 'array'],
            'remove_variant_ids.*' => ['integer'],
            'collections' => ['sometimes', 'array'],
            'collections.*' => [
                'integer',
                Rule::exists('collections', 'id')->where(fn ($query) => $query->where('store_id', app('current_store')->getKey())),
            ],
        ];
    }
}
