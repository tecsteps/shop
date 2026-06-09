<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    /**
     * Handle a customer login attempt scoped to the current store.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attempted = Auth::guard('customer')->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('storefront.account.index'));
    }

    /**
     * Log the customer out of the current store session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.account.login');
    }
}
