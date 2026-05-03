<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShippingRateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShippingRateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_map(fn (ShippingRateType $type): string => $type->value, ShippingRateType::cases()))],
            'config_json' => ['required', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
