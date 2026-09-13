<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class SuggestRequest extends FormRequest
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
            'q' => 'required|string|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:10',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'The search prefix is required.',
            'q.max' => 'The search prefix must not be longer than 100 characters.',
            'limit.max' => 'The limit must not exceed 10.',
        ];
    }
}
