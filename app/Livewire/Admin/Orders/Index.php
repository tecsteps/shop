<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Support\CurrencyFormatter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use CurrencyFormatter;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public string $sortField = 'placed_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function orders(): mixed
    {
        $query = Order::query()
            ->with('customer');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('financial_status', $this->statusFilter);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(15);
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.index')
            ->layout('layouts.admin', [
                'breadcrumbs' => [
                    ['label' => 'Orders'],
                ],
            ]);
    }
}
