<?php

namespace App\Http\Requests;

class UpdateCustomerAddressRequest extends StoreCustomerAddressRequest
{
    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules): array => ['sometimes', ...array_values(array_filter($rules, fn (string $rule): bool => $rule !== 'required'))])->all();
    }
}
