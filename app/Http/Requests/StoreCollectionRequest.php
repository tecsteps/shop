<?php

namespace App\Http\Requests;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Collection::class);
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
            'type' => ['required', 'in:manual,automated'],
            'rules_json' => ['required_if:type,automated', 'nullable', 'array'],
            'status' => ['nullable', 'in:active,draft,archived'],
            'product_ids' => ['nullable', 'array'],
        ];
    }
}
