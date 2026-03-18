<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Order Confirmation')]
class Confirmation extends Component
{
    public ?int $orderId = null;

    public function mount(): void
    {
        $this->orderId = session('last_order_id');

        if (! $this->orderId) {
            $this->redirect(route('home'));
        }
    }

    public function render(): View
    {
        $order = $this->orderId
            ? Order::withoutGlobalScopes()->with(['lines', 'payments'])->find($this->orderId)
            : null;

        return view('livewire.storefront.checkout.confirmation', [
            'order' => $order,
        ])->layout('storefront.layouts.app', ['title' => 'Order Confirmation']);
    }
}
