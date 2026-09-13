<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\GuestCartMerger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.login');
    }

    public function store(LoginRequest $request, GuestCartMerger $merger): RedirectResponse
    {
        if (! Auth::guard('customer')->attempt($request->validated())) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials']);
        }

        $request->session()->regenerate();

        $merger->mergeFromSession(Auth::guard('customer')->user());

        return redirect()->intended(route('customer.account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
