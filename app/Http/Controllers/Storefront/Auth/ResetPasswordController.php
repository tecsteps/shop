<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPasswordResetToken;
use App\Tenancy\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('storefront.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $storeId = app(CurrentStore::class)->id();
        $email = strtolower($validated['email']);

        $record = CustomerPasswordResetToken::query()
            ->where('store_id', $storeId)
            ->where('email', $email)
            ->first();

        $valid = $record !== null
            && Hash::check($validated['token'], $record->token)
            && $record->created_at !== null
            && $record->created_at->diffInMinutes(now()) < 60;

        if (! $valid) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired.']);
        }

        $customer = Customer::query()->where('email', $email)->first();

        if ($customer === null) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired.']);
        }

        $customer->forceFill(['password_hash' => $validated['password']])->save();
        $record->delete();

        return redirect()->route('customer.login')->with('status', 'Your password has been reset. You can now log in.');
    }
}
