<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerRegisterController extends Controller
{
    /**
     * Register a new customer for the current store and log them in.
     */
    public function store(Request $request): RedirectResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('store_id', $store->getKey()),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::query()->create([
            'store_id' => $store->getKey(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => $validated['password'],
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
        ]);

        Auth::guard('customer')->login($customer);

        $request->session()->regenerate();

        return redirect()->route('storefront.account.index');
    }
}
