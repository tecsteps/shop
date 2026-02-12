<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Customer Details')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $store = app('current_store');

        abort_unless($customer->store_id === $store->id, 404);

        $this->customer = $customer;
    }

    public function render(): View
    {
        $this->customer->load('addresses');

        $recentOrders = $this->customer->orders()
            ->latest()
            ->limit(10)
            ->get();

        $ordersCount = $this->customer->orders()->count();
        $totalSpent = (int) $this->customer->orders()->sum('total_amount');

        return view('livewire.admin.customers.show', [
            'recentOrders' => $recentOrders,
            'ordersCount' => $ordersCount,
            'totalSpent' => $totalSpent,
        ]);
    }
}
