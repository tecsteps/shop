<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $query = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->withCount('orders');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $customers = $query->latest()->paginate(15);

        return view('livewire.admin.customers.index', [
            'customers' => $customers,
        ]);
    }
}
