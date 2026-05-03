<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.customers.index', [
            'customers' => Customer::query()
                ->withCount('orders')
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->latest('updated_at')
                ->paginate(10),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Customers',
        ]);
    }
}
