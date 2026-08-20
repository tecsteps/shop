<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Component;

class Index extends Component
{
    public string $status = 'all';

    public function render(): mixed
    {
        $orders = Order::query()->with('customer')->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))->latest('placed_at')->paginate(15);

        return view('livewire.admin.orders.index', compact('orders'))->layout('layouts.admin');
    }
}
