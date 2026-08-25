<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerRegisterController extends Controller
{
    public function create()
    {
        return view('storefront.account.auth.register');
    }

    public function store(Request $request, CustomerService $customerService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:8', 'confirmed'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ]);

        $store = app('current_store');

        if (Customer::where('store_id', $store->id)->where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already registered.',
            ]);
        }

        $customerService->register($store, $validated);

        return redirect()->route('account.dashboard');
    }
}
