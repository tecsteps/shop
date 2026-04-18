<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $number;

    public function mount(string $number): void
    {
        $this->number = $number;
    }

    public function render()
    {
        $customer = auth('customer')->user();

        $order = Order::query()
            ->where('order_number', $this->number)
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->with(['lines', 'fulfillments', 'payments'])
            ->first();

        if (! $order || ! $customer) {
            throw new NotFoundHttpException('Order not found');
        }

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
        ]);
    }
}
