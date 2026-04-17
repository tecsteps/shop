<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Confirmation extends Component
{
    public int $checkoutId;

    public string $email = '';

    public string $paymentMethod = '';

    /** @var array<string, mixed> */
    public array $shippingAddress = [];

    /** @var array<string, mixed> */
    public array $totals = [];

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;

        $checkout = Checkout::query()
            ->withoutGlobalScopes()
            ->where('id', $checkoutId)
            ->with('cart.lines.variant.product')
            ->first();

        if (! $checkout) {
            abort(404);
        }

        if ($checkout->status !== CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.show', $checkoutId));

            return;
        }

        $this->email = $checkout->email ?? '';
        $this->paymentMethod = $checkout->payment_method ?? '';
        $this->shippingAddress = $checkout->shipping_address_json ?? [];
        $this->totals = $checkout->totals_json ?? [];

        $cart = $checkout->cart;
        if ($cart) {
            $this->lines = $cart->lines->map(function ($line) {
                return [
                    'product_title' => $line->variant?->product?->title,
                    'variant_title' => $line->variant?->title,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'line_total_amount' => $line->line_total_amount,
                ];
            })->toArray();
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.checkout.confirmation');
    }
}
