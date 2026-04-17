<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function render(): View
    {
        $this->customer->load(['orders' => function (\Illuminate\Database\Eloquent\Relations\HasMany $query): void {
            $query->latest('placed_at')->limit(10);
        }, 'addresses']);

        return view('livewire.admin.customers.show');
    }
}
