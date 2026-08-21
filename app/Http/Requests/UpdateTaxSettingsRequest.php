<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTaxSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app()->bound('current_store') && Gate::allows('update', app('current_store'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['mode' => ['sometimes', 'string'], 'provider' => ['sometimes', 'nullable', 'string'], 'prices_include_tax' => ['sometimes', 'boolean'], 'default_rate_basis_points' => ['sometimes', 'integer', 'min:0', 'max:10000'], 'rates_json' => ['sometimes', 'array'], 'provider_config_json' => ['sometimes', 'array']];
    }
}
