<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getCustomersProperty()
    {
        $query = Customer::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->withCount('orders')
            ->withSum('orders', 'total_amount');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.customers.index', [
            'customers' => $this->customers,
        ]);
    }
}
