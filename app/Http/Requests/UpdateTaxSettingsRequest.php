<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['mode' => ['required', 'in:manual,provider'], 'provider' => ['required', 'in:none,stripe_tax'], 'prices_include_tax' => ['required', 'boolean'], 'config_json' => ['required', 'array']];
    }
}
