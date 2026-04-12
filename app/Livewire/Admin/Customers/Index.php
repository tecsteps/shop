<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 20;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($query): void {
                    $query->where('email', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.customers.index', [
            'customers' => $customers,
        ]);
    }
}
