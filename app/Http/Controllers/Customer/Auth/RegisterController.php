<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegisterController extends StorefrontController
{
    public function show(Request $request)
    {
        return view('customer.auth.register', $this->storefrontViewData($request, [
            'title' => 'Create account',
            'metaDescription' => 'Create your customer account.',
        ]));
    }

    public function register(Request $request): RedirectResponse
    {
        $store = $this->currentStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('customers', 'email')->where(fn ($query) => $query->where('store_id', $store->id)),
            ],
            'password' => ['required', 'confirmed', 'min:8'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'password' => $validated['password'],
            'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
            'email_verified_at' => now(),
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('storefront.account.dashboard');
    }
}
