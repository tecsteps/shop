<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = 'all';

    public function render()
    {
        $orders = Order::query()
            ->when($this->status !== 'all', fn ($q) => $q->where('financial_status', $this->status))
            ->orderByDesc('placed_at')
            ->paginate(20);

        return view('livewire.admin.orders.index', compact('orders'))->title('Orders');
    }
}
