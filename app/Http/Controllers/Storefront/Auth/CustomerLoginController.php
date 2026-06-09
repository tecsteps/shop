<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Enums\CartStatus;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    public function __construct(protected CartService $cartService) {}

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

        $this->mergeGuestCart($request);

        return redirect()->intended(route('storefront.account.index'));
    }

    /**
     * Log the customer out of the current store session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.account.login');
    }

    /**
     * Merge the session guest cart into the customer's cart on login
     * (spec 05 section 4.1). Without an existing customer cart the guest
     * cart is simply claimed by the customer.
     */
    protected function mergeGuestCart(Request $request): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $guestCartId = $request->session()->get(CartService::SESSION_KEY);

        if ($guestCartId === null) {
            return;
        }

        $guestCart = Cart::query()
            ->whereNull('customer_id')
            ->where('status', CartStatus::Active)
            ->find($guestCartId);

        if ($guestCart === null) {
            return;
        }

        $customerCart = Cart::query()
            ->where('customer_id', $customer->getKey())
            ->where('status', CartStatus::Active)
            ->latest('id')
            ->first();

        if ($customerCart === null) {
            $guestCart->update(['customer_id' => $customer->getKey()]);

            return;
        }

        $this->cartService->mergeOnLogin($guestCart, $customerCart);

        $request->session()->put(CartService::SESSION_KEY, $customerCart->getKey());
    }
}
