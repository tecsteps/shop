<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\Shop\CartService;
use App\Services\Shop\CheckoutService;
use App\Services\Shop\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(CartService $cartService, PricingService $pricing): View
    {
        $cart = $cartService->current();
        $totals = $pricing->cartTotals($cart);

        return view('storefront.cart', compact('cart', 'totals'));
    }

    public function add(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::query()->with('product.media', 'optionValues', 'inventoryItem')->findOrFail($validated['variant_id']);
        $cartService->add($variant, (int) $validated['quantity']);

        return redirect()->route('cart.show')->with('status', 'Product added to cart.');
    }

    public function update(Request $request, int $line, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $cartService->updateLine($line, (int) $validated['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function discount(Request $request, CartService $cartService): RedirectResponse
    {
        $validated = $request->validate(['discount_code' => ['required', 'string', 'max:64']]);

        try {
            $cartService->applyDiscount($validated['discount_code']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Discount applied.');
    }

    public function removeDiscount(CartService $cartService): RedirectResponse
    {
        $cartService->removeDiscount();

        return back()->with('status', 'Discount removed.');
    }

    public function checkout(CartService $cartService, CheckoutService $checkoutService): RedirectResponse
    {
        try {
            $checkout = $checkoutService->start($cartService->current());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('checkout.show', $checkout);
    }
}
