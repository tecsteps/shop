<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Order History')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
