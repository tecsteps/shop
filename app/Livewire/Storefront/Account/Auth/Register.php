<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $marketing_opt_in = false;

    /**
     * Register a new customer.
     */
    public function register(): void
    {
        $this->ensureIsNotRateLimited();

        $store = app('current_store');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($store): void {
                    $exists = Customer::withoutGlobalScopes()
                        ->where('store_id', $store->id)
                        ->where('email', $value)
                        ->exists();

                    if ($exists) {
                        $fail(__('validation.unique', ['attribute' => $attribute]));
                    }
                },
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'marketing_opt_in' => ['boolean'],
        ]);

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'marketing_opt_in' => $validated['marketing_opt_in'],
        ]);

        RateLimiter::clear($this->throttleKey());

        Auth::guard('customer')->login($customer);

        Session::regenerate();

        $this->redirect(route('customer.dashboard'), navigate: true);
    }

    /**
     * Ensure the registration request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            RateLimiter::hit($this->throttleKey(), 60);

            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    /**
     * Get the throttle key for the request.
     */
    protected function throttleKey(): string
    {
        return 'customer-register:'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.auth.register')
            ->layout('storefront.layouts.app', [
                'title' => 'Create Account',
            ]);
    }
}
