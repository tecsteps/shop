<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $customer->loadMissing('addresses');
        $this->customer = $customer;
    }

    public function render()
    {
        $orders = Order::query()
            ->where('customer_id', $this->customer->id)
            ->orderByDesc('placed_at')
            ->limit(50)
            ->get();

        return view('livewire.admin.customers.show', [
            'orders' => $orders,
        ]);
    }
}
