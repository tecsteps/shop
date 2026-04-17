<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use App\Models\Order;
use Livewire\Component;

class Confirmation extends Component
{
    public Checkout $checkout;

    public ?Order $order = null;

    public function mount(Checkout $checkout): void
    {
        $isOwner = false;

        if ($checkout->customer_id && auth('customer')->id() === $checkout->customer_id) {
            $isOwner = true;
        }

        if (session('completed_checkout_id') === $checkout->id) {
            $isOwner = true;
        }

        if (! $isOwner) {
            abort(403);
        }

        $this->checkout = $checkout;
        $this->checkout->load('cart.lines.variant.product');

        $this->order = Order::withoutGlobalScopes()
            ->where('store_id', $this->checkout->store_id)
            ->where('email', $this->checkout->email)
            ->where('customer_id', $this->checkout->customer_id)
            ->latest()
            ->first();
    }

    public function render(): mixed
    {
        $bankTransferDetails = null;

        if ($this->order && $this->order->payment_method->value === 'bank_transfer') {
            $payment = $this->order->payments()->first();
            if ($payment && $payment->raw_json_encrypted) {
                $rawData = is_string($payment->raw_json_encrypted)
                    ? json_decode($payment->raw_json_encrypted, true)
                    : $payment->raw_json_encrypted;
                $bankTransferDetails = $rawData;
            }

            if (! $bankTransferDetails) {
                $bankTransferDetails = [
                    'bank_name' => 'Mock Bank AG',
                    'iban' => 'DE89 3704 0044 0532 0130 00',
                    'bic' => 'COBADEFFXXX',
                ];
            }
        }

        return view('livewire.storefront.checkout.confirmation', [
            'checkout' => $this->checkout,
            'order' => $this->order,
            'totals' => $this->checkout->totals_json ?? [],
            'bankTransferDetails' => $bankTransferDetails,
        ])->layout('layouts::storefront');
    }
}
