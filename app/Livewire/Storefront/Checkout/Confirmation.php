<?php

namespace App\Livewire\Storefront\Checkout;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Confirmation extends StorefrontComponent
{
    public Checkout $checkout;

    public Order $order;

    public function mount(int|string|Checkout $checkoutId): void
    {
        $this->checkout = ($checkoutId instanceof Checkout
            ? Checkout::query()->whereKey($checkoutId->getKey())
            : Checkout::query()->whereKey($checkoutId))
            ->where('store_id', $this->currentStore()->getKey())
            ->with('cart.lines.variant.product.media')
            ->firstOrFail();

        abort_unless($this->enumValue($this->checkout->status) === 'completed', 404);
        $customerId = Auth::guard('customer')->id();
        abort_unless(
            session('checkout_access.'.$this->checkout->getKey())
            || ($customerId && (int) $customerId === (int) $this->checkout->customer_id),
            403,
        );

        $this->order = Order::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->when(session('last_order_id'), fn ($query, $id) => $query->whereKey($id))
            ->when(! session('last_order_id'), fn ($query) => $query->where('email', $this->checkout->email)->latest('placed_at'))
            ->with(['lines.product.media', 'payments', 'fulfillments'])
            ->firstOrFail();
    }

    public function render(): View
    {
        return $this->storefront(
            view('storefront.checkout.confirmation'),
            'Order '.$this->order->order_number.' confirmed - '.$this->currentStore()->name,
            'Thank you for your order.',
        );
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
