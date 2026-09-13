<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'organization_id' => 'required|integer|exists:organizations,id',
            'name' => 'required|string|max:255',
            'handle' => 'required|string|max:63|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/|unique:stores,handle',
            'default_currency' => 'required|string|size:3',
            'default_locale' => 'required|string|max:10',
            'timezone' => 'required|string|timezone',
        ];
    }
}
