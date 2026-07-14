<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CustomerService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly WebhookService $webhooks,
    ) {}

    /** @param array<string, mixed> $data */
    public function register(Store $store, array $data): Customer
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers')->where(
                fn ($query) => $query->where('store_id', $store->id)->whereNotNull('password_hash'),
            )],
            'password' => ['required', 'string', 'min:8'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ])->validate();

        $email = mb_strtolower($validated['email']);
        $customer = DB::transaction(function () use ($store, $validated, $email): Customer {
            $guest = Customer::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('email', $email)
                ->whereNull('password_hash')
                ->lockForUpdate()
                ->first();

            if ($guest !== null) {
                $guest->update([
                    'email' => 'guest-'.$guest->id.'-'.Str::lower(Str::random(12)).'@unclaimed.invalid',
                ]);
            }

            return Customer::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'name' => $validated['name'],
                'email' => $email,
                'password_hash' => Hash::make($validated['password']),
                'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
            ]);
        });

        $this->audit->log(
            'customer.registered',
            storeId: (int) $store->id,
            resourceType: 'customer',
            resourceId: (int) $customer->id,
            extra: ['customer_id' => (int) $customer->id],
        );
        $this->webhooks->dispatch($store, 'customer.created', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'name' => $customer->name,
        ]);

        return $customer;
    }
}
