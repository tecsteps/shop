<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
#[Title('Order History')]
class Index extends Component
{
    use WithPagination;

    public function render(): \Illuminate\View\View
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $customer->store_id)
            ->where('customer_id', $customer->id)
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
