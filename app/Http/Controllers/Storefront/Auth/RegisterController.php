<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use App\Tenancy\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.register');
    }

    public function store(RegisterCustomerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $customer = Customer::query()->create([
            'store_id' => app(CurrentStore::class)->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => $validated['password'],
            'marketing_opt_in' => $validated['marketing_opt_in'] ?? false,
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        app(AuditLogger::class)->log('customer.registered', storeId: $customer->store_id, extra: [
            'customer_id' => $customer->getKey(),
        ]);

        return redirect()->route('customer.account');
    }
}
