<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPasswordResetToken;
use App\Notifications\CustomerResetPasswordNotification;
use App\Tenancy\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $store = app(CurrentStore::class)->get();
        $email = strtolower($validated['email']);

        $existing = CustomerPasswordResetToken::query()
            ->where('store_id', $store->getKey())
            ->where('email', $email)
            ->first();

        $throttled = $existing !== null
            && $existing->created_at !== null
            && $existing->created_at->diffInSeconds(now()) < 60;

        $customer = Customer::query()
            ->where('email', $email)
            ->first();

        if (! $throttled && $customer !== null) {
            $token = Str::random(64);

            CustomerPasswordResetToken::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $customer->notify(new CustomerResetPasswordNotification($token, $email, $store->handle));
        }

        return back()->with('status', 'If that email exists, we sent a reset link.');
    }
}
