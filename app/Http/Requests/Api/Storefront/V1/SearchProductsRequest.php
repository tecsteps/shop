<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'filters' => ['nullable', 'array'],
            'filters.collection_id' => ['nullable', 'integer', 'min:1'],
            'filters.price_min' => ['nullable', 'integer', 'min:0'],
            'filters.price_max' => ['nullable', 'integer', 'min:0', 'gte:filters.price_min'],
            'filters.in_stock' => ['nullable', 'boolean'],
            'filters.tags' => ['nullable', 'array', 'max:20'],
            'filters.tags.*' => ['string', 'max:100'],
            'filters.vendor' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'best_selling'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('filters'))) {
            return;
        }

        $decoded = json_decode((string) $this->input('filters'), true);

        $this->merge([
            'filters' => json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                ? $decoded
                : '__invalid_filters__',
        ]);
    }
}
