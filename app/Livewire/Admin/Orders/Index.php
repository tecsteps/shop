<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render(): View
    {
        $orders = Order::query()
            ->orderByDesc('placed_at')
            ->limit(50)
            ->get();

        return view('livewire.admin.orders.index', ['orders' => $orders]);
    }
}
