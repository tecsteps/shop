<?php

namespace App\Http\Controllers\Storefront\Account\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RequestCustomerPasswordResetRequest;
use App\Http\Requests\Storefront\Auth\ResetCustomerPasswordRequest;
use App\Models\Store;
use App\Services\CustomerPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CustomerPasswordResetController extends Controller
{
    public function send(RequestCustomerPasswordResetRequest $request, CustomerPasswordResetService $passwords): RedirectResponse
    {
        $validated = $request->validated();

        $passwords->sendResetLink($this->currentStore(), $validated['email']);

        return back()->with('status', __('If an account matches that email, a reset link has been sent.'));
    }

    public function update(ResetCustomerPasswordRequest $request, CustomerPasswordResetService $passwords): RedirectResponse
    {
        $validated = $request->validated();

        $reset = $passwords->reset(
            $this->currentStore(),
            $validated['email'],
            $validated['token'],
            $validated['password'],
        );

        if (! $reset) {
            throw ValidationException::withMessages([
                'email' => __('This password reset link is invalid or has expired.'),
            ]);
        }

        return redirect()
            ->route('account.login')
            ->with('status', __('Your password has been reset. You may log in with your new password.'));
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
