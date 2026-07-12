<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:flat,weight,price,carrier'], 'config_json' => ['required', 'array'], 'is_active' => ['sometimes', 'boolean']];
    }
}
