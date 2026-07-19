<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    /**
     * Resolve the order by its number, scoped to the authenticated
     * customer so other customers' orders are a 404 (spec 04 §10.5).
     * The "#" prefix is optional in the URL.
     */
    public function mount(string $orderNumber): void
    {
        $number = ltrim($orderNumber, '#');

        $this->order = Auth::guard('customer')->user()
            ->orders()
            ->where(fn ($query) => $query
                ->where('order_number', $number)
                ->orWhere('order_number', '#'.$number))
            ->with(['lines', 'fulfillments', 'payments'])
            ->firstOrFail();
    }

    /**
     * Render the order detail page.
     */
    public function render(): View
    {
        return view('livewire.storefront.account.orders.show')
            ->layout('storefront.layouts.app')
            ->title('Order '.$this->order->order_number);
    }
}
