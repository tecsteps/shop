<?php

namespace App\Http\Requests\Storefront\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
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
            'q' => ['required_without:query', 'string', 'min:1', 'max:200'],
            'query' => ['required_without:q', 'string', 'min:1', 'max:200'],
            'filters' => ['nullable'],
            'sort' => ['nullable', 'string', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'best_selling'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
