<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load('addresses');
    }

    /**
     * @return LengthAwarePaginator<Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_id', $this->customer->id)
            ->orderByDesc('placed_at')
            ->paginate(10);
    }

    #[Computed]
    public function totalOrders(): int
    {
        return Order::where('customer_id', $this->customer->id)->count();
    }

    #[Computed]
    public function totalSpent(): int
    {
        return (int) Order::where('customer_id', $this->customer->id)->sum('total_amount');
    }

    public function render()
    {
        return view('livewire.admin.customers.show')
            ->layout('layouts.admin', ['title' => $this->customer->name ?? 'Customer']);
    }
}
