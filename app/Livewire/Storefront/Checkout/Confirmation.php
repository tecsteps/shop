<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\Order;
use Livewire\Component;

class Confirmation extends Component
{
    public Order $order;

    public function mount(Checkout $checkout): void
    {
        abort_unless($checkout->status === CheckoutStatus::Completed, 404);

        $this->order = $checkout->order()->with(['lines.variant.product.media', 'payments'])->firstOrFail();
    }

    public function render()
    {
        return view('livewire.storefront.checkout.confirmation')
            ->layout('layouts.storefront')
            ->title('Order Confirmation - '.app('current_store')->name);
    }
}
