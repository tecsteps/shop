<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->where('customer_id', $customer->getKey())
            ->orderByDesc('placed_at')
            ->get();

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
