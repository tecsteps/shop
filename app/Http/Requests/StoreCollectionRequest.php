<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'handle' => ['sometimes', 'string', 'max:255'], 'description_html' => ['nullable', 'string'], 'type' => ['sometimes', 'in:manual,automated'], 'status' => ['sometimes', 'in:draft,active,archived'], 'product_ids' => ['sometimes', 'array'], 'product_ids.*' => ['integer']];
    }
}
