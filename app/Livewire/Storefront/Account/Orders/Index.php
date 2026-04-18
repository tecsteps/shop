<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $customer = auth('customer')->user();

        $orders = $customer
            ? Order::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('placed_at')
                ->paginate(15)
            : new LengthAwarePaginator([], 0, 15, 1);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
