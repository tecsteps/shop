<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public string $orderNumber;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    #[Computed]
    public function order(): Order
    {
        $customer = Auth::guard('customer')->user();
        $lookup = str_starts_with($this->orderNumber, '#')
            ? $this->orderNumber
            : '#'.$this->orderNumber;

        return $customer->orders()
            ->where('order_number', $lookup)
            ->with(['lines', 'payments', 'fulfillments'])
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show')
            ->layout('storefront.layouts.app', ['title' => 'Order '.$this->orderNumber]);
    }
}
