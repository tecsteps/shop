<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Illuminate\Support\Str;
use Livewire\Component;

class Confirmation extends Component
{
    public Order $order;

    public function mount(string $checkoutId): void
    {
        $this->order = Order::query()
            ->with(['lines.variant.product.media', 'customer', 'checkout', 'payments'])
            ->where('checkout_id', $checkoutId)
            ->first()
            ?? Order::query()
                ->with(['lines.variant.product.media', 'customer', 'checkout', 'payments'])
                ->where('id', $checkoutId)
                ->firstOrFail();

        $customerId = auth('customer')->id();
        $sessionCartIds = array_filter([
            session('cart_id'),
            session('cart_id_'.app('current_store')->getKey()),
        ]);

        abort_unless(
            ($customerId !== null && (int) $this->order->customer_id === (int) $customerId)
                || ($customerId === null && in_array($this->order->checkout?->cart_id, $sessionCartIds, true)),
            404,
        );
    }

    public function formatMoney(int|float $amount): string
    {
        return number_format((float) $amount / 100, 2, '.', ',').' '.($this->order->currency ?: 'EUR');
    }

    public function paymentLabel(): string
    {
        return match ((string) $this->order->payment_method) {
            'credit_card' => 'Credit card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank transfer',
            default => Str::headline((string) $this->order->payment_method),
        };
    }

    public function isBankTransfer(): bool
    {
        return $this->order->payment_method === 'bank_transfer';
    }

    public function canViewAccountOrder(): bool
    {
        return auth('customer')->check() && $this->order->customer_id !== null;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.confirmation')->layout('layouts.storefront');
    }
}
