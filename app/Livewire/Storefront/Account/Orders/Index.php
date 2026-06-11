<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::storefront')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $orders = $customer->orders()
            ->latest('placed_at')
            ->latest('id')
            ->paginate(10);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ])->title(__('Order History'));
    }
}
