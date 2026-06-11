<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\InteractsWithCart;
use App\Models\ShippingRate;
use App\Services\ShippingCalculator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Show extends Component
{
    use InteractsWithCart;

    public string $estimateCountry = '';

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Re-render with fresh cart data.
    }

    /**
     * The cheapest available shipping rate for the chosen estimate country.
     *
     * @return array{name: string, amount: int}|null
     */
    protected function shippingEstimate(): ?array
    {
        $cart = $this->currentCart();

        if ($this->estimateCountry === '' || $cart === null || ! $cart->requiresShipping()) {
            return null;
        }

        $calculator = app(ShippingCalculator::class);

        return $calculator
            ->getAvailableRates($this->currentStore(), ['country_code' => $this->estimateCountry])
            ->map(fn (ShippingRate $rate): array => [
                'name' => $rate->name,
                'amount' => $calculator->calculate($rate, $cart),
            ])
            ->sortBy('amount')
            ->first();
    }

    public function render(): View
    {
        $cart = $this->currentCart();
        $discount = $this->appliedDiscount($cart);
        $subtotal = $cart?->subtotalAmount() ?? 0;

        return view('livewire.storefront.cart.show', [
            'lines' => $this->cartLineData($cart),
            'currency' => $cart?->currency ?? $this->currentStore()->default_currency,
            'subtotalAmount' => $subtotal,
            'discount' => $discount,
            'estimatedTotal' => max(0, $subtotal - ($discount['amount'] ?? 0)),
            'shippingEstimate' => $this->shippingEstimate(),
            'requiresShipping' => $cart?->requiresShipping() ?? false,
        ])->title(__('Your Cart'));
    }
}
