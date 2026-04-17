<?php

namespace App\Http\Requests\Storefront\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchSuggestRequest extends FormRequest
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
            'q' => ['required_without:query', 'string', 'min:1', 'max:100'],
            'query' => ['required_without:q', 'string', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
