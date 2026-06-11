<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle an admin login attempt.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attempted = Auth::guard('web')->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'status' => 'active',
        ], $request->boolean('remember'));

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if (! $request->session()->has('current_store_id')) {
            $firstStoreId = $user->stores()->value('stores.id');

            if ($firstStoreId !== null) {
                $request->session()->put('current_store_id', $firstStoreId);
            }
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Log the admin out and invalidate the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
