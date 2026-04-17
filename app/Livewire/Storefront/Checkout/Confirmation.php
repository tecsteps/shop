<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Confirmation extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $order = Order::withoutGlobalScopes()
            ->with(['lines', 'payments'])
            ->where('store_id', $store->id)
            ->where(function ($query) use ($orderNumber): void {
                $query->where('order_number', $orderNumber)
                    ->orWhere('order_number', '#'.$orderNumber);
            })
            ->first();

        if (! $order) {
            abort(404);
        }

        $this->order = $order;
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation');
    }
}
