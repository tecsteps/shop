<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Storefront\StorefrontController;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends StorefrontController
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('account.dashboard');
        }

        return view('storefront.account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $remember = (bool) ($validated['remember'] ?? false);

        if (! Auth::guard('customer')->attempt([
            'email' => $email,
            'password' => (string) $validated['password'],
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('account.dashboard');
        }

        return view('storefront.account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $storeId = $this->currentStoreId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where(fn ($query) => $query->where('store_id', $storeId)),
            ],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'max:255'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::query()->create([
            'store_id' => $storeId,
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => (string) $validated['password'],
            'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard');
    }

    public function showForgotPassword(): View
    {
        return view('storefront.account.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        Password::broker('customers')->sendResetLink([
            'email' => strtolower(trim((string) $validated['email'])),
        ]);

        return back()->with('status', __('If that email exists, we have sent a password reset link.'));
    }

    public function showResetPassword(string $token): View
    {
        return view('storefront.account.reset-password', [
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'max:255'],
        ]);

        $status = Password::broker('customers')->reset(
            [
                'email' => strtolower(trim((string) $validated['email'])),
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $validated['token'],
            ],
            function (Customer $customer, string $password): void {
                $customer->forceFill([
                    'password' => $password,
                ])->save();

                event(new PasswordReset($customer));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('account.login')->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.login');
    }
}
