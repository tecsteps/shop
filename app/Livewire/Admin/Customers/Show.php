<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load(['addresses', 'orders' => fn ($q) => $q->latest()->limit(20)]);
    }

    public function render(): View
    {
        $orders = $this->customer->orders;

        $stats = [
            'orders_count' => $orders->count(),
            'total_spent' => (int) $orders->sum('total_amount'),
            'average' => $orders->count() > 0 ? (int) round($orders->sum('total_amount') / $orders->count()) : 0,
        ];

        return view('livewire.admin.customers.show', [
            'stats' => $stats,
        ]);
    }
}
