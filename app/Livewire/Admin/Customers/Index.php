<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, FormatsMoney, WithPagination;

    #[Layout('layouts.admin.app')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        $query = Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount');

        if (trim($this->search) !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhere('email', 'like', '%'.trim($this->search).'%');
            });
        }

        return $query->latest()->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.customers.index');
    }
}
