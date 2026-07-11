<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Confirmation extends Component
{
    public Order $order;

    public function mount(int $checkoutId): void
    {
        $checkout = Checkout::query()->whereKey($checkoutId)->where('status', CheckoutStatus::Completed)->firstOrFail();
        abort_unless($checkout->store_id === app('current_store')->id, 404);
        $this->order = $checkout->order()->with(['lines', 'payments'])->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation')
            ->layout('layouts.storefront', ['title' => 'Order '.$this->order->order_number]);
    }
}
