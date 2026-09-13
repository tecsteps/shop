<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingZoneRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'countries_json' => 'nullable|array|min:1',
            'countries_json.*' => 'string|size:2',
            'regions_json' => 'nullable|array',
            'regions_json.*' => 'string|max:10',
        ];
    }
}
