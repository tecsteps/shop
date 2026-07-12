<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CustomerService
{
    /** @param array<string, mixed> $data */
    public function register(Store $store, array $data): Customer
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers')->where('store_id', $store->id)],
            'password' => ['required', 'string', 'min:8'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ])->validate();

        return Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password_hash' => Hash::make($validated['password']),
            'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
        ]);
    }
}
