<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use EnsuresStore, WithPagination;

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function render(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->paginate(15);

        return view('livewire.storefront.account.orders.index', [
            'orders' => $orders,
        ]);
    }
}
