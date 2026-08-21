<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,draft,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['nullable', 'array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['required', 'integer', 'between:1,3'],
            'options.*.values' => ['nullable', 'array'],
            'options.*.values.*.value' => ['required', 'string', 'max:255'],
            'options.*.values.*.position' => ['nullable', 'integer', 'min:1'],
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.title' => ['nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['nullable', 'string', 'size:3', 'uppercase'],
            'variants.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['nullable', 'boolean'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.position' => ['nullable', 'integer', 'min:1'],
            'variants.*.status' => ['nullable', 'in:active,archived'],
            'variants.*.option_values' => ['nullable', 'array'],
            'variants.*.option_values.*.option_name' => ['required', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required', 'string', 'max:255'],
            'variants.*.inventory.quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['nullable', 'in:deny,continue'],
            'collections' => ['nullable', 'array'],
            'collections.*' => [
                'integer',
                Rule::exists('collections', 'id')->where(fn ($query) => $query->where('store_id', app('current_store')->getKey())),
            ],
        ];
    }
}
