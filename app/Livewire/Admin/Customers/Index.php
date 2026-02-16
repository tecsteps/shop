<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Customers'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.customers.index', [
            'customers' => Customer::query()
                ->where('store_id', $store->id)
                ->withCount('orders')
                ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                ))
                ->latest()
                ->paginate(15),
        ]);
    }
}
