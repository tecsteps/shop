<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $orderNumber = '';

    public ?Order $order = null;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->where('customer_id', $customer->getKey())
            ->where('order_number', $orderNumber)
            ->with('lines')
            ->first();

        if ($order === null) {
            abort(404);
        }

        $this->order = $order;
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show');
    }
}
