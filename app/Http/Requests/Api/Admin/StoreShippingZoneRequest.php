<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingZoneRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'countries_json' => 'required|array|min:1',
            'countries_json.*' => 'string|size:2',
            'regions_json' => 'nullable|array',
            'regions_json.*' => 'string|max:10',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'countries_json.required' => 'At least one country is required.',
            'countries_json.*.size' => 'Each country must be a valid ISO 3166-1 alpha-2 code.',
        ];
    }
}
