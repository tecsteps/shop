<?php

namespace App\Http\Requests\Api\Storefront;

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
            'q' => 'required|string|min:1|max:200',
            'filters' => 'nullable',
            'sort' => ['nullable', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'best_selling'])],
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required.',
            'q.max' => 'The search query must not be longer than 200 characters.',
            'per_page.max' => 'The per page value must not exceed 50.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parsedFilters(): array
    {
        $filters = $this->input('filters');

        if ($filters === null || $filters === '') {
            return [];
        }

        if (is_array($filters)) {
            return $filters;
        }

        if (is_string($filters)) {
            $decoded = json_decode($filters, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['__invalid' => true];
    }
}
