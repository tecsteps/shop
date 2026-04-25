<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Services\Shop\CheckoutService;
use App\Services\Shop\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Checkout $checkout, PricingService $pricing): View
    {
        $checkout->load('cart.lines.variant.product');
        $totals = $checkout->totals_json ?: $pricing->cartTotals($checkout->cart);

        return view('storefront.checkout.show', compact('checkout', 'totals'));
    }

    public function update(Request $request, Checkout $checkout, CheckoutService $checkoutService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['nullable', 'string'],
        ]);

        try {
            $checkout = $checkoutService->address($checkout, $validated['email'], [
                'name' => $validated['name'],
                'address1' => $validated['address1'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country_code' => strtoupper($validated['country_code']),
            ]);
            $checkout = $checkoutService->payment($checkout, $validated['payment_method']);
            $order = $checkoutService->complete($checkout, $validated);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('checkout.confirmation', $order->order_number);
    }

    public function confirmation(string $orderNumber): View
    {
        $order = \App\Models\Order::query()
            ->where('order_number', $orderNumber)
            ->with('lines', 'payments')
            ->firstOrFail();

        return view('storefront.checkout.confirmation', compact('order'));
    }
}
