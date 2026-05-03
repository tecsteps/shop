<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use Illuminate\View\View;
use Livewire\Component;

class Confirmation extends Component
{
    public int $checkoutId;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation', [
            'checkout' => Checkout::withoutGlobalScopes()
                ->with('cart.lines.variant.product', 'order.lines', 'order.payments')
                ->where('store_id', app('current_store')->id)
                ->whereKey($this->checkoutId)
                ->firstOrFail(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Confirmation',
        ]);
    }
}
