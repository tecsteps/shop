<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::forUser($this->user())->allows('manage-shipping');
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:flat,weight,price,carrier'], 'config_json' => ['required', 'array'], 'is_active' => ['sometimes', 'boolean']];
    }
}
