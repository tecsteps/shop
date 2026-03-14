<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use App\Services\AnalyticsService;
use Livewire\Component;

class Confirmation extends Component
{
    public Checkout $checkout;

    public function mount(int $checkoutId): void
    {
        $checkout = Checkout::with('cart.lines.variant.product')->find($checkoutId);

        if (! $checkout) {
            abort(404);
        }

        $this->checkout = $checkout;

        $store = app()->bound('current_store') ? app('current_store') : null;
        if ($store) {
            app(AnalyticsService::class)->track(
                $store,
                'checkout_completed',
                ['checkout_id' => $checkout->id],
                session()->getId(),
                auth('customer')->id()
            );
        }
    }

    public function render(): mixed
    {
        $cart = $this->checkout->cart;
        $lines = $cart->lines;
        $currency = $cart->currency;
        $totals = $this->checkout->totals_json;

        return view('livewire.storefront.checkout.confirmation', [
            'lines' => $lines,
            'currency' => $currency,
            'totals' => $totals,
        ])->layout('layouts.storefront', ['title' => 'Order Confirmation']);
    }
}
