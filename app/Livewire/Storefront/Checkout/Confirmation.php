<?php

namespace App\Livewire\Storefront\Checkout;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Confirmation extends Component
{
    use EnsuresStore;

    public Order $order;

    public function mount(string $order_number): void
    {
        $this->ensureCurrentStore();

        $this->order = Order::query()
            ->where('order_number', '#'.$order_number)
            ->with('lines')
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation', [
            'order' => $this->order,
        ]);
    }
}
