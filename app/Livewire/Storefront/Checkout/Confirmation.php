<?php

namespace App\Livewire\Storefront\Checkout;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Confirmation extends Component
{
    public ?int $orderId = null;

    public function mount(): void
    {
        $this->orderId = session('order_id');

        if (! $this->orderId) {
            $this->redirect(route('storefront.home'));
        }
    }

    public function render(): mixed
    {
        $order = $this->orderId
            ? \App\Models\Order::withoutGlobalScopes()->with('lines')->find($this->orderId)
            : null;

        return view('livewire.storefront.checkout.confirmation', [
            'order' => $order,
        ]);
    }
}
