<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public int $orderId;

    public function mount(int $order): void
    {
        $this->orderId = $order;
    }

    public function render(): View
    {
        $order = Order::query()
            ->with(['lines.variant.product', 'payments', 'refunds', 'fulfillments.lines'])
            ->findOrFail($this->orderId);

        return view('livewire.admin.orders.show', ['order' => $order]);
    }
}
