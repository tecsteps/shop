<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    use AuthorizesRequests;

    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', Customer::class);
        $this->customer = $customer;
    }

    public function render(): View
    {
        $recentOrders = Order::query()
            ->where('customer_id', $this->customer->id)
            ->latest('placed_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.customers.show', [
            'recentOrders' => $recentOrders,
        ]);
    }
}
