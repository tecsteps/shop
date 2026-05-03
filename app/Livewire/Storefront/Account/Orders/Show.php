<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $orderNumber;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function render(): View
    {
        return view('livewire.storefront.account.orders.show', [
            'order' => $this->order(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Order '.$this->normalizedOrderNumber(),
        ]);
    }

    private function order(): Order
    {
        return Order::query()
            ->with('lines', 'payments', 'fulfillments.lines')
            ->where('customer_id', $this->customer()->id)
            ->where('order_number', $this->normalizedOrderNumber())
            ->firstOrFail();
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function normalizedOrderNumber(): string
    {
        return str_starts_with($this->orderNumber, '#') ? $this->orderNumber : '#'.$this->orderNumber;
    }
}
