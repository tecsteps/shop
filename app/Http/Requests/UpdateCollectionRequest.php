<?php

namespace App\Http\Requests;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Collection::class);
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
            'type' => ['sometimes', 'in:manual,automated'],
            'rules_json' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'in:active,draft,archived'],
            'product_ids' => ['sometimes', 'array'],
        ];
    }
}
