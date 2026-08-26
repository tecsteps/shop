<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    public function create()
    {
        return view('storefront.account.auth.login');
    }

    public function store(Request $request, CartService $cartService)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $request->session()->regenerate();

        $this->mergeGuestCart($cartService);

        return redirect()->intended(route('account.dashboard'));
    }

    private function mergeGuestCart(CartService $cartService): void
    {
        $store = app('current_store');
        $customer = Auth::guard('customer')->user();
        $guestCartId = session('cart_id');

        if (! $guestCartId || ! $store) {
            return;
        }

        $guestCart = Cart::find($guestCartId);

        if (! $guestCart || $guestCart->customer_id) {
            return;
        }

        $customerCart = Cart::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if (! $customerCart) {
            $customerCart = $cartService->create($store, $customer);
        }

        $cartService->mergeOnLogin($guestCart, $customerCart);
    }
}
