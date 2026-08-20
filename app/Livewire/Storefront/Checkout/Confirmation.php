<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Confirmation extends Component
{
    public Order $order;

    public function mount(string $checkoutId): void
    {
        $this->order = Order::query()->with(['lines', 'customer', 'checkout'])->where('checkout_id', $checkoutId)->first()
            ?? Order::query()->with(['lines', 'customer', 'checkout'])->where('id', $checkoutId)->firstOrFail();

        $customerId = Auth::guard('customer')->id();
        $sessionCartIds = array_filter([session('cart_id'), session('cart_id_'.app('current_store')->getKey())]);
        abort_unless(($customerId !== null && (int) $this->order->customer_id === (int) $customerId)
            || ($customerId === null && in_array($this->order->checkout?->cart_id, $sessionCartIds, true)), 404);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.confirmation')->layout('layouts.storefront');
    }
}
