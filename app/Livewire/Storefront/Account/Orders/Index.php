<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Paginated list of the authenticated customer's orders.
 */
#[Layout('storefront.layouts.app')]
#[Title('Order history')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $orders = Order::query()
            ->where('customer_id', Auth::guard('customer')->id())
            ->latest('placed_at')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
