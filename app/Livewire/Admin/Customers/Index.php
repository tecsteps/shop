<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('orders as lifetime_value_amount', 'total_amount')
            ->when($this->search !== '', fn ($q) => $q->where('email', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.admin.customers.index', compact('customers'))->title('Customers');
    }
}
