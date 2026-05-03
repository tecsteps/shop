<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaxMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(array_map(fn (TaxMode $mode): string => $mode->value, TaxMode::cases()))],
            'provider' => ['required_if:mode,provider', 'nullable', Rule::in(['none', 'stripe_tax'])],
            'prices_include_tax' => ['required', 'boolean'],
            'config_json' => ['required', 'array'],
        ];
    }
}
