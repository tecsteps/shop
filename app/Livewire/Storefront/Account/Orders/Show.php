<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore;

    public Order $order;

    public function mount(string $orderNumber): void
    {
        $this->ensureCurrentStore();

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $this->order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', '#'.$orderNumber)
            ->with('lines')
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show', [
            'order' => $this->order,
        ]);
    }
}
