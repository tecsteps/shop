<?php

namespace App\Http\Requests\Api\Admin;

use App\Services\RolePermissionMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTokenRequest extends FormRequest
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
            'abilities' => 'sometimes|array',
            'abilities.*' => ['string', Rule::in(RolePermissionMap::allAbilities())],
            'expires_at' => 'sometimes|nullable|date|after:now',
        ];
    }
}
