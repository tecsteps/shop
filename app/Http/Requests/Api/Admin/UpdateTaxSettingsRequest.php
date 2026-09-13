<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxSettingsRequest extends FormRequest
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
            'mode' => ['required', Rule::in(['manual', 'provider'])],
            'provider' => ['required_if:mode,provider', 'nullable', Rule::in(['stripe_tax', 'none'])],
            'prices_include_tax' => 'required|boolean',
            'config_json' => 'required|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.required' => 'The mode field is required.',
            'prices_include_tax.required' => 'The prices include tax field is required.',
            'config_json.required' => 'The config field is required.',
        ];
    }
}
