<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CartStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\DiscountValidationException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CartController extends StorefrontController
{
    public function __construct(
        \App\Support\CurrentStore $currentStore,
        private readonly CartService $cartService,
        private readonly DiscountService $discountService,
    ) {
        parent::__construct($currentStore);
    }

    public function show(Request $request)
    {
        $cart = $this->resolveCart($request, false);

        if ($cart) {
            $cart->load([
                'lines.variant.product',
                'lines.variant.inventoryItem',
                'lines.variant.optionValues.option',
            ]);
        }

        $discountCode = $request->session()->get($this->discountSessionKey($this->currentStore()));
        $appliedDiscount = $cart
            ? $this->discountService->findAppliedDiscount($cart, is_string($discountCode) ? $discountCode : null)
            : null;

        return view('storefront.cart.show', $this->storefrontViewData($request, [
            'cart' => $cart,
            'totals' => $cart ? $this->cartService->totals($cart) : [
                'subtotal' => 0,
                'discount' => 0,
                'total' => 0,
                'currency' => $this->currentStore()->default_currency,
                'line_count' => 0,
                'item_count' => 0,
            ],
            'discountCode' => is_string($discountCode) ? $discountCode : null,
            'appliedDiscount' => $appliedDiscount,
            'title' => 'Your Cart',
            'metaDescription' => 'Review and update your cart before checkout.',
        ]));
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $cart = $this->resolveCart($request, true);

        try {
            $updatedCart = $this->cartService->addLine(
                $cart,
                (int) $validated['variant_id'],
                (int) $validated['quantity'],
                $cart->cart_version,
            );

            $this->reapplyDiscount($request, $updatedCart);
        } catch (InsufficientInventoryException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'quantity' => "Only {$exception->available} item(s) are available for this variant.",
                ]);
        } catch (CartVersionMismatchException) {
            return back()->withErrors([
                'cart' => 'Your cart changed in another tab. Please try again.',
            ]);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'cart' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to add this item to the cart.',
            ]);
        }

        return redirect()
            ->route('storefront.cart.show')
            ->with('status', 'Item added to cart.');
    }

    public function update(Request $request, int $lineId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $cart = $this->resolveCart($request, false);

        if (! $cart) {
            return redirect()->route('storefront.cart.show');
        }

        try {
            $updatedCart = $this->cartService->updateLineQuantity(
                $cart,
                $lineId,
                (int) $validated['quantity'],
                $cart->cart_version,
            );

            $this->reapplyDiscount($request, $updatedCart);
        } catch (InsufficientInventoryException $exception) {
            return back()->withErrors([
                'quantity' => "Only {$exception->available} item(s) are available for this variant.",
            ]);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'cart' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to update this line.',
            ]);
        }

        return redirect()->route('storefront.cart.show')->with('status', 'Cart updated.');
    }

    public function remove(Request $request, int $lineId): RedirectResponse
    {
        $cart = $this->resolveCart($request, false);

        if (! $cart) {
            return redirect()->route('storefront.cart.show');
        }

        try {
            $updatedCart = $this->cartService->removeLine($cart, $lineId, $cart->cart_version);
            $this->reapplyDiscount($request, $updatedCart);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'cart' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to remove this line.',
            ]);
        }

        return redirect()->route('storefront.cart.show')->with('status', 'Item removed from cart.');
    }

    public function applyDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'discount_code' => ['required', 'string', 'max:100'],
        ]);

        $cart = $this->resolveCart($request, false);

        if (! $cart || ! $cart->lines()->exists()) {
            return back()->withErrors([
                'discount_code' => 'Your cart is empty.',
            ]);
        }

        try {
            $this->discountService->applyCodeToCart($cart, $validated['discount_code']);
            $request->session()->put($this->discountSessionKey($this->currentStore()), strtoupper(trim($validated['discount_code'])));
        } catch (DiscountValidationException $exception) {
            return back()->withErrors([
                'discount_code' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            return back()->withErrors([
                'discount_code' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to apply this code.',
            ]);
        }

        return redirect()->route('storefront.cart.show')->with('status', 'Discount code applied.');
    }

    public function removeDiscount(Request $request): RedirectResponse
    {
        $cart = $this->resolveCart($request, false);

        if ($cart) {
            $this->discountService->clearCartDiscount($cart);
        }

        $request->session()->forget($this->discountSessionKey($this->currentStore()));

        return redirect()->route('storefront.cart.show')->with('status', 'Discount removed.');
    }

    protected function resolveCart(Request $request, bool $create): ?Cart
    {
        $store = $this->currentStore();
        $customer = $this->customer();
        $sessionKey = $this->cartService->sessionKey($store);

        $sessionCartId = $request->session()->get($sessionKey);

        $sessionCart = is_numeric($sessionCartId)
            ? Cart::query()
                ->where('store_id', $store->id)
                ->whereKey((int) $sessionCartId)
                ->where('status', CartStatus::Active)
                ->first()
            : null;

        $customerCart = $customer
            ? Cart::query()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first()
            : null;

        if ($customer && $sessionCart && $customerCart && $sessionCart->id !== $customerCart->id) {
            foreach ($sessionCart->lines as $line) {
                $this->cartService->addLine($customerCart, $line->variant_id, $line->quantity);
            }

            $sessionCart->status = CartStatus::Abandoned;
            $sessionCart->save();
            $sessionCart = null;
        }

        $cart = $customerCart ?: $sessionCart;

        if (! $cart && $create) {
            $cart = $this->cartService->create($store, $customer?->id, $store->default_currency);
        }

        if ($cart && $customer && ! $cart->customer_id) {
            $cart->customer_id = $customer->id;
            $cart->save();
        }

        if ($cart) {
            $request->session()->put($sessionKey, $cart->id);
        }

        return $cart;
    }

    protected function reapplyDiscount(Request $request, Cart $cart): void
    {
        $discountCode = $request->session()->get($this->discountSessionKey($this->currentStore()));

        if (! is_string($discountCode) || trim($discountCode) === '') {
            return;
        }

        try {
            $this->discountService->applyCodeToCart($cart, $discountCode);
        } catch (Throwable) {
            $this->discountService->clearCartDiscount($cart);
            $request->session()->forget($this->discountSessionKey($this->currentStore()));
        }
    }
}
