<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /** @param array<string, mixed> $data */
    public function register(Store $store, array $data): Customer
    {
        $validator = validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers')->where('store_id', $store->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return Customer::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'marketing_opt_in' => $validated['marketing_opt_in'] ?? false,
        ]);
    }
}
