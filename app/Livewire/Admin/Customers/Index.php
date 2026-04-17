<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        return view('livewire.admin.customers.index', [
            'customers' => $query->orderByDesc('created_at')->paginate(20),
        ]);
    }
}
